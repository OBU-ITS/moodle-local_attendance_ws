<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is distributed under the GNU GPL v3 or later.

namespace local_attendance_ws;

defined('MOODLE_INTERNAL') || die();

/**
 * @package local_attendance_ws
 * @copyright 2026 Oxford Brookes University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manual_upload_processor
{
    public static function preview_deletes($trace)
    {
        global $DB;

        $sql = "
            SELECT count(1)
            FROM {local_obu_att_ws_sessions} s
            LEFT JOIN {local_obu_att_ws_manualupload} m 
                ON s.eventidnumber = m.eventidnumber
               AND s.courseidnumber = m.courseidnumber
               AND s.groupname = m.groupname
               AND s.semestername = m.semestername
            WHERE m.id IS NULL;";

        $count = $DB->count_records_sql($sql);

        $trace->output("Sessions to Delete: {$count}");
    }

    public static function preview_upserts($trace)
    {
        global $DB;

        $sql = "
            SELECT count(1)
            FROM {local_obu_att_ws_manualupload} m
            LEFT JOIN {local_obu_att_ws_sessions} s 
                ON m.eventidnumber = s.eventidnumber
               AND m.courseidnumber = s.courseidnumber
               AND m.groupname = s.groupname
               AND m.semestername = s.semestername;";

        $count = $DB->count_records_sql($sql);

        $trace->output("Sessions to be upserted: {$count}");
    }

    public static function action_deletes($trace)
    {
        // TODO : Send to payload table
    }
    public static function action_upserts($trace)
    {
        // TODO : Send to payload table
    }
}