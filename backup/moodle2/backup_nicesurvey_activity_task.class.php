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

require_once("{$CFG->dirroot}/mod/nicesurvey/backup/moodle2/backup_nicesurvey_stepslib.php");

/**
 * Module backup activity task.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_nicesurvey_activity_task extends backup_activity_task {
    /**
     * Define backup settings.
     *
     * @return void
     */
    protected function define_my_settings(): void {
        // The module does not have any specific settings.
    }

    /**
     * Define backup steps.
     *
     * @return void
     * @throws base_task_exception
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_nicesurvey_activity_structure_step('nicesurvey_structure', 'nicesurvey.xml'));
    }

    /**
     * Encode content links.
     *
     * @param mixed $content
     * @return string
     */
    static public function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $query = '/(' . $base . '\/mod\/nicesurvey\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($query, '$@NICESURVEYINDEX*$2@$', $content);
        $viewquery = '/(' . $base . '\/mod\/nicesurvey\/view.php\?id\=)([0-9]+)/';

        return preg_replace($viewquery, '$@NICESURVEYVIEWBYID*$2@$', $content);
    }
}
