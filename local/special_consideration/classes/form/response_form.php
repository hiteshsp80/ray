<?php
namespace local_special_consideration\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class response_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $statuses = array(
            'approved' => get_string('approved', 'local_special_consideration'),
            'declined' => get_string('declined', 'local_special_consideration'),
            'more_info' => get_string('more_info', 'local_special_consideration')
        );
        $mform->addElement('select', 'status', get_string('status', 'local_special_consideration'), $statuses);
        $mform->addRule('status', null, 'required', null, 'client');

        $mform->addElement('textarea', 'feedback', get_string('feedback', 'local_special_consideration'));
        $mform->setType('feedback', PARAM_TEXT);
        $mform->addRule('feedback', null, 'required', null, 'client');

        $this->add_action_buttons();
    }
}