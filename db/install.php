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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     format_ludilearn
 * @category    upgrade
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom code to be run on installing the plugin.
 *
 * Function xmldb_format_ludilearn_install
 *
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function xmldb_format_ludilearn_install() {
    global $DB;
    // Insert question data.
    $questions = [];
    $questions[] = ['content' => 'questionnaire:question1',
        'label' => get_string('philanthropist', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question2',
        'label' => get_string('socialiser', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question3',
        'label' => get_string('philanthropist', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question4',
        'label' => get_string('socialiser', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question5',
        'label' => get_string('achiever', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question6',
        'label' => get_string('achiever', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question7',
        'label' => get_string('free_spirit', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question8',
        'label' => get_string('disruptor', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question9',
        'label' => get_string('player', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question10',
        'label' => get_string('free_spirit', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question11',
        'label' => get_string('disruptor', 'format_ludilearn'), 'type' => 'HEXAD'];
    $questions[] = ['content' => 'questionnaire:question12',
        'label' => get_string('player', 'format_ludilearn'), 'type' => 'HEXAD'];

    $DB->insert_records('format_ludilearn_questions', $questions);

    return true;
}
