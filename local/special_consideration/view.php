<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/special_consideration/classes/form/application_form.php');

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// If no id is provided, redirect to the apply.php page
if ($id == 0) {
    redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
}

$application = $DB->get_record('local_special_consideration', array('id' => $id), '*', MUST_EXIST);

if ($courseid == 0) {
    $courseid = $application->courseid;
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

if ($application->userid == $USER->id) {
    require_capability('local/special_consideration:apply', $context);
} else {
    require_capability('local/special_consideration:manage', $context);
}

$PAGE->set_url(new moodle_url('/local/special_consideration/view.php', array('id' => $id, 'courseid' => $courseid)));
$PAGE->set_title(get_string('viewapplication', 'local_special_consideration'));
$PAGE->set_heading($course->fullname);

$user = $DB->get_record('user', array('id' => $application->userid));

// Handle form submission
if (has_capability('local/special_consideration:manage', $context)) {
    $mform = new \local_special_consideration\form\response_form();
    
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
    } else if ($fromform = $mform->get_data()) {
        // Update the application status and feedback
        $application->status = $fromform->status;
        $application->feedback = $fromform->feedback;
        $application->reviewerid = $USER->id;  
        $application->timemodified = time();
        
        $DB->update_record('local_special_consideration', $application);
        
        // Redirect back to the list of applications
        redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)),
                 get_string('applicationupdated', 'local_special_consideration'),
                 null,
                 \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('applicationdetails', 'local_special_consideration'));

// Display application details
function get_readable_type($type) {
    return get_string('type_' . str_replace('-', '_', $type), 'local_special_consideration', $type);
}

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->data[] = array(get_string('applicant', 'local_special_consideration'), fullname($user));
$table->data[] = array(get_string('studentid', 'local_special_consideration'), $user->idnumber);

$displayType = get_string('type_' . $application->type, 'local_special_consideration', $application->type);

$table->data[] = array(get_string('applicationtype', 'local_special_consideration'), $displayType);


// Affected Assessment
$assessment_name = get_string('notspecified', 'local_special_consideration');
if (!empty($application->affectedassessment)) {
    $cm = get_coursemodule_from_id('', $application->affectedassessment, $courseid);
    if ($cm) {
        $assessment_name = $cm->name;
    }
}

$table->data[] = array(get_string('affectedassessment', 'local_special_consideration'), $assessment_name);
$table->data[] = array(get_string('dateaffected', 'local_special_consideration'), userdate($application->dateaffected));
$table->data[] = array(get_string('reason', 'local_special_consideration'), $application->reason);
$table->data[] = array(get_string('additionalcomments', 'local_special_consideration'), $application->additionalcomments);
$table->data[] = array(get_string('status', 'local_special_consideration'), $application->status);

echo html_writer::table($table);

//add reviewer
if (!empty($application->reviewerid)) {
    $reviewer = $DB->get_record('user', array('id' => $application->reviewerid));
    $table->data[] = array(get_string('reviewedby', 'local_special_consideration'), fullname($reviewer));
}

// Display supporting documents
if (!empty($application->supportingdocs)) {
    echo $OUTPUT->heading(get_string('supportingdocs', 'local_special_consideration'), 4);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_special_consideration', 'supportingdocs', $application->id, 'filename', false);
    
    // Debugging
    error_log('Files found: ' . count($files));
    error_log('Context ID: ' . $context->id);
    error_log('Application ID: ' . $application->id);

    if ($files) {
        $table = new html_table();
        $table->head = array(get_string('filename', 'local_special_consideration'), get_string('download', 'local_special_consideration'));
        $table->data = array();
        
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $filename);
            $table->data[] = array(
                $filename,
                html_writer::link($url, get_string('download', 'local_special_consideration'))
            );
            // Debugging
            error_log('File: ' . $filename);
            error_log('URL: ' . $url);
        }
        
        echo html_writer::table($table);
    } else {
        echo html_writer::tag('p', get_string('nofiles', 'local_special_consideration'));
    }
}

// Add "Back to Applications" button
$back_url = new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid));
echo $OUTPUT->single_button($back_url, get_string('backtoapplications', 'local_special_consideration'), 'get');

// Display response form for staff
if (has_capability('local/special_consideration:manage', $context) && $application->status == 'pending') {
    $mform = new \local_special_consideration\form\response_form();
    
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
    } else if ($fromform = $mform->get_data()) {
        // Update the application status and feedback
        $application->status = $fromform->status;
        $application->feedback = $fromform->feedback;
        $application->timemodified = time();
        
        $DB->update_record('local_special_consideration', $application);
        
        // Redirect back to the list of applications
        redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)),
                 get_string('applicationupdated', 'local_special_consideration'),
                 null,
                 \core\output\notification::NOTIFY_SUCCESS);
    }
    
    // Set default values if the application already has a status and feedback
    $mform->set_data(array(
        'id' => $application->id,
        'status' => $application->status,
        'feedback' => $application->feedback
    ));
    
    $mform->display();
} elseif (has_capability('local/special_consideration:manage', $context)) {
    // If the application has already been responded to, display the response instead of the form
    echo html_writer::tag('h4', get_string('response', 'local_special_consideration'));
    echo html_writer::tag('p', get_string('status', 'local_special_consideration') . ': ' . $application->status);
    echo html_writer::tag('p', get_string('feedback', 'local_special_consideration') . ': ' . $application->feedback);
}

echo $OUTPUT->footer();