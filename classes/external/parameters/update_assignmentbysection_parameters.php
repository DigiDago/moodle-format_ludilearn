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

namespace format_ludimoodle\external\parameters;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_course;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use format_ludimoodle\manager;

/**
 * Class for update assignment by section.
 *
 * @package     format_ludimoodle
 * @copyright   2024 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_assignmentbysection_parameters extends external_api {

    /**
     * Update course module parameters.
     *
     * @param int $courseid Id of the course.
     * @param array $sections Sections to update.
     *
     * @return array
     */
    public static function execute(int $courseid, array $sections): array {

        $context = context_course::instance($courseid);
        self::validate_context($context);
        $manager = new manager();
        try {
            foreach ($sections as $section) {
                $manager->attribution_by_section($courseid, $section['id'], $section['gameelementid']);
            }
        } catch (\Exception $e) {
            return [
                'success' => false
            ];
        }
        return [
            'success' => true
        ];
    }

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        $parameters = [
            'courseid' => new external_value(
                PARAM_INT,
                'Course ID',
                VALUE_REQUIRED
            ),
            'sections'  => new external_multiple_structure(
                new external_single_structure(
                    [
                        'id' => new external_value(
                            PARAM_INT,
                            'Section ID',
                            VALUE_REQUIRED
                        ),
                        'gameelementid' => new external_value(
                            PARAM_INT,
                            'Game element ID',
                            VALUE_REQUIRED
                        )
                    ]
                ),
                'Sections to update',
                VALUE_REQUIRED
            )
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $keys = [
            'success' => new external_value(
                PARAM_BOOL,
                'Success of the update',
                VALUE_REQUIRED
            )
        ];

        return new external_single_structure(
            $keys,
            'update_score_parameters'
        );
    }
}
