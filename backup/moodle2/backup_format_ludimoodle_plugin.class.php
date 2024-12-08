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
 * Specialised backup for Ludimoodle course format.
 *
 * @package     format_ludimoodle
 * @copyright   2024 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised backup for Ludimoodle course format.
 *
 * @package     format_ludimoodle
 * @copyright   2024 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_format_ludimoodle_plugin extends backup_format_plugin {

    /**
     * Define the backup ludimoodle_plugin section structure.
     *
     * @return backup_plugin_element The ludimoodle_plugin structure.
     */
    protected function define_section_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'ludimoodle');

        // Table ludimoodle_gameelements.
        $gameelements = new backup_nested_element('gameelements', ['id'], [
            'type', 'courseid', 'sectionid', 'timecreated',
        ]);

        // Table ludimoodle_attribution.
        $attributions = new backup_nested_element('attributions', ['id'], [
            'gameelementid', 'userid', 'timecreated',
        ]);

        // Table ludimoodle_params.
        $params = new backup_nested_element('params', ['id'], [
            'gameelementid', 'name', 'value',
        ]);

        // Table ludimoodle_bysection.
        $bysection = new backup_nested_element('bysection', ['id'], [
            'courseid', 'gameelementid', 'sectionid',
        ]);

        // Table ludimoodle_gameele_user.
        $gameeleuser = new backup_nested_element('gameele_user', ['id'], [
            'attributionid', 'name', 'value',
        ]);

        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        // Add the child nodes.
        $pluginwrapper->add_child($gameelements);
        $pluginwrapper->add_child($attributions);
        $pluginwrapper->add_child($bysection);
        $pluginwrapper->add_child($params);
        $pluginwrapper->add_child($gameeleuser);
        $plugin->add_child($pluginwrapper);

        // Filter the data to select only the gameelements of the current section.
        $gameelements->set_source_table('ludimoodle_gameelements',
            ['courseid' => backup::VAR_COURSEID, 'sectionid' => backup::VAR_SECTIONID]);

        $bysection->set_source_sql('
            SELECT * FROM {ludimoodle_bysection} WHERE gameelementid IN (
                SELECT id FROM {ludimoodle_gameelements} WHERE courseid = :courseid AND sectionid = :sectionid)',
            ['courseid' => backup::VAR_COURSEID, 'sectionid' => backup::VAR_SECTIONID]
        );

        $params->set_source_sql('
            SELECT * FROM {ludimoodle_params} WHERE gameelementid IN (
                SELECT id FROM {ludimoodle_gameelements} WHERE courseid = :courseid AND sectionid = :sectionid)',
            ['courseid' => backup::VAR_COURSEID, 'sectionid' => backup::VAR_SECTIONID]
        );

        if ($this->get_setting_value('users')) {
            $attributions->set_source_sql('
            SELECT * FROM {ludimoodle_attribution} WHERE gameelementid IN (
                SELECT id FROM {ludimoodle_gameelements} WHERE courseid = :courseid AND sectionid = :sectionid)',
                ['courseid' => backup::VAR_COURSEID, 'sectionid' => backup::VAR_SECTIONID]
            );

            $gameeleuser->set_source_sql('
            SELECT * FROM {ludimoodle_gameele_user} WHERE attributionid IN (
                SELECT id FROM {ludimoodle_attribution} WHERE gameelementid IN (
                    SELECT id FROM {ludimoodle_gameelements} WHERE courseid = :courseid AND sectionid = :sectionid))',
                ['courseid' => backup::VAR_COURSEID, 'sectionid' => backup::VAR_SECTIONID]
            );
        } else {
            $attributions->set_source_array([]);
            $gameeleuser->set_source_array([]);
        }

        return $plugin;
    }

    /**
     * Define the backup ludimoodle_plugin module structure.
     *
     * @return backup_plugin_element The ludimoodle_plugin structure.
     */
    protected function define_module_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'ludimoodle');
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        // Table ludimoodle_cm_params.
        $cmparams = new backup_nested_element('cm_params', ['id'], [
            'gameelementid', 'cmid', 'name', 'value',
        ]);

        // Table ludimoodle_cm_user.
        $cmuser = new backup_nested_element('cm_user', ['id'], [
            'attributionid', 'cmid', 'name', 'value',
        ]);

        $pluginwrapper->add_child($cmparams);
        $pluginwrapper->add_child($cmuser);
        $plugin->add_child($pluginwrapper);

        if ($this->get_setting_value('users')) {
            $cmuser->set_source_sql('
            SELECT * FROM {ludimoodle_cm_user} WHERE attributionid IN (
                SELECT id FROM {ludimoodle_attribution} WHERE gameelementid IN (
                    SELECT id FROM {ludimoodle_gameelements} WHERE courseid = :courseid AND cmid = :cmid))',
                ['courseid' => backup::VAR_COURSEID, 'cmid' => backup::VAR_MODID]
            );

            $cmparams->set_source_sql('
            SELECT * FROM {ludimoodle_cm_params} WHERE gameelementid IN (
                SELECT id FROM {ludimoodle_gameelements} WHERE courseid = :courseid AND cmid = :cmid)',
                ['courseid' => backup::VAR_COURSEID, 'cmid' => backup::VAR_MODID]
            );
        } else {
            $cmuser->set_source_array([]);
            $cmparams->set_source_array([]);
        }

        return $plugin;
    }
}
