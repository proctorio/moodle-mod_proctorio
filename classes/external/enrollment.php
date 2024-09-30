<?php

namespace local_proctorio\external;

use external_api;
use external_multiple_structure;
use external_single_structure;
use external_function_parameters;

class enrollment extends external_api
{

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_enrolled_users_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new \external_value(PARAM_INT, 'Course ID'),
                'teacherid' => new \external_value(PARAM_INT, 'User ID')
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_multiple_structure
     */
    public static function get_enrolled_users_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    "id" => new \external_value(PARAM_INT, 'User ID'),
                    "firstname" => new \external_value(PARAM_TEXT, 'User first name'),
                    "lastname" => new \external_value(PARAM_TEXT, 'User last name')
                ]
            )
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function get_enrolled_users($courseid, $teacherid)
    {
        // Parameter validation.
        // REQUIRED.
        $params = self::validate_parameters(
            self::get_enrolled_users_parameters(),
            array(
                'courseid' => $courseid,
                'teacherid' => $teacherid
            )
        );

        $context = \context_course::instance($params['courseid']);

        if (!has_capability('moodle/course:viewparticipants', $context, $params['teacherid'])) {
            throw new \moodle_exception('nopermissions', 'error', '', 'moodle/course:viewparticipants');
        }

        $enrolled_users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname');

        $users = array();

        foreach ($enrolled_users as $user) {
            $users[] = array(
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname
            );
        }

        return $users;
    }
}
