<?php
// Example URL:
// /local/attendance_ws/test/test_add_session.php?

require('../../../config.php');

global $CFG;
require_once($CFG->dirroot . '/local/attendance_ws/externallib.php');

if (!is_siteadmin()) {
    die('Access denied');
}

$courseidnumber = optional_param('courseidnumber', null, PARAM_TEXT);
$slotid = optional_param('slotid', null, PARAM_TEXT);
$roomid = optional_param('roomid', null, PARAM_TEXT);
$group = optional_param('group', null, PARAM_TEXT);
$start = optional_param('start', null, PARAM_INT);
$duration = optional_param('duration', null, PARAM_INT);
$semester = optional_param('semester', null, PARAM_TEXT);

if (!$courseidnumber || !$slotid || !$roomid || !$group || !$start || !$duration || !$semester) {
    echo "Missing required parameters. Example usage:<br>";
    echo "<code>?courseidnumber=ABC123&slotid=SLOT1&roomid=R1&group=G1&start=1722354000&duration=90&semester=Autumn</code>";
    exit;
}

$result = local_attendance_ws_external::add_session($courseidnumber, $slotid, $roomid, $group, $start, $duration, $semester);
echo json_encode($result);
