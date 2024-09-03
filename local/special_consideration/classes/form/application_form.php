<?php
namespace local_special_consideration\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class application_form extends \moodleform {
    protected function definition() {
        global $USER, $DB;

        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $editing = !empty($this->_customdata['editing']);

        // Add hidden fields
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        if ($editing) {
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
        }

        // Student Name (auto-filled)
        $mform->addElement('static', 'studentname', get_string('studentname', 'local_special_consideration'), fullname($USER));

        // Student ID (auto-filled)
        $mform->addElement('text', 'studentid', get_string('studentid', 'local_special_consideration'));
        $mform->setType('studentid', PARAM_TEXT);
        $mform->setDefault('studentid', $USER->idnumber);
        $mform->addRule('studentid', null, 'required', null, 'client');

        // Application Type
        $types = array(
            '' => get_string('selecttype', 'local_special_consideration'),
            'extension' => get_string('extension', 'local_special_consideration'),
            'grade_consideration' => get_string('grade_consideration', 'local_special_consideration'),
            'dispute_grade' => get_string('dispute_grade', 'local_special_consideration')
        );
        $mform->addElement('select', 'type', get_string('applicationtype', 'local_special_consideration'), $types);
        $mform->addRule('type', null, 'required', null, 'client');

        // Affected Assessment(s)
        $modinfo = get_fast_modinfo($course);
        $assessments = array('' => get_string('selectassessment', 'local_special_consideration'));
        foreach ($modinfo->get_cms() as $cm) {
            if (in_array($cm->modname, ['assign', 'quiz'])) {
                $assessments[$cm->id] = $cm->name;
                // $assessments[$cm->name] = $cm->name;
            }
        }
        $mform->addElement('select', 'affectedassessment', get_string('affectedassessment', 'local_special_consideration'), $assessments);
        $mform->addRule('affectedassessment', null, 'required', null, 'client');

        // Date(s) Affected
        $mform->addElement('date_selector', 'dateaffected', get_string('dateaffected', 'local_special_consideration'));
        $mform->addRule('dateaffected', null, 'required', null, 'client');

        // Reason for special consideration
        $mform->addElement('textarea', 'reason', get_string('reason', 'local_special_consideration'));
        $mform->setType('reason', PARAM_TEXT);
        $mform->addRule('reason', null, 'required', null, 'client');

        // Supporting Documentation Upload
        $mform->addElement('filemanager', 'supportingdocs', get_string('supportingdocs', 'local_special_consideration'), null, 
                           array('maxbytes' => 10485760, 'accepted_types' => '*'));

        // Additional Comments
        $mform->addElement('textarea', 'additionalcomments', get_string('additionalcomments', 'local_special_consideration'));
        $mform->setType('additionalcomments', PARAM_TEXT);

        $mform->addElement('hidden', 'courseid', optional_param('courseid', 0, PARAM_INT));
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }
}