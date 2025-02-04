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
 * Contains the default content output class.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_ludilearn\output\courseformat;

use core_courseformat\output\local\content as content_base;
use renderer_base;
use stdClass;

/**
 * Base class to render a course content.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * @var bool Ludilearn format has add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     *
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;
        $PAGE->requires->js_call_amd('format_ludilearn/mutations', 'init');
        $PAGE->requires->js_call_amd('format_ludilearn/cm', 'init');
        return parent::export_for_template($output);
    }
}
