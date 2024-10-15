<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/form/course_settings_form.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);
require_capability('local/special_consideration:manage', $context);

$PAGE->set_url('/local/special_consideration/course_settings.php', array('id' => $id));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($course->shortname . ': ' . get_string('specialconsideration', 'local_special_consideration'));
$PAGE->set_heading($course->fullname);

$mform = new course_settings_form(null, array('id' => $id));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $id)));
} else if ($data = $mform->get_data()) {
    // Save course-specific settings
    set_config('whocanapprove_' . $id, json_encode($data->whocanapprove), 'local_special_consideration');
    set_config('showfutureonly_' . $id, $data->showfutureonly, 'local_special_consideration');
    set_config('allownafiles_' . $id, $data->allownafiles, 'local_special_consideration');
    set_config('maxfilesize_' . $id, $data->maxfilesize, 'local_special_consideration');
    set_config('allowedfiletypes_' . $id, json_encode($data->allowedfiletypes), 'local_special_consideration');

    redirect(new moodle_url('/course/view.php', array('id' => $data->id)), get_string('settingssaved', 'local_special_consideration'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('specialconsideration', 'local_special_consideration'));

$mform->display();

echo $OUTPUT->footer();