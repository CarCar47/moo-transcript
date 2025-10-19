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
 * Library functions for the transcript report
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/report/lib.php');

/**
 * Add nodes to myprofile page.
 *
 * @param tree $tree Tree object
 * @param stdClass $user User object
 * @param bool $iscurrentuser Is the user viewing their own profile?
 * @param stdClass|null $course Course object
 */
function gradereport_transcript_myprofile_navigation(tree $tree, stdClass $user, bool $iscurrentuser, ?stdClass $course) {
    if (empty($course)) {
        // We want to display these reports under the site context.
        $course = get_fast_modinfo(SITEID)->get_course();
    }

    $context = context_course::instance($course->id);
    if (has_capability('gradereport/transcript:view', $context, $user->id)) {
        $url = new moodle_url('/grade/report/transcript/index.php', ['id' => $course->id, 'userid' => $user->id]);
        $node = new core_user\output\myprofile\node('reports', 'transcript',
                get_string('pluginname', 'gradereport_transcript'), null, $url);
        $tree->add_node($node);
    }
}

/**
 * Returns the grade report link for course context
 *
 * @param context_course $context Course context
 * @param int $courseid Course ID
 * @param array $element An array representing an element in the grade_tree
 * @param grade_plugin_return $gpr A grade_plugin_return object
 * @param string $mode Mode
 * @param stdClass|null $templatecontext Template context
 * @return stdClass|null
 */
function gradereport_transcript_get_report_link(context_course $context, int $courseid,
        array $element, grade_plugin_return $gpr, string $mode, ?stdClass $templatecontext): ?stdClass {

    if ($mode == 'user') {
        if (!isset($element['userid'])) {
            return null;
        }

        $userid = $element['userid'];
        if (!has_capability('gradereport/transcript:view', $context, $userid)) {
            return null;
        }

        $url = new moodle_url('/grade/report/transcript/index.php', ['id' => $courseid, 'userid' => $userid]);

        if (!isset($templatecontext)) {
            $templatecontext = new stdClass();
        }
        $templatecontext->url = $url;
        $templatecontext->text = get_string('viewtranscript', 'gradereport_transcript');

        return $templatecontext;
    }

    return null;
}
