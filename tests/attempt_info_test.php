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
 * Tests for local_proctorio_get_attempt_info() (SEG-03 remediation).
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * @covers ::local_proctorio_get_attempt_info
 */
class attempt_info_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Insert a minimal quiz_attempts row using a real question_usage for the uniqueid FK.
     */
    private function insert_quiz_attempt(int $quizid, int $cmid, int $userid, string $state, int $attempt): void {
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

    public function test_enrolled_student_gets_own_attempt(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->insert_quiz_attempt($quiz->id, $quiz->cmid, $student->id, 'finished', 1);

        $this->setUser($student);
        $result = local_proctorio_get_attempt_info($quiz->cmid);

        $this->assertIsArray($result);
        $this->assertEquals('finished', $result['attempt_status']);
        $this->assertEquals(1, $result['attempt_number']);
    }

    public function test_non_enrolled_user_is_denied(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $outsider = $this->getDataGenerator()->create_user();

        $this->setUser($outsider);

        $this->expectException(\require_login_exception::class);
        local_proctorio_get_attempt_info($quiz->cmid);
    }

    public function test_hidden_activity_is_denied_without_viewhiddenactivities(): void {
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'visible' => 0]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $this->expectException(\require_login_exception::class);
        local_proctorio_get_attempt_info($quiz->cmid);
    }

    public function test_capability_denied_when_stripped_from_role(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $modcontext = \context_module::instance($quiz->cmid);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('local/proctorio:viewattemptdata', CAP_PREVENT, $roleid, $modcontext->id, true);
        $modcontext->mark_dirty();

        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        local_proctorio_get_attempt_info($quiz->cmid);
    }
}
