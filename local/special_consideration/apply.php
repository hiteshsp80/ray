<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/special_consideration/classes/form/application_form.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once('lib.php');
$CFG->debug = 0;
$CFG->debugdisplay = 0;

$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

if ($courseid == 0) {
    $courseid = $fromform->courseid ?? $COURSE->id ?? 0;
}
if ($courseid == 0) {
    throw new moodle_exception('missingcourseid', 'local_special_consideration');
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

function get_readable_type($type) {
    return get_string('type_' . str_replace('-', '_', $type), 'local_special_consideration', $type);
}

function get_readable_status($status) {
    return get_string('status_' . $status, 'local_special_consideration', ucfirst($status));
}

// Check for view capability
if (!has_capability('local/special_consideration:view', $context)) {
    throw new required_capability_exception($context, 'local/special_consideration:view', 'nopermissions', 'local_special_consideration');
}

$PAGE->set_url(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
$PAGE->set_title(get_string('specialconsideration', 'local_special_consideration'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css('/local/special_consideration/styles.css');

$mform = new \local_special_consideration\form\application_form(null, array('course' => $course));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
} else if ($fromform = $mform->get_data()) {
    // Check for apply capability before saving the application
    if (!has_capability('local/special_consideration:apply', $context)) {
        throw new required_capability_exception($context, 'local/special_consideration:apply', 'nopermissions', 'local_special_consideration');
    }

    // Get course settings
    $settings = local_special_consideration_get_course_settings($courseid);

    // Validate file uploads against course settings
    $fs = get_file_storage();
    $usercontext = context_user::instance($USER->id);
    $draftitemid = $fromform->supportingdocs;
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id DESC', false);

    foreach ($files as $file) {
        $filename = $file->get_filename();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (!in_array('.' . $extension, $settings['allowedfiletypes'])) {
            throw new moodle_exception('invalidfiletype', 'local_special_consideration', '', $filename);
        }
        
        if ($file->get_filesize() > $settings['maxfilesize']) {
            throw new moodle_exception('filetoobig', 'local_special_consideration', '', $filename);
        }
    }


    // Save the application
    $application = new stdClass();
    $application->courseid = $courseid;
    $application->userid = $USER->id;
    $application->type = $fromform->type;
    $application->affectedassessment = $fromform->affectedassessment;
    $application->dateaffected = ($fromform->type === 'extension' && isset($fromform->dateaffected)) ? $fromform->dateaffected : 0;
    $application->reason = $fromform->reason;
    $application->additionalcomments = $fromform->additionalcomments;
    $application->status = 'pending';
    $application->timecreated = time();

    $applicationid = $DB->insert_record('local_special_consideration', $application);

       // Save file attachments
    //    file_save_draft_area_files($fromform->attachments, $context->id, 'local_special_consideration', 'attachments', $applicationid, array('subdirs' => 0, 'maxbytes' => $settings['maxfilesize'], 'maxfiles' => $settings['allownafiles']));
    if (!empty($fromform->supportingdocs)) {
        file_save_draft_area_files($fromform->supportingdocs, $context->id, 'local_special_consideration', 'supportingdocs', $applicationid, array('subdirs' => 0, 'maxbytes' => $settings['maxfilesize'], 'maxfiles' => $settings['allownafiles']));
    }

    if (!empty($fromform->supportingdocs)) {
        file_save_draft_area_files($fromform->supportingdocs, $context->id, 'local_special_consideration', 'supportingdocs', $applicationid);
        
        $application->id = $applicationid;
        $application->supportingdocs = $fromform->supportingdocs;
        $DB->update_record('local_special_consideration', $application);
    }

     // Send confirmation to the student
     $message = new \core\message\message();
     $message->component = 'local_special_consideration';
     $message->name = 'notification';
     $message->userfrom = core_user::get_noreply_user();
     $message->userto = $USER->id;
     $message->subject = get_string('applicationsubmitted', 'local_special_consideration');
     $message->fullmessage = get_string('applicationsubmitteddetail', 'local_special_consideration', fullname($USER));
     $message->fullmessageformat = FORMAT_MOODLE;
     $message->fullmessagehtml = "<p>A new special consideration request has been submitted by <strong>{$USER->firstname} {$USER->lastname}</strong>. <a href='{$CFG->wwwroot}/local/special_consideration/view.php?id={$applicationid}'>Click here to view the request</a>.</p>";
     $message->smallmessage = get_string('applicationsubmitted', 'local_special_consideration');
     $message->notification = 1;
    
     message_send($message);

    // Send notification to teachers
    $teachers = get_enrolled_users($context, 'mod/assign:grade'); // Adjust capability as required
    foreach ($teachers as $teacher) {
        $message = new \core\message\message();
        $message->component = 'local_special_consideration';
        $message->name = 'notification';
        $message->userfrom = $USER;
        $message->userto = $teacher->id;
        $message->subject = "New Special Consideration Request";
        $message->fullmessage = "A new special consideration request has been submitted by {$USER->firstname} {$USER->lastname}.";
        $message->fullmessageformat = FORMAT_MOODLE;
        $message->fullmessagehtml = "<p>A new special consideration request has been submitted by <strong>{$USER->firstname} {$USER->lastname}</strong>. <a href='{$CFG->wwwroot}/local/special_consideration/view.php?id={$applicationid}'>Click here to view the request</a>.</p>";
        $message->smallmessage = 'New special consideration request submitted';
        $message->notification = 1;
        // send_message_safely($message);
        message_send($message);
    }

    redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)),
            get_string('applicationsubmitted', 'local_special_consideration'),
            null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Check user roles
$canManage = has_capability('local/special_consideration:manage', $context);
$isStudent = has_capability('local/special_consideration:apply', $context) && !$canManage;

if ($canManage) {
     // Add settings icon
     $settings_url = new moodle_url('/local/special_consideration/course_settings.php', array('id' => $courseid));
     $settings_icon = $OUTPUT->pix_icon('i/settings', get_string('settings', 'local_special_consideration'));
     $settings_link = html_writer::link($settings_url, $settings_icon . ' ' . get_string('settings', 'local_special_consideration'));
     echo html_writer::div($settings_link, 'settings-link');

    // Admin/Teacher view
    if ($action === 'new') {
        //echo html_writer::tag('h3', get_string('newapplication', 'local_special_consideration'));
        $mform->display();
    } else {
        // Pending applications
        echo html_writer::tag('h3', get_string('pendingapplications', 'local_special_consideration'));
        
        $pendingapplications = $DB->get_records_sql(
            "SELECT sc.*, u.id AS userid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
             FROM {local_special_consideration} sc
             JOIN {user} u ON sc.userid = u.id
             WHERE sc.courseid = :courseid AND sc.status = 'pending'
             ORDER BY sc.timecreated DESC",
            array('courseid' => $courseid)
        );

        if (empty($pendingapplications)) {
            echo html_writer::tag('p', get_string('nopendingapplications', 'local_special_consideration'));
        } else {
            $modinfo = get_fast_modinfo($course); //get course module information

            $table = new html_table();
            $table->head = array(
                get_string('datesubmitted', 'local_special_consideration'),
                get_string('type', 'local_special_consideration'),
                get_string('affectedassessment', 'local_special_consideration'),
                get_string('status', 'local_special_consideration'),
                get_string('studentname', 'local_special_consideration'),
                get_string('actions', 'local_special_consideration')
            );
            
            foreach ($pendingapplications as $application) {
                $viewurl = new moodle_url('/local/special_consideration/view.php', array('id' => $application->id, 'courseid' => $courseid));
                $actions = html_writer::link($viewurl, get_string('view', 'local_special_consideration'));
                $displayType = get_readable_type($application->type);
                $affectedAssessment = get_string('notspecified', 'local_special_consideration');
                if (!empty($application->affectedassessment) && isset($modinfo->cms[$application->affectedassessment])) {
                    $cm = $modinfo->cms[$application->affectedassessment];
                    $affectedAssessment = $cm->name;
                }
                
                $row = array(
                    userdate($application->timecreated),
                    $displayType,
                    $affectedAssessment,
                    get_readable_status($application->status), 
                    fullname($application),
                    $actions
                );

                $table->data[] = $row;
            }

            echo html_writer::table($table);
        }

        echo html_writer::empty_tag('hr', array('class' => 'divider'));

        // Previous applications
        echo html_writer::tag('h3', get_string('previousapplications', 'local_special_consideration'));
        
        $previousapplications = $DB->get_records_sql(
            "SELECT sc.*, u.id AS userid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                    t.id AS reviewerid, t.firstname AS teacherfirstname, t.lastname AS teacherlastname, 
                    t.firstnamephonetic AS teacherfirstnamephonetic, t.lastnamephonetic AS teacherlastnamephonetic, 
                    t.middlename AS teachermiddlename, t.alternatename AS teacheralternatename
             FROM {local_special_consideration} sc
             JOIN {user} u ON sc.userid = u.id
             LEFT JOIN {user} t ON sc.reviewerid = t.id
             WHERE sc.courseid = :courseid AND sc.status != 'pending'
             ORDER BY sc.timecreated DESC",
            array('courseid' => $courseid)
        );

        if (empty($previousapplications)) {
            echo html_writer::tag('p', get_string('nopreviousapplications', 'local_special_consideration'));
        } else {
            $table = new html_table();
            $table->head = array(
                get_string('datesubmitted', 'local_special_consideration'),
                get_string('type', 'local_special_consideration'),
                get_string('status', 'local_special_consideration'),
                get_string('studentname', 'local_special_consideration'),
                get_string('reviewedby', 'local_special_consideration'),
                get_string('actions', 'local_special_consideration')
            );

            foreach ($previousapplications as $application) {
                $viewurl = new moodle_url('/local/special_consideration/view.php', array('id' => $application->id, 'courseid' => $courseid));
                $actions = html_writer::link($viewurl, get_string('view', 'local_special_consideration'));

                $reviewedby = $application->teacherfirstname && $application->teacherlastname 
                    ? fullname((object)[
                        'firstname' => $application->teacherfirstname,
                        'lastname' => $application->teacherlastname,
                        'firstnamephonetic' => $application->teacherfirstnamephonetic,
                        'lastnamephonetic' => $application->teacherlastnamephonetic,
                        'middlename' => $application->teachermiddlename,
                        'alternatename' => $application->teacheralternatename
                    ])
                    : get_string('notapplicable', 'local_special_consideration');

                $displayType = get_readable_type($application->type);
                
                $statusWithIcon = '<i class="fa ' . 
                ($application->status == 'approved' ? 'fa-check" style="color: green;">' : 
                ($application->status == 'declined' ? 'fa-times" style="color: red;">' : 
                'fa-question" style="color: orange;">')) . 
                '</i>' . " " . get_readable_status($application->status);
            
                $row = array(
                    userdate($application->timecreated),
                    $displayType, 
                    $statusWithIcon,
                    fullname($application),
                    $reviewedby,
                    $actions
                );

                $table->data[] = $row;
            }

            echo html_writer::table($table);
        }
    }
} else {
    // Student view
    if ($action === 'new') {
        // Check for apply capability before displaying the form
        if (!has_capability('local/special_consideration:apply', $context)) {
            throw new required_capability_exception($context, 'local/special_consideration:apply', 'nopermissions', 'local_special_consideration');
        }
        //echo html_writer::tag('h3', get_string('newapplication', 'local_special_consideration'));
        $mform->display();
    } else {
        // Display "Create New Application" button
        $create_new_url = new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid, 'action' => 'new'));
        $create_new_button = $OUTPUT->single_button($create_new_url, get_string('createnewapplication', 'local_special_consideration'), 'get');
        echo html_writer::div($create_new_button, 'create-new-application');

        echo html_writer::empty_tag('hr', array('class' => 'divider'));

        // Display previous applications
        echo html_writer::tag('h3', get_string('previousapplications', 'local_special_consideration'));

        $applications = $DB->get_records('local_special_consideration', array('userid' => $USER->id, 'courseid' => $courseid), 'timecreated DESC');

        if (empty($applications)) {
            echo html_writer::tag('p', get_string('nopreviousapplications', 'local_special_consideration'));
        } else {
            $table = new html_table();
            $table->head = array(
                get_string('datesubmitted', 'local_special_consideration'),
                get_string('type', 'local_special_consideration'),
                get_string('status', 'local_special_consideration'),
                get_string('actions', 'local_special_consideration')
            );

            foreach ($applications as $application) {
                $viewurl = new moodle_url('/local/special_consideration/view.php', array('id' => $application->id, 'courseid' => $courseid));
                $editurl = new moodle_url('/local/special_consideration/edit.php', array('id' => $application->id, 'courseid' => $courseid));
                
                $actions = html_writer::link($viewurl, get_string('view', 'local_special_consideration'));
                if ($application->status === 'pending' || $application->status === 'more_info') {
                    $actions .= ' | ' . html_writer::link($editurl, get_string('edit', 'local_special_consideration'));
                }
                if ($application->status === 'pending') {
                    $actions .= ' | ' . html_writer::link('#', get_string('withdraw', 'local_special_consideration'), 
                        array('class' => 'withdraw-button', 'data-id' => $application->id));
                }

                $displayType = get_readable_type($application->type); 

                $row = array(
                    userdate($application->timecreated),
                    $displayType,
                    get_readable_status($application->status), 
                    $actions
                );

                $table->data[] = $row;
            }

            echo html_writer::table($table);
        }

        // JavaScript for withdraw button
        $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            $('.withdraw-button').on('click', function(e) {
                e.preventDefault();
                var applicationId = $(this).data('id');
                
                if (window.confirm('Are you sure you want to withdraw this application?')) {
                    $.post('" . $CFG->wwwroot . "/local/special_consideration/withdraw.php', {
                        ajax: 1,
                        id: applicationId,
                        sesskey: '" . sesskey() . "'
                    }, function(data) {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.error);
                        }
                    }, 'json');
                }
            });
        });
        ");
    }
}

echo $OUTPUT->footer();