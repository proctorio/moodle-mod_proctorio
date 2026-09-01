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
 * External function for fetching a course's enrolled roster.
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
 * External function for fetching a course's enrolled roster.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_course_roster extends \external_api {

    /**
     * Parameter definition for execute().
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course id'),
        ]);
    }

    /**
     * Get the enrolled roster for the given course.
     *
     * @param int $courseid Course id
     * @return array[]
     */
    public static function execute(int $courseid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);

        $course = $DB->get_record('course', ['id' => $params['courseid']]);
        if (!$course) {
            throw new \moodle_exception('coursenotfound', 'local_proctorio');
        }

        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('local/proctorio:viewroster', $context);

        // Re-checks the capability above against the course context and applies
        // group/email visibility rules.
        return local_proctorio_get_course_roster($course);
    }

    /**
     * Return definition for execute().
     *
     * @return \external_multiple_structure
     */
    public static function execute_returns(): \external_multiple_structure {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'User id'),
                'fullname' => new \external_value(PARAM_NOTAGS, 'User full name'),
                'email' => new \external_value(
                    PARAM_RAW, 'User email address, when visible to the caller', VALUE_OPTIONAL, null, NULL_ALLOWED
                ),
            ])
        );
    }
}
