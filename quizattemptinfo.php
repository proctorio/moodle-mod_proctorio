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
 * Contains user badge class for displaying a badge issued to a user.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/local/proctorio/classes/attempt_fetcher.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/proctorio/quizattemptinfo.php'));
$PAGE->set_heading("quizinfo");

// Headers for JSON response.
header('Content-Type: application/json');

try {
    // Fetch parameters.
    global $USER, $DB;
    $userid = $USER->id;
    $quizid  = required_param('cmid', PARAM_INT);
    $modname = optional_param('modname', '', PARAM_PLUGIN);

    if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' || !isloggedin()) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'You must be loggedin.',
        ]);
        exit;
    }

    // Validate user exists.
    if (!core_user::is_real_user($userid)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid user',
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

    // Use attempt_fetcher class to get data.
    $attempt = \local_proctorio\attempt_fetcher::get_last_attempt($userid, $quizid, $modname);

    if (!$attempt) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'No attempt found']);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'status' => "success",
        'data' => $attempt,
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
