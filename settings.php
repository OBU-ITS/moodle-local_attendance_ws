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

if ($hassiteconfig) {
    $settings = new admin_settingpage(get_string('pluginname', 'local_attendance_ws'), get_string('plugintitle', 'local_attendance_ws'));
    $ADMIN->add('localplugins', $settings);
    $settings->add(new admin_setting_configcheckbox('local_attendance_ws/enable', get_string('enable', 'local_attendance_ws'), get_string('enabledescription', 'local_attendance_ws'), ''));
    $settings->add(new admin_setting_configtextarea('local_attendance_ws/module_list', get_string('modulelist', 'local_attendance_ws'), get_string('modulelistsettingtext', 'local_attendance_ws'), ''));
}