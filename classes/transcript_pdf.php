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
 * Custom PDF class for transcript generation
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');

/**
 * Custom PDF class with header and footer for transcripts
 *
 * Extends Moodle's pdf class (which extends TCPDF) to add custom headers
 * and footers to every page of the transcript.
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradereport_transcript_pdf extends pdf {

    /** @var stdClass School information */
    public $school;

    /** @var bool Whether this is an official transcript */
    public $official;

    /** @var gradereport_transcript_generator Reference to generator for helper methods */
    public $generator;

    /**
     * Custom header for all pages
     *
     * Displays school logo, name, address, contact info, and document title
     * on every page of the transcript.
     */
    public function Header() {
        if (empty($this->school) || empty($this->generator)) {
            return; // Skip if not initialized.
        }

        // Add school logo at top-left (if available).
        $logoinfo = $this->generator->get_school_logo_path();
        if ($logoinfo !== null) {
            // Save current Y position.
            $currenty = $this->GetY();

            // Calculate constrained dimensions for 20mm × 12mm max box.
            $dims = $this->generator->calculate_logo_dimensions(
                $logoinfo['width'],
                $logoinfo['height'],
                20,
                12
            );

            // Add logo.
            $this->Image($logoinfo['path'], 15, 10, $dims['width'], $dims['height']);

            // Reset Y position.
            $this->SetY($currenty);
        }

        // School name (centered).
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 6, $this->school->name, 0, 1, 'C');

        // School address.
        if (!empty($this->school->address)) {
            $this->SetFont('helvetica', '', 9);
            $this->Cell(0, 4, $this->school->address, 0, 1, 'C');
        }

        // School contact.
        if (!empty($this->school->phone) || !empty($this->school->website)) {
            $this->SetFont('helvetica', '', 9);
            $contact = [];
            if (!empty($this->school->phone)) {
                $contact[] = 'Phone: ' . $this->school->phone;
            }
            if (!empty($this->school->website)) {
                $contact[] = 'Web: ' . $this->school->website;
            }
            $this->Cell(0, 4, implode(' | ', $contact), 0, 1, 'C');
        }

        // Document title.
        $this->SetFont('helvetica', 'B', 12);
        $title = $this->official ? 'OFFICIAL TRANSCRIPT' : 'UNOFFICIAL TRANSCRIPT';
        $this->Cell(0, 6, $title, 0, 1, 'C');
    }

    /**
     * Custom footer for all pages
     *
     * Displays page numbers in "Page X of Y" format on every page.
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $pagenumber = 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
        $this->Cell(0, 10, $pagenumber, 0, 0, 'C');
    }
}
