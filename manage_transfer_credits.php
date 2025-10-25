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
 * Transfer credits management page
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

defined('MOODLE_INTERNAL') || die();

// Get parameters.
$programid = optional_param('programid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$transferid = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Validate required parameters.
if (empty($programid) || empty($userid)) {
    throw new moodle_exception('missingrequiredparams', 'gradereport_transcript');
}

// Load program and user.
$program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Require authentication and capability check.
require_login();
$systemcontext = context_system::instance();
require_capability('gradereport/transcript:manage', $systemcontext);

// Set page context and URL.
$PAGE->set_context($systemcontext);
$pageurl = new moodle_url('/grade/report/transcript/manage_transfer_credits.php', [
    'programid' => $programid,
    'userid' => $userid
]);
if ($action) {
    $pageurl->param('action', $action);
}
if ($transferid) {
    $pageurl->param('id', $transferid);
}
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('managetransfercredits', 'gradereport_transcript'));
$PAGE->set_heading(get_string('managetransfercredits', 'gradereport_transcript'));

$returnurl = new moodle_url('/grade/report/transcript/manage_transfer_credits.php', [
    'programid' => $programid,
    'userid' => $userid
]);

// Handle delete action.
if ($action === 'delete' && $transferid) {
    require_sesskey();

    // Validate ownership before any action.
    $transfer = $DB->get_record('gradereport_transcript_transfer',
        ['id' => $transferid, 'programid' => $programid, 'userid' => $userid],
        '*', MUST_EXIST);

    if ($confirm) {
        // Delete the transfer credit record.
        $DB->delete_records('gradereport_transcript_transfer', ['id' => $transferid]);

        redirect($returnurl, get_string('transfercreditdeleted', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletetransfercredit', 'gradereport_transcript'));

        echo $OUTPUT->confirm(
            get_string('deletetransfercreditconfirm', 'gradereport_transcript',
                s($transfer->coursecode) . ' - ' . s($transfer->coursename)),
            new moodle_url('/grade/report/transcript/manage_transfer_credits.php', [
                'action' => 'delete',
                'id' => $transferid,
                'programid' => $programid,
                'userid' => $userid,
                'confirm' => 1,
                'sesskey' => sesskey(),
            ]),
            $returnurl
        );

        echo $OUTPUT->footer();
        exit;
    }
}

// Handle add/edit actions.
if ($action === 'add' || $action === 'edit') {
    // Create form URL with action parameter.
    $formurl = new moodle_url('/grade/report/transcript/manage_transfer_credits.php', [
        'action' => $action,
        'programid' => $programid,
        'userid' => $userid
    ]);
    if ($transferid) {
        $formurl->param('id', $transferid);
    }

    // Pass custom data to form.
    $customdata = [
        'programid' => $programid,
        'userid' => $userid,
    ];
    $mform = new \gradereport_transcript\forms\transfer_credit_form($formurl, $customdata);

    // Form cancelled.
    if ($mform->is_cancelled()) {
        redirect($returnurl);
    }

    // Form submitted.
    $data = $mform->get_data();
    if ($data) {
        $data->timemodified = time();

        if ($action === 'edit' && $transferid) {
            // Update existing transfer credit.
            $data->id = $transferid;
            $DB->update_record('gradereport_transcript_transfer', $data);

            // Handle course equivalency mapping.
            $equivalentcourseid = isset($data->equivalentcourseid) ? $data->equivalentcourseid : 0;

            // Delete existing equivalency mapping for this transfer credit.
            $DB->delete_records('gradereport_transcript_equivalency', ['transferid' => $transferid]);

            // Create new equivalency mapping if a course was selected.
            if ($equivalentcourseid > 0) {
                $equivalency = new stdClass();
                $equivalency->transferid = $transferid;
                $equivalency->courseid = $equivalentcourseid;
                $equivalency->programid = $programid;
                $equivalency->userid = $userid;
                $equivalency->timecreated = time();
                $equivalency->timemodified = time();
                $DB->insert_record('gradereport_transcript_equivalency', $equivalency);
            }

            redirect($returnurl, get_string('transfercreditupdated', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Insert new transfer credit.
            $data->timecreated = time();
            $newtransferid = $DB->insert_record('gradereport_transcript_transfer', $data);

            // Handle course equivalency mapping.
            $equivalentcourseid = isset($data->equivalentcourseid) ? $data->equivalentcourseid : 0;

            // Create equivalency mapping if a course was selected.
            if ($equivalentcourseid > 0) {
                $equivalency = new stdClass();
                $equivalency->transferid = $newtransferid;
                $equivalency->courseid = $equivalentcourseid;
                $equivalency->programid = $programid;
                $equivalency->userid = $userid;
                $equivalency->timecreated = time();
                $equivalency->timemodified = time();
                $DB->insert_record('gradereport_transcript_equivalency', $equivalency);
            }

            redirect($returnurl, get_string('transfercreditadded', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    // Display form.
    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('managetransfercredits', 'gradereport_transcript') . ': ' .
        fullname($user) . ' - ' . format_string($program->name));

    if ($action === 'edit') {
        echo $OUTPUT->heading(get_string('edittransfercredit', 'gradereport_transcript'), 3);

        // Load existing transfer credit data with ownership validation.
        $transfer = $DB->get_record('gradereport_transcript_transfer',
            ['id' => $transferid, 'programid' => $programid, 'userid' => $userid],
            '*', MUST_EXIST);

        // Load equivalency mapping if exists.
        $equivalency = $DB->get_record('gradereport_transcript_equivalency',
            ['transferid' => $transferid], 'courseid');
        if ($equivalency) {
            $transfer->equivalentcourseid = $equivalency->courseid;
        } else {
            $transfer->equivalentcourseid = 0;
        }

        $mform->set_data($transfer);
    } else {
        echo $OUTPUT->heading(get_string('addtransfercredit', 'gradereport_transcript'), 3);
    }

    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Display transfer credits list (default view).
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('managetransfercredits', 'gradereport_transcript') . ': ' .
    fullname($user) . ' - ' . format_string($program->name));

// Back to student transcripts link.
$backurl = new moodle_url('/grade/report/transcript/view_student_transcripts.php', ['userid' => $userid]);
echo html_writer::div(
    html_writer::link($backurl, get_string('backtostudent', 'gradereport_transcript')),
    'mb-3'
);

// Add transfer credit button.
$addurl = new moodle_url('/grade/report/transcript/manage_transfer_credits.php', [
    'action' => 'add',
    'programid' => $programid,
    'userid' => $userid
]);
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addtransfercredit', 'gradereport_transcript'), 'get'),
    'mb-3'
);

// Get all transfer credits for this student and program.
$sql = "SELECT *
          FROM {gradereport_transcript_transfer}
         WHERE programid = ? AND userid = ?
      ORDER BY sortorder ASC, id ASC";

$transfers = $DB->get_records_sql($sql, [$programid, $userid]);

if (empty($transfers)) {
    echo html_writer::div(
        get_string('notransfercredits', 'gradereport_transcript'),
        'alert alert-info'
    );
} else {
    // Display table.
    $table = new html_table();

    // Build table headers based on program type.
    if ($program->type === 'creditbased') {
        $table->head = [
            get_string('coursecode', 'gradereport_transcript'),
            get_string('coursename', 'gradereport_transcript'),
            get_string('institution', 'gradereport_transcript'),
            get_string('grade', 'gradereport_transcript'),
            get_string('credits', 'gradereport_transcript'),
            get_string('transfersymbol', 'gradereport_transcript'),
            get_string('sortorder', 'gradereport_transcript'),
            get_string('actions', 'gradereport_transcript'),
        ];
    } else if ($program->type === 'hourbased') {
        $table->head = [
            get_string('coursecode', 'gradereport_transcript'),
            get_string('coursename', 'gradereport_transcript'),
            get_string('institution', 'gradereport_transcript'),
            get_string('grade', 'gradereport_transcript'),
            get_string('theoryhours', 'gradereport_transcript'),
            get_string('labhours', 'gradereport_transcript'),
            get_string('clinicalhours', 'gradereport_transcript'),
            get_string('totalhours', 'gradereport_transcript'),
            get_string('transfersymbol', 'gradereport_transcript'),
            get_string('sortorder', 'gradereport_transcript'),
            get_string('actions', 'gradereport_transcript'),
        ];
    } else {
        // CEU program type.
        $table->head = [
            get_string('coursecode', 'gradereport_transcript'),
            get_string('coursename', 'gradereport_transcript'),
            get_string('institution', 'gradereport_transcript'),
            get_string('grade', 'gradereport_transcript'),
            get_string('ceuvalue', 'gradereport_transcript'),
            get_string('transfersymbol', 'gradereport_transcript'),
            get_string('sortorder', 'gradereport_transcript'),
            get_string('actions', 'gradereport_transcript'),
        ];
    }

    $table->attributes['class'] = 'generaltable table table-striped';

    $totalcredits = 0;
    $totaltheory = 0;
    $totallab = 0;
    $totalclinical = 0;
    $totalhours = 0;
    $totalceu = 0;

    foreach ($transfers as $transfer) {
        $editurl = new moodle_url('/grade/report/transcript/manage_transfer_credits.php', [
            'action' => 'edit',
            'id' => $transfer->id,
            'programid' => $programid,
            'userid' => $userid
        ]);
        $deleteurl = new moodle_url('/grade/report/transcript/manage_transfer_credits.php', [
            'action' => 'delete',
            'id' => $transfer->id,
            'programid' => $programid,
            'userid' => $userid,
            'sesskey' => sesskey()
        ]);

        $actions = html_writer::link($editurl, get_string('edit')) . ' | ' .
                   html_writer::link($deleteurl, get_string('delete'));

        if ($program->type === 'creditbased') {
            $totalcredits += $transfer->credits;

            $table->data[] = [
                s($transfer->coursecode),
                s($transfer->coursename),
                s($transfer->institution),
                s($transfer->grade),
                number_format($transfer->credits, 2),
                s($transfer->transfersymbol),
                $transfer->sortorder,
                $actions,
            ];
        } else if ($program->type === 'hourbased') {
            // Add defensive defaults for hour fields.
            $theory = property_exists($transfer, 'theoryhours') ? $transfer->theoryhours : 0;
            $lab = property_exists($transfer, 'labhours') ? $transfer->labhours : 0;
            $clinical = property_exists($transfer, 'clinicalhours') ? $transfer->clinicalhours : 0;
            $total = property_exists($transfer, 'hours') ? $transfer->hours : ($theory + $lab + $clinical);

            $totaltheory += $theory;
            $totallab += $lab;
            $totalclinical += $clinical;
            $totalhours += $total;

            $table->data[] = [
                s($transfer->coursecode),
                s($transfer->coursename),
                s($transfer->institution),
                s($transfer->grade),
                number_format($theory, 1),
                number_format($lab, 1),
                number_format($clinical, 1),
                number_format($total, 1),
                s($transfer->transfersymbol),
                $transfer->sortorder,
                $actions,
            ];
        } else {
            // CEU type.
            $ceu = property_exists($transfer, 'ceuvalue') ? $transfer->ceuvalue : 0;
            $totalceu += $ceu;

            $table->data[] = [
                s($transfer->coursecode),
                s($transfer->coursename),
                s($transfer->institution),
                s($transfer->grade),
                number_format($ceu, 2),
                s($transfer->transfersymbol),
                $transfer->sortorder,
                $actions,
            ];
        }
    }

    // Add totals row based on program type.
    if ($program->type === 'creditbased') {
        $table->data[] = [
            '',
            html_writer::tag('strong', get_string('total', 'core')),
            '',
            '',
            html_writer::tag('strong', number_format($totalcredits, 2)),
            '',
            '',
            '',
        ];
    } else if ($program->type === 'hourbased') {
        $table->data[] = [
            '',
            html_writer::tag('strong', get_string('total', 'core')),
            '',
            '',
            html_writer::tag('strong', number_format($totaltheory, 1)),
            html_writer::tag('strong', number_format($totallab, 1)),
            html_writer::tag('strong', number_format($totalclinical, 1)),
            html_writer::tag('strong', number_format($totalhours, 1)),
            '',
            '',
            '',
        ];
    } else {
        // CEU type.
        $table->data[] = [
            '',
            html_writer::tag('strong', get_string('total', 'core')),
            '',
            '',
            html_writer::tag('strong', number_format($totalceu, 2)),
            '',
            '',
            '',
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
