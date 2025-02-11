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

use core_courseformat\output\section_renderer;
use format_ludilearn\local\adaptation\hexad_scores;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\manager;
use moodle_exception;
use moodle_page;
use moodle_url;
use renderable;
use section_info;
use stdClass;

/**
 * Ludilearn Plus content class.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {

    /**
     * Constructor method, calls the parent constructor.
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_ludilearn_renderer::section_edit_control_items() only displays the 'Highlight' control
        // when editing mode is on we need to be sure that the link 'Turn editing mode on' is available for a user
        // who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param stdClass $course               The course entry from DB
     *
     * @return string HTML to output.
     */
    public function section_title($section, $course): string {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Generate the section title to be displayed on the section page, without a link.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course           The course entry from DB
     *
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course): string {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }

    /**
     * Render the questionnaire page.
     *
     * @param int $courseid Course ID.
     *
     * @return string HTML to output.
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     * @throws \dml_exception
     */
    public function render_questionnaire(int $courseid): string {
        global $DB, $CFG;

        $questions = $DB->get_records('format_ludilearn_questions');

        $data = new stdClass();
        $data->questionsHEXAD = [];
        $number = 1;
        foreach ($questions as $question) {
            $q = new stdClass();
            $q->id = $question->id;
            $q->content = get_string($question->content, 'format_ludilearn');
            $q->answers = [];
            $q->number = $number;

            $countanswers = 7;
            for ($i = 2; $i <= $countanswers; $i++) {
                $a = new stdClass();
                $a->id = $i;
                $a->value = $i;
                $q->answers[] = $a;
            }
            $data->questionsHEXAD[] = $q;
            $number++;
        }
        $data->questionscount = $number - 1;
        $urlgameprofile = $this->page->url;
        $urlgameprofile->param('gameprofile', true);
        $urlgameprofile->param('hideheader', 1);
        $urlgameprofile = new moodle_url($urlgameprofile->out(false), $urlgameprofile->params());
        $this->page->requires->js_call_amd('format_ludilearn/questionnaire', 'init',
            ['courseid' => $courseid,
                'questionscount' => $data->questionscount,
                'urlgameprofile' => $urlgameprofile->out(false),
            ]
        );
        return $this->render_from_template(
            'format_ludilearn/questionnaire',
            $data
        );
    }

    /**
     * Render the report page.
     *
     * @param int $courseid Course ID.
     *
     * @return string HTML to output.
     */
    public function render_report(int $courseid): string {

        $this->page->requires->js_call_amd('format_ludilearn/report', 'init',
            ['courseid' => $courseid]);
        return $this->render_from_template(
            'format_ludilearn/report/report',
            ['courseid' => $courseid]
        );
    }

    /**
     * Render the game profile page.
     *
     * @param int $courseid Course ID.
     *
     * @return string HTML to output.
     * @throws \dml_exception
     */
    public function render_gameprofile(int $courseid = 0): string {
        global $DB, $USER;

        $hexadscores = hexad_scores::from_database($USER->id);

        $data = new stdClass();
        if ($courseid != 0) {
            $data->courseid = $courseid;
        }
        $data->hexadscores = new stdClass();
        $data->hexadscores->achiever = $hexadscores->get_value('achiever');
        $data->hexadscores->player = $hexadscores->get_value('player');
        $data->hexadscores->socialiser = $hexadscores->get_value('socialiser');
        $data->hexadscores->freeSpirit = $hexadscores->get_value('freeSpirit');
        $data->hexadscores->disruptor = $hexadscores->get_value('disruptor');
        $data->hexadscores->philanthropist = $hexadscores->get_value('philanthropist');

        $this->page->requires->js_call_amd('format_ludilearn/gameprofile', 'init',
            ['hexadscores' => $data->hexadscores]);
        return $this->render_from_template(
            'format_ludilearn/gameprofile',
            $data
        );
    }
}
