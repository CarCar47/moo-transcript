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
 * Program management page for transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('gradereporttranscriptprograms');

$action = optional_param('action', '', PARAM_ALPHA);
$programid = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Set page URL with parameters.
$pageurl = new moodle_url('/grade/report/transcript/manage_programs.php');
if ($action) {
    $pageurl->param('action', $action);
}
if ($programid) {
    $pageurl->param('id', $programid);
}
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('manageprograms', 'gradereport_transcript'));
$PAGE->set_heading(get_string('manageprograms', 'gradereport_transcript'));

$returnurl = new moodle_url('/grade/report/transcript/manage_programs.php');

// Handle delete action.
if ($action === 'delete' && $programid) {
    require_sesskey();

    if ($confirm) {
        // Delete the program record.
        $DB->delete_records('gradereport_transcript_programs', ['id' => $programid]);

        // Delete associated PDF template files.
        $fs = get_file_storage();
        $fs->delete_area_files(
            context_system::instance()->id,
            'gradereport_transcript',
            'pdftemplate',
            $programid
        );

        // Redirect with success message.
        redirect($returnurl, get_string('programdeleted', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deleteprogram', 'gradereport_transcript'));

        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid],
            'id, schoolid, categoryid, name, type, pdftemplate, gradescaleid, timecreated, timemodified', MUST_EXIST);

        echo $OUTPUT->confirm(
            get_string('deleteprogramconfirm', 'gradereport_transcript', format_string($program->name)),
            new moodle_url('/grade/report/transcript/manage_programs.php', [
                'action' => 'delete',
                'id' => $programid,
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
    $formurl = new moodle_url('/grade/report/transcript/manage_programs.php', ['action' => $action]);
    if ($programid) {
        $formurl->param('id', $programid);
    }

    // Pass custom data to form.
    $customdata = ['action' => $action, 'id' => $programid];
    $mform = new \gradereport_transcript\forms\program_form($formurl, $customdata);

    // Form cancelled.
    if ($mform->is_cancelled()) {
        redirect($returnurl);
    }

    // Form submitted.
    $data = $mform->get_data();
    if ($data) {
        $data->timemodified = time();

        if ($action === 'edit' && $programid) {
            // Update existing program.
            $data->id = $programid;
            $DB->update_record('gradereport_transcript_programs', $data);

            // Handle PDF template file upload.
            file_save_draft_area_files(
                $data->pdftemplate,
                context_system::instance()->id,
                'gradereport_transcript',
                'pdftemplate',
                $programid,
                ['subdirs' => 0, 'maxfiles' => 1]
            );

            redirect($returnurl, get_string('programupdated', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Insert new program.
            $data->timecreated = time();
            $newid = $DB->insert_record('gradereport_transcript_programs', $data);

            // Handle PDF template file upload.
            file_save_draft_area_files(
                $data->pdftemplate,
                context_system::instance()->id,
                'gradereport_transcript',
                'pdftemplate',
                $newid,
                ['subdirs' => 0, 'maxfiles' => 1]
            );

            redirect($returnurl, get_string('programadded', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    // Display form.
    echo $OUTPUT->header();

    if ($action === 'edit') {
        echo $OUTPUT->heading(get_string('editprogram', 'gradereport_transcript'));

        // Load existing program data.
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid],
            'id, schoolid, categoryid, name, type, pdftemplate, gradescaleid, timecreated, timemodified', MUST_EXIST);

        // Prepare PDF template filemanager.
        $draftitemid = file_get_submitted_draft_itemid('pdftemplate');
        file_prepare_draft_area(
            $draftitemid,
            context_system::instance()->id,
            'gradereport_transcript',
            'pdftemplate',
            $programid,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $program->pdftemplate = $draftitemid;

        $mform->set_data($program);
    } else {
        echo $OUTPUT->heading(get_string('addprogram', 'gradereport_transcript'));
    }

    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Display program list (default view).
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageprograms', 'gradereport_transcript'));

// Add program button.
$addurl = new moodle_url('/grade/report/transcript/manage_programs.php', ['action' => 'add']);
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addprogram', 'gradereport_transcript'), 'get'),
    'mb-3'
);

// Get all programs with school names.
$sql = "SELECT p.*, s.name AS schoolname
          FROM {gradereport_transcript_programs} p
          JOIN {gradereport_transcript_schools} s ON s.id = p.schoolid
         ORDER BY s.name ASC, p.name ASC";
$programs = $DB->get_records_sql($sql);

if (empty($programs)) {
    echo $OUTPUT->notification(get_string('noprograms', 'gradereport_transcript'),
        \core\output\notification::NOTIFY_INFO);
} else {
    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('programname', 'gradereport_transcript'),
        get_string('school', 'gradereport_transcript'),
        get_string('category'),
        get_string('programtype', 'gradereport_transcript'),
        get_string('pdftemplate', 'gradereport_transcript'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'generaltable';
    $table->data = [];

    foreach ($programs as $program) {
        $editurl = new moodle_url('/grade/report/transcript/manage_programs.php', [
            'action' => 'edit',
            'id' => $program->id,
        ]);
        $deleteurl = new moodle_url('/grade/report/transcript/manage_programs.php', [
            'action' => 'delete',
            'id' => $program->id,
            'sesskey' => sesskey(),
        ]);

        $actions = html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-secondary']) . ' ' .
                   html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-sm btn-danger']);

        // Get category name.
        $category = \core_course_category::get($program->categoryid, IGNORE_MISSING);
        $categoryname = $category ? $category->get_formatted_name() : get_string('categorynotfound', 'gradereport_transcript');

        // Get program type display name.
        $typename = get_string($program->type, 'gradereport_transcript');

        // Check if PDF template exists.
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            context_system::instance()->id,
            'gradereport_transcript',
            'pdftemplate',
            $program->id,
            'filename',
            false
        );
        $pdfstatus = empty($files) ? get_string('notemplate', 'gradereport_transcript') : get_string('templateuploaded', 'gradereport_transcript');

        $table->data[] = [
            format_string($program->name),
            format_string($program->schoolname),
            $categoryname,
            $typename,
            $pdfstatus,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
