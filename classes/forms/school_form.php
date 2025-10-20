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
 * School management form for transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding/editing schools
 *
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class school_form extends \moodleform {

    /**
     * Define the form
     */
    public function definition() {
        $mform = $this->_form;

        // Hidden field for school ID (when editing).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Hidden field for action (add/edit).
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);
        if (isset($this->_customdata['action'])) {
            $mform->setDefault('action', $this->_customdata['action']);
        }

        // School name (required).
        $mform->addElement('text', 'name', get_string('schoolname', 'gradereport_transcript'),
            ['size' => '60']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'schoolname', 'gradereport_transcript');

        // Address (textarea).
        $mform->addElement('textarea', 'address', get_string('schooladdress', 'gradereport_transcript'),
            ['rows' => 4, 'cols' => 60]);
        $mform->setType('address', PARAM_TEXT);
        $mform->addHelpButton('address', 'schooladdress', 'gradereport_transcript');

        // Phone number.
        $mform->addElement('text', 'phone', get_string('schoolphone', 'gradereport_transcript'),
            ['size' => '30']);
        $mform->setType('phone', PARAM_TEXT);
        $mform->addHelpButton('phone', 'schoolphone', 'gradereport_transcript');

        // Website URL.
        $mform->addElement('text', 'website', get_string('schoolwebsite', 'gradereport_transcript'),
            ['size' => '60']);
        $mform->setType('website', PARAM_URL);
        $mform->addHelpButton('website', 'schoolwebsite', 'gradereport_transcript');

        // Logo file upload.
        $mform->addElement('filemanager', 'logo', get_string('schoollogo', 'gradereport_transcript'),
            null, $this->get_filemanager_options());
        $mform->addHelpButton('logo', 'schoollogo', 'gradereport_transcript');

        // Action buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Form validation
     *
     * @param array $data Data from the form
     * @param array $files Files uploaded
     * @return array Errors array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // Validate school name is not empty (beyond whitespace).
        if (isset($data['name']) && trim($data['name']) === '') {
            $errors['name'] = get_string('error:schoolnameempty', 'gradereport_transcript');
        }

        // Validate website URL format if provided.
        if (!empty($data['website'])) {
            $url = trim($data['website']);
            // Ensure URL has protocol.
            if (!preg_match('/^https?:\/\//i', $url)) {
                $errors['website'] = get_string('error:websiteinvalid', 'gradereport_transcript');
            }
        }

        // Validate phone format if provided (basic validation).
        if (!empty($data['phone'])) {
            $phone = trim($data['phone']);
            // Allow common phone formats: digits, spaces, dashes, parentheses, plus sign.
            if (!preg_match('/^[\d\s\-\(\)\+]+$/', $phone)) {
                $errors['phone'] = get_string('error:phoneinvalid', 'gradereport_transcript');
            }
        }

        return $errors;
    }

    /**
     * Get filemanager options for logo upload
     *
     * @return array Filemanager options
     */
    private function get_filemanager_options(): array {
        return [
            'subdirs' => 0,
            'maxbytes' => 1048576, // 1 MB max file size.
            'maxfiles' => 1,
            'accepted_types' => ['image'], // Only image files.
            'return_types' => FILE_INTERNAL,
        ];
    }
}
