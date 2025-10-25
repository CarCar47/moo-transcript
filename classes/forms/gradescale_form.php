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
 * Grading scale form
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Grading scale form
 *
 * Form for adding/editing grading scale rows.
 * Allows schools to customize their grading scale (A-F mapping).
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradescale_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        // Custom data passed from management page.
        $schoolid = $this->_customdata['schoolid'] ?? 0;

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'schoolid');
        $mform->setType('schoolid', PARAM_INT);
        $mform->setDefault('schoolid', $schoolid);

        // Section: Grade Scale Definition.
        $mform->addElement('header', 'gradescaleheader', get_string('gradescaledefinition', 'gradereport_transcript'));

        // Letter grade (e.g., A, B, C, D, F).
        $mform->addElement('text', 'lettergrade', get_string('lettergrade', 'gradereport_transcript'), ['size' => 5]);
        $mform->setType('lettergrade', PARAM_TEXT);
        $mform->addRule('lettergrade', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('lettergrade', 'lettergrade', 'gradereport_transcript');

        // Percentage range (minimum).
        $mform->addElement('float', 'minpercentage', get_string('minpercentage', 'gradereport_transcript'));
        $mform->addRule('minpercentage', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('minpercentage', 'minpercentage', 'gradereport_transcript');

        // Percentage range (maximum).
        $mform->addElement('float', 'maxpercentage', get_string('maxpercentage', 'gradereport_transcript'));
        $mform->addRule('maxpercentage', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('maxpercentage', 'maxpercentage', 'gradereport_transcript');

        // Grade points (e.g., 4.0, 3.0, 2.0).
        $mform->addElement('float', 'gradepoints', get_string('gradepoints', 'gradereport_transcript'));
        $mform->addRule('gradepoints', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('gradepoints', 'gradepoints', 'gradereport_transcript');

        // Quality descriptor (e.g., Excellent, Good, Satisfactory).
        $mform->addElement('text', 'quality', get_string('quality', 'gradereport_transcript'), ['size' => 30]);
        $mform->setType('quality', PARAM_TEXT);
        $mform->addHelpButton('quality', 'quality', 'gradereport_transcript');

        // Sort order.
        $mform->addElement('text', 'sortorder', get_string('sortorder', 'gradereport_transcript'), ['size' => 5]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);
        $mform->addHelpButton('sortorder', 'sortorder', 'gradereport_transcript');

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Form validation
     *
     * @param array $data Form data
     * @param array $files Form files
     * @return array Errors
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // Validate letter grade is not empty.
        if (empty(trim($data['lettergrade']))) {
            $errors['lettergrade'] = get_string('required');
        }

        // Validate percentage range.
        if ($data['minpercentage'] < 0 || $data['minpercentage'] > 100) {
            $errors['minpercentage'] = get_string('percentagerange', 'gradereport_transcript');
        }
        if ($data['maxpercentage'] < 0 || $data['maxpercentage'] > 100) {
            $errors['maxpercentage'] = get_string('percentagerange', 'gradereport_transcript');
        }
        if ($data['minpercentage'] > $data['maxpercentage']) {
            $errors['minpercentage'] = get_string('minmustbelessmax', 'gradereport_transcript');
        }

        // Validate grade points are non-negative.
        if ($data['gradepoints'] < 0) {
            $errors['gradepoints'] = get_string('valuemustbepositive', 'gradereport_transcript');
        }

        return $errors;
    }
}
