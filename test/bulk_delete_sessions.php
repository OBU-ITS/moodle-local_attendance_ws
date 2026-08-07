<?php
// Example URL: /local/attendance_ws/test/test_bulk_delete_sessions.php?

require('../../../config.php');

global $CFG;
require_once($CFG->dirroot . '/local/attendance_ws/externallib.php');

if (!is_siteadmin()) {
    die('Access denied');
}

$sessionsparam = optional_param('sessions', null, PARAM_TEXT); // Expecting comma-separated session IDs

if (!$sessionsparam) {
    echo "Missing required parameter. Example usage:<br>";
    echo "<code>?sessions=123,456,789</code>";
    exit;
}

$sessionids = array_map('intval', explode(',', $sessionsparam));

$result = local_attendance_ws_external::delete_sessions(['sessions' => $sessionids]);

echo json_encode($result, JSON_PRETTY_PRINT);
