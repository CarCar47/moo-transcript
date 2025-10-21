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
 * Student transcript request page
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/forms/transcript_request_form.php');
require_once(__DIR__ . '/classes/helper.php');

defined('MOODLE_INTERNAL') || die();

// Require login.
require_login();

// Get URL parameters for pre-filling form.
$programid = optional_param('programid', 0, PARAM_INT);
$requesttype = optional_param('type', '', PARAM_ALPHA);

// Set up page.
$PAGE->set_url(new moodle_url('/grade/report/transcript/request_transcript.php', [
    'programid' => $programid,
    'type' => $requesttype
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('requesttranscript', 'gradereport_transcript'));
$PAGE->set_heading(get_string('requesttranscript', 'gradereport_transcript'));

// Get available programs for current user (based on enrolled categories).
$programs = \gradereport_transcript\helper::get_user_programs($USER->id);

// Prepare custom data for form.
$customdata = [
    'programs' => $programs,
    'pricing' => \gradereport_transcript\helper::get_pricing_information($USER->id, $programid),
    'programid' => $programid,
    'requesttype' => $requesttype
];

// Create form instance.
$mform = new \gradereport_transcript\forms\transcript_request_form(null, $customdata);

// Handle form submission.
if ($mform->is_cancelled()) {
    // Redirect to dashboard.
    redirect(new moodle_url('/my/'));

} else if ($data = $mform->get_data()) {
    // Process the request.
    $result = \gradereport_transcript\helper::process_transcript_request($data);

    if ($result['success']) {
        // Redirect with success notification.
        redirect(
            new moodle_url('/grade/report/transcript/request_transcript.php'),
            get_string('requestsubmitted', 'gradereport_transcript'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Display error.
        echo $OUTPUT->header();
        echo $OUTPUT->notification($result['error'], \core\output\notification::NOTIFY_ERROR);
        $mform->display();
        echo $OUTPUT->footer();
        die();
    }
}

// Output page.
echo $OUTPUT->header();

// Page description.
echo html_writer::start_div('alert alert-info');
echo html_writer::tag('p', get_string('requestdescription', 'gradereport_transcript'));
echo html_writer::end_div();

// Display existing requests.
\gradereport_transcript\helper::display_user_requests($USER->id);

// Display form.
$mform->display();

echo $OUTPUT->footer();
