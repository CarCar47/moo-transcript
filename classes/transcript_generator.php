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
 * Transcript PDF generator class
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

/**
 * Transcript generator class
 *
 * Generates academic transcripts and CEU certificates in PDF format.
 * Fetches student grades from Moodle gradebook, calculates GPA, and
 * creates professionally formatted PDF documents.
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradereport_transcript_generator {

    /** @var int Program ID */
    protected $programid;

    /** @var int User ID */
    protected $userid;

    /** @var stdClass Program record */
    protected $program;

    /** @var stdClass School record */
    protected $school;

    /** @var stdClass User record */
    protected $user;

    /** @var array Course mappings */
    protected $coursemappings;

    /** @var array Student grades */
    protected $grades;

    /** @var gradereport_transcript_grade_calculator Grade calculator instance */
    protected $gradecalculator;

    /** @var string Verification code for official transcripts */
    protected $verificationcode;

    /** @var string|null Temporary path to school logo file */
    protected $logotemppath = null;

    /** @var int|null Program start date (Unix timestamp) */
    protected $programstartdate = null;

    /** @var string|null Completion status (graduated or withdrawn) */
    protected $completionstatus = null;

    /** @var int|null Graduation date (Unix timestamp) */
    protected $graduationdate = null;

    /** @var int|null Withdrawn date (Unix timestamp) */
    protected $withdrawndate = null;

    /**
     * Constructor
     *
     * @param int $programid Program ID
     * @param int $userid User ID
     * @throws moodle_exception If program or user not found
     */
    public function __construct($programid, $userid) {
        global $DB;

        $this->programid = $programid;
        $this->userid = $userid;

        // Load program with dynamic column detection.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('gradereport_transcript_programs');

        $columns = 'id, schoolid, categoryid, name, type, pdftemplate, gradescaleid, timecreated, timemodified';

        if ($dbman->field_exists($table, new xmldb_field('hour1label'))) {
            $columns .= ', hour1label, hour2label, hour3label';
        }

        $this->program = $DB->get_record('gradereport_transcript_programs',
            ['id' => $programid], $columns, MUST_EXIST);

        // Add defensive defaults for hour labels if columns don't exist.
        if (!property_exists($this->program, 'hour1label')) {
            $this->program->hour1label = 'Theory Hours';
            $this->program->hour2label = 'Lab Hours';
            $this->program->hour3label = 'Clinical Hours';
        }

        // Load school.
        $this->school = $DB->get_record('gradereport_transcript_schools',
            ['id' => $this->program->schoolid], '*', MUST_EXIST);

        // Load user.
        $this->user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // Initialize grade calculator.
        require_once(__DIR__ . '/grade_calculator.php');
        $this->gradecalculator = new gradereport_transcript_grade_calculator();
    }

    /**
     * Set program completion dates (for official transcripts)
     *
     * @param int|null $startdate Program start date (Unix timestamp)
     * @param string|null $status Completion status (graduated or withdrawn)
     * @param int|null $graduationdate Graduation date (Unix timestamp)
     * @param int|null $withdrawndate Withdrawn date (Unix timestamp)
     */
    public function set_completion_dates($startdate, $status, $graduationdate, $withdrawndate) {
        $this->programstartdate = $startdate;
        $this->completionstatus = $status;
        $this->graduationdate = $graduationdate;
        $this->withdrawndate = $withdrawndate;
    }

    /**
     * Get student information
     *
     * @return stdClass Student information object
     */
    public function get_student_info() {
        return (object)[
            'fullname' => fullname($this->user),
            'firstname' => $this->user->firstname,
            'lastname' => $this->user->lastname,
            'email' => $this->user->email,
            'userid' => $this->user->id,
            'username' => $this->user->username,
        ];
    }

    /**
     * Get course mappings for this program
     *
     * @return array Course mappings
     */
    protected function get_course_mappings() {
        global $DB;

        if (!isset($this->coursemappings)) {
            // Get course mappings with dynamic column detection.
            $dbman = $DB->get_manager();
            $table = new xmldb_table('gradereport_transcript_courses');

            $columns = 'id, programid, courseid, sortorder, theoryhours, labhours, credits, ceuvalue, timecreated, timemodified';

            if ($dbman->field_exists($table, new xmldb_field('clinicalhours'))) {
                $columns .= ', clinicalhours';
            }

            $sql = "SELECT $columns
                      FROM {gradereport_transcript_courses}
                     WHERE programid = ?
                  ORDER BY sortorder ASC, id ASC";

            $mappings = $DB->get_records_sql($sql, [$this->programid]);

            // Add defensive defaults for clinicalhours.
            foreach ($mappings as $mapping) {
                if (!property_exists($mapping, 'clinicalhours')) {
                    $mapping->clinicalhours = 0;
                }
            }

            $this->coursemappings = $mappings;
        }

        return $this->coursemappings;
    }

    /**
     * Get transfer credits for this student and program
     *
     * Retrieves custom transfer credit entries from the database.
     * Transfer credits are displayed ABOVE institutional courses on transcripts.
     *
     * @return array Array of transfer credit course data
     */
    protected function get_transfer_credits() {
        global $DB;

        $sql = "SELECT *
                  FROM {gradereport_transcript_transfer}
                 WHERE programid = ? AND userid = ?
              ORDER BY sortorder ASC, id ASC";

        $transfers = $DB->get_records_sql($sql, [$this->programid, $this->userid]);

        $coursedata = [];
        foreach ($transfers as $transfer) {
            // Format grade with transfer symbol (e.g., "A T").
            $gradeletter = trim($transfer->grade);
            if (!empty($transfer->transfersymbol)) {
                $gradeletter .= ' ' . $transfer->transfersymbol;
            }

            $coursedata[] = (object)[
                'mapping' => $transfer,
                'course' => (object)[
                    'shortname' => $transfer->coursecode,
                    'fullname' => $transfer->coursename
                ],
                'gradeletter' => $gradeletter,
                'gradevalue' => null,
                'gradepercentage' => null,
                'sortorder' => $transfer->sortorder,
                'istransfer' => true,
            ];
        }

        return $coursedata;
    }

    /**
     * Get student grades for all mapped courses
     *
     * Uses Moodle's official Gradebook API (grade_get_course_grade) to retrieve
     * final course grades. This method handles grade overrides, hidden grades,
     * locked grades, and automatically recalculates stale grades.
     *
     * Now includes transfer credits FIRST, followed by institutional courses.
     *
     * @return array Array of course data with grades (transfer + institutional)
     */
    public function get_student_grades() {
        global $DB, $CFG;

        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php'); // For grade_get_gradable_activities().

        if (!isset($this->grades)) {
            // Get transfer credits FIRST.
            $transfercredits = $this->get_transfer_credits();

            // Get institutional courses.
            $mappings = $this->get_course_mappings();
            $coursedata = [];

            foreach ($mappings as $mapping) {
                $course = $DB->get_record('course', ['id' => $mapping->courseid], '*', IGNORE_MISSING);

                if (!$course) {
                    continue; // Skip if course doesn't exist.
                }

                $gradevalue = null;
                $gradeletter = null;
                $gradepercentage = null;

                // Check if course has gradeable activities BEFORE fetching grade.
                // This prevents empty courses from incorrectly aggregating to 100% ("A").
                // Per Moodle best practices: only show grades for courses with actual gradeable content.
                $gradable_activities = grade_get_gradable_activities($course->id);

                if (!empty($gradable_activities)) {
                    // Course has gradeable content - proceed with grade fetching.

                    // Check if grade needs recalculation before fetching.
                    $gradeitem = grade_item::fetch([
                        'courseid' => $course->id,
                        'itemtype' => 'course'
                    ]);

                    if ($gradeitem && $gradeitem->needsupdate) {
                        // Force recalculation to ensure fresh, accurate grades.
                        // This is critical for transcripts to show current grades.
                        grade_regrade_final_grades($course->id);
                    }

                    // Use Moodle's official API to get course grade.
                    // This handles all gradebook logic: overrides, hidden grades, aggregation, etc.
                    $coursegrade = grade_get_course_grade($this->userid, $course->id);

                    if ($coursegrade && isset($coursegrade->grade) && $coursegrade->grade !== null) {
                        $gradevalue = $coursegrade->grade;

                        // Re-fetch grade item for letter formatting (after potential recalculation).
                        $gradeitem = grade_item::fetch([
                            'courseid' => $course->id,
                            'itemtype' => 'course'
                        ]);

                        if ($gradeitem) {
                            // Get letter grade using Moodle's grade formatter.
                            $gradeletter = grade_format_gradevalue(
                                $gradevalue,
                                $gradeitem,
                                true,
                                GRADE_DISPLAY_TYPE_LETTER
                            );

                            // Calculate percentage.
                            if ($gradeitem->grademax > 0) {
                                $gradepercentage = ($gradevalue / $gradeitem->grademax) * 100;
                            }
                        }
                    }
                }
                // If no gradeable activities, leave gradevalue/gradeletter/gradepercentage as NULL (displays as "N/A").

                $coursedata[] = (object)[
                    'mapping' => $mapping,
                    'course' => $course,
                    'gradevalue' => $gradevalue,
                    'gradeletter' => $gradeletter,
                    'gradepercentage' => $gradepercentage,
                    'sortorder' => $mapping->sortorder,
                    'istransfer' => false,
                ];
            }

            // Merge transfer credits FIRST, then institutional courses.
            $this->grades = array_merge($transfercredits, $coursedata);
        }

        return $this->grades;
    }

    /**
     * Generate PDF transcript
     *
     * @param string $outputmode Output mode: 'D' for download, 'I' for inline, 'S' for string
     * @param bool $official Whether this is an official transcript
     * @return mixed PDF output or string
     */
    public function generate_pdf($outputmode = 'D', $official = false) {
        // Create PDF instance.
        $pdf = new pdf('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information.
        $pdf->SetCreator('Moodle - Academic Transcripts Plugin');
        $pdf->SetAuthor($this->school->name);
        $pdf->SetTitle('Academic Transcript - ' . fullname($this->user));

        // Disable header and footer.
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins.
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Add page.
        $pdf->AddPage();

        // Generate content based on program type.
        switch ($this->program->type) {
            case 'hourbased':
                $this->generate_hourbased_content($pdf, $official);
                break;
            case 'creditbased':
                $this->generate_creditbased_content($pdf, $official);
                break;
            case 'ceu':
                $this->generate_ceu_content($pdf, $official);
                break;
            default:
                throw new moodle_exception('invalidprogramtype', 'gradereport_transcript', '', $this->program->type);
        }

        // Generate filename.
        $filename = $this->generate_filename($official);

        // Output PDF.
        return $pdf->Output($filename, $outputmode);
    }

    /**
     * Generate hour-based transcript content
     *
     * @param pdf $pdf PDF object
     * @param bool $official Whether this is official
     */
    protected function generate_hourbased_content($pdf, $official) {
        // Header section.
        $this->add_header($pdf, $official);

        // Student information section.
        $this->add_student_info($pdf);

        // Program information.
        $this->add_program_info($pdf);

        // Course table with hours.
        $this->add_hourbased_courses_table($pdf);

        // Footer with verification.
        $this->add_footer($pdf, $official);

        // Academic information page (page 2) - only for official transcripts.
        if ($official) {
            $this->add_academic_info_page($pdf);
        }
    }

    /**
     * Generate credit-based transcript content
     *
     * @param pdf $pdf PDF object
     * @param bool $official Whether this is official
     */
    protected function generate_creditbased_content($pdf, $official) {
        // Header section.
        $this->add_header($pdf, $official);

        // Student information section.
        $this->add_student_info($pdf);

        // Program information.
        $this->add_program_info($pdf);

        // Course table with credits and GPA.
        $this->add_creditbased_courses_table($pdf);

        // Footer with verification.
        $this->add_footer($pdf, $official);

        // Academic information page (page 2) - only for official transcripts.
        if ($official) {
            $this->add_academic_info_page($pdf);
        }
    }

    /**
     * Generate CEU certificate content
     *
     * @param pdf $pdf PDF object
     * @param bool $official Whether this is official
     */
    protected function generate_ceu_content($pdf, $official) {
        // Header section.
        $this->add_header($pdf, $official);

        // Student information section.
        $this->add_student_info($pdf);

        // Program information.
        $this->add_program_info($pdf);

        // CEU courses table.
        $this->add_ceu_courses_table($pdf);

        // Footer with verification.
        $this->add_footer($pdf, $official);

        // Academic information page (page 2) - only for official transcripts.
        if ($official) {
            $this->add_academic_info_page($pdf);
        }
    }

    /**
     * Add header section to PDF
     *
     * @param pdf $pdf PDF object
     * @param bool $official Whether this is official
     */
    protected function add_header($pdf, $official) {
        // Add school logo at top-left (if available).
        // Position: X=15mm (left margin), Y=15mm (top margin)
        // Maximum size: 20mm width × 12mm height (letterhead standard)
        $logoinfo = $this->get_school_logo_path();
        if ($logoinfo !== null) {
            // Save current Y position before adding logo.
            $currenty = $pdf->GetY();

            // Calculate constrained dimensions for 20mm × 12mm max box (letterhead standard).
            // This uses manual aspect ratio calculation because TCPDF's fitbox
            // parameter is unreliable (confirmed bug - only works 79% of time).
            // By calculating which dimension to constrain and setting the other to 0,
            // we GUARANTEE the logo never exceeds the max box size.
            $dims = $this->calculate_logo_dimensions(
                $logoinfo['width'],   // Image width in pixels
                $logoinfo['height'],  // Image height in pixels
                20,                   // Max width in mm (letterhead standard)
                12                    // Max height in mm (letterhead standard)
            );

            // Add logo with calculated dimensions (one will be 0 for auto-calculate).
            // TCPDF reliably calculates the 0 dimension proportionally.
            // This GUARANTEES logo fits within 20×12mm box with perfect aspect ratio.
            $pdf->Image($logoinfo['path'], 15, 15, $dims['width'], $dims['height']);

            // Reset Y position to continue with centered school name.
            // This ensures the school name remains centered and not affected by logo.
            $pdf->SetY($currenty);
        }

        // School name (centered, not affected by logo positioning).
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $this->school->name, 0, 1, 'C');

        // School address.
        if (!empty($this->school->address)) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, $this->school->address, 0, 1, 'C');
        }

        // School contact.
        if (!empty($this->school->phone) || !empty($this->school->website)) {
            $pdf->SetFont('helvetica', '', 10);
            $contact = [];
            if (!empty($this->school->phone)) {
                $contact[] = 'Phone: ' . $this->school->phone;
            }
            if (!empty($this->school->website)) {
                $contact[] = 'Web: ' . $this->school->website;
            }
            $pdf->Cell(0, 5, implode(' | ', $contact), 0, 1, 'C');
        }

        // Document title.
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 14);
        $title = $official ? 'OFFICIAL TRANSCRIPT' : 'UNOFFICIAL TRANSCRIPT';
        $pdf->Cell(0, 10, $title, 0, 1, 'C');

        $pdf->Ln(5);
    }

    /**
     * Add student information section
     *
     * @param pdf $pdf PDF object
     */
    protected function add_student_info($pdf) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'STUDENT INFORMATION', 0, 1, 'L');

        // Row 1: Name | Start Date (if available)
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Name:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        if ($this->programstartdate !== null) {
            // Name on left, Start Date on right (2-column layout)
            $pdf->Cell(55, 6, fullname($this->user), 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(35, 6, 'Start Date:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, date('m/d/y', $this->programstartdate), 0, 1, 'L');
        } else {
            $pdf->Cell(0, 6, fullname($this->user), 0, 1, 'L');
        }

        // Row 2: Student ID | Graduation/Withdrawn Date (if available)
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Student ID:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        if ($this->completionstatus === 'graduated' && $this->graduationdate !== null) {
            // Student ID on left, Graduation Date on right
            $pdf->Cell(55, 6, $this->user->id, 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(35, 6, 'Graduation Date:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, date('m/d/y', $this->graduationdate), 0, 1, 'L');
        } else if ($this->completionstatus === 'withdrawn' && $this->withdrawndate !== null) {
            // Student ID on left, Withdrawn Date on right
            $pdf->Cell(55, 6, $this->user->id, 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(35, 6, 'Withdrawn Date:', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, date('m/d/y', $this->withdrawndate), 0, 1, 'L');
        } else {
            $pdf->Cell(0, 6, $this->user->id, 0, 1, 'L');
        }

        // Row 3: Email (always full width)
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $this->user->email, 0, 1, 'L');

        $pdf->Ln(5);
    }

    /**
     * Add program information section
     *
     * @param pdf $pdf PDF object
     */
    protected function add_program_info($pdf) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'PROGRAM INFORMATION', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 6, 'Program:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $this->program->name, 0, 1, 'L');

        $pdf->Ln(5);
    }

    /**
     * Add hour-based courses table
     *
     * @param pdf $pdf PDF object
     */
    protected function add_hourbased_courses_table($pdf) {
        $grades = $this->get_student_grades();

        // Table header.
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'COURSES AND HOURS', 0, 1, 'L');

        // Determine which hour columns to show.
        $showhour1 = !empty(trim($this->program->hour1label));
        $showhour2 = !empty(trim($this->program->hour2label));
        $showhour3 = !empty(trim($this->program->hour3label));

        // Build HTML table for better text wrapping.
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">';

        // Header row.
        $html .= '<tr style="background-color:#CCCCCC;font-weight:bold;">';
        $html .= '<th width="45%" align="left">Course</th>';
        $html .= '<th width="10%" align="center">Grade</th>';

        if ($showhour1) {
            $html .= '<th width="12%" align="center">' . htmlspecialchars($this->program->hour1label) . '</th>';
        }
        if ($showhour2) {
            $html .= '<th width="12%" align="center">' . htmlspecialchars($this->program->hour2label) . '</th>';
        }
        if ($showhour3) {
            $html .= '<th width="12%" align="center">' . htmlspecialchars($this->program->hour3label) . '</th>';
        }

        $html .= '<th width="15%" align="center">Total Hours</th>';
        $html .= '</tr>';

        // Data rows.
        $totalhour1 = 0;
        $totalhour2 = 0;
        $totalhour3 = 0;
        $grandtotal = 0;

        foreach ($grades as $coursedata) {
            $mapping = $coursedata->mapping;
            $course = $coursedata->course;

            $rowtotal = $mapping->theoryhours + $mapping->labhours + $mapping->clinicalhours;

            $coursename = htmlspecialchars($course->shortname . ' - ' . $course->fullname);

            $html .= '<tr>';
            $html .= '<td align="left">' . $coursename . '</td>';
            $html .= '<td align="center">' . ($coursedata->gradeletter ?? 'N/A') . '</td>';

            if ($showhour1) {
                $html .= '<td align="center">' . number_format($mapping->theoryhours, 1) . '</td>';
                $totalhour1 += $mapping->theoryhours;
            }
            if ($showhour2) {
                $html .= '<td align="center">' . number_format($mapping->labhours, 1) . '</td>';
                $totalhour2 += $mapping->labhours;
            }
            if ($showhour3) {
                $html .= '<td align="center">' . number_format($mapping->clinicalhours, 1) . '</td>';
                $totalhour3 += $mapping->clinicalhours;
            }

            $html .= '<td align="center">' . number_format($rowtotal, 1) . '</td>';
            $html .= '</tr>';

            $grandtotal += $rowtotal;
        }

        // Totals row.
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td align="right">TOTAL</td>';
        $html .= '<td align="center"></td>';

        if ($showhour1) {
            $html .= '<td align="center">' . number_format($totalhour1, 1) . '</td>';
        }
        if ($showhour2) {
            $html .= '<td align="center">' . number_format($totalhour2, 1) . '</td>';
        }
        if ($showhour3) {
            $html .= '<td align="center">' . number_format($totalhour3, 1) . '</td>';
        }

        $html .= '<td align="center">' . number_format($grandtotal, 1) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Write HTML table.
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(5);
    }

    /**
     * Add credit-based courses table
     *
     * @param pdf $pdf PDF object
     */
    protected function add_creditbased_courses_table($pdf) {
        $grades = $this->get_student_grades();

        // Separate transfer credits from institutional courses.
        $transfercredits = [];
        $institutionalcredits = [];
        foreach ($grades as $coursedata) {
            if (!empty($coursedata->istransfer)) {
                $transfercredits[] = $coursedata;
            } else {
                $institutionalcredits[] = $coursedata;
            }
        }

        // Calculate GPA (using school's custom scale).
        $gpa = $this->gradecalculator->calculate_weighted_gpa($grades, 'credits', $this->school->id);

        // Table header.
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'COURSES AND GRADES', 0, 1, 'L');

        // Build HTML table for better text wrapping.
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">';

        // Header row.
        $html .= '<tr style="background-color:#CCCCCC;font-weight:bold;">';
        $html .= '<th width="60%" align="left">Course</th>';
        $html .= '<th width="13%" align="center">Grade</th>';
        $html .= '<th width="13%" align="center">Credits</th>';
        $html .= '<th width="14%" align="center">Points</th>';
        $html .= '</tr>';

        // Transfer credits section.
        $transfertotalcredits = 0;
        $transfertotalpoints = 0;

        if (!empty($transfercredits)) {
            // Transfer credits section header.
            $html .= '<tr style="background-color:#EEEEEE;font-weight:bold;">';
            $html .= '<td colspan="4" align="left">TRANSFER CREDITS</td>';
            $html .= '</tr>';

            foreach ($transfercredits as $coursedata) {
                $mapping = $coursedata->mapping;
                $course = $coursedata->course;

                $gradepoints = $this->gradecalculator->letter_to_gpa($coursedata->gradeletter ?? '', $this->school->id);
                $qualitypoints = $gradepoints * $mapping->credits;

                $coursename = htmlspecialchars($course->shortname . ' - ' . $course->fullname);

                $html .= '<tr>';
                $html .= '<td align="left">' . $coursename . '</td>';
                $html .= '<td align="center">' . htmlspecialchars($coursedata->gradeletter ?? 'N/A') . '</td>';
                $html .= '<td align="center">' . number_format($mapping->credits, 1) . '</td>';
                $html .= '<td align="center">' . number_format($qualitypoints, 2) . '</td>';
                $html .= '</tr>';

                $transfertotalcredits += $mapping->credits;
                $transfertotalpoints += $qualitypoints;
            }

            // Transfer credits subtotal.
            $html .= '<tr style="font-weight:bold;background-color:#F5F5F5;">';
            $html .= '<td align="right">Transfer Credits Subtotal</td>';
            $html .= '<td align="center"></td>';
            $html .= '<td align="center">' . number_format($transfertotalcredits, 1) . '</td>';
            $html .= '<td align="center">' . number_format($transfertotalpoints, 2) . '</td>';
            $html .= '</tr>';
        }

        // Institutional credits section.
        $institutionaltotalcredits = 0;
        $institutionaltotalpoints = 0;

        if (!empty($institutionalcredits)) {
            // Institutional credits section header.
            $html .= '<tr style="background-color:#EEEEEE;font-weight:bold;">';
            $html .= '<td colspan="4" align="left">INSTITUTIONAL CREDITS</td>';
            $html .= '</tr>';

            foreach ($institutionalcredits as $coursedata) {
                $mapping = $coursedata->mapping;
                $course = $coursedata->course;

                $gradepoints = $this->gradecalculator->letter_to_gpa($coursedata->gradeletter ?? '', $this->school->id);
                $qualitypoints = $gradepoints * $mapping->credits;

                $coursename = htmlspecialchars($course->shortname . ' - ' . $course->fullname);

                $html .= '<tr>';
                $html .= '<td align="left">' . $coursename . '</td>';
                $html .= '<td align="center">' . htmlspecialchars($coursedata->gradeletter ?? 'N/A') . '</td>';
                $html .= '<td align="center">' . number_format($mapping->credits, 1) . '</td>';
                $html .= '<td align="center">' . number_format($qualitypoints, 2) . '</td>';
                $html .= '</tr>';

                $institutionaltotalcredits += $mapping->credits;
                $institutionaltotalpoints += $qualitypoints;
            }

            // Institutional credits subtotal.
            $html .= '<tr style="font-weight:bold;background-color:#F5F5F5;">';
            $html .= '<td align="right">Institutional Credits Subtotal</td>';
            $html .= '<td align="center"></td>';
            $html .= '<td align="center">' . number_format($institutionaltotalcredits, 1) . '</td>';
            $html .= '<td align="center">' . number_format($institutionaltotalpoints, 2) . '</td>';
            $html .= '</tr>';
        }

        // Grand totals row.
        $totalcredits = $transfertotalcredits + $institutionaltotalcredits;
        $totalpoints = $transfertotalpoints + $institutionaltotalpoints;

        $html .= '<tr style="font-weight:bold;background-color:#CCCCCC;">';
        $html .= '<td align="right">TOTAL CREDITS</td>';
        $html .= '<td align="center"></td>';
        $html .= '<td align="center">' . number_format($totalcredits, 1) . '</td>';
        $html .= '<td align="center">' . number_format($totalpoints, 2) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Write HTML table.
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(3);

        // GPA row.
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(90, 6, 'CUMULATIVE GPA:', 0, 0, 'R');
        $pdf->Cell(20, 6, number_format($gpa, 2), 0, 1, 'L');

        $pdf->Ln(5);
    }

    /**
     * Add CEU courses table
     *
     * @param pdf $pdf PDF object
     */
    protected function add_ceu_courses_table($pdf) {
        $grades = $this->get_student_grades();

        // Table header.
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'CONTINUING EDUCATION UNITS', 0, 1, 'L');

        // Build HTML table for better text wrapping.
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">';

        // Header row.
        $html .= '<tr style="background-color:#CCCCCC;font-weight:bold;">';
        $html .= '<th width="73%" align="left">Course</th>';
        $html .= '<th width="13%" align="center">Grade</th>';
        $html .= '<th width="14%" align="center">CEUs</th>';
        $html .= '</tr>';

        // Data rows.
        $totalceu = 0;

        foreach ($grades as $coursedata) {
            $mapping = $coursedata->mapping;
            $course = $coursedata->course;

            $coursename = htmlspecialchars($course->shortname . ' - ' . $course->fullname);

            $html .= '<tr>';
            $html .= '<td align="left">' . $coursename . '</td>';
            $html .= '<td align="center">' . ($coursedata->gradeletter ?? 'N/A') . '</td>';
            $html .= '<td align="center">' . number_format($mapping->ceuvalue, 2) . '</td>';
            $html .= '</tr>';

            $totalceu += $mapping->ceuvalue;
        }

        // Totals row.
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td align="right">TOTAL CEUs</td>';
        $html .= '<td align="center"></td>';
        $html .= '<td align="center">' . number_format($totalceu, 2) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        // Write HTML table.
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(5);
    }

    /**
     * Add footer with verification information
     *
     * @param pdf $pdf PDF object
     * @param bool $official Whether this is official
     */
    protected function add_footer($pdf, $official) {
        if ($official) {
            // Generate verification code.
            require_once(__DIR__ . '/verification_generator.php');
            $verifier = new gradereport_transcript_verification_generator();
            $documenttype = $official ? 'official' : 'unofficial';
            $this->verificationcode = $verifier->generate_and_save($this->userid, $this->programid, $documenttype);

            // Issue date and verification code on same line (compact layout).
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(90, 5, 'Issue Date: ' . date('F d, Y'), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Verification Code: ' . $this->verificationcode, 0, 1, 'R');

            // Check if signature area is enabled.
            $showsignature = get_config('gradereport_transcript', 'showsignature');
            if ($showsignature === false) {
                // Default to enabled if setting not configured.
                $showsignature = 1;
            }

            if ($showsignature) {
                $pdf->Ln(5);

                // Signature and seal side-by-side (2-column layout for compactness).
                $pdf->SetFont('helvetica', '', 8);

                // Left column: Signature area.
                $y = $pdf->GetY();
                $pdf->SetXY(15, $y);
                $pdf->Cell(85, 4, '___________________________________', 0, 1, 'L');
                $pdf->SetX(15);
                $pdf->Cell(85, 4, 'Authorized Signature', 0, 0, 'L');

                // Right column: Seal area.
                $pdf->SetXY(105, $y);
                $pdf->Cell(85, 4, 'Official Seal/Stamp:', 0, 1, 'C');
                $pdf->SetXY(115, $y + 6);
                $pdf->Cell(65, 15, '', 1, 0, 'C'); // Seal box (15mm height).
            }
        } else {
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->Cell(0, 6, 'This is an unofficial transcript. Not valid for official use.', 0, 1, 'C');
        }
    }

    /**
     * Add QR code for verification to current page (Phase 7)
     *
     * Adds a QR code at the bottom-right of the current page (page 2).
     * The QR code contains a URL to the public verification page.
     *
     * Uses TCPDF's built-in 2D barcode support for QR code generation.
     * Positioned to fit on page 2 without creating extra pages.
     *
     * @param pdf $pdf PDF object
     */
    protected function add_qr_code_to_page($pdf) {
        global $CFG;

        if (empty($this->verificationcode)) {
            return; // No verification code available.
        }

        // Create verification URL with absolute path.
        $verifyurl = $CFG->wwwroot . '/grade/report/transcript/verify.php?code=' . $this->verificationcode;

        // Position QR code at bottom right of page 2.
        // A4 page size: 210mm x 297mm, margins: 15mm
        // Usable area: 180mm x 267mm (15mm to 195mm horizontal, 15mm to 282mm vertical)
        // QR size: 25mm x 25mm (smaller to fit better)
        // Position: Bottom-right corner with 5mm padding from edges
        $size = 25;
        $x = 210 - 15 - $size - 5;  // Right edge - margin - QR size - padding = 165mm
        $y = 297 - 15 - $size - 8;  // Bottom edge - margin - QR size - label space = 249mm

        // Add QR code using TCPDF's 2D barcode method.
        $style = [
            'border' => false,
            'padding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false
        ];

        $pdf->write2DBarcode(
            $verifyurl,              // Absolute URL.
            'QRCODE,H',              // QR code with high error correction.
            $x,
            $y,
            $size,
            $size,
            $style,
            'N'
        );

        // Add label below QR code.
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($x, $y + $size + 1);
        $pdf->Cell($size, 3, get_string('scantoverify', 'gradereport_transcript'), 0, 0, 'C');
    }

    /**
     * Add academic information page (Page 2) - only for official transcripts
     *
     * @param pdf $pdf PDF object
     */
    protected function add_academic_info_page($pdf) {
        global $DB;

        // Add new page for academic information
        $pdf->AddPage();

        // Page heading
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'ACADEMIC INFORMATION', 0, 1, 'C');
        $pdf->Ln(5);

        // Section 1: Grading Scale (from database - custom per school)
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'GRADING SCALE', 0, 1, 'L');

        // Load custom grading scale from database.
        $gradescale = $DB->get_records('gradereport_transcript_gradescale',
            ['schoolid' => $this->school->id], 'sortorder ASC');

        $html = '<table border="1" cellpadding="4" cellspacing="0" style="font-size:9pt;">';
        $html .= '<tr style="background-color:#CCCCCC;font-weight:bold;">';
        $html .= '<th width="20%" align="center">Letter Grade</th>';
        $html .= '<th width="30%" align="center">Percentage Range</th>';
        $html .= '<th width="20%" align="center">Grade Points (GPA)</th>';
        $html .= '<th width="30%" align="center">Quality</th>';
        $html .= '</tr>';

        if (empty($gradescale)) {
            // Fallback to default if no custom scale exists.
            $grades = [
                ['A', '90-100%', '4.0', 'Excellent'],
                ['B', '80-89%', '3.0', 'Good'],
                ['C', '70-79%', '2.0', 'Satisfactory'],
                ['D', '60-69%', '1.0', 'Poor'],
                ['F', 'Below 60%', '0.0', 'Failing']
            ];

            foreach ($grades as $grade) {
                $html .= '<tr>';
                $html .= '<td align="center">' . $grade[0] . '</td>';
                $html .= '<td align="center">' . $grade[1] . '</td>';
                $html .= '<td align="center">' . $grade[2] . '</td>';
                $html .= '<td align="center">' . $grade[3] . '</td>';
                $html .= '</tr>';
            }
        } else {
            // Use custom grading scale from database.
            foreach ($gradescale as $row) {
                $percentagerange = number_format($row->minpercentage, 0) . '-' . number_format($row->maxpercentage, 0) . '%';

                $html .= '<tr>';
                $html .= '<td align="center">' . htmlspecialchars($row->lettergrade) . '</td>';
                $html .= '<td align="center">' . $percentagerange . '</td>';
                $html .= '<td align="center">' . number_format($row->gradepoints, 1) . '</td>';
                $html .= '<td align="center">' . htmlspecialchars($row->quality) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        // Section 2: GPA Calculation
        if ($this->program->type === 'creditbased') {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 7, 'GRADE POINT AVERAGE (GPA) CALCULATION', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(0, 5, 'GPA is calculated by dividing the total Quality Points earned by the total number of Credits attempted. Formula: GPA = Total Quality Points ÷ Total Credits. Quality Points are calculated by multiplying the Grade Points (listed above) by the number of Credits for each course.', 0, 'L');
            $pdf->Ln(3);
        }

        // Section 3: Symbols and Notations (from database - custom per school)
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'SYMBOLS AND NOTATIONS', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);

        // Load custom symbols from database.
        $symbolsdb = $DB->get_records('gradereport_transcript_symbols',
            ['schoolid' => $this->school->id], 'sortorder ASC');

        $html = '<table border="1" cellpadding="3" cellspacing="0" style="font-size:9pt;">';
        $html .= '<tr style="background-color:#CCCCCC;font-weight:bold;">';
        $html .= '<th width="15%" align="center">Symbol</th>';
        $html .= '<th width="85%" align="left">Meaning</th>';
        $html .= '</tr>';

        if (empty($symbolsdb)) {
            // Fallback to default symbols if none exist.
            $symbols = [
                ['W', 'Withdrawn - Student officially withdrew from the course'],
                ['I', 'Incomplete - Coursework not completed within the term'],
                ['T', 'Transfer Credit - Credit earned at another institution'],
                ['P', 'Pass - Credit earned in pass/fail course'],
                ['AU', 'Audit - Course taken for no credit'],
                ['IP', 'In Progress - Course currently being taken']
            ];

            foreach ($symbols as $symbol) {
                $html .= '<tr>';
                $html .= '<td align="center"><strong>' . $symbol[0] . '</strong></td>';
                $html .= '<td align="left">' . $symbol[1] . '</td>';
                $html .= '</tr>';
            }
        } else {
            // Use custom symbols from database.
            foreach ($symbolsdb as $symbolrow) {
                $html .= '<tr>';
                $html .= '<td align="center"><strong>' . htmlspecialchars($symbolrow->symbol) . '</strong></td>';
                $html .= '<td align="left">' . htmlspecialchars($symbolrow->meaning) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        // Section 4: Course Numbering System
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'COURSE NUMBERING SYSTEM', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 5, 'Courses are identified by a program code followed by a course number (e.g., FSP-201). The program code represents the subject area or program of study, and the course number indicates the level and sequence of the course within that program.', 0, 'L');
        $pdf->Ln(3);

        // Section 5: Transfer Credit Policy
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'TRANSFER CREDIT POLICY', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 5, 'The acceptance and applicability of transfer credits and hours is subject to the sole discretion of the receiving institution. This institution makes no guarantee regarding the transferability of credits earned here to other institutions. Students are advised to consult with the receiving institution regarding their specific transfer credit policies before enrolling.', 0, 'L');

        // Add school logo at bottom-left of page 2 (if available).
        // Position matches QR code vertical position but on left side.
        // Logo: Bottom-left, QR Code: Bottom-right
        $logoinfo = $this->get_school_logo_path();
        if ($logoinfo !== null) {
            // Position: X=15mm (left margin), Y=249mm (same as QR code vertical position)
            // Maximum size: 25mm width × 25mm height (matches QR code size)
            // A4 page: 210mm x 297mm, margins: 15mm
            // QR code is at X=165mm (right), Logo is at X=15mm (left)

            // Calculate constrained dimensions for 25mm × 25mm max box.
            // Uses manual aspect ratio calculation (fitbox is unreliable).
            $dims = $this->calculate_logo_dimensions(
                $logoinfo['width'],   // Image width in pixels
                $logoinfo['height'],  // Image height in pixels
                25,                   // Max width in mm
                25                    // Max height in mm
            );

            // Add logo with calculated dimensions.
            // GUARANTEES logo fits within 25×25mm box symmetrically with QR code.
            $pdf->Image($logoinfo['path'], 15, 249, $dims['width'], $dims['height']);
        }

        // Add QR code at bottom of page 2 (Phase 7 - Verification System).
        if (!empty($this->verificationcode)) {
            $this->add_qr_code_to_page($pdf);
        }
    }

    /**
     * Generate filename for PDF
     *
     * @param bool $official Whether this is official
     * @return string Filename
     */
    protected function generate_filename($official) {
        $type = $official ? 'Official' : 'Unofficial';
        $studentname = clean_filename(fullname($this->user));
        $programname = clean_filename($this->program->name);
        $date = date('Y-m-d');

        return "Transcript_{$type}_{$studentname}_{$programname}_{$date}.pdf";
    }

    /**
     * Get school logo file path and dimensions for use in PDF
     *
     * Retrieves the school logo from Moodle's File API and copies it to a temporary
     * location for use with TCPDF. Also gets image dimensions for aspect ratio calculations.
     * The temporary file is automatically cleaned up when the object is destroyed.
     *
     * Following Moodle 2025 File API best practices:
     * - Uses get_file_storage() to access File API
     * - Uses get_area_files() to retrieve files from specific file area
     * - Uses copy_content_to_temp() for temporary file creation
     * - Uses getimagesize() for dimension detection
     *
     * @return array|null Array with 'path', 'width', 'height', or null if no logo exists
     */
    protected function get_school_logo_path() {
        // Return cached info if already retrieved.
        if ($this->logotemppath !== null) {
            return $this->logotemppath;
        }

        // Get file storage instance.
        $fs = get_file_storage();

        // Get system context.
        $context = context_system::instance();

        // Retrieve logo file from File API.
        // Component: gradereport_transcript
        // File area: schoollogo
        // Item ID: school ID
        $files = $fs->get_area_files(
            $context->id,
            'gradereport_transcript',
            'schoollogo',
            $this->school->id,
            'filename',
            false  // Do not include directories.
        );

        // Check if logo file exists.
        if (empty($files)) {
            return null;  // No logo uploaded.
        }

        // Get first (and should be only) file.
        $file = reset($files);

        // Copy file content to temporary location for TCPDF.
        // TCPDF requires a file path, not a stored_file object.
        $temppath = $file->copy_content_to_temp();

        // Get image dimensions for aspect ratio calculations.
        // This is required for guaranteed logo sizing (fitbox parameter is unreliable).
        $imagesize = getimagesize($temppath);
        if ($imagesize === false) {
            // Invalid or corrupted image file.
            @unlink($temppath);  // Clean up temp file.
            return null;
        }

        // Prepare logo info array.
        $logoinfo = [
            'path' => $temppath,
            'width' => $imagesize[0],   // Width in pixels.
            'height' => $imagesize[1],  // Height in pixels.
        ];

        // Cache the info for reuse.
        $this->logotemppath = $logoinfo;

        return $logoinfo;
    }

    /**
     * Calculate logo dimensions constrained within a maximum box
     *
     * Determines which dimension (width or height) to constrain to ensure the logo
     * fits within the specified maximum dimensions while maintaining aspect ratio.
     *
     * This is the GUARANTEED method for constraining images in TCPDF when the fitbox
     * parameter is unreliable. By setting one dimension to the max and the other to 0,
     * TCPDF automatically calculates the 0 dimension proportionally.
     *
     * Algorithm (from TCPDF community best practices):
     * - Calculate aspect ratios of both image and box
     * - If box is wider than image ratio → constrain height (set width=0)
     * - If box is narrower than image ratio → constrain width (set height=0)
     * - This ensures image NEVER exceeds max_width × max_height box
     *
     * @param int $imagewidth Image width in pixels
     * @param int $imageheight Image height in pixels
     * @param float $maxwidth Maximum width in mm
     * @param float $maxheight Maximum height in mm
     * @return array Array with 'width' and 'height' (one will be 0 for auto-calculate)
     */
    protected function calculate_logo_dimensions($imagewidth, $imageheight, $maxwidth, $maxheight) {
        // Calculate aspect ratios.
        $boxratio = $maxwidth / $maxheight;
        $imageratio = $imagewidth / $imageheight;

        // Determine which dimension to constrain.
        if ($boxratio > $imageratio) {
            // Box is wider than image (tall/portrait image).
            // Constrain HEIGHT, let width auto-calculate.
            return ['width' => 0, 'height' => $maxheight];
        } else {
            // Box is narrower than image (wide/landscape image).
            // Constrain WIDTH, let height auto-calculate.
            return ['width' => $maxwidth, 'height' => 0];
        }
    }

    /**
     * Destructor
     *
     * Clean up temporary logo file if it exists.
     * Following Moodle best practices for resource cleanup.
     */
    public function __destruct() {
        // Clean up temporary logo file.
        if ($this->logotemppath !== null && is_array($this->logotemppath)) {
            if (isset($this->logotemppath['path']) && file_exists($this->logotemppath['path'])) {
                @unlink($this->logotemppath['path']);
            }
        }
    }
}
