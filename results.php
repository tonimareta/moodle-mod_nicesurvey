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
 * Results page.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_nicesurvey\answer;
use mod_nicesurvey\output\results_renderer;
use mod_nicesurvey\question;

require_once('../../config.php');
require_once('lib.php');
require_once("{$CFG->libdir}/phpspreadsheet/vendor/autoload.php");

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'nicesurvey');
$survey = $DB->get_record('nicesurvey', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$question = new question($id, $survey);
$answer = new answer($question);
$deleteanswers = filter_input(INPUT_POST, 'deleteanswers', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

require_course_login($course, true, $cm);
require_capability('mod/nicesurvey:results', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/nicesurvey/results.php', ['id' => $cm->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($survey->name);
$PAGE->set_heading($survey->name);
$PAGE->requires->js_call_amd('mod_nicesurvey/results', 'init');

if (!empty($deleteanswers) && optional_param('deleteanswerskey', '', PARAM_RAW) === $question->sesskey) {
    $status = $answer->delete_records($survey->id, $deleteanswers) ? 'success' : 'failed';
    redirect($PAGE->url, get_string("savestatus{$status}", 'mod_nicesurvey'));
}

$answers = $answer->get_records(false);
$userids = array_unique(array_column($answers, 'userid'));
$users = $DB->get_records_select('user', 'id IN(' . implode(',', $userids) . ')');

$answeredusers = array_map(function ($user) use ($id, $survey) {
    $user->profileurl = !$survey->anonymous ? new moodle_url('/user/profile.php', ['id' => $user->id]) : '#';
    $user->fullname = !$survey->anonymous ? trim($user->lastname . ' ' . $user->firstname . ' ' . $user->middlename) : md5($user->id);

    return $user;
}, $users);

$courseusers = !$survey->anonymous ? enrol_get_course_users($course->id, true) : [];
$notansweredusers = [];

foreach ($courseusers as $user) {
    if (empty($users[$user->id])) {
        $notansweredusers[$user->id] = [
            'id' => $user->id,
            'fullname' => trim($user->lastname . ' ' . $user->firstname . ' ' . $user->middlename),
            'profileurl' => new moodle_url('/user/profile.php', ['id' => $user->id]),
        ];
    }
}

array_multisort(array_column($answeredusers, 'fullname'), SORT_NATURAL, $answeredusers);
array_multisort(array_column($notansweredusers, 'fullname'), SORT_NATURAL, $notansweredusers);
$forexport = optional_param('exportkey', '', PARAM_RAW) === $question->sesskey;
$results = $answer->get_results($answers, $users, $forexport);

if ($forexport) {
    $renderer = new results_renderer($survey->name, $results, $answeredusers, (bool) $survey->anonymous);
    $renderer->render_xls();
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_nicesurvey/results', [
    'cmid' => $id,
    'results' => $results,
    'users' => array_values($answeredusers),
    'numofusers' => count($users),
    'numofallusers' => count($courseusers),
    'numofnotansweredusers' => count($notansweredusers),
    'notansweredusers' => array_values($notansweredusers),
    'sesskey' => $question->sesskey,
    'exporturl' => new moodle_url('/mod/nicesurvey/results.php', ['id' => $id, 'exportkey' => $question->sesskey]),
    'anonymous' => $survey->anonymous,
]);
echo $OUTPUT->footer();