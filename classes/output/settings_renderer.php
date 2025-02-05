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

namespace format_ludilearn\output;

use core_reportbuilder\external\filters\set;
use format_ludilearn\local\gameelements\game_element;
use moodle_url;
use plugin_renderer_base;

/**
 * Renderer for the Ludilearn elements settings.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_renderer extends plugin_renderer_base {

    /**
     * Render the settings page.
     *
     * @param settings $settings Settings renderable
     *
     * @return string HTML to output.
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    public function render_settings(settings $settings): string {
        global $DB;

        // Render menu.
        $rendermenu = $this->render_menu($settings->get_courseid(), $settings->get_url()->out(false));

        // Call js.
        $this->page->requires->js_call_amd('format_ludilearn/settings', 'init',
            ['courseid' => $settings->get_courseid(),
                'type' => $settings->get_type(),
                'parameterslist' => $settings->get_parameterslist()]);

        // If the type is not a game element but the assignment by section setting page.
        if ($settings->get_type() == 'assignmentbysection') {
            return $rendermenu . $this->render_from_template('format_ludilearn/settings_assignment_by_section',
                    $settings->export_for_template($this));
        } else if ($settings->get_type() == 'updateprogression') {
            return $rendermenu . $this->render_from_template('format_ludilearn/settings_update_progression',
                    $settings->export_for_template($this));
        }

        return $rendermenu . $this->render_from_template('format_ludilearn/' . $settings->get_type() . '/settings',
                $settings->export_for_template($this));
    }

    /**
     * Render the settings menu.
     *
     * @param int $courseid     Course ID.
     * @param string $activeurl Active URL.
     *
     * @return string HTML to output.
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     */
    public function render_menu(int $courseid, string $activeurl): string {
        global $CFG;
        $menu = [];

        // Settings part.
        $settingsview = [];

        // Course format options.
        $format = course_get_format($courseid);
        $assignment = $format->get_format_options()['assignment'];

        // Global game element settings.
        $params = ['id' => $courseid, 'type' => 'assignmentbysection', 'hideheader' => 1];
        $url = new moodle_url("$CFG->wwwroot/course/format/ludilearn/settings_game_elements.php", $params);
        if ($assignment == 'bysection') {
            $settingsview[$url->out(false)] = get_string('assignmentbysection', 'format_ludilearn');
        }

        // Game elements settings by type.
        foreach (game_element::get_all_types() as $type) {
            $params = ['id' => $courseid, 'type' => $type, 'hideheader' => 1];
            $url = new moodle_url("$CFG->wwwroot/course/format/ludilearn/settings_game_elements.php", $params);
            $settingsview[$url->out(false)] = get_string($type, 'format_ludilearn');
        }

        // Tools part.
        $toolsview = [];
        $params = ['id' => $courseid, 'type' => 'updateprogression', 'hideheader' => 1];
        $url = new moodle_url("$CFG->wwwroot/course/format/ludilearn/settings_game_elements.php", $params);
        $toolsview[$url->out(false)] = get_string('settings:updateprogression', 'format_ludilearn');

        // Render tertiary navigation.
        $menu[][get_string('settings')] = $settingsview;
        $menu[][get_string('tools', 'format_ludilearn')] = $toolsview;
        $selectmenu = new \core\output\select_menu('settings', $menu, $activeurl);
        $selectmenu->set_label(get_string('settings'), ['class' => 'sr-only']);
        $options = \html_writer::tag(
            'div',
            $this->render_from_template('core/tertiary_navigation_selector', $selectmenu->export_for_template($this)),
            ['class' => 'row pb-3']
        );
        return \html_writer::tag(
            'div',
            $options,
            ['class' => 'tertiary-navigation full-width-bottom-border ml-0', 'id' => 'tertiary-navigation']);
    }
}
