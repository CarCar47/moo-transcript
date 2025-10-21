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
 * Help and documentation page for the transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('gradereporttranscripthelp');

defined('MOODLE_INTERNAL') || die();

$PAGE->set_url(new moodle_url('/grade/report/transcript/help.php'));
$PAGE->set_title(get_string('help', 'gradereport_transcript'));
$PAGE->set_heading(get_string('help', 'gradereport_transcript'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginhelp', 'gradereport_transcript'));

// Quick Start Guide section.
echo html_writer::start_tag('div', ['class' => 'transcript-help-section']);
echo $OUTPUT->heading(get_string('quickstartguide', 'gradereport_transcript'), 3);
echo html_writer::tag('p', get_string('quickstartintro', 'gradereport_transcript'));

echo html_writer::start_tag('ol', ['class' => 'transcript-quickstart-steps']);
echo html_writer::tag('li', html_writer::tag('strong', get_string('step1schools', 'gradereport_transcript')) . ' - ' .
    get_string('step1schoolsdesc', 'gradereport_transcript'));
echo html_writer::tag('li', html_writer::tag('strong', get_string('step2programs', 'gradereport_transcript')) . ' - ' .
    get_string('step2programsdesc', 'gradereport_transcript'));
echo html_writer::tag('li', html_writer::tag('strong', get_string('step3courses', 'gradereport_transcript')) . ' - ' .
    get_string('step3coursesdesc', 'gradereport_transcript'));
echo html_writer::tag('li', html_writer::tag('strong', get_string('step4template', 'gradereport_transcript')) . ' - ' .
    get_string('step4templatedesc', 'gradereport_transcript'));
echo html_writer::tag('li', html_writer::tag('strong', get_string('step5test', 'gradereport_transcript')) . ' - ' .
    get_string('step5testdesc', 'gradereport_transcript'));
echo html_writer::end_tag('ol');
echo html_writer::end_tag('div');

// PDF Template Creation Guide section.
echo html_writer::start_tag('div', ['class' => 'transcript-help-section']);
echo $OUTPUT->heading(get_string('pdftemplatecreation', 'gradereport_transcript'), 3);
echo html_writer::tag('p', get_string('pdftemplateintro', 'gradereport_transcript'));

echo $OUTPUT->heading(get_string('adobeacrobatsetup', 'gradereport_transcript'), 4);
echo html_writer::start_tag('ol', ['class' => 'transcript-adobe-steps']);
echo html_writer::tag('li', get_string('adobestep1', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('adobestep2', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('adobestep3', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('adobestep4', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('adobestep5', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('adobestep6', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('adobestep7', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('adobestep8', 'gradereport_transcript'));
echo html_writer::end_tag('ol');
echo html_writer::end_tag('div');

// PDF Form Field Reference section.
echo html_writer::start_tag('div', ['class' => 'transcript-help-section']);
echo $OUTPUT->heading(get_string('pdffieldreference', 'gradereport_transcript'), 3);
echo html_writer::tag('p', get_string('pdffieldintro', 'gradereport_transcript'));

// Student Information Fields table.
echo $OUTPUT->heading(get_string('studentinfofields', 'gradereport_transcript'), 4);
echo html_writer::start_tag('table', ['class' => 'table table-bordered']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('fieldname', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('fieldtype', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('description', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('example', 'gradereport_transcript'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$studentfields = [
    ['student_name', 'Text', 'Student full name', 'John Doe'],
    ['student_id', 'Text', 'Student ID number', 'STU123456'],
    ['student_email', 'Text', 'Student email address', 'student@example.com'],
    ['date_of_birth', 'Date', 'Student date of birth', '01/15/1995'],
    ['program_name', 'Text', 'Name of the program', 'Permanent Makeup Artistry'],
    ['enrollment_date', 'Date', 'Program enrollment date', '09/01/2024'],
    ['graduation_date', 'Date', 'Expected/actual graduation date', '06/15/2025'],
];

foreach ($studentfields as $field) {
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', html_writer::tag('code', $field[0]));
    echo html_writer::tag('td', $field[1]);
    echo html_writer::tag('td', $field[2]);
    echo html_writer::tag('td', html_writer::tag('em', $field[3]));
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Course Fields table.
echo $OUTPUT->heading(get_string('coursefields', 'gradereport_transcript'), 4);
echo html_writer::tag('p', get_string('coursefieldsnote', 'gradereport_transcript'));

echo html_writer::start_tag('table', ['class' => 'table table-bordered']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('fieldpattern', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('fieldtype', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('description', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('example', 'gradereport_transcript'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$coursefields = [
    ['course_number_{N}', 'Text', 'Course code/number', 'course_number_1 = "PMU 101"'],
    ['course_title_{N}', 'Text', 'Course title', 'course_title_1 = "Intro to PMU"'],
    ['grade_letter_{N}', 'Text', 'Letter grade (A, B, C, D, F)', 'grade_letter_1 = "A"'],
    ['grade_number_{N}', 'Number', 'Numeric grade (0-100)', 'grade_number_1 = "95"'],
    ['theory_hours_{N}', 'Number', 'Theory/classroom hours', 'theory_hours_1 = "40"'],
    ['lab_hours_{N}', 'Number', 'Lab/practical hours', 'lab_hours_1 = "80"'],
    ['total_hours_{N}', 'Number', 'Total hours (auto-calculated)', 'total_hours_1 = "120"'],
    ['credits_{N}', 'Number', 'Credit hours (for credit-based)', 'credits_1 = "3.0"'],
    ['ceu_value_{N}', 'Number', 'CEU value (for CEU programs)', 'ceu_value_1 = "1.2"'],
];

foreach ($coursefields as $field) {
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', html_writer::tag('code', $field[0]));
    echo html_writer::tag('td', $field[1]);
    echo html_writer::tag('td', $field[2]);
    echo html_writer::tag('td', html_writer::tag('em', $field[3]));
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Summary Fields table.
echo $OUTPUT->heading(get_string('summaryfields', 'gradereport_transcript'), 4);
echo html_writer::start_tag('table', ['class' => 'table table-bordered']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('fieldname', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('fieldtype', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('description', 'gradereport_transcript'));
echo html_writer::tag('th', get_string('example', 'gradereport_transcript'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$summaryfields = [
    ['total_theory_hours', 'Number', 'Sum of all theory hours', '240'],
    ['total_lab_hours', 'Number', 'Sum of all lab hours', '480'],
    ['total_clinical_hours', 'Number', 'Sum of all clinical hours', '120'],
    ['total_hours', 'Number', 'Grand total of all hours', '840'],
    ['total_credits', 'Number', 'Total credits earned', '60.0'],
    ['gpa', 'Number', 'Grade Point Average', '3.75'],
    ['issue_date', 'Date', 'Date transcript was generated', '10/18/2025'],
    ['verification_code', 'Text', 'Unique verification code', 'TXN-A7B9C2D4E6F8'],
    ['school_name', 'Text', 'Name of issuing school', 'Professional Career School'],
    ['school_address', 'Text', 'School address', '123 Main St, City, ST 12345'],
];

foreach ($summaryfields as $field) {
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', html_writer::tag('code', $field[0]));
    echo html_writer::tag('td', $field[1]);
    echo html_writer::tag('td', $field[2]);
    echo html_writer::tag('td', html_writer::tag('em', $field[3]));
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_tag('div');

// Troubleshooting section.
echo html_writer::start_tag('div', ['class' => 'transcript-help-section']);
echo $OUTPUT->heading(get_string('troubleshooting', 'gradereport_transcript'), 3);

echo $OUTPUT->heading(get_string('fieldsnotfilling', 'gradereport_transcript'), 4);
echo html_writer::tag('p', get_string('fieldsnotfillingdesc', 'gradereport_transcript'));
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('troubleshoot1', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('troubleshoot2', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('troubleshoot3', 'gradereport_transcript'));
echo html_writer::end_tag('ul');

echo $OUTPUT->heading(get_string('missinggrades', 'gradereport_transcript'), 4);
echo html_writer::tag('p', get_string('missingradesdesc', 'gradereport_transcript'));

echo $OUTPUT->heading(get_string('incorrectgpa', 'gradereport_transcript'), 4);
echo html_writer::tag('p', get_string('incorrectgpadesc', 'gradereport_transcript'));
echo html_writer::end_tag('div');

// Best Practices section.
echo html_writer::start_tag('div', ['class' => 'transcript-help-section']);
echo $OUTPUT->heading(get_string('bestpractices', 'gradereport_transcript'), 3);
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('bestpractice1', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('bestpractice2', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('bestpractice3', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('bestpractice4', 'gradereport_transcript'));
echo html_writer::tag('li', get_string('bestpractice5', 'gradereport_transcript'));
echo html_writer::end_tag('ul');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
