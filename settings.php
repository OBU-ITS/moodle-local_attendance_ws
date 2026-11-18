<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Standard lib
 *
 * @package    local_attendance_ws
 * @author     Emir Kamel
 * @copyright  2023, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG, $ADMIN;

if ($hassiteconfig) {
    $settingscat = new admin_category('attendancewstasks', get_string('plugintitle', 'local_attendance_ws'));

    $settings = new admin_settingpage(get_string('pluginname', 'local_attendance_ws'), get_string('plugintitle', 'local_attendance_ws'));
    $settings->add(new admin_setting_configcheckbox('local_attendance_ws/enable', get_string('enable', 'local_attendance_ws'), get_string('enabledescription', 'local_attendance_ws'), ''));
    $settings->add(new admin_setting_configtextarea('local_attendance_ws/module_list', get_string('modulelist', 'local_attendance_ws'), get_string('modulelistsettingtext', 'local_attendance_ws'), ''));
    $settings->add(new admin_setting_confightmleditor('local_attendance_ws/activity_intro', get_string('activityintro', 'local_attendance_ws'), get_string('activityintrosettingtext', 'local_attendance_ws'), ''));
    $settings->add(new admin_setting_configtext('local_attendance_ws/salt', get_string('salt', 'local_attendance_ws'), get_string('saltsettingtext', 'local_attendance_ws'), ''));
    $settings->add(new admin_setting_configcheckbox(
        'local_attendance_ws/enableevents',
        get_string('enableevents', 'local_attendance_ws'),
        get_string('enableeventsdescription', 'local_attendance_ws'), true));
    $settings->add(new admin_setting_heading('local_attendance_ws/csv',
        get_string('csvtitle', 'local_attendance_ws'),
        html_writer::link(new moodle_url('/local/attendance_ws/csv.php'),
            get_string('csvuploadlink', 'local_attendance_ws'))));

    $settingscat->add('attendancewstasks', $settings);

    $settingscat->add('attendancewstasks', new admin_externalpage(
        'attendancewsrunchanges',
        get_string('runchangestitle', 'local_attendance_ws'),
        new moodle_url('/local/attendance_ws/run_changes.php'),
        'local/attendance_ws:managesessions'));

    $settingscat->add('attendancewstasks', new admin_externalpage(
        'attendancewstaskssync',
        'Sync metalinked sessions',
        "$CFG->wwwroot/local/attendance_ws/tools/syncmetalinkedsessions.php"));

    $ADMIN->add('localplugins', $settingscat);

}
