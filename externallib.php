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
    // DEPRECATED - remove after implementation of Upsert
    public static function add_session_parameters() {
		return new external_function_parameters(
			array(
				'idnumber' => new external_value(PARAM_TEXT, 'Course ID number'),
                'slotid' => new external_value(PARAM_TEXT, 'Slot ID number'),
                'roomid' => new external_value(PARAM_TEXT, 'Room ID number'),
				'group' => new external_value(PARAM_TEXT, 'Group'),
                'start' => new external_value(PARAM_INT, 'Session start time'),
                'duration' => new external_value(PARAM_INT, 'Session duration'),
                'semesterName' => new external_value(PARAM_TEXT, 'Semester name')
			)
		);
	}
    // DEPRECATED - remove after implementation of Upsert
	public static function add_session_returns() {
		return new external_single_structure(
			array(
				'result' => new external_value(PARAM_INT, 'Result')
			)
		);
	}
    // DEPRECATED - remove after implementation of Upsert
	public static function add_session($idnumber, $slotid, $roomid, $group, $start, $duration, $semesterName) {
		global $DB;

		self::validate_context(context_system::instance());
		$params = self::validate_parameters(
			self::add_session_parameters(), array(
				'idnumber' => $idnumber,
                'slotid' => $slotid,
                'roomid' => $roomid,
				'group' => $group,
				'start' => $start,
                'duration' => $duration,
                'semesterName' => $semesterName
			)
		);


		if (strlen($params['idnumber']) < 1 || strlen($params['slotid']) < 1 || strlen($params['roomid']) < 1) {
			return array('result' => -1);
		}

		return local_attendance_ws_create_session($params['idnumber'], $params['slotid'], $params['roomid'], $params['group'], $params['start'], $params['duration'], $params['semesterName']);
	}

    // DEPRECATED - remove after implementation of Upsert
	public static function update_session_parameters() {
		return new external_function_parameters(
			array(
				'sessionid' => new external_value(PARAM_INT, 'Session ID'),
				'start' => new external_value(PARAM_INT, 'Session start time'),
                'duration' => new external_value(PARAM_INT, 'Session duration'),
                'roomid' => new external_value(PARAM_TEXT, 'Room Ids')
			)
		);
	}
    // DEPRECATED - remove after implementation of Upsert
	public static function update_session_returns() {
		return new external_single_structure(
			array(
				'result' => new external_value(PARAM_INT, 'Result')
			)
		);
	}
    // DEPRECATED - remove after implementation of Upsert
	public static function update_session($sessionid, $start, $duration, $roomid) {
		global $DB;

		self::validate_context(context_system::instance());
		$params = self::validate_parameters(
			self::update_session_parameters(), array(
				'sessionid' => $sessionid,
				'start' => $start,
                'duration' => $duration,
                'roomid' => $roomid
			)
		);

		if ($params['sessionid'] < 1) {
			return array('result' => -1);
		}

		return local_attendance_ws_update_session($params['sessionid'], $params['start'], $params['duration'], $params['roomid']);
	}

    // DEPRECATED - remove after full implementation of Upsert
	public static function delete_session_parameters() {
		return new external_function_parameters(
			array(
				'sessionid' => new external_value(PARAM_INT, 'Session ID')
			)
		);
	}

	public static function delete_session_returns() {
		return new external_single_structure(
			array(
				'result' => new external_value(PARAM_INT, 'Result')
			)
		);
	}

	public static function delete_session($sessionid) {
		global $DB;

		self::validate_context(context_system::instance());
		$params = self::validate_parameters(
			self::delete_session_parameters(), array(
				'sessionid' => $sessionid
			)
		);

		if (strlen($params['sessionid']) < 1) {
			return array('result' => -1);
		}

		return local_attendance_ws_delete_session($params['sessionid']);
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
        global $DB;

        self::validate_context(context_system::instance());

        $params = self::validate_parameters(
            self::upsert_sessions_parameters(),
            ['reservations' => $reservations]
        );

        $currentTime = time();

        foreach ($params['reservations'] as $reservation) {

            $eventIdNumber = $reservation['eventIdNumber'];
            $roomId = $reservation['roomId'];
            $start = $reservation['start'];
            $duration = $reservation['duration'];

            $payloadjson = json_encode($reservation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $payloadhash = sha1($payloadjson);

            $existing = $DB->get_record('local_obu_att_ws_reservation', [
                'eventidnumber' => $eventIdNumber
            ], '*', IGNORE_MISSING);

            if (!$existing) {
                $record = (object)[
                    'eventidnumber' => $eventIdNumber,
                    'roomid' => $roomId,
                    'start' => $start,
                    'duration' => $duration,
                    'payloadjson' => $payloadjson,
                    'payloadhash' => $payloadhash,
                    'is_delete' => 0,
                    'is_processed' => 0,
                    'timecreated' => $currentTime,
                    'timemodified' => $currentTime,
                ];
                $DB->insert_record('local_obu_att_ws_reservation', $record);

            } else {
                $update = (object)[
                    'id'           => $existing->id,
                    'roomid'        => $roomId,
                    'start'         => $start,
                    'duration'      => $duration,
                    'payloadjson'  => $payloadjson,
                    'payloadhash'  => $payloadhash,
                    'is_delete'    => 0,
                    'timemodified' => $currentTime,
                ];

                // Only queue the scheduled task if changed.
                if ($existing->payloadhash !== $payloadhash) {
                    $update->is_processed = 0;
                }

                $DB->update_record('local_obu_att_ws_reservation', $update);
            }
        }

        return [
            'success' => true
        ];
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
        global $DB;

        self::validate_context(context_system::instance());

        $params = self::validate_parameters(
            self::delete_sessions_parameters(),
            array(
                'eventIdNumbers' => $eventidnumbers
            )
        );

        $currenttime = time();

        foreach ($params['eventIdNumbers'] as $eventidnumber) {
            $payloadjson = json_encode(
                ['eventIdNumber' => $eventidnumber],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $payloadhash = sha1($payloadjson);

            $existing = $DB->get_record('local_obu_att_ws_reservation', [
                'eventidnumber' => $eventidnumber
            ]);

            if (!$existing) {
                $record = (object)[
                    'eventidnumber' => $eventidnumber,
                    'roomid' => '',
                    'start' => 0,
                    'duration' => 0,
                    'payloadjson' => $payloadjson,
                    'payloadhash' => $payloadhash,
                    'is_delete' => 1,
                    'is_processed' => 0,
                    'timecreated' => $currenttime,
                    'timemodified' => $currenttime,
                ];

                $DB->insert_record('local_obu_att_ws_reservation', $record);
            } else {
                $update = new \stdClass();
                $update->id = $existing->id;
                $update->payloadjson = $payloadjson;
                $update->payloadhash = $payloadhash;
                $update->is_delete = 1;
                $update->is_processed = 0;
                $update->timemodified = $currenttime;

                $DB->update_record('local_obu_att_ws_reservation', $update);
            }
        }

        return [
            'success' => true
        ];
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
