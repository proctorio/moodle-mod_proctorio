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

defined('MOODLE_INTERNAL') || die();

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
