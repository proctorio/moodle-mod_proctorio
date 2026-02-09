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
 * Contains user badge class for displaying a badge issued to a user.
 *
 * @package   local_proctorio
 * @copyright 2025 Proctorio <support@proctorio.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_proctorio', 'Proctorio Moodle Selectors');

    $ADMIN->add('localplugins', $settings);

    // Helper function to add a group of selector fields.
    require_once(__DIR__ . '/lib.php');
    if ($ADMIN->fulltree) {

        // Candidate selectors.
        $candidatefields = [
            'quiz_access_code' => "#id_quizpassword",
            'quiz_info' => ".quizinfo",
            'quiz_attempt' => ".quizattempt",
            'page_mod_quiz_view' => "#page-mod-quiz-view",
            'region_main' => "#region-main",
            'breadcrumb' => ".breadcrumb",
            'breadcrumb_item' => ".breadcrumb-item",
            'page_navbar' => "#page-navbar",
            'page_wrapper' => "#page-wrapper",
            'page_content' => "#page-content",
            'page_header' => "#page-header",
            'navbar' => "#navbar",
            'submit_btns' => ".submitbtns",
            'quiz_attempt_counts' => ".quizattemptcounts",
            "url_path" => "mod/quiz",
            'quiz_time_left' => '#quiz-time-left',
            'start_attempt_file' => 'startattempt.php',
            'review_attempt_page' => 'mod/quiz/review.php',
            'quiz_landing_page' => 'mod/quiz/view.php',
            'take_exam_attempt_page' => 'mod/quiz/startattempt.php',
            'process_attempt_page' => 'mod/quiz/processattempt.php',
        ];

        local_proctorio_add_selector_group(
            $settings,
            'student',
            "Candidate Selectors",
            get_string('candidate_heading', 'local_proctorio'),
            $candidatefields
        );

        // Professor selectors.
        $professorfields = [
            'dropdown_item' => ".dropdown-item",
            'page_mod_quiz_report' => ".page-mod-quiz-report",
            'quiz_info' => ".quizinfo",
            'quiz_attempt' => ".quizattempt",
            'general_table' => ".generaltable",
            'id_quizpassword' => "#id_quizpassword",
            'fgroup_id_buttonar' => "#fgroup_id_buttonar",
            'id_submitbutton' => "#id_submitbutton",
            'region_main' => "#region-main",
            'breadcrumb' => ".breadcrumb",
            'fitem_id_quizpassword' => "#fitem_id_quizpassword",
            'quiz_attempt_counts' => ".quizattemptcounts",
            'mod_quiz_preflight_form' => "#mod_quiz_preflight_form",
            'page' => "#page",
            'url_path' => "mod/quiz",
            'add_quiz' => 'quiz',
            'modulename_value' => 'quiz',
            'quiz_overrides' => '#quizoverrides',
            'report_page' => 'report.php',
            'start_attempt_file' => 'mod/quiz/startattempt.php',
            'review_attempt_page' => 'mod/quiz/review.php',
            'take_exam_attempt_page' => 'mod/quiz/startattempt.php',
            'quiz_landing_page' => 'mod/quiz/view.php',
        ];

        local_proctorio_add_selector_group(
            $settings,
            'professor',
            "Professor Selectors",
            get_string('professor_heading', 'local_proctorio'),
            $professorfields
        );

        $settings->add(new admin_setting_heading(
            'local_proctorio/hide_defaults',
            '',
            '<style>br { display: none !important; }</style>'
        ));

        // Database Quiz Configurations - Simple Line Format.
        $settings->add(new admin_setting_heading(
            'local_proctorio/quiz_configs_heading',
            get_string('quiz_configs_heading', 'local_proctorio'),
            get_string('quiz_configs_heading_desc', 'local_proctorio')
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_proctorio/quiz_configurations',
            get_string('quiz_configurations', 'local_proctorio'),
            get_string('quiz_configurations_help', 'local_proctorio'),
            '',
            PARAM_RAW,
            100,
            12
        ));

        $settings->add(new admin_setting_heading(
            'local_proctorio/hide_defaults_end',
            '',
            '<style>br { display: block !important; }</style>'
        ));
    }
}
