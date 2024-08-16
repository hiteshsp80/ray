<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/accesslib.php');

function import_users_from_csv($filename) {
    global $CFG, $DB;

    $count = 0;
    $skipped = 0;

    cli_writeln("Opening CSV file: $filename");
    $handle = fopen($filename, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        cli_writeln("CSV Header: " . implode(', ', $header));

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $username = trim($data[0]);
            $password = trim($data[1]);
            $firstname = trim($data[2]);
            $lastname = trim($data[3]);
            $email = trim($data[4]);
            $role = trim($data[5]);

            cli_writeln("\nProcessing user: $username");

            // 检查用户是否已存在
            $existing_user = $DB->get_record('user', array('username' => $username));
            if (!$existing_user) {
                cli_writeln("  User does not exist. Creating new user.");
                $user = new stdClass();
                $user->auth = 'manual';
                $user->confirmed = 1;
                $user->mnethostid = $CFG->mnet_localhost_id;
                $user->username = $username;
                $user->password = hash_internal_user_password($password);
                $user->firstname = $firstname;
                $user->lastname = $lastname;
                $user->email = $email;

                try {
                    $userid = user_create_user($user, false, false);
                    cli_writeln("  User created successfully. User ID: $userid");

                    // 分配角色
                    $systemcontext = context_system::instance();
                    switch ($role) {
                        case 'student':
                            $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
                            break;
                        case 'admin':
                            $roleid = $DB->get_field('role', 'id', array('shortname' => 'manager'));
                            break;
                        case 'teacher':
                            $roleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
                            break;
                        default:
                            $roleid = null;
                    }

                    if ($roleid) {
                        role_assign($roleid, $userid, $systemcontext->id);
                        cli_writeln("  Role '$role' assigned successfully.");
                    } else {
                        cli_writeln("  Warning: Unknown role '$role'. No role assigned.");
                    }

                    $count++;
                } catch (Exception $e) {
                    cli_writeln("  Error creating user: " . $e->getMessage());
                }
            } else {
                cli_writeln("  User already exists. User ID: {$existing_user->id}");
                $skipped++;
            }
        }
        fclose($handle);
    } else {
        cli_error("Error: Unable to open CSV file.");
    }

    cli_writeln("\nImport Summary:");
    cli_writeln("  Users added: $count");
    cli_writeln("  Users skipped (already exist): $skipped");

    return $count;
}

// 主脚本逻辑
cli_writeln("Starting user import process...");

$csv_filename = __DIR__ . '/users.csv';
$imported_users = import_users_from_csv($csv_filename);

cli_writeln("\nTotal users imported: $imported_users");

cli_writeln("\nMoodle Configuration:");
cli_writeln("Moodle Version: " . $CFG->version);
cli_writeln("Authentication Plugins Enabled: " . implode(', ', get_enabled_auth_plugins()));
cli_writeln("Default Authentication Method: " . $CFG->auth);
?>