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
 * @package    local_attendance_ws
 * @author     Emir Kamel
 * @copyright  2026, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class process_reservations extends \core\task\scheduled_task{

    public function get_name() : string {
        return "Process Reservations Task";
    }

    public function execute() {
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_attendance_ws');
        $lock = $lockfactory->get_lock('process_reservations', 30);

        if (!$lock) {
            mtrace('Could not acquire process reservations lock. Another run may already be active.');
            return;
        }

        try {
            $trace = new \text_progress_trace();

            $handler = new \local_attendance_ws\handlers\process_reservations_handler($trace);
            $handler->handle_process_reservations();
        } finally {
            $lock->release();
        }
        
    }
}