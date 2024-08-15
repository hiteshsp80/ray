<?php
// This script must be run from the command line, not via a web server.
define('CLI_SCRIPT', true);

// Print a starting message.
echo "Script is starting...\n";

// Load the Moodle configuration and necessary libraries.
require_once(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/course/lib.php');

echo "Libraries loaded.\n";

// Path to the CSV file.
$csvFile = __DIR__.'/courses.csv';

// Check if the file exists.
if (!file_exists($csvFile)) {
    die("CSV file not found: $csvFile\n");
}

// Open the CSV file.
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // Read the header row.
    fgetcsv($handle);

    // Loop through each row in the CSV file.
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $fullname = $data[0];
        $shortname = $data[1];
        $categoryid = isset($data[2]) ? (int)$data[2] : 1; // Default category ID is 1

        // Create the course.
        create_custom_course($fullname, $shortname, $categoryid);
    }

    fclose($handle);
} else {
    echo "Unable to open CSV file.\n";
}

echo "Course creation completed.\n";

// Function to create a course.
function create_custom_course($fullname, $shortname, $categoryid = 1) {
    global $DB;

    // Create a new course object.
    $course = new stdClass();
    $course->fullname = $fullname;
    $course->shortname = $shortname;
    $course->category = $categoryid;

    // Set other course defaults as needed.
    // $course->summary = "Summary for $fullname";
    // $course->format = "topics";

    // Create the course in Moodle.
    try {
        $newcourse = create_course($course);
        echo "Created course: $fullname (Shortname: $shortname)\n";
    } catch (Exception $e) {
        echo "Error creating course $fullname: " . $e->getMessage() . "\n";
    }
}
