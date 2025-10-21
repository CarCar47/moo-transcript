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
 * Public transcript verification page
 *
 * This page is PUBLIC (no login required). Anyone can verify the authenticity
 * of a transcript by entering the verification code found on the PDF.
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Load Moodle configuration (REQUIRED for all Moodle pages).
// Path: /grade/report/transcript/verify.php -> /config.php (3 levels up).
require_once('../../../config.php');

defined('MOODLE_INTERNAL') || die();

// NO require_login() - this is a PUBLIC page accessible without authentication.

// Get verification code from URL parameter or form submission.
// PARAM_ALPHANUMEXT ensures only alphanumeric and hyphen characters (security).
$code = optional_param('code', '', PARAM_ALPHANUMEXT);

// Set up page using Moodle Page API.
$PAGE->set_url(new moodle_url('/grade/report/transcript/verify.php', !empty($code) ? ['code' => $code] : []));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('verifytranscript', 'gradereport_transcript'));
$PAGE->set_heading(get_string('verifytranscript', 'gradereport_transcript'));

// Output page header.
echo $OUTPUT->header();

// Page heading.
echo $OUTPUT->heading(get_string('verifytranscript', 'gradereport_transcript'));

// Display instructions.
echo html_writer::start_div('alert alert-info');
echo html_writer::tag('p', get_string('verificationinstructions', 'gradereport_transcript'));
echo html_writer::end_div();

// Verification form.
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring(), 'class' => 'form-inline mb-3']);
echo html_writer::start_div('form-group mr-2');
echo html_writer::label(get_string('verifycode', 'gradereport_transcript'), 'code', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'code',
    'id' => 'code',
    'value' => s($code),
    'placeholder' => 'TXN-XXXXXXXXXXXX',
    'class' => 'form-control',
    'required' => 'required',
    'pattern' => '[A-Z0-9-]+',
    'title' => 'Verification code (e.g., TXN-A1B2C3D4E5F6)'
]);
echo html_writer::end_div();
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('verifybutton', 'gradereport_transcript'),
    'class' => 'btn btn-primary'
]);
echo html_writer::end_tag('form');

// Process verification if code provided.
if (!empty($code)) {
    echo html_writer::start_div('verification-result mt-4');
    echo $OUTPUT->heading(get_string('verificationresult', 'gradereport_transcript'), 3);

    // Query database for verification record (parameterized query - secure).
    $record = $DB->get_record('gradereport_transcript_verify', ['verificationcode' => $code]);

    if ($record) {
        // Valid code found - fetch related data.
        $user = $DB->get_record('user', ['id' => $record->userid], 'id, firstname, lastname');
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $record->programid], 'id, name');
        $school = null;
        if ($program) {
            $school = $DB->get_record('gradereport_transcript_schools', ['id' => $program->schoolid], 'id, name');
        }

        // Display success message.
        echo html_writer::start_div('alert alert-success');
        echo html_writer::tag('h4', '✓ ' . get_string('validtranscript', 'gradereport_transcript'), ['class' => 'alert-heading']);

        // Display verification details in a table.
        echo html_writer::start_tag('table', ['class' => 'table table-bordered mt-3']);
        echo html_writer::start_tag('tbody');

        // Student name.
        if ($user) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', get_string('issuedto', 'gradereport_transcript'), ['scope' => 'row', 'width' => '30%']);
            echo html_writer::tag('td', fullname($user));
            echo html_writer::end_tag('tr');
        }

        // School name.
        if ($school) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', get_string('schoolname', 'gradereport_transcript'), ['scope' => 'row']);
            echo html_writer::tag('td', format_string($school->name));
            echo html_writer::end_tag('tr');
        }

        // Program name.
        if ($program) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', get_string('programname', 'gradereport_transcript'), ['scope' => 'row']);
            echo html_writer::tag('td', format_string($program->name));
            echo html_writer::end_tag('tr');
        }

        // Document type.
        $doctype = ($record->documenttype == 'official')
            ? get_string('transcriptofficial', 'gradereport_transcript')
            : get_string('transcriptunofficial', 'gradereport_transcript');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('documenttype', 'gradereport_transcript'), ['scope' => 'row']);
        echo html_writer::tag('td', $doctype);
        echo html_writer::end_tag('tr');

        // Issue date.
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('issuedate', 'gradereport_transcript'), ['scope' => 'row']);
        echo html_writer::tag('td', userdate($record->issuedate, get_string('strftimedatefullshort', 'langconfig')));
        echo html_writer::end_tag('tr');

        // Verification code.
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('verifycode', 'gradereport_transcript'), ['scope' => 'row']);
        echo html_writer::tag('td', html_writer::tag('code', s($record->verificationcode)));
        echo html_writer::end_tag('tr');

        // PDF hash (for integrity verification).
        if (!empty($record->pdfhash)) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('th', 'PDF Hash (SHA256)', ['scope' => 'row']);
            echo html_writer::tag('td', html_writer::tag('small', html_writer::tag('code', s($record->pdfhash))));
            echo html_writer::end_tag('tr');
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');

        echo html_writer::tag('p', html_writer::tag('small', html_writer::tag('em',
            'This transcript was issued on ' . userdate($record->issuedate) .
            ' and is valid. The information above confirms the authenticity of this document.'
        )));

        echo html_writer::end_div(); // alert-success

    } else {
        // Invalid code - not found in database.
        echo html_writer::start_div('alert alert-danger');
        echo html_writer::tag('h4', '✗ ' . get_string('invalidcode', 'gradereport_transcript'), ['class' => 'alert-heading']);
        echo html_writer::tag('p', get_string('codenotfound', 'gradereport_transcript'));
        echo html_writer::tag('p', html_writer::tag('small', html_writer::tag('em',
            'The verification code you entered does not exist in our system. ' .
            'Please check that you have entered the code correctly as it appears on the transcript.'
        )));
        echo html_writer::end_div();
    }

    echo html_writer::end_div(); // verification-result
}

// Output page footer.
echo $OUTPUT->footer();
