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
 * External function for fetching the plugin's CSS selector configuration.
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
 * External function for fetching the plugin's CSS selector configuration.
 *
 * The selector set is admin-configured, global, and not tied to any course or
 * user, so it is gated by the system-level local/proctorio:viewselectors
 * capability rather than a course/module capability (see db/access.php).
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_selectors extends \external_api {

    /**
     * Parameter definition for execute().
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'type' => new \external_value(PARAM_ALPHA, 'Selector audience: "student" or "professor"'),
        ]);
    }

    /**
     * Get the CSS selector configuration for the requested audience.
     *
     * @param string $type Selector audience: "student" or "professor"
     * @return string JSON-encoded selector configuration, matching the plugin's legacy AJAX shape
     */
    public static function execute(string $type): string {
        $params = self::validate_parameters(self::execute_parameters(), ['type' => $type]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/proctorio:viewselectors', $context);

        // Throws moodle_exception('invalidtype') for anything other than student/professor.
        $data = local_proctorio_fetch_selectors($params['type']);

        return json_encode(array_values($data));
    }

    /**
     * Return definition for execute().
     *
     * @return \external_value
     */
    public static function execute_returns(): \external_value {
        return new \external_value(PARAM_RAW, 'JSON-encoded selector configuration');
    }
}
