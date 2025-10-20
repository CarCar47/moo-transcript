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
 * School management page for transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('gradereporttranscriptschools');

$action = optional_param('action', '', PARAM_ALPHA);
$schoolid = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Set page URL with parameters.
$pageurl = new moodle_url('/grade/report/transcript/manage_schools.php');
if ($action) {
    $pageurl->param('action', $action);
}
if ($schoolid) {
    $pageurl->param('id', $schoolid);
}
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('manageschools', 'gradereport_transcript'));
$PAGE->set_heading(get_string('manageschools', 'gradereport_transcript'));

$returnurl = new moodle_url('/grade/report/transcript/manage_schools.php');

// Handle delete action.
if ($action === 'delete' && $schoolid) {
    require_sesskey();

    if ($confirm) {
        // Delete the school record.
        $DB->delete_records('gradereport_transcript_schools', ['id' => $schoolid]);

        // Redirect with success message.
        redirect($returnurl, get_string('schooldeleted', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deleteschool', 'gradereport_transcript'));

        $school = $DB->get_record('gradereport_transcript_schools', ['id' => $schoolid], '*', MUST_EXIST);

        echo $OUTPUT->confirm(
            get_string('deleteschoolconfirm', 'gradereport_transcript', $school->name),
            new moodle_url('/grade/report/transcript/manage_schools.php', [
                'action' => 'delete',
                'id' => $schoolid,
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
    $formurl = new moodle_url('/grade/report/transcript/manage_schools.php', ['action' => $action]);
    if ($schoolid) {
        $formurl->param('id', $schoolid);
    }

    // Pass custom data to form.
    $customdata = ['action' => $action, 'id' => $schoolid];
    $mform = new \gradereport_transcript\forms\school_form($formurl, $customdata);

    // Form cancelled.
    if ($mform->is_cancelled()) {
        redirect($returnurl);
    }

    // Form submitted.
    $data = $mform->get_data();
    if ($data) {
        $data->timemodified = time();

        if ($action === 'edit' && $schoolid) {
            // Update existing school.
            $data->id = $schoolid;
            $DB->update_record('gradereport_transcript_schools', $data);

            // Handle logo file upload.
            file_save_draft_area_files(
                $data->logo,
                context_system::instance()->id,
                'gradereport_transcript',
                'schoollogo',
                $schoolid,
                ['subdirs' => 0, 'maxfiles' => 1]
            );

            redirect($returnurl, get_string('schoolupdated', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Insert new school.
            $data->timecreated = time();
            $newid = $DB->insert_record('gradereport_transcript_schools', $data);

            // Handle logo file upload.
            file_save_draft_area_files(
                $data->logo,
                context_system::instance()->id,
                'gradereport_transcript',
                'schoollogo',
                $newid,
                ['subdirs' => 0, 'maxfiles' => 1]
            );

            redirect($returnurl, get_string('schooladded', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    // Display form.
    echo $OUTPUT->header();

    if ($action === 'edit') {
        echo $OUTPUT->heading(get_string('editschool', 'gradereport_transcript'));

        // Load existing school data.
        $school = $DB->get_record('gradereport_transcript_schools', ['id' => $schoolid], '*', MUST_EXIST);

        // Prepare logo filemanager.
        $draftitemid = file_get_submitted_draft_itemid('logo');
        file_prepare_draft_area(
            $draftitemid,
            context_system::instance()->id,
            'gradereport_transcript',
            'schoollogo',
            $schoolid,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $school->logo = $draftitemid;

        $mform->set_data($school);
    } else {
        echo $OUTPUT->heading(get_string('addschool', 'gradereport_transcript'));
    }

    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Display school list (default view).
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageschools', 'gradereport_transcript'));

// Add school button.
$addurl = new moodle_url('/grade/report/transcript/manage_schools.php', ['action' => 'add']);
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addschool', 'gradereport_transcript'), 'get'),
    'mb-3'
);

// Get all schools.
$schools = $DB->get_records('gradereport_transcript_schools', null, 'name ASC');

if (empty($schools)) {
    echo $OUTPUT->notification(get_string('noschools', 'gradereport_transcript'),
        \core\output\notification::NOTIFY_INFO);
} else {
    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('schoolname', 'gradereport_transcript'),
        get_string('schooladdress', 'gradereport_transcript'),
        get_string('schoolphone', 'gradereport_transcript'),
        get_string('schoolwebsite', 'gradereport_transcript'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'generaltable';
    $table->data = [];

    foreach ($schools as $school) {
        $editurl = new moodle_url('/grade/report/transcript/manage_schools.php', [
            'action' => 'edit',
            'id' => $school->id,
        ]);
        $deleteurl = new moodle_url('/grade/report/transcript/manage_schools.php', [
            'action' => 'delete',
            'id' => $school->id,
            'sesskey' => sesskey(),
        ]);

        $actions = html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-secondary']) . ' ' .
                   html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-sm btn-danger']);

        // Format website as clickable link if provided.
        $website = !empty($school->website)
            ? html_writer::link(s($school->website), s($school->website),
                ['target' => '_blank', 'rel' => 'noopener noreferrer'])
            : '';

        $table->data[] = [
            format_string($school->name),
            format_text($school->address, FORMAT_PLAIN),
            format_string($school->phone),
            $website,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
