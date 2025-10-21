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
 * The gradebook transcript report - Student view
 *
 * Phase 5: Student transcript viewing with program detection
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/transcript/lib.php');
require_once($CFG->dirroot . '/grade/report/transcript/classes/helper.php');

defined('MOODLE_INTERNAL') || die();

// Course ID is optional, defaults to SITEID (for profile/system context access)
$courseid = optional_param('id', SITEID, PARAM_INT);
$userid   = optional_param('userid', null, PARAM_INT);

$PAGE->set_url(new moodle_url('/grade/report/transcript/index.php', ['id' => $courseid]));

// Require login (simple check - any logged in user can access)
require_login();
$PAGE->set_pagelayout('report');

// Load course for page heading (optional, defaults to site)
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    $course = get_site();
}

// Determine if user is viewing own transcript or another user's
if ($userid && $userid != $USER->id) {
    // Viewing another user's transcript - only allow for site admins and managers
    if (!is_siteadmin()) {
        // Check if user is a manager/teacher in ANY course
        $systemcontext = context_system::instance();
        if (!has_capability('gradereport/transcript:manage', $systemcontext)) {
            throw new \moodle_exception('nopermissions', 'error', '', 'View other user transcripts');
        }
    }
    $viewingother = true;
} else {
    // Viewing own transcript - any logged in user can do this
    $userid = $USER->id;
    $viewingother = false;

    // Check if student access is enabled in settings (only for students viewing their own)
    $systemcontext = context_system::instance();
    if (!is_siteadmin() && !has_capability('gradereport/transcript:manage', $systemcontext)) {
        $enablestudents = get_config('gradereport_transcript', 'enablestudents');
        if ($enablestudents === false) {
            $enablestudents = 1;  // Default to enabled
        }
        if (!$enablestudents) {
            throw new \moodle_exception('studentaccessdisabled', 'gradereport_transcript');
        }
    }
}

// Load user.
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Set page title and heading.
$PAGE->set_title(get_string('pluginname', 'gradereport_transcript'));
$PAGE->set_heading($course->fullname);

// Output header.
echo $OUTPUT->header();

// Page heading.
if ($viewingother) {
    echo $OUTPUT->heading(get_string('viewingtranscriptfor', 'gradereport_transcript', fullname($user)));
} else {
    echo $OUTPUT->heading(get_string('mytranscript', 'gradereport_transcript'));
}

// Display user information.
echo html_writer::start_div('transcript-student-info alert alert-info');
echo html_writer::tag('h4', get_string('studentinformation', 'gradereport_transcript'));
echo html_writer::tag('p', html_writer::tag('strong', get_string('studentname', 'gradereport_transcript') . ': ') . fullname($user));
echo html_writer::tag('p', html_writer::tag('strong', get_string('studentid', 'gradereport_transcript') . ': ') . s($user->id));
echo html_writer::tag('p', html_writer::tag('strong', get_string('studentemail', 'gradereport_transcript') . ': ') . s($user->email));
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

    $sql = "SELECT DISTINCT p.id, p.name, p.type, p.schoolid, s.name AS schoolname
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
        'alert alert-warning'
    );

    if ($viewingother) {
        echo html_writer::tag('p', get_string('studentnotenrolled', 'gradereport_transcript'));
    } else {
        echo html_writer::tag('p', get_string('youarenotenrolled', 'gradereport_transcript'));
    }

} else {
    // Display program list.
    echo html_writer::tag('h3', get_string('availabletranscripts', 'gradereport_transcript'));
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
        echo html_writer::tag('td', format_string($program->name));

        // School name.
        echo html_writer::tag('td', format_string($program->schoolname));

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

        // Get pricing for this program's school.
        $pricing = \gradereport_transcript\helper::get_pricing($program->schoolid);

        // Button 1: View Transcript (always available).
        $viewurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
            'programid' => $program->id,
            'userid' => $userid,
            'official' => 0
        ]);
        echo html_writer::link($viewurl, get_string('viewtranscript', 'gradereport_transcript'),
            ['class' => 'btn btn-sm btn-primary']);

        echo ' ';

        if ($pricing) {
            // Button 2: Download Unofficial OR Order Unofficial (conditional based on pricing).
            if ($pricing->unofficialprice > 0) {
                // Unofficial costs money - show ORDER button.
                $orderunofficialurl = new moodle_url('/grade/report/transcript/request_transcript.php', [
                    'programid' => $program->id,
                    'type' => 'unofficial'
                ]);
                echo html_writer::link($orderunofficialurl, get_string('orderunofficial', 'gradereport_transcript'),
                    ['class' => 'btn btn-sm btn-warning']);
            } else {
                // Unofficial is free - show DOWNLOAD button.
                $unofficialurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
                    'programid' => $program->id,
                    'userid' => $userid,
                    'official' => 0,
                    'action' => 'download',
                    'sesskey' => sesskey()
                ]);
                echo html_writer::link($unofficialurl, get_string('downloadunofficial', 'gradereport_transcript'),
                    ['class' => 'btn btn-sm btn-secondary']);
            }

            echo ' ';

            // Button 3: Order Official (always available for students).
            $orderofficialurl = new moodle_url('/grade/report/transcript/request_transcript.php', [
                'programid' => $program->id,
                'type' => 'official'
            ]);
            echo html_writer::link($orderofficialurl, get_string('orderofficial', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-success']);
        } else {
            // No pricing configured - show warning instead of order buttons.
            echo html_writer::tag('span', get_string('pricingnotconfigured', 'gradereport_transcript'),
                ['class' => 'badge badge-danger']);
        }

        // Button 4: Download Official (admin only - keep existing).
        if (is_siteadmin() || has_capability('gradereport/transcript:manage', context_system::instance())) {
            echo ' ';
            $officialurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
                'programid' => $program->id,
                'userid' => $userid,
                'official' => 1,
                'action' => 'download',
                'sesskey' => sesskey()
            ]);
            echo html_writer::link($officialurl, get_string('downloadofficial', 'gradereport_transcript'),
                ['class' => 'btn btn-sm btn-info']);
        }

        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // Help text.
    echo html_writer::start_div('alert alert-secondary mt-3');
    echo html_writer::tag('h5', get_string('transcripthelp', 'gradereport_transcript'));
    echo html_writer::start_tag('ul');
    echo html_writer::tag('li', get_string('viewtranscripthelp', 'gradereport_transcript'));
    echo html_writer::tag('li', get_string('unofficialtranscripthelp', 'gradereport_transcript'));
    if (is_siteadmin() || has_capability('gradereport/transcript:manage', context_system::instance())) {
        echo html_writer::tag('li', get_string('officialtranscripthelp', 'gradereport_transcript'));
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

// Output footer.
echo $OUTPUT->footer();
