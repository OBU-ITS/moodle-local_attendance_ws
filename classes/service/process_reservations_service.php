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
                    $key = $unprocessedReservation->eventidnumber . '|' . $course['courseIdNumber'] . '|' . $group['name'];
                    $newitems[$key] = [
                        'eventidnumber' => $unprocessedReservation->eventidnumber,
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
                $key = $row->eventidnumber . '|' . $row->courseidnumber . '|' . $row->groupname;
                $olditems[$key] = $row;
            }

            $deletekeys = array_diff(array_keys($olditems), array_keys($newitems));
            $createkeys = array_diff(array_keys($newitems), array_keys($olditems));
            $commonkeys = array_intersect(array_keys($olditems), array_keys($newitems));

            foreach ($deletekeys as $key) {
                $old = $olditems[$key];
                // delete Moodle session using $old->session_id
                // delete lookup row
            }

            foreach ($createkeys as $key) {
                $new = $newitems[$key];
                // create Moodle session
                // insert lookup row with returned session_id
            }

            foreach ($commonkeys as $key) {
                $old = $olditems[$key];
                $new = $newitems[$key];

                if (($old->start == $new['start']) && ($old->duration == $new['duration']) && ($old->roomid == $new['roomId'])) {
                    continue;
                }

                // update Moodle session using $old->session_id
                // update lookup row
            }

            // mark reservation processed
        }
    }

}