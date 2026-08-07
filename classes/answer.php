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
use core\exception\moodle_exception;
use dml_exception;
use moodle_database;
use moodle_url;

/**
 * Nice Survey user answer.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answer {
    /**
     * @var bool
     */
    public bool $forexport = false;

    /**
     * @var moodle_database
     */
    protected moodle_database $db;

    /**
     * @var question
     */
    protected question $question;

    /**
     * @var int
     */
    protected int $userid = 0;

    /**
     * Answer constructor.
     *
     * @param question $question
     */
    public function __construct(question $question) {
        global $DB, $USER;

        $this->db = $DB;
        $this->question = $question;
        $this->userid = $USER->id ?? 0;
    }

    /**
     * Delete answers from DB.
     *
     * @param int $id
     * @param array $userids
     * @return bool
     * @throws dml_exception
     */
    public function delete_records(int $id, array $userids = []) {
        return $this->db->delete_records_select('nicesurvey_answers', 'surveyid = ? AND userid IN (' . implode(',', $userids) . ')', [$id]);
    }

    /**
     * Get answers from DB.
     *
     * @param bool $forcurrentuseronly
     * @return array
     * @throws dml_exception
     */
    public function get_records(bool $forcurrentuseronly = true): array {
        $params = ['surveyid' => $this->question->get_survey_id()];

        if ($forcurrentuseronly) {
            $params['userid'] = $this->userid;
        }

        return $this->db->get_records('nicesurvey_answers', $params);
    }

    /**
     * Get answers results.
     *
     * @param array $answers
     * @param array $users
     * @param bool $forexport
     * @return array
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function get_results(array $answers, array $users, bool $forexport): array {
        $results = [];
        $othertext = get_string('otheroption', 'mod_nicesurvey');
        $emptyvalue = get_string('emptyvalue', 'mod_nicesurvey');
        $anonymous = $this->question->is_anonymous();

        foreach ($this->question->records as $record) {
            $results[$record->id]['id'] = $record->id;
            $results[$record->id]['title'] = $record->title;
            $results[$record->id]['description'] = $record->description;
            $results[$record->id]['annotation'] = $record->annotation;

            $values = !$record->data ? [] : array_column($record->data, 'value');

            foreach ($values as $value) {
                $uid = md5($value);
                $results[$record->id]['answers'][$uid]['uid'] = $uid;
                $results[$record->id]['answers'][$uid]['value'] = $value;
                $results[$record->id]['answers'][$uid]['datatype'] = $record->datatype;
            }

            foreach ($answers as $useranswer) {
                if ($useranswer->questionid == $record->id) {
                    $answervalues = explode(';', $useranswer->answer);

                    foreach ($answervalues as $answervalue) {
                        if (empty($users[$useranswer->userid])) {
                            continue;
                        }

                        $user = $users[$useranswer->userid];
                        $uservalue = $record->hasother && !empty($values) && !in_array($answervalue, $values)
                            ? $othertext
                            : $answervalue;

                        if (empty($uservalue)) {
                            $uservalue = $emptyvalue;
                        }

                        $uid = md5($uservalue);
                        $results[$record->id]['answers'][$uid]['uid'] = $uid;
                        $results[$record->id]['answers'][$uid]['value'] = $uservalue;
                        $results[$record->id]['answers'][$uid]['datatype'] = $record->datatype;

                        $results[$record->id]['answers'][$uid]['users'][$useranswer->userid]['userfullname'] = !$anonymous
                            ? trim($user->lastname . ' ' . $user->firstname . ' ' . $user->middlename)
                            : md5($useranswer->userid);

                        $results[$record->id]['answers'][$uid]['users'][$useranswer->userid]['userprofile'] = !$anonymous
                            ? new moodle_url('/user/profile.php', ['id' => $useranswer->userid])
                            : '#';

                        $results[$record->id]['answers'][$uid]['users'][$useranswer->userid]['useranswer'] = $answervalue;
                        $results[$record->id]['answers'][$uid]['users'][$useranswer->userid]['dateanswered'] = date('d.m.Y H:i:s', $useranswer->timeanswered);
                    }
                }
            }
        }

        $this->forexport = $forexport;
        return $this->prepare_results($results, $users);
    }

    /**
     * Save answers to DB.
     *
     * @param array $answers
     * @return void
     */
    public function save_records(array $answers): void {
        $records = [];

        foreach ($answers as $questionId => $answer) {
            foreach ($answer as $value) {
                if (is_array($value)) {
                    $value = implode(';', array_filter($value, 'strlen'));
                }

                $records[] = (object) [
                    'answer' => $value,
                    'surveyid' => $this->question->get_survey_id(),
                    'questionid' => $questionId,
                    'userid' => $this->userid,
                    'timeanswered' => time(),
                ];
            }
        }

        $this->db->insert_records('nicesurvey_answers', $records);
    }

    /**
     * Prepare the answer for template.
     *
     * @param array $answer
     * @param int $offset
     * @param array $palette
     * @param int $numofusers
     * @return object|null
     */
    protected function prepare_answer(array $answer, int &$offset, array &$palette, int $numofusers): ?object {
        $answer['users'] = $answer['users'] ?? [];
        $answer['amount'] = count($answer['users']);
        $answer['percent'] = !$numofusers ? 0 : $answer['amount'] * 100 / $numofusers;
        $answer['amountpercent'] = round($answer['percent']);

        $userids = array_keys($answer['users']);
        array_multisort(array_column($answer['users'], 'userfullname'), SORT_NATURAL, $answer['users'], $userids);
        $answer['users'] = array_combine($userids, $answer['users']);
        $answer['users'] = array_map(fn ($user) => (object) $user, $answer['users']);

        if (!$this->forexport) {
            $answer['users'] = array_values($answer['users']);
        }

        if (!in_array($answer['datatype'], ['text', 'answer'])) {
            if (!$color = next($palette)) {
                $color = reset($palette);
            }

            $answer['haschart'] = true;
            $answer['chart'] = (object) [
                'color' => $color,
                'height' => 16,
                'width' => $answer['percent'],
                'x' => 0,
                'y' => $offset,
            ];

            $offset += 24;
        }

        return (object) $answer;
    }

    /**
     * Prepare results for template.
     *
     * @param array $results
     * @param array $users
     * @return array
     */
    protected function prepare_results(array $results, array $users): array {
        $offset = 0;
        $palette = $this->question->get_pallete();
        $numofusers = count($users);

        return array_values(array_map(function ($result) use (&$offset, &$palette, $numofusers) {
            if (!empty($result['answers'])) {
                $result['answers'] = array_map(function ($answer) use (&$offset, &$palette, $numofusers) {
                    return $this->prepare_answer($answer, $offset, $palette, $numofusers);
                }, $result['answers']);

                if (!$this->forexport) {
                    $result['answers'] = array_values($result['answers']);
                }
            }

            return (object) $result;
        }, $results));
    }
}