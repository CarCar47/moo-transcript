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
 * Helper functions for the transcript plugin
 *
 * This class contains static helper methods used across multiple pages
 * to avoid code duplication and follow Moodle best practices.
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for transcript plugin utility functions
 */
class helper {

    /**
     * Get pricing configuration for a school
     *
     * @param int $schoolid School ID
     * @return object|false Pricing record or false
     */
    public static function get_pricing($schoolid) {
        global $DB;
        return $DB->get_record('gradereport_transcript_pricing', ['schoolid' => $schoolid]);
    }

    /**
     * Display filter options for manage requests page
     */
    public static function display_request_filters() {
        global $PAGE, $OUTPUT;

        $status = optional_param('status', 'all', PARAM_ALPHA);
        $requesttype = optional_param('requesttype', 'all', PARAM_ALPHA);
        $paymentstatus = optional_param('paymentstatus', 'all', PARAM_ALPHA);
        $deliverystatus = optional_param('deliverystatus', 'all', PARAM_ALPHA);

        echo $OUTPUT->heading(get_string('filters', 'gradereport_transcript'), 3);

        echo \html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring()]);
        echo \html_writer::start_div('form-inline mb-3');

        // Status filter.
        echo \html_writer::start_div('form-group mr-3');
        echo \html_writer::label(get_string('status', 'gradereport_transcript'), 'status', false, ['class' => 'mr-2']);
        $statusoptions = [
            'all' => get_string('all'),
            'pending' => get_string('statuspending', 'gradereport_transcript'),
            'approved' => get_string('statusapproved', 'gradereport_transcript'),
            'rejected' => get_string('statusrejected', 'gradereport_transcript')
        ];
        echo \html_writer::select($statusoptions, 'status', $status, false, ['class' => 'form-control', 'id' => 'status']);
        echo \html_writer::end_div();

        // Request type filter.
        echo \html_writer::start_div('form-group mr-3');
        echo \html_writer::label(get_string('requesttype', 'gradereport_transcript'), 'requesttype', false, ['class' => 'mr-2']);
        $typeoptions = [
            'all' => get_string('all'),
            'official' => get_string('transcriptofficial', 'gradereport_transcript'),
            'unofficial' => get_string('transcriptunofficial', 'gradereport_transcript')
        ];
        echo \html_writer::select($typeoptions, 'requesttype', $requesttype, false, ['class' => 'form-control', 'id' => 'requesttype']);
        echo \html_writer::end_div();

        // Payment status filter.
        echo \html_writer::start_div('form-group mr-3');
        echo \html_writer::label(get_string('paymentstatus', 'gradereport_transcript'), 'paymentstatus', false, ['class' => 'mr-2']);
        $paymentoptions = [
            'all' => get_string('all'),
            'pending' => get_string('paymentpending', 'gradereport_transcript'),
            'paid' => get_string('paymentpaid', 'gradereport_transcript'),
            'free' => get_string('paymentfree', 'gradereport_transcript')
        ];
        echo \html_writer::select($paymentoptions, 'paymentstatus', $paymentstatus, false, ['class' => 'form-control', 'id' => 'paymentstatus']);
        echo \html_writer::end_div();

        // Delivery status filter.
        echo \html_writer::start_div('form-group mr-3');
        echo \html_writer::label(get_string('deliverystatus', 'gradereport_transcript'), 'deliverystatus', false, ['class' => 'mr-2']);
        $deliveryoptions = [
            'all' => get_string('all'),
            'pending' => get_string('deliverypending', 'gradereport_transcript'),
            'sent' => get_string('deliverysent', 'gradereport_transcript'),
            'delivered' => get_string('deliverydelivered', 'gradereport_transcript'),
            'pickedup' => get_string('deliverypickedup', 'gradereport_transcript')
        ];
        echo \html_writer::select($deliveryoptions, 'deliverystatus', $deliverystatus, false, ['class' => 'form-control', 'id' => 'deliverystatus']);
        echo \html_writer::end_div();

        // Submit button.
        echo \html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('filter', 'gradereport_transcript'),
            'class' => 'btn btn-primary'
        ]);

        echo \html_writer::end_div();
        echo \html_writer::end_tag('form');
    }

    /**
     * Display requests table for manage requests page
     */
    public static function display_requests_table() {
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

        $table = new \html_table();
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
            $row[] = format_string($request->programname);

            // Request type.
            $row[] = get_string('transcript' . $request->requesttype, 'gradereport_transcript');

            // Status.
            $statusclass = 'badge badge-' . ($request->status === 'approved' ? 'success' :
                ($request->status === 'rejected' ? 'danger' : 'warning'));
            $row[] = \html_writer::tag('span', get_string('status' . $request->status, 'gradereport_transcript'),
                ['class' => $statusclass]);

            // Price.
            $row[] = '$' . format_float($request->price, 2);

            // Payment status.
            $paymentclass = 'badge badge-' . ($request->paymentstatus === 'paid' ? 'success' :
                ($request->paymentstatus === 'free' ? 'info' : 'warning'));
            $row[] = \html_writer::tag('span', get_string('payment' . $request->paymentstatus, 'gradereport_transcript'),
                ['class' => $paymentclass]);

            // Delivery status.
            $deliveryclass = 'badge badge-' . ($request->deliverystatus === 'delivered' || $request->deliverystatus === 'pickedup' ? 'success' :
                ($request->deliverystatus === 'sent' ? 'info' : 'warning'));
            $row[] = \html_writer::tag('span', get_string('delivery' . $request->deliverystatus, 'gradereport_transcript'),
                ['class' => $deliveryclass]);

            // Recipient information.
            $recipient = [];
            if (!empty($request->recipientname)) {
                $recipient[] = \html_writer::tag('strong', s($request->recipientname));
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
            $detailsurl = new \moodle_url($PAGE->url, [
                'action' => 'details',
                'requestid' => $request->id
            ]);
            $actions[] = \html_writer::link($detailsurl, get_string('details', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-info']);

            if ($request->status === 'pending') {
                $approveurl = new \moodle_url($PAGE->url, [
                    'action' => 'approve',
                    'requestid' => $request->id,
                    'sesskey' => sesskey()
                ]);
                $actions[] = \html_writer::link($approveurl, get_string('approve', 'gradereport_transcript'),
                    ['class' => 'btn btn-sm btn-success']);

                $rejecturl = new \moodle_url($PAGE->url, [
                    'action' => 'reject',
                    'requestid' => $request->id,
                    'sesskey' => sesskey()
                ]);
                $actions[] = \html_writer::link($rejecturl, get_string('reject', 'gradereport_transcript'),
                    ['class' => 'btn btn-sm btn-danger']);
            }

            if ($request->status === 'approved' && $request->paymentstatus === 'pending') {
                $markpaidurl = new \moodle_url($PAGE->url, [
                    'action' => 'markpaid',
                    'requestid' => $request->id,
                    'sesskey' => sesskey()
                ]);
                $actions[] = \html_writer::link($markpaidurl, get_string('markpaid', 'gradereport_transcript'),
                    ['class' => 'btn btn-sm btn-primary']);
            }

            $row[] = implode(' ', $actions);

            $table->data[] = $row;
        }

        echo \html_writer::table($table);
    }

    /**
     * Approve a transcript request
     *
     * @param int $requestid Request ID
     */
    public static function approve_request($requestid) {
        global $DB, $USER;

        $request = new \stdClass();
        $request->id = $requestid;
        $request->status = 'approved';
        $request->approvedby = $USER->id;
        $request->approvaldate = time();
        $request->timemodified = time();

        $DB->update_record('gradereport_transcript_requests', $request);

        // Send notification to student.
        self::send_student_notification($requestid, 'approved');
    }

    /**
     * Reject a transcript request
     *
     * @param int $requestid Request ID
     */
    public static function reject_request($requestid) {
        global $DB, $USER;

        $request = new \stdClass();
        $request->id = $requestid;
        $request->status = 'rejected';
        $request->approvedby = $USER->id;
        $request->approvaldate = time();
        $request->timemodified = time();

        $DB->update_record('gradereport_transcript_requests', $request);

        // Send notification to student.
        self::send_student_notification($requestid, 'rejected');
    }

    /**
     * Mark request as paid
     *
     * @param int $requestid Request ID
     */
    public static function mark_paid($requestid) {
        global $DB;

        $request = new \stdClass();
        $request->id = $requestid;
        $request->paymentstatus = 'paid';
        $request->paiddate = time();
        $request->timemodified = time();

        $DB->update_record('gradereport_transcript_requests', $request);

        // Send notification to student.
        self::send_student_notification($requestid, 'paid');
    }

    /**
     * Send notification to student about request status change
     *
     * @param int $requestid Request ID
     * @param string $status New status
     */
    public static function send_student_notification($requestid, $status) {
        global $DB, $CFG;

        $request = $DB->get_record('gradereport_transcript_requests', ['id' => $requestid], '*', MUST_EXIST);
        $student = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $request->programid]);

        $subject = get_string('requeststatus' . $status . 'subject', 'gradereport_transcript');

        $message = get_string('requeststatus' . $status . 'body', 'gradereport_transcript', [
            'studentname' => fullname($student),
            'programname' => format_string($program->name),
            'requesttype' => $request->requesttype
        ]);

        email_to_user($student, \core_user::get_noreply_user(), $subject, $message);
    }

    /**
     * Get programs available to user based on enrollment
     *
     * @param int $userid User ID
     * @return array Array of program IDs and names
     */
    public static function get_user_programs($userid) {
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
            $programs[$record->id] = format_string($record->name);
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
    public static function get_pricing_information($userid, $programid = 0) {
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

        if ($pricing->firstfree && $officialcount === 0) {
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
    public static function process_transcript_request($data) {
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
                if ($pricing && $pricing->firstfree && $officialcount === 0) {
                    $price = 0.00;
                    $paymentstatus = 'free';
                } else {
                    $price = $pricing ? $pricing->officialprice : 0.00;
                }
            } else {
                // Unofficial transcript.
                $price = $pricing ? $pricing->unofficialprice : 0.00;
                if ($price === 0.00) {
                    $paymentstatus = 'free';
                }
            }

            // Create request record.
            $request = new \stdClass();
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
            self::send_admin_notification($requestid, $request);

            return ['success' => true, 'requestid' => $requestid];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email notification to admin about new request
     *
     * @param int $requestid Request ID
     * @param object $request Request object
     */
    public static function send_admin_notification($requestid, $request) {
        global $DB, $CFG;

        // Get user details.
        $user = $DB->get_record('user', ['id' => $request->userid]);
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $request->programid]);

        // Get admin users with transcript management capability.
        $admins = get_users_by_capability(
            \context_system::instance(),
            'gradereport/transcript:manage',
            'id, email, firstname, lastname'
        );

        foreach ($admins as $admin) {
            $subject = get_string('newrequestsubject', 'gradereport_transcript');

            $message = get_string('newrequestbody', 'gradereport_transcript', [
                'studentname' => fullname($user),
                'programname' => format_string($program->name),
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
    public static function display_user_requests($userid) {
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

        $table = new \html_table();
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
            $row[] = $program ? format_string($program->name) : '-';
            $row[] = get_string('transcript' . $request->requesttype, 'gradereport_transcript');
            $row[] = get_string('status' . $request->status, 'gradereport_transcript');
            $row[] = '$' . format_float($request->price, 2);
            $row[] = get_string('payment' . $request->paymentstatus, 'gradereport_transcript');

            $table->data[] = $row;
        }

        echo \html_writer::table($table);
    }
}
