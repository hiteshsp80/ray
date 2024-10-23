<?php
defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2024090500;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2022041912.00;  // Requires this Moodle version
$plugin->component = 'local_special_consideration'; // Full name of the plugin (used for diagnostics)


$plugin->cron  = 0; // Period for cron to check this module (secs)
$plugin->release = 'v0.4.9'; // Period for cron to check this module (secs)

$plugin->maturity = MATURITY_STABLE; // Period for cron to check this module (secs)

?>