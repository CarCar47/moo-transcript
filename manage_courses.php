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

/**
 * v1.0.33: Extract course code from category fullname
 *
 * Assumes format: "CODE Course Title" (e.g., "MT100 Florida Laws and Rules")
 *
 * @param string $fullname Category fullname
 * @return string Course code
 */
function extract_course_code_from_category($fullname) {
    // Match pattern: word at start (letters + numbers).
    if (preg_match('/^([A-Z0-9]+)\s/', $fullname, $matches)) {
        return $matches[1];
    }
    // Fallback: first word or first 10 chars.
    $parts = explode(' ', $fullname);
    return !empty($parts[0]) ? $parts[0] : substr($fullname, 0, 10);
}

admin_externalpage_setup('gradereporttranscriptcourses');

$programid = optional_param('programid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]));
$PAGE->set_title(get_string('managecourses', 'gradereport_transcript'));
$PAGE->set_heading(get_string('managecourses', 'gradereport_transcript'));

// v1.0.33: Handle form submission (update existing mappings).
if ($action === 'save' && $programid) {
    require_sesskey();

    $program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid],
        'id, schoolid, categoryid, name, type, pdftemplate, gradescaleid, timecreated, timemodified', MUST_EXIST);

    // v1.0.33: Get mapping IDs and values from form.
    $mappingids = optional_param_array('mappingid', [], PARAM_INT);
    $theoryhours = optional_param_array('theoryhours', [], PARAM_FLOAT);
    $labhours = optional_param_array('labhours', [], PARAM_FLOAT);
    $clinicalhours = optional_param_array('clinicalhours', [], PARAM_FLOAT);
    $credits = optional_param_array('credits', [], PARAM_FLOAT);
    $ceuvalues = optional_param_array('ceuvalue', [], PARAM_FLOAT);
    $sortorders = optional_param_array('sortorder', [], PARAM_INT);

    $time = time();

    foreach ($mappingids as $index => $mappingid) {
        if (empty($mappingid)) {
            continue;
        }

        // Get existing mapping.
        $existing = $DB->get_record('gradereport_transcript_courses', ['id' => $mappingid], '*', MUST_EXIST);

        // Update only the editable fields.
        $existing->sortorder = $sortorders[$index] ?? $existing->sortorder;
        $existing->theoryhours = $theoryhours[$index] ?? 0;
        $existing->labhours = $labhours[$index] ?? 0;
        $existing->clinicalhours = $clinicalhours[$index] ?? 0;
        $existing->credits = $credits[$index] ?? 0;
        $existing->ceuvalue = $ceuvalues[$index] ?? 0;
        $existing->timemodified = $time;

        $DB->update_record('gradereport_transcript_courses', $existing);
    }

    redirect(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]),
        get_string('coursemappingssaved', 'gradereport_transcript'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// v1.0.33: Handle add_mapping action (add single course or category mapping).
if ($action === 'add_mapping' && $programid) {
    require_sesskey();

    $courseid = required_param('courseid', PARAM_INT);
    $mappingtype = required_param('mappingtype', PARAM_ALPHA);
    $categoryid = optional_param('categoryid', 0, PARAM_INT);

    // Validate course selection.
    if ($courseid === 0) {
        redirect(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]),
            get_string('error:courserequired', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    // Validate mapping type.
    if (!in_array($mappingtype, ['course', 'category'])) {
        $mappingtype = 'course';
    }

    // Validate category selection when mapping type is category.
    if ($mappingtype === 'category' && $categoryid === 0) {
        redirect(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]),
            get_string('error:categoryrequiredformapping', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    // Check if mapping already exists.
    $existing = $DB->get_record('gradereport_transcript_courses', [
        'programid' => $programid,
        'courseid' => $courseid,
        'mappingtype' => $mappingtype,
        'categoryid' => $categoryid
    ]);

    if (!$existing) {
        // Get highest sortorder.
        $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {gradereport_transcript_courses} WHERE programid = ?', [$programid]);
        $newsortorder = ($maxsort !== false) ? $maxsort + 1 : 1;

        $record = new stdClass();
        $record->programid = $programid;
        $record->courseid = $courseid;
        $record->mappingtype = $mappingtype;
        $record->categoryid = $categoryid;
        $record->sortorder = $newsortorder;
        $record->theoryhours = 0;
        $record->labhours = 0;
        $record->clinicalhours = 0;
        $record->credits = 0;
        $record->ceuvalue = 0;
        $record->timecreated = time();
        $record->timemodified = time();

        $DB->insert_record('gradereport_transcript_courses', $record);

        redirect(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]),
            get_string('mappingadded', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]),
            get_string('mappingexists', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_WARNING);
    }
}

// v1.0.33: Handle delete_mapping action.
if ($action === 'delete_mapping') {
    require_sesskey();

    $mappingid = required_param('mappingid', PARAM_INT);
    $confirm = optional_param('confirm', 0, PARAM_INT);

    if ($confirm) {
        $DB->delete_records('gradereport_transcript_courses', ['id' => $mappingid]);

        redirect(new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid]),
            get_string('mappingdeleted', 'gradereport_transcript'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletemapping', 'gradereport_transcript'));

        $mapping = $DB->get_record('gradereport_transcript_courses', ['id' => $mappingid], '*', MUST_EXIST);

        echo $OUTPUT->confirm(
            get_string('confirmdeletemapping', 'gradereport_transcript'),
            new moodle_url('/grade/report/transcript/manage_courses.php', [
                'programid' => $programid,
                'action' => 'delete_mapping',
                'mappingid' => $mappingid,
                'confirm' => 1,
                'sesskey' => sesskey()
            ]),
            new moodle_url('/grade/report/transcript/manage_courses.php', ['programid' => $programid])
        );

        echo $OUTPUT->footer();
        exit;
    }
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

    // v1.0.33: Get all courses from program's category for dropdown in "Add Mapping" form.
    $courses = $DB->get_records('course', ['category' => $program->categoryid], 'shortname ASC');
    unset($courses[SITEID]);

    // v1.0.33: Get ALL existing mappings (not just one per course).
    $mappings = $DB->get_records('gradereport_transcript_courses',
        ['programid' => $programid], 'sortorder ASC, id ASC');

    // Add defensive checks for missing fields.
    foreach ($mappings as $mapping) {
        if (!property_exists($mapping, 'clinicalhours')) {
            $mapping->clinicalhours = 0;
        }
        if (!property_exists($mapping, 'mappingtype')) {
            $mapping->mappingtype = 'course';
        }
        if (!property_exists($mapping, 'categoryid')) {
            $mapping->categoryid = 0;
        }
    }

    // v1.0.33: "Add New Mapping" form section.
    $categorymapping_enabled = get_config('gradereport_transcript', 'enablecategorymapping');

    if (!empty($courses)) {
        echo html_writer::start_div('card mb-4');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', get_string('addmapping', 'gradereport_transcript'), ['class' => 'card-title']);

        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => 'manage_courses.php',
            'class' => 'add-mapping-form',
            'id' => 'add-mapping-form'
        ]);

        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'programid', 'value' => $programid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'add_mapping']);

        echo html_writer::start_div('row g-3 align-items-end');

        // Course selector.
        echo html_writer::start_div('col-md-4');
        echo html_writer::label(get_string('selectcourse', 'gradereport_transcript'), 'add-course-select', true, ['class' => 'form-label']);
        $courseoptions = [];
        foreach ($courses as $course) {
            $courseoptions[$course->id] = format_string($course->shortname) . ' - ' . format_string($course->fullname);
        }
        echo html_writer::select($courseoptions, 'courseid', 0, [0 => get_string('selectcourse', 'gradereport_transcript')], ['id' => 'add-course-select', 'class' => 'form-select']);
        echo html_writer::end_div();

        // Mapping type selector (if enabled).
        if ($categorymapping_enabled) {
            echo html_writer::start_div('col-md-3');
            echo html_writer::label(get_string('mappingtype', 'gradereport_transcript'), 'add-mapping-type', true, ['class' => 'form-label']);
            $mappingoptions = [
                'course' => get_string('mappingtype_course', 'gradereport_transcript'),
                'category' => get_string('mappingtype_category', 'gradereport_transcript'),
            ];
            echo html_writer::select($mappingoptions, 'mappingtype', 'course', false, ['id' => 'add-mapping-type', 'class' => 'form-select']);
            echo html_writer::end_div();

            // Category selector (hidden by default).
            echo html_writer::start_div('col-md-3');
            echo html_writer::label(get_string('selectcategory', 'gradereport_transcript'), 'add-category-select', true, ['class' => 'form-label']);
            echo html_writer::select(
                [],
                'categoryid',
                0,
                [0 => get_string('selectcategory', 'gradereport_transcript')],
                ['id' => 'add-category-select', 'class' => 'form-select', 'style' => 'display:none;']
            );
            echo html_writer::end_div();
        } else {
            // Hidden field for mapping type if category mapping is disabled.
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mappingtype', 'value' => 'course']);
        }

        // Add button.
        echo html_writer::start_div('col-md-2');
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('addmappingbtn', 'gradereport_transcript'),
            'class' => 'btn btn-success w-100',
        ]);
        echo html_writer::end_div();

        echo html_writer::end_div(); // row
        echo html_writer::end_tag('form');
        echo html_writer::end_div(); // card-body
        echo html_writer::end_div(); // card
    }

    // Display existing mappings table.
    if (empty($mappings)) {
        echo $OUTPUT->notification(get_string('nomappings', 'gradereport_transcript'),
            \core\output\notification::NOTIFY_INFO);
    } else {
        // Build form.
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => 'manage_courses.php',
            'class' => 'course-mapping-form',
        ]);

        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'programid', 'value' => $programid]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);

        // v1.0.33: Build table showing existing mappings.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable table-striped';

        // Table headers based on program type.
        $headers = [
            get_string('coursecode', 'gradereport_transcript'),
            get_string('coursename', 'gradereport_transcript'),
        ];

        // v1.0.33: Add mapping type column if enabled.
        if ($categorymapping_enabled) {
            $headers[] = get_string('mappingtype', 'gradereport_transcript');
        }

        $headers[] = get_string('sortorder', 'gradereport_transcript');

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

        // v1.0.33: Add Actions column.
        $headers[] = get_string('actions');

        $table->head = $headers;
        $table->data = [];

        // v1.0.33: Loop through existing mappings (not courses).
        foreach ($mappings as $mapping) {
            $row = [];

            // Get course details.
            $course = $DB->get_record('course', ['id' => $mapping->courseid], 'id, shortname, fullname');
            if (!$course) {
                continue; // Skip if course was deleted.
            }

            // v1.0.33: Display course code or category code.
            if ($categorymapping_enabled && $mapping->mappingtype === 'category' && $mapping->categoryid) {
                // Get category details.
                $category = $DB->get_record('grade_categories', ['id' => $mapping->categoryid], 'id, fullname');
                if ($category) {
                    $coursecode = extract_course_code_from_category($category->fullname);
                } else {
                    $coursecode = format_string($course->shortname);
                }
            } else {
                $coursecode = format_string($course->shortname);
            }
            $row[] = html_writer::tag('strong', $coursecode);

            // v1.0.33: Display course name or category name.
            if ($categorymapping_enabled && $mapping->mappingtype === 'category' && $mapping->categoryid) {
                $category = $DB->get_record('grade_categories', ['id' => $mapping->categoryid], 'id, fullname');
                if ($category) {
                    $coursename = format_string($category->fullname);
                } else {
                    $coursename = format_string($course->fullname);
                }
            } else {
                $coursename = format_string($course->fullname);
            }
            $row[] = $coursename;

            // Hidden mapping ID for updates.
            $mappingidfield = html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'mappingid[]',
                'value' => $mapping->id,
            ]);

            // v1.0.33: Display mapping type (read-only).
            if ($categorymapping_enabled) {
                $mappingtype_label = ($mapping->mappingtype === 'category') ?
                    get_string('mappingtype_category', 'gradereport_transcript') :
                    get_string('mappingtype_course', 'gradereport_transcript');
                $row[] = $mappingtype_label;
            }

            // Sort order.
            $row[] = $mappingidfield . html_writer::empty_tag('input', [
                'type' => 'number',
                'name' => 'sortorder[]',
                'value' => $mapping->sortorder,
                'min' => '0',
                'step' => '1',
                'class' => 'form-control',
                'style' => 'width: 80px;',
            ]);

            // Program-type specific fields.
            if ($program->type === 'hourbased') {
                // v1.0.33: Get hour values from mapping.
                $hourvalues = [
                    1 => $mapping->theoryhours ?? 0,
                    2 => $mapping->labhours ?? 0,
                    3 => $mapping->clinicalhours ?? 0,
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
                $row[] = html_writer::empty_tag('input', [
                    'type' => 'number',
                    'name' => 'credits[]',
                    'value' => $mapping->credits ?? 0,
                    'min' => '0',
                    'step' => '0.5',
                    'class' => 'form-control',
                    'style' => 'width: 100px;',
                ]);
            } else if ($program->type === 'ceu') {
                $row[] = html_writer::empty_tag('input', [
                    'type' => 'number',
                    'name' => 'ceuvalue[]',
                    'value' => $mapping->ceuvalue ?? 0,
                    'min' => '0',
                    'step' => '0.1',
                    'class' => 'form-control',
                    'style' => 'width: 100px;',
                ]);
            }

            // v1.0.33: Add DELETE button.
            $deleteurl = new moodle_url('/grade/report/transcript/manage_courses.php', [
                'programid' => $programid,
                'action' => 'delete_mapping',
                'mappingid' => $mapping->id,
                'sesskey' => sesskey()
            ]);
            $row[] = html_writer::link($deleteurl, get_string('delete'), [
                'class' => 'btn btn-sm btn-danger',
                'title' => get_string('deletemapping', 'gradereport_transcript')
            ]);

            $table->data[] = $row;
        }

        // v1.0.33: Add Grand Total row.
        if ($program->type === 'hourbased' && !empty($showhourlabels)) {
            $grandtotalrow = [];
            $grandtotalrow[] = html_writer::tag('strong', get_string('grandtotal', 'gradereport_transcript'));
            $grandtotalrow[] = ''; // Empty course name column.
            if ($categorymapping_enabled) {
                $grandtotalrow[] = ''; // Empty mapping type column.
            }
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

            $grandtotalrow[] = ''; // Empty actions column.
            $table->data[] = $grandtotalrow;
        } else if ($program->type === 'creditbased') {
            $grandtotalrow = [];
            $grandtotalrow[] = html_writer::tag('strong', get_string('grandtotal', 'gradereport_transcript'));
            $grandtotalrow[] = ''; // Empty course name column.
            if ($categorymapping_enabled) {
                $grandtotalrow[] = ''; // Empty mapping type column.
            }
            $grandtotalrow[] = ''; // Empty sort order column.
            $grandtotalrow[] = html_writer::tag('strong', '0.0', [
                'class' => 'total-credits',
                'id' => 'total-credits',
            ]);
            $grandtotalrow[] = ''; // Empty actions column.
            $table->data[] = $grandtotalrow;
        } else if ($program->type === 'ceu') {
            $grandtotalrow = [];
            $grandtotalrow[] = html_writer::tag('strong', get_string('grandtotal', 'gradereport_transcript'));
            $grandtotalrow[] = ''; // Empty course name column.
            if ($categorymapping_enabled) {
                $grandtotalrow[] = ''; // Empty mapping type column.
            }
            $grandtotalrow[] = ''; // Empty sort order column.
            $grandtotalrow[] = html_writer::tag('strong', '0.0', [
                'class' => 'total-ceu',
                'id' => 'total-ceu',
            ]);
            $grandtotalrow[] = ''; // Empty actions column.
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

    // v1.0.32: Category mapping JavaScript (if enabled).
    if ($categorymapping_enabled && $programid) {
        echo html_writer::start_tag('script');
        ?>
        // v1.0.32: Category mapping dynamic UI logic.
        (function() {
            const mappingSelects = document.querySelectorAll('.mapping-type-select');
            const categoryCache = {}; // Cache categories per course.

            // Function to fetch categories for a course.
            function fetchCategories(courseid, callback) {
                if (categoryCache[courseid]) {
                    callback(categoryCache[courseid]);
                    return;
                }

                const xhr = new XMLHttpRequest();
                xhr.open('GET', M.cfg.wwwroot + '/grade/report/transcript/ajax_get_categories.php?courseid=' + courseid, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const categories = JSON.parse(xhr.responseText);
                            categoryCache[courseid] = categories;
                            callback(categories);
                        } catch (e) {
                            console.error('Failed to parse categories JSON:', e);
                            callback([]);
                        }
                    } else {
                        console.error('Failed to fetch categories. Status:', xhr.status);
                        callback([]);
                    }
                };
                xhr.onerror = function() {
                    console.error('Network error while fetching categories');
                    callback([]);
                };
                xhr.send();
            }

            // Function to populate category dropdown.
            function populateCategorySelect(categorySelect, categories, selectedId) {
                categorySelect.innerHTML = '<option value="0"><?php echo get_string('selectcategory', 'gradereport_transcript'); ?></option>';

                categories.forEach(function(cat) {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.fullname;
                    if (cat.id == selectedId) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });
            }

            // Initialize all mapping type selectors.
            mappingSelects.forEach(function(select) {
                const courseid = select.getAttribute('data-courseid');
                const categorySelect = document.querySelector('.category-select[data-courseid="' + courseid + '"]');
                const categoryWrapper = categorySelect.closest('.category-wrapper');

                // Handle mapping type change.
                select.addEventListener('change', function() {
                    if (this.value === 'category') {
                        // Show category dropdown and fetch categories.
                        categoryWrapper.querySelector('.category-select').style.display = 'block';

                        // Fetch categories for this course.
                        fetchCategories(courseid, function(categories) {
                            const currentValue = categorySelect.value;
                            populateCategorySelect(categorySelect, categories, currentValue);
                        });
                    } else {
                        // Hide category dropdown.
                        categoryWrapper.querySelector('.category-select').style.display = 'none';
                    }
                });

                // Pre-load categories if already set to 'category' type.
                if (select.value === 'category') {
                    fetchCategories(courseid, function(categories) {
                        const currentValue = categorySelect.value;
                        populateCategorySelect(categorySelect, categories, currentValue);
                    });
                }
            });

            // v1.0.33: Add Mapping form - handle mapping type change.
            const addMappingTypeSelect = document.getElementById('add-mapping-type');
            const addCourseSelect = document.getElementById('add-course-select');
            const addCategorySelect = document.getElementById('add-category-select');

            if (addMappingTypeSelect && addCourseSelect && addCategorySelect) {
                // Handle mapping type change in Add Mapping form.
                addMappingTypeSelect.addEventListener('change', function() {
                    if (this.value === 'category') {
                        addCategorySelect.style.display = 'block';

                        // Fetch categories for selected course.
                        const courseid = addCourseSelect.value;
                        if (courseid && courseid != '0') {
                            fetchCategories(courseid, function(categories) {
                                populateCategorySelect(addCategorySelect, categories, '');
                            });
                        }
                    } else {
                        addCategorySelect.style.display = 'none';
                    }
                });

                // Handle course change in Add Mapping form.
                addCourseSelect.addEventListener('change', function() {
                    const courseid = this.value;
                    if (courseid && courseid != '0' && addMappingTypeSelect.value === 'category') {
                        fetchCategories(courseid, function(categories) {
                            populateCategorySelect(addCategorySelect, categories, '');
                        });
                    }
                });

                // Add form validation on submit.
                const addMappingForm = document.getElementById('add-mapping-form');
                if (addMappingForm) {
                    addMappingForm.addEventListener('submit', function(e) {
                        const courseid = addCourseSelect.value;
                        const mappingtype = addMappingTypeSelect.value;
                        const categoryid = addCategorySelect.value;

                        // Validate course selection.
                        if (!courseid || courseid == '0') {
                            alert('<?php echo get_string('error:courserequired', 'gradereport_transcript'); ?>');
                            e.preventDefault();
                            return false;
                        }

                        // Validate category selection when type is category.
                        if (mappingtype === 'category' && (!categoryid || categoryid == '0')) {
                            alert('<?php echo get_string('error:categoryrequiredformapping', 'gradereport_transcript'); ?>');
                            e.preventDefault();
                            return false;
                        }

                        // Allow form submission when validation passes.
                        return true;
                    });
                }
            }
        })();
        <?php
        echo html_writer::end_tag('script');
    }
}

echo $OUTPUT->footer();
