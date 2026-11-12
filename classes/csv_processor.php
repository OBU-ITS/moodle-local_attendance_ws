<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is distributed under the GNU GPL v3 or later.

namespace local_attendance_ws;

defined('MOODLE_INTERNAL') || die();

/**
 * Import reservation CSV rows for attendance processing.
 *
 * @package local_attendance_ws
 * @copyright 2026 Oxford Brookes University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_processor
{
    public static function process_csv_file(string $filepath, $trace): int
    {
        global $DB;


        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_obu_att_ws_manualupload');
        $transaction->allow_commit();
        $trace->output("Previous rows deleted.");
        $time_start = microtime(true);

        $count = 0;
        $batchSize = 1000;
        $batch = [];

        foreach (\local_attendance_ws\csv_processor::process_sorted_csv_stream($filepath) as $event) {
            $batch[] = $event;

            if (count($batch) >= $batchSize) {
                $transaction = $DB->start_delegated_transaction();
                $DB->insert_records('local_obu_att_ws_manualupload', $batch);
                $transaction->allow_commit();

                $batch = []; // Clear chunk memory

                $count += 1000;
            }
        }

        if (!empty($batch)) {
            $transaction = $DB->start_delegated_transaction();
            $DB->insert_records('local_obu_att_ws_manualupload', $batch);
            $transaction->allow_commit();

            $count += count($batch);
        }

        $trace->output("Rows saved {$count}");
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start)/60;
        $trace->output("Total Execution Time: {$execution_time} Mins");
        return $count;
    }

    /**
     * Yields reservation records line-by-line from a CSV.
     * Uses constant O(1) memory regardless of file size.
     *
     * @param string $filepath Path to the CSV file.
     * @return \Generator Yields event array per completed event.
     */
    public static function process_sorted_csv_stream(string $filepath): \Generator {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new \moodle_exception('errorinvalidfile', 'error');
        }

        $file = fopen($filepath, 'r');
        if ($file === false) {
            throw new \moodle_exception('errorcannotopenfile', 'error');
        }

        // Skip header row
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            list($id, $room, $date, $time, $length, $module, $moduleGroup, $moduleGroupName) = $row;

            // Calculate start Unix timestamp
            $timeParts = explode('-', $time);
            $startTimeStr = trim($timeParts[0]);
            $dateObj = \DateTime::createFromFormat('!j/n/Y H:i', "{$date} {$startTimeStr}", new \DateTimeZone(date_default_timezone_get()));
            $startTimestamp = $dateObj ? (string)$dateObj->getTimestamp() : '0';

            // Accept HH:MM:SS and retain support for HH:MM (e.g. 02:00:00 -> 7200).
            list($hours, $minutes, $seconds) = array_pad(explode(':', $length), 3, '0');
            $durationSeconds = (string)(((int)$hours * 3600) + ((int)$minutes * 60) + (int)$seconds);

            // Standardize module to course ID number (e.g. ENGR6010_S1_2_202609 -> 2026.ENGR6010_S1_2)
            $courseIdNumber = self::format_course_id($module);

            // Extract semester name (e.g. S1)
            $groupSemesterName = self::extract_semester($moduleGroup);

            yield [
                'eventidnumber'     => (string)$id,
                'courseidnumber'    => $courseIdNumber,
                'roomid'            => (string)$room,
                'start'             => $startTimestamp,
                'duration'          => $durationSeconds,
                'groupname'         => (string)$moduleGroupName,
                'semestername' => $groupSemesterName,
            ];
        }

        fclose($file);
    }

    private static function format_course_id(string $module): string {
        if (preg_match('/^(.+)_(\d{4})\d{2}$/', trim($module), $matches)) {
            return "{$matches[2]}.{$matches[1]}";
        }
        return $module;
    }

    private static function extract_semester(string $module): string {
        if (preg_match('/^([A-Z0-9_]+)_(\d{4})\d{2}_Whole Cohort$/', $module, $matches)) {
            return "WC";
        }
        if (preg_match('/^([A-Z0-9_]+)_(\d{4})\d{2}_[a-zA-Z0-9]+_([A-Z0-9]+)$/', $module, $matches)) {
            return $matches[3];
        }
        return $module;
    }
}
