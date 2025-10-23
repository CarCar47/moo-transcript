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
 * Admin page to manage transcript requests
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/forms/request_details_form.php');
require_once(__DIR__ . '/classes/helper.php');

defined('MOODLE_INTERNAL') || die();

// Require login and capability.
require_login();
require_capability('gradereport/transcript:manage', context_system::instance());

// Get parameters.
$action = optional_param('action', '', PARAM_ALPHA);
$requestid = optional_param('requestid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

// Set up page.
$PAGE->set_url(new moodle_url('/grade/report/transcript/manage_requests.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('managerequests', 'gradereport_transcript'));
$PAGE->set_heading(get_string('managerequests', 'gradereport_transcript'));

// Handle detailed view/edit action.
if ($action === 'details' && $requestid) {
    $request = $DB->get_record('gradereport_transcript_requests', ['id' => $requestid], '*', MUST_EXIST);
    $user = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);
    $program = $DB->get_record('gradereport_transcript_programs', ['id' => $request->programid], '*', MUST_EXIST);

    // Create form.
    $mform = new \gradereport_transcript\forms\request_details_form(null, ['request' => $request]);

    // Handle form submission.
    if ($mform->is_cancelled()) {
        redirect($PAGE->url);
    } else if ($data = $mform->get_data()) {
        // Update request with payment and delivery information.
        $updaterequest = new stdClass();
        $updaterequest->id = $data->requestid;
        $updaterequest->paymentstatus = $data->paymentstatus;
        $updaterequest->paymentmethod = $data->paymentmethod ?? null;
        $updaterequest->receiptnumber = $data->receiptnumber ?? null;
        $updaterequest->paiddate = $data->paiddate ?? null;
        $updaterequest->paymentnotes = $data->paymentnotes ?? null;
        $updaterequest->deliverystatus = $data->deliverystatus;
        $updaterequest->deliverydate = $data->deliverydate ?? null;
        $updaterequest->trackingnumber = $data->trackingnumber ?? null;
        $updaterequest->deliverynotes = $data->deliverynotes ?? null;

        // Save program completion fields (for official transcripts).
        if ($request->requesttype === 'official') {
            // Convert 0 (unchecked optional date checkbox) to null for database storage.
            // Moodle date_selector with optional=true returns 0 when unchecked, timestamp when checked.
            $updaterequest->programstartdate = !empty($data->programstartdate) ? $data->programstartdate : null;
            $updaterequest->completionstatus = !empty($data->completionstatus) ? $data->completionstatus : null;
            $updaterequest->graduationdate = !empty($data->graduationdate) ? $data->graduationdate : null;
            $updaterequest->withdrawndate = !empty($data->withdrawndate) ? $data->withdrawndate : null;
        }

        // Handle pickup person name (store in delivery notes with structured format).
        if ($request->deliverymethod === 'pickup' && !empty($data->pickupperson)) {
            $pickupinfo = "Picked up by: " . $data->pickupperson;
            if (!empty($updaterequest->deliverynotes)) {
                $updaterequest->deliverynotes .= "\n" . $pickupinfo;
            } else {
                $updaterequest->deliverynotes = $pickupinfo;
            }
        }

        $updaterequest->timemodified = time();

        $DB->update_record('gradereport_transcript_requests', $updaterequest);

        // Send notification to student if delivery status changed.
        if ($request->deliverystatus !== $data->deliverystatus) {
            \gradereport_transcript\helper::send_student_notification($requestid, 'delivery_' . $data->deliverystatus);
        }

        redirect(
            $PAGE->url,
            get_string('requestupdated', 'gradereport_transcript'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // Display detail page.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('requestdetails', 'gradereport_transcript'));

    // Display request summary.
    echo html_writer::start_div('alert alert-info');
    echo html_writer::tag('h4', get_string('requestsummary', 'gradereport_transcript'));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('student', 'gradereport_transcript') . ': ') . fullname($user));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('program', 'gradereport_transcript') . ': ') . format_string($program->name));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('requesttype', 'gradereport_transcript') . ': ') .
        get_string('transcript' . $request->requesttype, 'gradereport_transcript'));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('deliverymethod', 'gradereport_transcript') . ': ') .
        get_string('delivery' . $request->deliverymethod, 'gradereport_transcript'));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('price', 'gradereport_transcript') . ': ') .
        '$' . format_float($request->price, 2));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('requestdate', 'gradereport_transcript') . ': ') .
        userdate($request->timecreated, '%m/%d/%Y %I:%M %p'));

    // Display recipient information if available.
    if (!empty($request->recipientname)) {
        echo html_writer::tag('p', html_writer::tag('strong', get_string('recipientinformation', 'gradereport_transcript') . ':'));
        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientname', 'gradereport_transcript') . ': ') . s($request->recipientname));
        if (!empty($request->recipientemail)) {
            echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientemail', 'gradereport_transcript') . ': ') . s($request->recipientemail));
        }
        if (!empty($request->recipientaddress)) {
            echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientaddress', 'gradereport_transcript') . ': ') . nl2br(s($request->recipientaddress)));
        }
        if (!empty($request->recipientphone)) {
            echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientphone', 'gradereport_transcript') . ': ') . s($request->recipientphone));
        }
        echo html_writer::end_tag('ul');
    }

    // Display notes if available.
    if (!empty($request->notes)) {
        echo html_writer::tag('p', html_writer::tag('strong', get_string('notes', 'gradereport_transcript') . ': ') . nl2br(s($request->notes)));
    }

    echo html_writer::end_div();

    // Display form for payment and delivery recording.
    $mform->display();

    // Add link to download transcript.
    echo html_writer::start_div('mt-3');
    $downloadurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
        'programid' => $request->programid,
        'userid' => $request->userid,
        'official' => ($request->requesttype === 'official' ? 1 : 0),
        'requestid' => $request->id,
        'action' => 'download',
        'sesskey' => sesskey()
    ]);
    echo html_writer::link($downloadurl, get_string('downloadtranscript', 'gradereport_transcript'),
        ['class' => 'btn btn-primary', 'target' => '_blank']);
    echo html_writer::end_div();

    echo $OUTPUT->footer();
    exit;
}

// Handle actions.
if ($action && $requestid && confirm_sesskey()) {
    $request = $DB->get_record('gradereport_transcript_requests', ['id' => $requestid], '*', MUST_EXIST);

    if ($confirm) {
        switch ($action) {
            case 'approve':
                \gradereport_transcript\helper::approve_request($requestid);
                redirect(
                    $PAGE->url,
                    get_string('requestapproved', 'gradereport_transcript'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
                break;

            case 'reject':
                \gradereport_transcript\helper::reject_request($requestid);
                redirect(
                    $PAGE->url,
                    get_string('requestrejected', 'gradereport_transcript'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
                break;

            case 'markpaid':
                \gradereport_transcript\helper::mark_paid($requestid);
                redirect(
                    $PAGE->url,
                    get_string('markedpaid', 'gradereport_transcript'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
                break;
        }
    } else {
        // Show confirmation page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('confirm'));

        $confirmurl = new moodle_url($PAGE->url, [
            'action' => $action,
            'requestid' => $requestid,
            'confirm' => 1,
            'sesskey' => sesskey()
        ]);

        $user = $DB->get_record('user', ['id' => $request->userid]);
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $request->programid]);

        $message = get_string('confirm' . $action, 'gradereport_transcript', [
            'studentname' => fullname($user),
            'programname' => format_string($program->name),
            'requesttype' => $request->requesttype
        ]);

        echo $OUTPUT->confirm($message, $confirmurl, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }
}

// Output page.
echo $OUTPUT->header();

// Page description.
echo html_writer::start_div('alert alert-info');
echo html_writer::tag('p', get_string('managerequestsdesc', 'gradereport_transcript'));
echo html_writer::end_div();

// Display filters.
\gradereport_transcript\helper::display_request_filters();

// Display requests table.
\gradereport_transcript\helper::display_requests_table();

echo $OUTPUT->footer();
