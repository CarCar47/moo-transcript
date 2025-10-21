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
 * Admin page to view student transcripts
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/lib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('gradereporttranscriptviewstudents');

// Get optional user ID parameter.
$userid = optional_param('userid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/grade/report/transcript/view_student_transcripts.php', ['userid' => $userid]));
$PAGE->set_title(get_string('viewstudenttranscripts', 'gradereport_transcript'));
$PAGE->set_heading(get_string('viewstudenttranscripts', 'gradereport_transcript'));

// Require viewall capability.
$systemcontext = context_system::instance();
require_capability('gradereport/transcript:viewall', $systemcontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewstudenttranscripts', 'gradereport_transcript'));

// Search form.
echo html_writer::start_div('student-search-form mb-4');
echo html_writer::tag('h4', get_string('searchstudent', 'gradereport_transcript'));

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url, 'class' => 'form-inline']);

// Search input with autocomplete.
echo html_writer::start_div('form-group mr-2');
echo html_writer::tag('label', get_string('studentnameoremail', 'gradereport_transcript'),
    ['for' => 'search', 'class' => 'sr-only']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'search',
    'class' => 'form-control',
    'placeholder' => get_string('studentnameoremail', 'gradereport_transcript'),
    'value' => $search,
    'size' => 40
]);
echo html_writer::end_div();

// Search button.
echo html_writer::tag('button', get_string('search'), ['type' => 'submit', 'class' => 'btn btn-primary']);

echo html_writer::end_tag('form');
echo html_writer::end_div();

// If search was performed, show results.
if (!empty($search) && empty($userid)) {
    $searchsql = $DB->sql_like('CONCAT(firstname, \' \', lastname, \' \', email)', ':search', false, false);
    $searchparam = '%' . $DB->sql_like_escape($search) . '%';

    $students = $DB->get_records_sql(
        "SELECT id, firstname, lastname, email, username
           FROM {user}
          WHERE deleted = 0 AND suspended = 0 AND $searchsql
       ORDER BY lastname ASC, firstname ASC",
        ['search' => $searchparam],
        0,
        20  // Limit to 20 results
    );

    if (empty($students)) {
        echo html_writer::div(
            get_string('nostudentsfound', 'gradereport_transcript'),
            'alert alert-warning'
        );
    } else {
        echo html_writer::tag('h4', get_string('searchresults', 'core'));
        echo html_writer::start_tag('table', ['class' => 'generaltable table table-striped']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('fullname'));
        echo html_writer::tag('th', get_string('email'));
        echo html_writer::tag('th', get_string('username'));
        echo html_writer::tag('th', get_string('actions', 'core'), ['class' => 'text-center']);
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        foreach ($students as $student) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', fullname($student));
            echo html_writer::tag('td', $student->email);
            echo html_writer::tag('td', $student->username);
            echo html_writer::start_tag('td', ['class' => 'text-center']);

            $selecturl = new moodle_url('/grade/report/transcript/view_student_transcripts.php', ['userid' => $student->id]);
            echo html_writer::link($selecturl, get_string('select', 'core'), ['class' => 'btn btn-sm btn-primary']);

            echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');

        if (count($students) >= 20) {
            echo html_writer::div(
                get_string('toomanyresults', 'gradereport_transcript'),
                'alert alert-info'
            );
        }
    }
}

// If user is selected, display their transcripts.
if ($userid) {
    // Load user.
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    // Display user information.
    echo html_writer::start_div('transcript-student-info alert alert-info mt-4');
    echo html_writer::tag('h4', get_string('selectedstudent', 'gradereport_transcript'));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('studentname', 'gradereport_transcript') . ': ') . fullname($user));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('studentid', 'gradereport_transcript') . ': ') . $user->id);
    echo html_writer::tag('p', html_writer::tag('strong', get_string('studentemail', 'gradereport_transcript') . ': ') . $user->email);
    echo html_writer::tag('p', html_writer::tag('strong', get_string('username', 'core') . ': ') . $user->username);

    // Clear selection button.
    $clearurl = new moodle_url('/grade/report/transcript/view_student_transcripts.php');
    echo html_writer::link($clearurl, get_string('selectdifferentstudent', 'gradereport_transcript'),
        ['class' => 'btn btn-sm btn-secondary mt-2']);

    echo html_writer::end_div();

    // Auto-detect programs student is enrolled in.
    $programs = [];

    // Get all courses user is enrolled in.
    $enrolledcourses = enrol_get_users_courses($userid, false, 'id,shortname,fullname');

    if (!empty($enrolledcourses)) {
        // Get course IDs.
        $courseids = array_keys($enrolledcourses);

        // Query course mappings to find which programs these courses belong to.
        list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $sql = "SELECT DISTINCT p.id, p.name, p.type, s.name AS schoolname
                  FROM {gradereport_transcript_courses} c
                  JOIN {gradereport_transcript_programs} p ON c.programid = p.id
                  JOIN {gradereport_transcript_schools} s ON p.schoolid = s.id
                 WHERE c.courseid $insql
              ORDER BY p.name ASC";

        $programs = $DB->get_records_sql($sql, $params);
    }

    // Display programs.
    if (empty($programs)) {
        // No transcripts available.
        echo html_writer::div(
            get_string('notranscriptsavailable', 'gradereport_transcript'),
            'alert alert-warning mt-3'
        );

        echo html_writer::tag('p', get_string('studentnotenrolled', 'gradereport_transcript'));

    } else {
        // Display program list.
        echo html_writer::tag('h3', get_string('availabletranscripts', 'gradereport_transcript'), ['class' => 'mt-4']);
        echo html_writer::tag('p', get_string('selectprogrambelow', 'gradereport_transcript'));

        echo html_writer::start_tag('table', ['class' => 'generaltable table table-striped']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('programname', 'gradereport_transcript'));
        echo html_writer::tag('th', get_string('school', 'gradereport_transcript'));
        echo html_writer::tag('th', get_string('programtype', 'gradereport_transcript'));
        echo html_writer::tag('th', get_string('actions', 'core'), ['class' => 'text-center']);
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');

        echo html_writer::start_tag('tbody');

        foreach ($programs as $program) {
            echo html_writer::start_tag('tr');

            // Program name.
            echo html_writer::tag('td', $program->name);

            // School name.
            echo html_writer::tag('td', $program->schoolname);

            // Program type.
            $typename = '';
            switch ($program->type) {
                case 'hourbased':
                    $typename = get_string('programtype_hourbased', 'gradereport_transcript');
                    break;
                case 'creditbased':
                    $typename = get_string('programtype_creditbased', 'gradereport_transcript');
                    break;
                case 'ceu':
                    $typename = get_string('programtype_ceu', 'gradereport_transcript');
                    break;
            }
            echo html_writer::tag('td', $typename);

            // Actions.
            echo html_writer::start_tag('td', ['class' => 'text-center']);

            // View Transcript button (HTML preview).
            $viewurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
                'programid' => $program->id,
                'userid' => $userid,
                'official' => 0
            ]);
            echo html_writer::link($viewurl, get_string('viewtranscript', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-primary']);

            echo ' ';

            // Download Unofficial PDF button.
            $unofficialurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
                'programid' => $program->id,
                'userid' => $userid,
                'official' => 0,
                'action' => 'download',
                'sesskey' => sesskey()
            ]);
            echo html_writer::link($unofficialurl, get_string('downloadunofficial', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-secondary']);

            echo ' ';

            // Download Official PDF button.
            $officialurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
                'programid' => $program->id,
                'userid' => $userid,
                'official' => 1,
                'action' => 'download',
                'sesskey' => sesskey()
            ]);
            echo html_writer::link($officialurl, get_string('downloadofficial', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-success']);

            echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }
} else if (empty($search)) {
    // No search performed and no user selected - show instructions.
    echo html_writer::div(
        get_string('searchinstructions', 'gradereport_transcript'),
        'alert alert-info'
    );
}

echo $OUTPUT->footer();
