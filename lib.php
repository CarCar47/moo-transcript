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
 * This function adds a link to the Academic Transcripts report in the user's
 * profile page. The link is shown based on user permissions and plugin settings.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object for profile navigation
 * @param stdClass $user User object whose profile is being viewed
 * @param bool $iscurrentuser Whether the current user is viewing their own profile
 * @param stdClass|null $course Course object (not used in this plugin)
 * @return void
 */
function gradereport_transcript_myprofile_navigation(\core_user\output\myprofile\tree $tree, stdClass $user, bool $iscurrentuser, ?stdClass $course) {
    global $USER;

    // Determine if viewing own profile
    $viewingown = ($user->id === $USER->id);

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
 * Returns the grade report link for course context.
 *
 * This function adds a "View Transcript" link in the grade reports interface
 * when viewing a user's grades. The link is shown based on permissions and settings.
 *
 * @param \context_course $context Course context object
 * @param int $courseid Moodle course ID
 * @param array $element An array representing an element in the grade_tree
 * @param \grade_plugin_return $gpr A grade_plugin_return object for navigation
 * @param string $mode Display mode ('user' or other)
 * @param stdClass|null $templatecontext Template context object for rendering
 * @return stdClass|null Returns link template context or null if link should not be shown
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
        $viewingown = ($userid === $USER->id);

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
