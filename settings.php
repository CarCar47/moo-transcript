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

if ($hassiteconfig) {
    // Create a new admin category for transcript plugin.
    $ADMIN->add('gradereports', new admin_category('gradereporttranscript',
        get_string('pluginname', 'gradereport_transcript')));

    // Add help/documentation page.
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscripthelp',
        get_string('help', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/help.php'),
        'gradereport/transcript:manage'
    ));

    // Add school management page (Phase 1).
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptschools',
        get_string('manageschools', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_schools.php'),
        'gradereport/transcript:manage'
    ));

    // Add program management page (Phase 2).
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptprograms',
        get_string('manageprograms', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_programs.php'),
        'gradereport/transcript:manage'
    ));

    // Add course mapping page (Phase 3).
    $ADMIN->add('gradereporttranscript', new admin_externalpage(
        'gradereporttranscriptcourses',
        get_string('managecourses', 'gradereport_transcript'),
        new moodle_url('/grade/report/transcript/manage_courses.php'),
        'gradereport/transcript:manage'
    ));
}
