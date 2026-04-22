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
 * AJAX endpoint returning enrolled course roster as JSON.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once('lib.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/proctorio/users.php'));
$PAGE->set_heading(get_string('pluginname', 'local_proctorio'));

// Headers for JSON response.
header('Content-Type: application/json');

try {
    global $DB;

    if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' || !isloggedin()) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'You must be loggedin.',
        ]);
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Only GET method is allowed.',
        ]);
        exit;
    }

    $courseid = required_param('courseid', PARAM_INT);
    $course = $DB->get_record('course', ['id' => $courseid]);

    if (!$course) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Course not found.']);
        exit;
    }

    $context = context_course::instance($courseid);
    require_capability('moodle/course:viewparticipants', $context);

    $users = get_enrolled_users($context);

    if (empty($users)) {
        // No users found.
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'No users enrolled in this course.',
            'data' => [],
        ]);
        exit;
    }

    $roster = [];
    foreach ($users as $user) {
        $roster[] = [
            'id' => $user->id,
            'fullname' => fullname($user),
            'email' => $user->email,
        ];
    }
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Enrolled users fetched successfully.',
        'data' => $roster,
    ]);
    exit;

} catch (moodle_exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
}
