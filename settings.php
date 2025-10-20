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
 * Admin settings and navigation for the transcript report
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Admin category and management pages
// These appear under: Site Administration → Grades → Academic Transcripts & CEU Certificates
if ($hassiteconfig) {

    // Create admin category under Grades section
    $ADMIN->add('grades', new admin_category('gradereporttranscript',
        get_string('pluginname', 'gradereport_transcript')));

    // Create settings page manually with UNIQUE identifier (different from category name)
    // Following Moodle pattern: category = 'gradereporttranscript', settingpage = 'gradereporttranscriptsettings'
    $settingspage = new admin_settingpage('gradereporttranscriptsettings',
        get_string('settings'));

    // Add all plugin settings to our manual settings page
    $settingspage->add(new admin_setting_configcheckbox(
        'gradereport_transcript/enablestudents',
        get_string('enablestudents', 'gradereport_transcript'),
        get_string('enablestudents_help', 'gradereport_transcript'),
        1  // Default: enabled
    ));

    $settingspage->add(new admin_setting_configcheckbox(
        'gradereport_transcript/allowunofficial',
        get_string('allowunofficial', 'gradereport_transcript'),
        get_string('allowunofficial_help', 'gradereport_transcript'),
        1  // Default: enabled
    ));

    $settingspage->add(new admin_setting_configcheckbox(
        'gradereport_transcript/showinreports',
        get_string('transcriptlinkinreports', 'gradereport_transcript'),
        get_string('transcriptlinkinreports_help', 'gradereport_transcript'),
        1  // Default: enabled
    ));

    $settingspage->add(new admin_setting_configcheckbox(
        'gradereport_transcript/showsignature',
        get_string('showsignature', 'gradereport_transcript'),
        get_string('showsignature_help', 'gradereport_transcript'),
        1  // Default: enabled
    ));

    // Add student transcript viewer (FIRST in menu - most used feature)
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptviewstudents',
        get_string('viewstudenttranscripts', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/view_student_transcripts.php'),
        'gradereport/transcript:viewall'
    ));

    // Add settings page (SECOND in menu)
    $ADMIN->add('gradereporttranscript', $settingspage);

    // Add help/documentation page
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscripthelp',
        get_string('help', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/help.php'),
        'gradereport/transcript:manage'
    ));

    // Add school management page (Phase 1)
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptschools',
        get_string('manageschools', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_schools.php'),
        'gradereport/transcript:manage'
    ));

    // Add program management page (Phase 2)
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptprograms',
        get_string('manageprograms', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_programs.php'),
        'gradereport/transcript:manage'
    ));

    // Add course mapping page (Phase 3)
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptcourses',
        get_string('managecourses', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_courses.php'),
        'gradereport/transcript:manage'
    ));

    // Add pricing configuration page (Phase 6.2)
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptpricing',
        get_string('configurepricing', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_pricing.php'),
        'gradereport/transcript:manage'
    ));

    // Add request management page (Phase 6.1)
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptrequests',
        get_string('managerequests', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_requests.php'),
        'gradereport/transcript:manage'
    ));
}

// Prevent Moodle from auto-creating duplicate "Report settings" entry
// (We create our own settings page above in the custom category)
$settings = null;
