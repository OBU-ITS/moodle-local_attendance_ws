<?php
namespace local_attendance_ws\service;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/attendance/lib.php');
require_once($CFG->dirroot . '/mod/attendance/locallib.php');
require_once($CFG->dirroot . '/local/obu_metalinking/lib.php');
require_once($CFG->dirroot . '/local/obu_group_manager/lib.php');

class session_service {

    public static function build_session_object($data, $course, $attendance, $pluginconfig) {
        global $DB;

        $session = new \stdClass();
        $session->attendanceid = $attendance->id;
        $session->timetableeventid = $data['slotid'];
        $session->roomid = $data['roomid'];
        $session->sessdate = $data['start'];
        $session->duration = $data['duration'];
        $session->lasttaken = null;
        $session->lasttakenby = 0;
        $session->timemodified = time();
        $session->description = "Room(s): " . $data['roomid'];
        $session->descriptionformat = 1;
        $session->statusset = 0;
        $session->calendarevent = 0;

        $salt = get_config('local_attendance_ws', 'salt');
        $session->studentpassword = local_attendance_ws_password_hash(
            $data['slotid'], $data['roomid'], $data['start'], 6, $salt
        );
        $session->sessioninstancecode = local_attendance_ws_session_instance_code(
            $data['slotid'], $data['roomid'], $data['start']
        );

        $group = $data['group'];
        $semesterName = $data['semesterName'] ?? null;
        $teachingcourse = local_obu_metalinking_get_teaching_course($course);

        $usergroup = ($group === '0' || $group === '')
            ? local_obu_group_manager_create_system_group($course, null, null, null, null, $teachingcourse)
            : local_obu_group_manager_create_system_group($course, null, null, $semesterName, $group, $teachingcourse);

        $session->groupid = $usergroup->id;

        $defaults = [
            'caleventid' => 'calendarevent_default',
            'studentscanmark' => 'studentscanmark_default',
            'randompassword' => 'randompassword_default',
            'includeqrcode' => 'includeqrcode_default',
            'autoassignstatus' => 'autoassignstatus',
            'allowupdatestatus' => 'allowupdatestatus_default',
            'rotateqrcode' => 'rotateqrcode_default',
            'automark' => 'automark_default',
            'studentsearlyopentime' => 'studentsearlyopentime',
        ];

        foreach ($defaults as $property => $configkey) {
            if (isset($pluginconfig->{$configkey})) {
                $session->{$property} = $pluginconfig->{$configkey};
            }
        }

        if (!empty($session->rotateqrcode)) {
            $secret = local_attendance_ws_password_hash(
                $data['slotid'], $data['roomid'], $data['start'], 6, $salt
            );
            $session->studentpassword = $secret;
            $session->rotateqrcodesecret = $secret;
        }

        return $session;
    }

    public static function update_session_properties($session, $start, $duration, $roomid) {
        $session->sessdate = $start;
        $session->duration = $duration;
        $session->roomid = $roomid;
        $session->description = "Room(s): " . $roomid;
        $session->timemodified = time();
        return $session;
    }

    public static function trigger_session_added_event($attendance, $cm, $context, $session) {
        $event = \mod_attendance\event\session_added::create([
            'objectid' => $attendance->id,
            'context' => $context,
            'other' => [
                'info' => construct_session_full_date_time($session->sessdate, $session->duration)
            ]
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('attendance_sessions', $session);
        $event->trigger();
    }

    public static function trigger_session_updated_event($session, $cm, $context) {
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
    }

    public static function trigger_session_deleted_event($sessionid, $attendanceid, $cm, $context) {
        $event = \mod_attendance\event\session_deleted::create([
            'objectid' => $attendanceid,
            'context' => $context,
            'other' => ['info' => $sessionid]
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        $event->trigger();
    }
}
