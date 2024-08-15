<?php
define('CLI_SCRIPT', true);

// Moodle configuration
require_once(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

cli_writeln("Script started.");

// Function to create a course
function create_course($fullname, $shortname, $category = 1) {
    $course = new stdClass();
    $course->fullname = $fullname;
    $course->shortname = $shortname;
    $course->category = $category;
    return create_course($course);
}

// Function to create a user
function create_user($username, $password, $firstname, $lastname, $email, $role) {
    global $DB, $CFG;
    $user = new stdClass();
    $user->username = $username;
    $user->password = password_hash($password, PASSWORD_DEFAULT);
    $user->firstname = $firstname;
    $user->lastname = $lastname;
    $user->email = $email;
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    
    $userid = user_create_user($user);
    
    // Assign role
    $context = context_system::instance();
    $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
    if ($roleid) {
        role_assign($roleid, $userid, $context->id);
    } else {
        cli_writeln("Warning: Role not found: $role");
    }
    
    return $userid;
}

// Function to read CSV file
function read_csv($filename) {
    $data = [];
    if (($handle = fopen($filename, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

// Read courses from CSV
cli_writeln("Reading courses.csv...");
$courses_file = __DIR__.'/courses.csv';
if (!file_exists($courses_file)) {
    cli_writeln("Error: courses.csv not found in " . __DIR__);
    exit(1);
}
$courses = read_csv($courses_file);
// Skip header row
array_shift($courses);
cli_writeln("Found " . count($courses) . " courses.");

// Read users from CSV
cli_writeln("Reading users.csv...");
$users_file = __DIR__.'/users.csv';
if (!file_exists($users_file)) {
    cli_writeln("Error: users.csv not found in " . __DIR__);
    exit(1);
}
$users = read_csv($users_file);
// Skip header row
array_shift($users);
cli_writeln("Found " . count($users) . " users.");

// Create courses
foreach ($courses as $course) {
    try {
        if (count($course) < 2) {
            cli_writeln("Warning: Skipping invalid course entry");
            continue;
        }
        $createdCourse = create_course($course[0], $course[1]);
        cli_writeln("Created course: {$course[0]}");
    } catch (Exception $e) {
        cli_writeln("Error creating course {$course[0]}: " . $e->getMessage());
    }
}

// Create users
foreach ($users as $user) {
    try {
        if (count($user) < 6) {
            cli_writeln("Warning: Skipping invalid user entry");
            continue;
        }
        $createdUser = create_user($user[0], $user[1], $user[2], $user[3], $user[4], $user[5]);
        cli_writeln("Created user: {$user[2]} {$user[3]} with role {$user[5]}");
    } catch (Exception $e) {
        cli_writeln("Error creating user {$user[2]} {$user[3]}: " . $e->getMessage());
    }
}

cli_writeln("Bulk import completed.");
?>