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
 * Version info
 *
 * @package    local_attendance_ws
 * @author     Peter Welham
 * @copyright  2017, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_attendance_ws';
$plugin->version = 2026073100;
$plugin->requires = 2012120301;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v1.7.2.0';
$plugin->dependencies = array(
    'mod_attendance' => 2024070301, // OBU Customisation fork
    'local_obu_metalinking' => 2024110101,
    'local_obu_group_manager' => 2024100301,
    'local_obu_attendance_events' => 2024100901,
    'local_obu_metalinking_events' => 2024100901
);

