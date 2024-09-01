<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/special_consideration/classes/form/withdraw_form.php');

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
    print_error('nopermissions', 'error', '', 'withdraw this application');
}

$PAGE->set_url(new moodle_url('/local/special_consideration/withdraw.php', array('id' => $id, 'courseid' => $courseid)));
$PAGE->set_title(get_string('withdrawapplication', 'local_special_consideration'));
$PAGE->set_heading($course->fullname);

$mform = new \local_special_consideration\form\withdraw_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
} else if ($fromform = $mform->get_data()) {
    $application->status = 'withdrawn';
    $application->withdrawreason = $fromform->reason;
    $application->timemodified = time();
    
    $DB->update_record('local_special_consideration', $application);

    redirect(new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid)));
}

echo $OUTPUT->header();
echo html_writer::tag('h3', get_string('withdrawapplication', 'local_special_consideration'));
$mform->display();
echo $OUTPUT->footer();