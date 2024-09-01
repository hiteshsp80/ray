<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/special_consideration/classes/form/response_form.php');

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

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

$mform = new \local_special_consideration\form\response_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
} else if ($fromform = $mform->get_data()) {
    $application->status = $fromform->status;
    $application->feedback = $fromform->feedback;
    $application->timemodified = time();
    
    $DB->update_record('local_special_consideration', $application);

    redirect(new moodle_url('/local/special_consideration/view.php', array('id' => $id, 'courseid' => $courseid)));
}

echo $OUTPUT->header();

// Display application details
$user = $DB->get_record('user', array('id' => $application->userid));
echo html_writer::tag('h3', get_string('applicationdetails', 'local_special_consideration'));
echo html_writer::tag('p', get_string('applicant', 'local_special_consideration') . ': ' . fullname($user));
echo html_writer::tag('p', get_string('type', 'local_special_consideration') . ': ' . $application->type);
echo html_writer::tag('p', get_string('dateaffected', 'local_special_consideration') . ': ' . userdate($application->dateaffected));
echo html_writer::tag('p', get_string('reason', 'local_special_consideration') . ': ' . $application->reason);
echo html_writer::tag('p', get_string('additionalcomments', 'local_special_consideration') . ': ' . $application->additionalcomments);
echo html_writer::tag('p', get_string('status', 'local_special_consideration') . ': ' . $application->status);

// Display response form for staff
if (has_capability('local/special_consideration:manage', $context)) {
    $mform->display();
}

echo $OUTPUT->footer();