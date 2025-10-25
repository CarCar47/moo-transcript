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
 * Transfer credit form
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Transfer credit form
 *
 * Form for adding/editing transfer credit entries.
 * Follows pattern from program_form.php and course_form.php.
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transfer_credit_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Custom data passed from management page.
        $programid = $this->_customdata['programid'] ?? 0;
        $userid = $this->_customdata['userid'] ?? 0;

        // Load program to determine type (credit-based vs hour-based).
        if (empty($programid)) {
            throw new \moodle_exception('invalidprogramid', 'gradereport_transcript');
        }
        $program = $DB->get_record('gradereport_transcript_programs', ['id' => $programid], '*', MUST_EXIST);

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);
        $mform->setDefault('programid', $programid);

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $userid);

        // Section: Course Information.
        $mform->addElement('header', 'courseinfoheader', get_string('courseinformation', 'gradereport_transcript'));

        // Course code (required).
        $mform->addElement('text', 'coursecode', get_string('coursecode', 'gradereport_transcript'), ['size' => 20]);
        $mform->setType('coursecode', PARAM_TEXT);
        $mform->addRule('coursecode', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('coursecode', 'coursecode', 'gradereport_transcript');

        // Course name (required).
        $mform->addElement('text', 'coursename', get_string('coursename', 'gradereport_transcript'), ['size' => 50]);
        $mform->setType('coursename', PARAM_TEXT);
        $mform->addRule('coursename', get_string('required'), 'required', null, 'client');

        // Originating institution (optional).
        $mform->addElement('text', 'institution', get_string('institution', 'gradereport_transcript'), ['size' => 50]);
        $mform->setType('institution', PARAM_TEXT);
        $mform->addHelpButton('institution', 'institution', 'gradereport_transcript');

        // Section: Grading Information.
        $mform->addElement('header', 'gradeinfoheader', get_string('gradeinformation', 'gradereport_transcript'));

        // Letter grade (optional).
        $mform->addElement('text', 'grade', get_string('grade', 'gradereport_transcript'), ['size' => 5]);
        $mform->setType('grade', PARAM_TEXT);
        $mform->addHelpButton('grade', 'transfergrade', 'gradereport_transcript');

        // Transfer symbol (optional - defaults to "T").
        $mform->addElement('text', 'transfersymbol', get_string('transfersymbol', 'gradereport_transcript'), ['size' => 5]);
        $mform->setType('transfersymbol', PARAM_TEXT);
        $mform->setDefault('transfersymbol', 'T');
        $mform->addHelpButton('transfersymbol', 'transfersymbol', 'gradereport_transcript');

        // Credits or Hours (depending on program type).
        if ($program->type === 'creditbased') {
            $mform->addElement('float', 'credits', get_string('credits', 'gradereport_transcript'));
            $mform->setDefault('credits', 0);
            $mform->addHelpButton('credits', 'transfercredits', 'gradereport_transcript');
        } else if ($program->type === 'hourbased') {
            $mform->addElement('float', 'hours', get_string('totalhours', 'gradereport_transcript'));
            $mform->setDefault('hours', 0);
            $mform->addHelpButton('hours', 'transferhours', 'gradereport_transcript');
        }

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

        // Validate course code is not empty.
        if (empty(trim($data['coursecode']))) {
            $errors['coursecode'] = get_string('required');
        }

        // Validate course name is not empty.
        if (empty(trim($data['coursename']))) {
            $errors['coursename'] = get_string('required');
        }

        // Validate credits/hours are non-negative.
        if (isset($data['credits']) && $data['credits'] < 0) {
            $errors['credits'] = get_string('valuemustbepositive', 'gradereport_transcript');
        }
        if (isset($data['hours']) && $data['hours'] < 0) {
            $errors['hours'] = get_string('valuemustbepositive', 'gradereport_transcript');
        }

        return $errors;
    }
}
