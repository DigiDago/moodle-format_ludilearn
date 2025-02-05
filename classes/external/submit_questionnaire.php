<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace format_ludilearn\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_multiple_structure;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\local\adaptation\suggestion_output;
use format_ludilearn\manager;
use stdClass;

/**
 * Class for submit questionnaire.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_questionnaire extends external_api {

    /**
     * Execute the webservice.
     *
     * @param array $answers Array of answers.
     *
     * @return array The web service return.
     * @throws \dml_exception
     */
    public static function execute(array $answers): array {
        global $DB, $USER;

        $success = true;

        foreach ($answers as $answer) {
            $existing = $DB->get_record('format_ludilearn_answers',
                ['userid' => $USER->id, 'questionid' => $answer['id']]);
            if ($existing) {
                $existing->score = $answer['score'];
                $success = $success && $DB->update_record('format_ludilearn_answers', $existing);
            } else {
                $notexisting = new stdClass();
                $notexisting->userid = $USER->id;
                $notexisting->questionid = $answer['id'];
                $notexisting->score = $answer['score'];
                $success = $success && $DB->insert_record('format_ludilearn_answers', $notexisting);
            }
        }

        if ($success) {
            suggestion_output::generate_suggestion_based_on_affinities($USER->id);
        }

        return [
            'success' => $success,
        ];
    }

    /**
     * Get the webservice parameters.
     *
     * @return external_function_parameters The webservice parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        $parameters = [
            'answers' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Answer id'),
                    'score' => new external_value(PARAM_TEXT, 'Answer score'),
                ])
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get the return structure.
     *
     * @return external_single_structure The webservice return structure.
     */
    public static function execute_returns(): external_single_structure {
        $keys = [
            'success' => new external_value(
                PARAM_BOOL,
                'Success of the update',
                VALUE_REQUIRED
            ),
        ];

        return new external_single_structure(
            $keys,
            'submit_questionnaire'
        );
    }
}
