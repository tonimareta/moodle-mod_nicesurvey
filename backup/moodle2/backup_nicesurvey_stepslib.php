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

/**
 * Module activity structure step.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_nicesurvey_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define module structure.
     *
     * @return backup_nested_element
     * @throws base_element_struct_exception
     */
    protected function define_structure() {
        $nicesurvey = new backup_nested_element('nicesurvey', ['id'], [
            'name', 'intro', 'introformat', 'completionmessage', 'anonymous', 'palette',
            'timestart', 'timeend', 'timecreated', 'timemodified',
        ]);

        $nicesurvey->set_source_table('nicesurvey', ['id' => backup::VAR_ACTIVITYID]);
        return $this->prepare_activity_structure($nicesurvey);
    }
}
