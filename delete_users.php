<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/lib.php');

function delete_users_from_csv($filename) {
    global $CFG, $DB;

    $deleted = 0;
    $skipped = 0;

    cli_writeln("Opening CSV file: $filename");
    $handle = fopen($filename, "r");
    if ($handle !== FALSE) {
        // 跳过标题行
        fgetcsv($handle, 1000, ",");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $username = trim($data[0]);

            cli_writeln("\nProcessing user: $username");

            $user = $DB->get_record('user', array('username' => $username));
            if ($user) {
                // 检查是否是管理员或其他关键用户
                if ($user->id == 1 || is_siteadmin($user->id)) {
                    cli_writeln("  Skipping deletion of admin user: $username");
                    $skipped++;
                    continue;
                }

                try {
                    delete_user($user);
                    cli_writeln("  User deleted successfully: $username");
                    $deleted++;
                } catch (Exception $e) {
                    cli_writeln("  Error deleting user $username: " . $e->getMessage());
                    $skipped++;
                }
            } else {
                cli_writeln("  User not found: $username");
                $skipped++;
            }
        }
        fclose($handle);
    } else {
        cli_error("Error: Unable to open CSV file.");
    }

    cli_writeln("\nDeletion Summary:");
    cli_writeln("  Users deleted: $deleted");
    cli_writeln("  Users skipped: $skipped");

    return array($deleted, $skipped);
}

// 主脚本逻辑
cli_writeln("Starting user deletion process...");

$csv_filename = __DIR__ . '/users.csv';
list($deleted, $skipped) = delete_users_from_csv($csv_filename);

cli_writeln("\nProcess completed:");
cli_writeln("Total users deleted: $deleted");
cli_writeln("Total users skipped: $skipped");
?>