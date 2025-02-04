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

/**
 * Specialised restore for Ludilearn course format.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised restore for Ludilearn course format.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_ludilearn_plugin extends restore_format_plugin {

    /**
     * Define the restore structure of the section plugin.
     *
     * @return array Array of restore paths.
     */
    protected function define_section_plugin_structure(): array {
        $paths = [];

        // Path to restore format_ludilearn_elements.
        $paths[] = new restore_path_element('gameelements', $this->get_pathfor('/gameelements'));

        // Path to restore format_ludilearn_attributio.
        $paths[] = new restore_path_element('attributions', $this->get_pathfor('/attributions'));

        // Path to restore format_ludilearn_params.
        $paths[] = new restore_path_element('params', $this->get_pathfor('/params'));

        // Path to restore format_ludilearn_bysection.
        $paths[] = new restore_path_element('bysection', $this->get_pathfor('/bysection'));

        // Path to restore format_ludilearn_ele_user.
        $paths[] = new restore_path_element('gameele_user', $this->get_pathfor('/gameele_user'));

        return $paths;
    }

    /**
     * Define the restore structure of the module plugin.
     *
     * @return array Array of restore paths.
     */
    protected function define_module_plugin_structure(): array {
        $paths = [];

        // Path to restore format_ludilearn_cm_params.
        $paths[] = new restore_path_element('cm_params', $this->get_pathfor('/cm_params'));

        // Path to restore format_ludilearn_cm_user.
        $paths[] = new restore_path_element('cm_user', $this->get_pathfor('/cm_user'));

        return $paths;
    }

    /**
     * Process restore format_ludilearn_elements.
     *
     * @param array $data Data to restore.
     *
     * @return void
     * @throws dml_exception
     */
    public function process_gameelements(array $data): void {
        global $DB;

        $data = (object)$data;

        // Mapping of ids.
        $data->courseid = $this->task->get_courseid();
        $data->sectionid = $this->task->get_sectionid();

        // Insert data into the table format_ludilearn_elements.
        $newitemid = $DB->insert_record('format_ludilearn_elements', $data);
        $this->set_mapping('gameelements', $data->id, $newitemid, true);
    }

    /**
     * Process restore format_ludilearn_params.
     *
     * @param array $data Data to restore.
     *
     * @return void
     * @throws dml_exception
     */
    public function process_params(array $data): void {
        global $DB;

        $data = (object)$data;

        // Mapping of gameelementid.
        $data->gameelementid = $this->get_mappingid('gameelements', $data->gameelementid);

        if ($data->gameelementid == 0) {
            return;
        }

        // Insert data into the table format_ludilearn_params.
        $newitemid = $DB->insert_record('format_ludilearn_params', $data);
        $this->set_mapping('params', $data->id, $newitemid, true);
    }

    /**
     * Process restore format_ludilearn_bysection.
     *
     * @param array $data Data to restore.
     *
     * @return void
     * @throws dml_exception
     */
    public function process_bysection(array $data): void {
        global $DB;

        $data = (object)$data;

        // Mapping of ids.
        $data->courseid = $this->task->get_courseid();
        $data->gameelementid = $this->get_mappingid('gameelements', $data->gameelementid);
        $data->sectionid = $this->task->get_sectionid();

        if ($data->gameelementid == 0 || $data->sectionid == 0) {
            return;
        }

        // Insert data into the table format_ludilearn_bysection.
        $newitemid = $DB->insert_record('format_ludilearn_bysection', $data);
        $this->set_mapping('bysection', $data->id, $newitemid, true);
    }

    /**
     * Process restore format_ludilearn_cm_params.
     *
     * @param array $data Data to restore.
     *
     * @return void
     * @throws dml_exception
     */
    public function process_cm_params(array $data): void {
        global $DB;

        $data = (object)$data;

        // Mapping of ids.
        $data->gameelementid = $this->get_mappingid('gameelements', $data->gameelementid);
        $data->cmid = $this->task->get_moduleid();

        if ($data->gameelementid == 0) {
            return;
        }

        // Insert data into the table format_ludilearn_cm_params.
        $newitemid = $DB->insert_record('format_ludilearn_cm_params', $data);
        $this->set_mapping('cm_params', $data->id, $newitemid, true);
    }

    /**
     * Process restore format_ludilearn_attributio.
     *
     * @param array $data Data to restore.
     *
     * @return void
     * @throws dml_exception
     */
    public function process_attributions(array $data): void {
        global $DB;

        $data = (object)$data;

        // Mapping of ids.
        $data->gameelementid = $this->get_mappingid('gameelements', $data->gameelementid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        if ($data->gameelementid == 0 || $data->userid == 0) {
            return;
        }

        // Insert data into the table format_ludilearn_attributio.
        $newitemid = $DB->insert_record('format_ludilearn_attributio', $data);
        $this->set_mapping('attributions', $data->id, $newitemid, true);
    }

    /**
     * Process restore format_ludilearn_ele_user.
     *
     * @param array $data Data to restore.
     *
     * @return void
     * @throws dml_exception
     */
    public function process_gameele_user(array $data): void {
        global $DB;

        $data = (object)$data;

        // Mapping of ids.
        $data->attributionid = $this->get_mappingid('attributions', $data->attributionid);

        if ($data->attributionid == 0) {
            return;
        }

        // Insert data into the table format_ludilearn_ele_user.
        $newitemid = $DB->insert_record('format_ludilearn_ele_user', $data);
        $this->set_mapping('gameele_user', $data->id, $newitemid, true);
    }

    /**
     * Process restore format_ludilearn_cm_user.
     *
     * @param array $data Data to restore.
     *
     * @return void
     * @throws dml_exception
     */
    public function process_cm_user(array $data): void {
        global $DB;

        $data = (object)$data;

        // Mapping of ids.
        $data->attributionid = $this->get_mappingid('attributions', $data->attributionid);
        $data->cmid = $this->task->get_moduleid();

        if ($data->attributionid == 0) {
            return;
        }

        // Insert data into the table format_ludilearn_cm_user.
        $newitemid = $DB->insert_record('format_ludilearn_cm_user', $data);
        $this->set_mapping('cm_user', $data->id, $newitemid, true);
    }
}
