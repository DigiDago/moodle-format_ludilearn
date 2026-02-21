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

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Behat custom steps for format_ludilearn.
 *
 * @package          format_ludilearn
 * @copyright        2026 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Amine NEDJAR <amine.nedjar4716@gmail.com>
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_format_ludilearn extends behat_base {
    /**
     * Opens the core course settings page for a course shortname.
     *
     * @Given /^I visit Ludilearn course settings page for "(?P<shortname_string>(?:[^"\\]|\\.)*)"$/
     * @param string $shortname
     */
    public function i_visit_ludilearn_course_settings_page_for(string $shortname): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $shortname], 'id', MUST_EXIST);
        $this->getSession()->visit($this->locate_path('/course/edit.php?id=' . $course->id));
    }

    /**
     * Opens the Ludilearn game element settings page for a course shortname.
     *
     * @Given /^I visit Ludilearn game elements settings page for "(?P<shortname_string>(?:[^"\\]|\\.)*)"$/
     * @param string $shortname
     */
    public function i_visit_ludilearn_game_elements_settings_page_for(string $shortname): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $shortname], 'id', MUST_EXIST);
        $this->getSession()->visit($this->locate_path('/course/format/ludilearn/settings_game_elements.php?id=' . $course->id));
    }
}
