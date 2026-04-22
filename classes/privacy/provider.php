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
 * Privacy API provider for the Proctorio local plugin.
 *
 * This plugin does not store personal data in its own tables. It acts as an
 * API layer that forwards existing Moodle data (user profiles, quiz attempts)
 * to the Proctorio browser extension on request. The metadata provider declares
 * that external data sharing with Proctorio takes place.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider declaring external data sharing with Proctorio.
 *
 * @package    local_proctorio
 * @copyright  2025 Proctorio <support@proctorio.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Declare personal data shared with the external Proctorio service.
     *
     * @param collection $collection Metadata collection to populate.
     * @return collection Updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'proctorio',
            [
                'userid'        => 'privacy:metadata:proctorio:userid',
                'fullname'      => 'privacy:metadata:proctorio:fullname',
                'email'         => 'privacy:metadata:proctorio:email',
                'attemptstate'  => 'privacy:metadata:proctorio:attemptstate',
                'attemptnumber' => 'privacy:metadata:proctorio:attemptnumber',
            ],
            'privacy:metadata:proctorio'
        );
        return $collection;
    }

    /**
     * This plugin stores no personal data of its own.
     *
     * @param int $userid The user ID to retrieve contexts for.
     * @return contextlist Empty context list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * This plugin stores no personal data of its own.
     *
     * @param \context $context The context to retrieve users for.
     * @param userlist $userlist The user list to populate.
     */
    public static function get_users_in_context(userlist $userlist): void {
    }

    /**
     * This plugin stores no personal data; nothing to export.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
    }

    /**
     * This plugin stores no personal data; nothing to delete.
     *
     * @param \context $context The context to delete data in.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
    }

    /**
     * This plugin stores no personal data; nothing to delete.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
    }

    /**
     * This plugin stores no personal data; nothing to delete.
     *
     * @param approved_userlist $userlist Approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
    }
}
