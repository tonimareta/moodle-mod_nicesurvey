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

namespace mod_nicesurvey;

use coding_exception;
use dml_exception;
use mod_nicesurvey\output\question_renderer;
use moodle_database;
use moodle_url;

/**
 * Nice Survey question.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question {
    /**
     * Question data types.
     */
    public const DATA_TYPES = [
        'answer',
        'onechoice',
        'multichoice',
        'dropdown',
        'text',
        'scale',
    ];

    /**
     * @var array
     */
    public array $datamessages = [];

    /**
     * @var array
     */
    public array $records = [];

    /**
     * @var array
     */
    public array $conditions = [];

    /**
     * @var string
     */
    public string $sesskey;

    /**
     * @var int
     */
    protected int $cmid;

    /**
     * @var moodle_database
     */
    protected moodle_database $db;

    /**
     * @var object|null
     */
    protected ?object $survey = null;

    /**
     * Question constructor.
     *
     * @param int $cmid
     * @param mixed $survey
     * @throws dml_exception
     */
    public function __construct(int $cmid, mixed $survey) {
        global $DB;

        $this->db = $DB;
        $this->cmid = $cmid;
        $this->survey = is_object($survey) ? $survey : null;
        $this->sesskey = sesskey();
        $this->datamessages = (array) get_strings(self::DATA_TYPES, 'mod_nicesurvey');
        $this->records = $this->db->get_records('nicesurvey_questions', ['surveyid' => $this->get_survey_id()], 'sequence');
        $this->prepare_records();
    }

    /**
     * Create a new question.
     *
     * @return object
     */
    public function create_question_record(): object {
        $record = (object) [
            'id' => 0,
            'data' => '[]',
            'datatype' => null,
            'required' => 1,
            'sequence' => count($this->records) + 1,
            'condition' => null,
        ];

        $record = $this->prepare_record($record);

        return $this->prepare_record_relactions($record);
    }

    /**
     * Copy the question into a new record.
     *
     * @param int $id
     * @return bool
     * @throws dml_exception
     */
    public function copy_record(int $id): bool {
        if (empty($this->records[$id])) {
            return false;
        }

        $record = $this->records[$id];
        $record->id = 0;
        $record->title = $record->title . ' ' . get_string('copy', 'mod_nicesurvey');
        $record->sequence = count($this->records) + 1;

        return $this->save_record($record);
    }

    /**
     * Export questions to a text file.
     *
     * @return void
     */
    public function export_records()
    {
        $txt = [];
        $records = array_values($this->records);

        foreach ($records as $index => $record) {
            if (empty($record->description)) {
                $record->description ='';
            }

            $num = $index + 1;
            $pieces = [
                "{$num}. {$record->title} ({$record->annotation})",
            ];

            if (!empty($record->data)) {
                foreach ($record->data as $data) {
                    $pieces[] = "- {$data->value}";
                }
            }

            $txt[] = implode(PHP_EOL, $pieces) . PHP_EOL;
        }

        $txt = implode(PHP_EOL, $txt);
        $filename = clean_filename($this->get_survey_name() . '_' . date('Y-m-d'));

        header('Content-Description: File Transfer');
        header('Content-type: application/text; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($txt));

        echo $txt;
        exit();
    }

    /**
     * Get a palette for plotting the results of a question answer.
     *
     * @return array
     */
    public function get_pallete(): array {
        if (empty($this->survey->palette)) {
            return [];
        }

        return explode(';', $this->survey->palette);
    }

    /**
     * Get conditions between questions.
     *
     * @param int $conditionid
     * @return array
     */
    public function get_conditions(int $conditionid = 0): array {
        if (empty($this->conditions)) {
            if (!$surveyid = $this->get_survey_id()) {
                return [];
            }

            $this->conditions = $this->db->get_records_select(
                'nicesurvey_questions',
                "surveyid = ? AND datatype NOT IN('text', 'answer')",
                [$surveyid]
            );
        }

        return array_values(array_map(function($condition) use ($conditionid) {
            return [
                'id' => $condition->id,
                'title' => $condition->title,
                'active' => $condition->id == $conditionid,
                'data' => !$condition->data ? [] : array_values(json_decode($condition->data)),
            ];
        }, $this->conditions));
    }

    /**
     * Get the survey id.
     *
     * @return int
     */
    public function get_survey_id(): int {
        return $this->survey->id ?? 0;
    }

    /**
     * Get the survey name.
     *
     * @return string
     */
    public function get_survey_name(): string {
        return $this->survey->name ?? '';
    }

    /**
     * Check if the survey is anonymous.
     *
     * @return bool
     */
    public function is_anonymous(): bool {
        return $this->survey->anonymous ?? false;
    }

    /**
     * Move the question to a new position.
     *
     * @param int $id
     * @param int $direction
     * @return bool
     * @throws dml_exception
     */
    public function move_record(int $id, int $direction = 0): bool {
        if (empty($this->records[$id]) || !$direction) {
            return false;
        }

        while(key($this->records) !== null && key($this->records) !== $id) {
            next($this->records);
        }

        $record = current($this->records);
        $sequence = $record->sequence;
        $record->sequence += $direction;

        if ($nextrecord = $direction > 0 ? next($this->records) : prev($this->records)) {
            $nextrecord->sequence = $sequence;
            return $this->save_record($nextrecord) && $this->save_record($record);
        }

        return false;
    }

    /**
     * Delete the question.
     *
     * @param int $id
     * @return bool
     * @throws dml_exception
     */
    public function remove_record(int $id): bool {
        if (empty($this->records[$id])) {
            return false;
        }

        return $this->db->delete_records('nicesurvey_questions', ['id' => $id]);
    }

    /**
     * Display the question in HTML format.
     *
     * @param object $record
     * @return object
     * @throws coding_exception
     */
    public function render_html(object $record): object {
        $record->html = question_renderer::render($record);
        return $record;
    }

    /**
     * Save the question record.
     *
     * @param object|array $record
     * @return bool
     * @throws dml_exception
     */
    public function save_record(object|array $record): bool {
        $question = (object) $record;
        $question->surveyid = $this->get_survey_id();

        if (empty($question->surveyid)) {
            return false;
        }

        if (!empty($question->data)) {
            $question->data = json_encode(array_values($question->data));
        }

        if (empty($question->required)) {
            $question->required = 0;
        }

        if (empty($question->id)) {
            $question->timecreated = time();
            unset($question->id);

            return (bool) $this->db->insert_record('nicesurvey_questions', $question);
        }

        return $this->db->update_record('nicesurvey_questions', $question);
    }

    /**
     * Prepare the question record.
     *
     * @param object|array $record
     * @return object
     */
    protected function prepare_record(object|array $record): object {
        $record = (object) $record;
        $record->datatypes = [];

        foreach ($this->datamessages as $id => $name) {
            $record->datatypes[$id] = ['id' => $id, 'name' => $name, 'active' => $id == $record->datatype];
        }

        if (!empty($record->data)) {
            $record->data = json_decode($record->data);

            if (!empty($record->data[0]) && isset($record->data[0]->item) && $record->data[0]->item == 1) {
                $record->data[0]->ismain = true;
            }
        }

        $record->hasother = (int) ($record->hasother ?? 0);
        $record->required = (int) $record->required;
        $record->conditionid = (int) ($record->conditionid ?? 0);
        $record->datalength = !$record->data ? 0 : count($record->data);
        $record->annotation = $record->datatypes[$record->datatype]['name'] ?? null;
        $record->otherincluded = $record->datatype == 'multichoice';
        $record->datatypes = array_values($record->datatypes);

        $record->removequestionurl = new moodle_url('/mod/nicesurvey/questions.php', [
            'id' => $this->cmid,
            'questionid' => $record->id,
            'removekey' => $this->sesskey,
        ]);

        $record->copyquestionurl = new moodle_url('/mod/nicesurvey/questions.php', [
            'id' => $this->cmid,
            'questionid' => $record->id,
            'copykey' => $this->sesskey,
        ]);

        $record->movequestionupurl = new moodle_url('/mod/nicesurvey/questions.php', [
            'id' => $this->cmid,
            'questionid' => $record->id,
            'direction' => -1,
        ]);

        $record->movequestiondownurl = new moodle_url('/mod/nicesurvey/questions.php', [
            'id' => $this->cmid,
            'questionid' => $record->id,
            'direction' => 1,
        ]);

        return $record;
    }

    /**
     * Prepare a conditionship question.
     *
     * @param object $record
     * @return object
     */
    protected function prepare_record_relactions(object $record): object {
        if ($record->conditionid) {
            $record->condition = $this->records[$record->conditionid];

            if (!empty($record->condition->data)) {
                $record->condition->data = array_map(function ($data) use ($record) {
                    $data->active = $data->value == $record->conditionvalue;
                    return $data;
                }, $record->condition->data);
            }
        }

        $record->conditions = $this->get_conditions($record->conditionid);
        $record->conditionslength = count($record->conditions);

        return $record;
    }

    /**
     * Prepare questions.
     *
     * @return void
     */
    protected function prepare_records(): void {
        $this->records = array_map([$this, 'prepare_record'], $this->records);
        $this->records = array_map([$this, 'prepare_record_relactions'], $this->records);
    }
}