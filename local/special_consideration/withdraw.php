<?php
require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$ajax = optional_param('ajax', 0, PARAM_INT);

require_login();
require_sesskey();

$application = $DB->get_record('local_special_consideration', array('id' => $id), '*', MUST_EXIST);

if ($application->userid != $USER->id) {
    print_error('nopermissions', 'error', '', 'withdraw this application');
}

if ($application->status !== 'pending') {
    print_error('cannotwithdraw', 'local_special_consideration');
}

if ($ajax) {
    $application->status = 'withdrawn';
    $application->timemodified = time();
    
    if ($DB->update_record('local_special_consideration', $application)) {
        // Delete associated files
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_special_consideration', 'supportingdocs', $application->id);
        
        // Update the application record to remove the file area ID
        $application->supportingdocs = null;
        $DB->update_record('local_special_consideration', $application);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => get_string('errorwithdrawing', 'local_special_consideration')]);
    }
    die();
}


redirect(new moodle_url('/local/special_consideration/apply.php'));