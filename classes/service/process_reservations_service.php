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
     * Retrieves all unprocessed reservation API calls from the database.
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

    /**
     * Main function for processing reservation API calls.
     */
    public function process_reservations(\progress_trace $trace, $unprocessedReservations) {
        global $DB;

        $eventIdNumbers = $this->buildEventIdNumbers($unprocessedReservations);
        $oldSessionRows = $this->getOldSessionRowsByEventIdNumbers($eventIdNumbers);

        $attendanceSessionIds = $this->buildAttendanceSessionIds($oldSessionRows);
        $attendanceSessions = $this->getAttendanceSessions($attendanceSessionIds);

        foreach ($unprocessedReservations as $unprocessedReservation) {
            $payload = json_decode($unprocessedReservation->payloadjson, true);

            $newReservations = $this->buildNewReservations($unprocessedReservation, $payload);

            $oldSessionRowsForUnprocessedReservation = $oldSessionRows[$unprocessedReservation->eventidnumber] ?? [];
            $oldReservations = $this->buildOldReservations($attendanceSessions, $oldSessionRowsForUnprocessedReservation);

            $keyDiffs = $this->getReservationKeyDiffs($oldReservations, $newReservations);

            $this->processDeletes($keyDiffs['delete'], $oldReservations, $trace);
            $this->processCreates($keyDiffs['create'], $newReservations, $trace);
            $this->processUpdates($keyDiffs['update'], $oldReservations, $newReservations, $trace);

            // check that reservation has not been updated while we were acting on changes, if so leave is_processed alone
            $currentreservation = $DB->get_record('local_obu_att_ws_reservation', [
                'id' => $unprocessedReservation->id
            ], 'id, payloadhash', IGNORE_MISSING);

            if ($currentreservation->payloadhash !== $unprocessedReservation->payloadhash) {
                $trace->output("Reservation {$unprocessedReservation->eventidnumber} changed during processing, leaving as unprocessed.");
                continue;
            }

            $updatereservation = new \stdClass();
            $updatereservation->id = $unprocessedReservation->id;
            $updatereservation->is_processed = 1;
            $updatereservation->timemodified = time();

            $DB->update_record('local_obu_att_ws_reservation', $updatereservation);
        }
    }

    /**
     * Retrieves all eventidnumbers for the passed-in reservations
     *
     * @return array An array of eventidnumbers.
     */
    private function buildEventIdNumbers($unprocessedReservations) : array {
        $eventIdNumbers = [];

        foreach ($unprocessedReservations as $unprocessedReservation) {
            $eventIdNumbers[] = $unprocessedReservation->eventidnumber;
        }

        return array_unique($eventIdNumbers);
    }

    /**
     * Retrieves all sessionIds for the passed-in rows from the lookup table.
     *
     * @return array An array of unique sessionIds.
     */
    private function buildAttendanceSessionIds(array $oldSessionRows) : array {
        $sessionIds = [];

        foreach ($oldSessionRows as $rowsForEvent) {
            foreach ($rowsForEvent as $row) {
                if (!empty($row->session_id)) {
                    $sessionIds[] = (int)$row->session_id;
                }
            }
        }

        return array_values(array_unique($sessionIds));
    }

    /**
     * Retrieves all active attendance sessions in Moodle for the passed-in sessionIds.
     *
     * @return array An array of active Moodle attendance sessions.
     */
    private function getAttendanceSessions(array $sessionIds) : array{
        global $DB;

        if (empty($sessionIds)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($sessionIds, SQL_PARAMS_NAMED);

        return $DB->get_records_select(
            'attendance_sessions',
            "id $insql",
            $params
        );
    }

    /**
     * Retrieves all rows from our lookup table for the passed-in eventidnumbers.
     *
     * @return array An array of "sessions" as rows from our lookup table by eventidnumber.
     * There can be multiple rows from the lookup table per eventidnumber, as there can be multiple sessions to each reservation.
     */
    private function getOldSessionRowsByEventIdNumbers(array $eventIdNumbers) {
        global $DB;

        $oldSessionRows = [];

        if (empty($eventIdNumbers)) {
            return $oldSessionRows;
        }

        list($insql, $params) = $DB->get_in_or_equal($eventIdNumbers, SQL_PARAMS_NAMED);

        $rows = $DB->get_records_select(
            'local_obu_att_ws_sessions',
            "eventidnumber $insql",
            $params
        );

        foreach ($rows as $row) {
            $oldSessionRows[$row->eventidnumber][] = $row;
        }

        return $oldSessionRows;
    }

    /**
     * Builds the reservations from the passed in unprocessed reservation and its payload value from the table.
     *
     * @return array An array of reservations.
     */
    private function buildNewReservations($unprocessedReservation, array $payload) : array {
        $newReservations = [];

        foreach ($payload['courses'] as $course) {

            foreach ($course['groups'] as $group) {

                $key = $unprocessedReservation->eventidnumber . '|' . $course['courseIdNumber'] . '|' . $group['name'];
                $newReservations[$key] = [
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

        return $newReservations;
    }

    /**
     * Builds the old reservations from the passed in active Moodle sessions and the rows from the lookup table.
     *
     * @return array An array of old reservations.
     */
    private function buildOldReservations(array $attendanceSessions, array $oldSessionRows) : array {
        $oldReservations = [];

        foreach ($oldSessionRows as $row) {
            $attendancesession = $attendanceSessions[$row->session_id] ?? null;

            $key = $row->eventidnumber . '|' . $row->courseidnumber . '|' . $row->groupname;

            $oldReservations[$key] = [
                'id' => $row->id,
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

        return $oldReservations;
    }

    /**
     * Compares a list of old reservations and new reservations to determine if a create, delete, or update is required
     *
     * @return array An array of creates, updates and deletes and the corresponding reservations for the operation required.
     */
    private function getReservationKeyDiffs(array $oldReservations, array $newReservations): array {
        $oldKeys = array_keys($oldReservations);
        $newKeys = array_keys($newReservations);

        return [
            'delete' => array_diff($oldKeys, $newKeys),
            'create' => array_diff($newKeys, $oldKeys),
            'update' => array_intersect($oldKeys, $newKeys),
        ];
    }

    private function processDeletes($deleteKeys, $oldReservations, $trace): void {
        global $DB;

        foreach ($deleteKeys as $key) {
            $old = $oldReservations[$key];

            $trace->output("Deleting session for key {$key} using Moodle session ID {$old['session_id']}");

            // delete Moodle session using $old->session_id
            $result = local_attendance_ws_delete_session($old['session_id']);

            if (!isset($result['result']) || (int)$result['result'] <= 0) {
                $trace->output("Failed to delete Moodle session {$old['session_id']} for {$key} with result: {$result['result']}");
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
    }

    private function processCreates($createKeys, $newReservations, $trace): void {
        global $DB;

        foreach ($createKeys as $key) {
            $new = $newReservations[$key];

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
                $trace->output("Failed to create Moodle session for {$key} with result: {$result['result']}");
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
    }

    private function processUpdates($updateKeys, $oldReservations, $newReservations, $trace): void {
        global $DB;

        foreach ($updateKeys as $key) {
            $old = $oldReservations[$key];
            $new = $newReservations[$key];

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
                $trace->output("Failed to update Moodle session {$old['session_id']} for {$key} with result: {$result['result']}");
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
    }
}