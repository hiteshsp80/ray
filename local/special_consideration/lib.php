<?php
function local_special_consideration_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/special_consideration:apply', $context) || 
        has_capability('local/special_consideration:manage', $context)) {
        $url = new moodle_url('/local/special_consideration/apply.php', array('courseid' => $course->id));
        $node = navigation_node::create(
            get_string('specialconsideration', 'local_special_consideration'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'specialconsideration',
            new pix_icon('i/settings', '')
        );
        $navigation->add_node($node);
    }
}

function local_special_consideration_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    require_login($course);

    if ($filearea !== 'supportingdocs') {
        return false;
    }

    $itemid = array_shift($args);

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    
    if (!$file = $fs->get_file($context->id, 'local_special_consideration', $filearea, $itemid, $filepath, $filename) or $file->is_directory()) {
        return false;
    }

    // Make sure the user has access to this file
    $application = $DB->get_record('local_special_consideration', array('id' => $itemid), '*', MUST_EXIST);
    if ($application->userid != $USER->id && !has_capability('local/special_consideration:manage', $context)) {
        return false;
    }

    // Debugging
    error_log('File found: ' . $filename);
    error_log('File path: ' . $filepath);
    error_log('Item ID: ' . $itemid);

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

function local_special_consideration_get_course_settings($courseid) {
    $settings = array();
    $settings['whocanapprove'] = json_decode(get_config('local_special_consideration', 'whocanapprove_' . $courseid), true);
    $settings['showfutureonly'] = get_config('local_special_consideration', 'showfutureonly_' . $courseid);
    $settings['allownafiles'] = get_config('local_special_consideration', 'allownafiles_' . $courseid);
    $settings['maxfilesize'] = get_config('local_special_consideration', 'maxfilesize_' . $courseid);
    $allowedfiletypes = json_decode(get_config('local_special_consideration', 'allowedfiletypes_' . $courseid), true);
    $settings['allowedfiletypes'] = is_array($allowedfiletypes) ? $allowedfiletypes : array();
    return $settings;
}

function local_special_consideration_extend_settings_navigation($settingsnav, $context) {
    global $CFG, $PAGE;

    // only add this settings item on non-site course pages
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    // only let users with the appropriate capability see this settings item
    if (!has_capability('local/special_consideration:manage', context_course::instance($PAGE->course->id))) {
        return;
    }

    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $strfoo = get_string('specialconsideration', 'local_special_consideration');
        $url = new moodle_url('/local/special_consideration/course_settings.php', array('id' => $PAGE->course->id));
        $foonode = navigation_node::create(
            $strfoo,
            $url,
            navigation_node::NODETYPE_LEAF,
            'specialconsideration',
            'specialconsideration',
            new pix_icon('i/settings', $strfoo)
        );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $foonode->make_active();
        }
        $settingnode->add_node($foonode);
    }
}