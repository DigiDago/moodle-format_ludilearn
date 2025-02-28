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

namespace format_ludilearn\external\parameters;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\local\gameelements\score;

/**
 * Class for update course parameters for score elements.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_score_parameters extends external_api {

    /**
     * Execute the webservice.
     *
     * @param int $courseid             Id of the course.
     * @param int $multiplier           Multiplier for the score.
     * @param int $bonuscompletion      Bonus completion for the score.
     * @param int $percentagecompletion Percentage completion for the score.
     *
     * @return array The web service return.
     */
    public static function execute(int $courseid, int $multiplier, int $bonuscompletion, int $percentagecompletion): array {

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('format/ludilearn:manage', $context);

        return [
            'success' => score::update_course_parameters($courseid, $multiplier, $bonuscompletion, $percentagecompletion),
        ];
    }

    /**
     * Get webservice parameters structure.
     *
     * @return external_function_parameters The webservice parameters structure.
     */
    public static function execute_parameters(): external_function_parameters {
        $parameters = [
            'courseid' => new external_value(
                PARAM_INT,
                'Course ID',
                VALUE_REQUIRED
            ),
            'multiplier' => new external_value(
                PARAM_INT,
                'Multiplier',
                VALUE_REQUIRED
            ),
            'bonuscompletion' => new external_value(
                PARAM_INT,
                'Bonus completion',
                VALUE_REQUIRED
            ),
            'percentagecompletion' => new external_value(
                PARAM_INT,
                'Percentage completion',
                VALUE_REQUIRED
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get webservice returns structure.
     *
     * @return external_single_structure The webservice returns structure.
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
            'update_score_parameters'
        );
    }
}
