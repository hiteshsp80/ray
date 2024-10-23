<?php
namespace local_special_consideration\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class application_form extends \moodleform {
    protected function definition() {
        global $USER, $DB, $COURSE, $OUTPUT;

        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $editing = !empty($this->_customdata['editing']);

        // Get course-specific settings
        $settings = $this->get_course_settings($course->id);

        // Retrieve the special announcement
        $specialannouncement = get_config('local_special_consideration', 'specialannouncement_' . $course->id);

        // Add hidden fields
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);

        if ($editing) {
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
        }

        // Display the special announcement if it exists
        if (!empty($specialannouncement)) {
            $mform->addElement('html', 
            '<div style="border: 2px solid #000; padding: 20px; margin: 20px;">' . 
            '<strong>Special Announcement:</strong>' .
            $OUTPUT->box(format_text($specialannouncement, FORMAT_HTML), 'generalbox') . 
            '</div>');
        }
        
        $mform->addElement('html', '<h3>' . get_string('newapplication', 'local_special_consideration') . '</h3> </br>');

        // Student Name (auto-filled)
        $mform->addElement('static', 'studentname', get_string('studentname', 'local_special_consideration'), fullname($USER));

        // Student ID (auto-filled)
        $mform->addElement('static', 'studentid', get_string('studentid', 'local_special_consideration'), $USER->id);

        // Application Type
        $types = array(
            '' => get_string('selecttype', 'local_special_consideration'),
            'extension' => get_string('extension', 'local_special_consideration'),
            'grade_consideration' => get_string('grade_consideration', 'local_special_consideration'),
            'dispute_grade' => get_string('dispute_grade', 'local_special_consideration')
        );
        $mform->addElement('select', 'type', get_string('applicationtype', 'local_special_consideration'), $types);
        $mform->addRule('type', null, 'required', null, 'client');

        // Affected Assessment
        $modinfo = get_fast_modinfo($course);
        $assessments = array('' => get_string('selectassessment', 'local_special_consideration'));
        foreach ($modinfo->get_cms() as $cm) {
            if (in_array($cm->modname, ['assign', 'quiz'])) {
                if (!$settings['showfutureonly'] || $cm->get_course_module_record()->duedate > time()) {
                    $assessments[$cm->id] = $cm->name;
                }
            }
        }
        $mform->addElement('select', 'affectedassessment', get_string('affectedassessment', 'local_special_consideration'), $assessments);
        $mform->addRule('affectedassessment', null, 'required', null, 'client');

        // Date Affected
        $mform->addElement('date_selector', 'dateaffected', get_string('dateaffected', 'local_special_consideration'));
        $mform->hideIf('dateaffected', 'type', 'neq', 'extension');
        

        // Reason for special consideration
        $mform->addElement('textarea', 'reason', get_string('reason', 'local_special_consideration'));
        $mform->setType('reason', PARAM_TEXT);
        $mform->addRule('reason', null, 'required', null, 'client');

        // Supporting Documentation Upload
        $filemanageroptions = array(
            'subdirs' => 0,
            'maxbytes' => $settings['maxfilesize'],
            'maxfiles' => $settings['allownafiles'],
            'accepted_types' => $settings['allowedfiletypes']
        );
        $mform->addElement('filemanager', 'supportingdocs', get_string('supportingdocs', 'local_special_consideration'), null, $filemanageroptions);

        // Additional Comments
        $mform->addElement('textarea', 'additionalcomments', get_string('additionalcomments', 'local_special_consideration'));
        $mform->setType('additionalcomments', PARAM_TEXT);

        $this->add_action_buttons();

        $mform->addElement('html', '
            <script type="text/javascript">
            (function($) {
                $(document).ready(function() {
                    function updateDateLabel() {
                        var type = $("#id_type").val();
                        var dateLabel = type === "extension" 
                            ? "' . get_string('requested_deadline', 'local_special_consideration') . '"
                            : "' . get_string('dateaffected', 'local_special_consideration') . '";
                        $("label[for=\'id_dateaffected\']").text(dateLabel);
                    }
                    $("#id_type").change(updateDateLabel);
                    updateDateLabel(); // Run once on page load
                });
            })(jQuery);
            </script>
        ');

        $mform->addElement('html', '
        <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                function updateDateField() {
                    var type = $("#id_type").val();
                    if (type === "extension") {
                        $("#fitem_id_dateaffected").show();
                    } else {
                        $("#fitem_id_dateaffected").hide();
                    }
                }
                $("#id_type").change(updateDateField);
                updateDateField(); // Run once on page load
            });
        })(jQuery);
        </script>
    ');

    }

    protected function get_course_settings($courseid) {
        $settings = array();
        $settings['whocanapprove'] = json_decode(get_config('local_special_consideration', 'whocanapprove_' . $courseid), true) ?: array();
        $settings['showfutureonly'] = get_config('local_special_consideration', 'showfutureonly_' . $courseid);
        $settings['allownafiles'] = get_config('local_special_consideration', 'allownafiles_' . $courseid);
        $settings['maxfilesize'] = get_config('local_special_consideration', 'maxfilesize_' . $courseid);
        $settings['allowedfiletypes'] = json_decode(get_config('local_special_consideration', 'allowedfiletypes_' . $courseid), true) ?: array();
        
        // Convert allowedfiletypes to the format expected by the form
        $allowedfiletypes = array();
        if (is_array($settings['allowedfiletypes'])) {
            foreach ($settings['allowedfiletypes'] as $type => $allowed) {
                if ($allowed) {
                    $allowedfiletypes[] = '.' . $type;
                }
            }
        }
        $settings['allowedfiletypes'] = $allowedfiletypes;
    
        return $settings;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Additional validation based on course settings can be added here
        
        return $errors;
    }

    
}