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

namespace local_proctorio;

/**
 * Attempt fetcher class for retrieving quiz attempt information.
 *
 * This class provides methods to fetch the last attempt for various quiz types
 * including standard Moodle quiz, adaptive quiz, and custom quiz modules.
 * It supports custom SQL queries per module where users write their own queries
 * with required aliases: attempt_status, attempt_number.
 * Parameters :userid and :quizid are automatically bound at runtime.
 *
 * @package    local_proctorio
 * @copyright  2025 Proctorio <support@proctorio.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_fetcher {

    /**
     * Get the last attempt for a user on a specific quiz.
     *
     * This method retrieves the most recent quiz attempt for a given user and course module.
     * It automatically detects the quiz type and uses the appropriate query strategy.
     *
     * @param int $userid The ID of the user whose attempt to fetch
     * @param int $cmid The course module ID of the quiz
     * @param string $modname Optional module name (e.g., 'quiz', 'adaptivequiz'). If empty, auto-detected.
     * @return array|null Array containing attempt data (id, attemptstatus, attempt_number) or null if not found
     */
    public static function get_last_attempt(int $userid, int $cmid, string $modname = '') {
        global $DB;

        $data = self::detect_module_and_quizid_from_instance($cmid);
        if (!$modname) {
            // Try to detect module from quizid.
            $modname = $data['modname'];
            $quizid = $data['instanceid'];
        } else {
            $quizid = $data["instanceid"];
        }

        return self::get_quiz_attempt($modname, $userid, $quizid);
    }

    /**
     * Detect the module name and instance ID from a course module ID.
     *
     * @param int $quizid The course module ID (cmid)
     * @return array Array with 'modname' and 'instanceid' keys
     * @throws \moodle_exception If course module not found
     */
    private static function detect_module_and_quizid_from_instance(int $quizid): array {
        $cm = get_coursemodule_from_id(null, $quizid, 0, false, MUST_EXIST);
        $modname = $cm->modname;
        $instanceid = $cm->instance;

        return [
            'modname' => $modname,
            'instanceid' => $instanceid,
        ];
    }

    /**
     * Get quiz attempt using configured settings or auto-detection.
     *
     * This method first tries to use custom SQL query for the module,
     * then falls back to auto-detection for known quiz types.
     *
     * @param string $modname The module name (e.g., 'quiz', 'adaptivequiz', 'customquiz')
     * @param int $userid The user ID
     * @param int $quizid The quiz instance ID
     * @return array|null Array containing attempt data or null if not found
     */
    private static function get_quiz_attempt(string $modname, int $userid, int $quizid) {
        global $DB;

        // Try to get custom SQL query for this specific module.
        $customsql = self::get_module_config($modname);

        if ($customsql) {
            // Execute custom SQL query with bound parameters.
            return self::execute_custom_query($customsql, $userid, $quizid);
        } else {
            // No configuration found - detect module and use appropriate query.
            return self::get_attempt_by_module($modname, $userid, $quizid);
        }
    }

    /**
     * Get custom SQL query configuration for a specific module from settings.
     *
     * Checks the 'quiz_configurations' setting for a SQL query matching the module name.
     * Configuration format is one line per module:
     * modname|SELECT ... FROM ... WHERE ... :userid ... :quizid ... AS attempt_status ... AS attempt_number
     *
     * Requirements:
     * - Query must use :userid and :quizid placeholders in WHERE clause
     * - Query must have aliases: attempt_status and attempt_number
     * - Query should ORDER BY and LIMIT to get the last attempt
     *
     * @param string $modname Module name to look up (e.g., 'customquiz', 'examquiz')
     * @return string|null SQL query string if found, null if no configuration exists for this module
     */
    private static function get_module_config(string $modname): ?string {
        // Check multi-quiz configurations (line-by-line format).
        $quizconfigs = get_config('local_proctorio', 'quiz_configurations');

        if (!empty($quizconfigs)) {
            $query = self::parse_quiz_configurations($quizconfigs, $modname);
            if ($query) {
                return $query;
            }
        }

        return null;
    }

    /**
     * Parse line-by-line quiz configurations from settings text.
     *
     * Parses a multi-line configuration string where each line represents one quiz type.
     * Format: modname|SELECT ... FROM ... WHERE ... :userid ... :quizid ... AS attempt_status ... AS attempt_number
     *
     * Features:
     * - Lines starting with # or // are treated as comments and ignored
     * - Empty lines are ignored
     * - Each line must have exactly 2 pipe-separated values (modname|SQL)
     * - SQL query must contain :userid and :quizid placeholders
     * - SQL query must have aliases: attempt_status and attempt_number
     *
     * @param string $configtext Multi-line configuration text from settings
     * @param string $modname Module name to search for in the configuration
     * @return string|null SQL query string if found and valid, null otherwise
     */
    private static function parse_quiz_configurations(string $configtext, string $modname): ?string {
        $lines = explode("\n", $configtext);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments.
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '//') === 0) {
                continue;
            }

            // Parse the line: modname|SQL_QUERY.
            // Use limit of 2 to allow pipes within SQL query.
            $parts = explode('|', $line, 2);

            // Must have exactly 2 parts.
            if (count($parts) !== 2) {
                continue;
            }

            // Trim each part.
            $linemodname = trim($parts[0]);
            $sqlquery = trim($parts[1]);

            // Check if this is the module we're looking for.
            if ($linemodname === $modname) {
                // Validate query has required placeholders and aliases.
                if (!empty($sqlquery) &&
                    stripos($sqlquery, ':userid') !== false &&
                    stripos($sqlquery, ':quizid') !== false &&
                    stripos($sqlquery, 'attempt_status') !== false &&
                    stripos($sqlquery, 'attempt_number') !== false) {
                    return $sqlquery;
                }
            }
        }

        return null;
    }

    /**
     * Execute custom SQL query with parameter binding.
     *
     * This method executes a user-provided SQL query with automatic parameter binding.
     * The query must include :userid and :quizid placeholders and must return columns
     * with aliases: attempt_status and attempt_number.
     *
     * @param string $sqlquery The SQL query string with :userid and :quizid placeholders
     * @param int $userid The user ID to bind to :userid placeholder
     * @param int $quizid The quiz instance ID to bind to :quizid placeholder
     * @return array|null Array with keys: id, attempt_status, attempt_number. Returns null if no attempt found.
     * @throws \dml_exception If query execution fails
     */
    private static function execute_custom_query(string $sqlquery, int $userid, int $quizid): ?array {
        global $DB;

        try {
            // Moodle requires unique parameter names for each placeholder occurrence.
            // Replace duplicate placeholders with numbered versions.
            $processedquery = self::process_query_placeholders($sqlquery);
            $params = self::build_params_for_query($processedquery, $userid, $quizid);

            // Execute the query with bound parameters.
            $attempt = $DB->get_record_sql($processedquery, $params);

            if ($attempt) {
                // Extract required fields from the result.
                if (!isset($attempt->attempt_status) || !isset($attempt->attempt_number)) {
                    debugging('Custom query must return attempt_status and attempt_number aliases', DEBUG_DEVELOPER);
                    return null;
                }

                return [
                    'attempt_status' => $attempt->attempt_status,
                    'attempt_number' => $attempt->attempt_number,
                ];
            }
        } catch (\dml_exception $e) {
            // Log the error and return null.
            debugging('Custom query execution failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }

        return null;
    }

    /**
     * Process query placeholders to ensure unique parameter names.
     *
     * Moodle's DML requires each placeholder to have a unique name.
     * This method converts duplicate placeholders like:
     * :userid, :userid, :quizid, :quizid
     * Into unique names:
     * :userid0, :userid1, :quizid0, :quizid1
     *
     * @param string $sqlquery The original SQL query
     * @return string Query with unique placeholder names
     */
    private static function process_query_placeholders(string $sqlquery): string {
        $useridcount = 0;
        $quizidcount = 0;

        // Replace :userid placeholders with numbered versions.
        $processedquery = preg_replace_callback(
            '/:userid\b/i',
            function($matches) use (&$useridcount) {
                return ':userid' . $useridcount++;
            },
            $sqlquery
        );

        // Replace :quizid placeholders with numbered versions.
        $processedquery = preg_replace_callback(
            '/:quizid\b/i',
            function($matches) use (&$quizidcount) {
                return ':quizid' . $quizidcount++;
            },
            $processedquery
        );

        return $processedquery;
    }

    /**
     * Build parameters array for query execution.
     *
     * Creates parameter array with all numbered placeholders that were
     * generated by process_query_placeholders().
     *
     * @param string $sqlquery The processed SQL query with numbered placeholders
     * @param int $userid The user ID value
     * @param int $quizid The quiz ID value
     * @return array Parameters array for $DB->get_record_sql()
     */
    private static function build_params_for_query(string $sqlquery, int $userid, int $quizid): array {
        $params = [];

        // Count occurrences of numbered userid placeholders.
        preg_match_all('/:userid(\d+)\b/i', $sqlquery, $useridmatches);
        foreach ($useridmatches[1] as $num) {
            $params['userid' . $num] = $userid;
        }

        // Count occurrences of numbered quizid placeholders.
        preg_match_all('/:quizid(\d+)\b/i', $sqlquery, $quizidmatches);
        foreach ($quizidmatches[1] as $num) {
            $params['quizid' . $num] = $quizid;
        }

        return $params;
    }

    /**
     * Get attempt by auto-detecting the module type and using built-in query logic.
     *
     * This method handles known quiz types with optimized queries:
     *
     * - 'quiz': Standard Moodle quiz (uses quiz_attempts table)
     * - 'adaptivequiz': Adaptive quiz plugin (counts attempts as it has no attempt_number column)
     * - Other modules: Attempts generic pattern ({modname}_attempts table) with auto-detection
     *
     * For unknown modules, it tries to:
     * - Find table named {modname}_attempts
     * - Auto-detect common column names for attempt number and status
     *
     * @param string $modname Module name (e.g., 'quiz', 'adaptivequiz', 'customquiz')
     * @param int $userid The user ID
     * @param int $quizid The quiz instance ID
     * @return array|null Array with keys: id, attempt_status, attempt_number. Returns null if not found.
     */
    private static function get_attempt_by_module(string $modname, int $userid, int $quizid): ?array {
        global $DB;

        switch ($modname) {
            case 'quiz':
                // Standard Moodle quiz.
                $sql = "SELECT qa.attempt as attemptnumber, qa.state as attemptstate
                        FROM {quiz_attempts} qa
                        WHERE qa.quiz = :quizid AND qa.userid = :userid
                        ORDER BY qa.attempt DESC
                        LIMIT 1";
                $attempt = $DB->get_record_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);

                if ($attempt) {
                    return [
                        'attempt_status' => $attempt->attemptstate,
                        'attempt_number' => $attempt->attemptnumber,
                    ];
                }
                break;

            case 'adaptivequiz':
                // Adaptive quiz plugin - note: this table doesn't have an attemptnumber column.
                // We need to get the latest attempt and count total attempts.

                // Get the latest attempt.
                $sql = "SELECT aa.attemptstate, aa.timemodified
                        FROM {adaptivequiz_attempt} aa
                        WHERE aa.instance = :quizid AND aa.userid = :userid
                        ORDER BY aa.timemodified DESC
                        LIMIT 1";
                $attempt = $DB->get_record_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);

                if ($attempt) {
                    // Count total attempts to determine the attempt number.
                    $countsql = "SELECT COUNT(*)
                                  FROM {adaptivequiz_attempt}
                                  WHERE instance = :quizid AND userid = :userid";
                    $attemptcount = $DB->count_records_sql($countsql, ['quizid' => $quizid, 'userid' => $userid]);

                    return [
                        'attempt_status' => $attempt->attemptstate,
                        'attempt_number' => $attemptcount,
                    ];
                }
                break;

            default:
                // Fallback for unknown module types - try generic pattern.
                $table = $modname . '_attempts';

                // Check if the table exists.
                if ($DB->get_manager()->table_exists($modname . '_attempts')) {
                    $sql = "SELECT *
                            FROM {{$table}}
                            WHERE userid = :userid AND instance = :quizid
                            ORDER BY timemodified DESC
                            LIMIT 1";
                    $attempt = $DB->get_record_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);

                    if ($attempt) {
                        // Try to detect common column names.
                        $attemptnumber = null;
                        $attemptstatus = null;

                        if (isset($attempt->attemptnumber)) {
                            $attemptnumber = $attempt->attemptnumber;
                        } else if (isset($attempt->attempt)) {
                            $attemptnumber = $attempt->attempt;
                        }

                        if (isset($attempt->attemptstate)) {
                            $attemptstatus = $attempt->attemptstate;
                        } else if (isset($attempt->state)) {
                            $attemptstatus = $attempt->state;
                        } else if (isset($attempt->status)) {
                            $attemptstatus = $attempt->status;
                        }

                        return [
                            'attempt_status' => $attemptstatus,
                            'attempt_number' => $attemptnumber,
                        ];
                    }
                }
                break;
        }

        return null;
    }
}
