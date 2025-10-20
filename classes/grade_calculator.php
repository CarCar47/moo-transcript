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
 * Grade calculator class for GPA calculations
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Grade calculator class
 *
 * Handles GPA calculations, letter grade to GPA point conversion,
 * and weighted GPA calculations for credit-based and hour-based programs.
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradereport_transcript_grade_calculator {

    /**
     * Convert letter grade to GPA points (4.0 scale)
     *
     * Uses standard US grading scale:
     * - A = 4.0
     * - B = 3.0
     * - C = 2.0
     * - D = 1.0
     * - F = 0.0
     *
     * Supports plus/minus variations:
     * - A+ = 4.0 (capped at 4.0)
     * - A  = 4.0
     * - A- = 3.7
     * - B+ = 3.3
     * - B  = 3.0
     * - etc.
     *
     * @param string $letter Letter grade (A, B, C, D, F, with optional +/-)
     * @return float GPA points (0.0 - 4.0)
     */
    public function letter_to_gpa($letter) {
        if (empty($letter)) {
            return 0.0;
        }

        // Clean up the letter grade (remove whitespace, convert to uppercase).
        $letter = strtoupper(trim($letter));

        // Handle special cases (incomplete, withdrawn, etc.).
        $nongrades = ['W', 'WD', 'I', 'IP', 'N/A', 'NA', 'P', 'NP'];
        if (in_array($letter, $nongrades)) {
            return 0.0; // These don't count toward GPA.
        }

        // Standard letter grade mapping.
        $mapping = [
            'A+' => 4.0,
            'A'  => 4.0,
            'A-' => 3.7,
            'B+' => 3.3,
            'B'  => 3.0,
            'B-' => 2.7,
            'C+' => 2.3,
            'C'  => 2.0,
            'C-' => 1.7,
            'D+' => 1.3,
            'D'  => 1.0,
            'D-' => 0.7,
            'F'  => 0.0,
        ];

        // Return mapped value or 0.0 if not found.
        return $mapping[$letter] ?? 0.0;
    }

    /**
     * Calculate weighted GPA for credit-based or hour-based programs
     *
     * Formula: GPA = Sum(grade_points * weight) / Sum(weight)
     *
     * For credit-based: weight = credits
     * For hour-based: weight = total_hours
     *
     * @param array $coursedata Array of course data objects (from transcript_generator)
     * @param string $weightfield Field to use as weight ('credits', 'hours', or 'all')
     * @return float Calculated GPA (0.0 - 4.0)
     */
    public function calculate_weighted_gpa($coursedata, $weightfield = 'credits') {
        $totalpoints = 0.0;
        $totalweight = 0.0;

        foreach ($coursedata as $course) {
            // Get grade letter.
            $gradeletter = $course->gradeletter ?? '';

            // Skip non-letter grades.
            if (empty($gradeletter)) {
                continue;
            }

            // Convert letter to GPA points.
            $gradepoints = $this->letter_to_gpa($gradeletter);

            // Skip grades that don't count (W, I, etc.).
            if ($gradepoints === 0.0 && !in_array(strtoupper($gradeletter), ['F', 'F+', 'F-'])) {
                continue;
            }

            // Determine weight based on field.
            $weight = 0.0;

            if ($weightfield === 'credits') {
                $weight = $course->mapping->credits ?? 0.0;
            } else if ($weightfield === 'hours' || $weightfield === 'all') {
                // Total hours (theory + lab + clinical).
                $theoryhours = $course->mapping->theoryhours ?? 0.0;
                $labhours = $course->mapping->labhours ?? 0.0;
                $clinicalhours = $course->mapping->clinicalhours ?? 0.0;
                $weight = $theoryhours + $labhours + $clinicalhours;
            }

            // Skip if no weight.
            if ($weight <= 0) {
                continue;
            }

            // Add to totals.
            $totalpoints += ($gradepoints * $weight);
            $totalweight += $weight;
        }

        // Calculate GPA.
        if ($totalweight > 0) {
            return $totalpoints / $totalweight;
        }

        return 0.0;
    }

    /**
     * Get grade scale letters from Moodle
     *
     * Uses Moodle's built-in grade_get_letters() function to retrieve
     * the site-wide or course-specific grade scale.
     *
     * @param int $contextid Context ID (system or course)
     * @return array Array of grade letters (e.g., ['A' => 90, 'B' => 80, ...])
     */
    public function get_grade_letters($contextid = null) {
        if ($contextid === null) {
            $context = context_system::instance();
        } else {
            $context = context::instance_by_id($contextid);
        }

        return grade_get_letters($context);
    }

    /**
     * Calculate quality points for a single course
     *
     * Quality Points = Grade Points × Credits (or Hours)
     *
     * @param string $gradeletter Letter grade
     * @param float $weight Credits or hours
     * @return float Quality points
     */
    public function calculate_quality_points($gradeletter, $weight) {
        $gradepoints = $this->letter_to_gpa($gradeletter);
        return $gradepoints * $weight;
    }

    /**
     * Determine if a grade should be included in GPA calculation
     *
     * Some grades (W, I, IP, N/A) should not be included in GPA.
     * This method checks if a grade is valid for GPA calculation.
     *
     * @param string $gradeletter Letter grade
     * @return bool True if grade should be included in GPA
     */
    public function is_grade_valid_for_gpa($gradeletter) {
        if (empty($gradeletter)) {
            return false;
        }

        $letter = strtoupper(trim($gradeletter));

        // Grades that don't count toward GPA.
        $excluded = ['W', 'WD', 'I', 'IP', 'N/A', 'NA', 'P', 'NP'];

        return !in_array($letter, $excluded);
    }

    /**
     * Get human-readable explanation of a letter grade
     *
     * @param string $gradeletter Letter grade
     * @return string Explanation (e.g., "A = Excellent (4.0)")
     */
    public function get_grade_explanation($gradeletter) {
        $letter = strtoupper(trim($gradeletter));
        $gpa = $this->letter_to_gpa($letter);

        $descriptions = [
            'A+' => 'Outstanding',
            'A'  => 'Excellent',
            'A-' => 'Excellent',
            'B+' => 'Very Good',
            'B'  => 'Good',
            'B-' => 'Good',
            'C+' => 'Satisfactory',
            'C'  => 'Satisfactory',
            'C-' => 'Satisfactory',
            'D+' => 'Passing',
            'D'  => 'Passing',
            'D-' => 'Passing',
            'F'  => 'Failing',
            'W'  => 'Withdrawn (no credit)',
            'I'  => 'Incomplete (no credit)',
            'IP' => 'In Progress (no credit)',
            'P'  => 'Pass (no GPA)',
            'NP' => 'No Pass (no GPA)',
        ];

        $description = $descriptions[$letter] ?? 'Unknown';

        if ($gpa > 0) {
            return "{$letter} = {$description} ({$gpa})";
        } else {
            return "{$letter} = {$description}";
        }
    }

    /**
     * Calculate cumulative GPA from multiple semesters/terms
     *
     * This is useful for future enhancements where transcripts
     * might show term-by-term breakdown.
     *
     * @param array $termdata Array of term data, each with coursedata
     * @param string $weightfield Weight field to use
     * @return float Cumulative GPA
     */
    public function calculate_cumulative_gpa($termdata, $weightfield = 'credits') {
        $allcourses = [];

        // Flatten all courses from all terms.
        foreach ($termdata as $term) {
            if (isset($term['courses']) && is_array($term['courses'])) {
                $allcourses = array_merge($allcourses, $term['courses']);
            }
        }

        // Calculate weighted GPA across all courses.
        return $this->calculate_weighted_gpa($allcourses, $weightfield);
    }

    /**
     * Get GPA interpretation message
     *
     * Provides context for what a GPA means (e.g., Dean's List, Academic Warning).
     *
     * @param float $gpa GPA value
     * @return string Interpretation message
     */
    public function get_gpa_interpretation($gpa) {
        if ($gpa >= 3.75) {
            return 'Summa Cum Laude (Highest Honors)';
        } else if ($gpa >= 3.5) {
            return 'Magna Cum Laude (High Honors)';
        } else if ($gpa >= 3.25) {
            return 'Cum Laude (Honors)';
        } else if ($gpa >= 3.0) {
            return 'Dean\'s List';
        } else if ($gpa >= 2.0) {
            return 'Good Academic Standing';
        } else if ($gpa >= 1.5) {
            return 'Academic Warning';
        } else {
            return 'Academic Probation';
        }
    }
}
