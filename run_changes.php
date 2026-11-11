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

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/attendance_ws:managesessions', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/attendance_ws/run_changes.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('runchangestitle', 'local_attendance_ws'));
$PAGE->set_heading(get_string('plugintitle', 'local_attendance_ws'));
$PAGE->navbar->add(get_string('runchangestitle', 'local_attendance_ws'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('runchangestitle', 'local_attendance_ws'));

$csvlink = html_writer::link(new moodle_url('/local/attendance_ws/csv.php'),
    get_string('csvuploadlink', 'local_attendance_ws'));
echo $OUTPUT->notification(get_string('runchangescsvwarning', 'local_attendance_ws', $csvlink), 'warning');

// Placeholder only: connect this button to the processing workflow later.
echo html_writer::tag('button', get_string('runchangesbutton', 'local_attendance_ws'), [
    'type' => 'button',
    'id' => 'attendance-run-changes',
    'class' => 'btn btn-primary',
]);

echo $OUTPUT->footer();
