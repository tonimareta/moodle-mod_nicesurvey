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

namespace mod_nicesurvey\output;

use coding_exception;
use html_writer;

/**
 * Nice Survey question renderer.
 *
 * @package    mod_nicesurvey
 * @copyright  2026 Anton Mareta
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_renderer {
    /**
     * Display question.
     *
     * @param object $record
     * @return string
     * @throws coding_exception
     */
    public static function render(object $record): string {
        return match ($record->datatype) {
            'answer' => self::render_answer($record),
            'text' => self::render_text($record),
            'dropdown' => self::render_dropdown($record),
            'onechoice', 'multichoice', 'scale' => self::render_choices($record),
            default => '',
        };
    }

    /**
     * Display answer question type.
     *
     * @param object $record
     * @return string
     */
    public static function render_answer(object $record): string {
        return html_writer::empty_tag('input', array_filter([
            'class' => 'form-control',
            'name' => "answer[{$record->id}][{$record->datatype}]",
            'type' => 'text',
            'value' => '',
            'required' => $record->required ? 'required' : null,
        ]));
    }

    /**
     * Display question with choices.
     *
     * @param object $record
     * @return string
     * @throws coding_exception
     */
    public static function render_choices(object $record): string {
        $choices = array_values(array_map(function($choice) use ($record) {
            $attributes = [];
            $attributes['id'] = "answer-{$record->id}-{$choice->item}";
            $attributes['class'] = 'mr-1';
            $attributes['type'] = $record->datatype == 'multichoice' ? 'checkbox' : 'radio';
            $attributes['name'] = "answer[{$record->id}][{$record->datatype}]" . ($record->datatype == 'multichoice' ? '[]' : '');
            $attributes['value'] = $choice->value;

            if ($record->required) {
                $attributes['required'] = 'required';
            }

            $input = html_writer::empty_tag('input', $attributes)
                . html_writer::label($choice->value, $attributes['id']);

            return html_writer::div($input, 'form-group ' . ($record->datatype == 'scale' ? 'me-3' : ''));
        }, $record->data));

        if ($record->hasother) {
            $input = html_writer::label(get_string('otheroption', 'mod_nicesurvey'), "answer-{$record->id}-other");
            $input .= html_writer::empty_tag('input', [
                'id' => "answer-{$record->id}-other",
                'class' => 'form-control',
                'type' => 'text',
                'name' => "answer[{$record->id}][{$record->datatype}_other]" . (
                    $record->datatype == 'multichoice' ? '[]' : ''
                    ),
                'value' => '',
            ]);

            $choices[] = html_writer::div($input, 'form-group');
        }

        return html_writer::alist($choices, ['class' => 'list-unstyled ' . ($record->datatype == 'scale' ? 'd-flex flex-wrap w-100' : '')]);
    }

    /**
     * Display dropdown question type.
     *
     * @param object $record
     * @return string
     */
    public static function render_dropdown(object $record): string {
        $options =  array_column($record->data, 'value', 'value');
        $dropdown = html_writer::select($options, "answer[{$record->id}][{$record->datatype}]");

        return html_writer::div($dropdown, 'form-group');
    }

    /**
     * Display text question type.
     *
     * @param object $record
     * @return string
     */
    public static function render_text(object $record): string {
        return html_writer::tag('textarea', '', array_filter([
            'class' => 'form-control',
            'name' => "answer[{$record->id}][{$record->datatype}]",
            'rows' => 5,
            'required' => $record->required ? 'required' : null,
        ]));
    }
}