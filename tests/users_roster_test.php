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
 * Tests for local_proctorio_get_course_roster() (SEG-01 remediation).
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_proctorio;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * @covers ::local_proctorio_get_course_roster
 */
class users_roster_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_student_without_capability_is_denied(): void {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        local_proctorio_get_course_roster($course);
    }

    public function test_instructor_with_capability_can_view_roster(): void {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($teacher);

        $roster = local_proctorio_get_course_roster($course);
        $ids = array_column($roster, 'id');

        $this->assertContains((int)$teacher->id, $ids);
        $this->assertContains((int)$student->id, $ids);
    }

    public function test_separate_groups_restricts_roster_to_own_group(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS, 'groupmodeforce' => 1]);
        $context = \context_course::instance($course->id);

        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        // Editing teachers have moodle/site:accessallgroups by default; strip it so the
        // group restriction in local_proctorio_get_course_roster() is actually exercised.
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('moodle/site:accessallgroups', CAP_PREVENT, $roleid, $context->id, true);
        $context->mark_dirty();

        $groupa = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $groupb = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $groupa->id, 'userid' => $teacher->id]);

        $studenta = $this->getDataGenerator()->create_user();
        $studentb = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($studenta->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($studentb->id, $course->id, 'student');
        $this->getDataGenerator()->create_group_member(['groupid' => $groupa->id, 'userid' => $studenta->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $groupb->id, 'userid' => $studentb->id]);

        $this->setUser($teacher);
        $roster = local_proctorio_get_course_roster($course);
        $ids = array_column($roster, 'id');

        $this->assertContains((int)$studenta->id, $ids);
        $this->assertNotContains((int)$studentb->id, $ids);
    }

    public function test_blind_email_is_hidden_unless_permitted(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        // Editing teachers get moodle/course:useremail by default; strip it so the
        // maildisplay check is what decides visibility for this test.
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('moodle/course:useremail', CAP_PREVENT, $roleid, $context->id, true);
        $context->mark_dirty();

        $hiddenstudent = $this->getDataGenerator()->create_user(['maildisplay' => 0]);
        $this->getDataGenerator()->enrol_user($hiddenstudent->id, $course->id, 'student');

        $this->setUser($teacher);
        $roster = local_proctorio_get_course_roster($course);

        $entry = null;
        foreach ($roster as $r) {
            if ((int)$r['id'] === (int)$hiddenstudent->id) {
                $entry = $r;
                break;
            }
        }
        $this->assertNotNull($entry);
        $this->assertNull($entry['email']);
    }
}
