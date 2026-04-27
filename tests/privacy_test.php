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
 * Privacy provider tests for the Proctorio local plugin.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio;

defined('MOODLE_INTERNAL') || die();

use local_proctorio\privacy\provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

/**
 * Tests for the privacy provider.
 *
 * @covers \local_proctorio\privacy\provider
 */
class privacy_test extends \core_privacy\tests\provider_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_metadata_declares_external_proctorio_link(): void {
        $collection = new collection('local_proctorio');
        $result = provider::get_metadata($collection);

        $this->assertInstanceOf(collection::class, $result);
        $this->assertNotEmpty($result->get_collection());
    }

    public function test_get_contexts_for_userid_returns_empty_contextlist(): void {
        $user = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($user->id);

        $this->assertCount(0, $contextlist);
    }

    public function test_get_users_in_context_returns_empty_userlist(): void {
        $context  = \context_system::instance();
        $userlist = new userlist($context, 'local_proctorio');

        provider::get_users_in_context($userlist);

        $this->assertCount(0, $userlist);
    }

    public function test_export_user_data_does_nothing(): void {
        $user         = $this->getDataGenerator()->create_user();
        $approvedlist = new approved_contextlist($user, 'local_proctorio', []);

        // Must not throw.
        provider::export_user_data($approvedlist);
        $this->assertTrue(true);
    }

    public function test_delete_data_for_all_users_in_context_does_nothing(): void {
        // Must not throw.
        provider::delete_data_for_all_users_in_context(\context_system::instance());
        $this->assertTrue(true);
    }

    public function test_delete_data_for_user_does_nothing(): void {
        $user         = $this->getDataGenerator()->create_user();
        $approvedlist = new approved_contextlist($user, 'local_proctorio', []);

        // Must not throw.
        provider::delete_data_for_user($approvedlist);
        $this->assertTrue(true);
    }

    public function test_delete_data_for_users_does_nothing(): void {
        $context      = \context_system::instance();
        $approvedlist = new approved_userlist($context, 'local_proctorio', []);

        // Must not throw.
        provider::delete_data_for_users($approvedlist);
        $this->assertTrue(true);
    }
}
