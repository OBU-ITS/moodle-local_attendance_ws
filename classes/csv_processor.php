<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is distributed under the GNU GPL v3 or later.

namespace local_attendance_ws;

defined('MOODLE_INTERNAL') || die();

/**
 * Convert module group CSV files to per-course JSON.
 *v
 * @package local_attendance_ws
 * @copyright 2026 Oxford Brookes University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_processor
{
    /**
     * Yields JSON objects line-by-line for a CSV sorted by 'Id'.
     * Uses constant O(1) memory regardless of file size.
     *
     * @param string $filepath Path to the CSV file.
     * @return \Generator Yields [eventId => json_string] per completed event.
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

        $currentId = null;
        $currentEvent = null;

        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            list($id, $room, $date, $time, $length, $module, $moduleGroup, $moduleGroupName) = $row;
            $id = (string)$id;

            // When ID changes, yield the completed event and free memory
            if ($currentId !== null && $id !== $currentId) {
                yield $currentId => self::finalize_event_json($currentEvent);
                $currentEvent = null;
            }

            // Initialize new event block
            if ($currentEvent === null) {
                $currentId = $id;

                // Calculate timestamp
                $timeParts = explode('-', $time);
                $startTimeStr = trim($timeParts[0]);
                $dateObj = \DateTime::createFromFormat('j/n/y H:i', "{$date} {$startTimeStr}", new \DateTimeZone(date_default_timezone_get()));
                $startTimestamp = $dateObj ? (string)$dateObj->getTimestamp() : '0';

                // Calculate duration in seconds
                list($hours, $minutes) = explode(':', $length);
                $durationSeconds = (string)(((int)$hours * 3600) + ((int)$minutes * 60));

                $currentEvent = [
                    'eventIdNumber' => $id,
                    'roomId'        => (string)$room,
                    'start'         => $startTimestamp,
                    'duration'      => $durationSeconds,
                    'courses'       => []
                ];
            }

            // Course ID formatting & Semester extraction
            $courseIdNumber = self::format_course_id($module);
            $semesterName   = self::extract_semester($module);

            if (!isset($currentEvent['courses'][$courseIdNumber])) {
                $currentEvent['courses'][$courseIdNumber] = [
                    'courseIdNumber' => $courseIdNumber,
                    'groups'         => []
                ];
            }

            $currentEvent['courses'][$courseIdNumber]['groups'][] = [
                'name'         => (string)$moduleGroupName,
                'semesterName' => $semesterName
            ];
        }

        // Yield the very last event in the file
        if ($currentEvent !== null) {
            yield $currentId => self::finalize_event_json($currentEvent);
        }

        fclose($file);
    }

    /**
     * Converts course associative array into indexed structure and encodes to JSON.
     */
    private static function finalize_event_json(array $eventData): string {
        $eventData['courses'] = array_values($eventData['courses']);
        return json_encode($eventData, JSON_UNESCAPED_SLASHES);
    }

    // TODO : Review following Regexs
    private static function format_course_id(string $module): string {
        if (preg_match('/^(.+)_(\d{4})\d{2}$/', trim($module), $matches)) {
            return "{$matches[2]}.{$matches[1]}";
        }
        return $module;
    }

    private static function extract_semester(string $module): string {
        if (preg_match('/_(S\d+)/i', $module, $matches)) {
            return strtoupper($matches[1]);
        }
        return 'S1';
    }
}