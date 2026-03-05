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
use context_system;
use external_multiple_structure;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\manager;
use stdClass;

/**
 * Class for get state.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_state extends external_api {
    /**
     * Execute the webservice.
     *
     * @param string $ressource Ressource get state (course, section, cm).
     * @param int $ressourceid   Id of the ressource.
     *
     * @return string The web service return.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function execute(string $ressource, int $ressourceid): string {
        global $PAGE;
        require_login();
        $PAGE->set_context(context_system::instance());
        $manager = new manager();
        $state = $manager->get_state($ressource, $ressourceid);
        return $state;
    }

    /**
     * Get webservice parameters structure.
     *
     * @return external_function_parameters The webservice parameters structure.
     */
    public static function execute_parameters(): external_function_parameters {
        $parameters = [
            'ressource' => new external_value(
                PARAM_TEXT,
                'Ressource get state (course, section, cm)',
                VALUE_REQUIRED
            ),
            'ressourceid' => new external_value(
                PARAM_INT,
                'Ressource id (course id, section id, cm id)',
                VALUE_REQUIRED
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get webservice return structure.
     *
     * @return external_value The webservice return structure.
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_RAW, 'Encoded course state JSON');
    }
}
