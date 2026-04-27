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
 * Unit tests for the attempt_fetcher class.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio;

/**
 * Test case for attempt_fetcher private methods via reflection.
 *
 * @covers \local_proctorio\attempt_fetcher
 */
class attempt_fetcher_test extends \advanced_testcase {

    /** @var \ReflectionClass */
    private \ReflectionClass $reflection;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->reflection = new \ReflectionClass(attempt_fetcher::class);
    }

    /**
     * Call a private static method on attempt_fetcher.
     */
    private function call(string $method, mixed ...$args): mixed {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke(null, ...$args);
    }

    // -------------------------------------------------------------------------
    // Parse_quiz_configurations.

    public function test_parse_quiz_configurations_returns_null_for_empty_config(): void {
        $result = $this->call('parse_quiz_configurations', '', 'quiz');
        $this->assertNull($result);
    }

    public function test_parse_quiz_configurations_returns_matching_query(): void {
        $sql = 'SELECT id, status AS attempt_status, attempt AS attempt_number'
             . ' FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid'
             . ' ORDER BY timemodified DESC LIMIT 1';
        $config = "customquiz|$sql";

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertSame($sql, $result);
    }

    public function test_parse_quiz_configurations_returns_null_when_no_match(): void {
        $config = 'customquiz|SELECT id, status AS attempt_status, attempt AS attempt_number'
                . ' FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid LIMIT 1';

        $result = $this->call('parse_quiz_configurations', $config, 'otherquiz');
        $this->assertNull($result);
    }

    public function test_parse_quiz_configurations_skips_comment_lines(): void {
        $config = "# This is a comment\n"
                . "// Another comment\n"
                . 'customquiz|SELECT id, status AS attempt_status, attempt AS attempt_number'
                . ' FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid LIMIT 1';

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertNotNull($result);
    }

    public function test_parse_quiz_configurations_skips_empty_lines(): void {
        $sql = 'SELECT id, status AS attempt_status, attempt AS attempt_number'
             . ' FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid LIMIT 1';
        $config = "\n\n   \ncustomquiz|$sql";

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertSame($sql, $result);
    }

    public function test_parse_quiz_configurations_skips_lines_without_pipe(): void {
        $config = "invalidsyntaxnopipe\n"
                . 'customquiz|SELECT id, status AS attempt_status, attempt AS attempt_number'
                . ' FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid LIMIT 1';

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertNotNull($result);
    }

    public function test_parse_quiz_configurations_skips_query_missing_userid_placeholder(): void {
        $config = 'customquiz|SELECT id, status AS attempt_status, attempt AS attempt_number'
                . ' FROM {customquiz_attempts} WHERE quizid = :quizid LIMIT 1';

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertNull($result);
    }

    public function test_parse_quiz_configurations_skips_query_missing_quizid_placeholder(): void {
        $config = 'customquiz|SELECT id, status AS attempt_status, attempt AS attempt_number'
                . ' FROM {customquiz_attempts} WHERE userid = :userid LIMIT 1';

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertNull($result);
    }

    public function test_parse_quiz_configurations_skips_query_missing_attempt_status_alias(): void {
        $config = 'customquiz|SELECT id, attempt AS attempt_number'
                . ' FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid LIMIT 1';

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertNull($result);
    }

    public function test_parse_quiz_configurations_skips_query_missing_attempt_number_alias(): void {
        $config = 'customquiz|SELECT id, status AS attempt_status'
                . ' FROM {customquiz_attempts} WHERE userid = :userid AND quizid = :quizid LIMIT 1';

        $result = $this->call('parse_quiz_configurations', $config, 'customquiz');
        $this->assertNull($result);
    }

    public function test_parse_quiz_configurations_matches_first_of_multiple_modules(): void {
        $sql1 = 'SELECT id, s AS attempt_status, n AS attempt_number FROM {t1}'
              . ' WHERE userid = :userid AND quizid = :quizid LIMIT 1';
        $sql2 = 'SELECT id, s AS attempt_status, n AS attempt_number FROM {t2}'
              . ' WHERE userid = :userid AND quizid = :quizid LIMIT 1';
        $config = "mod1|$sql1\nmod2|$sql2";

        $this->assertSame($sql1, $this->call('parse_quiz_configurations', $config, 'mod1'));
        $this->assertSame($sql2, $this->call('parse_quiz_configurations', $config, 'mod2'));
    }

    // -------------------------------------------------------------------------
    // Process_query_placeholders.

    public function test_process_query_placeholders_single_occurrences(): void {
        $sql    = 'SELECT * FROM {t} WHERE userid = :userid AND quizid = :quizid LIMIT 1';
        $result = $this->call('process_query_placeholders', $sql);

        $this->assertStringContainsString(':userid0', $result);
        $this->assertStringContainsString(':quizid0', $result);
        $this->assertStringNotContainsString(':userid ', $result);
        $this->assertStringNotContainsString(':quizid ', $result);
    }

    public function test_process_query_placeholders_multiple_occurrences(): void {
        $sql = 'SELECT (SELECT COUNT(*) FROM {t} WHERE userid = :userid AND quizid = :quizid) AS n'
             . ' FROM {t} WHERE userid = :userid AND quizid = :quizid LIMIT 1';

        $result = $this->call('process_query_placeholders', $sql);

        $this->assertStringContainsString(':userid0', $result);
        $this->assertStringContainsString(':userid1', $result);
        $this->assertStringContainsString(':quizid0', $result);
        $this->assertStringContainsString(':quizid1', $result);
    }

    // -------------------------------------------------------------------------
    // Build_params_for_query.

    public function test_build_params_for_query_single_placeholders(): void {
        $sql    = 'SELECT * FROM {t} WHERE userid = :userid0 AND quizid = :quizid0';
        $params = $this->call('build_params_for_query', $sql, 42, 99);

        $this->assertSame(['userid0' => 42, 'quizid0' => 99], $params);
    }

    public function test_build_params_for_query_multiple_placeholders(): void {
        $sql = 'SELECT * FROM {t} WHERE userid = :userid0 AND quizid = :quizid0'
             . ' AND userid = :userid1 AND quizid = :quizid1';

        $params = $this->call('build_params_for_query', $sql, 7, 13);

        $this->assertSame([
            'userid0' => 7,
            'userid1' => 7,
            'quizid0' => 13,
            'quizid1' => 13,
        ], $params);
    }

    public function test_build_params_for_query_empty_returns_empty_array(): void {
        $params = $this->call('build_params_for_query', 'SELECT 1', 1, 1);
        $this->assertSame([], $params);
    }

    // -------------------------------------------------------------------------
    // Execute_custom_query.

    public function test_execute_custom_query_returns_null_when_no_record_found(): void {
        $sql = "SELECT id, 'finished' AS attempt_status, 1 AS attempt_number"
             . " FROM {user} WHERE id = :userid AND id = :quizid LIMIT 1";

        // ID 0 never exists in the user table.
        $result = $this->call('execute_custom_query', $sql, 0, 0);
        $this->assertNull($result);
    }

    public function test_execute_custom_query_returns_attempt_data_when_record_found(): void {
        $user = $this->getDataGenerator()->create_user();

        $sql = "SELECT id, 'inprogress' AS attempt_status, 3 AS attempt_number"
             . " FROM {user} WHERE id = :userid AND id != :quizid LIMIT 1";

        $result = $this->call('execute_custom_query', $sql, $user->id, 0);

        $this->assertIsArray($result);
        $this->assertEquals('inprogress', $result['attempt_status']);
        $this->assertEquals(3, $result['attempt_number']);
    }

    public function test_execute_custom_query_returns_null_on_failed_query(): void {
        $sql = 'SELECT id, state AS attempt_status, attempt AS attempt_number'
             . ' FROM {this_table_does_not_exist} WHERE id = :userid AND id = :quizid LIMIT 1';

        $result = $this->call('execute_custom_query', $sql, 1, 1);
        $this->assertNull($result);
    }

    public function test_execute_custom_query_returns_null_when_aliases_missing_from_result(): void {
        $user = $this->getDataGenerator()->create_user();

        // Query returns a row but without the required attempt_status / attempt_number aliases.
        $sql = "SELECT id FROM {user} WHERE id = :userid AND :quizid >= 0 LIMIT 1";

        $result = $this->call('execute_custom_query', $sql, $user->id, 0);
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // Get_attempt_by_module.

    public function test_get_attempt_by_module_quiz_returns_null_when_no_attempt(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $result = $this->call('get_attempt_by_module', 'quiz', $user->id, $quiz->id);
        $this->assertNull($result);
    }

    public function test_get_attempt_by_module_quiz_returns_attempt_data(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_quiz_attempt($quiz->id, $quiz->cmid, $user->id, 'finished', 1);

        $result = $this->call('get_attempt_by_module', 'quiz', $user->id, $quiz->id);

        $this->assertIsArray($result);
        $this->assertEquals('finished', $result['attempt_status']);
        $this->assertEquals(1, $result['attempt_number']);
    }

    public function test_get_attempt_by_module_quiz_returns_most_recent_of_multiple_attempts(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_quiz_attempt($quiz->id, $quiz->cmid, $user->id, 'finished', 1);
        $this->insert_quiz_attempt($quiz->id, $quiz->cmid, $user->id, 'inprogress', 2);

        $result = $this->call('get_attempt_by_module', 'quiz', $user->id, $quiz->id);

        $this->assertIsArray($result);
        $this->assertEquals('inprogress', $result['attempt_status']);
        $this->assertEquals(2, $result['attempt_number']);
    }

    public function test_get_attempt_by_module_unknown_module_returns_null_when_table_absent(): void {
        $result = $this->call('get_attempt_by_module', 'nonexistentmodule', 1, 1);
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // Get_last_attempt (public API).

    public function test_get_last_attempt_returns_null_when_no_attempt_exists(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $result = attempt_fetcher::get_last_attempt($user->id, $quiz->cmid);
        $this->assertNull($result);
    }

    public function test_get_last_attempt_returns_attempt_data_for_standard_quiz(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $this->insert_quiz_attempt($quiz->id, $quiz->cmid, $user->id, 'finished', 2);

        $result = attempt_fetcher::get_last_attempt($user->id, $quiz->cmid);

        $this->assertIsArray($result);
        $this->assertEquals('finished', $result['attempt_status']);
        $this->assertEquals(2, $result['attempt_number']);
    }

    public function test_get_last_attempt_routes_through_custom_sql_when_configured(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz   = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $user   = $this->getDataGenerator()->create_user();

        $sql = "SELECT id, 'submitted' AS attempt_status, 7 AS attempt_number"
             . " FROM {user} WHERE id = :userid AND :quizid >= 0 LIMIT 1";
        set_config('quiz_configurations', "quiz|$sql", 'local_proctorio');

        $result = attempt_fetcher::get_last_attempt($user->id, $quiz->cmid);

        $this->assertIsArray($result);
        $this->assertEquals('submitted', $result['attempt_status']);
        $this->assertEquals(7, $result['attempt_number']);
    }

    // -------------------------------------------------------------------------
    // Helpers.

    /**
     * Insert a minimal quiz_attempts row using a real question_usage for the uniqueid FK.
     */
    private function insert_quiz_attempt(
        int $quizid,
        int $cmid,
        int $userid,
        string $state = 'finished',
        int $attempt = 1
    ): void {
        global $DB;

        $context = \context_module::instance($cmid);
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $context);
        $quba->set_preferred_behaviour('deferredfeedback');
        \question_engine::save_questions_usage_by_activity($quba);

        $DB->insert_record('quiz_attempts', (object)[
            'quiz'                => $quizid,
            'userid'              => $userid,
            'attempt'             => $attempt,
            'uniqueid'            => $quba->get_id(),
            'layout'              => '',
            'currentpage'         => 0,
            'preview'             => 0,
            'state'               => $state,
            'timestart'           => time(),
            'timefinish'          => 0,
            'timemodified'        => time(),
            'timemodifiedoffline' => 0,
        ]);
    }
}
