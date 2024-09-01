<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/special_consideration/classes/form/application_form.php');

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$application = $DB->get_record('local_special_consideration', array('id' => $id), '*', MUST_EXIST);

if ($courseid == 0) {
    $courseid = $application->courseid;
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/special_consideration:apply', $context);

if ($application->userid != $USER->id) {
    print_error('nopermissions', 'error', '', 'edit this application');
}

if ($application->status !== 'pending') {
    print_error('cantedit', 'local_special_consideration');
}

$PAGE->set_url(new moodle_url('/local/special_consideration/edit.php', array('id' => $id, 'courseid' => $courseid)));
$PAGE->set_title(get_string('editapplication', 'local_special_consideration'));
$PAGE->set_heading($course->fullname);

$mform = new \local_special_consideration\form\application_form(null, array('course' => $course));

// Fill form with existing data
$application->assessments = json_decode($application->assessments);
$mform->set_data($application);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
} else if ($fromform = $mform->get_data()) {
    $application->type = $fromform->type;
    $application->assessments = json_encode($fromform->assessments);
    $application->dateaffected = $fromform->dateaffected;
    $application->reason = $fromform->reason;
    $application->additionalcomments = $fromform->additionalcomments;
    $application->timemodified = time();
    
    $DB->update_record('local_special_consideration', $application);

    if (!empty($fromform->supportingdocs)) {
        file_save_draft_area_files($fromform->supportingdocs, $context->id, 'local_special_consideration', 'supportingdocs', $application->id);
    }

    redirect(new moodle_url('/local/special_consideration/view.php', array('id' => $application->id, 'courseid' => $courseid)));
}

echo $OUTPUT->header();
echo html_writer::tag('h3', get_string('editapplication', 'local_special_consideration'));
$mform->display();
echo $OUTPUT->footer();