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
 * Symbols/notations management page
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('gradereporttranscriptsymbols');

$action = optional_param('action', '', PARAM_ALPHA);
$symbolid = optional_param('id', 0, PARAM_INT);
$schoolid = optional_param('schoolid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Set page URL with parameters.
$pageurl = new moodle_url('/grade/report/transcript/manage_symbols.php');
if ($action) {
    $pageurl->param('action', $action);
}
if ($symbolid) {
    $pageurl->param('id', $symbolid);
}
if ($schoolid) {
    $pageurl->param('schoolid', $schoolid);
}
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('managesymbols', 'gradereport_transcript'));
$PAGE->set_heading(get_string('managesymbols', 'gradereport_transcript'));

$returnurl = new moodle_url('/grade/report/transcript/manage_symbols.php', ['schoolid' => $schoolid]);

// Handle delete action.
if ($action === 'delete' && $symbolid) {
    require_sesskey();

    if ($confirm) {
        // Delete the symbol record.
        $DB->delete_records('gradereport_transcript_symbols', ['id' => $symbolid]);

        redirect($returnurl, get_string('symboldeleted', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletesymbol', 'gradereport_transcript'));

        $symbol = $DB->get_record('gradereport_transcript_symbols', ['id' => $symbolid], '*', MUST_EXIST);

        echo $OUTPUT->confirm(
            get_string('deletesymbolconfirm', 'gradereport_transcript', $symbol->symbol),
            new moodle_url('/grade/report/transcript/manage_symbols.php', [
                'action' => 'delete',
                'id' => $symbolid,
                'schoolid' => $schoolid,
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
    // Require schoolid for add/edit.
    if (!$schoolid && !$symbolid) {
        throw new moodle_exception('schoolidrequired', 'gradereport_transcript');
    }

    // If editing, load schoolid from existing record.
    if ($action === 'edit' && $symbolid) {
        $symbol = $DB->get_record('gradereport_transcript_symbols', ['id' => $symbolid], '*', MUST_EXIST);
        $schoolid = $symbol->schoolid;
    }

    // Create form URL with action parameter.
    $formurl = new moodle_url('/grade/report/transcript/manage_symbols.php', [
        'action' => $action,
        'schoolid' => $schoolid
    ]);
    if ($symbolid) {
        $formurl->param('id', $symbolid);
    }

    // Pass custom data to form.
    $customdata = ['schoolid' => $schoolid];
    $mform = new \gradereport_transcript\forms\symbol_form($formurl, $customdata);

    // Form cancelled.
    if ($mform->is_cancelled()) {
        redirect($returnurl);
    }

    // Form submitted.
    $data = $mform->get_data();
    if ($data) {
        $data->timemodified = time();

        if ($action === 'edit' && $symbolid) {
            // Update existing symbol.
            $data->id = $symbolid;
            $DB->update_record('gradereport_transcript_symbols', $data);

            redirect($returnurl, get_string('symbolupdated', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        } else {
            // Insert new symbol.
            $data->timecreated = time();
            $DB->insert_record('gradereport_transcript_symbols', $data);

            redirect($returnurl, get_string('symboladded', 'gradereport_transcript'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    // Display form.
    echo $OUTPUT->header();

    // Load school name.
    $school = $DB->get_record('gradereport_transcript_schools', ['id' => $schoolid], 'name', MUST_EXIST);
    echo $OUTPUT->heading(get_string('managesymbols', 'gradereport_transcript') . ': ' . format_string($school->name));

    if ($action === 'edit') {
        echo $OUTPUT->heading(get_string('editsymbol', 'gradereport_transcript'), 3);
        $mform->set_data($symbol);
    } else {
        echo $OUTPUT->heading(get_string('addsymbol', 'gradereport_transcript'), 3);
    }

    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Display symbols list (default view).
echo $OUTPUT->header();

// School selector dropdown.
$schools = $DB->get_records('gradereport_transcript_schools', null, 'name ASC');
if (empty($schools)) {
    echo html_writer::div(
        get_string('noschools', 'gradereport_transcript'),
        'alert alert-warning'
    );
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('selectschool', 'gradereport_transcript') . ': ', ['for' => 'schoolselector']);
echo html_writer::start_tag('select', ['id' => 'schoolselector', 'class' => 'custom-select']);
echo html_writer::tag('option', get_string('choosedots'), ['value' => '']);
foreach ($schools as $school) {
    $selected = ($school->id == $schoolid) ? ['selected' => 'selected'] : [];
    echo html_writer::tag('option', format_string($school->name), array_merge(['value' => $school->id], $selected));
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

// JavaScript for school selector.
$PAGE->requires->js_amd_inline("
    require(['jquery'], function($) {
        $('#schoolselector').change(function() {
            var schoolid = $(this).val();
            if (schoolid) {
                window.location.href = '" . (new moodle_url('/grade/report/transcript/manage_symbols.php'))->out(false) . "?schoolid=' + schoolid;
            }
        });
    });
");

if (!$schoolid) {
    echo $OUTPUT->footer();
    exit;
}

$school = $DB->get_record('gradereport_transcript_schools', ['id' => $schoolid], 'name', MUST_EXIST);
echo $OUTPUT->heading(get_string('managesymbols', 'gradereport_transcript') . ': ' . format_string($school->name));

// Add symbol button.
$addurl = new moodle_url('/grade/report/transcript/manage_symbols.php', [
    'action' => 'add',
    'schoolid' => $schoolid
]);
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addsymbol', 'gradereport_transcript'), 'get'),
    'mb-3'
);

// Get all symbols for this school.
$symbols = $DB->get_records('gradereport_transcript_symbols', ['schoolid' => $schoolid], 'sortorder ASC, id ASC');

if (empty($symbols)) {
    echo html_writer::div(
        get_string('nosymbols', 'gradereport_transcript'),
        'alert alert-info'
    );
} else {
    // Display table.
    $table = new html_table();
    $table->head = [
        get_string('symbol', 'gradereport_transcript'),
        get_string('meaning', 'gradereport_transcript'),
        get_string('sortorder', 'gradereport_transcript'),
        get_string('actions', 'gradereport_transcript'),
    ];
    $table->attributes['class'] = 'generaltable table table-striped';

    foreach ($symbols as $symbol) {
        $editurl = new moodle_url('/grade/report/transcript/manage_symbols.php', [
            'action' => 'edit',
            'id' => $symbol->id,
            'schoolid' => $schoolid
        ]);
        $deleteurl = new moodle_url('/grade/report/transcript/manage_symbols.php', [
            'action' => 'delete',
            'id' => $symbol->id,
            'schoolid' => $schoolid,
            'sesskey' => sesskey()
        ]);

        $actions = html_writer::link($editurl, get_string('edit')) . ' | ' .
                   html_writer::link($deleteurl, get_string('delete'));

        $table->data[] = [
            html_writer::tag('strong', s($symbol->symbol)),
            s($symbol->meaning),
            $symbol->sortorder,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
