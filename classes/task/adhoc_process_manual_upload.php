<?php

namespace local_attendance_ws\task;

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Ad hoc task to process manually uploaded attendance reservations.
 *
 * @package    local_attendance_ws
 * @author     Emir Kamel
 * @copyright  2026, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class adhoc_process_manual_upload extends \core\task\adhoc_task
{
    /**
     * Run the manual upload processor.
     */
    public function execute() {
        $trace = new \text_progress_trace();
        \local_attendance_ws\manual_upload_processor::batch_delete_process($trace);
        \local_attendance_ws\manual_upload_processor::batch_upsert_process($trace);
    }
}
