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
 * Transcript request form
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for requesting official/unofficial transcripts
 */
class transcript_request_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $USER;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        // Form header.
        $mform->addElement('header', 'requestheader', get_string('requesttranscript', 'gradereport_transcript'));

        // Program selection (required).
        $programs = $customdata['programs'] ?? [];
        if (empty($programs)) {
            $mform->addElement('static', 'noprograms', '',
                get_string('noprogramsavailable', 'gradereport_transcript'));
            return;
        }

        $mform->addElement('select', 'programid', get_string('selectprogram', 'gradereport_transcript'), $programs);
        $mform->addRule('programid', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('programid', 'selectprogram', 'gradereport_transcript');

        // Request type (required).
        $requesttypes = [
            'unofficial' => get_string('transcriptunofficial', 'gradereport_transcript'),
            'official' => get_string('transcriptofficial', 'gradereport_transcript')
        ];
        $mform->addElement('select', 'requesttype', get_string('requesttype', 'gradereport_transcript'), $requesttypes);
        $mform->addRule('requesttype', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('requesttype', 'requesttype', 'gradereport_transcript');

        // Delivery method (required).
        $deliverymethods = [
            'email' => get_string('deliveryemail', 'gradereport_transcript'),
            'postal' => get_string('deliverypostal', 'gradereport_transcript'),
            'pickup' => get_string('deliverypickup', 'gradereport_transcript')
        ];
        $mform->addElement('select', 'deliverymethod', get_string('deliverymethod', 'gradereport_transcript'), $deliverymethods);
        $mform->addRule('deliverymethod', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('deliverymethod', 'deliverymethod', 'gradereport_transcript');

        // Recipient information header (for official transcripts).
        $mform->addElement('header', 'recipientheader', get_string('recipientinformation', 'gradereport_transcript'));
        $mform->addElement('static', 'recipientdesc', '',
            get_string('recipientdescription', 'gradereport_transcript'));

        // Recipient name (institution/company name) - REQUIRED for official transcripts.
        $mform->addElement('text', 'recipientname', get_string('recipientname', 'gradereport_transcript'),
            ['size' => 60, 'maxlength' => 255]);
        $mform->setType('recipientname', PARAM_TEXT);
        $mform->addHelpButton('recipientname', 'recipientname', 'gradereport_transcript');
        $mform->hideIf('recipientname', 'requesttype', 'eq', 'unofficial');

        // Recipient address - REQUIRED for official transcripts with postal delivery.
        $mform->addElement('textarea', 'recipientaddress', get_string('recipientaddress', 'gradereport_transcript'),
            ['rows' => 4, 'cols' => 60]);
        $mform->setType('recipientaddress', PARAM_TEXT);
        $mform->addHelpButton('recipientaddress', 'recipientaddress', 'gradereport_transcript');
        $mform->hideIf('recipientaddress', 'requesttype', 'eq', 'unofficial');

        // Recipient phone (optional).
        $mform->addElement('text', 'recipientphone', get_string('recipientphone', 'gradereport_transcript'),
            ['size' => 30, 'maxlength' => 50]);
        $mform->setType('recipientphone', PARAM_TEXT);
        $mform->addHelpButton('recipientphone', 'recipientphone', 'gradereport_transcript');
        $mform->hideIf('recipientphone', 'requesttype', 'eq', 'unofficial');

        // Recipient email - REQUIRED for official transcripts with email delivery.
        $mform->addElement('text', 'recipientemail', get_string('recipientemail', 'gradereport_transcript'),
            ['size' => 60, 'maxlength' => 255]);
        $mform->setType('recipientemail', PARAM_EMAIL);
        $mform->addHelpButton('recipientemail', 'recipientemail', 'gradereport_transcript');

        // Additional notes (optional).
        $mform->addElement('textarea', 'notes', get_string('requestnotes', 'gradereport_transcript'),
            ['rows' => 4, 'cols' => 60]);
        $mform->setType('notes', PARAM_TEXT);
        $mform->addHelpButton('notes', 'requestnotes', 'gradereport_transcript');

        // Pricing information (display only).
        if (isset($customdata['pricing'])) {
            $mform->addElement('header', 'pricingheader', get_string('pricinginformation', 'gradereport_transcript'));
            $mform->addElement('static', 'pricinginfo', '', $customdata['pricing']);
        }

        // Hidden fields.
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        // Set default values from URL parameters (pre-fill).
        if (isset($customdata['programid']) && $customdata['programid'] > 0) {
            $mform->setDefault('programid', $customdata['programid']);
        }
        if (isset($customdata['requesttype']) && !empty($customdata['requesttype'])) {
            $mform->setDefault('requesttype', $customdata['requesttype']);
        }

        // Action buttons.
        $this->add_action_buttons(true, get_string('submitrequest', 'gradereport_transcript'));
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

        // Validate recipient information for official transcripts.
        if ($data['requesttype'] === 'official') {
            // Recipient name is REQUIRED for official transcripts.
            if (empty($data['recipientname'])) {
                $errors['recipientname'] = get_string('required');
            }

            // Recipient address is REQUIRED for postal delivery.
            if ($data['deliverymethod'] === 'postal' && empty($data['recipientaddress'])) {
                $errors['recipientaddress'] = get_string('required');
            }

            // Recipient email is REQUIRED for email delivery.
            if ($data['deliverymethod'] === 'email' && empty($data['recipientemail'])) {
                $errors['recipientemail'] = get_string('required');
            }
        }

        return $errors;
    }
}
