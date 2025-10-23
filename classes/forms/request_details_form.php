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
 * Request details form for payment and delivery recording
 *
 * @package   gradereport_transcript
 * @copyright 2025 COR4EDU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_transcript\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for recording payment and delivery details
 */
class request_details_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $customdata = $this->_customdata;
        $request = $customdata['request'];

        // Payment Information Section.
        $mform->addElement('header', 'paymentheader', get_string('paymentinformation', 'gradereport_transcript'));

        // Payment status.
        $paymentstatuses = [
            'pending' => get_string('paymentpending', 'gradereport_transcript'),
            'paid' => get_string('paymentpaid', 'gradereport_transcript'),
            'free' => get_string('paymentfree', 'gradereport_transcript'),
            'refunded' => get_string('paymentrefunded', 'gradereport_transcript')
        ];
        $mform->addElement('select', 'paymentstatus', get_string('paymentstatus', 'gradereport_transcript'), $paymentstatuses);
        $mform->addHelpButton('paymentstatus', 'paymentstatus', 'gradereport_transcript');

        // Payment method.
        $paymentmethods = [
            '' => get_string('select'),
            'cash' => get_string('paymentcash', 'gradereport_transcript'),
            'check' => get_string('paymentcheck', 'gradereport_transcript'),
            'credit' => get_string('paymentcredit', 'gradereport_transcript'),
            'debit' => get_string('paymentdebit', 'gradereport_transcript'),
            'online' => get_string('paymentonline', 'gradereport_transcript'),
            'other' => get_string('paymentother', 'gradereport_transcript')
        ];
        $mform->addElement('select', 'paymentmethod', get_string('paymentmethod', 'gradereport_transcript'), $paymentmethods);
        $mform->hideIf('paymentmethod', 'paymentstatus', 'eq', 'free');
        $mform->hideIf('paymentmethod', 'paymentstatus', 'eq', 'pending');

        // Receipt number.
        $mform->addElement('text', 'receiptnumber', get_string('receiptnumber', 'gradereport_transcript'), ['size' => 30]);
        $mform->setType('receiptnumber', PARAM_TEXT);
        $mform->addHelpButton('receiptnumber', 'receiptnumber', 'gradereport_transcript');
        $mform->hideIf('receiptnumber', 'paymentstatus', 'eq', 'free');
        $mform->hideIf('receiptnumber', 'paymentstatus', 'eq', 'pending');

        // Date paid.
        $this->add_us_date_selector('paiddate', get_string('paiddate', 'gradereport_transcript'));
        $mform->addHelpButton('paiddate_group', 'paiddate', 'gradereport_transcript');
        $this->set_us_date_default('paiddate', $request->paiddate ?? null);

        // Payment notes.
        $mform->addElement('textarea', 'paymentnotes', get_string('paymentnotes', 'gradereport_transcript'),
            ['rows' => 3, 'cols' => 60]);
        $mform->setType('paymentnotes', PARAM_TEXT);
        $mform->addHelpButton('paymentnotes', 'paymentnotes', 'gradereport_transcript');

        // Program Completion Information Section (Official Transcripts Only).
        if ($request->requesttype === 'official') {
            $mform->addElement('header', 'completionheader', get_string('programcompletioninformation', 'gradereport_transcript'));
            $mform->addHelpButton('completionheader', 'programcompletioninformation', 'gradereport_transcript');

            // Program start date.
            $this->add_us_date_selector('programstartdate', get_string('programstartdate', 'gradereport_transcript'));
            $mform->addHelpButton('programstartdate_group', 'programstartdate', 'gradereport_transcript');
            $this->set_us_date_default('programstartdate', $request->programstartdate ?? null);

            // Completion status.
            $completionstatuses = [
                '' => get_string('select'),
                'graduated' => get_string('completionstatus_graduated', 'gradereport_transcript'),
                'withdrawn' => get_string('completionstatus_withdrawn', 'gradereport_transcript')
            ];
            $mform->addElement('select', 'completionstatus',
                get_string('completionstatus', 'gradereport_transcript'), $completionstatuses);
            $mform->addHelpButton('completionstatus', 'completionstatus', 'gradereport_transcript');

            // Graduation date (only show if status is graduated).
            $this->add_us_date_selector('graduationdate', get_string('graduationdate', 'gradereport_transcript'));
            $mform->addHelpButton('graduationdate_group', 'graduationdate', 'gradereport_transcript');
            $this->set_us_date_default('graduationdate', $request->graduationdate ?? null);

            // Withdrawn date (only show if status is withdrawn).
            $this->add_us_date_selector('withdrawndate', get_string('withdrawndate', 'gradereport_transcript'));
            $mform->addHelpButton('withdrawndate_group', 'withdrawndate', 'gradereport_transcript');
            $this->set_us_date_default('withdrawndate', $request->withdrawndate ?? null);
        }

        // Delivery Information Section.
        $mform->addElement('header', 'deliveryheader', get_string('deliveryinformation', 'gradereport_transcript'));

        // Delivery status.
        $deliverystatuses = [
            'pending' => get_string('deliverypending', 'gradereport_transcript'),
            'sent' => get_string('deliverysent', 'gradereport_transcript'),
            'delivered' => get_string('deliverydelivered', 'gradereport_transcript'),
            'pickedup' => get_string('deliverypickedup', 'gradereport_transcript')
        ];
        $mform->addElement('select', 'deliverystatus', get_string('deliverystatus', 'gradereport_transcript'), $deliverystatuses);
        $mform->addHelpButton('deliverystatus', 'deliverystatus', 'gradereport_transcript');

        // Delivery date.
        $this->add_us_date_selector('deliverydate', get_string('deliverydate', 'gradereport_transcript'));
        $mform->addHelpButton('deliverydate_group', 'deliverydate', 'gradereport_transcript');
        $this->set_us_date_default('deliverydate', $request->deliverydate ?? null);

        // Tracking number (for postal delivery only).
        $mform->addElement('text', 'trackingnumber', get_string('trackingnumber', 'gradereport_transcript'), ['size' => 40]);
        $mform->setType('trackingnumber', PARAM_TEXT);
        $mform->addHelpButton('trackingnumber', 'trackingnumber', 'gradereport_transcript');
        // Only show for postal delivery method.
        if ($request->deliverymethod === 'postal') {
            $mform->hideIf('trackingnumber', 'deliverystatus', 'eq', 'pending');
        } else {
            $mform->hardFreeze('trackingnumber');
        }

        // Pickup person name (for pickup delivery only).
        $mform->addElement('text', 'pickupperson', get_string('pickupperson', 'gradereport_transcript'), ['size' => 60]);
        $mform->setType('pickupperson', PARAM_TEXT);
        $mform->addHelpButton('pickupperson', 'pickupperson', 'gradereport_transcript');
        // Only show for pickup delivery method.
        if ($request->deliverymethod === 'pickup') {
            $mform->hideIf('pickupperson', 'deliverystatus', 'neq', 'pickedup');
        } else {
            $mform->hardFreeze('pickupperson');
        }

        // Delivery notes.
        $mform->addElement('textarea', 'deliverynotes', get_string('deliverynotes', 'gradereport_transcript'),
            ['rows' => 3, 'cols' => 60]);
        $mform->setType('deliverynotes', PARAM_TEXT);
        $mform->addHelpButton('deliverynotes', 'deliverynotes', 'gradereport_transcript');

        // Hidden fields.
        $mform->addElement('hidden', 'requestid', $request->id);
        $mform->setType('requestid', PARAM_INT);

        // Action buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Add US-format date selector (Month/Day/Year with optional checkbox)
     *
     * @param string $name Field name
     * @param string $label Field label
     * @param bool $required Whether the field is required
     */
    private function add_us_date_selector($name, $label, $required = false) {
        $mform = $this->_form;

        // Create date group elements: checkbox, month, day, year
        $elements = [];

        // Optional checkbox
        $elements[] = $mform->createElement('advcheckbox', $name . '_enabled', '', get_string('enable'), [], [0, 1]);

        // Month dropdown (01-12)
        $months = ['' => get_string('month', 'form')];
        for ($i = 1; $i <= 12; $i++) {
            $months[sprintf('%02d', $i)] = userdate(mktime(0, 0, 0, $i, 1, 2000), '%B');
        }
        $elements[] = $mform->createElement('select', $name . '_month', '', $months);

        // Day dropdown (01-31)
        $days = ['' => get_string('day', 'form')];
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }
        $elements[] = $mform->createElement('select', $name . '_day', '', $days);

        // Year dropdown (1900-2050)
        $years = ['' => get_string('year', 'form')];
        for ($i = 1900; $i <= 2050; $i++) {
            $years[$i] = $i;
        }
        $elements[] = $mform->createElement('select', $name . '_year', '', $years);

        // Add group
        $mform->addGroup($elements, $name . '_group', $label, ' ', false);

        if ($required) {
            $mform->addRule($name . '_group', get_string('required'), 'required');
        }
    }

    /**
     * Set default values for US-format date field from timestamp
     *
     * @param string $name Field name
     * @param int|null $timestamp Timestamp value
     */
    private function set_us_date_default($name, $timestamp) {
        $mform = $this->_form;

        if (!empty($timestamp)) {
            $mform->setDefault($name . '_enabled', 1);
            $mform->setDefault($name . '_month', sprintf('%02d', date('m', $timestamp)));
            $mform->setDefault($name . '_day', date('j', $timestamp));
            $mform->setDefault($name . '_year', date('Y', $timestamp));
        }
    }

    /**
     * Process US-format date fields from form data
     *
     * @param object $data Form data
     * @param string $name Field name
     * @return int|null Timestamp or null
     */
    public static function process_us_date($data, $name) {
        $enabled = $name . '_enabled';
        $month = $name . '_month';
        $day = $name . '_day';
        $year = $name . '_year';

        if (empty($data->$enabled) || empty($data->$month) || empty($data->$day) || empty($data->$year)) {
            return null;
        }

        return mktime(0, 0, 0, (int)$data->$month, (int)$data->$day, (int)$data->$year);
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

        // Validate payment details if status is paid.
        if ($data['paymentstatus'] === 'paid') {
            if (empty($data['paymentmethod'])) {
                $errors['paymentmethod'] = get_string('required');
            }
            if (empty($data['paiddate_enabled']) || empty($data['paiddate_month']) ||
                empty($data['paiddate_day']) || empty($data['paiddate_year'])) {
                $errors['paiddate_group'] = get_string('required');
            }
        }

        // Validate delivery details based on status.
        if ($data['deliverystatus'] !== 'pending') {
            if (empty($data['deliverydate_enabled']) || empty($data['deliverydate_month']) ||
                empty($data['deliverydate_day']) || empty($data['deliverydate_year'])) {
                $errors['deliverydate_group'] = get_string('required');
            }
        }

        // Validate pickup person if status is picked up.
        if ($data['deliverystatus'] === 'pickedup') {
            if (empty($data['pickupperson'])) {
                $errors['pickupperson'] = get_string('required');
            }
        }

        // Validate program completion fields for official transcripts.
        if (isset($data['completionstatus']) && !empty($data['completionstatus'])) {
            // Validate graduation date if status is graduated.
            if ($data['completionstatus'] === 'graduated') {
                if (empty($data['graduationdate_enabled']) || empty($data['graduationdate_month']) ||
                    empty($data['graduationdate_day']) || empty($data['graduationdate_year'])) {
                    $errors['graduationdate_group'] = get_string('required');
                }
            }
            // Validate withdrawn date if status is withdrawn.
            if ($data['completionstatus'] === 'withdrawn') {
                if (empty($data['withdrawndate_enabled']) || empty($data['withdrawndate_month']) ||
                    empty($data['withdrawndate_day']) || empty($data['withdrawndate_year'])) {
                    $errors['withdrawndate_group'] = get_string('required');
                }
            }
        }

        return $errors;
    }
}
