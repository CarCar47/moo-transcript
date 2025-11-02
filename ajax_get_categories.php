<?php
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
 * v1.0.32: AJAX endpoint to fetch gradebook categories for a course
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/grade/grade_category.php');

// Require login.
require_login();

// Check capability.
require_capability('gradereport/transcript:manage', context_system::instance());

// Get course ID parameter.
$courseid = required_param('courseid', PARAM_INT);

// Verify course exists.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Get all grade categories for this course (excluding the course-level category).
$categories = grade_category::fetch_all(['courseid' => $courseid]);

$result = [];

if ($categories) {
    foreach ($categories as $category) {
        // Skip the course-level category (parent = null).
        if ($category->is_course_category()) {
            continue;
        }

        $result[] = [
            'id' => $category->id,
            'fullname' => format_string($category->fullname),
            'depth' => $category->depth,
        ];
    }
}

// Sort by fullname.
usort($result, function($a, $b) {
    return strcmp($a['fullname'], $b['fullname']);
});

// Return JSON.
header('Content-Type: application/json');
echo json_encode($result);
