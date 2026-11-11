<?php
// Example URL:
// /local/attendance_ws/test/test_bulk_add_sessions.php
// (uses hardcoded data; edit file to change sessions)

require('../../../config.php');

global $CFG;
require_once($CFG->dirroot . '/local/attendance_ws/externallib.php');

if (!is_siteadmin()) {
    die('Access denied');
}

$params = [
    'courses' => [
        [
            'courseIdNumber' => 'COURSE101',
            'sessions' => [
                [
                    'slotId' => 'S1',
                    'roomId' => 'A1',
                    'group' => 'G1',
                    'start' => time() + 3600,
                    'duration' => 60,
                    'semesterName' => 'Autumn'
                ]
            ]
        ]
    ]
];

$result = local_attendance_ws_external::add_sessions($params);
echo json_encode($result);
