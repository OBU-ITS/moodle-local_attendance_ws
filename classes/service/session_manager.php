<?php
namespace local_attendance_ws\service;

defined('MOODLE_INTERNAL') || die();

use context_module;
use moodle_exception;

global $CFG;
require_once($CFG->dirroot . '/mod/attendance/lib.php');
require_once($CFG->dirroot . '/mod/attendance/locallib.php');
require_once($CFG->dirroot . '/local/obu_metalinking/lib.php');
require_once($CFG->dirroot . '/local/obu_group_manager/lib.php');

class session_manager {

    public static function resolve_attendance_context(string $courseidnumber): array {
        global $DB;

        $course = $DB->get_record('course', ['idnumber' => $courseidnumber], '*', MUST_EXIST);
        $teachingcourse = local_obu_metalinking_get_teaching_course($course);
        $attendance = local_attendance_ws_find_attendance_activity($teachingcourse);

        if (!$attendance) {
            throw new moodle_exception("No attendance activity found for course '{$courseidnumber}'");
        }

        $cm = get_coursemodule_from_instance('attendance', $attendance->id, 0, false);
        if (!$cm) {
            throw new moodle_exception("No course module found for attendance ID '{$attendance->id}'");
        }

        $context = context_module::instance($cm->id);

        return [
            'course' => $course,
            'attendance' => $attendance,
            'cm' => $cm,
            'context' => $context
        ];
    }

    public static function create_session(array $data): \stdClass {
        global $DB;

        $resolved = self::resolve_attendance_context($data['courseIdNumber']);
        $context = $resolved['context'];
        require_capability('mod/attendance:manageattendances', $context);

        $pluginconfig = get_config('attendance');
        $session = session_service::build_session_object($data, $resolved['course'], $resolved['attendance'], $pluginconfig);
        $session->id = $DB->insert_record('attendance_sessions', $session);

        attendance_create_calendar_event($session);

        session_service::trigger_session_added_event($resolved['attendance'], $resolved['cm'], $context, $session);

        return $session;
    }

    public static function update_session(int $sessionid, array $data): \stdClass {
        global $DB;

        $session = $DB->get_record('attendance_sessions', ['id' => $sessionid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('attendance', $session->attendanceid, 0, false);
        if (!$cm) {
            throw new moodle_exception("Course module missing for attendance ID '{$session->attendanceid}'");
        }

        $context = context_module::instance($cm->id);
        require_capability('mod/attendance:manageattendances', $context);

        $session = session_service::update_session_properties($session, $data['start'], $data['duration'], $data['roomid']);
        $DB->update_record('attendance_sessions', $session);

        session_service::trigger_session_updated_event($session, $cm, $context);

        return $session;
    }

    public static function delete_session(int $sessionid): bool {
        global $DB;

        $session = $DB->get_record('attendance_sessions', ['id' => $sessionid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('attendance', $session->attendanceid, 0, false);
        if (!$cm) {
            throw new moodle_exception("Course module not found for deletion.");
        }

        $context = context_module::instance($cm->id);
        require_capability('mod/attendance:manageattendances', $context);

        if ($session->caleventid) {
            attendance_delete_calendar_events([$sessionid]);
        }

        $DB->delete_records('attendance_log', ['sessionid' => $sessionid]);
        $DB->delete_records('attendance_sessions', ['id' => $sessionid]);

        session_service::trigger_session_deleted_event($sessionid, $session->attendanceid, $cm, $context);

        return true;
    }
}
