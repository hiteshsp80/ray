<?php
function local_special_consideration_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;

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

        // Add JavaScript to create modal
        $PAGE->requires->js_call_amd('local_special_consideration/modal', 'init', array($url->out()));
    }
}