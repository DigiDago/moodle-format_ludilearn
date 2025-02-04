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

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\manager;

/**
 * Class for update course module parameters.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_cm_parameters extends external_api {

    /**
     * Execute the webservice.
     *
     * @param int $courseid      Id of the course.
     * @param int $gameelementid Id of the game element.
     * @param int $cmid          Id of the course module.
     * @param string $name       Name of the parameter.
     * @param string $value      Value of the parameter.
     *
     * @return array The web service return.
     */
    public static function execute(int $courseid, int $gameelementid, int $cmid, string $name, string $value): array {

        $context = context_course::instance($courseid);
        self::validate_context($context);
        $manager = new manager();

        return [
            'success' => $manager->update_cm_parameter($gameelementid, $cmid, $name, $value),
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
            'gameelementid' => new external_value(
                PARAM_INT,
                'Game element ID',
                VALUE_REQUIRED
            ),
            'cmid' => new external_value(
                PARAM_INT,
                'Course module ID',
                VALUE_REQUIRED
            ),
            'name' => new external_value(
                PARAM_TEXT,
                'Name of the parameter',
                VALUE_REQUIRED
            ),
            'value' => new external_value(
                PARAM_TEXT,
                'Value of the parameter',
                VALUE_REQUIRED
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get webservice return structure.
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
            'update_cm_parameters'
        );
    }
}
