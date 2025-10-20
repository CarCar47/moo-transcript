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
            send_student_notification($requestid, 'delivery_' . $data->deliverystatus);
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
    echo html_writer::tag('p', html_writer::tag('strong', get_string('program', 'gradereport_transcript') . ': ') . $program->name);
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
        echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientname', 'gradereport_transcript') . ': ') . $request->recipientname);
        if (!empty($request->recipientemail)) {
            echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientemail', 'gradereport_transcript') . ': ') . $request->recipientemail);
        }
        if (!empty($request->recipientaddress)) {
            echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientaddress', 'gradereport_transcript') . ': ') . nl2br(s($request->recipientaddress)));
        }
        if (!empty($request->recipientphone)) {
            echo html_writer::tag('li', html_writer::tag('strong', get_string('recipientphone', 'gradereport_transcript') . ': ') . $request->recipientphone);
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
                approve_request($requestid);
                redirect(
                    $PAGE->url,
                    get_string('requestapproved', 'gradereport_transcript'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
                break;

            case 'reject':
                reject_request($requestid);
                redirect(
                    $PAGE->url,
                    get_string('requestrejected', 'gradereport_transcript'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
                break;

            case 'markpaid':
                mark_paid($requestid);
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
            'programname' => $program->name,
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
display_filters();

// Display requests table.
display_requests_table();

echo $OUTPUT->footer();

/**
 * Display filter options
 */
function display_filters() {
    global $PAGE, $OUTPUT;

    $status = optional_param('status', 'all', PARAM_ALPHA);
    $requesttype = optional_param('requesttype', 'all', PARAM_ALPHA);
    $paymentstatus = optional_param('paymentstatus', 'all', PARAM_ALPHA);
    $deliverystatus = optional_param('deliverystatus', 'all', PARAM_ALPHA);

    echo $OUTPUT->heading(get_string('filters', 'gradereport_transcript'), 3);

    echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring()]);
    echo html_writer::start_div('form-inline mb-3');

    // Status filter.
    echo html_writer::start_div('form-group mr-3');
    echo html_writer::label(get_string('status', 'gradereport_transcript'), 'status', false, ['class' => 'mr-2']);
    $statusoptions = [
        'all' => get_string('all'),
        'pending' => get_string('statuspending', 'gradereport_transcript'),
        'approved' => get_string('statusapproved', 'gradereport_transcript'),
        'rejected' => get_string('statusrejected', 'gradereport_transcript')
    ];
    echo html_writer::select($statusoptions, 'status', $status, false, ['class' => 'form-control', 'id' => 'status']);
    echo html_writer::end_div();

    // Request type filter.
    echo html_writer::start_div('form-group mr-3');
    echo html_writer::label(get_string('requesttype', 'gradereport_transcript'), 'requesttype', false, ['class' => 'mr-2']);
    $typeoptions = [
        'all' => get_string('all'),
        'official' => get_string('transcriptofficial', 'gradereport_transcript'),
        'unofficial' => get_string('transcriptunofficial', 'gradereport_transcript')
    ];
    echo html_writer::select($typeoptions, 'requesttype', $requesttype, false, ['class' => 'form-control', 'id' => 'requesttype']);
    echo html_writer::end_div();

    // Payment status filter.
    echo html_writer::start_div('form-group mr-3');
    echo html_writer::label(get_string('paymentstatus', 'gradereport_transcript'), 'paymentstatus', false, ['class' => 'mr-2']);
    $paymentoptions = [
        'all' => get_string('all'),
        'pending' => get_string('paymentpending', 'gradereport_transcript'),
        'paid' => get_string('paymentpaid', 'gradereport_transcript'),
        'free' => get_string('paymentfree', 'gradereport_transcript')
    ];
    echo html_writer::select($paymentoptions, 'paymentstatus', $paymentstatus, false, ['class' => 'form-control', 'id' => 'paymentstatus']);
    echo html_writer::end_div();

    // Delivery status filter.
    echo html_writer::start_div('form-group mr-3');
    echo html_writer::label(get_string('deliverystatus', 'gradereport_transcript'), 'deliverystatus', false, ['class' => 'mr-2']);
    $deliveryoptions = [
        'all' => get_string('all'),
        'pending' => get_string('deliverypending', 'gradereport_transcript'),
        'sent' => get_string('deliverysent', 'gradereport_transcript'),
        'delivered' => get_string('deliverydelivered', 'gradereport_transcript'),
        'pickedup' => get_string('deliverypickedup', 'gradereport_transcript')
    ];
    echo html_writer::select($deliveryoptions, 'deliverystatus', $deliverystatus, false, ['class' => 'form-control', 'id' => 'deliverystatus']);
    echo html_writer::end_div();

    // Submit button.
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('filter', 'gradereport_transcript'),
        'class' => 'btn btn-primary'
    ]);

    echo html_writer::end_div();
    echo html_writer::end_tag('form');
}

/**
 * Display requests table
 */
function display_requests_table() {
    global $DB, $OUTPUT, $PAGE;

    // Get filter parameters.
    $status = optional_param('status', 'all', PARAM_ALPHA);
    $requesttype = optional_param('requesttype', 'all', PARAM_ALPHA);
    $paymentstatus = optional_param('paymentstatus', 'all', PARAM_ALPHA);
    $deliverystatus = optional_param('deliverystatus', 'all', PARAM_ALPHA);

    // Build query.
    $conditions = [];
    $params = [];

    if ($status !== 'all') {
        $conditions[] = 'r.status = :status';
        $params['status'] = $status;
    }

    if ($requesttype !== 'all') {
        $conditions[] = 'r.requesttype = :requesttype';
        $params['requesttype'] = $requesttype;
    }

    if ($paymentstatus !== 'all') {
        $conditions[] = 'r.paymentstatus = :paymentstatus';
        $params['paymentstatus'] = $paymentstatus;
    }

    if ($deliverystatus !== 'all') {
        $conditions[] = 'r.deliverystatus = :deliverystatus';
        $params['deliverystatus'] = $deliverystatus;
    }

    $where = empty($conditions) ? '1=1' : implode(' AND ', $conditions);

    $sql = "SELECT r.*, u.firstname, u.lastname, u.email, p.name as programname
            FROM {gradereport_transcript_requests} r
            JOIN {user} u ON u.id = r.userid
            JOIN {gradereport_transcript_programs} p ON p.id = r.programid
            WHERE $where
            ORDER BY r.timecreated DESC";

    $requests = $DB->get_records_sql($sql, $params);

    if (empty($requests)) {
        echo $OUTPUT->notification(get_string('norequests', 'gradereport_transcript'), \core\output\notification::NOTIFY_INFO);
        return;
    }

    echo $OUTPUT->heading(get_string('requests', 'gradereport_transcript'), 3);

    $table = new html_table();
    $table->head = [
        get_string('requestdate', 'gradereport_transcript'),
        get_string('student', 'gradereport_transcript'),
        get_string('program', 'gradereport_transcript'),
        get_string('type', 'gradereport_transcript'),
        get_string('status', 'gradereport_transcript'),
        get_string('price', 'gradereport_transcript'),
        get_string('paymentstatus', 'gradereport_transcript'),
        get_string('deliverystatus', 'gradereport_transcript'),
        get_string('recipient', 'gradereport_transcript'),
        get_string('actions', 'gradereport_transcript')
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($requests as $request) {
        $row = [];

        // Request date (MM/DD/YYYY format).
        $row[] = userdate($request->timecreated, '%m/%d/%Y');

        // Student name.
        $row[] = fullname($request);

        // Program name.
        $row[] = $request->programname;

        // Request type.
        $row[] = get_string('transcript' . $request->requesttype, 'gradereport_transcript');

        // Status.
        $statusclass = 'badge badge-' . ($request->status === 'approved' ? 'success' :
            ($request->status === 'rejected' ? 'danger' : 'warning'));
        $row[] = html_writer::tag('span', get_string('status' . $request->status, 'gradereport_transcript'),
            ['class' => $statusclass]);

        // Price.
        $row[] = '$' . format_float($request->price, 2);

        // Payment status.
        $paymentclass = 'badge badge-' . ($request->paymentstatus === 'paid' ? 'success' :
            ($request->paymentstatus === 'free' ? 'info' : 'warning'));
        $row[] = html_writer::tag('span', get_string('payment' . $request->paymentstatus, 'gradereport_transcript'),
            ['class' => $paymentclass]);

        // Delivery status.
        $deliveryclass = 'badge badge-' . ($request->deliverystatus === 'delivered' || $request->deliverystatus === 'pickedup' ? 'success' :
            ($request->deliverystatus === 'sent' ? 'info' : 'warning'));
        $row[] = html_writer::tag('span', get_string('delivery' . $request->deliverystatus, 'gradereport_transcript'),
            ['class' => $deliveryclass]);

        // Recipient information.
        $recipient = [];
        if (!empty($request->recipientname)) {
            $recipient[] = html_writer::tag('strong', $request->recipientname);
        }
        if (!empty($request->recipientaddress)) {
            $recipient[] = nl2br(s($request->recipientaddress));
        }
        if (!empty($request->recipientphone)) {
            $recipient[] = 'Phone: ' . s($request->recipientphone);
        }
        if (!empty($request->recipientemail)) {
            $recipient[] = 'Email: ' . s($request->recipientemail);
        }
        $row[] = empty($recipient) ? '-' : implode('<br>', $recipient);

        // Actions.
        $actions = [];

        // Always show Details button.
        $detailsurl = new moodle_url($PAGE->url, [
            'action' => 'details',
            'requestid' => $request->id
        ]);
        $actions[] = html_writer::link($detailsurl, get_string('details', 'gradereport_transcript'),
            ['class' => 'btn btn-sm btn-info']);

        if ($request->status === 'pending') {
            $approveurl = new moodle_url($PAGE->url, [
                'action' => 'approve',
                'requestid' => $request->id,
                'sesskey' => sesskey()
            ]);
            $actions[] = html_writer::link($approveurl, get_string('approve', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-success']);

            $rejecturl = new moodle_url($PAGE->url, [
                'action' => 'reject',
                'requestid' => $request->id,
                'sesskey' => sesskey()
            ]);
            $actions[] = html_writer::link($rejecturl, get_string('reject', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-danger']);
        }

        if ($request->status === 'approved' && $request->paymentstatus === 'pending') {
            $markpaidurl = new moodle_url($PAGE->url, [
                'action' => 'markpaid',
                'requestid' => $request->id,
                'sesskey' => sesskey()
            ]);
            $actions[] = html_writer::link($markpaidurl, get_string('markpaid', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-primary']);
        }

        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

/**
 * Approve a transcript request
 *
 * @param int $requestid Request ID
 */
function approve_request($requestid) {
    global $DB, $USER;

    $request = new stdClass();
    $request->id = $requestid;
    $request->status = 'approved';
    $request->approvedby = $USER->id;
    $request->approvaldate = time();
    $request->timemodified = time();

    $DB->update_record('gradereport_transcript_requests', $request);

    // Send notification to student.
    send_student_notification($requestid, 'approved');
}

/**
 * Reject a transcript request
 *
 * @param int $requestid Request ID
 */
function reject_request($requestid) {
    global $DB, $USER;

    $request = new stdClass();
    $request->id = $requestid;
    $request->status = 'rejected';
    $request->approvedby = $USER->id;
    $request->approvaldate = time();
    $request->timemodified = time();

    $DB->update_record('gradereport_transcript_requests', $request);

    // Send notification to student.
    send_student_notification($requestid, 'rejected');
}

/**
 * Mark request as paid
 *
 * @param int $requestid Request ID
 */
function mark_paid($requestid) {
    global $DB;

    $request = new stdClass();
    $request->id = $requestid;
    $request->paymentstatus = 'paid';
    $request->paiddate = time();
    $request->timemodified = time();

    $DB->update_record('gradereport_transcript_requests', $request);

    // Send notification to student.
    send_student_notification($requestid, 'paid');
}

/**
 * Send notification to student about request status change
 *
 * @param int $requestid Request ID
 * @param string $status New status
 */
function send_student_notification($requestid, $status) {
    global $DB, $CFG;

    $request = $DB->get_record('gradereport_transcript_requests', ['id' => $requestid], '*', MUST_EXIST);
    $student = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);
    $program = $DB->get_record('gradereport_transcript_programs', ['id' => $request->programid]);

    $subject = get_string('requeststatus' . $status . 'subject', 'gradereport_transcript');

    $message = get_string('requeststatus' . $status . 'body', 'gradereport_transcript', [
        'studentname' => fullname($student),
        'programname' => $program->name,
        'requesttype' => $request->requesttype
    ]);

    email_to_user($student, core_user::get_noreply_user(), $subject, $message);
}
