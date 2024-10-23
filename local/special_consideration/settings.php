<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_special_consideration_settings', get_string('pluginname', 'local_special_consideration'));
    $ADMIN->add('localplugins', $settings);

    // Who can approve/deny
    $approvers = array(
        'teacher' => get_string('teacher', 'local_special_consideration'),
        'coteacher' => get_string('coteacher', 'local_special_consideration'),
        'other1' => get_string('other1', 'local_special_consideration'),
        'other2' => get_string('other2', 'local_special_consideration')
    );
    $settings->add(new admin_setting_configmulticheckbox(
        'local_special_consideration/whocanapprove',
        get_string('whocanapprove', 'local_special_consideration'),
        '',
        array('teacher' => 1),
        $approvers
    ));

    // Only show future assessments
    $settings->add(new admin_setting_configcheckbox(
        'local_special_consideration/showfutureonly',
        get_string('showfutureonly', 'local_special_consideration'),
        get_string('showfutureonly_desc', 'local_special_consideration'),
        0
    ));

    // Allow N/A files
    $naOptions = array(1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5');
    $settings->add(new admin_setting_configselect(
        'local_special_consideration/allownafiles',
        get_string('allownafiles', 'local_special_consideration'),
        '',
        1,
        $naOptions
    ));

    // Max file size
    $sizeOptions = array(
        2097152 => '2 MB',
        1048576 => '1 MB',
        512000 => '500 KB',
        102400 => '100 KB',
        51200 => '50 KB',
        10240 => '10 KB'
    );
    $settings->add(new admin_setting_configselect(
        'local_special_consideration/maxfilesize',
        get_string('maxfilesize', 'local_special_consideration'),
        '',
        1048576,
        $sizeOptions
    ));

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
    $settings->add(new admin_setting_configmulticheckbox(
        'local_special_consideration/allowedfiletypes',
        get_string('allowedfiletypes', 'local_special_consideration'),
        '',
        array('pdf' => 1),
        $allowedTypes
    ));
}