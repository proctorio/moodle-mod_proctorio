<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// Check if the courseid is present in the query
if (!isset($_GET['courseid']))
{
    header("HTTP/1.0 400 Bad Request");
    header('Content-Type: application/json');
    echo json_encode(array("error" => array("message" => "Missing course id", "type" => "missing_param")));
    exit;
}

$courseid = required_param('courseid', PARAM_RAW);

//Check if courseid is a number
if(!is_numeric($courseid))
{
    header("HTTP/1.0 400 Bad Request");
    header('Content-Type: application/json');
    echo json_encode(array("error" => array("message" => "Invalid course id, course id should be a number", "type" => "invalid_param")));
    exit;
}

// Check if the courseid is valid
if (!$DB->record_exists('course', array('id' => $courseid)))
{
    header("HTTP/1.0 404 Not Found");
    header('Content-Type: application/json');
    echo json_encode(array("error" => array("message" => "Invalid course id, course id not found", "type" => "not_found")));
    exit;
}

// Get student role id
$studentrole = $DB->get_record('role', array('shortname' => 'student'));

// Get enrolled students in the course
$enrolledusers = get_enrolled_users(context_course::instance($courseid), '', 0, 'u.id, u.firstname, u.lastname, u.email', null, 0, $studentrole->id);

// check if there are enrolled students in the course
if(empty($enrolledusers))
{
    header("HTTP/1.0 404 Not Found");
    header('Content-Type: application/json');
    echo json_encode(array("error" => array("message" => "There are no enrolled students in this course", "type" => "no_data")));
    exit;
}

// create an array to store the users data
$users = array();
foreach ($enrolledusers as $user)
{
    $users[] = array(
        "id" => $user->id,
        "firstname" => $user->firstname,
        "lastname" => $user->lastname,
        "email" => $user->email
    );
}

header('Content-Type: application/json');
echo json_encode($users);