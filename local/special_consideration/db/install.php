<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_special_consideration_install() {
    global $CFG;
    require_once($CFG->dirroot . '/mod/assign/renderer.php');
}
