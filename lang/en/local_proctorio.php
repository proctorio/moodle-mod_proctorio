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

$string['add_quiz'] = 'Add Quiz';
$string['add_quiz_help'] = "Copy the path part of your Moodle URL. For example, when you want to create a new quiz, in your URL you will have <b>add=quiz</b> part, copy the value after <b>=</b> and paste into our input field. Example https://moodle/course/modedit.php?<b>add=quiz</b>&type&course=2&section=2&return=0&beforemod=0";
$string["breadcrumb"] = "Breadcrumb";
$string['breadcrumb_help'] = "Try to find a component that holds information abour your current path and find <b>&lt;ol&gt;</b> tag. when you find it copy its selector. for example <b>&lt;ol class='breadcrumb'&gt; &lt;&#47;ol&gt;</b>";
$string["breadcrumb_item"] = "Breadcrumb Item";
$string['breadcrumb_item_help'] = "Inside previous <b>&lt;ol&gt;</b> element you have <b>&lt;li&gt;</b> tag, copy its class. For example <b>&lt;li class='breadcrumb-item'&gt; &lt;&#47;ol&gt;</b>";
$string['candidate_heading'] = "Settings for candidate-side CSS selectors.";
$string['quiz_configs_heading'] = "Custom Quiz SQL Queries";
$string['quiz_configs_heading_desc'] = "Write custom SQL queries for quiz modules that are not automatically supported. Standard 'quiz' and 'adaptivequiz' modules work automatically and do not need configuration. Each query must use :userid and :quizid placeholders and return attempt_status and attempt_number aliases.";
$string['quiz_configurations'] = 'Custom Quiz SQL Queries';
$string['quiz_configurations_help'] = "<strong>⚠️ Write SQL Queries for Custom Quiz Types</strong><br><br><strong>Format (one line per quiz type):</strong><br><code>modname|SELECT state AS attempt_status, attempt AS attempt_number FROM {tablename} WHERE userid = :userid AND quiz = :quizid ORDER BY timemodified DESC LIMIT 1</code><br><br><strong>Requirements:</strong><br>• Must use <code>:userid</code> and <code>:quizid</code> placeholders in WHERE clause<br>• Must have aliases: <code>AS attempt_status</code> and <code>AS attempt_number</code><br>• <strong>You CAN reuse the same placeholders multiple times</strong> (e.g., in subqueries - they're automatically handled)<br>• Should include ORDER BY and LIMIT to get the latest attempt<br>• Use Moodle table syntax: <code>{tablename}</code> (curly braces, no mdl_ prefix)<br><br><strong>Examples:</strong><br><code># Custom quiz module<br>quiz|SELECT state AS attempt_status, attempt AS attempt_number FROM {quiz_attempts} WHERE userid = :userid AND quiz = :quizid ORDER BY timemodified DESC LIMIT 1<br><br># Adaptive quiz with subquery (notice :userid and :quizid used twice - this works!)<br>adaptivequiz|SELECT attemptstate AS attempt_status, (SELECT COUNT(*) FROM {adaptivequiz_attempt} WHERE userid = :userid AND instance = :quizid) AS attempt_number FROM {adaptivequiz_attempt} WHERE userid = :userid AND instance = :quizid ORDER BY timemodified DESC LIMIT 1</code><br><br><strong>How to build your query:</strong><br>1. Find your table: <code>SHOW TABLES LIKE '%attempt%';</code><br>2. See columns: <code>DESCRIBE mdl_yourquiz_attempts;</code><br>3. Test query: <code>SELECT * FROM mdl_yourquiz_attempts WHERE userid = 123 LIMIT 1;</code><br>4. Add aliases and placeholders<br><br><strong>Notes:</strong><br>• Standard 'quiz' and 'adaptivequiz' work automatically (no config needed)<br>• One line per quiz type, use pipe (|) to separate modname from SQL<br>• Parameters :userid and :quizid can be used multiple times - they're automatically numbered internally<br>• Lines starting with # are comments<br>• Use subqueries if you need to count attempts";
$string['attempt_number'] = 'Attempt Number';
$string['attempt_number_help'] = "Specify the database column name that stores the quiz attempt number.";
$string['attempt_status'] = 'Attempt Status';
$string['attempt_status_help'] = "Specify the database column name that stores the status of the quiz attempt (e.g., 'finished', 'inprogress', 'abandoned').";
$string['dropdown_item'] = 'Dropdown Item';
$string['dropdown_item_help'] = "Try to find where is the drop-down menu that contains options like 'log out', 'profile' etc, and try to copy the class of the &lt;a&gt;&lt;&#47;a&gt; tag that lives inside the drop-down";
$string['fgroup_id_buttonar'] = 'Fgroup Id Buttonar';
$string['fgroup_id_buttonar_help'] = "Click on Preview quiz button that will open the modal where you will have quiz password input field. Try to find div element that holds 'start attempt' and 'cancel' button and copy its id selector";
$string['fitem_id_quizpassword'] = 'Fitem Id Quiz Password';
$string['fitem_id_quizpassword_help'] = "Click on Preview quiz button that will open the modal where you will have quiz password input field. Try to find div element that holds the whole component elements for example <b>&lt;div id='fitem_id_quizpassword'&gt;&lt;&#47;div&gt;</b>";
$string['general_table'] = 'General Table';
$string['general_table_help'] = "Go to quiz page and copy the class of the table tag. In default moodle it looks like this <b>&lt;table class='generaltable'&gt;&lt;&#47;table&gt;</b>";
$string['id_quizpassword'] = 'Id Quiz Password';
$string["id_quizpassword_help"] = "Locate the HTML input element on your Moodle site where users enter the exam password. Copy the CSS selector for this field and paste it into the form below.";
$string['id_submitbutton'] = 'Id Submit Button';
$string['id_submitbutton_help'] = "Click on Preview quiz button that will open the modal where you will have quiz password input field. Try to find <b>&lt;input type='submit' id='id_submitbutton'&gt;</b> element and copy its id selector";
$string['mod_quiz_preflight_form'] = 'Mod Quiz Preflight Form';
$string['mod_quiz_preflight_form_help'] = "When you click on 'Preview quiz' moodle opens modal page that has quiz password input field. Try to copy id of the form tag from that modal";
$string['modulename_value'] = 'Modulename Value';
$string['modulename_value_help'] = "When you want to create a new quiz, or want to edit existing one by visiting the settings page try to find <b>&lt;input type='hidden' name='modulename' value='quiz'&gt;</b> element with the <b>modulename</b> name. When you find it copy the value of the value attribute and paste it to our form";
$string["navbar"] = "Navbar";
$string['navbar_help'] = "Try to find the nav tag and copy its class, in default moodle default clogin_info_helplass is <b>navbar</b>";
$string['page'] = 'Page';
$string["page_content"] = "Page Content";
$string['page_content_help'] = "In quiz page this is the element that holds the whole content, starting from Attempt/Re-attempt button all the way to the feedback. In default moodle this is the first div element after header tag.";
$string["page_header"] = "Page Header";
$string['page_header_help'] = "Try to find header tag and copy its id";
$string['page_help'] = "Try to identify the id of the div element that holds content of the whole page";
$string['page_mod_quiz_report'] = 'Page Mod Quiz Report';
$string['page_mod_quiz_report_help'] = "Copy the id of the body tag";
$string['page_mod_quiz_view'] = "Page Mod Quiz View";
$string['page_mod_quiz_view_help'] = "Open inspect element and check id of the body tag. Default one is 'page-mod-quiz-view'";
$string["page_navbar"] = "Page Navbar";
$string['page_navbar_help'] = "Try to identify component that holds information about your current path. For example <b>Dashboard &#47; My courses &#47; MT &#47; Ext &#47; Multichoice question</b> and copy selector of that div element";
$string['page_wrapper'] = "Page wrapper";
$string['page_wrapper_help'] = "Try to look at one of the first div elements after body tag, and element that is wrapper for the whole page is our element, copy its selector.";
$string["pluginname"] = "Proctorio Selectors";
$string["process_attempt_page"] = "Process Attempt Page";
$string["process_attempt_page_help"] = "Identify the path from the URL of the page where users end their quiz attempt.";
$string["professor_heading"] = "Settings for professor-side CSS selectors.";
$string['quiz_access_code'] = "Quiz Access Code";
$string["quiz_access_code_help"] = "Locate the HTML input element on your Moodle site where users enter the exam password. Copy the CSS selector for this field and paste it into the form below.";
$string['quiz_attempt'] = "Quiz Attempt";
$string["quiz_attempt_counts"] = "Quiz Attempt Counts";
$string['quiz_attempt_counts_help'] = "An feature on the quiz page indicates how many attempts you have made. Make a copy of the div element's class.";
$string['quiz_attempt_help'] = "Identify the section on the quiz landing page that displays users’ previous attempt history, including how many times they have taken the quiz. This section is typically part of the page at mod/quiz/view.php in a standard Moodle setup.";
$string['quiz_info'] = "Quiz Info";
$string['quiz_info_help'] = 'Navigate to any quiz you have and attempt to locate any quiz information, such as => <b>"Grading method: Highest grade"</b> copy its first div class, such as <b>"quizinfo"</b>.';
$string['quiz_id'] = 'Quiz ID';
$string['quiz_id_help'] = "Specify the database column name that stores the quiz ID.";
$string["quiz_landing_page"] = "Quiz Landing Page";
$string["quiz_landing_page_help"] = "Identify the path from the URL of the page where users first access the quiz, before starting an attempt.";
$string['quiz_overrides'] = 'Quiz Overrides';
$string['quiz_overrides_help'] = "Go to quiz overrides and try to locate the div element like this one <b>&lt;div id='quizoverrides'&gt;</b> copy the id value";
$string["quiz_time_left"] = "Quiz Time Left";
$string['quiz_time_left_help'] = "Attempt a time-limited quiz, locate the timer, and copy the ID selector, for example. <b>&lt;span id='quiz-time-left'&gt;0:01:39&lt;&#47;span&gt;</b>";
$string['region_main'] = "Region Main";
$string['region_main_help'] = "Is's supposed to be a div element that will hold button for attempting the exam, 'quiz info', 'your attempts' and etc. The default one is <b>region-main</b>";
$string['report_page'] = 'Report Page';
$string["report_page_help"] = "Identify the file name from the URL of the page where quiz reports are displayed, showing detailed results and statistics. This is typically report.php in a standard Moodle installation.";
$string["review_attempt_page"] = "Review Attempt Page";
$string['review_attempt_page_help'] = "Identify the path from the URL of the page where users review their quiz attempt.";
$string["start_attempt_file"] = "Start Attempt File";
$string['start_attempt_file_help'] = "Identify the file name from the URL of the page where users are actively taking the quiz.";
$string["submit_btns"] = "Submit Btns";
$string['submit_btns_help'] = "While you are in attempt, copy the class of the div element of the button that you use to finish your attempt.";
$string['table'] = 'Table';
$string['table_help'] = "Specify the database table name where quiz attempts are stored (e.g., 'quiz_attempts').";
$string["take_exam_attempt_page"] = "Take Exam Attempt Page";
$string['take_exam_attempt_page_help'] = "Identify the path from the URL of the page where users are actively attempting the quiz (i.e., answering questions during the exam). This is typically mod/quiz/attempt.php in a standard Moodle installation.";
$string["url_path"] = "Url Path";
$string['url_path_help'] = "Copy the path part of your Moodle URL. For example, go to any quiz you have and if your URL is <b>https://moodle/mod/quiz/view.php?id=3</b> copy the <b>'mod/quiz'</b> part and obtain us that path";
$string['user_id'] = 'User ID';
$string['user_id_help'] = "Specify the database column name that stores the user ID associated with the quiz attempt.";
