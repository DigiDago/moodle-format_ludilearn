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
        $context = context_course::instance($courseid);
        $manager = new manager();
        $format = course_get_format($courseid);

        // Get the format options.
        $options = $format->get_format_options();

        // Get the default game element.
        $gameelementtype = $options['default_game_element'];
        $this->assignment = $options['assignment'];
        // Verify if the cours is assigned automatically and if the user has answered yet to the questionnaire.
        if ($this->assignment == 'automatic') {
            $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $USER->id]);
            if ($profile) {
                $gameelementtype = $profile->type;

                // Verify if the user has attributions.
                $manager->check_attribution_course($courseid, $USER->id, $gameelementtype);
            } else {
                $this->notanswered = true;
            }
        }

        if (is_enrolled(context_course::instance($courseid), $USER->id)) {
            $this->isenrolled = true;
        }

        // If the user has capabilities to update the course and he is not enrolled or the course is assigned automatically.
        if (has_capability('moodle/course:update', $context) && (($this->assignment == 'automatic') || !$this->isenrolled)) {
            // Verify is there is an attribution for this user.
            $attributionexist = $manager->has_attribution($courseid, $USER->id);
            // If not attribution exist, create one with nogamified type.
            $gameelementtype = 'nogamified';
            if (!$attributionexist) {
                $manager->check_attribution_course($courseid, $USER->id, $gameelementtype);
            }
            $this->notanswered = false;
        }

        // Get the course.
        $this->course = get_course($courseid);
        $this->course->formatoptions = $options;
        $this->course->sections = $DB->get_records('course_sections', ['course' => $this->course->id], 'section');

        foreach ($this->course->sections as $key => $value) {
            $this->course->sections[$key]->gameelement = $this->get_element_by_section($value->id, $gameelementtype);
        }

        // Get the section.
        if ($sectionid != -1) {
            $this->section = $DB->get_record('course_sections', ['id' => $sectionid]);
            $this->section->gameelement = $this->get_element_by_section($sectionid, $gameelementtype);

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
            // Don't show hidden section.
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
                $sectiondata->parameters = new stdClass();

                // Get the section parameters.
                foreach ($section->gameelement->get_parameters() as $key => $value) {
                    $sectiondata->parameters->$key = $value;
                }

                // Populate the section parameters.
                $sectiondata->parameters = $this->populate_section($sectiondata->parameters, $section, $type);

                $sectiondata->parameters->gamified = false;
                if ($section->gameelement->get_count_cm_gamified() > 0) {
                    $sectiondata->parameters->gamified = true;
                }

                if ($section->visible) {
                    $sectiondata->visible = true;
                }

            }
            if (has_capability('moodle/course:update', $contextcourse) || $sectioninfo->get_available()) {
                $urlsection = new moodle_url('/course/view.php?id=' . $this->course->id . '&section=' . $section->section);
                $sectiondata->url = $urlsection->out(false);
            }
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
            $data->section->parameters = new stdClass();

            // Get the section parameters.
            foreach ($this->section->gameelement->get_parameters() as $key => $value) {
                $data->section->parameters->$key = $value;
            }

            // Populate the section parameters.
            $data->section->parameters = $this->populate_section($data->section->parameters, $this->section, $type);

            $data->section->parameters->gamified = false;
            if ($this->section->gameelement->get_count_cm_gamified() > 0) {
                $data->section->parameters->gamified = true;
            }

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
                $cmdata->parameters = $this->populate_cm($cmdata->parameters, $cm, $this->section, $type);

                $contextactivity = context_module::instance($cminfo->id);
                if (has_capability('moodle/course:viewhiddenactivities', $contextactivity)
                    || has_capability('moodle/course:update', $contextcourse) || $cminfo->available) {
                    if ($cminfo->get_url()) {
                        $cmdata->url = $cminfo->get_url()->out(false);
                    }

                }
                $data->section->cms[] = $cmdata;
            }

            // Course module view.
            if ($this->cm != null) {
                $cminfo = get_fast_modinfo($this->course->id)->get_cm($this->cm->id);
                $data->cm = new stdClass();
                $data->cm->id = $cminfo->id;
                $data->cm->name = format_string($cminfo->name);
                $type = $this->section->gameelement->get_type();
                $data->cm->$type = true;
                if (isset($this->section->gameelement)) {
                    // Get the course module parameters.
                    $cmparameters = $this->section->gameelement->get_cm_parameters();
                    $data->cm->parameters = new stdClass();
                    foreach ($cmparameters[$cminfo->id] as $key => $value) {
                        $data->cm->parameters->$key = $value;
                    }

                    // Populate the course module parameters.
                    $data->cm->parameters = $this->populate_cm($data->cm->parameters, $this->cm, $this->section, $type);
                }
                // Check if the course module is restricted.
                $data->cm->parameters->restricted = false;
                if ($cminfo->available) {
                    if ($cminfo->get_url()) {
                        $data->cm->url = $cminfo->get_url()->out(false);
                    }
                }
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
     * Populate the course module parameters.
     *
     * @param stdClass $parameters Parameters to populate.
     * @param stdClass $section    Section of the course module.
     * @param string $type         Type of the section.
     *
     * @return stdClass Populated parameters.
     * @throws \moodle_exception
     */
    protected function populate_section(stdClass $parameters, stdClass $section, string $type): stdClass {
        global $USER;

        $sectioninfo = get_fast_modinfo($this->course)->get_section_info($section->section);

        $manager = new manager();
        // Populate the section parameters in function of type.
        switch ($type) {
            case 'score':
                if ($parameters->maxscore == 0) {
                    $parameters->noscore = true;
                }
                break;
            case 'badge':
                $parameters->badge = $section->gameelement->get_current_badge();

                // Count of badges for each type.
                $parameters->bronzecount = $section->gameelement->get_bronze_count() == 0
                    ? false : $section->gameelement->get_bronze_count();
                $parameters->silvercount = $section->gameelement->get_silver_count() == 0
                    ? false : $section->gameelement->get_silver_count();
                $parameters->goldcount = $section->gameelement->get_gold_count() == 0
                    ? false : $section->gameelement->get_gold_count();
                $parameters->completioncount = $section->gameelement->get_completion_count() == 0
                    ? false : $section->gameelement->get_completion_count();

                break;
            case 'progress':
                // Different display if the section is completed.
                if ($section->gameelement->get_progression() == 100) {
                    $parameters->completed = true;
                }

                // Step of the progress bar.
                $parameters->step = 0;
                if ($section->gameelement->get_progression() != 0) {
                    $parameters->step = intval($section->gameelement->get_progression() / 10);
                }

                // Attribution of planete number.
                // Modulo 9 because there are 9 diffenrent planets.
                $parameters->planetenumber = ($section->section) % 8 + 1;
                break;
            case 'avatar':
                $ownedstatus = avatar::get_items_owned_status_by_section($this->course->id, $USER->id, $section->id);
                $parameters->itemsownedcount = $ownedstatus->owned;
                $parameters->itemsownablecount = $ownedstatus->ownable;
                $ownedstatus = avatar::get_items_owned_status($this->course->id, $USER->id);

                // Get total of items ownable in the section and total of items owned in the section.
                $parameters->totalitems = $ownedstatus->ownable;
                $parameters->totalitemsowned = $ownedstatus->owned;
                if ($parameters->totalitemsowned >= 10) {
                    $parameters->itemsownedcounttwodigits = true;
                }
                break;
            case 'ranking':
                $parameters->rank = $parameters->ranking->user_rank;
                if ($parameters->rank != null) {
                    $parameters->postfix = $manager->get_postfix($parameters->rank);
                }
                if ($parameters->maxscore == 0) {
                    $parameters->noscore = true;
                }

                $parameters->ranks = [];
                if ($parameters->rank == 1) {
                    // Case when user is first.
                    $parameters->crowned = true;
                    $parameters->ranked = false;

                    // First user.
                    $rank = new stdClass();
                    $rank->rank = $manager->stringify_rank(1);
                    $rank->score = intval($parameters->score);
                    $rank->me = true;
                    $parameters->ranks[] = $rank;

                    // User.
                    if ($parameters->ranking->succeeding_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $manager->stringify_rank($parameters->ranking->succeeding_user_rank);
                        if ($parameters->ranking->succeeding_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->succeeding_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }

                    // After user.
                    if ($parameters->ranking->succeeding2_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $manager->stringify_rank(3);
                        if ($parameters->ranking->succeeding2_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->succeeding2_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }
                } else if ($parameters->rank == 2) {
                    // Case when user is not first.
                    $parameters->ranked = true;

                    // First user.
                    $rank = new stdClass();
                    $rank->rank = $manager->stringify_rank(1);
                    if ($parameters->ranking->first_user_total_score != null) {
                        $rank->score = intval($parameters->ranking->first_user_total_score);
                    } else {
                        $rank->score = 0;
                    }
                    $parameters->ranks[] = $rank;

                    // User.
                    $rank = new stdClass();
                    $rank->rank = $manager->stringify_rank($parameters->rank);
                    $rank->score = intval($parameters->score);
                    $rank->me = true;
                    $parameters->ranks[] = $rank;

                    // After user.
                    if ($parameters->ranking->succeeding_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $manager->stringify_rank($parameters->ranking->succeeding_user_rank);
                        if ($parameters->ranking->succeeding_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->succeeding_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }
                } else {
                    // Case when user is last.
                    $parameters->ranked = true;

                    // First user.
                    $rank = new stdClass();
                    $rank->rank = $manager->stringify_rank(1);
                    if ($parameters->ranking->first_user_total_score != null) {
                        $rank->score = intval($parameters->ranking->first_user_total_score);
                    } else {
                        $rank->score = 0;
                    }
                    $parameters->ranks[] = $rank;

                    // Before user.
                    if ($parameters->ranking->preceding_user_rank != null) {
                        $rank = new stdClass();
                        $rank->rank = $manager->stringify_rank($parameters->ranking->preceding_user_rank);
                        if ($parameters->ranking->preceding_user_total_score != null) {
                            $rank->score = intval($parameters->ranking->preceding_user_total_score);
                        } else {
                            $rank->score = 0;
                        }
                        $parameters->ranks[] = $rank;
                    }

                    // User.
                    $rank = new stdClass();
                    if ($parameters->rank != null) {
                        $rank->rank = $manager->stringify_rank($parameters->rank);
                    }
                    $rank->score = intval($parameters->score);
                    $rank->me = true;
                    $parameters->ranks[] = $rank;
                }
                $parameters->uniqueid = uniqid('section-summary-');
                $parameters->uniqueid2 = uniqid('section-groupview-');
                break;
            case 'timer':
                $parameters->averagetime = $section->gameelement->get_averagetime();
                if ($parameters->averagetime > 0) {
                    $minutes = str_pad(intval($parameters->averagetime / 60), 2, "0", STR_PAD_LEFT);
                    $secondes = str_pad(intval($parameters->averagetime % 60), 2, "0", STR_PAD_LEFT);
                    $parameters->averagetime = $minutes . ":" . $secondes;
                } else {
                    $parameters->averagetime = "--:--";
                }
                break;
            default:
                break;
        }

        if (!$sectioninfo->get_available()) {
            $parameters->restricted = true;
            $parameters->restrictedinfo =
                \core_availability\info::format_info($sectioninfo->availableinfo, $this->course->id);
        }
        return $parameters;
    }

    /**
     * Populate the course module parameters.
     *
     * @param stdClass $parameters    Parameters to populate.
     * @param stdClass $cm            Course module.
     * @param stdClass $parentsection Section of the course module.
     * @param string $type            Type of the section.
     *
     * @return stdClass Populated parameters.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function populate_cm(stdClass $parameters, stdClass $cm, stdClass $parentsection, string $type): stdClass {
        global $PAGE, $DB;
        $cminfo = get_fast_modinfo($this->course->id)->get_cm($cm->id);
        $manager = new manager();

        // Populate the section parameters in function of type.
        switch ($type) {
            case 'score':
                if ($parameters->gamified) {
                    if ($parameters->maxscore == 0) {
                        $parameters->noscore = true;
                    }
                }
                break;
            case 'badge':
                if ($parameters->gamified) {
                    if (isset($parameters->progression)) {
                        $parameters->badge = $parentsection->gameelement->get_cm_badge($parameters->progression);
                    } else {
                        $parameters->badge = $parentsection->gameelement->get_cm_badge(0);
                    }
                }
                break;
            case 'progress':
                if ($parameters->gamified) {
                    $parameters->step = 0;
                    if ($parameters->progression > 0) {
                        $parameters->step = intval($parameters->progression / 10);
                    }

                    $parameters->sectionprogression = $parentsection->gameelement->get_progression();
                    $parameters->sectionstep = 0;
                    if ($parameters->sectionprogression > 0) {
                        $parameters->sectionstep = intval($parameters->sectionprogression / 10);
                    }
                    // Attribution of planete number.
                    // Modulo 9 because there are 9 diffenrent planets.
                    $parameters->planetenumber = ($parentsection->section) % 8 + 1;
                }
                break;
            case 'avatar':
                if ($parameters->gamified) {
                    if ($parameters->progression > 0) {
                        $parameters->completed = true;
                    }
                }
                $parameters->sectionparameters = $parentsection->parameters;
                break;
            case 'timer';
                if ($parameters->gamified) {
                    // Format best time.
                    if ($parameters->besttime > 0) {
                        $besttime = intval($parameters->besttime);
                        $minutes = str_pad(intval($besttime / 60), 2, "0", STR_PAD_LEFT);
                        $secondes = str_pad(intval($besttime % 60), 2, "0", STR_PAD_LEFT);
                        $parameters->besttime = $minutes . ":" . $secondes;

                        if ($parameters->bestpenalties > 0) {
                            $parameters->bestpenaltiescalc = $parameters->bestpenalties *
                                $parentsection->gameelement->get_penalties();
                        }
                    } else {
                        $parameters->besttime = "--:--";
                    }
                    // When the page is an attempt quiz page.
                    if ($PAGE->bodyid == 'page-mod-quiz-attempt' && $cm->id == $this->cm->id) {
                        $attemptid = optional_param('attempt', 0, PARAM_INT);
                        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);
                        if ($attempt) {
                            $currenttime = time() - $attempt->timestart;
                            $minutes = str_pad(intval($currenttime / 60), 2, "0", STR_PAD_LEFT);
                            $secondes = str_pad(intval($currenttime % 60), 2, "0", STR_PAD_LEFT);
                            $parameters->currenttime = $minutes . ":" . $secondes;

                            if ($parameters->currentpenalties > 0) {
                                $parameters->currentpenaltiescalc = $parameters->currentpenalties *
                                    $parentsection->gameelement->get_penalties();
                            }
                            $PAGE->requires->js_call_amd('format_ludilearn/chrono', 'init',
                                ['timestart' => $attempt->timestart]);

                        }
                    }
                }
                break;
            case 'ranking':
                if ($parameters->gamified) {
                    if ($parameters->maxscore == 0) {
                        $parameters->noscore = true;
                    }
                    if ($parameters->maxscore == 0) {
                        $parameters->noscore = true;
                    }
                    if ($parameters->ranking->user_rank == 1) {
                        $parameters->crowned = true;
                        $parameters->ranked = false;
                    } else {
                        $parameters->ranked = true;
                    }

                    // Only if gradable.
                    $gradable = $parentsection->gameelement->is_gradable($cminfo->id);
                    if ($gradable && $parameters->gamified) {
                        $parameters->score = intval($parameters->score);
                        if (isset($parameters->ranking->preceding2_user_rank)) {
                            $before2 = $parameters->ranking->preceding2_user_rank;
                            if ($before2 != 0 && $before2 != null) {
                                $parameters->before_2 = $before2;
                                $parameters->before_2_th = $manager->get_postfix($before2);
                            }
                        }
                        if (isset($parameters->ranking->preceding_user_rank)) {
                            $before1 = $parameters->ranking->preceding_user_rank;
                            if ($before1 != 0 && $before1 != null) {
                                $parameters->before_1 = $before1;
                                $parameters->before_1_th = $manager->get_postfix($before1);
                            }
                        }
                        if (isset($parameters->ranking->user_rank)) {
                            $parameters->rank = $parameters->ranking->user_rank;
                            if ($parameters->ranking->user_rank != 0 && $parameters->ranking->user_rank != null) {
                                $parameters->postfix = $manager->get_postfix($parameters->ranking->user_rank);
                            }
                        }
                        if (isset($parameters->ranking->succeeding_user_rank)) {
                            $after1 = $parameters->ranking->succeeding_user_rank;
                            if ($after1 != 0 && $after1 != null) {
                                $parameters->after_1 = $after1;
                                $parameters->after_1_th = $manager->get_postfix($after1);
                            }
                        }
                        if (isset($parameters->ranking->succeeding2_user_rank)) {
                            $after2 = $parameters->ranking->succeeding2_user_rank;
                            if ($after2 != 0 && $after2 != null) {
                                $parameters->after_2 = $after2;
                                $parameters->after_2_th = $manager->get_postfix($after2);
                            }
                        }
                    }
                    $parameters->uniqueid = uniqid('cm-summary-');
                    $parameters->uniqueid2 = uniqid('cm-groupview-');
                }
                break;
            default:
                break;
        }

        // Check if the course module is restricted.
        $parameters->restricted = false;
        if (!$cminfo->available) {
            $parameters->restricted = true;
            $parameters->restrictedinfo =
                \core_availability\info::format_info($cminfo->availableinfo, $this->course->id);
        }
        return $parameters;
    }

    /**
     * Get the element by section.
     *
     * @param int $sectionid The section id.
     * @param string $type The type of the game element.
     *
     * @return game_element The game element.
     * @throws \coding_exception
     */
    protected function get_element_by_section(int $sectionid, string $type): game_element {
        global $DB, $USER;
        $gameelement = null;
        $manager = new manager();
        if ($this->assignment != 'bysection' || !$this->isenrolled) {
            $gameelement = game_element::get_element($this->course->id, $sectionid, $USER->id,
                $type);
            // If attribution is missing, create one.
            if ($gameelement == null && $this->isenrolled) {
                $gameelement = $DB->get_record('format_ludilearn_elements',
                    ['courseid' => $this->course->id, 'sectionid' => $sectionid, 'type' => $type]);
                $manager->attribution_game_element($gameelement->id, $USER->id);
                $gameelement = game_element::get_element($this->course->id, $sectionid, $USER->id,
                    $type);
            }
        } else {
            // Get the attributions by section.
            $sql = "SELECT ge.id, ge.type FROM {format_ludilearn_bysection} bs
                        INNER JOIN {format_ludilearn_elements} ge ON bs.gameelementid = ge.id
                        WHERE bs.courseid = :courseid AND bs.sectionid = :sectionid";
            $bysection = $DB->get_record_sql($sql,
                ['courseid' => $this->course->id, 'sectionid' => $sectionid]);
            if ($bysection) {
                $gameelement = game_element::get_element($this->course->id,
                    $sectionid,
                    $USER->id,
                    $bysection->type);
                // If attribution is missing, create one.
                if ($gameelement == null) {
                    $manager->attribution_game_element($bysection->id, $USER->id);
                    $gameelement = game_element::get_element($this->course->id, $sectionid, $USER->id,
                        $bysection->type);
                }
            } else {
                $gameelement = game_element::get_element($this->course->id,
                    $sectionid,
                    $USER->id,
                    $type);
                // If attribution is missing, create one.
                if ($gameelement == null) {
                    $gameelement = $DB->get_record('format_ludilearn_elements',
                        ['courseid' => $this->course->id, 'sectionid' => $sectionid, 'type' => $type]);
                    $manager->attribution_game_element($gameelement->id, $USER->id);
                    $gameelement = game_element::get_element($this->course->id, $sectionid, $USER->id,
                        $type);
                }
            }
        }
        return $gameelement;
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
