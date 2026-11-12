<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is distributed under the GNU GPL v3 or later.

namespace local_attendance_ws;

use local_attendance_ws\service\process_reservations_service;

defined('MOODLE_INTERNAL') || die();

/**
 * @package local_attendance_ws
 * @copyright 2026 Oxford Brookes University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manual_upload_processor
{
    public static function batch_process($trace) {
        foreach (manual_upload_processor::get_aggregated_reservations() as $eventid => $event) {

            // TODO : What are we doing with it?
        }
    }

    /**
     * Streams aggregated JSON strings one event at a time from local_obu_att_ws_manualupload.
     *
     * @return Generator<string, string> Yields eventidnumber => json_string
     */
    public static function get_aggregated_reservations(): \Generator {
        global $DB;

        $sql = "SELECT 
                    m.eventidnumber,
                    m.courseidnumber,
                    m.roomid,
                    m.start,
                    m.duration,
                    m.groupname,
                    m.semestername,
                    s.id AS existing_session_record_id,
                    CASE 
                        WHEN s.id IS NULL THEN 'INSERT'
                        ELSE 'UPDATE'
                    END AS action_needed
                FROM {local_obu_att_ws_manualupload} m
                LEFT JOIN {local_obu_att_ws_sessions} s 
                    ON m.eventidnumber = s.eventidnumber
                   AND m.courseidnumber = s.courseidnumber
                   AND m.groupname = s.groupname
                   AND m.semestername = s.semestername";

        $recordset = $DB->get_recordset_sql($sql);

        $current_event = null;
        $current_event_id = null;
        $current_course_index = -1;
        $current_course_id = null;

        foreach ($recordset as $row) {
            // 1. New Event encountered -> yield the completed previous event and reset
            if ($current_event_id !== $row->eventidnumber) {
                if ($current_event !== null) {
                    yield $current_event_id => $current_event;
                }

                $current_event_id = $row->eventidnumber;
                $current_course_id = null;
                $current_course_index = -1;

                $current_event = [
                    'eventIdNumber' => (string) $row->eventidnumber,
                    'roomId'        => (string) $row->roomid,
                    'start'         => (string) $row->start,
                    'duration'      => (string) $row->duration,
                    'courses'       => [],
                ];
            }

            // 2. New Course within the current event -> add new course entry
            if ($current_course_id !== $row->courseidnumber) {
                $current_course_id = $row->courseidnumber;
                $current_event['courses'][] = [
                    'courseIdNumber' => (string) $row->courseidnumber,
                    'groups'         => [],
                ];
                $current_course_index++;
            }

            // 3. Append the group to the active course
            $current_event['courses'][$current_course_index]['groups'][] = [
                'name'         => (string) $row->groupname,
                'semesterName' => (string) $row->semestername,
            ];
        }

        // Flush the very last event after the loop ends
        if ($current_event !== null) {
            yield $current_event_id => $current_event;
        }

        $recordset->close();
    }
}