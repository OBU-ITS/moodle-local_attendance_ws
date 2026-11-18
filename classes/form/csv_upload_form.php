<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is distributed under the GNU GPL v3 or later.

namespace local_attendance_ws\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * CSV upload form.
 *
 * @package local_attendance_ws
 * @copyright 2026 Oxford Brookes University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_upload_form extends \moodleform {
    /** Define the upload controls. */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'local_attendance_ws'), null, [
            'accepted_types' => ['.csv'],
            'maxbytes' => 20 * 1024 * 1024, // TODO : 20MB enough?
        ]);
        $mform->addRule('csvfile', null, 'required', null, 'client');
        $this->add_action_buttons(false, get_string('csvprocess', 'local_attendance_ws'));
    }
}