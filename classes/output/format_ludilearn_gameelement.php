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

use cm_info;
use context_course;
use context_module;
use format_ludilearn\local\gameelements\avatar;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\manager;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Ludilearn Plus game element renderer.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludilearn_gameelement implements renderable, templatable {

    /**
     * @var stdClass $course The course.
     */
    protected $course = null;

    /**
     * @var stdClass $section The section.
     */
    protected $section = null;

    /**
     * @var cm_info $cminfo
     */
    protected $cm = null;

    /**
     * @var bool $isenrolled Is the user enrolled in the course.
     */
    protected $isenrolled = false;

    /**
     * @var bool $notanswered Has the user answered yet to the questionnaire.
     */
    protected $notanswered = false;

    /**
     * @var string $assignment The assignment.
     */
    protected $assignment = null;

    /**
     * Constructor.
     *
     * @param int $courseid  The course id.
     * @param int $sectionid |null The section id.
     * @param int $cmid      |null The course module id.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(int $courseid, int $sectionid = -1, int $cmid = -1) {
        global $DB, $USER;
        $manager = new manager();
        $format = course_get_format($courseid);

        // Get the format options.
        $options = $format->get_format_options();

        // Get the assignment.
        $this->assignment = $options['assignment'];

        // Get the global game element type for the course.
        $gameelementtype = $manager->get_element_type($courseid, $USER->id);
        if (!$gameelementtype) {
            $gameelementtype = $options['default_game_element'];
            $this->notanswered = true;
        }

        // Get the course.
        $this->course = get_course($courseid);
        $this->course->formatoptions = $options;
        $this->course->sections = $DB->get_records('course_sections', ['course' => $this->course->id], 'section');

        foreach ($this->course->sections as $key => $value) {
            $this->course->sections[$key]->gameelement = $manager->get_element_by_section($this->course->id,
                $value->id,
                $gameelementtype);
        }

        // Get the section.
        if ($sectionid != -1) {
            $this->section = $DB->get_record('course_sections', ['id' => $sectionid]);
            $this->section->gameelement = $manager->get_element_by_section($this->course->id,
                    $sectionid,
                    $gameelementtype
            );

            // Get the cmid sorted.
            $sequence = explode(",", $this->section->sequence);
            $this->section->cms = [];
            foreach ($sequence as $cmidsequence) {
                if (!empty($cmidsequence)) {
                    $cm = $DB->get_record('course_modules', ['id' => $cmidsequence]);
                    if ($cm) {
                        $this->section->cms[] = $cm;
                    }
                }
            }

            // Get the course module.
            if ($cmid != -1) {
                $this->cm = $DB->get_record('course_modules', ['id' => $cmid]);
                if ($this->section->gameelement) {
                    $this->cm->gameelementparameters = $this->section->gameelement->get_cm_parameters($cmid);
                }
            }
        }

    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Renderer base.
     *
     * @return stdClass
     * @throws \coding_exception
     * @throws \core\exception\moodle_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $DB, $USER, $PAGE, $CFG;

        // Verify if the cours is assigned automatically and if the user has answered yet to the questionnaire.
        if ($this->notanswered) {
            return new stdClass();
        }

        // Export the data.
        $format = course_get_format($this->course->id);
        $contextcourse = context_course::instance($this->course->id, MUST_EXIST);
        $manager = new manager();
        $data = new stdClass();
        $data->isenrolled = $this->isenrolled;
        $data->course = new stdClass();
        $data->course->id = $this->course->id;
        $world = $this->course->formatoptions['world'];
        $data->world = $world;
        $data->$world = true;
        $data->sections = [];
        // For each section.
        foreach ($this->course->sections as $section) {
            // Don't show hidden sections.
            $sectioninfo = get_fast_modinfo($this->course)->get_section_info($section->section);
            $uservisible = $format->is_section_visible($sectioninfo);
            if (!$uservisible) {
                continue;
            }

            $sectiondata = new stdClass();
            $sectiondata->id = $section->id;
            $sectiondata->courseid = $this->course->id;
            $sectiondata->section = $section->section;
            $sectiondata->name = format_string(get_section_name($this->course, $section));
            if (isset($section->gameelement)) {
                $type = $section->gameelement->get_type();
                $sectiondata->$type = true;
                $sectiondata->type = $type;
                $sectiondata->gameelementid = $section->gameelement->get_id();
                $sectiondata->parameters = new stdClass();

                // Get the section parameters.
                foreach ($section->gameelement->get_parameters() as $key => $value) {
                    $sectiondata->parameters->$key = $value;
                }

                $sectiondata->parameters->gamified = false;
                if ($section->gameelement->get_count_cm_gamified() > 0) {
                    $sectiondata->parameters->gamified = true;
                }

                if ($section->visible) {
                    $sectiondata->visible = true;
                }

                // Populate the section parameters.
                $sectiondata->parameters = $manager->populate_section($this->course, $sectiondata->parameters, $section, $type);

            }
            if (has_capability('moodle/course:update', $contextcourse) || $sectioninfo->get_available()) {
                $urlsection = new moodle_url('/course/view.php?id=' . $this->course->id . '&section=' . $section->section);
                $sectiondata->url = $urlsection->out(false);
            }

            // Add json state for initialyze the section state in js.
            $sectiondata->jsonstate = $manager->get_course_section_state($this->course, $section, $section->gameelement);
            $data->sections[] = $sectiondata;
        }
        // Section view.
        if ($this->section != null && $this->section->gameelement) {
            $data->section = new stdClass();
            $data->section->id = $this->section->id;
            $data->section->name = format_string(get_section_name($this->course, $this->section->section));
            // Section summary.
            $data->section->summary = $this->format_summary_text($this->section);
            $type = $this->section->gameelement->get_type();
            $data->section->$type = true;
            $data->section->type = $type;
            $data->section->parameters = new stdClass();

            // Get the section parameters.
            foreach ($this->section->gameelement->get_parameters() as $key => $value) {
                $data->section->parameters->$key = $value;
            }

            $data->section->parameters->gamified = false;
            if ($this->section->gameelement->get_count_cm_gamified() > 0) {
                $data->section->parameters->gamified = true;
            }

            // Populate the section parameters.
            $data->section->parameters = $manager->populate_section($this->course, $data->section->parameters,
                    $this->section, $type);

            // Update last access.
            $manager->update_gameelement_user($this->section->gameelement->get_id(), $USER->id, 'lastaccess', time());
            $this->section->parameters = $data->section->parameters;
            $data->section->cms = [];
            // For each course module of the section.
            foreach ($this->section->cms as $cm) {
                $cminfo = get_fast_modinfo($this->course->id)->get_cm($cm->id);
                // Don't show hidden course module.
                if (!$cminfo->visible || !$cminfo->is_visible_on_course_page()) {
                    continue;
                }
                $cmdata = new stdClass();
                $cmdata->id = $cminfo->id;
                $cmdata->name = format_string($cminfo->name);
                $cmdata->parameters = new stdClass();
                $cmdata->parameters->viewed = $manager->cm_viewed_by_user($cminfo->id, $USER->id);

                // Verify if the cm is a label.
                if ($cminfo->modname == 'label') {
                    $cmdata->label = true;
                    $label = $DB->get_record('label', ['id' => $cminfo->instance]);
                    if ($label) {
                        $cmdata->labeltext = $cminfo->get_formatted_content();
                    }

                    // Not display if the label is restricted.
                    $contextactivity = context_module::instance($cminfo->id);
                    if (!$cminfo->available && !has_capability('moodle/course:viewhiddenactivities', $contextactivity)) {
                        // Le label est restreint pour l'utilisateur.
                        continue;
                    }
                    // Add the label to the section.
                    // And no need to continue because label is not gamified.
                    $data->section->cms[] = $cmdata;
                    continue;
                }

                if (isset($this->section->gameelement)) {
                    // Get the course module parameters.
                    $cmparameters = $this->section->gameelement->get_cm_parameters();
                    foreach ($cmparameters[$cminfo->id] as $key => $value) {
                        $cmdata->parameters->$key = $value;
                    }
                }

                // Populate the course module parameters.
                $cmdata->parameters = $manager->populate_cm($this->course, $cmdata->parameters, $cm, $this->section, $type);

                $contextactivity = context_module::instance($cminfo->id);
                if (has_capability('moodle/course:viewhiddenactivities', $contextactivity)
                    || has_capability('moodle/course:update', $contextcourse) || $cminfo->available) {
                    if ($cminfo->get_url()) {
                        $cmdata->url = $cminfo->get_url()->out(false);
                    }

                }
                $data->section->cms[] = $cmdata;
            }

            // Add json state for initialyze the section state in js.
            $data->section->jsonstate = $manager->get_section_state($this->section, $this->section->gameelement);

            // Course module view.
            if ($this->cm != null) {
                $cminfo = get_fast_modinfo($this->course->id)->get_cm($this->cm->id);
                $data->cm = new stdClass();
                $data->cm->id = $cminfo->id;
                $data->cm->name = format_string($cminfo->name);
                $type = $this->section->gameelement->get_type();
                $data->cm->$type = true;
                $data->cm->type = $type;
                if (isset($this->section->gameelement)) {
                    // Get the course module parameters.
                    $cmparameters = $this->section->gameelement->get_cm_parameters();
                    $data->cm->parameters = new stdClass();
                    foreach ($cmparameters[$cminfo->id] as $key => $value) {
                        $data->cm->parameters->$key = $value;
                    }

                    // Populate the course module parameters.
                    $data->cm->parameters = $manager->populate_cm($this->course, $data->cm->parameters, $this->cm,
                            $this->section, $type);

                    // Case it's timer game element on quiz attempt page.
                    if ($type == 'timer' && $PAGE->bodyid == 'page-mod-quiz-attempt') {
                        $attemptid = optional_param('attempt', 0, PARAM_INT);
                        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);
                        if ($attempt) {
                            $currenttime = time() - $attempt->timestart;
                            $minutes = str_pad(intval($currenttime / 60), 2, "0", STR_PAD_LEFT);
                            $secondes = str_pad(intval($currenttime % 60), 2, "0", STR_PAD_LEFT);
                            $data->cm->parameters->currenttime = $minutes . ":" . $secondes;

                            if ($data->cm->parameters->currentpenalties > 0) {
                                $data->cm->parameters->currentpenaltiescalc = $data->cm->parameters->currentpenalties *
                                        $data->cm->parameters->gameelement->get_penalties();
                            }
                            $PAGE->requires->js_call_amd('format_ludilearn/chrono', 'init',
                                    ['timestart' => $attempt->timestart]);

                        }
                    }
                }
                // Check if the course module is restricted.
                $data->cm->parameters->restricted = false;
                if ($cminfo->available) {
                    if ($cminfo->get_url()) {
                        $data->cm->url = $cminfo->get_url()->out(false);
                    }
                }

                // Add json state for initialyze the section state in js.
                $data->cm->jsonstate = $manager->get_cm_state($this->cm, $this->section, $this->section->gameelement);
            }

            $urlimages = $CFG->wwwroot .
                '/course/format/ludilearn/pix/' .
                $this->course->formatoptions['world'] .
                '/avatar/items/images/';

            // Call Js only if the section is an avatar game element.
            if ($this->section->gameelement instanceof avatar) {
                $PAGE->requires->js_call_amd('format_ludilearn/items', 'init',
                    ['courseid' => $this->course->id,
                        'sectionid' => $this->section->id,
                        'urlimages' => $urlimages]);
            }
        }

        // Load js trace.
        $params = ['courseid' => $this->course->id];
        if ($this->section != null) {
            $params['sectionid'] = $this->section->id;
        } else {
            $params['sectionid'] = 0;
        }
        if ($this->cm != null) {
            $params['cmid'] = $this->cm->id;
        } else {
            $params['cmid'] = 0;
        }
        // Check if the user can edit the course.
        $context = context_course::instance($this->course->id);
        if (has_capability('moodle/course:update', $context)) {
            $data->settings = true;
            $data->report = true;
        }

        return $data;
    }

    /**
     * Generate html for a section summary text
     *
     * @param stdClass $section The section.
     *
     * @return string HTML to output.
     * @throws \coding_exception
     */
    public function format_summary_text(stdClass $section): string {
        $context = context_course::instance($section->course);
        $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php',
            $context->id, 'course', 'section', $section->id);

        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        return format_text($summarytext, $section->summaryformat, $options);
    }
}
