<?php
function local_special_consideration_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/special_consideration:apply', $context) || 
        has_capability('local/special_consideration:manage', $context)) {
        $url = new moodle_url('/local/special_consideration/apply.php', array('courseid' => $course->id));
        $node = navigation_node::create(
            get_string('specialconsideration', 'local_special_consideration'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'specialconsideration',
            new pix_icon('i/settings', '')
        );
        $navigation->add_node($node);
    }
}

function local_special_consideration_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    require_login($course);

    if ($filearea !== 'supportingdocs') {
        return false;
    }

    $itemid = array_shift($args);

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    
    if (!$file = $fs->get_file($context->id, 'local_special_consideration', $filearea, $itemid, $filepath, $filename) or $file->is_directory()) {
        return false;
    }

    // Make sure the user has access to this file
    $application = $DB->get_record('local_special_consideration', array('id' => $itemid), '*', MUST_EXIST);
    if ($application->userid != $USER->id && !has_capability('local/special_consideration:manage', $context)) {
        return false;
    }

    // Debugging
    error_log('File found: ' . $filename);
    error_log('File path: ' . $filepath);
    error_log('Item ID: ' . $itemid);

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

function notify_user_of_changes($userid, $subject, $message) {
    global $DB;

    // Get the user's email
    $user = $DB->get_record('user', array('id' => $userid), 'email, firstname, lastname', MUST_EXIST);

    // Prepare the email
    $body = "Dear {$user->firstname} {$user->lastname},\n\n" . $message . "\n\nBest regards,\nYour Team";

    // Send the email
    email_to_user($user, get_admin(), $subject, $body);
}

function get_teacher_id() {
    global $DB;
    // Logic to get the teacher's user ID
    $teacher = $DB->get_record('role_assignments', array('roleid' => 3), 'userid', MUST_EXIST); // Assuming roleid 3 is for teachers
    return $teacher->userid;
}

function get_admin_id() {
    global $DB;
    // Logic to get the admin's user ID
    $admin = $DB->get_record('role_assignments', array('roleid' => 1), 'userid', MUST_EXIST); // Assuming roleid 1 is for admins
    return $admin->userid;
}

function student_makes_request($applicationid, $studentid) {
    global $DB;

    // Logic to handle the student's request

    // Notify the teacher and admin
    $subject = "New Special Consideration Request";
    $message = "A new special consideration request has been made by student ID {$studentid}.";
    
    // Get teacher and admin user IDs
    $teacherid = get_teacher_id();
    $adminid = get_admin_id();

    notify_user_of_changes($teacherid, $subject, $message);
    notify_user_of_changes($adminid, $subject, $message);
}

function teacher_or_admin_responds($applicationid, $response, $responderid) {
    global $DB;
    

    // Notify the student
    $application = $DB->get_record('local_special_consideration', array('id' => $applicationid), '*', MUST_EXIST);
    $studentid = $application->userid;
    $subject = "Response to Your Special Consideration Request";
    $message = "Your special consideration request has been {$response} by user ID {$responderid}.";

    notify_user_of_changes($studentid, $subject, $message);
}

function local_special_consideration_message_provider_enabled($provider) {
    if ($provider === 'local_special_consideration/notification') {
        return true;
    }
    return null;
}

function local_special_consideration_is_processor_enabled($processor) {
    if ($processor === 'email') {
        return false;
    }
    return null;
}

function local_special_consideration_get_message_providers() {
    return [
        'notification' => [
            'defaults' => [
                'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
                // 'email' => MESSAGE_PERMITTED
            ],
        ],
    ];
}


function local_special_consideration_get_course_settings($courseid) {
    $settings = array();
    $settings['whocanapprove'] = json_decode(get_config('local_special_consideration', 'whocanapprove_' . $courseid), true);
    $settings['showfutureonly'] = get_config('local_special_consideration', 'showfutureonly_' . $courseid);
    $settings['allownafiles'] = get_config('local_special_consideration', 'allownafiles_' . $courseid);
    $settings['maxfilesize'] = get_config('local_special_consideration', 'maxfilesize_' . $courseid);
    $allowedfiletypes = json_decode(get_config('local_special_consideration', 'allowedfiletypes_' . $courseid), true);
    $settings['allowedfiletypes'] = is_array($allowedfiletypes) ? $allowedfiletypes : array();
    return $settings;
}

function local_special_consideration_extend_settings_navigation($settingsnav, $context) {
    global $CFG, $PAGE;

    // only add this settings item on non-site course pages
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    // only let users with the appropriate capability see this settings item
    if (!has_capability('local/special_consideration:manage', context_course::instance($PAGE->course->id))) {
        return;
    }

    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $strfoo = get_string('specialconsideration', 'local_special_consideration');
        $url = new moodle_url('/local/special_consideration/course_settings.php', array('id' => $PAGE->course->id));
        $foonode = navigation_node::create(
            $strfoo,
            $url,
            navigation_node::NODETYPE_LEAF,
            'specialconsideration',
            'specialconsideration',
            new pix_icon('i/settings', $strfoo)
        );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $foonode->make_active();
        }
        $settingnode->add_node($foonode);
    }
}

function update_assignment_deadline($application) {
    global $DB, $CFG, $COURSE;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    //start a transaction
    $transaction = $DB->start_delegated_transaction();

    try {
        $cm = get_coursemodule_from_id('assign', $application->affectedassessment, $application->courseid);
        if (!$cm) {
            throw new moodle_exception('Course module not found');
        }

        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $course);

        $new_due_date = $application->dateaffected;
        $instance = $assign->get_instance();

        //update the assignment instance
        $instance->duedate = $new_due_date;
        $DB->update_record('assign', $instance);

        //update or create user override
        $override = $DB->get_record('assign_overrides', 
            array('assignid' => $instance->id, 'userid' => $application->userid));

        if ($override) {
            $override->duedate = $new_due_date;
            $DB->update_record('assign_overrides', $override);
        } else {
            $override = new stdClass();
            $override->assignid = $instance->id;
            $override->userid = $application->userid;
            $override->duedate = $new_due_date;
            $override->id = $DB->insert_record('assign_overrides', $override);
        }

        //force update of assignment cache
        $assign->update_effective_access($application->userid);
        
        //trigger events
        $params = array(
            'context' => $context,
            'objectid' => $override->id,
            'relateduserid' => $application->userid,
            'other' => array(
                'assignid' => $instance->id,
                'duedate' => $new_due_date
            )
        );
        $event = \mod_assign\event\user_override_updated::create($params);
        $event->trigger();

        //force cache updates
        assign_update_events($assign);
        rebuild_course_cache($COURSE->id, true);
        
        //clear assign cache
        cache_helper::purge_by_event('assignmentchanged');
        
        //clear user's grade cache
        grade_get_grades($COURSE->id, 'mod', 'assign', $instance->id, $application->userid);

        $transaction->allow_commit();

        mtrace("Assignment deadline successfully updated for user {$application->userid} in assignment {$instance->id}");

        return true;

    } catch (Exception $e) {
        $transaction->rollback($e);
        mtrace("Error updating assignment deadline: " . $e->getMessage());
        return false;
    }
}

function local_special_consideration_before_standard_html_head() {
    global $PAGE, $USER, $DB, $CFG;

    if ($PAGE->pagetype === 'mod-assign-view') {
        $cm = $PAGE->cm;
        if ($cm && $cm->modname === 'assign') {
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
            $context = context_module::instance($cm->id);
            $assign = new assign($context, $cm, $PAGE->course);
            $instance = $assign->get_instance();

            $override = $DB->get_record('assign_overrides', [
                'assignid' => $instance->id,
                'userid' => $USER->id
            ]);

            if ($override && $override->duedate) {
                $newduedate = userdate($override->duedate);
                $timeremaining = format_time($override->duedate - time());
                
                $PAGE->requires->js_init_code("
                    require(['jquery'], function($) {
                        $(document).ready(function() {
                            var currentDueDate = $('.activity-dates').find('strong:contains(\"Due:\")').next().text();
                            var newDueDate = '$newduedate';
                            
                            if (currentDueDate !== newDueDate) {
                                $('.activity-dates').find('strong:contains(\"Due:\")').next().text(newDueDate);
                                $('th:contains(\"Time remaining\")').next('td').text('$timeremaining');
                                $('.duedate').text(newDueDate);
                                
                                // Set a flag in session storage to indicate that we've updated the page
                                sessionStorage.setItem('dueDateUpdated', 'true');
                                
                                // Reload the page once to ensure all Moodle internals are updated
                                if (sessionStorage.getItem('pageReloaded') !== 'true') {
                                    sessionStorage.setItem('pageReloaded', 'true');
                                    location.reload();
                                }
                            }

                        });
                    });
                ");
            }
        }
    }
}
