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
 * Plugin library.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Add module instance to course.
 *
 * @param object $nicesurvey
 * @return int
 * @throws dml_exception|coding_exception
 */
function nicesurvey_add_instance(object $nicesurvey): int {
    global $DB;

    nicesurvey_prepare_instance($nicesurvey);
    $nicesurvey->timecreated = time();
    $nicesurvey->id = $DB->insert_record('nicesurvey', $nicesurvey);
    $DB->set_field('course_modules', 'instance', $nicesurvey->id, ['id' => $nicesurvey->coursemodule]);

    return $nicesurvey->id;
}

/**
 * Delete module instance.
 *
 * @param int $id
 * @return bool
 * @throws dml_exception
 */
function nicesurvey_delete_instance(int $id): bool {
    global $DB;

    if (!$nicesurvey = $DB->get_record('nicesurvey', ['id' => $id])) {
        return false;
    }

    if ($DB->record_exists('nicesurvey_questions', ['surveyid' => $id])) {
        $DB->delete_records('nicesurvey_questions', ['surveyid' => $id]);
    }

    if ($DB->record_exists('nicesurvey_answers', ['surveyid' => $id])) {
        $DB->delete_records('nicesurvey_answers', ['surveyid' => $id]);
    }

    return $DB->delete_records('nicesurvey', ['id' => $nicesurvey->id]);
}

/**
 * Extends settings navigation on module page.
 *
 * @param settings_navigation $navigation
 * @return void
 * @throws \core\exception\moodle_exception
 * @throws \core\exception\coding_exception
 */
function nicesurvey_extend_settings_navigation(settings_navigation $navigation): void {
    global $PAGE;

    if (has_capability('mod/nicesurvey:edit', context_module::instance($PAGE->cm->id))) {
        if ($main_node = $navigation->find('modulesettings', null)) {
            foreach (['questions', 'results'] as $node) {
                $menu_item = navigation_node::create(
                    get_string($node, 'mod_nicesurvey'),
                    new moodle_url("/mod/nicesurvey/{$node}.php", ['id' => $PAGE->cm->id]),
                    navigation_node::TYPE_SETTING,
                    "nicesurvey{$node}",
                    "nicesurvey{$node}",
                    new pix_icon('monologo', '', 'nicesurvey')
                );

                $main_node->add_node($menu_item, 'modedit');
            }
        }
    }
}

/**
 * Prepare module instance.
 *
 * @param object $nicesurvey
 * @return void
 * @throws coding_exception
 */
function nicesurvey_prepare_instance(object $nicesurvey): void {
    if ($palette = optional_param_array('colors', [], PARAM_RAW)) {
        $nicesurvey->palette = implode(';', $palette);
    }

    if (!optional_param('nicesurveyhasdates', null, PARAM_INT)) {
        $nicesurvey->timestart = null;
        $nicesurvey->timeend = null;
    }

    if ($nicesurvey->completionmessage) {
        $nicesurvey->completionmessage = $nicesurvey->completionmessage['text'] ?? '';
    }
}

/**
 * Module supports.
 *
 * @param string $feature
 * @return string|bool|null
 */
function nicesurvey_supports(string $feature): bool|string|null {
    switch ($feature) {
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COMMUNICATION;
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_MOD_INTRO:
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;
        default:
            return null;
    }
}

/**
 * Update module instance.
 *
 * @param object $nicesurvey
 * @return bool
 * @throws dml_exception|coding_exception
 */
function nicesurvey_update_instance(object $nicesurvey): bool {
    global $DB;

    nicesurvey_prepare_instance($nicesurvey);
    $nicesurvey->id = $nicesurvey->instance;
    $nicesurvey->timemodified = time();

    return $DB->update_record('nicesurvey', $nicesurvey);
}