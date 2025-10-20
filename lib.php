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
    global $USER;

    // Determine if viewing own profile
    $viewingown = ($user->id == $USER->id);

    // Check if user is admin/manager
    $isadmin = is_siteadmin();
    $systemcontext = context_system::instance();
    $ismanager = has_capability('gradereport/transcript:manage', $systemcontext);

    if ($viewingown) {
        // Student viewing own profile - check if student access is enabled
        if (!$isadmin && !$ismanager) {
            $enablestudents = get_config('gradereport_transcript', 'enablestudents');
            if ($enablestudents === false) {
                $enablestudents = 1;  // Default to enabled if not set
            }
            if (!$enablestudents) {
                return;  // Don't add node - student access disabled in settings
            }
        }
        // Add node for own profile (student access enabled OR user is admin/manager)
        $url = new moodle_url('/grade/report/transcript/index.php', ['id' => SITEID, 'userid' => $user->id]);
        $node = new core_user\output\myprofile\node('reports', 'transcript',
                get_string('pluginname', 'gradereport_transcript'), null, $url);
        $tree->add_node($node);
    } else if ($isadmin || $ismanager) {
        // Admin/manager viewing another user's profile - always show
        $url = new moodle_url('/grade/report/transcript/index.php', ['id' => SITEID, 'userid' => $user->id]);
        $node = new core_user\output\myprofile\node('reports', 'transcript',
                get_string('pluginname', 'gradereport_transcript'), null, $url);
        $tree->add_node($node);
    }
    // Otherwise, don't add node (regular user viewing another user's profile)
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
        global $USER;

        // Determine if viewing own grades
        $viewingown = ($userid == $USER->id);

        // Check if user is admin/manager
        $isadmin = is_siteadmin();
        $systemcontext = context_system::instance();
        $ismanager = has_capability('gradereport/transcript:manage', $systemcontext);

        if ($viewingown) {
            // Student viewing own grades - check if student access is enabled
            if (!$isadmin && !$ismanager) {
                $enablestudents = get_config('gradereport_transcript', 'enablestudents');
                if ($enablestudents === false) {
                    $enablestudents = 1;  // Default to enabled if not set
                }
                if (!$enablestudents) {
                    return null;  // Don't show link - student access disabled in settings
                }
            }
            // Show link for own grades (student access enabled OR user is admin/manager)
            $url = new moodle_url('/grade/report/transcript/index.php', ['id' => SITEID, 'userid' => $userid]);

            if (!isset($templatecontext)) {
                $templatecontext = new stdClass();
            }
            $templatecontext->url = $url;
            $templatecontext->text = get_string('viewtranscript', 'gradereport_transcript');

            return $templatecontext;
        } else if ($isadmin || $ismanager) {
            // Admin/manager viewing another user's grades - always show
            $url = new moodle_url('/grade/report/transcript/index.php', ['id' => SITEID, 'userid' => $userid]);

            if (!isset($templatecontext)) {
                $templatecontext = new stdClass();
            }
            $templatecontext->url = $url;
            $templatecontext->text = get_string('viewtranscript', 'gradereport_transcript');

            return $templatecontext;
        }
        // Otherwise, don't show link (regular user viewing another user's grades)
    }

    return null;
}
