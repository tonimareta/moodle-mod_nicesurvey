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
 * This page prints a particular instance of nice survey.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use mod_nicesurvey\answer;
use mod_nicesurvey\question;

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'nicesurvey');
$survey = $DB->get_record('nicesurvey', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$question = new question($id, $survey);
$answer = new answer($question);
$answers = filter_input(INPUT_POST, 'answer', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);

require_course_login($course, true, $cm);
require_capability('mod/nicesurvey:view', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/nicesurvey/view.php', ['id' => $cm->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($survey->name);
$PAGE->set_heading($survey->name);
$PAGE->requires->js_call_amd('mod_nicesurvey/main', 'init');

if (!empty($answers)) {
    $answer->save_records($answers);
    redirect($PAGE->url);
}

$surveyisopenned = true;

if ($survey->timestart) {
    $surveyisopenned = time() >= $survey->timestart;

    if ($survey->timeend) {
        $surveyisopenned = $surveyisopenned && time() <= $survey->timeend;
    }
}

if (!$surveyisopenned) {
    if (!is_siteadmin()) {
        $question->records = [];
    }

    notification::info(get_string('surveyisclosed', 'mod_nicesurvey', [
        'opened' => date('d.m.Y H:i', $survey->timestart),
        'closed' => !empty($survey->timeend)
            ? date('d.m.Y H:i', $survey->timeend)
            : '',
    ]));
}

if (!empty($answer->get_records())) {
    if (empty($survey->completionmessage)) {
        $survey->completionmessage = get_string('surveycompletionmessage', 'mod_nicesurvey');
    }

    notification::info($survey->completionmessage);
    $question->records = [];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_nicesurvey/view', [
    'survey' => $survey,
    'questions' => array_values(array_map(fn ($record) => $question->render_html($record), $question->records)),
    'questionslength' => count($question->records),
]);
echo $OUTPUT->footer();