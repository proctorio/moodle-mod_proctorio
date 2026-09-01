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
 * External function for fetching the current user's last quiz attempt status.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/proctorio/lib.php');

/**
 * External function for fetching the current user's last quiz attempt status.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_attempt_info extends \external_api {

    /**
     * Parameter definition for execute().
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module id of the quiz-like activity'),
        ]);
    }

    /**
     * Get the current user's last attempt for the given quiz-like course module.
     *
     * @param int $cmid Course module id of the quiz-like activity
     * @return array
     */
    public static function execute(int $cmid): array {
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);

        $cm = get_coursemodule_from_id(null, $params['cmid'], 0, false, MUST_EXIST);
        $modcontext = \context_module::instance($cm->id);
        self::validate_context($modcontext);
        require_capability('local/proctorio:viewattemptdata', $modcontext);

        // Enforces enrolment/visibility for the current user and re-checks the
        // capability above against the resolved module context.
        $attempt = local_proctorio_get_attempt_info($params['cmid']);

        return [
            'found' => $attempt !== null,
            'attempt_status' => $attempt['attempt_status'] ?? null,
            'attempt_number' => $attempt['attempt_number'] ?? null,
        ];
    }

    /**
     * Return definition for execute().
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'found' => new \external_value(PARAM_BOOL, 'Whether an attempt was found'),
            'attempt_status' => new \external_value(
                PARAM_RAW, 'Attempt status', VALUE_OPTIONAL, null, NULL_ALLOWED
            ),
            'attempt_number' => new \external_value(
                PARAM_RAW, 'Attempt number', VALUE_OPTIONAL, null, NULL_ALLOWED
            ),
        ]);
    }
}
