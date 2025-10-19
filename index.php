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
 * The gradebook transcript report
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/transcript/lib.php');

$courseid = required_param('id', PARAM_INT);
$userid   = optional_param('userid', null, PARAM_INT);

$PAGE->set_url(new moodle_url('/grade/report/transcript/index.php', ['id' => $courseid]));

// Basic access checks.
if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new \moodle_exception('invalidcourseid');
}
require_login($course);
$PAGE->set_pagelayout('report');

$context = context_course::instance($course->id);
require_capability('gradereport/transcript:view', $context);

// Validate user access.
if ($userid && $userid != $USER->id) {
    require_capability('gradereport/transcript:viewall', $context);
}

// If no specific user, show current user.
if (!$userid) {
    $userid = $USER->id;
}

// Set page title and heading.
$PAGE->set_title(get_string('pluginname', 'gradereport_transcript'));
$PAGE->set_heading($course->fullname);

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mytranscript', 'gradereport_transcript'));

// Placeholder content.
echo html_writer::tag('div', get_string('pluginname', 'gradereport_transcript'), ['class' => 'alert alert-info']);
echo html_writer::tag('p', 'This plugin is currently in development. Transcript generation features will be available soon.');

// Display basic user information.
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
echo html_writer::tag('h3', 'Student Information');
echo html_writer::tag('p', 'Name: ' . fullname($user));
echo html_writer::tag('p', 'Email: ' . $user->email);

// Output footer.
echo $OUTPUT->footer();
