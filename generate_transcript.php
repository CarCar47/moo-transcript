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
 * Generate and download transcript PDF
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/grade/report/transcript/classes/transcript_generator.php');

defined('MOODLE_INTERNAL') || die();

require_login();

$programid = required_param('programid', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$official = optional_param('official', 0, PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHA);
$requestid = optional_param('requestid', 0, PARAM_INT);

// Security checks - simple user-based logic.
// Students can view their own transcripts, admins/managers can view anyone's.
if ($userid != $USER->id) {
    // Viewing another user - only allow for site admins and managers
    if (!is_siteadmin()) {
        $systemcontext = context_system::instance();
        if (!has_capability('gradereport/transcript:manage', $systemcontext)) {
            throw new \moodle_exception('nopermissions', 'error', '', 'View other user transcripts');
        }
    }
}

// Check if student access is enabled (for students viewing their own)
if ($userid == $USER->id && !is_siteadmin()) {
    $systemcontext = context_system::instance();
    if (!has_capability('gradereport/transcript:manage', $systemcontext)) {
        $enablestudents = get_config('gradereport_transcript', 'enablestudents');
        if ($enablestudents === false) {
            $enablestudents = 1;  // Default enabled
        }
        if (!$enablestudents) {
            throw new \moodle_exception('studentaccessdisabled', 'gradereport_transcript');
        }
    }
}

// Load program to verify it exists.
$program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid], '*', MUST_EXIST);

// Load user to verify they exist.
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Set page context to system (for page rendering).
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/grade/report/transcript/generate_transcript.php', [
    'programid' => $programid,
    'userid' => $userid,
    'official' => $official
]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('generatetranscript', 'gradereport_transcript'));
$PAGE->set_heading(get_string('generatetranscript', 'gradereport_transcript'));

// Handle PDF download action.
if ($action === 'download') {
    require_sesskey();

    try {
        // Create transcript generator.
        $generator = new gradereport_transcript_generator($programid, $userid);

        // If request ID provided and this is official, load completion dates.
        if ($requestid > 0 && $official) {
            $request = $DB->get_record('gradereport_transcript_requests', ['id' => $requestid]);
            if ($request && $request->requesttype === 'official') {
                $generator->set_completion_dates(
                    $request->programstartdate,
                    $request->completionstatus,
                    $request->graduationdate,
                    $request->withdrawndate
                );
            }
        }

        // Generate PDF (output mode 'D' = Download).
        $generator->generate_pdf('D', (bool)$official);

        // Script ends here - PDF is streamed to browser.
        exit;

    } catch (Exception $e) {
        // Log error.
        debugging('Error generating transcript: ' . $e->getMessage(), DEBUG_DEVELOPER);

        // Redirect with error message.
        redirect(
            new moodle_url('/grade/report/transcript/generate_transcript.php', [
                'programid' => $programid,
                'userid' => $userid
            ]),
            get_string('errorgeneratingpdf', 'gradereport_transcript'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Display page header.
echo $OUTPUT->header();

// Page heading.
echo $OUTPUT->heading(get_string('generatetranscript', 'gradereport_transcript'));

// Show student information.
echo html_writer::start_div('transcript-student-info alert alert-info');
echo html_writer::tag('h4', get_string('studentinformation', 'gradereport_transcript'));
echo html_writer::tag('p', html_writer::tag('strong', get_string('studentname', 'gradereport_transcript') . ': ') . fullname($user));
echo html_writer::tag('p', html_writer::tag('strong', get_string('studentid', 'gradereport_transcript') . ': ') . s($user->id));
echo html_writer::tag('p', html_writer::tag('strong', get_string('studentemail', 'gradereport_transcript') . ': ') . s($user->email));
echo html_writer::end_div();

// Show program information.
echo html_writer::start_div('transcript-program-info alert alert-secondary');
echo html_writer::tag('h4', get_string('programinformation', 'gradereport_transcript'));
echo html_writer::tag('p', html_writer::tag('strong', get_string('programname', 'gradereport_transcript') . ': ') . format_string($program->name));

$programtype = '';
switch ($program->type) {
    case 'hourbased':
        $programtype = get_string('programtype_hourbased', 'gradereport_transcript');
        break;
    case 'creditbased':
        $programtype = get_string('programtype_creditbased', 'gradereport_transcript');
        break;
    case 'ceu':
        $programtype = get_string('programtype_ceu', 'gradereport_transcript');
        break;
}
echo html_writer::tag('p', html_writer::tag('strong', get_string('programtype', 'gradereport_transcript') . ': ') . $programtype);
echo html_writer::end_div();

// Show transcript preview (HTML version).
try {
    $generator = new gradereport_transcript_generator($programid, $userid);
    $grades = $generator->get_student_grades();

    if (empty($grades)) {
        echo html_writer::div(
            get_string('nogradesyet', 'gradereport_transcript'),
            'alert alert-warning'
        );
    } else {
        // Display course table based on program type.
        echo html_writer::tag('h3', get_string('coursesandgrades', 'gradereport_transcript'));

        echo html_writer::start_tag('table', ['class' => 'generaltable table table-striped']);

        // Table header - varies by program type.
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');

        echo html_writer::tag('th', get_string('coursename', 'gradereport_transcript'));
        echo html_writer::tag('th', get_string('grade', 'gradereport_transcript'), ['class' => 'text-center']);

        if ($program->type === 'hourbased') {
            // Check which hour labels are active.
            $dbman = $DB->get_manager();
            $table = new xmldb_table('gradereport_transcript_programs');

            $showlabels = true;
            if ($dbman->field_exists($table, new xmldb_field('hour1label'))) {
                $programfull = $DB->get_record('gradereport_transcript_programs',
                    ['id' => $programid], 'hour1label, hour2label, hour3label');

                if (!empty(trim($programfull->hour1label))) {
                    echo html_writer::tag('th', format_string($programfull->hour1label), ['class' => 'text-center']);
                }
                if (!empty(trim($programfull->hour2label))) {
                    echo html_writer::tag('th', format_string($programfull->hour2label), ['class' => 'text-center']);
                }
                if (!empty(trim($programfull->hour3label))) {
                    echo html_writer::tag('th', format_string($programfull->hour3label), ['class' => 'text-center']);
                }
            } else {
                // Default labels.
                echo html_writer::tag('th', get_string('theoryhours', 'gradereport_transcript'), ['class' => 'text-center']);
                echo html_writer::tag('th', get_string('labhours', 'gradereport_transcript'), ['class' => 'text-center']);
                echo html_writer::tag('th', 'Clinical Hours', ['class' => 'text-center']);
            }
            echo html_writer::tag('th', get_string('totalhours', 'gradereport_transcript'), ['class' => 'text-center']);

        } else if ($program->type === 'creditbased') {
            echo html_writer::tag('th', get_string('credits', 'gradereport_transcript'), ['class' => 'text-center']);
            echo html_writer::tag('th', get_string('qualitypoints', 'gradereport_transcript'), ['class' => 'text-center']);

        } else if ($program->type === 'ceu') {
            echo html_writer::tag('th', 'CEU Value', ['class' => 'text-center']);
        }

        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');

        // Table body - course rows.
        echo html_writer::start_tag('tbody');

        $totaltheory = 0;
        $totallab = 0;
        $totalclinical = 0;
        $grandtotal = 0;
        $totalcredits = 0;
        $totalpoints = 0;
        $totalceu = 0;

        require_once(__DIR__ . '/classes/grade_calculator.php');
        $calculator = new gradereport_transcript_grade_calculator();

        foreach ($grades as $coursedata) {
            $mapping = $coursedata->mapping;
            $course = $coursedata->course;

            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', $course->shortname . ' - ' . $course->fullname);
            echo html_writer::tag('td', $coursedata->gradeletter ?? 'N/A', ['class' => 'text-center']);

            if ($program->type === 'hourbased') {
                $rowtotal = $mapping->theoryhours + $mapping->labhours + $mapping->clinicalhours;

                if ($showlabels && isset($programfull)) {
                    if (!empty(trim($programfull->hour1label))) {
                        echo html_writer::tag('td', number_format($mapping->theoryhours, 1), ['class' => 'text-center']);
                        $totaltheory += $mapping->theoryhours;
                    }
                    if (!empty(trim($programfull->hour2label))) {
                        echo html_writer::tag('td', number_format($mapping->labhours, 1), ['class' => 'text-center']);
                        $totallab += $mapping->labhours;
                    }
                    if (!empty(trim($programfull->hour3label))) {
                        echo html_writer::tag('td', number_format($mapping->clinicalhours, 1), ['class' => 'text-center']);
                        $totalclinical += $mapping->clinicalhours;
                    }
                } else {
                    echo html_writer::tag('td', number_format($mapping->theoryhours, 1), ['class' => 'text-center']);
                    echo html_writer::tag('td', number_format($mapping->labhours, 1), ['class' => 'text-center']);
                    echo html_writer::tag('td', number_format($mapping->clinicalhours, 1), ['class' => 'text-center']);
                    $totaltheory += $mapping->theoryhours;
                    $totallab += $mapping->labhours;
                    $totalclinical += $mapping->clinicalhours;
                }
                echo html_writer::tag('td', number_format($rowtotal, 1), ['class' => 'text-center']);
                $grandtotal += $rowtotal;

            } else if ($program->type === 'creditbased') {
                $gradepoints = $calculator->letter_to_gpa($coursedata->gradeletter ?? '');
                $qualitypoints = $gradepoints * $mapping->credits;

                echo html_writer::tag('td', number_format($mapping->credits, 1), ['class' => 'text-center']);
                echo html_writer::tag('td', number_format($qualitypoints, 2), ['class' => 'text-center']);

                $totalcredits += $mapping->credits;
                $totalpoints += $qualitypoints;

            } else if ($program->type === 'ceu') {
                echo html_writer::tag('td', number_format($mapping->ceuvalue, 2), ['class' => 'text-center']);
                $totalceu += $mapping->ceuvalue;
            }

            echo html_writer::end_tag('tr');
        }

        // Totals row.
        echo html_writer::start_tag('tr', ['class' => 'font-weight-bold']);
        echo html_writer::tag('td', get_string('total', 'core'), ['class' => 'text-right']);
        echo html_writer::tag('td', '', ['class' => 'text-center']);

        if ($program->type === 'hourbased') {
            if ($showlabels && isset($programfull)) {
                if (!empty(trim($programfull->hour1label))) {
                    echo html_writer::tag('td', number_format($totaltheory, 1), ['class' => 'text-center']);
                }
                if (!empty(trim($programfull->hour2label))) {
                    echo html_writer::tag('td', number_format($totallab, 1), ['class' => 'text-center']);
                }
                if (!empty(trim($programfull->hour3label))) {
                    echo html_writer::tag('td', number_format($totalclinical, 1), ['class' => 'text-center']);
                }
            } else {
                echo html_writer::tag('td', number_format($totaltheory, 1), ['class' => 'text-center']);
                echo html_writer::tag('td', number_format($totallab, 1), ['class' => 'text-center']);
                echo html_writer::tag('td', number_format($totalclinical, 1), ['class' => 'text-center']);
            }
            echo html_writer::tag('td', number_format($grandtotal, 1), ['class' => 'text-center']);

        } else if ($program->type === 'creditbased') {
            echo html_writer::tag('td', number_format($totalcredits, 1), ['class' => 'text-center']);
            echo html_writer::tag('td', number_format($totalpoints, 2), ['class' => 'text-center']);

        } else if ($program->type === 'ceu') {
            echo html_writer::tag('td', number_format($totalceu, 2), ['class' => 'text-center']);
        }

        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');

        // Show GPA for credit-based programs.
        if ($program->type === 'creditbased' && $totalcredits > 0) {
            $gpa = $totalpoints / $totalcredits;
            echo html_writer::div(
                html_writer::tag('h4', get_string('cumulativegpa', 'gradereport_transcript') . ': ' . number_format($gpa, 2)),
                'alert alert-success text-center'
            );
        }
    }

} catch (Exception $e) {
    echo html_writer::div(
        get_string('errorloadingprogram', 'gradereport_transcript') . ': ' . $e->getMessage(),
        'alert alert-danger'
    );
}

// Download buttons.
echo html_writer::start_div('transcript-download-buttons mt-4 mb-4');

// Unofficial transcript button.
$unofficialurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
    'programid' => $programid,
    'userid' => $userid,
    'official' => 0,
    'action' => 'download',
    'sesskey' => sesskey()
]);
echo html_writer::link(
    $unofficialurl,
    get_string('downloadunofficial', 'gradereport_transcript'),
    ['class' => 'btn btn-secondary btn-lg mr-2']
);

// Official transcript button (only for site admins and managers).
if (is_siteadmin() || has_capability('gradereport/transcript:manage', context_system::instance())) {
    $officialurl = new moodle_url('/grade/report/transcript/generate_transcript.php', [
        'programid' => $programid,
        'userid' => $userid,
        'official' => 1,
        'action' => 'download',
        'sesskey' => sesskey()
    ]);
    echo html_writer::link(
        $officialurl,
        get_string('downloadofficial', 'gradereport_transcript'),
        ['class' => 'btn btn-primary btn-lg']
    );
}

echo html_writer::end_div();

// Display footer.
echo $OUTPUT->footer();
