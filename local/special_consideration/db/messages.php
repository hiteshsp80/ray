<?php

defined('MOODLE_INTERNAL') || die();

/*
$messageproviders = array(
    // Notification for special consideration submissions.
    'notification' => array(
        'capability'  => 'moodle/site:sendmessage',  // Capability to send messages.
        // 'providers' => array('popup' => PROVIDER_ALLOWED),
    
),
);
*/

$messageproviders = array(
    // Notification for special consideration submissions.
    'notification' => array(
        // Set the default message preferences for web (popup) and email notifications.
        'defaults' => array(
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,  // Enable popup notifications by default.
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,  // Enable email notifications by default.
            'web'   => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,  // Enable web notifications by default.
        ),
        'capability' => 'moodle/site:sendmessage',  // Capability to send messages.
    ),

);