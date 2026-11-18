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

$string['csvtitle'] = 'Upload reservation CSV';
$string['csvuploadlink'] = 'Upload a reservation CSV';
$string['csvfile'] = 'CSV file';
$string['csvprocess'] = 'Upload reservations';
$string['csvinstructions'] = 'Upload a UTF-8, comma-separated CSV (maximum 20 MB) with a header row. '
    . 'The first eight columns must contain the reservation ID, room, date, time, length, module, module group and module group name, in that order. '
    . 'Columns are read by position; the header row is skipped. Use day/month/four-digit year for dates (for example, 10/12/2026), '
    . '24-hour times (for example, 09:00-11:00) and hours:minutes:seconds for length (for example, 02:00:00). '
    . 'Only the start of the time range is used; the duration comes from the hours, minutes and seconds in the length column. '
    . 'Module codes such as ENGR7006_S1_1_202609 become course ID numbers such as 2026.ENGR7006_S1_1. '
    . 'A module group such as ENGR7006_S1_1_202609_Set2_GS2 supplies the semester value GS2; '
    . 'ENGR7006_S1_1_202609_Whole Cohort supplies WC. The module group name (for example, SetB2) is stored as the group name. '
    . 'Rows without a reservation ID are skipped. Each upload replaces all previously uploaded reservation rows saved for processing; '
    . 'it does not display JSON or apply the attendance changes.';
$string['csvresults'] = 'Upload results';
$string['csvcannotread'] = 'The uploaded CSV could not be read. Please upload it again.';
$string['csvinvalidheader'] = 'The CSV must have a header row with at least eight columns.';
$string['csvinvalidrow'] = 'CSV record {$a} has fewer than eight columns. Please check the file and upload it again.';
$string['csvinvalidencoding'] = 'The CSV contains invalid text encoding. Please save it as UTF-8 and upload it again.';
$string['csvnodata'] = 'No reservation data was found. Include data rows after the header, with a reservation ID in the first column.';

$string['runchangestitle'] = 'Run attendance changes';
$string['runchangescsvwarning'] = 'Before running the changes, upload the CSV if you have not already done so. {$a}';
$string['runchangesbutton'] = 'Run changes';
$string['runchangestaskcreated'] = 'Ad hoc task created. It will run in the background when cron processes the task queue.';
