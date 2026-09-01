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
 * External function for fetching plugin and Moodle version details.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * External function for fetching plugin and Moodle version details.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_plugin_details extends \external_api {

    /**
     * Parameter definition for execute().
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([]);
    }

    /**
     * Get the plugin release and Moodle release strings.
     *
     * @return array
     */
    public static function execute(): array {
        global $CFG;

        self::validate_parameters(self::execute_parameters(), []);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/proctorio:viewselectors', $context);

        $plugin = new \stdClass();
        $pluginversionfile = $CFG->dirroot . '/local/proctorio/version.php';
        if (file_exists($pluginversionfile)) {
            include($pluginversionfile);
        }

        return [
            'pluginversion' => $plugin->release ?? null,
            'moodleversion' => $CFG->release ?? null,
        ];
    }

    /**
     * Return definition for execute().
     *
     * @return \external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'pluginversion' => new \external_value(
                PARAM_RAW, 'Plugin release version', VALUE_OPTIONAL, null, NULL_ALLOWED
            ),
            'moodleversion' => new \external_value(
                PARAM_RAW, 'Moodle release version', VALUE_OPTIONAL, null, NULL_ALLOWED
            ),
        ]);
    }
}
