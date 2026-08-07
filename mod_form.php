<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module form.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_nicesurvey_mod_form extends moodleform_mod {
    /**
     * Default palette for results charts.
     */
    protected const PALETTE = [
        '#fd7f6f', '#7eb0d5', '#b2e061', '#bd7ebe', '#ffb55a', '#ffee65',
        '#beb9db', '#fdcce5', '#8bd3c7', '#115f9a', '#1984c5', '#22a7f0',
        '#48b5c4', '#76c68f', '#a6d75b', '#c9e52f', '#d0ee11', '#d0f400',
    ];

    /**
     * Make definition.
     *
     * @return void
     * @throws \core\exception\moodle_exception
     * @throws \core\exception\coding_exception
     * @throws dml_exception
     */
    function definition(): void {
        global $CFG, $DB, $PAGE, $OUTPUT;

        $nicesurvey = !$this->_cm ? null : $DB->get_record('nicesurvey', ['id' => $this->_cm->instance], '*', MUST_EXIST);
        $icon = new pix_icon('i/loading', get_string('loading', 'admin'), 'moodle', ['class' => 'loadingicon']);
        $disabled = !empty($nicesurvey->id) ? ['disabled' => 'disabled'] : [];

        $this->_form->addElement('header', 'general', get_string('plugingeneral', 'mod_nicesurvey'));

        $this->_form->addElement('hidden', 'questionid');
        $this->_form->setType('questionid', PARAM_INT);

        // Display name.
        $this->_form->addElement('text', 'name', get_string('name'));
        $this->_form->setType('name', PARAM_RAW);
        $this->_form->addRule('name', null, 'required');
        $this->_form->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255);

        // Is the survey anonymous?
        $this->_form->addElement('selectyesno', 'anonymous', get_string('anonymous', 'mod_nicesurvey'), $disabled);

        // Is the survey date-specific?
        $this->_form->addElement('advcheckbox', 'nicesurveyhasdates', get_string('hasdates', 'mod_nicesurvey'));
        $this->_form->setDefault('nicesurveyhasdates', (int) !empty($nicesurvey->timestart));

        $this->_form->addElement('date_time_selector', 'timestart', get_string('timestart', 'mod_nicesurvey'));
        $this->_form->hideIf('timestart', 'nicesurveyhasdates');

        $this->_form->addElement('date_time_selector', 'timeend', get_string('timeend', 'mod_nicesurvey'));
        $this->_form->hideIf('timeend', 'nicesurveyhasdates');

        $this->standard_intro_elements();

        // Message after completing the survey.
        $this->_form->addElement('editor', 'completionmessage', get_string('completionmessage', 'mod_nicesurvey'), ['rows' => 10]);
        $this->_form->setType('completionmessage', PARAM_RAW);

        if (!empty($nicesurvey->completionmessage)) {
            $this->_form->getElement('completionmessage')->setValue([
                'text' => $nicesurvey->completionmessage,
                'format' => FORMAT_HTML,
            ]);
        }

        // Palette for results charts.
        $this->_form->addElement('header', 'palettesettings', get_string('palette', 'mod_nicesurvey'));

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/lib/javascript-static.js'));

        $colors = self::PALETTE;

        if (!empty($nicesurvey->palette)) {
            $colors = explode(';', $nicesurvey->palette);
        }

        foreach ($colors as $index => $color) {
            $this->_form->addElement('html', $OUTPUT->render_from_template('core_admin/setting_configcolourpicker', [
                'id' => "nicesurvey-color-{$index}",
                'name' => 'colors[]',
                'value' => mb_strtoupper($color),
                'icon' => $icon->export_for_template($OUTPUT),
                'haspreviewconfig' => false,
                'forceltr' => null,
            ]));

            $PAGE->requires->js_init_call('M.util.init_colour_picker', ["nicesurvey-color-$index", $color]);
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}