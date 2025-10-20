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
$programs = get_user_programs($USER->id);

// Prepare custom data for form.
$customdata = [
    'programs' => $programs,
    'pricing' => get_pricing_information($USER->id, $programid),
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
    $result = process_transcript_request($data);

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
display_user_requests($USER->id);

// Display form.
$mform->display();

echo $OUTPUT->footer();

/**
 * Get programs available to user based on enrollment
 *
 * @param int $userid User ID
 * @return array Array of program IDs and names
 */
function get_user_programs($userid) {
    global $DB;

    // Get all enrolled categories for user.
    $sql = "SELECT DISTINCT p.id, p.name
            FROM {gradereport_transcript_programs} p
            JOIN {course_categories} cc ON p.categoryid = cc.id
            JOIN {course} c ON c.category = cc.id
            JOIN {user_enrolments} ue ON ue.userid = :userid
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = c.id
            WHERE ue.status = 0
            ORDER BY p.name";

    $records = $DB->get_records_sql($sql, ['userid' => $userid]);

    $programs = [];
    foreach ($records as $record) {
        $programs[$record->id] = $record->name;
    }

    return $programs;
}

/**
 * Get pricing information for display
 *
 * @param int $userid User ID
 * @param int $programid Program ID (optional)
 * @return string HTML pricing information
 */
function get_pricing_information($userid, $programid = 0) {
    global $DB;

    // Get school pricing configuration.
    if ($programid > 0) {
        // Get pricing for specific program's school.
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid]);
        if ($program) {
            $pricing = $DB->get_record('gradereport_transcript_pricing', ['schoolid' => $program->schoolid]);
        } else {
            $pricing = false;
        }
    } else {
        // Get first available pricing.
        $pricing = $DB->get_record_sql("SELECT * FROM {gradereport_transcript_pricing} LIMIT 1");
    }

    if (!$pricing) {
        return '<div class="alert alert-danger">' . get_string('pricingnotconfigured', 'gradereport_transcript') . '</div>';
    }

    // Count official transcript requests for this user.
    $officialcount = $DB->count_records('gradereport_transcript_requests', [
        'userid' => $userid,
        'requesttype' => 'official'
    ]);

    $html = '<div class="pricing-info alert alert-info">';

    if ($pricing->firstfree && $officialcount == 0) {
        $html .= '<p><strong>' . get_string('freefirstofficial', 'gradereport_transcript') . '</strong></p>';
        if ($pricing->officialprice > 0) {
            $html .= '<p>' . get_string('subsequentprice', 'gradereport_transcript',
                '$' . format_float($pricing->officialprice, 2)) . '</p>';
        }
    } else {
        if ($pricing->officialprice > 0) {
            $html .= '<p>' . get_string('officialpriceis', 'gradereport_transcript',
                '$' . format_float($pricing->officialprice, 2)) . '</p>';
        }
    }

    if ($pricing->unofficialprice > 0) {
        $html .= '<p>' . get_string('unofficialpriceis', 'gradereport_transcript',
            '$' . format_float($pricing->unofficialprice, 2)) . '</p>';
    } else {
        $html .= '<p>' . get_string('unofficialfree', 'gradereport_transcript') . '</p>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Process transcript request submission
 *
 * @param object $data Form data
 * @return array Result array with success/error
 */
function process_transcript_request($data) {
    global $DB, $USER;

    try {
        // Get program details.
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $data->programid], '*', MUST_EXIST);

        // Get pricing configuration.
        $pricing = $DB->get_record('gradereport_transcript_pricing', ['schoolid' => $program->schoolid]);

        // Calculate price.
        $price = 0.00;
        $paymentstatus = 'pending';

        if ($data->requesttype === 'official') {
            // Count previous official requests.
            $officialcount = $DB->count_records('gradereport_transcript_requests', [
                'userid' => $USER->id,
                'requesttype' => 'official'
            ]);

            // Apply "first free" logic.
            if ($pricing && $pricing->firstfree && $officialcount == 0) {
                $price = 0.00;
                $paymentstatus = 'free';
            } else {
                $price = $pricing ? $pricing->officialprice : 0.00;
            }
        } else {
            // Unofficial transcript.
            $price = $pricing ? $pricing->unofficialprice : 0.00;
            if ($price == 0.00) {
                $paymentstatus = 'free';
            }
        }

        // Create request record.
        $request = new stdClass();
        $request->userid = $USER->id;
        $request->programid = $data->programid;
        $request->requesttype = $data->requesttype;
        $request->status = 'pending';
        $request->deliverymethod = $data->deliverymethod;
        $request->recipientemail = $data->recipientemail ?? null;
        $request->recipientaddress = $data->recipientaddress ?? null;
        $request->recipientname = $data->recipientname ?? null;
        $request->recipientphone = $data->recipientphone ?? null;
        $request->notes = $data->notes ?? null;
        $request->price = $price;
        $request->paymentstatus = $paymentstatus;
        $request->paymentmethod = null;
        $request->invoicenumber = null;
        $request->invoicedate = null;
        $request->paiddate = null;
        $request->approvedby = null;
        $request->approvaldate = null;
        $request->timecreated = time();
        $request->timemodified = time();

        $requestid = $DB->insert_record('gradereport_transcript_requests', $request);

        // Send notification email to admin.
        send_admin_notification($requestid, $request);

        return ['success' => true, 'requestid' => $requestid];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send email notification to admin about new request
 *
 * @param int $requestid Request ID
 * @param object $request Request object
 */
function send_admin_notification($requestid, $request) {
    global $DB, $CFG;

    // Get user details.
    $user = $DB->get_record('user', ['id' => $request->userid]);
    $program = $DB->get_record('gradereport_transcript_programs', ['id' => $request->programid]);

    // Get admin users with transcript management capability.
    $admins = get_users_by_capability(
        context_system::instance(),
        'gradereport/transcript:manage',
        'id, email, firstname, lastname'
    );

    foreach ($admins as $admin) {
        $subject = get_string('newrequestsubject', 'gradereport_transcript');

        $message = get_string('newrequestbody', 'gradereport_transcript', [
            'studentname' => fullname($user),
            'programname' => $program->name,
            'requesttype' => $request->requesttype,
            'price' => format_float($request->price, 2),
            'url' => $CFG->wwwroot . '/grade/report/transcript/manage_requests.php'
        ]);

        email_to_user($admin, $user, $subject, $message);
    }
}

/**
 * Display user's existing requests
 *
 * @param int $userid User ID
 */
function display_user_requests($userid) {
    global $DB, $OUTPUT;

    $requests = $DB->get_records('gradereport_transcript_requests',
        ['userid' => $userid],
        'timecreated DESC',
        '*',
        0,
        10
    );

    if (empty($requests)) {
        return;
    }

    echo $OUTPUT->heading(get_string('yourrequests', 'gradereport_transcript'), 3);

    $table = new html_table();
    $table->head = [
        get_string('requestdate', 'gradereport_transcript'),
        get_string('program', 'gradereport_transcript'),
        get_string('type', 'gradereport_transcript'),
        get_string('status', 'gradereport_transcript'),
        get_string('price', 'gradereport_transcript'),
        get_string('paymentstatus', 'gradereport_transcript')
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($requests as $request) {
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $request->programid]);

        $row = [];
        $row[] = userdate($request->timecreated, '%m/%d/%Y');
        $row[] = $program ? $program->name : '-';
        $row[] = get_string('transcript' . $request->requesttype, 'gradereport_transcript');
        $row[] = get_string('status' . $request->status, 'gradereport_transcript');
        $row[] = '$' . format_float($request->price, 2);
        $row[] = get_string('payment' . $request->paymentstatus, 'gradereport_transcript');

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}
