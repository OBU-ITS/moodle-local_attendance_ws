<?php

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
 * OBU Attendance Web Service - Database upgrade
 *
 * @package    attendance_ws
 * @category   local
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
global $CFG;
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/local/attendance_ws/locallib.php');

function xmldb_local_attendance_ws_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2024090501) {
        $sql = "DELETE FROM {grade_items_history}
                WHERE itemname = 'Module Attendance' AND itemtype = 'mod' AND itemmodule = 'attendance'";

        $DB->execute($sql);

        $sql = "DELETE FROM {grade_items}
                WHERE itemname = 'Module Attendance' AND itemtype = 'mod' AND itemmodule = 'attendance'";

        $DB->execute($sql);

        upgrade_plugin_savepoint(true, 2024090501, 'local', 'attendance_ws');
    }

    if ($oldversion < 2024100103) {
        $sql = "UPDATE {attendance_sessions} s1
                INNER JOIN {attendance_sessions} s2 ON s1.id = s2.id
                SET s1.description = CONCAT('Room(s): ', s2.roomid)
                WHERE s2.roomid IS NOT NULL AND s2.roomid <> ''";

        $DB->execute($sql);

        upgrade_plugin_savepoint(true, 2024100103, 'local', 'attendance_ws');
    }

    if($oldversion < 2024110102) {
        $sql = "SELECT DISTINCT 
                    c.id AS 'parentid',
                    e.customint1 AS 'childid'
                FROM {enrol} e
                JOIN {course} c ON c.id = e.courseid
                JOIN {course_categories} cat ON cat.id = c.category
                JOIN {attendance} a ON a.course = c.id
                JOIN {attendance_sessions} s ON a.id = s.attendanceid AND (s.timetableeventid <> '' AND s.timetableeventid IS NOT NULL)
                WHERE e.enrol = 'meta'
                  AND (c.idnumber NOT LIKE '%.%' OR cat.idnumber NOT LIKE 'SRS%')
                  AND c.idnumber NOT LIKE '%ANML%'";

        $records = $DB->get_records_sql($sql);

        $trace = new \null_progress_trace();
        foreach($records as $record) {
            local_attendance_ws_meta_course_return($trace,$record->parentid,$record->childid);
        }

        upgrade_plugin_savepoint(true, 2024110102, 'local', 'attendance_ws');
    }

    if($oldversion < 2025073001) {
        $table = new xmldb_table('local_obu_att_session_lookup');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('slot_id', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_id_number', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('session_id', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025073001, 'local', 'attendance_ws');
    }

    return $result;
}