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
 * Language strings
 *
 * @package    local_attendance_ws
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['privacy:metadata'] = 'The Attendance Web Service plugin does not store any personal data.';

$string['pluginname'] = 'attendance_ws';
$string['plugintitle'] = 'OBU Attendance';
$string['header'] = 'You are using OBU Attendance Plugin version {$a->version}.';
$string['livesettings'] = 'Live Import Settings';
$string['enable'] = 'Is enabled?';
$string['enabledescription'] = 'Toggle to enable the plugin or disable the plugin in Moodle.';
$string['modulelist'] = 'Module List:';
$string['modulelistsettingtext'] = 'Section to provide list of modules to include in OBU Attendance plugin. Leave empty to import all modules';
$string['activityintro'] = 'Default activity description:';
$string['activityintrosettingtext'] = 'The description text when generating a course attendance activity.';
$string['salt'] = 'Salt:';
$string['saltsettingtext'] = 'Salt';
$string['enableevents'] = 'Enable event capture';
$string['enableeventsdescription'] = 'Toggle to enable the capturing of Moodle events to move child sessions onto the parent course when metalinked';

$string['csvtitle'] = 'Convert group CSV to JSON';
$string['csvuploadlink'] = 'Upload a CSV and view JSON';
$string['csvfile'] = 'CSV file';
$string['csvprocess'] = 'Generate JSON';
$string['csvinstructions'] = 'Upload a UTF-8, comma-separated CSV (maximum 5 MB) with a header row. The first four columns must contain the relation, group name, instance name and pipe-separated usernames, in that order. Example: ENGR4009_S1_1_202609 becomes 2026.ENGR4009_S1_1. The generated JSON is displayed below; this does not change course or group memberships.';
$string['csvresults'] = 'Generated JSON';
$string['csvcannotread'] = 'The uploaded CSV could not be read. Please upload it again.';
$string['csvinvalidheader'] = 'The CSV must have a header row with at least four columns.';
$string['csvinvalidrow'] = 'CSV record {$a} has fewer than four columns. Please check the file and upload it again.';
$string['csvinvalidencoding'] = 'The CSV contains invalid text encoding. Please save it as UTF-8 and upload it again.';
$string['csvnodata'] = 'No course data was found. Include data rows after the header, with a relation in the first column.';
