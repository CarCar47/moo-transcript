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
 * Transcript pricing configuration page
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/forms/pricing_form.php');

defined('MOODLE_INTERNAL') || die();

// Require login and capability.
require_login();
require_capability('gradereport/transcript:manage', context_system::instance());

// Admin page setup.
admin_externalpage_setup('gradereporttranscriptpricing');

// Get parameters.
$schoolid = optional_param('schoolid', 0, PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);

// Set up page - INCLUDE ALL PARAMETERS.
$PAGE->set_url(new moodle_url('/grade/report/transcript/manage_pricing.php', ['action' => $action, 'schoolid' => $schoolid]));
$PAGE->set_title(get_string('configurepricing', 'gradereport_transcript'));
$PAGE->set_heading(get_string('configurepricing', 'gradereport_transcript'));

// Handle form submission (edit/create pricing).
if ($action == 'edit') {
    global $DB;

    // Validate schoolid parameter.
    if ($schoolid <= 0) {
        throw new moodle_exception('invalidschoolid', 'gradereport_transcript');
    }

    // Verify school exists.
    $school = $DB->get_record('gradereport_transcript_schools', ['id' => $schoolid], '*', MUST_EXIST);

    // Create form with $PAGE->url to preserve URL parameters (Moodle standard method).
    $mform = new \gradereport_transcript\forms\pricing_form($PAGE->url, ['schoolid' => $schoolid]);

    if ($mform->is_cancelled()) {
        // Redirect back to list.
        redirect(new moodle_url('/grade/report/transcript/manage_pricing.php'));

    } else if ($data = $mform->get_data()) {

        // Save pricing configuration.
        $pricing = $DB->get_record('gradereport_transcript_pricing', ['schoolid' => $data->schoolid]);

        // Get checkbox value (advcheckbox always provides a value).
        $firstfree = $data->firstfree;

        if ($pricing) {
            // Update existing pricing.
            $pricing->firstfree = $firstfree;
            $pricing->officialprice = $data->officialprice;
            $pricing->unofficialprice = $data->unofficialprice;
            $pricing->timemodified = time();
            $DB->update_record('gradereport_transcript_pricing', $pricing);
        } else {
            // Create new pricing.
            $pricing = new stdClass();
            $pricing->schoolid = $data->schoolid;
            $pricing->firstfree = $firstfree;
            $pricing->officialprice = $data->officialprice;
            $pricing->unofficialprice = $data->unofficialprice;
            $pricing->timecreated = time();
            $pricing->timemodified = time();
            $DB->insert_record('gradereport_transcript_pricing', $pricing);
        }

        // Redirect with success message.
        redirect(
            new moodle_url('/grade/report/transcript/manage_pricing.php'),
            get_string('pricingupdated', 'gradereport_transcript'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // Display form.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pricingconfiguration', 'gradereport_transcript'));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Display pricing list.
echo $OUTPUT->header();

// Check if any schools exist.
$schools = $DB->get_records('gradereport_transcript_schools', null, 'name ASC');

if (empty($schools)) {
    // No schools configured yet.
    echo $OUTPUT->notification(
        get_string('noschoolsconfigured', 'gradereport_transcript'),
        \core\output\notification::NOTIFY_WARNING
    );
    echo html_writer::tag('p', html_writer::link(
        new moodle_url('/grade/report/transcript/manage_schools.php'),
        get_string('manageschools', 'gradereport_transcript'),
        ['class' => 'btn btn-primary']
    ));
    echo $OUTPUT->footer();
    exit;
}

// Page heading.
echo $OUTPUT->heading(get_string('pricingconfiguration', 'gradereport_transcript'));

// Description.
echo html_writer::div(
    get_string('pricingdescription', 'gradereport_transcript'),
    'alert alert-info'
);

// Display pricing table.
$table = new html_table();
$table->head = [
    get_string('school', 'gradereport_transcript'),
    get_string('firstfree', 'gradereport_transcript'),
    get_string('officialpricelabel', 'gradereport_transcript'),
    get_string('unofficialpricelabel', 'gradereport_transcript'),
    get_string('actions', 'core')
];
$table->attributes['class'] = 'generaltable table table-striped';

foreach ($schools as $school) {
    $pricing = $DB->get_record('gradereport_transcript_pricing', ['schoolid' => $school->id]);

    $row = [];

    // School name.
    $row[] = format_string($school->name);

    if ($pricing) {
        // First free.
        $row[] = $pricing->firstfree ? get_string('yes') : get_string('no');

        // Official price.
        $row[] = '$' . format_float($pricing->officialprice, 2);

        // Unofficial price.
        $row[] = '$' . format_float($pricing->unofficialprice, 2);

        // Edit button.
        $editurl = new moodle_url('/grade/report/transcript/manage_pricing.php', [
            'action' => 'edit',
            'schoolid' => $school->id
        ]);
        $row[] = html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-primary']);
    } else {
        // Not configured yet.
        $row[] = html_writer::tag('span',
            get_string('notconfigured', 'gradereport_transcript'),
            ['class' => 'badge badge-danger']);
        $row[] = '-';
        $row[] = '-';

        // Setup button.
        $setupurl = new moodle_url('/grade/report/transcript/manage_pricing.php', [
            'action' => 'edit',
            'schoolid' => $school->id
        ]);
        $row[] = html_writer::link($setupurl,
            get_string('setup', 'gradereport_transcript'),
            ['class' => 'btn btn-sm btn-success']);
    }

    $table->data[] = $row;
}

echo html_writer::table($table);

// Footer.
echo $OUTPUT->footer();
