<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is distributed under the GNU GPL v3 or later.

/**
 * Upload reservation data for attendance processing.
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
$PAGE->set_url(new moodle_url('/local/attendance_ws/csv.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('csvtitle', 'local_attendance_ws'));
$PAGE->set_heading(get_string('plugintitle', 'local_attendance_ws'));
$PAGE->navbar->add(get_string('csvtitle', 'local_attendance_ws'));

$form = new \local_attendance_ws\form\csv_upload_form();
$results = null;
$error = null;
$trace = new \html_progress_trace();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('csvtitle', 'local_attendance_ws'));

if ($form->get_data()) {
    $filepath = $form->save_temp_file('csvfile');
    if ($filepath === false) {
        $error = get_string('csvcannotread', 'local_attendance_ws');
    } else {
        try {
            $results = \local_attendance_ws\csv_processor::process_csv_file($filepath, $trace);
        } catch (moodle_exception $e) {
            $error = $e->getMessage();
        } finally {
            unlink($filepath);
        }
    }
}

echo html_writer::tag('p', get_string('csvinstructions', 'local_attendance_ws'));
echo html_writer::tag('pre', s("Id,Room,Date,Time,Length,Module,ModuleGroup,ModuleGroupName\n"
    . "76262,NHHB.2.13,10/12/2026,09:00-11:00,02:00:00,ENGR7006_S1_1_202609,ENGR7006_S1_1_202609_Set2_GS2,SetB2"));
$form->display();

if ($error !== null) {
    echo $OUTPUT->notification(s($error), 'error');
}
if ($results !== null) {
    if (!$results) {
        echo $OUTPUT->notification(get_string('csvnodata', 'local_attendance_ws'), 'warning');
    } else {
        echo $OUTPUT->heading(get_string('csvresults', 'local_attendance_ws'), 3);
        $trace->finished();
    }
}
echo $OUTPUT->footer();
