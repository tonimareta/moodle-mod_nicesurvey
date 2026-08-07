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

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->dirroot}/mod/nicesurvey/backup/moodle2/restore_nicesurvey_stepslib.php");

/**
 * Restore activity task.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_nicesurvey_activity_task extends restore_activity_task {
    /**
     * Define restore settings.
     *
     * @return void
     */
    protected function define_my_settings(): void {
        // The module does not have any specific settings.
    }

    /**
     * Define restore steps.
     *
     * @return void
     * @throws base_task_exception
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_nicesurvey_activity_structure_step('nicesurvey_structure', 'nicesurvey.xml'));
    }

    /**
     * Define restore decode content.
     *
     * @return restore_decode_content[]
     */
    static public function define_decode_contents(): array {
        return [
            new restore_decode_content('nicesurvey', ['name'], 'nicesurvey'),
        ];
    }

    /**
     * Define restore decode rules.
     *
     * @return restore_decode_rule[]
     */
    static public function define_decode_rules(): array {
        return [
            new restore_decode_rule('NICESURVEYINDEX', '/mod/nicesurvey/index.php?id=$1', 'course'),
            new restore_decode_rule('NICESURVEYVIEWBYID', '/mod/nicesurvey/view.php?id=$1', 'course_module'),
        ];
    }

    /**
     * Define restore log rules.
     *
     * @return restore_log_rule[]
     */
    static public function define_restore_log_rules(): array {
        return [
            new restore_log_rule('nicesurvey', 'add', 'view.php?id={course_module}', '{nicesurvey}'),
            new restore_log_rule('nicesurvey', 'update', 'view.php?id={course_module}', '{nicesurvey}'),
            new restore_log_rule('nicesurvey', 'view', 'view.php?id={course_module}', '{nicesurvey}'),
        ];
    }

    /**
     * Define restore course log rules.
     *
     * @return restore_log_rule[]
     */
    static public function define_restore_log_rules_for_course(): array {
        return [
            new restore_log_rule('nicesurvey', 'view all', 'index.php?id={course}', null),
        ];
    }
}
