<?php
// Example URL:
// /local/attendance_ws/test/test_delete_session.php?

require('../../../config.php');

global $CFG;
require_once($CFG->dirroot . '/local/attendance_ws/externallib.php');

if (!is_siteadmin()) {
    die('Access denied');
}

$sessionid = optional_param('sessionid', null, PARAM_INT);

if (!$sessionid) {
    echo "Missing required parameter. Example usage:<br>";
    echo "<code>?sessionid=123</code>";
    exit;
}

$result = local_attendance_ws_external::delete_session($sessionid);
echo json_encode($result);
