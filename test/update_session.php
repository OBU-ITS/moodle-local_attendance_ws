<?php
// Example URL:
// /local/attendance_ws/test/test_update_session.php?

require('../../../config.php');

global $CFG;
require_once($CFG->dirroot . '/local/attendance_ws/externallib.php');

if (!is_siteadmin()) {
    die('Access denied');
}

$sessionid = optional_param('sessionid', null, PARAM_INT);
$start = optional_param('start', null, PARAM_INT);
$duration = optional_param('duration', null, PARAM_INT);
$roomid = optional_param('roomid', null, PARAM_TEXT);

if (!$sessionid || !$start || !$duration || !$roomid) {
    echo "Missing required parameters. Example usage:<br>";
    echo "<code>?sessionid=123&start=1722354000&duration=90&roomid=R1</code>";
    exit;
}

$result = local_attendance_ws_external::update_session($sessionid, $start, $duration, $roomid);
echo json_encode($result);
