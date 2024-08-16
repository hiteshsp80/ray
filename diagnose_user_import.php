<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');

function diagnose_csv($filename) {
    cli_writeln("Diagnosing CSV file: $filename");

    if (!file_exists($filename)) {
        cli_error("Error: CSV file not found: $filename");
    }

    if (!is_readable($filename)) {
        cli_error("Error: Cannot read CSV file. Check file permissions.");
    }

    $handle = fopen($filename, "r");
    if ($handle === FALSE) {
        cli_error("Error: Unable to open CSV file.");
    }

    $line_count = 0;
    $header = null;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $line_count++;
        if ($line_count === 1) {
            $header = $data;
            cli_writeln("CSV Header: " . implode(', ', $header));
            if (count($header) !== 6) {
                cli_writeln("Warning: Expected 6 columns, found " . count($header));
            }
        } else {
            cli_writeln("\nLine $line_count:");
            if (count($data) !== count($header)) {
                cli_writeln("  Warning: Incorrect number of fields");
            }
            for ($i = 0; $i < count($header); $i++) {
                $value = isset($data[$i]) ? $data[$i] : 'N/A';
                cli_writeln("  {$header[$i]}: $value");
            }
        }

        if ($line_count > 5) {  // 只显示前5行数据
            cli_writeln("...");
            break;
        }
    }

    fclose($handle);

    cli_writeln("\nTotal lines in CSV: $line_count");
}

function check_moodle_config() {
    global $CFG, $DB;

    cli_writeln("\nChecking Moodle Configuration:");
    cli_writeln("Moodle Version: " . $CFG->version);
    cli_writeln("Authentication Plugins Enabled: " . implode(', ', get_enabled_auth_plugins()));
    cli_writeln("Default Authentication Method: " . $CFG->auth);

    // 检查是否可以连接到数据库
    try {
        $user_count = $DB->count_records('user');
        cli_writeln("Database connection successful. Total users in database: $user_count");
    } catch (Exception $e) {
        cli_writeln("Error connecting to database: " . $e->getMessage());
    }
}

// 主脚本逻辑
cli_writeln("Starting diagnostic process...");

$csv_filename = __DIR__ . '/users.csv';
diagnose_csv($csv_filename);
check_moodle_config();

cli_writeln("\nDiagnostic complete. Please review the output above for any issues.");
?>