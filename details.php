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
 * AJAX endpoint returning plugin and Moodle version information as JSON.
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
$PAGE->set_url(new moodle_url('/local/proctorio/details.php'));
$PAGE->set_heading(get_string('pluginname', 'local_proctorio'));

$pluginversionfile = __DIR__ . '/version.php';

if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && isloggedin()) {
    try {
        if (file_exists($pluginversionfile)) {
            $plugin = new stdClass();
            include($pluginversionfile);

            $details = [
                'pluginversion' => $plugin->release ?? null,
                'moodleversion' => $CFG->release ?? null,
            ];

            header("Content-Type: application/json");
            echo json_encode($details);
            http_response_code(200);
        } else {
            header("Content-type: application/json");
            echo json_encode(['error' => "Version file doesn't exist"]);
            http_response_code(404);
        }
    } catch (Exception $e) {
        header("Content-type: application/json");
        $code = $e->getCode();
        echo json_encode(['error' => $e->getMessage()]);
        http_response_code($code);
    }
    exit;
} else {
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $redirecturl = (strpos($referer, $CFG->wwwroot) === 0) ? $referer : new moodle_url('/');
    redirect($redirecturl);
}
