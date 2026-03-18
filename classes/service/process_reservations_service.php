<?php
namespace local_attendance_ws\service;

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
 * @package    local_attendance_ws
 * @author     Emir Kamel
 * @copyright  2026, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/attendance_ws/locallib.php');

class process_reservations_service {

    private static ?process_reservations_service $instance = null;
    public static function getInstance() : process_reservations_service {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Retrieves all unprocessed reservations from the database.
     *
     * @return array An array of unprocessed reservation records.
     */
    public function get_unprocessed_reservations() {
        global $DB;

        $sql = "SELECT *
                FROM {local_obu_att_ws_reservation} 
                WHERE is_processed = 0 
                ORDER BY id ASC";

        return $DB->get_records_sql($sql);
    }

    public function process_reservations(\progress_trace $trace, $unprocessedReservations) {
        global $DB;
        foreach ($unprocessedReservations as $unprocessedReservation) {
            $payload = json_decode($unprocessedReservation->payloadjson, true);

            $newitems = [];
            foreach ($payload['courses'] as $course) {
                foreach ($course['groups'] as $group) {
                    $key = $unprocessedReservation->eventIdNumber . '|' . $course['courseIdNumber'] . '|' . $group['name'];
                    $newitems[$key] = [
                        'eventidnumber' => $unprocessedReservation->eventidnumber,
                        'start' => $unprocessedReservation->start,
                        'duration' => $unprocessedReservation->duration,
                        'roomid' => $unprocessedReservation->roomid,
                        'courseidnumber' => $course['courseIdNumber'],
                        'groupname' => $group['name'],
                        'semestername' => $group['semesterName'],
                    ];
                }
            }

            $oldsessionrows = $DB->get_records('local_obu_att_ws_sessions', [
                'eventidnumber' => $unprocessedReservation->eventidnumber
            ]);

            $olditems = [];
            foreach ($oldsessionrows as $row) {
                $attendancesession = $DB->get_record('attendance_sessions', [
                    'id' => $row->session_id
                ], '*', IGNORE_MISSING);

                $key = $row->eventidnumber . '|' . $row->courseidnumber . '|' . $row->groupname;

                $olditems[$key] = [
                    'eventidnumber' => $row->eventidnumber,
                    'start' => $attendancesession ? $attendancesession->sessdate : null,
                    'duration' => $attendancesession ? $attendancesession->duration : null,
                    'roomid' => $attendancesession ? $attendancesession->roomid : null,
                    'courseidnumber' => $row->courseidnumber,
                    'groupname' => $row->groupname,
                    'semestername' => $row->semestername,
                    'session_id' => $row->session_id,
                ];
            }

            $deletekeys = array_diff(array_keys($olditems), array_keys($newitems));
            $createkeys = array_diff(array_keys($newitems), array_keys($olditems));
            $commonkeys = array_intersect(array_keys($olditems), array_keys($newitems));

            foreach ($deletekeys as $key) {
                $old = $olditems[$key];

                $trace->output("Deleting session for key {$key} using Moodle session ID {$old['session_id']}");

                // delete Moodle session using $old->session_id
                $result = local_attendance_ws_delete_session($old['session_id']);

                if (!isset($result['result']) || (int)$result['result'] <= 0) {
                    $trace->output("Failed to delete Moodle session {$old['session_id']} for {$key}");
                    continue;
                }

                // delete lookup row
                $DB->delete_records('local_obu_att_ws_sessions', [
                    'eventidnumber' => $old['eventidnumber'],
                    'courseidnumber' => $old['courseidnumber'],
                    'groupname' => $old['groupname'],
                ]);

                $trace->output("Deleted Moodle session {$old['session_id']} and removed lookup row for {$key}");
            }

            //Create session in Moodle
            foreach ($createkeys as $key) {
                $new = $newitems[$key];

                $trace->output("Creating Moodle session for {$key}");

                $result = local_attendance_ws_create_session(
                    $new['courseidnumber'],
                    (string)$new['eventidnumber'],
                    $new['roomid'],
                    $new['groupname'],
                    (int)$new['start'],
                    (int)$new['duration'],
                    $new['semestername']
                );

                if (!isset($result['result']) || (int)$result['result'] <= 0) {
                    $trace->output("Failed to create Moodle session for {$key}");
                    continue;
                }

                // insert lookup row with returned session_id
                $lookuprecord = new \stdClass();
                $lookuprecord->eventidnumber = $new['eventidnumber'];
                $lookuprecord->courseidnumber = $new['courseidnumber'];
                $lookuprecord->groupname = $new['groupname'];
                $lookuprecord->semestername = $new['semestername'];
                $lookuprecord->session_id = (int)$result['result'];
                $lookuprecord->timecreated = time();
                $lookuprecord->timemodified = time();

                $DB->insert_record('local_obu_att_ws_sessions', $lookuprecord);

                $trace->output("Created Moodle session {$lookuprecord->session_id} for {$key}");
            }

            foreach ($commonkeys as $key) {
                $old = $olditems[$key];
                $new = $newitems[$key];

                if (
                    $old['start'] == $new['start'] &&
                    $old['duration'] == $new['duration'] &&
                    $old['roomid'] == $new['roomid']
                ) {
                    continue;
                }

                // update Moodle session using $old->session_id
                $trace->output("Updating Moodle session {$old['session_id']} for {$key}");

                $result = local_attendance_ws_update_session(
                    (int)$old['session_id'],
                    (int)$new['start'],
                    (int)$new['duration'],
                    $new['roomid']
                );

                if (!isset($result['result']) || (int)$result['result'] <= 0) {
                    $trace->output("Failed to update Moodle session {$old['session_id']} for {$key}");
                    continue;
                }

                // update lookup row
                $updatelookup = new \stdClass();
                $updatelookup->id = $old['id'];
                $updatelookup->semestername = $new['semestername'];
                $updatelookup->timemodified = time();

                $DB->update_record('local_obu_att_ws_sessions', $updatelookup);

                $trace->output("Updated Moodle session {$old['session_id']} for {$key}");
            }

            //TODO:: Get reservation from our table where id is unprocessedReservation->id and compare hashes, if the same then make is processed and time updates, if not then continue.
            // mark reservation processed
            $updatereservation = new \stdClass();
            $updatereservation->id = $unprocessedReservation->id;
            $updatereservation->is_processed = 1;
            $updatereservation->timemodified = time();

            $DB->update_record('local_obu_att_ws_reservation', $updatereservation);
        }
    }

}