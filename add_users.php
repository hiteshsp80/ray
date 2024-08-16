<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/lib.php');

function add_users_from_csv($filename) {
    global $CFG, $DB;

    $added = 0;
    $skipped = 0;

    cli_writeln("Opening CSV file: $filename");
    $handle = fopen($filename, "r");
    if ($handle !== FALSE) {
        
        fgetcsv($handle, 1000, ",");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $username = trim($data[0]);
            $password = trim($data[1]);
            $firstname = trim($data[2]);
            $lastname = trim($data[3]);
            $email = trim($data[4]);
            $role = trim($data[5]);

            cli_writeln("\nProcessing user: $username");

            
            if ($DB->record_exists('user', array('username' => $username))) {
                cli_writeln("  User already exists, skipping: $username");
                $skipped++;
                continue;
            }

            $user = new stdClass();
            $user->auth = 'manual';
            $user->confirmed = 1;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->username = $username;
            $user->password = $password;  
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->email = $email;

            try {
                $userid = user_create_user($user, true, false);  
                cli_writeln("  User created successfully. User ID: $userid");

                
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

                $added++;
            } catch (Exception $e) {
                cli_writeln("  Error creating user: " . $e->getMessage());
                $skipped++;
            }
        }
        fclose($handle);
    } else {
        cli_error("Error: Unable to open CSV file.");
    }

    cli_writeln("\nImport Summary:");
    cli_writeln("  Users added: $added");
    cli_writeln("  Users skipped: $skipped");

    return array($added, $skipped);
}


cli_writeln("Starting user import process...");

$csv_filename = __DIR__ . '/users.csv';
list($added, $skipped) = add_users_from_csv($csv_filename);

cli_writeln("\nProcess completed:");
cli_writeln("Total users added: $added");
cli_writeln("Total users skipped: $skipped");
?>