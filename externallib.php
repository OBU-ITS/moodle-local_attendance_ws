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

namespace local_attendance_ws;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_system;
use context_module;

use local_attendance_ws\service\session_service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/course/modlib.php");
require_once($CFG->dirroot . "/mod/attendance/locallib.php");
require_once($CFG->dirroot . "/mod/attendance/renderhelpers.php");
require_once($CFG->dirroot . "/mod/attendance/classes/structure.php");

class external extends external_api {
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
        global $DB;

        self::validate_context(context_system::instance());
        $params = self::validate_parameters(self::add_session_parameters(), compact('idnumber', 'slotid', 'roomid', 'group', 'start', 'duration', 'semesterName'));

        if (empty($params['idnumber']) || empty($params['slotid']) || empty($params['roomid'])) {
            return ['result' => -1];
        }

        $course = $DB->get_record('course', ['idnumber' => $params['idnumber']]);
        if (!$course) {
            return ['result' => -2];
        }

        $teachingcourse = local_obu_metalinking_get_teaching_course($course);
        $attendance = local_attendance_ws_find_attendance_activity($teachingcourse);
        if (!$attendance) {
            return ['result' => -3];
        }

        $cm = get_coursemodule_from_instance('attendance', $attendance->id, 0, false);
        if (!$cm) {
            return ['result' => -4];
        }

        $context = context_module::instance($cm->id);
        require_capability('mod/attendance:manageattendances', $context);

        $pluginconfig = get_config('attendance');
        $sessiondata = [
            'slotid' => $params['slotid'],
            'roomid' => $params['roomid'],
            'start' => $params['start'],
            'duration' => $params['duration'],
            'group' => $params['group'],
            'semesterName' => $params['semesterName']
        ];

        $session = session_service::build_session_object($sessiondata, $course, $attendance, $pluginconfig);
        $session->id = $DB->insert_record('attendance_sessions', $session);
        attendance_create_calendar_event($session);

        $event = \mod_attendance\event\session_added::create([
            'objectid' => $attendance->id,
            'context' => $context,
            'other' => ['info' => construct_session_full_date_time($session->sessdate, $session->duration)]
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('attendance_sessions', $session);
        $event->trigger();

        mod_attendance_notifyqueue::notify_success(get_string('sessiongenerated', 'attendance'));

        return ['result' => $session->id];
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
        global $DB;

        self::validate_context(context_system::instance());
        $params = self::validate_parameters(self::update_session_parameters(), compact('sessionid', 'start', 'duration', 'roomid'));

        if ($params['sessionid'] < 1) {
            return ['result' => -1];
        }

        $session = $DB->get_record('attendance_sessions', ['id' => $params['sessionid']]);
        if (!$session) {
            return ['result' => 0];
        }

        $cm = get_coursemodule_from_instance('attendance', $session->attendanceid, 0, false);
        if (!$cm) {
            return ['result' => -2];
        }

        $context = context_module::instance($cm->id);
        require_capability('mod/attendance:manageattendances', $context);

        $session = session_service::update_session_properties($session, $params['start'], $params['duration'], $params['roomid']);
        $DB->update_record('attendance_sessions', $session);

        $event = \mod_attendance\event\session_updated::create([
            'objectid' => $session->attendanceid,
            'context' => $context,
            'other' => [
                'info' => construct_session_full_date_time($session->sessdate, $session->duration),
                'sessionid' => $session->id,
                'action' => \mod_attendance_sessions_page_params::ACTION_UPDATE
            ]
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('attendance_sessions', $session);
        $event->trigger();

        return ['result' => $session->id];
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
        global $DB;

        self::validate_context(context_system::instance());
        $params = self::validate_parameters(self::delete_session_parameters(), ['sessionid' => $sessionid]);

        if ($params['sessionid'] < 1) {
            return ['result' => -1];
        }

        $session = $DB->get_record('attendance_sessions', ['id' => $params['sessionid']]);
        if (!$session) {
            return ['result' => 0];
        }

        $cm = get_coursemodule_from_instance('attendance', $session->attendanceid, 0, false);
        if (!$cm) {
            return ['result' => -2];
        }

        $context = context_module::instance($cm->id);
        require_capability('mod/attendance:manageattendances', $context);

        if ($session->caleventid) {
            attendance_delete_calendar_events([$params['sessionid']]);
        }

        $DB->delete_records('attendance_log', ['sessionid' => $params['sessionid']]);
        $DB->delete_records('attendance_sessions', ['id' => $params['sessionid']]);

        $event = \mod_attendance\event\session_deleted::create([
            'objectid' => $session->attendanceid,
            'context' => $context,
            'other' => ['info' => $params['sessionid']]
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        $event->trigger();

        return ['result' => $params['sessionid']];
    }

    public static function add_sessions_parameters() {
        return new external_function_parameters([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'courseIdNumber' => new external_value(PARAM_TEXT),
                    'sessions' => new external_multiple_structure(
                        new external_single_structure([
                            'slotId' => new external_value(PARAM_TEXT),
                            'roomId' => new external_value(PARAM_TEXT),
                            'group' => new external_value(PARAM_TEXT),
                            'start' => new external_value(PARAM_INT),
                            'duration' => new external_value(PARAM_INT),
                            'semesterName' => new external_value(PARAM_TEXT)
                        ])
                    )
                ])
            )
        ]);
    }

    public static function add_sessions_returns() {
        return new external_single_structure([
            'messages' => new external_multiple_structure(new external_value(PARAM_TEXT)),
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'courseIdNumber' => new external_value(PARAM_TEXT),
                    'slotId' => new external_value(PARAM_TEXT),
                    'roomId' => new external_value(PARAM_TEXT),
                    'group' => new external_value(PARAM_TEXT),
                    'start' => new external_value(PARAM_INT),
                    'duration' => new external_value(PARAM_INT),
                    'status' => new external_value(PARAM_BOOL),
                    'message' => new external_value(PARAM_TEXT, 'Optional message', VALUE_OPTIONAL),
                    'sessionId' => new external_value(PARAM_INT, 'Optional session ID', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    public static function add_sessions($params) {
        global $DB;

        $params = self::validate_parameters(self::add_sessions_parameters(), $params);
        $results = [];
        $messages = [];

        foreach ($params['courses'] as $courseData) {
            $idnumber = $courseData['courseIdNumber'];
            $course = $DB->get_record('course', ['idnumber' => $idnumber]);

            if (!$course) {
                $messages[] = "Course '{$idnumber}' not found.";
                continue;
            }

            $teachingcourse = local_obu_metalinking_get_teaching_course($course);
            $attendance = local_attendance_ws_find_attendance_activity($teachingcourse);
            if (!$attendance) {
                $messages[] = "No attendance activity for '{$idnumber}'.";
                continue;
            }

            $cm = get_coursemodule_from_instance('attendance', $attendance->id);
            if (!$cm) {
                $messages[] = "No course module for '{$idnumber}'.";
                continue;
            }

            $context = context_module::instance($cm->id);
            require_capability('mod/attendance:manageattendances', $context);
            $pluginconfig = get_config('attendance');

            foreach ($courseData['sessions'] as $sdata) {
                $result = [
                    'courseIdNumber' => $idnumber,
                    'slotId' => $sdata['slotId'],
                    'roomId' => $sdata['roomId'],
                    'group' => $sdata['group'],
                    'start' => $sdata['start'],
                    'duration' => $sdata['duration'],
                    'status' => false
                ];

                if (empty($sdata['slotId']) || empty($sdata['roomId'])) {
                    $result['message'] = "Invalid slot or room ID.";
                    $results[] = $result;
                    continue;
                }

                try {
                    $session = session_service::build_session_object($sdata, $course, $attendance, $pluginconfig);
                    $session->id = $DB->insert_record('attendance_sessions', $session);
                    attendance_create_calendar_event($session);

                    $event = \mod_attendance\event\session_added::create([
                        'objectid' => $attendance->id,
                        'context' => $context,
                        'other' => ['info' => construct_session_full_date_time($session->sessdate, $session->duration)]
                    ]);
                    $event->add_record_snapshot('course_modules', $cm);
                    $event->add_record_snapshot('attendance_sessions', $session);
                    $event->trigger();

                    $result['status'] = true;
                    $result['sessionId'] = $session->id;

                } catch (\Exception $e) {
                    $result['message'] = "Error: " . $e->getMessage();
                }

                $results[] = $result;
            }
        }

        return ['messages' => $messages, 'results' => $results];
    }

    public static function update_sessions_parameters() {
        return new external_function_parameters([
            'sessions' => new external_multiple_structure(
                new external_single_structure([
                    'sessionid' => new external_value(PARAM_INT),
                    'start' => new external_value(PARAM_INT),
                    'duration' => new external_value(PARAM_INT),
                    'roomid' => new external_value(PARAM_TEXT)
                ])
            )
        ]);
    }

    public static function update_sessions_returns() {
        return new external_single_structure([
            'messages' => new external_multiple_structure(new external_value(PARAM_TEXT)),
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'sessionId' => new external_value(PARAM_INT),
                    'status' => new external_value(PARAM_BOOL),
                    'message' => new external_value(PARAM_TEXT, 'Optional message', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    public static function update_sessions($params) {
        global $DB;

        $params = self::validate_parameters(self::update_sessions_parameters(), $params);
        $results = [];
        $messages = [];

        foreach ($params['sessions'] as $data) {
            $result = [
                'sessionId' => $data['sessionid'],
                'status' => false
            ];

            $session = $DB->get_record('attendance_sessions', ['id' => $data['sessionid']]);
            if (!$session) {
                $result['message'] = "Session not found.";
                $results[] = $result;
                continue;
            }

            $cm = get_coursemodule_from_instance('attendance', $session->attendanceid);
            if (!$cm) {
                $result['message'] = "Course module missing.";
                $results[] = $result;
                continue;
            }

            $context = context_module::instance($cm->id);
            require_capability('mod/attendance:manageattendances', $context);

            try {
                $session = session_service::update_session_properties($session, $data['start'], $data['duration'], $data['roomid']);
                $DB->update_record('attendance_sessions', $session);

                $event = \mod_attendance\event\session_updated::create([
                    'objectid' => $session->attendanceid,
                    'context' => $context,
                    'other' => [
                        'info' => construct_session_full_date_time($session->sessdate, $session->duration),
                        'sessionid' => $session->id,
                        'action' => \mod_attendance_sessions_page_params::ACTION_UPDATE
                    ]
                ]);
                $event->add_record_snapshot('course_modules', $cm);
                $event->add_record_snapshot('attendance_sessions', $session);
                $event->trigger();

                $result['status'] = true;
            } catch (\Exception $e) {
                $result['message'] = "Update error: " . $e->getMessage();
            }

            $results[] = $result;
        }

        return ['messages' => $messages, 'results' => $results];
    }
    
    public static function delete_sessions_parameters() {
        return new external_function_parameters([
            'sessions' => new external_multiple_structure(
                new external_value(PARAM_INT)
            )
        ]);
    }

    public static function delete_sessions_returns() {
        return new external_single_structure([
            'messages' => new external_multiple_structure(new external_value(PARAM_TEXT)),
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'sessionId' => new external_value(PARAM_INT),
                    'status' => new external_value(PARAM_BOOL),
                    'message' => new external_value(PARAM_TEXT, 'Optional message', VALUE_OPTIONAL)
                ])
            )
        ]);
    }

    public static function delete_sessions($params) {
        global $DB;

        $params = self::validate_parameters(self::delete_sessions_parameters(), $params);
        $results = [];
        $messages = [];

        foreach ($params['sessions'] as $sessionid) {
            $result = [
                'sessionId' => $sessionid,
                'status' => false
            ];

            $session = $DB->get_record('attendance_sessions', ['id' => $sessionid]);
            if (!$session) {
                $result['message'] = "Session not found.";
                $results[] = $result;
                continue;
            }

            $cm = get_coursemodule_from_instance('attendance', $session->attendanceid);
            if (!$cm) {
                $result['message'] = "Module not found.";
                $results[] = $result;
                continue;
            }

            $context = context_module::instance($cm->id);
            require_capability('mod/attendance:manageattendances', $context);

            try {
                if ($session->caleventid) {
                    attendance_delete_calendar_events([$sessionid]);
                }
                $DB->delete_records('attendance_log', ['sessionid' => $sessionid]);
                $DB->delete_records('attendance_sessions', ['id' => $sessionid]);

                $event = \mod_attendance\event\session_deleted::create([
                    'objectid' => $session->attendanceid,
                    'context' => $context,
                    'other' => ['info' => $sessionid]
                ]);
                $event->add_record_snapshot('course_modules', $cm);
                $event->trigger();

                $result['status'] = true;
            } catch (\Exception $e) {
                $result['message'] = "Delete failed: " . $e->getMessage();
            }

            $results[] = $result;
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
        global $DB;

        $params = self::validate_parameters(self::upsert_sessions_parameters(), $params);
        $results = [];
        $messages = [];

        foreach ($params['courses'] as $courseData) {
            $idnumber = $courseData['courseIdNumber'];
            $course = $DB->get_record('course', ['idnumber' => $idnumber]);

            if (!$course) {
                $messages[] = "Course '{$idnumber}' not found.";
                continue;
            }

            $teachingcourse = local_obu_metalinking_get_teaching_course($course);
            $attendance = local_attendance_ws_find_attendance_activity($teachingcourse);
            if (!$attendance) {
                $messages[] = "No attendance activity for '{$idnumber}'.";
                continue;
            }

            $cm = get_coursemodule_from_instance('attendance', $attendance->id);
            if (!$cm) {
                $messages[] = "No course module for '{$idnumber}'.";
                continue;
            }

            $context = context_module::instance($cm->id);
            require_capability('mod/attendance:manageattendances', $context);
            $pluginconfig = get_config('attendance');

            foreach ($courseData['sessions'] as $sdata) {
                $result = [
                    'courseIdNumber' => $idnumber,
                    'slotId' => $sdata['slotId'],
                    'roomId' => $sdata['roomId'],
                    'group' => $sdata['group'],
                    'start' => $sdata['start'],
                    'duration' => $sdata['duration'],
                    'status' => false
                ];

                $existingid = local_attendance_ws_get_session_id($sdata['slotId'], $idnumber);

                try {
                    if ($existingid > 0) {
                        $session = $DB->get_record('attendance_sessions', ['id' => $existingid]);
                        if (!$session) {
                            throw new moodle_exception("Session ID $existingid not found.");
                        }

                        $session = \local_attendance_ws\service\session_service::update_session_properties(
                            $session,
                            $sdata['start'],
                            $sdata['duration'],
                            $sdata['roomId']
                        );

                        $DB->update_record('attendance_sessions', $session);

                        $event = \mod_attendance\event\session_updated::create([
                            'objectid' => $session->attendanceid,
                            'context' => $context,
                            'other' => [
                                'info' => construct_session_full_date_time($session->sessdate, $session->duration),
                                'sessionid' => $session->id,
                                'action' => \mod_attendance_sessions_page_params::ACTION_UPDATE
                            ]
                        ]);
                        $event->add_record_snapshot('course_modules', $cm);
                        $event->add_record_snapshot('attendance_sessions', $session);
                        $event->trigger();

                        $result['sessionId'] = $session->id;
                        $result['status'] = true;
                        $result['message'] = "Updated existing session.";

                    } else {
                        $session = \local_attendance_ws\service\session_service::build_session_object($sdata, $course, $attendance, $pluginconfig);
                        $session->id = $DB->insert_record('attendance_sessions', $session);
                        attendance_create_calendar_event($session);

                        $event = \mod_attendance\event\session_added::create([
                            'objectid' => $attendance->id,
                            'context' => $context,
                            'other' => ['info' => construct_session_full_date_time($session->sessdate, $session->duration)]
                        ]);
                        $event->add_record_snapshot('course_modules', $cm);
                        $event->add_record_snapshot('attendance_sessions', $session);
                        $event->trigger();

                        $result['sessionId'] = $session->id;
                        $result['status'] = true;
                        $result['message'] = "Added new session.";
                    }

                } catch (\Exception $e) {
                    $result['message'] = "Error during upsert: " . $e->getMessage();
                }

                $results[] = $result;
            }
        }

        return ['messages' => $messages, 'results' => $results];
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