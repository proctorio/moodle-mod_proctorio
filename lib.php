<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Core library functions for the Proctorio local plugin.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Log a caught exception's real message server-side and return a generic message that
 * is safe to send back to an HTTP client.
 *
 * Exception messages (especially from dml_exception) can carry SQL, table, or schema
 * details that have no business leaving the server (END-01). The real message still goes
 * to the server's error log - visible to admins with server access, never to the caller.
 *
 * @param \Throwable $e The caught exception
 * @param string $endpoint Short label identifying which endpoint logged this, for grepping
 * @return string Generic, client-safe message
 */
function local_proctorio_log_and_get_client_message(\Throwable $e, string $endpoint): string {
    error_log("local_proctorio ({$endpoint}): " . $e->getMessage());

    return 'An error occurred while processing your request.';
}

/**
 * Build the enrolled-user roster for a course, enforcing the plugin's own
 * authorization rules rather than a generic core capability.
 *
 * Requires local/proctorio:viewroster in the course context, restricts the
 * result to the caller's own groups when the course uses separate groups
 * and the caller lacks moodle/site:accessallgroups, and only reveals a
 * user's email address when Moodle's own visibility rules for that field
 * (maildisplay, or moodle/course:useremail) permit it.
 *
 * @param stdClass $course Full course record (must include groupmode/groupmodeforce).
 * @return array[] List of ['id' => int, 'fullname' => string, 'email' => string|null].
 */
function local_proctorio_get_course_roster(stdClass $course): array {
    global $USER;

    $context = context_course::instance($course->id);
    require_capability('local/proctorio:viewroster', $context);

    $userfields = 'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, '
        . 'u.middlename, u.alternatename, u.email, u.maildisplay';

    $groupmode = groups_get_course_groupmode($course);
    if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
        $users = [];
        foreach (array_keys(groups_get_all_groups($course->id, $USER->id)) as $groupid) {
            // Union by key: a user in several of the caller's groups must only appear once.
            $users += get_enrolled_users($context, '', $groupid, $userfields);
        }
    } else {
        $users = get_enrolled_users($context, '', 0, $userfields);
    }

    $canseeemail = has_capability('moodle/course:useremail', $context);

    $roster = [];
    foreach ($users as $user) {
        // Maildisplay 0 = hidden from everyone but the user themself and staff with the capability above.
        $showemail = $canseeemail || (int)$user->id === (int)$USER->id || (int)$user->maildisplay !== 0;

        $roster[] = [
            'id' => (int)$user->id,
            'fullname' => fullname($user),
            'email' => $showemail ? $user->email : null,
        ];
    }

    return $roster;
}

/**
 * Resolve the current user's last attempt for a quiz-like course module.
 *
 * Enforces enrolment and activity visibility (require_login with $cm) and the plugin's
 * own local/proctorio:viewattemptdata capability before ever calling attempt_fetcher.
 * The module name is deliberately never taken from a caller - see SEG-03 - it is always
 * resolved from the course module itself by attempt_fetcher::get_last_attempt().
 *
 * @param int $cmid Course module ID of the quiz-like activity
 * @return array|null Attempt data (attempt_status, attempt_number), or null if none found
 */
function local_proctorio_get_attempt_info(int $cmid): ?array {
    global $USER;

    $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
    $course = get_course($cm->course);

    // Enforces enrolment and activity visibility for the current user. This is an API
    // function with no page of its own to redirect to, so failures must throw rather
    // than attempt a header redirect.
    require_login($course, false, $cm, true, true);

    $modcontext = context_module::instance($cm->id);
    require_capability('local/proctorio:viewattemptdata', $modcontext);

    return \local_proctorio\attempt_fetcher::get_last_attempt($USER->id, $cmid);
}

/**
 * Fetch all candidate selectors.
 *
 * @param string $type Type of configuration - student/professor.
 * @return stdClass[] Array of candidate selector objects, keyed by their IDs.
 */
function local_proctorio_fetch_selectors($type) {
    if ($type !== 'student' && $type !== 'professor') {
        throw new moodle_exception('invalidtype', 'local_proctorio', '', $type);
    }

    $all = get_config("local_proctorio");
    $selectors = new stdClass();
    $start = $type == "student" ? 8 : 10;
    // Filter only candidate selectors.
    foreach ($all as $key => $value) {
        if (strpos($key, "{$type}_") === 0 && !empty($value)) {
            $formatedkey = substr($key, $start);
            $selectors->$formatedkey = $value;
        }
    }
    $pluginversionfile = __DIR__ . '/version.php';

    if (file_exists($pluginversionfile) && !empty(get_object_vars($selectors))) {
        $plugin = new stdClass();
        include($pluginversionfile);

        // Insert plugin version.
        $selectors->version = $plugin->release;
    }
    return [$selectors];
}

/**
 * Adds a group of selector fields (as textarea settings) to a Moodle admin settings page.
 *
 * This function is used to dynamically add multiple related textarea inputs
 * under a specific heading, such as "Candidate Selectors" or "Professor Selectors",
 * to the plugin's settings page. Each field is saved under the Moodle config
 * using the format: local_myplugin/{prefix}_{key}.
 *
 * @param admin_settingpage $settings The admin settings page object to add settings to.
 * @param string $prefix A short identifier (e.g. 'student', 'professor') used to group the setting keys.
 * @param string $title The heading title displayed above the group of fields.
 * @param string $description Optional description shown under the heading.
 * @param array $fields An associative array of keys and labels. Each key becomes part of the config name,
 *                      and each value is used as the label shown in the settings UI.
 *
 * @return void
 */
function local_proctorio_add_selector_group($settings, $prefix, $title, $description, $fields) {
    $settings->add(new admin_setting_heading(
        "local_proctorio/{$prefix}_heading",
        $title,
        $description
    ));

    foreach ($fields as $key => $value) {
        $label = get_string($key, "local_proctorio");

        $helptext = get_string($key."_help", 'local_proctorio');

        $setting = new admin_setting_configtextarea(
            "local_proctorio/{$prefix}_{$key}",
            $label,
            $helptext,
            $value,
            PARAM_RAW,
            "80",
            '2'
        );

        $settings->add($setting);

    }
}
