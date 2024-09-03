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

if ($application->userid == $USER->id) {
    require_capability('local/special_consideration:apply', $context);
} else {
    require_capability('local/special_consideration:manage', $context);
}

$PAGE->set_url(new moodle_url('/local/special_consideration/view.php', array('id' => $id, 'courseid' => $courseid)));
$PAGE->set_title(get_string('viewapplication', 'local_special_consideration'));
$PAGE->set_heading($course->fullname);

$user = $DB->get_record('user', array('id' => $application->userid));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('applicationdetails', 'local_special_consideration'));

// Display application details
$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->data[] = array(get_string('applicant', 'local_special_consideration'), fullname($user));
$table->data[] = array(get_string('studentid', 'local_special_consideration'), $user->idnumber);
$table->data[] = array(get_string('applicationtype', 'local_special_consideration'), $application->type);

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

// Add "Back to Applications" button
$back_url = new moodle_url('/local/special_consideration/apply.php', array('courseid' => $courseid));
echo $OUTPUT->single_button($back_url, get_string('backtoapplications', 'local_special_consideration'), 'get');

// Display response form for staff
if (has_capability('local/special_consideration:manage', $context)) {
    $mform = new \local_special_consideration\form\response_form();
    $mform->display();
}

echo $OUTPUT->footer();