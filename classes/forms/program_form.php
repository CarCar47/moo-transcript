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
 * Program management form for transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding/editing programs
 *
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_form extends \moodleform {

    /**
     * Define the form
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Hidden field for program ID (when editing).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Hidden field for action (add/edit).
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);
        if (isset($this->_customdata['action'])) {
            $mform->setDefault('action', $this->_customdata['action']);
        }

        // School selection (required).
        $schools = $DB->get_records_menu('gradereport_transcript_schools', null, 'name ASC', 'id, name');
        if (empty($schools)) {
            $schools = [0 => get_string('noschoolsavailable', 'gradereport_transcript')];
            $mform->addElement('select', 'schoolid', get_string('school', 'gradereport_transcript'), $schools);
            $mform->setType('schoolid', PARAM_INT);
            $mform->hardFreeze('schoolid');
            $mform->addElement('static', 'schoolwarning', '', get_string('mustcreateschool', 'gradereport_transcript'));
        } else {
            $schools = [0 => get_string('selectschool', 'gradereport_transcript')] + $schools;
            $mform->addElement('select', 'schoolid', get_string('school', 'gradereport_transcript'), $schools);
            $mform->setType('schoolid', PARAM_INT);
            $mform->addRule('schoolid', get_string('required'), 'required', null, 'client');
            $mform->addRule('schoolid', get_string('required'), 'nonzero', null, 'client');
            $mform->addHelpButton('schoolid', 'school', 'gradereport_transcript');
        }

        // Moodle category selection (required).
        $categories = \core_course_category::make_categories_list('', 0, ' / ');
        $categories = [0 => get_string('selectcategory', 'gradereport_transcript')] + $categories;
        $mform->addElement('select', 'categoryid', get_string('category'), $categories);
        $mform->setType('categoryid', PARAM_INT);
        $mform->addRule('categoryid', get_string('required'), 'required', null, 'client');
        $mform->addRule('categoryid', get_string('required'), 'nonzero', null, 'client');
        $mform->addHelpButton('categoryid', 'programcategory', 'gradereport_transcript');

        // Program name (required).
        $mform->addElement('text', 'name', get_string('programname', 'gradereport_transcript'),
            ['size' => '60']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'programname', 'gradereport_transcript');

        // Program type (required).
        $types = [
            'hourbased' => get_string('hourbased', 'gradereport_transcript'),
            'creditbased' => get_string('creditbased', 'gradereport_transcript'),
            'ceu' => get_string('ceu', 'gradereport_transcript'),
        ];
        $mform->addElement('select', 'type', get_string('programtype', 'gradereport_transcript'), $types);
        $mform->setType('type', PARAM_ALPHA);
        $mform->setDefault('type', 'hourbased');
        $mform->addHelpButton('type', 'programtype', 'gradereport_transcript');

        // Hour type labels (for hour-based programs only).
        $mform->addElement('header', 'hourlabelshdr', get_string('hourlabels', 'gradereport_transcript'));
        $mform->setExpanded('hourlabelshdr');

        $mform->addElement('text', 'hour1label', get_string('hour1label', 'gradereport_transcript'),
            ['size' => '30']);
        $mform->setType('hour1label', PARAM_TEXT);
        $mform->setDefault('hour1label', 'Theory Hours');
        $mform->addHelpButton('hour1label', 'hour1label', 'gradereport_transcript');
        $mform->hideIf('hour1label', 'type', 'neq', 'hourbased');

        $mform->addElement('text', 'hour2label', get_string('hour2label', 'gradereport_transcript'),
            ['size' => '30']);
        $mform->setType('hour2label', PARAM_TEXT);
        $mform->setDefault('hour2label', 'Lab Hours');
        $mform->addHelpButton('hour2label', 'hour2label', 'gradereport_transcript');
        $mform->hideIf('hour2label', 'type', 'neq', 'hourbased');

        $mform->addElement('text', 'hour3label', get_string('hour3label', 'gradereport_transcript'),
            ['size' => '30']);
        $mform->setType('hour3label', PARAM_TEXT);
        $mform->setDefault('hour3label', 'Clinical Hours');
        $mform->addHelpButton('hour3label', 'hour3label', 'gradereport_transcript');
        $mform->hideIf('hour3label', 'type', 'neq', 'hourbased');

        $mform->hideIf('hourlabelshdr', 'type', 'neq', 'hourbased');

        // PDF template upload (optional).
        $mform->addElement('filemanager', 'pdftemplate', get_string('pdftemplate', 'gradereport_transcript'),
            null, $this->get_filemanager_options());
        $mform->addHelpButton('pdftemplate', 'pdftemplate', 'gradereport_transcript');

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

        // Validate program name is not empty (beyond whitespace).
        if (isset($data['name']) && trim($data['name']) === '') {
            $errors['name'] = get_string('error:programnameempty', 'gradereport_transcript');
        }

        // Validate school is selected.
        if (empty($data['schoolid'])) {
            $errors['schoolid'] = get_string('error:schoolrequired', 'gradereport_transcript');
        }

        // Validate category is selected.
        if (empty($data['categoryid'])) {
            $errors['categoryid'] = get_string('error:categoryrequired', 'gradereport_transcript');
        }

        // Validate program type is valid.
        $validtypes = ['hourbased', 'creditbased', 'ceu'];
        if (!in_array($data['type'], $validtypes, true)) {
            $errors['type'] = get_string('error:invalidprogramtype', 'gradereport_transcript');
        }

        return $errors;
    }

    /**
     * Get filemanager options for PDF template upload
     *
     * @return array Filemanager options
     */
    private function get_filemanager_options(): array {
        return [
            'subdirs' => 0,
            'maxbytes' => 5242880, // 5 MB max file size.
            'maxfiles' => 1,
            'accepted_types' => ['.pdf'], // Only PDF files.
            'return_types' => FILE_INTERNAL,
        ];
    }
}
