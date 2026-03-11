<?php

namespace local_attendance_ws\handlers;

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

use local_attendance_ws\service\process_reservations_service;
use progress_trace;
class process_reservations_handler {
    private process_reservations_service $process_reservations_service;

    private progress_trace $trace;

    public function __construct($trace) {
        $this->process_reservations_service = process_reservations_service::getInstance();
        $this->trace = $trace;
    }

    public function handle_process_reservations() {
        $unprocessedReservations = $this->process_reservations_service->get_unprocessed_reservations();
        if (count($unprocessedReservations) == 0) {
            $this->trace->output("No unprocessed reservations found.");
        } else {
            $this->process_reservations_service->process_reservations($this->trace, $unprocessedReservations);
            $this->trace->output("Processed " . count($unprocessedReservations) . " reservation records.");
        }
    }
}