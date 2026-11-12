<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is distributed under the GNU GPL v3 or later.

/**
 * Prepare to run attendance changes after uploading the group CSV.
 *
 * @package local_attendance_ws
 * @copyright 2026 Oxford Brookes University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_attendance_ws\task\adhoc_process_manual_upload;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/task/adhoc_process_manual_upload.php');

require_login();
$context = context_system::instance();
require_capability('local/attendance_ws:managesessions', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/attendance_ws/run_changes.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('runchangestitle', 'local_attendance_ws'));
$PAGE->set_heading(get_string('plugintitle', 'local_attendance_ws'));
$PAGE->navbar->add(get_string('runchangestitle', 'local_attendance_ws'));

if (data_submitted()) {
    require_sesskey();

    $task = new adhoc_process_manual_upload();
    $task->set_component('local_attendance_ws');
    \core\task\manager::queue_adhoc_task($task);

    redirect($PAGE->url, get_string('runchangestaskcreated', 'local_attendance_ws'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('runchangestitle', 'local_attendance_ws'));

$csvlink = html_writer::link(new moodle_url('/local/attendance_ws/csv.php'),
    get_string('csvuploadlink', 'local_attendance_ws'));
echo $OUTPUT->notification(get_string('runchangescsvwarning', 'local_attendance_ws', $csvlink), 'warning');

echo $OUTPUT->single_button($PAGE->url, get_string('runchangesbutton', 'local_attendance_ws'), 'post',
    ['primary' => true]);

echo $OUTPUT->footer();
