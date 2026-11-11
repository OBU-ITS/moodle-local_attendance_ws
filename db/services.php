<?php

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
 * Attendance web service - service functions
 * @package   local_attendance_ws
 * @author    Peter Welham
 * @copyright 2017, Oxford Brookes University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define the web service functions to install.
$functions = array(
	'local_attendance_ws_add_session' => array(
		'classname'   => 'local_attendance_ws_external',
		'methodname'  => 'add_session',
		'classpath'   => 'local/attendance_ws/externallib.php',
		'description' => 'Adds a new attendance session with the given details. Returns a result code.',
		'type'        => 'write',
		'capabilities'=> 'mod/attendance:manageattendances'
	),
    'local_attendance_ws_add_sessions' => array(
        'classname'   => 'local_attendance_ws_external',
        'methodname'  => 'add_sessions',
        'classpath'   => 'local/attendance_ws/externallib.php',
        'description' => 'Adds new attendance sessions with the given details. Returns an array.',
        'type'        => 'write',
        'capabilities'=> 'mod/attendance:manageattendances'
    ),
	'local_attendance_ws_update_session' => array(
		'classname'   => 'local_attendance_ws_external',
		'methodname'  => 'update_session',
		'classpath'   => 'local/attendance_ws/externallib.php',
		'description' => 'Updates an attendance session with the given details. Returns a result code.',
		'type'        => 'write',
		'capabilities'=> 'mod/attendance:manageattendances'
	),
    'local_attendance_ws_upsert_sessions' => array(
        'classname'   => 'local_attendance_ws_external',
        'methodname'  => 'upsert_sessions',
        'classpath'   => 'local/attendance_ws/externallib.php',
        'description' => 'Inserts new or updates existing attendance sessions with the given details.',
        'type'        => 'write',
        'capabilities'=> 'mod/attendance:manageattendances,local/attendance_ws:managesessions'
    ),
	'local_attendance_ws_delete_session' => array(
		'classname'   => 'local_attendance_ws_external',
		'methodname'  => 'delete_session',
		'classpath'   => 'local/attendance_ws/externallib.php',
		'description' => 'Deletes an attendance session with the given ID. Returns a result code.',
		'type'        => 'write',
		'capabilities'=> 'mod/attendance:manageattendances'
	),
    'local_attendance_ws_delete_sessions' => array(
        'classname'   => 'local_attendance_ws_external',
        'methodname'  => 'delete_sessions',
        'classpath'   => 'local/attendance_ws/externallib.php',
        'description' => 'Deletes attendance sessions with the given ID. Returns an array.',
        'type'        => 'write',
        'capabilities'=> 'mod/attendance:manageattendances,local/attendance_ws:managesessions'
    ),
    'local_attendance_ws_get_settings' => array(
        'classname'   => 'local_attendance_ws_external',
        'methodname'  => 'get_settings',
        'classpath'   => 'local/attendance_ws/externallib.php',
        'description' => 'Gets the settings and gives them to the API caller.',
        'type'        => 'read',
        'capabilities'=> 'mod/attendance:manageattendances'
    )
);

// Define the services to install as pre-build services.
$services = array(
	'Attendance web service' => array(
		'shortname' => 'attendance_ws',
		'functions' => array(
			'local_attendance_ws_add_session',
			'local_attendance_ws_update_session',
			'local_attendance_ws_delete_session',
            'local_attendance_ws_upsert_sessions',
            'local_attendance_ws_delete_sessions',
            'local_attendance_ws_get_settings'
		),
		'restrictedusers' => 1,
		'enabled' => 1
	)
);
