<?php
function local_special_consideration_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/special_consideration:apply', $context)) {
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
