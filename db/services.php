<?php

$functions = array(
    'local_proctorio_get_enrolled_users' => array(
        'classname' => 'local_proctorio\external\enrollment',
        'methodname' => 'get_enrolled_users',
        'description' => 'Get enrolled users',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => false,
        'capabilities' => 'moodle/course:viewparticipants'
    )
);

$services = array(
    'Proctorio services' => array(
        'functions' => array(
            'local_proctorio_get_enrolled_users'
        ),
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);