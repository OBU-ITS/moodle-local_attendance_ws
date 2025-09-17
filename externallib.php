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

// namespace local_attendance_ws;

//use external_api;
//use external_function_parameters;
//use external_single_structure;
//use external_multiple_structure;
//use external_value;
//use context_system;
//use context_module;

use local_attendance_ws\service\session_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/mod/attendance/locallib.php");

class local_attendance_ws_external extends external_api {
    public static function add_session_parameters() {
        return new external_function_parameters([
            'idnumber' => new external_value(PARAM_TEXT),
            'slotid' => new external_value(PARAM_TEXT),
            'roomid' => new external_value(PARAM_TEXT),
            'group' => new external_value(PARAM_TEXT),
            'start' => new external_value(PARAM_INT),
            'duration' => new external_value(PARAM_INT),
            'semesterName' => new external_value(PARAM_TEXT),
        ]);
    }

    public static function add_session_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_INT)
        ]);
    }

    public static function add_session($idnumber, $slotid, $roomid, $group, $start, $duration, $semesterName) {
        self::validate_context(context_system::instance());
        $params = self::validate_parameters(self::add_session_parameters(), compact(
            'idnumber', 'slotid', 'roomid', 'group', 'start', 'duration', 'semesterName'
        ));

        if (empty($params['idnumber']) || empty($params['slotid']) || empty($params['roomid'])) {
            return ['result' => -1];
        }

        try {
            $session = session_manager::create_session([
                'courseIdNumber' => $params['idnumber'],
                'slotid' => $params['slotid'],
                'roomid' => $params['roomid'],
                'group' => $params['group'],
                'start' => $params['start'],
                'duration' => $params['duration'],
                'semesterName' => $params['semesterName']
            ]);

            return ['result' => $session->id];

        } catch (\moodle_exception $e) {
            return ['result' => -2];
        }
    }

    public static function update_session_parameters() {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT),
            'start' => new external_value(PARAM_INT),
            'duration' => new external_value(PARAM_INT),
            'roomid' => new external_value(PARAM_TEXT)
        ]);
    }

    public static function update_session_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_INT)
        ]);
    }

    public static function update_session($sessionid, $start, $duration, $roomid) {
        self::validate_context(context_system::instance());
        $params = self::validate_parameters(self::update_session_parameters(), compact('sessionid', 'start', 'duration', 'roomid'));

        if ($params['sessionid'] < 1) {
            return ['result' => -1];
        }

        try {
            $updated = session_manager::update_session($params['sessionid'], $params);
            return ['result' => $updated->id];

        } catch (\moodle_exception $e) {
            return ['result' => 0];
        }
    }

    public static function delete_session_parameters() {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT)
        ]);
    }

    public static function delete_session_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_INT)
        ]);
    }

    public static function delete_session($sessionid) {
        self::validate_context(context_system::instance());
        $params = self::validate_parameters(self::delete_session_parameters(), ['sessionid' => $sessionid]);

        if ($params['sessionid'] < 1) {
            return ['result' => -1];
        }

        try {
            session_manager::delete_session($params['sessionid']);
            return ['result' => $params['sessionid']];
        } catch (\moodle_exception $e) {
            return ['result' => 0];
        }
    }

    public static function add_sessions_parameters() {
        return new \external_function_parameters([
            'courses' => new \external_multiple_structure(
                new \external_single_structure([
                    'courseIdNumber' => new \external_value(PARAM_TEXT),
                    'sessions' => new \external_multiple_structure(
                        new \external_single_structure([
                            'slotid' => new \external_value(PARAM_TEXT),
                            'roomid' => new \external_value(PARAM_TEXT),
                            'group' => new \external_value(PARAM_TEXT),
                            'start' => new \external_value(PARAM_INT),
                            'duration' => new \external_value(PARAM_INT),
                            'semesterName' => new \external_value(PARAM_TEXT)
                        ])
                    )
                ])
            )
        ]);
    }

    public static function add_sessions_returns() {
        return new \external_single_structure([
            'messages' => new \external_multiple_structure(new \external_value(PARAM_TEXT)),
            'results' => new \external_multiple_structure(
                new \external_single_structure([
                    'courseIdNumber' => new \external_value(PARAM_TEXT),
                    'slotid' => new \external_value(PARAM_TEXT),
                    'roomid' => new \external_value(PARAM_TEXT),
                    'group' => new \external_value(PARAM_TEXT),
                    'start' => new \external_value(PARAM_INT),
                    'duration' => new \external_value(PARAM_INT),
                    'status' => new \external_value(PARAM_BOOL),
                    'message' => new \external_value(PARAM_TEXT, 'Optional message', VALUE_OPTIONAL),
                    'sessionid' => new \external_value(PARAM_INT, 'Optional session ID', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    public static function add_sessions($params) {
        self::validate_context(\context_system::instance());
        $params = self::validate_parameters(self::add_sessions_parameters(), $params);

        $results = [];
        $messages = [];

        foreach ($params['courses'] as $courseData) {
            $idnumber = $courseData['courseIdNumber'];

            foreach ($courseData['sessions'] as $sdata) {
                $data = [
                    'courseIdNumber' => $idnumber,
                    'slotid' => $sdata['slotId'],
                    'roomid' => $sdata['roomId'],
                    'group' => $sdata['group'],
                    'start' => $sdata['start'],
                    'duration' => $sdata['duration'],
                    'semesterName' => $sdata['semesterName'],
                ];

                try {
                    $session = \local_attendance_ws\service\session_manager::create_session($data);
                    $results[] = array_merge($sdata, [
                        'courseIdNumber' => $idnumber,
                        'status' => true,
                        'sessionid' => $session->id
                    ]);
                } catch (\Exception $e) {
                    $results[] = array_merge($sdata, [
                        'courseIdNumber' => $idnumber,
                        'status' => false,
                        'message' => $e->getMessage()
                    ]);
                }
            }
        }

        return ['messages' => $messages, 'results' => $results];
    }

    public static function update_sessions_parameters() {
        return new \external_function_parameters([
            'sessions' => new \external_multiple_structure(
                new \external_single_structure([
                    'sessionid' => new \external_value(PARAM_INT),
                    'start' => new \external_value(PARAM_INT),
                    'duration' => new \external_value(PARAM_INT),
                    'roomid' => new \external_value(PARAM_TEXT)
                ])
            )
        ]);
    }

    public static function update_sessions_returns() {
        return new \external_single_structure([
            'messages' => new \external_multiple_structure(new \external_value(PARAM_TEXT)),
            'results' => new \external_multiple_structure(
                new \external_single_structure([
                    'sessionid' => new \external_value(PARAM_INT),
                    'status' => new \external_value(PARAM_BOOL),
                    'message' => new \external_value(PARAM_TEXT, 'Optional message', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    public static function update_sessions($params) {
        self::validate_context(\context_system::instance());
        $params = self::validate_parameters(self::update_sessions_parameters(), $params);

        $results = [];
        $messages = [];

        foreach ($params['sessions'] as $sdata) {
            $sessionid = $sdata['sessionid'];
            $data = [
                'start' => $sdata['start'],
                'duration' => $sdata['duration'],
                'roomid' => $sdata['roomid'],
            ];

            try {
                \local_attendance_ws\service\session_manager::update_session($sessionid, $data);
                $results[] = [
                    'sessionid' => $sessionid,
                    'status' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'sessionid' => $sessionid,
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return ['messages' => $messages, 'results' => $results];
    }

    public static function delete_sessions_parameters() {
        return new \external_function_parameters([
            'sessions' => new \external_multiple_structure(new \external_value(PARAM_INT))
        ]);
    }

    public static function delete_sessions_returns() {
        return new \external_single_structure([
            'messages' => new \external_multiple_structure(new \external_value(PARAM_TEXT)),
            'results' => new \external_multiple_structure(
                new \external_single_structure([
                    'sessionid' => new \external_value(PARAM_INT),
                    'status' => new \external_value(PARAM_BOOL),
                    'message' => new \external_value(PARAM_TEXT, 'Optional message', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    public static function delete_sessions($params) {
        self::validate_context(\context_system::instance());
        $params = self::validate_parameters(self::delete_sessions_parameters(), $params);

        $results = [];
        $messages = [];

        foreach ($params['sessions'] as $sessionid) {
            try {
                \local_attendance_ws\service\session_manager::delete_session($sessionid);
                $results[] = [
                    'sessionid' => $sessionid,
                    'status' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'sessionid' => $sessionid,
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return ['messages' => $messages, 'results' => $results];
    }

    public static function upsert_sessions_parameters() {
        return self::add_sessions_parameters();
    }

    public static function upsert_sessions_returns() {
        return self::add_sessions_returns();
    }

    public static function upsert_sessions($params) {
        self::validate_context(\context_system::instance());
        $params = self::validate_parameters(self::upsert_sessions_parameters(), $params);

        $results = [];
        $messages = [];

        foreach ($params['courses'] as $courseData) {
            $idnumber = $courseData['courseIdNumber'];

            foreach ($courseData['sessions'] as $sdata) {
                $data = [
                    'courseIdNumber' => $idnumber,
                    'slotid' => $sdata['slotId'],
                    'roomid' => $sdata['roomId'],
                    'group' => $sdata['group'],
                    'start' => $sdata['start'],
                    'duration' => $sdata['duration'],
                    'semesterName' => $sdata['semesterName'],
                ];

                try {
                    $existingid = local_attendance_ws_get_session_id($data['slotid'], $data['courseIdNumber']);

                    if ($existingid) {
                        $session = \local_attendance_ws\service\session_manager::update_session($existingid, [
                            'start' => $data['start'],
                            'duration' => $data['duration'],
                            'roomid' => $data['roomid']
                        ]);
                        $message = 'Updated existing session.';
                    } else {
                        $session = \local_attendance_ws\service\session_manager::create_session($data);
                        $message = 'Created new session.';
                    }

                    $results[] = array_merge($sdata, [
                        'courseIdNumber' => $idnumber,
                        'status' => true,
                        'sessionid' => $session->id,
                        'message' => $message
                    ]);
                } catch (\Exception $e) {
                    $results[] = array_merge($sdata, [
                        'courseIdNumber' => $idnumber,
                        'status' => false,
                        'message' => $e->getMessage()
                    ]);
                }
            }
        }

        return ['messages' => $messages, 'results' => $results];
    }

    public static function get_settings_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_settings_returns() {
        return new external_single_structure([
            'enabled' => new external_value(PARAM_BOOL, 'Enabled'),
            'modulelist' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Module List'))
        ]);
    }

    public static function get_settings() {
        $enabled = get_config('local_attendance_ws', 'enable');
        $modulelist = get_config('local_attendance_ws', 'module_list');
        $modulesarray = array_filter(explode(",", str_replace(" ", "", $modulelist)));

        return ['enabled' => (bool) $enabled, 'modulelist' => $modulesarray];
    }
}
