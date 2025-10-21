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
 * Pricing configuration form
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for configuring transcript pricing per school
 */
class pricing_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $customdata = $this->_customdata;
        $schoolid = $customdata['schoolid'] ?? 0;

        // Form header.
        $mform->addElement('header', 'pricingheader', get_string('pricingconfiguration', 'gradereport_transcript'));

        // School ID (hidden field - REQUIRED for saving).
        $mform->addElement('hidden', 'schoolid', $schoolid);
        $mform->setType('schoolid', PARAM_INT);

        // School name (display only).
        if ($schoolid > 0) {
            $school = $DB->get_record('gradereport_transcript_schools', ['id' => $schoolid], '*', MUST_EXIST);
            $mform->addElement('static', 'schoolname', get_string('school', 'gradereport_transcript'), format_string($school->name));
        }

        // First official transcript free checkbox.
        $mform->addElement('advcheckbox', 'firstfree',
            get_string('firstfree', 'gradereport_transcript'),
            get_string('yes'),
            array(),
            array(0, 1));
        $mform->addHelpButton('firstfree', 'firstfree', 'gradereport_transcript');
        $mform->setDefault('firstfree', 1);

        // Official transcript price.
        $mform->addElement('text', 'officialprice',
            get_string('officialpricelabel', 'gradereport_transcript'),
            ['size' => 10]);
        $mform->setType('officialprice', PARAM_FLOAT);
        $mform->addRule('officialprice', get_string('required'), 'required', null, 'client');
        $mform->addRule('officialprice', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addHelpButton('officialprice', 'officialprice', 'gradereport_transcript');
        $mform->setDefault('officialprice', '0.00');

        // Unofficial transcript price.
        $mform->addElement('text', 'unofficialprice',
            get_string('unofficialpricelabel', 'gradereport_transcript'),
            ['size' => 10]);
        $mform->setType('unofficialprice', PARAM_FLOAT);
        $mform->addRule('unofficialprice', get_string('required'), 'required', null, 'client');
        $mform->addRule('unofficialprice', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $mform->addHelpButton('unofficialprice', 'unofficialprice', 'gradereport_transcript');
        $mform->setDefault('unofficialprice', '0.00');

        // Load existing pricing data if editing.
        if ($schoolid > 0) {
            $pricing = $DB->get_record('gradereport_transcript_pricing', ['schoolid' => $schoolid]);
            if ($pricing) {
                $mform->setDefault('firstfree', $pricing->firstfree);
                $mform->setDefault('officialprice', format_float($pricing->officialprice, 2));
                $mform->setDefault('unofficialprice', format_float($pricing->unofficialprice, 2));
            }
        }

        // Action buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Form validation
     *
     * @param array $data Form data
     * @param array $files Form files
     * @return array Errors array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // Validate schoolid is provided.
        if (empty($data['schoolid']) || $data['schoolid'] <= 0) {
            $errors['schoolid'] = get_string('error:schoolrequired', 'gradereport_transcript');
        }

        // Validate prices are non-negative.
        if (isset($data['officialprice']) && $data['officialprice'] < 0) {
            $errors['officialprice'] = get_string('error:negativevalue', 'gradereport_transcript');
        }

        if (isset($data['unofficialprice']) && $data['unofficialprice'] < 0) {
            $errors['unofficialprice'] = get_string('error:negativevalue', 'gradereport_transcript');
        }

        return $errors;
    }
}
