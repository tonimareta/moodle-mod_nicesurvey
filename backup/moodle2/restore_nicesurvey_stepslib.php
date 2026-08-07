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
 * Restore activity structure step.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_nicesurvey_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define restore structure.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        return $this->prepare_activity_structure([
            new restore_path_element('nicesurvey', '/activity/nicesurvey'),
        ]);
    }

    /**
     * Restore process.
     *
     * @param object|array $data
     * @return void
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_nicesurvey(object|array $data): void {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($instanceid = $DB->insert_record('nicesurvey', $data)) {
            // copy questions to new module
            if ($questions = $DB->get_records('nicesurvey_questions', ['surveyid' => $data->id])) {
                foreach ($questions as $oldquestion) {
                    $question = $oldquestion;
                    $question->surveyid = $instanceid;
                    $question->timecreated = time();
                    $question->timemodified = time();
                    unset($question->id);

                    if ($question->id = $DB->insert_record('nicesurvey_questions', $question)) {
                        if ($question->conditionid) {
                            if ($oldcondition = $DB->get_record('nicesurvey_questions', ['id' => $question->conditionid])) {
                                $params = [
                                    'title' => $oldcondition->title,
                                    'surveyid' => $instanceid,
                                    'datatype' => $oldcondition->datatype,
                                    'hasother' => $oldcondition->hasother,
                                    'required' => $oldcondition->required,
                                    'sequence' => $oldcondition->sequence,
                                ];

                                if (!$condition = $DB->get_record('nicesurvey_questions', $params)) {
                                    $condition = $oldcondition;
                                    $condition->surveyid = $instanceid;
                                    $condition->timecreated = time();
                                    $condition->timemodified = time();
                                    unset($condition->id);

                                    $condition->id = $DB->insert_record('nicesurvey_questions', $condition);
                                }

                                if (!empty($condition->id)) {
                                    $question->conditionid = $condition->id;
                                    $DB->update_record('nicesurvey_questions', $question);
                                }
                            }
                        }
                    }
                }
            }

            $this->apply_activity_instance($instanceid);
        }
    }
}
