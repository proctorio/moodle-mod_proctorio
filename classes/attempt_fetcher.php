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
 * Attempt fetcher class for retrieving quiz attempt data across module types.
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
     * The module name and quiz instance ID are always resolved directly from the course
     * module ID. There is deliberately no way for a caller to supply/override the module
     * name: doing so would let one module's instance ID be paired with a different
     * module's query strategy (SEG-03 - module identity confusion).
     *
     * @param int $userid The ID of the user whose attempt to fetch
     * @param int $cmid The course module ID of the quiz
     * @return array|null Array containing attempt data (attempt_status, attempt_number) or null if not found
     */
    public static function get_last_attempt(int $userid, int $cmid): ?array {
        $data = self::detect_module_and_quizid_from_instance($cmid);

        return self::get_quiz_attempt($data['modname'], $userid, $data['instanceid']);
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
     * - Query should ORDER BY to get the last attempt; any LIMIT/OFFSET is stripped and
     *   ignored - execute_custom_query() always imposes its own limit of one row (DIS-01)
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
     * Remove SQL comments from a query.
     *
     * Comments are stripped before the query is validated or executed so that required
     * tokens (placeholders/aliases) cannot be satisfied by text hidden inside a comment
     * while the real, executed statement does something else.
     *
     * @param string $sql Raw SQL text
     * @return string SQL text with block, "--" and "#" comments removed
     */
    private static function strip_sql_comments(string $sql): string {
        $sql = preg_replace('#/\*.*?\*/#s', ' ', $sql);
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/#.*$/m', '', $sql);

        return trim($sql);
    }

    /**
     * Check that a (comment-stripped) query is a single, plain SELECT statement.
     *
     * This blocks stacked statements and data-modifying/administrative statements from
     * being smuggled in as a "custom quiz" query. It does not, and cannot, guarantee that
     * a SELECT only reads tables/columns the plugin intends - admins configuring this
     * setting are trusted to the same degree as anyone else holding moodle/site:config.
     *
     * @param string $strippedsql Query text with comments already removed
     * @return bool True if the statement looks like a single safe SELECT
     */
    private static function is_single_safe_select(string $strippedsql): bool {
        if (!preg_match('/^SELECT\b/i', $strippedsql)) {
            return false;
        }

        // Reject stacked/multiple statements.
        if (strpos($strippedsql, ';') !== false) {
            return false;
        }

        // Reject data-modifying, DDL, and administrative keywords anywhere in the statement.
        $blockedkeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE',
            'GRANT', 'REVOKE', 'MERGE', 'REPLACE', 'EXEC', 'EXECUTE', 'CALL',
            'OUTFILE', 'DUMPFILE', 'LOAD_FILE'];
        foreach ($blockedkeywords as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $strippedsql)) {
                return false;
            }
        }

        return true;
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
     * - SQL query must be a single SELECT statement (no stacked statements, no
     *   write/DDL/admin keywords) once its own comments are stripped
     * - SQL query must contain :userid and :quizid placeholders
     * - SQL query must have aliases: attempt_status and attempt_number
     *
     * @param string $configtext Multi-line configuration text from settings
     * @param string $modname Module name to search for in the configuration
     * @return string|null Comment-stripped SQL query string if found and valid, null otherwise
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
            if ($linemodname === $modname && !empty($sqlquery)) {
                $stripped = self::strip_sql_comments($sqlquery);

                if (!self::is_single_safe_select($stripped)) {
                    debugging(
                        "Custom quiz configuration for '{$modname}' was rejected: " .
                        'must be a single SELECT statement with no write/DDL keywords.',
                        DEBUG_DEVELOPER
                    );
                    continue;
                }

                // Validate query has required placeholders and aliases (post comment-strip,
                // so they can't be satisfied by text hidden inside a comment).
                if (stripos($stripped, ':userid') !== false &&
                    stripos($stripped, ':quizid') !== false &&
                    stripos($stripped, 'attempt_status') !== false &&
                    stripos($stripped, 'attempt_number') !== false) {
                    return $stripped;
                }
            }
        }

        return null;
    }

    /**
     * Remove a trailing LIMIT/OFFSET clause from a query.
     *
     * The row limit is always imposed by execute_custom_query() itself (via
     * get_records_sql()'s $limitnum), never by the configured query text - so any
     * trailing LIMIT an admin wrote is stripped first to avoid colliding with it
     * (some drivers would otherwise end up with two LIMIT clauses, which is invalid SQL).
     *
     * @param string $sql SQL text (comments already stripped)
     * @return string SQL text with a trailing LIMIT/OFFSET clause removed, if present
     */
    private static function strip_trailing_limit_clause(string $sql): string {
        return rtrim(preg_replace('/\s*\bLIMIT\s+\d+(?:\s*,\s*\d+|\s+OFFSET\s+\d+)?\s*$/i', '', $sql));
    }

    /**
     * Execute custom SQL query with parameter binding.
     *
     * This method executes a user-provided SQL query with automatic parameter binding.
     * The query must include :userid and :quizid placeholders and must return columns
     * with aliases: attempt_status and attempt_number. At most one row is ever fetched,
     * via a limit imposed by this method - not by the configured query (DIS-01).
     *
     * @param string $sqlquery The SQL query string with :userid and :quizid placeholders
     * @param int $userid The user ID to bind to :userid placeholder
     * @param int $quizid The quiz instance ID to bind to :quizid placeholder
     * @return array|null Array with keys: attempt_status, attempt_number. Returns null if no attempt found.
     * @throws \dml_exception If query execution fails
     */
    private static function execute_custom_query(string $sqlquery, int $userid, int $quizid): ?array {
        global $DB;

        try {
            $sqlquery = self::strip_trailing_limit_clause($sqlquery);

            // Moodle requires unique parameter names for each placeholder occurrence.
            // Replace duplicate placeholders with numbered versions.
            $processedquery = self::process_query_placeholders($sqlquery);
            $params = self::build_params_for_query($processedquery, $userid, $quizid);

            // Fetch at most one row - a code-imposed limit, never left to the configured query.
            $records = $DB->get_records_sql($processedquery, $params, 0, 1);
            $attempt = $records ? reset($records) : false;

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
            debugging('Custom query execution failed', DEBUG_DEVELOPER);
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
