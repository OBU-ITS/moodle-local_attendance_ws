<?php
// Example URL: /local/attendance_ws/test/bulk_update_sessions.php
// (uses hardcoded data; edit file to change sessions)

require('../../../config.php');

global $CFG;
require_once($CFG->dirroot . '/local/attendance_ws/externallib.php');

if (!is_siteadmin()) {
    die('Access denied');
}

// Example hardcoded input — change this for real session IDs and times.
$params = [
    'sessions' => [
        [
            'sessionid' => 123,
            'start' => time() + 3600,         // 1 hour from now
            'duration' => 60,
            'roomid' => 'Room-A'
        ],
        [
            'sessionid' => 456,
            'start' => time() + 7200,         // 2 hours from now
            'duration' => 45,
            'roomid' => 'Room-B'
        ]
    ]
];

$result = local_attendance_ws_external::update_sessions($params);

echo json_encode($result, JSON_PRETTY_PRINT);
