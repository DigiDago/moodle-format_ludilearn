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
 * Contains the cm control menu output class.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludilearn\output\courseformat\content\cm;

use action_menu_link_secondary;
use context_module;
use core_courseformat\output\local\content\cm\controlmenu as controlmenu_base;
use format_ludilearn\local\gameelements\game_element;
use moodle_url;
use pix_icon;

/**
 * Base class to render a control menu content.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controlmenu extends controlmenu_base {

    /**
     * Generate the edit control items of a course module.
     *
     * This method uses course_get_cm_edit_actions function to get the cm actions.
     * However, format plugins can override the method to add or remove elements
     * from the menu.
     *
     * @return array of edit control items
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     * @throws \dml_exception
     */
    protected function cm_control_items(): array {
        $modcontext = context_module::instance($this->mod->id);
        $editactions = parent::cm_control_items();

        if (has_capability('moodle/course:manageactivities', $modcontext)) {
            $url = $modcontext->get_url();
            $gamified = game_element::is_gamified($this->mod->id);
            if ($gamified) {
                $url->param('gamify', 0);
                $str = get_string('notgamify', 'format_ludilearn');
                $icon = new pix_icon('i/checkedcircle', '', 'moodle', ['class' => 'iconsmall']);
                $action = 'cmNotgamify';

                // State after action.
                $swapname = get_string('gamify', 'format_ludilearn');
                $swapicon = 'i/uncheckedcircle';
            } else {
                $url->param('gamify', 1);
                $str = get_string('gamify', 'format_ludilearn');
                $icon = new pix_icon('i/uncheckedcircle', '', 'moodle', ['class' => 'iconsmall']);
                $action = 'cmGamify';

                // State after action.
                $swapname = get_string('notgamify', 'format_ludilearn');
                $swapicon = 'i/checkedcircle';
            }

            $editactions[] = new action_menu_link_secondary(
                new moodle_url($url, ['id' => $this->mod->id, 'gamified' => !$gamified]),
                $icon,
                $str,
                [
                    'class' => 'editing_gamify',
                    'data-action' => $action,
                    'data-id' => $this->mod->id,
                    'data-swapname' => $swapname,
                    'data-swapicon' => $swapicon,
                ]
            );
        }

        return $editactions;
    }
}
