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
 * This file defines the global nicesurvey administration form.
 * For NGMU only.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    if ($DB->record_exists('modules', ['name' => 'poll'])) {
        if (optional_param('migrate', '', PARAM_RAW) === sesskey()) {
            $manager = $DB->get_manager();
            $module = $DB->get_record('modules', ['name' => 'nicesurvey']);
            $oldmodule = $DB->get_record('modules', ['name' => 'poll']);

            if ($module && $oldmodule) {
                if ($DB->record_exists('course_modules', ['module' => $oldmodule->id])) {
                    if ($manager->table_exists('poll')) {
                        $polls = $DB->get_records('poll');

                        foreach ($polls as $poll) {
                            $coursemodule = $DB->get_record('course_modules', [
                                'course' => $poll->course,
                                'instance' => $poll->id,
                                'module' => $oldmodule->id
                            ]);

                            if (!$coursemodule || $DB->record_exists('nicesurvey',
                                    ['course' => $poll->course, 'timecreated' => $poll->created,
                                        'timemodified' => $poll->modified])) {
                                continue;
                            }

                            $survey = (object) [
                                'course' => $poll->course,
                                'name' => $poll->name,
                                'intro' => $poll->intro,
                                'introformat' => $poll->introformat,
                                'completionmessage' => $poll->completion_message,
                                'anonymous' => $poll->anonymous,
                                'palette' => $poll->colors,
                                'timestart' => $poll->opened,
                                'timeend' => $poll->closed,
                                'timecreated' => $poll->created,
                                'timemodified' => $poll->modified,
                            ];

                            if ($survey->id = $DB->insert_record('nicesurvey', $survey)) {
                                $coursemodule->instance = $survey->id;
                                $coursemodule->module = $module->id;

                                if (!$DB->update_record('course_modules', $coursemodule)) {
                                    continue;
                                }

                                if ($manager->table_exists('poll_questions')) {
                                    $pollquestions = $DB->get_records_select(
                                        'poll_questions',
                                        "module_id = ? AND data_type NOT IN('multiple_group', 'onechoice_group', 'file')",
                                        [$poll->id]
                                    );

                                    foreach ($pollquestions as $pollquestion) {
                                        $question = (object) [
                                            'title' => $pollquestion->title,
                                            'description' => $pollquestion->description,
                                            'surveyid' => $survey->id,
                                            'datatype' => $pollquestion->data_type == 'multiple' ? 'multichoice' :
                                                $pollquestion->data_type,
                                            'data' => $pollquestion->data,
                                            'hasother' => $pollquestion->has_other,
                                            'required' => $pollquestion->required,
                                            'sequence' => $pollquestion->sequence,
                                            'conditionid' => null,
                                            'conditionvalue' => null,
                                            'timecreated' => $pollquestion->created,
                                        ];

                                        if ($question->id = $DB->insert_record('nicesurvey_questions', $question)) {
                                            if ($pollquestion->relation_id) {
                                                if ($pollrelation = $DB->get_record('poll_questions', ['id' => $pollquestion->relation_id])) {
                                                    $relation = $DB->get_record('nicesurvey_questions', [
                                                        'title' => $pollrelation->title,
                                                        'surveyid' => $survey->id,
                                                        'datatype' => $pollrelation->data_type,
                                                        'hasother' => $pollrelation->has_other,
                                                        'required' => $pollrelation->required,
                                                        'sequence' => $pollrelation->sequence,
                                                        'timecreated' => $pollrelation->created,
                                                    ]);

                                                    if (!$relation) {
                                                        $relation = (object) [
                                                            'title' => $pollrelation->title,
                                                            'description' => $pollrelation->description,
                                                            'surveyid' => $survey->id,
                                                            'datatype' => $pollrelation->data_type,
                                                            'data' => $pollrelation->data,
                                                            'hasother' => $pollrelation->has_other,
                                                            'required' => $pollrelation->required,
                                                            'sequence' => $pollrelation->sequence,
                                                            'timecreated' => $pollrelation->created,
                                                        ];

                                                        $relation->id = $DB->insert_record('nicesurvey_questions', $relation);
                                                    }

                                                    if (!empty($relation->id)) {
                                                        $question->conditionid = $relation->id;
                                                        $question->conditionvalue = $pollquestion->relation_value;
                                                        $DB->update_record('nicesurvey_questions', $question);
                                                    }
                                                }
                                            }

                                            if ($manager->table_exists('poll_answers')) {
                                                $answers = [];
                                                $pollanswers = $DB->get_records_select(
                                                    'poll_answers',
                                                    "module_id = ? AND question_id = ?",
                                                    [$poll->id, $pollquestion->id]
                                                );

                                                foreach ($pollanswers as $answer) {
                                                    $answers[] = (object) [
                                                        'answer' => $answer->answer,
                                                        'surveyid' => $survey->id,
                                                        'questionid' => $question->id,
                                                        'userid' => $answer->user_id,
                                                        'timeanswered' => $answer->created,
                                                    ];
                                                }

                                                $DB->insert_records('nicesurvey_answers', $answers);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            purge_caches();
        }

        $settings->add(new admin_setting_heading(
            'migrate_nicesurvey',
            get_string('migrate', 'mod_nicesurvey'),
            html_writer::link(new moodle_url($PAGE->url, ['migrate' => sesskey()]), get_string('migrate', 'mod_nicesurvey')),
        ));
    }
}