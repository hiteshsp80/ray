<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/config.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir.'/accesslib.php');

function enrolment_courses() {
    global $DB;

    $courses = $DB->get_records('course', array(), '', 'id, shortname');
    $self_plugin = enrol_get_plugin('self');
    $manual_plugin = enrol_get_plugin('manual');
    $cohort_plugin = enrol_get_plugin('cohort');

    foreach ($courses as $course) {
        if ($course->id == 1) {  // Skip the site home page
            continue;
        }

        // Enable self-enrolment
        $self_instances = $DB->get_records('enrol', array('courseid'=>$course->id, 'enrol'=>'self'), 'id');
        if (empty($self_instances)) {
            $self_plugin->add_instance($course);
            cli_writeln("Enabled self-enrolment for course: {$course->shortname}");
        } else {
            cli_writeln("Self-enrolment already enabled for course: {$course->shortname}");
        }

        // Enable manual enrolment
        $manual_instances = $DB->get_records('enrol', array('courseid'=>$course->id, 'enrol'=>'manual'), 'id');
        if (empty($manual_instances)) {
            $manual_plugin->add_instance($course);
            cli_writeln("Enabled manual enrolment for course: {$course->shortname}");
        } else {
            cli_writeln("Manual enrolment already enabled for course: {$course->shortname}");
        }

        // Enable cohort enrolment
        $cohort_instances = $DB->get_records('enrol', array('courseid'=>$course->id, 'enrol'=>'cohort'), 'id');
        if (empty($cohort_instances)) {
            $cohort_plugin->add_instance($course);
            cli_writeln("Enabled cohort enrolment for course: {$course->shortname}");
        } else {
            cli_writeln("Cohort enrolment already enabled for course: {$course->shortname}");
        }
    }
}

function grant_enrolment_capabilities() {
    global $DB;

    $studentrole = $DB->get_record('role', array('shortname' => 'student'));
    $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $managerrole = $DB->get_record('role', array('shortname' => 'manager'));

    if (!$studentrole || !$teacherrole || !$managerrole) {
        cli_error("One or more required roles not found");
    }

    $context = context_system::instance();

    // Grant self-enrolment capability to students
    assign_capability('enrol/self:enrolself', CAP_ALLOW, $studentrole->id, $context->id);
    cli_writeln("Granted self-enrolment capability to student role");

    // Grant manual enrolment capability to teachers and managers
    assign_capability('enrol/manual:enrol', CAP_ALLOW, $teacherrole->id, $context->id);
    assign_capability('enrol/manual:enrol', CAP_ALLOW, $managerrole->id, $context->id);
    cli_writeln("Granted manual enrolment capability to teacher and manager roles");

    // Grant cohort enrolment capability to managers
    assign_capability('enrol/cohort:config', CAP_ALLOW, $managerrole->id, $context->id);
    cli_writeln("Granted cohort enrolment capability to manager role");
}

// Main script logic
cli_writeln("Starting to enable all enrolment methods for all courses...");
enable_all_enrolment_methods();

cli_writeln("\nGranting necessary enrolment capabilities to roles...");
grant_enrolment_capabilities();

cli_writeln("\nProcess completed. All enrolment methods have been enabled and necessary capabilities granted.");
?>