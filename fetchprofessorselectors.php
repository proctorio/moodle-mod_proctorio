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
require_once('lib.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/proctorio/fetchprofessorselectors.php'));
$PAGE->set_heading("proctorio professor selectors");

if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && isloggedin()) {
    try {
        $data = local_proctorio_fetch_selectors('professor');
        $responsedata = array_values($data);
        header("Content-Type: application/json");
        echo json_encode($responsedata);
        http_response_code(200);
    } catch (Exception $e) {
        header("Content-type: application/json");
        $code = $e->getCode();
        http_response_code($code);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
} else {
    $redirecturl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : new moodle_url('/');
    redirect($redirecturl);
}
