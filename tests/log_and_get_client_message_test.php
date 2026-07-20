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
 * Tests for local_proctorio_log_and_get_client_message() (END-01 remediation).
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * @covers ::local_proctorio_log_and_get_client_message
 */
class log_and_get_client_message_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_returns_generic_message_never_the_original(): void {
        $sensitive = 'SELECT password FROM mdl_user WHERE id = 42 -- schema leak';
        $exception = new \Exception($sensitive);

        $message = local_proctorio_log_and_get_client_message($exception, 'phpunit');

        $this->assertStringNotContainsString('password', $message);
        $this->assertStringNotContainsString('mdl_user', $message);
        $this->assertStringNotContainsString($sensitive, $message);
        $this->assertSame('An error occurred while processing your request.', $message);
    }

    public function test_returns_same_generic_message_for_any_exception_type(): void {
        $message = local_proctorio_log_and_get_client_message(new \Exception('anything internal'), 'phpunit');

        $this->assertSame('An error occurred while processing your request.', $message);
    }
}
