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
 * Academic policies management page
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('gradereporttranscriptpolicies');

$schoolid = required_param('schoolid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Set page URL.
$pageurl = new moodle_url('/grade/report/transcript/manage_policies.php', ['schoolid' => $schoolid]);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('managepolicies', 'gradereport_transcript'));
$PAGE->set_heading(get_string('managepolicies', 'gradereport_transcript'));

// Verify school exists.
$school = $DB->get_record('gradereport_transcript_schools', ['id' => $schoolid], '*', MUST_EXIST);

// Default policy text.
$defaults = [
    'course_numbering' => 'Courses are identified by a program code followed by a course number (e.g., FSP-201). The program code represents the subject area or program of study, and the course number indicates the level and sequence of the course within that program.',
    'transfer_credit' => 'The acceptance and applicability of transfer credits and hours is subject to the sole discretion of the receiving institution. This institution makes no guarantee regarding the transferability of credits earned here to other institutions. Students are advised to consult with the receiving institution regarding their specific transfer credit policies before enrolling.',
];

// Handle form submission.
if ($action === 'save' && confirm_sesskey()) {
    $coursenumbering = required_param('course_numbering', PARAM_RAW);
    $transfercredit = required_param('transfer_credit', PARAM_RAW);

    $timenow = time();

    // Save or update course numbering policy.
    $existing = $DB->get_record('gradereport_transcript_policies',
        ['schoolid' => $schoolid, 'policytype' => 'course_numbering']);

    if ($coursenumbering !== $defaults['course_numbering']) {
        // Custom text - save to database.
        if ($existing) {
            $existing->content = $coursenumbering;
            $existing->timemodified = $timenow;
            $DB->update_record('gradereport_transcript_policies', $existing);
        } else {
            $record = (object)[
                'schoolid' => $schoolid,
                'policytype' => 'course_numbering',
                'content' => $coursenumbering,
                'timecreated' => $timenow,
                'timemodified' => $timenow,
            ];
            $DB->insert_record('gradereport_transcript_policies', $record);
        }
    } else if ($existing) {
        // Text matches default - delete custom record.
        $DB->delete_records('gradereport_transcript_policies', ['id' => $existing->id]);
    }

    // Save or update transfer credit policy.
    $existing = $DB->get_record('gradereport_transcript_policies',
        ['schoolid' => $schoolid, 'policytype' => 'transfer_credit']);

    if ($transfercredit !== $defaults['transfer_credit']) {
        // Custom text - save to database.
        if ($existing) {
            $existing->content = $transfercredit;
            $existing->timemodified = $timenow;
            $DB->update_record('gradereport_transcript_policies', $existing);
        } else {
            $record = (object)[
                'schoolid' => $schoolid,
                'policytype' => 'transfer_credit',
                'content' => $transfercredit,
                'timecreated' => $timenow,
                'timemodified' => $timenow,
            ];
            $DB->insert_record('gradereport_transcript_policies', $record);
        }
    } else if ($existing) {
        // Text matches default - delete custom record.
        $DB->delete_records('gradereport_transcript_policies', ['id' => $existing->id]);
    }

    redirect($pageurl, get_string('policiessaved', 'gradereport_transcript'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Handle reset to defaults.
if ($action === 'reset' && confirm_sesskey()) {
    $DB->delete_records('gradereport_transcript_policies', ['schoolid' => $schoolid]);
    redirect($pageurl, get_string('policiesreset', 'gradereport_transcript'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Load current policies (custom or default).
$coursenumbering = $DB->get_field('gradereport_transcript_policies', 'content',
    ['schoolid' => $schoolid, 'policytype' => 'course_numbering']);
if (empty($coursenumbering)) {
    $coursenumbering = $defaults['course_numbering'];
}

$transfercredit = $DB->get_field('gradereport_transcript_policies', 'content',
    ['schoolid' => $schoolid, 'policytype' => 'transfer_credit']);
if (empty($transfercredit)) {
    $transfercredit = $defaults['transfer_credit'];
}

// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managepolicies', 'gradereport_transcript') . ': ' . format_string($school->name));

// Instructions.
echo html_writer::tag('p', get_string('policiesdescription', 'gradereport_transcript'));

// Form.
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);

// Course Numbering System.
echo html_writer::tag('h3', get_string('coursenumberingsystem', 'gradereport_transcript'));
echo html_writer::tag('textarea', s($coursenumbering), [
    'name' => 'course_numbering',
    'rows' => 4,
    'cols' => 100,
    'style' => 'width: 100%; max-width: 800px;',
]);
echo html_writer::tag('p', '');

// Transfer Credit Policy.
echo html_writer::tag('h3', get_string('transfercreditpolicy', 'gradereport_transcript'));
echo html_writer::tag('textarea', s($transfercredit), [
    'name' => 'transfer_credit',
    'rows' => 6,
    'cols' => 100,
    'style' => 'width: 100%; max-width: 800px;',
]);
echo html_writer::tag('p', '');

// Buttons.
echo html_writer::start_tag('div', ['style' => 'margin-top: 20px;']);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('savechanges'),
    'class' => 'btn btn-primary',
]);
echo ' ';
echo html_writer::link(
    new moodle_url($pageurl, ['action' => 'reset', 'sesskey' => sesskey()]),
    get_string('resettodefaults', 'gradereport_transcript'),
    ['class' => 'btn btn-secondary', 'onclick' => 'return confirm("' . get_string('resetconfirm', 'gradereport_transcript') . '");']
);
echo ' ';
echo html_writer::link(
    new moodle_url('/grade/report/transcript/manage_schools.php'),
    get_string('cancel'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
