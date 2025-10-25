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
 * Symbol notation form
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Symbol notation form
 *
 * Form for adding/editing symbol notations (W, I, T, P, AU, IP, etc.).
 * Allows schools to customize their transcript symbols and meanings.
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class symbol_form extends \moodleform {

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

        // Section: Symbol Definition.
        $mform->addElement('header', 'symbolheader', get_string('symboldefinition', 'gradereport_transcript'));

        // Symbol (e.g., W, I, T, P, AU, IP).
        $mform->addElement('text', 'symbol', get_string('symbol', 'gradereport_transcript'), ['size' => 10]);
        $mform->setType('symbol', PARAM_TEXT);
        $mform->addRule('symbol', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('symbol', 'symbol', 'gradereport_transcript');

        // Meaning (full description).
        $mform->addElement('textarea', 'meaning', get_string('meaning', 'gradereport_transcript'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('meaning', PARAM_TEXT);
        $mform->addRule('meaning', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('meaning', 'meaning', 'gradereport_transcript');

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

        // Validate symbol is not empty.
        if (empty(trim($data['symbol']))) {
            $errors['symbol'] = get_string('required');
        }

        // Validate meaning is not empty.
        if (empty(trim($data['meaning']))) {
            $errors['meaning'] = get_string('required');
        }

        return $errors;
    }
}
