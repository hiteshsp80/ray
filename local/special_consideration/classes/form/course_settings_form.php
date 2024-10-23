<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class course_settings_form extends moodleform {
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        $courseid = $this->_customdata['id'];

        // Add a hidden field for course id
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $courseid);

        // Add a text box for special announcements
        $mform->addElement('header', 'specialconsideration', get_string('specialconsideration', 'local_special_consideration'));
        $mform->addElement('editor', 'specialannouncement', get_string('specialannouncement', 'local_special_consideration'), null, array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => context_course::instance($courseid)));
        $mform->setType('specialannouncement', PARAM_RAW);

        // Who can approve/deny
        $approvers = array(
            'teacher' => get_string('teacher', 'local_special_consideration'),
            'coteacher' => get_string('coteacher', 'local_special_consideration'),
            'other1' => get_string('other1', 'local_special_consideration'),
            'other2' => get_string('other2', 'local_special_consideration')
        );
        $mform->addElement('advcheckbox', 'whocanapprove', get_string('whocanapprove', 'local_special_consideration'), $approvers['teacher'], array('group' => 1), array(0, 1));
        $mform->addElement('advcheckbox', '', '', $approvers['coteacher'], array('group' => 1), array(0, 1));
        $mform->addElement('advcheckbox', '', '', $approvers['other1'], array('group' => 1), array(0, 1));
        $mform->addElement('advcheckbox', '', '', $approvers['other2'], array('group' => 1), array(0, 1));

        // Only show future assessments
        $mform->addElement('advcheckbox', 'showfutureonly', get_string('showfutureonly', 'local_special_consideration'), get_string('showfutureonly_desc', 'local_special_consideration'));

        // Allow N/A files
        $naOptions = array(1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5');
        $mform->addElement('select', 'allownafiles', get_string('allownafiles', 'local_special_consideration'), $naOptions);

        // Max file size
        $sizeOptions = array(
            '2097152' => '2 MB',
            '1048576' => '1 MB',
            '512000' => '500 KB',
            '102400' => '100 KB',
            '51200' => '50 KB',
            '10240' => '10 KB'
        );
        $mform->addElement('select', 'maxfilesize', get_string('maxfilesize', 'local_special_consideration'), $sizeOptions);

        // Allowed file types
        $allowedTypes = array(
            'doc' => get_string('filetypedoc', 'local_special_consideration'),
            'docx' => get_string('filetypedocx', 'local_special_consideration'),
            'odt' => get_string('filetypeodt', 'local_special_consideration'),
            'html' => get_string('filetypehtml', 'local_special_consideration'),
            'txt' => get_string('filetypetxt', 'local_special_consideration'),
            'rtf' => get_string('filetypertf', 'local_special_consideration'),
            'pdf' => get_string('filetypepdf', 'local_special_consideration')
        );
        $filetypesGroup = array();
        foreach ($allowedTypes as $key => $label) {
            $filetypesGroup[] = $mform->createElement('advcheckbox', 'allowedfiletypes['.$key.']', '', $label, array('group' => 1), array(0, 1));
        }
        $mform->addGroup($filetypesGroup, 'allowedfiletypesgroup', get_string('allowedfiletypes', 'local_special_consideration'), '<br>', false);

        $this->add_action_buttons();

        // Set default values
        $this->set_data(array(
            'whocanapprove' => json_decode(get_config('local_special_consideration', 'whocanapprove_' . $courseid), true),
            'showfutureonly' => get_config('local_special_consideration', 'showfutureonly_' . $courseid),
            'allownafiles' => get_config('local_special_consideration', 'allownafiles_' . $courseid),
            'maxfilesize' => get_config('local_special_consideration', 'maxfilesize_' . $courseid),
            'allowedfiletypes' => json_decode(get_config('local_special_consideration', 'allowedfiletypes_' . $courseid), true),
            'specialannouncement' => array('text' => get_config('local_special_consideration', 'specialannouncement_' . $courseid), 'format' => FORMAT_HTML)
        ));
    }
}