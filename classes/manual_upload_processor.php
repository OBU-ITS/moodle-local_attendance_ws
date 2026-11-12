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
    public static function batch_delete_process($trace) {
        global $DB;

        $sql = "SELECT s.eventidnumber
                  FROM {local_obu_att_ws_payload} p
             LEFT JOIN {local_obu_att_ws_manualupload} m
                    ON p.eventidnumber = m.eventidnumber
                 WHERE m.id IS NULL";

        $eventidnumbers = $DB->get_fieldset_sql($sql);
        $count = count($eventidnumbers);

        $trace->output("Delete started. Deleting {$count}");

        local_attendance_ws_delete_sessions($eventidnumbers);

        $trace->output("Delete complete.");
    }

    public static function batch_upsert_process($trace) {
        $trace->output("Upsert started.");
        foreach (manual_upload_processor::get_aggregated_reservation_upserts() as $event) {
            local_attendance_ws_upsert_sessions($event);
        }
        $trace->output("Upsert complete.");
    }

    private static function get_aggregated_reservation_upserts(): \Generator {
        global $DB;

        $sql = "SELECT 
                    eventidnumber,
                    courseidnumber,
                    roomid,
                    start,
                    duration,
                    groupname,
                    semestername,
                FROM {local_obu_att_ws_manualupload}";

        $recordset = $DB->get_recordset_sql($sql);

        $current_event = null;
        $current_event_id = null;
        $current_course_index = -1;
        $current_course_id = null;

        foreach ($recordset as $row) {
            // 1. New Event encountered -> yield the completed previous event and reset
            if ($current_event_id !== $row->eventidnumber) {
                if ($current_event !== null) {
                    yield $current_event;
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
            yield $current_event;
        }

        $recordset->close();
    }
}