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
 * Edit questions.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_nicesurvey\question;

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'nicesurvey');
$survey = $DB->get_record('nicesurvey', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$question = new question($id, $survey);
$questiondata = filter_input(INPUT_POST, 'question', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

require_course_login($course, true, $cm);
require_capability('mod/nicesurvey:edit', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/nicesurvey/questions.php', ['id' => $cm->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($survey->name);
$PAGE->set_heading($survey->name);
$PAGE->requires->js_call_amd('mod_nicesurvey/questions', 'init');

$status = null;

if (optional_param('export', '', PARAM_RAW) == $question->sesskey) {
    $question->export_records();
}

if ($direction = optional_param('direction', 0, PARAM_INT)) {
    $recordid = optional_param('questionid', 0, PARAM_INT);
    $status = $question->move_record($recordid, $direction) ? 'success' : 'failed';
}

if (optional_param('copykey', '', PARAM_RAW) == $question->sesskey) {
    $recordid = optional_param('questionid', 0, PARAM_INT);
    $status = $question->copy_record($recordid) ? 'success' : 'failed';
}

if (optional_param('removekey', '', PARAM_RAW) == $question->sesskey) {
    $recordid = optional_param('questionid', 0, PARAM_INT);
    $status = $question->remove_record($recordid) ? 'success' : 'failed';
}

if (!empty($questiondata)) {
    $status = $question->save_record($questiondata) ? 'success' : 'failed';
}

if ($status) {
    redirect($PAGE->url, get_string("savestatus{$status}", 'mod_nicesurvey'));
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_nicesurvey/questions', [
    'survey' => $survey,
    'questions' => array_values($question->records),
    'questionslength' => count($question->records),
    'newquestion' => $question->create_question_record(),
    'exporturl' => new moodle_url('/mod/nicesurvey/questions.php', ['id' => $cm->id, 'export' => $question->sesskey]),
]);
echo $OUTPUT->footer();