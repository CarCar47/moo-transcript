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
 * Course mapping form for transcript plugin
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding course mappings to programs
 *
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_mapping_form extends \moodleform {

    /**
     * Define the form
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        $programid = $customdata['programid'];
        $courses = $customdata['courses'];
        $categorymapping_enabled = $customdata['categorymapping_enabled'];

        // Hidden fields.
        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);

        $mform->addElement('hidden', 'action', 'add_mapping');
        $mform->setType('action', PARAM_ALPHA);

        // Course selector.
        $courseoptions = [];
        foreach ($courses as $course) {
            $courseoptions[$course->id] = format_string($course->shortname) . ' - ' . format_string($course->fullname);
        }
        $mform->addElement('select', 'courseid', get_string('selectcourse', 'gradereport_transcript'), $courseoptions);
        $mform->addRule('courseid', get_string('error:courserequired', 'gradereport_transcript'), 'required', null, 'client');

        // Mapping type (if enabled).
        if ($categorymapping_enabled) {
            $mappingoptions = [
                'course' => get_string('mappingtype_course', 'gradereport_transcript'),
                'category' => get_string('mappingtype_category', 'gradereport_transcript'),
            ];
            $mform->addElement('select', 'mappingtype', get_string('mappingtype', 'gradereport_transcript'), $mappingoptions);
            $mform->setDefault('mappingtype', 'course');

            // v1.0.33: Category selector - pre-load ALL categories from ALL courses server-side.
            // Per Moodle best practices: "For select elements, only data that could have been
            // selected will be allowed" - options MUST be defined server-side for validation.
            $categoryoptions = ['' => get_string('selectcategory', 'gradereport_transcript')];

            // Get all grade categories for all courses in this program.
            require_once($CFG->libdir . '/gradelib.php');
            require_once($CFG->libdir . '/grade/grade_category.php');

            foreach ($courses as $course) {
                $categories = grade_category::fetch_all(['courseid' => $course->id]);
                if ($categories) {
                    foreach ($categories as $category) {
                        // Skip the course-level category.
                        if ($category->is_course_category()) {
                            continue;
                        }
                        // Format: "CourseShortname - Category Name".
                        $label = format_string($course->shortname) . ' - ' . format_string($category->fullname);
                        $categoryoptions[$category->id] = $label;
                    }
                }
            }

            $mform->addElement('select', 'categoryid', get_string('selectcategory', 'gradereport_transcript'), $categoryoptions);
            $mform->disabledIf('categoryid', 'mappingtype', 'neq', 'category');
        } else {
            // Hidden field if category mapping disabled.
            $mform->addElement('hidden', 'mappingtype', 'course');
            $mform->setType('mappingtype', PARAM_ALPHA);
        }

        // Submit button.
        $this->add_action_buttons(false, get_string('addmappingbtn', 'gradereport_transcript'));
    }

    /**
     * Form validation
     *
     * @param array $data Data from the form
     * @param array $files Files uploaded
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate category selection when mapping type is category.
        if (isset($data['mappingtype']) && $data['mappingtype'] === 'category') {
            if (empty($data['categoryid'])) {
                $errors['categoryid'] = get_string('error:categoryrequiredformapping', 'gradereport_transcript');
            }
        }

        return $errors;
    }
}
