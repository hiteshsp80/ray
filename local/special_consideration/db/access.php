<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
'local/special_consideration:apply' => array(
'captype' => 'write',
'contextlevel' => CONTEXT_COURSE,
'archetypes' => array(
'student' => CAP_ALLOW
)
),
'local/special_consideration:manage' => array(
'captype' => 'write',
'contextlevel' => CONTEXT_COURSE,
'archetypes' => array(
'editingteacher' => CAP_ALLOW,
'manager' => CAP_ALLOW
)
)
);