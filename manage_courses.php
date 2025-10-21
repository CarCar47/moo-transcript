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
 * Course mapping page for transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/ddllib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('gradereporttranscriptcourses');

$programid = optional_param('programid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]));
$PAGE->set_title(get_string('managecourses', 'gradereport_transcript'));
$PAGE->set_heading(get_string('managecourses', 'gradereport_transcript'));

// Handle form submission.
if ($action === 'save' && $programid) {
    require_sesskey();

    $program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid],
        'id, schoolid, categoryid, name, type, pdftemplate, gradescaleid, timecreated, timemodified', MUST_EXIST);

    // Get all courses from the form.
    $courseids = optional_param_array('courseid', [], PARAM_INT);
    $theoryhours = optional_param_array('theoryhours', [], PARAM_FLOAT);
    $labhours = optional_param_array('labhours', [], PARAM_FLOAT);
    $clinicalhours = optional_param_array('clinicalhours', [], PARAM_FLOAT);
    $credits = optional_param_array('credits', [], PARAM_FLOAT);
    $ceuvalues = optional_param_array('ceuvalue', [], PARAM_FLOAT);
    $sortorders = optional_param_array('sortorder', [], PARAM_INT);

    $time = time();

    foreach ($courseids as $index => $courseid) {
        if (empty($courseid)) {
            continue;
        }

        // Check if mapping already exists.
        $existing = $DB->get_record('gradereport_transcript_courses', [
            'programid' => $programid,
            'courseid' => $courseid,
        ], 'id, programid, courseid, sortorder, theoryhours, labhours, credits, ceuvalue, timecreated, timemodified');

        // Add defensive check for clinicalhours.
        if ($existing && !property_exists($existing, 'clinicalhours')) {
            $existing->clinicalhours = 0;
        }

        $record = new stdClass();
        $record->programid = $programid;
        $record->courseid = $courseid;
        $record->sortorder = $sortorders[$index] ?? 0;
        $record->theoryhours = $theoryhours[$index] ?? 0;
        $record->labhours = $labhours[$index] ?? 0;
        $record->clinicalhours = $clinicalhours[$index] ?? 0;
        $record->credits = $credits[$index] ?? 0;
        $record->ceuvalue = $ceuvalues[$index] ?? 0;
        $record->timemodified = $time;

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('gradereport_transcript_courses', $record);
        } else {
            $record->timecreated = $time;
            $DB->insert_record('gradereport_transcript_courses', $record);
        }
    }

    redirect(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]),
        get_string('coursemappingssaved', 'gradereport_transcript'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managecourses', 'gradereport_transcript'));

// Program selection form.
$programs = $DB->get_records_menu('gradereport_transcript_programs', null, 'name ASC', 'id, name');
// Apply format_string to all program names for XSS protection
$programs = array_map('format_string', $programs);

if (empty($programs)) {
    echo $OUTPUT->notification(get_string('noprogramsavailable', 'gradereport_transcript'),
        \core\output\notification::NOTIFY_WARNING);
    echo html_writer::tag('p', get_string('mustcreateprogram', 'gradereport_transcript'));
    echo $OUTPUT->footer();
    exit;
}

// Program selector.
echo html_writer::start_tag('form', ['method' => 'get', 'action' => 'manage_courses.php', 'class' => 'mb-3']);
echo html_writer::label(get_string('selectprogram', 'gradereport_transcript'), 'programid-select', true, ['class' => 'me-2']);

$programs = [0 => get_string('selectprogram', 'gradereport_transcript')] + $programs;
echo html_writer::select($programs, 'programid', $programid, false, ['id' => 'programid-select', 'class' => 'custom-select']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('loadcourses', 'gradereport_transcript'), 'class' => 'btn btn-primary ms-2']);
echo html_writer::end_tag('form');

// If program selected, show course mapping table.
if ($programid) {
    // Build column list - include hour labels if columns exist (defensive for pre-upgrade).
    $dbman = $DB->get_manager();
    $table = new xmldb_table('gradereport_transcript_programs');

    $columns = 'id, schoolid, categoryid, name, type, pdftemplate, gradescaleid, timecreated, timemodified';

    // Add hour label columns if they exist (after v1.2.1 database upgrade).
    if ($dbman->field_exists($table, new xmldb_field('hour1label'))) {
        $columns .= ', hour1label, hour2label, hour3label';
    }

    $program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid], $columns, MUST_EXIST);

    // Add default hour labels if columns don't exist yet (pre-upgrade fallback).
    if (!property_exists($program, 'hour1label')) {
        $program->hour1label = 'Theory Hours';
    }
    if (!property_exists($program, 'hour2label')) {
        $program->hour2label = 'Lab Hours';
    }
    if (!property_exists($program, 'hour3label')) {
        $program->hour3label = 'Clinical Hours';
    }

    echo $OUTPUT->heading(get_string('mappingcoursesfor', 'gradereport_transcript', format_string($program->name)), 3);
    echo html_writer::tag('p', get_string('coursemappinginstructions', 'gradereport_transcript'));

    // Get all courses from program's category (sorted by course code).
    $courses = $DB->get_records('course', ['category' => $program->categoryid], 'shortname ASC');

    // Remove site course.
    unset($courses[SITEID]);

    if (empty($courses)) {
        echo $OUTPUT->notification(get_string('nocoursesincategory', 'gradereport_transcript'),
            \core\output\notification::NOTIFY_INFO);
    } else {
        // Get existing mappings.
        $mappings = $DB->get_records_menu('gradereport_transcript_courses',
            ['programid' => $programid], '', 'courseid, id');

        $existingdata = [];
        if (!empty($mappings)) {
            list($sql, $params) = $DB->get_in_or_equal(array_values($mappings));
            $existingdata = $DB->get_records_select('gradereport_transcript_courses', "id $sql", $params, '',
                'courseid, id, programid, sortorder, theoryhours, labhours, credits, ceuvalue, timecreated, timemodified');

            // Add defensive check for clinicalhours for each record.
            foreach ($existingdata as $data) {
                if (!property_exists($data, 'clinicalhours')) {
                    $data->clinicalhours = 0;
                }
            }
        }

        // Build form.
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => 'manage_courses.php',
            'class' => 'course-mapping-form',
        ]);

        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'programid', 'value' => $programid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);

        // Build table.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable table-striped';

        // Table headers based on program type.
        $headers = [
            get_string('coursecode', 'gradereport_transcript'),
            get_string('coursename', 'gradereport_transcript'),
            get_string('sortorder', 'gradereport_transcript'),
        ];

        // Determine which hour columns to show (only non-empty labels).
        $showhourlabels = [];
        if ($program->type === 'hourbased') {
            // Use property_exists to handle missing columns gracefully.
            $hour1 = property_exists($program, 'hour1label') ? $program->hour1label : 'Theory Hours';
            $hour2 = property_exists($program, 'hour2label') ? $program->hour2label : 'Lab Hours';
            $hour3 = property_exists($program, 'hour3label') ? $program->hour3label : 'Clinical Hours';

            if (!empty(trim($hour1))) {
                $showhourlabels[] = ['column' => 1, 'label' => $hour1];
            }
            if (!empty(trim($hour2))) {
                $showhourlabels[] = ['column' => 2, 'label' => $hour2];
            }
            if (!empty(trim($hour3))) {
                $showhourlabels[] = ['column' => 3, 'label' => $hour3];
            }

            // Add hour column headers.
            foreach ($showhourlabels as $hourlabel) {
                $headers[] = $hourlabel['label'];
            }

            // Add total hours column if any hour columns are shown.
            if (!empty($showhourlabels)) {
                $headers[] = get_string('totalhours', 'gradereport_transcript');
            }
        } else if ($program->type === 'creditbased') {
            $headers[] = get_string('credits', 'gradereport_transcript');
        } else if ($program->type === 'ceu') {
            $headers[] = get_string('ceuvalue', 'gradereport_transcript');
        }

        $table->head = $headers;
        $table->data = [];

        $sortorder = 1;
        foreach ($courses as $course) {
            $existing = isset($existingdata[$course->id]) ? $existingdata[$course->id] : null;

            $row = [];

            // Course code.
            $row[] = html_writer::tag('strong', format_string($course->shortname));

            // Course name.
            $row[] = format_string($course->fullname);

            // Hidden course ID.
            $courseidfield = html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'courseid[]',
                'value' => $course->id,
            ]);

            // Sort order.
            $sortordervalue = $existing ? $existing->sortorder : $sortorder;
            $row[] = $courseidfield . html_writer::empty_tag('input', [
                'type' => 'number',
                'name' => 'sortorder[]',
                'value' => $sortordervalue,
                'min' => '0',
                'step' => '1',
                'class' => 'form-control',
                'style' => 'width: 80px;',
            ]);

            // Program-type specific fields.
            if ($program->type === 'hourbased') {
                // Get hour values for each column (handle missing columns gracefully).
                $hourvalues = [
                    1 => $existing ? ($existing->theoryhours ?? 0) : 0,
                    2 => $existing ? ($existing->labhours ?? 0) : 0,
                    3 => $existing && property_exists($existing, 'clinicalhours') ? $existing->clinicalhours : 0,
                ];

                $totalvalue = 0;

                // Add input fields for each visible hour column.
                foreach ($showhourlabels as $hourlabel) {
                    $column = $hourlabel['column'];
                    $value = $hourvalues[$column];
                    $totalvalue += $value;

                    $fieldname = match($column) {
                        1 => 'theoryhours',
                        2 => 'labhours',
                        3 => 'clinicalhours',
                    };

                    $row[] = html_writer::empty_tag('input', [
                        'type' => 'number',
                        'name' => $fieldname . '[]',
                        'value' => $value,
                        'min' => '0',
                        'step' => '0.5',
                        'class' => 'form-control',
                        'style' => 'width: 100px;',
                    ]);
                }

                // Add hidden fields for hour columns that are not shown (to preserve data).
                if (!in_array(1, array_column($showhourlabels, 'column'))) {
                    $row[count($row) - 1] .= html_writer::empty_tag('input', [
                        'type' => 'hidden',
                        'name' => 'theoryhours[]',
                        'value' => $hourvalues[1],
                    ]);
                }
                if (!in_array(2, array_column($showhourlabels, 'column'))) {
                    $row[count($row) - 1] .= html_writer::empty_tag('input', [
                        'type' => 'hidden',
                        'name' => 'labhours[]',
                        'value' => $hourvalues[2],
                    ]);
                }
                if (!in_array(3, array_column($showhourlabels, 'column'))) {
                    $row[count($row) - 1] .= html_writer::empty_tag('input', [
                        'type' => 'hidden',
                        'name' => 'clinicalhours[]',
                        'value' => $hourvalues[3],
                    ]);
                }

                // Total hours column.
                if (!empty($showhourlabels)) {
                    $row[] = html_writer::tag('span', number_format($totalvalue, 1), ['class' => 'total-hours']);
                }
            } else if ($program->type === 'creditbased') {
                $creditvalue = $existing ? $existing->credits : 0;
                $row[] = html_writer::empty_tag('input', [
                    'type' => 'number',
                    'name' => 'credits[]',
                    'value' => $creditvalue,
                    'min' => '0',
                    'step' => '0.5',
                    'class' => 'form-control',
                    'style' => 'width: 100px;',
                ]);
            } else if ($program->type === 'ceu') {
                $ceuvalue = $existing ? $existing->ceuvalue : 0;
                $row[] = html_writer::empty_tag('input', [
                    'type' => 'number',
                    'name' => 'ceuvalue[]',
                    'value' => $ceuvalue,
                    'min' => '0',
                    'step' => '0.1',
                    'class' => 'form-control',
                    'style' => 'width: 100px;',
                ]);
            }

            $table->data[] = $row;
            $sortorder++;
        }

        // Add Grand Total row.
        if ($program->type === 'hourbased' && !empty($showhourlabels)) {
            $grandtotalrow = [];
            $grandtotalrow[] = html_writer::tag('strong', get_string('grandtotal', 'gradereport_transcript'));
            $grandtotalrow[] = ''; // Empty course name column.
            $grandtotalrow[] = ''; // Empty sort order column.

            // Add total cells for each visible hour column.
            foreach ($showhourlabels as $hourlabel) {
                $column = $hourlabel['column'];
                $columnclass = match($column) {
                    1 => 'total-theory',
                    2 => 'total-lab',
                    3 => 'total-clinical',
                };
                $grandtotalrow[] = html_writer::tag('strong', '0.0', [
                    'class' => $columnclass,
                    'id' => $columnclass,
                ]);
            }

            // Grand total of all hours.
            $grandtotalrow[] = html_writer::tag('strong', '0.0', [
                'class' => 'grand-total-hours',
                'id' => 'grand-total-hours',
            ]);

            $table->data[] = $grandtotalrow;
        } else if ($program->type === 'creditbased') {
            $grandtotalrow = [];
            $grandtotalrow[] = html_writer::tag('strong', get_string('grandtotal', 'gradereport_transcript'));
            $grandtotalrow[] = ''; // Empty course name column.
            $grandtotalrow[] = ''; // Empty sort order column.
            $grandtotalrow[] = html_writer::tag('strong', '0.0', [
                'class' => 'total-credits',
                'id' => 'total-credits',
            ]);
            $table->data[] = $grandtotalrow;
        } else if ($program->type === 'ceu') {
            $grandtotalrow = [];
            $grandtotalrow[] = html_writer::tag('strong', get_string('grandtotal', 'gradereport_transcript'));
            $grandtotalrow[] = ''; // Empty course name column.
            $grandtotalrow[] = ''; // Empty sort order column.
            $grandtotalrow[] = html_writer::tag('strong', '0.0', [
                'class' => 'total-ceu',
                'id' => 'total-ceu',
            ]);
            $table->data[] = $grandtotalrow;
        }

        echo html_writer::table($table);

        // Save button.
        echo html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'submit',
                'value' => get_string('savemappings', 'gradereport_transcript'),
                'class' => 'btn btn-primary',
            ]),
            'mt-3'
        );

        echo html_writer::end_tag('form');

        // Add JavaScript for real-time total calculations.
        echo html_writer::start_tag('script');
        ?>
        (function() {
            'use strict';

            function updateTotals() {
                // Get all rows of inputs.
                var theoryInputs = document.querySelectorAll('input[name="theoryhours[]"]');
                var labInputs = document.querySelectorAll('input[name="labhours[]"]');
                var clinicalInputs = document.querySelectorAll('input[name="clinicalhours[]"]');
                var creditInputs = document.querySelectorAll('input[name="credits[]"]');
                var ceuInputs = document.querySelectorAll('input[name="ceuvalue[]"]');

                var rowTotals = document.querySelectorAll('.total-hours');

                var totalTheory = 0;
                var totalLab = 0;
                var totalClinical = 0;
                var totalCredits = 0;
                var totalCEU = 0;
                var grandTotal = 0;

                // Calculate row totals and column totals for hour-based programs.
                if (theoryInputs.length > 0 || labInputs.length > 0 || clinicalInputs.length > 0) {
                    for (var i = 0; i < rowTotals.length; i++) {
                        var theory = theoryInputs[i] ? parseFloat(theoryInputs[i].value) || 0 : 0;
                        var lab = labInputs[i] ? parseFloat(labInputs[i].value) || 0 : 0;
                        var clinical = clinicalInputs[i] ? parseFloat(clinicalInputs[i].value) || 0 : 0;

                        var rowTotal = theory + lab + clinical;
                        rowTotals[i].textContent = rowTotal.toFixed(1);

                        totalTheory += theory;
                        totalLab += lab;
                        totalClinical += clinical;
                        grandTotal += rowTotal;
                    }

                    // Update column totals in Grand Total row.
                    var totalTheoryEl = document.getElementById('total-theory');
                    var totalLabEl = document.getElementById('total-lab');
                    var totalClinicalEl = document.getElementById('total-clinical');
                    var grandTotalEl = document.getElementById('grand-total-hours');

                    if (totalTheoryEl) totalTheoryEl.textContent = totalTheory.toFixed(1);
                    if (totalLabEl) totalLabEl.textContent = totalLab.toFixed(1);
                    if (totalClinicalEl) totalClinicalEl.textContent = totalClinical.toFixed(1);
                    if (grandTotalEl) grandTotalEl.textContent = grandTotal.toFixed(1);
                }

                // Calculate total credits for credit-based programs.
                if (creditInputs.length > 0) {
                    for (var i = 0; i < creditInputs.length; i++) {
                        totalCredits += parseFloat(creditInputs[i].value) || 0;
                    }
                    var totalCreditsEl = document.getElementById('total-credits');
                    if (totalCreditsEl) totalCreditsEl.textContent = totalCredits.toFixed(1);
                }

                // Calculate total CEU for CEU programs.
                if (ceuInputs.length > 0) {
                    for (var i = 0; i < ceuInputs.length; i++) {
                        totalCEU += parseFloat(ceuInputs[i].value) || 0;
                    }
                    var totalCEUEl = document.getElementById('total-ceu');
                    if (totalCEUEl) totalCEUEl.textContent = totalCEU.toFixed(1);
                }
            }

            // Attach event listeners to all input fields.
            var allInputs = document.querySelectorAll('input[name="theoryhours[]"], input[name="labhours[]"], input[name="clinicalhours[]"], input[name="credits[]"], input[name="ceuvalue[]"]');
            allInputs.forEach(function(input) {
                input.addEventListener('input', updateTotals);
                input.addEventListener('change', updateTotals);
            });

            // Calculate totals on page load.
            updateTotals();
        })();
        <?php
        echo html_writer::end_tag('script');
    }
}

echo $OUTPUT->footer();
