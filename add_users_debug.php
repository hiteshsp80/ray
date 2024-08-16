<?php
define('CLI_SCRIPT', true);

require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/accesslib.php');

function import_users_from_csv($filename) {
    global $CFG, $DB;

    $count = 0;
    $handle = fopen($filename, "r");
    if ($handle !== FALSE) {
        fgetcsv($handle, 1000, ","); // 跳过标题行

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $username = $data[0];
            $password = $data[1];
            $firstname = $data[2];
            $lastname = $data[3];
            $email = $data[4];
            $role = $data[5];

            // 检查用户是否已存在
            $existing_user = $DB->get_record('user', array('username' => $username));
            if (!$existing_user) {
                $user = new stdClass();
                $user->auth = 'manual';
                $user->confirmed = 1;
                $user->mnethostid = $CFG->mnet_localhost_id;
                $user->username = $username;
                $user->firstname = $firstname;
                $user->lastname = $lastname;
                $user->email = $email;
                $user->password = hash_internal_user_password($password); // 正确处理密码

                $userid = user_create_user($user, false, false);

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
                }

                $count++;
                cli_writeln("Added user: $username (ID: $userid)");

                // 验证用户是否可以登录
                $auth = get_auth_plugin($user->auth);
                if ($auth->user_login($username, $password)) {
                    cli_writeln("User $username can log in successfully.");
                } else {
                    cli_writeln("WARNING: User $username cannot log in. Please check authentication settings.");
                }
            } else {
                cli_writeln("User already exists: $username (ID: {$existing_user->id})");
            }
        }
        fclose($handle);
    }

    return $count;
}

// 主脚本逻辑
cli_writeln("Starting user import process...");

$csv_filename = __DIR__ . '/users.csv';
if (!file_exists($csv_filename)) {
    cli_error("Error: CSV file not found: $csv_filename");
}

$imported_users = import_users_from_csv($csv_filename);

cli_writeln("Successfully imported $imported_users users to Moodle.");

// 额外的调试信息
cli_writeln("\nDebug Information:");
cli_writeln("Moodle Version: " . $CFG->version);
cli_writeln("Authentication Plugins Enabled: " . implode(', ', get_enabled_auth_plugins()));
cli_writeln("Default Authentication Method: " . $CFG->auth);
?>
