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
 * External service (web service / AJAX) function definitions.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_proctorio_get_attempt_info' => [
        'classname'     => 'local_proctorio\external\get_attempt_info',
        'methodname'    => 'execute',
        'description'   => 'Get the current user\'s last attempt status for a quiz-like course module.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/proctorio:viewattemptdata',
    ],
    'local_proctorio_get_course_roster' => [
        'classname'     => 'local_proctorio\external\get_course_roster',
        'methodname'    => 'execute',
        'description'   => 'Get the enrolled roster for a course.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/proctorio:viewroster',
    ],
    'local_proctorio_get_selectors' => [
        'classname'     => 'local_proctorio\external\get_selectors',
        'methodname'    => 'execute',
        'description'   => 'Get the plugin\'s CSS selector configuration for the requested audience.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/proctorio:viewselectors',
    ],
    'local_proctorio_get_plugin_details' => [
        'classname'     => 'local_proctorio\external\get_plugin_details',
        'methodname'    => 'execute',
        'description'   => 'Get the plugin and Moodle version details.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'local/proctorio:viewselectors',
    ],
];
