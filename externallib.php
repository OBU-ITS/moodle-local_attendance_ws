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

/*
 * Attendance web service - external library
 *
 * @package    local_attendance_ws
 * @author     Peter Welham
 * @copyright  2017, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/local/attendance_ws/locallib.php");
require_once($CFG->dirroot . "/mod/attendance/renderhelpers.php");
require_once($CFG->dirroot . "/mod/attendance/classes/structure.php");
require_once($CFG->dirroot . "/mod/attendance/locallib.php");
require_once($CFG->dirroot . "/course/modlib.php");
require_once($CFG->dirroot . "/group/lib.php");
require_once($CFG->dirroot . "/local/obu_metalinking/lib.php");
require_once($CFG->dirroot . "/local/obu_group_manager/lib.php");

class local_attendance_ws_external extends external_api {

    private static function require_manage_sesions_access(): void {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/attendance_ws:managesessions', $context);
    }

    public static function upsert_sessions_parameters() {
        return new external_function_parameters(
            array(
                'reservations' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'eventIdNumber' => new external_value(PARAM_TEXT, 'Event ID number'),
                            'roomId' => new external_value(PARAM_TEXT, 'Room ID(s)'),
                            'start' => new external_value(PARAM_TEXT, 'Session Start'),
                            'duration' => new external_value(PARAM_TEXT, 'Session Duration'),
                            'courses' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'courseIdNumber' => new external_value(PARAM_TEXT, 'Course ID number'),
                                        'groups' => new external_multiple_structure(
                                            new external_single_structure(
                                                array(
                                                    'name' => new external_value(PARAM_TEXT, 'Group Name'),
                                                    'semesterName' => new external_value(PARAM_TEXT, 'Group Semester Name'),
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    public static function upsert_sessions_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success to save session')
            )
        );
    }

    public static function upsert_sessions($reservations) {
        self::validate_context(context_system::instance());

        $params = self::validate_parameters(
            self::upsert_sessions_parameters(),
            ['reservations' => $reservations]
        );

        self::require_manage_sesions_access();

        return local_attendance_ws_upsert_sessions($params['reservations']);
    }

    public static function delete_sessions_parameters() {
        return new external_function_parameters(
            array(
                'eventIdNumbers' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Reservation Event ID Number'),
                    'Array of reservation event ID numbers'
                )
            )
        );
    }

    public static function delete_sessions_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'True if the delete requests were queued successfully')
            )
        );
    }

    public static function delete_sessions($eventidnumbers) {
        self::validate_context(context_system::instance());

        $params = self::validate_parameters(
            self::delete_sessions_parameters(),
            array(
                'eventIdNumbers' => $eventidnumbers
            )
        );

        self::require_manage_sesions_access();

        return local_attendance_ws_delete_sessions($params['eventIdNumbers']);
    }

    // Get settings
    public static function get_settings_parameters() {
        return new external_function_parameters(
            array(
            )
        );
    }

    public static function get_settings_returns() {
        return new external_single_structure(
            array(
                'enabled' => new external_value(PARAM_BOOL, 'Enabled'),
                'modulelist' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Module List')),
            )
        );
    }

    public static function get_settings(){
        $enabled = get_config('local_attendance_ws', 'enable');
        $modulelist = get_config('local_attendance_ws', 'module_list');
        $modulesarray = array_filter(explode(",", str_replace(" ", "", $modulelist)));

        return array('enabled' => $enabled, 'modulelist' => $modulesarray);
    }
}
