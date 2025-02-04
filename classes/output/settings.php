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

use coding_exception;
use context_course;
use format_ludilearn\local\gameelements\avatar;
use format_ludilearn\local\gameelements\badge;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\local\gameelements\score;
use format_ludilearn\local\gameelements\timer;
use mod_bigbluebuttonbn\local\helpers\reset;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Ludilearn settings renderer.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings implements renderable, templatable {

    /**
     * @var int $courseid The course id.
     */
    protected int $courseid;

    /**
     * @var string $type
     */
    protected string $type;

    /**
     * @var moodle_url $url
     */
    protected moodle_url $url;

    /**
     * @var array $parametersname Name of course parameters.
     */
    protected array $parameterslist;

    /**
     * Constructor.
     *
     * @param int $courseid The course id.
     * @param string $type  The game element type.
     *
     * @throws \core\exception\moodle_exception
     * @throws \dml_exception
     */
    public function __construct(int $courseid, string $type) {
        global $CFG, $DB;
        $this->courseid = $courseid;
        $this->type = $type;
        $params = ["id" => $this->courseid, "type" => $type, "hideheader" => 1];
        $this->url = new moodle_url("$CFG->wwwroot/course/format/ludilearn/settings_game_elements.php", $params);

        // If the type is not a game element but the assignment by section setting page.
        if ($this->type == 'assignmentbysection') {
            $this->parameterslist = [];
            // Get section of the course.
            $sections = $DB->get_records('course_sections', ['course' => $this->courseid], 'section', 'id');
            foreach ($sections as $section) {
                $this->parameterslist[] = 'section_' . $section->id;
            }
            return;
        } else if ($this->type == 'updateprogression') {
            $this->parameterslist = [];
            return;
        }
        $classname = "\\format_ludilearn\\local\\gameelements\\$type";
        $this->parameterslist = $classname::get_parameters_list();
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output Output renderer
     *
     * @return stdClass The data.
     * @throws coding_exception
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->courseid = $this->courseid;

        // Get the visual world.
        $format = course_get_format($this->courseid);
        $options = $format->get_format_options();
        $world = $options['world'];
        $data->world = $world;
        $data->$world = true;
        // If type is real type.
        if ($this->type != 'assignmentbysection' && $this->type != 'updateprogression') {
            if (!in_array($this->type, game_element::get_all_types())) {
                throw new coding_exception('Invalid game element type');
            }
        }

        // Return the data assiociated to the good type.
        $getdata = 'get_data_' . $this->type;
        return $this->$getdata($data);
    }

    /**
     * Return score data for template.
     *
     * @param stdClass $data The score data for template
     *
     * @return stdClass The score data for template
     * @throws \dml_exception
     */
    protected function get_data_score(stdClass $data): stdClass {
        global $DB;

        $courseparameters = game_element::get_course_parameters($this->courseid, 'score');

        if (isset($courseparameters->multiplier)) {
            $data->multiplier = $courseparameters->multiplier;
        } else {
            $data->multiplier = score::DEFAULT_MULTIPLIER;
        }

        if (isset($courseparameters->bonuscompletion)) {
            $data->bonuscompletion = $courseparameters->bonuscompletion;
        } else {
            $data->bonuscompletion = score::DEFAULT_BONUSCOMPLETION;
        }

        if (isset($courseparameters->percentagecompletion)) {
            $data->percentagecompletion = $courseparameters->percentagecompletion;
        } else {
            $data->percentagecompletion = score::DEFAULT_PERCENTAGECOMPLETION;
        }

        return $data;
    }

    /**
     * Return badge data for template.
     *
     * @param stdClass $data The badge data for template
     *
     * @return stdClass The badge data for template
     * @throws \dml_exception
     */
    protected function get_data_badge(stdClass $data): stdClass {

        $courseparameters = game_element::get_course_parameters($this->courseid, 'badge');

        if (isset($courseparameters->badgegold)) {
            $data->badgegold = $courseparameters->badgegold;
        } else {
            $data->badgegold = badge::DEFAULT_BADGE_GOLD;
        }

        if (isset($courseparameters->badgesilver)) {
            $data->badgesilver = $courseparameters->badgesilver;
        } else {
            $data->badgesilver = badge::DEFAULT_BADGE_SILVER;
        }

        if (isset($courseparameters->badgebronze)) {
            $data->badgebronze = $courseparameters->badgebronze;
        } else {
            $data->badgebronze = badge::DEFAULT_BADGE_BRONZE;
        }

        return $data;
    }

    /**
     * Return progress data for template.
     *
     * @param stdClass $data The progress data for template
     *
     * @return stdClass The progress data for template
     * @throws \dml_exception
     */
    protected function get_data_progress(stdClass $data): stdClass {

        return $data;
    }

    /**
     * Return timer data for template.
     *
     * @param stdClass $data The timer data for template
     * @return stdClass The timer data for template
     */
    protected function get_data_timer(stdClass $data): stdClass {

        $courseparameters = game_element::get_course_parameters($this->courseid, 'timer');
        if (isset($courseparameters->penalties)) {
            $data->penalties = $courseparameters->penalties;
        } else {
            $data->penalties = timer::DEFAULT_PENALTIES;
        }

        return $data;
    }

    /**
     * Return ranking data for template.
     *
     * @param stdClass $data The ranking data for template
     * @return stdClass The ranking data for template
     */
    protected function get_data_ranking(stdClass $data): stdClass {
        // Just fake data for display visual.
        $data->parameters = new stdClass();
        $data->parameters->crowned = false;
        $data->parameters->ranked = true;
        $data->parameters->me = true;

        return $data;
    }

    /**
     * Return avatar data for template.
     *
     * @param stdClass $data The avatar data for template
     *
     * @return stdClass The avatar data for template
     * @throws \dml_exception
     */
    protected function get_data_avatar(stdClass $data): stdClass {
        $courseparameters = game_element::get_course_parameters($this->courseid, 'avatar');
        if (isset($courseparameters->thresholdtoearn)) {
            $data->thresholdtoearn = $courseparameters->thresholdtoearn;
        } else {
            $data->thresholdtoearn = avatar::DEFAULT_THRESHOLDTOEARN;
        }
        return $data;
    }

    /**
     * Return data for template.
     *
     * @param stdClass $data The data for template.
     * @return stdClass The data for template.
     */
    protected function get_data_nogamified(stdClass $data): stdClass {
        return $data;
    }

    /**
     * Get data for assignment by section.
     *
     * @param stdClass $data The data for assignment by section.
     *
     * @return stdClass The data for assignment by section.
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function get_data_assignmentbysection(stdClass $data): stdClass {
        global $DB;
        $context = context_course::instance($this->courseid);
        $sections = $DB->get_records('course_sections', ['course' => $this->courseid],
            'section', 'id, name, section');
        $data->sections = [];
        $courseformat = course_get_format($this->courseid);

        foreach ($sections as $section) {
            $s = new stdClass();
            $s->id = $section->id;

            // Get the section name.
            // If it's empty, get the default section name.
            if ($section->name != '') {
                $s->name = format_string($section->name, true, ['context' => $context]);
            } else {
                $s->name = $courseformat->get_default_section_name($section);
            }

            // Get the game element of the section.
            $bysection = $DB->get_record('format_ludilearn_bysection',
                ['courseid' => $this->courseid, 'sectionid' => $section->id]);
            $gameelements = $DB->get_records('format_ludilearn_elements',
                ['courseid' => $this->courseid, 'sectionid' => $section->id]);

            if ($bysection) {
                $s->gameelementid = $bysection->gameelementid;
                $gameelement = $DB->get_record('format_ludilearn_elements',
                    ['id' => $s->gameelementid]);
                $s->type = $gameelement->type;
            } else {
                // If no game element is set, set the default game element.
                $defaultgameelement = $courseformat->get_format_options()['default_game_element'];
                $gameelement = $DB->get_record('format_ludilearn_elements',
                    ['courseid' => $this->courseid, 'sectionid' => $section->id, 'type' => $defaultgameelement]);
                $s->gameelementid = $gameelement->id;
                $s->type = $gameelement->type;
            }

            // Data for select element.
            foreach ($gameelements as $gameelement) {
                $ge = new stdClass();
                $ge->gameelementid = $gameelement->id;
                $ge->type = get_string($gameelement->type, 'format_ludilearn');
                if ($gameelement->type == $s->type) {
                    $ge->selected = true;
                } else {
                    $ge->selected = false;
                }
                $s->gameelements[] = $ge;
            }
            $data->sections[] = $s;
        }
        return $data;
    }

    /**
     * Get data for update progression.
     *
     * @param stdClass $data The data for update progression.
     * @return stdClass The data for update progression.
     */
    protected function get_data_updateprogression(stdClass $data): stdClass {
        return $data;
    }

    /**
     * Get course id.
     *
     * @return int Course id.
     */
    public function get_courseid(): int {
        return $this->courseid;
    }

    /**
     * Get game element type.
     *
     * @return string Game element type.
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Get the URL for the settings.
     *
     * @return moodle_url The URL for the settings.
     */
    public function get_url(): moodle_url {
        return $this->url;
    }

    /**
     * Get the list of parameters.
     *
     * @return array The list of parameters.
     */
    public function get_parameterslist(): array {
        return $this->parameterslist;
    }
}
