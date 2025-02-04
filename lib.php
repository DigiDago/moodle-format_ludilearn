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

/**
 *  Format base class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\inplace_editable;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\output\format_ludilearn_gameelement;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Format class for the ludilearn course format.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ludilearn extends core_courseformat\base {

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool true if this course format uses sections.
     */
    public function uses_sections(): bool {
        return true;
    }

    /**
     * Returns true if this course format uses course index.
     *
     * @return bool true if this course format uses course index.
     */
    public function uses_course_index(): bool {
        return true;
    }

    /**
     * Returns true if this course format uses indentation.
     *
     * @return bool true if this course format uses indentation.
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     *
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_section_name($section): string {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the ludilearn course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of course_format::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     *
     * @return string The default value for the section name.
     * @throws coding_exception
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_ludilearn');
        } else {
            // Use course_format::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     * @throws coding_exception
     */
    public function page_title(): string {
        return get_string('sectionoutline');
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *                              if omitted the course view page is returned
     * @param array $options        options for view URL. At the moment core uses:
     *                              'navigation' (bool) if true and section has no separate page, the function returns null
     *                              'sr' (int) used by multipage formats to specify to which section to return
     *
     * @return null|moodle_url
     * @throws \core\exception\moodle_exception
     * @throws moodle_exception
     */
    public function get_view_url($section, $options = []): moodle_url {
        $course = $this->get_course();
        if (array_key_exists('sr', $options) && !is_null($options['sr'])) {
            $sectionno = $options['sr'];
        } else if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ((!empty($options['navigation']) || array_key_exists('sr', $options)) && $sectionno !== null) {
            // Display section on separate page.
            $sectioninfo = $this->get_section($sectionno);
            return new moodle_url('/course/section.php', ['id' => $sectioninfo->id]);
        }

        return new moodle_url('/course/view.php', ['id' => $course->id]);
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Returns true if this course format is compatible with content components.
     *
     * @return bool
     */
    public function supports_components(): bool {
        return true;
    }

    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     *
     * @return void
     * @throws \core\exception\moodle_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function extend_course_navigation($navigation, navigation_node $node): void {
        global $PAGE, $CFG;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     * @throws moodle_exception
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Soft Course format uses the following options:
     * - coursedisplay
     * - hideallsections
     *
     * @param bool $foreditform
     *
     * @return array of options
     * @throws coding_exception
     * @throws dml_exception
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'assignment' => [
                    'default' => 'manual',
                    'type' => PARAM_TEXT,
                ],
                'default_game_element' => [
                    'default' => 'score',
                    'type' => PARAM_TEXT,
                ],
                'world' => [
                    'default' => 'school',
                    'type' => PARAM_TEXT,
                ],
            ];
        }
        if ($foreditform) {
            $optionsedit = [
                'assignment' => [
                    'label' => get_string('assignment', "format_ludilearn"),
                    'help' => 'assignment',
                    'help_component' => 'format_ludilearn',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'manual' => get_string('manual', "format_ludilearn"),
                            'automatic' => get_string('automatic', "format_ludilearn"),
                            'bysection' => get_string('bysection', "format_ludilearn"),
                        ],
                    ],
                ],
                'default_game_element' => [
                    'label' => get_string('default_game_element', "format_ludilearn"),
                    'help' => 'default_game_element',
                    'help_component' => 'format_ludilearn',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'score' => get_string('score', "format_ludilearn"),
                            'badge' => get_string('badge', "format_ludilearn"),
                            'progress' => get_string('progress', "format_ludilearn"),
                            'avatar' => get_string('avatar', "format_ludilearn"),
                            'timer' => get_string('timer', "format_ludilearn"),
                            'ranking' => get_string('ranking', "format_ludilearn"),
                            'nogamified' => get_string('nogamified', "format_ludilearn"),
                        ],
                    ],
                ],
                'world' => [
                    'label' => get_string('world', "format_ludilearn"),
                    'help' => 'world',
                    'help_component' => 'format_ludilearn',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            'school' => get_string('school', "format_ludilearn"),
                            'professional' => get_string('professional', "format_ludilearn"),
                            'highschool' => get_string('highschool', "format_ludilearn"),
                        ],
                    ],
                ],
            ];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $optionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection       'true' if this is a section edit form, 'false' if this is course edit form.
     *
     * @return array array of references to the added form elements.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        return $elements;
    }

    /**
     * Updates format options for a course.
     *
     * In case if course format was changed to 'ludilearn', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse  if this function is called from {@link update_course()}
     *                             this object contains information about the course before update
     *
     * @return bool whether there were any changes to the options values
     * @throws coding_exception
     * @throws dml_exception
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }

        if (!isset($oldcourse['format']) || ($oldcourse['format'] != 'ludilearn')) {
            // Create game elements if not exist.
            $manager = new \format_ludilearn\manager();
            game_element::create_all_for_course($this->get_course()->id);
            if (isset($data['assignment']) && isset($data['default_game_element'])) {
                $manager->sync_user_attribution($this->get_course()->id, $data['assignment'], $data['default_game_element'], false);
            }
        }

        return $this->update_format_options($data);
    }

    /**
     * Updates format options for a section.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param int $sectionid section id.
     * @return bool whether there were any changes to the options values.
     */
    public function update_format_options($data, $sectionid = null): bool {
        $oldoptions = $this->get_format_options();
        $changed = parent::update_format_options($data, $sectionid);
        if (isset($data['assignment'])) {
            // If the assignment or default game element has changed, we need to update the database.
            if (($oldoptions['assignment'] != $data['assignment']
                    || $oldoptions['default_game_element'] != $data['default_game_element'])
                && isset($oldoptions['assignment'])
                && isset($oldoptions['default_game_element'])) {

                // Create game elements if not exist.
                $manager = new \format_ludilearn\manager();
                game_element::create_all_for_course($this->get_course()->id);
                $manager->sync_user_attribution($this->get_course()->id,
                    $data['assignment'],
                    $data['default_game_element'],
                    $oldoptions['assignment'] != $data['assignment']);
            }
        }
        return $changed;
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
        $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_ludilearn');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_ludilearn', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     *
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
     * @throws moodle_exception
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'ludilearn' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_ludilearn');

        if (!($section instanceof section_info)) {
            $modinfo = course_modinfo::instance($this->courseid);
            $section = $modinfo->get_section_info($section->section);
        }
        $elementclass = $this->get_output_classname('content\\section\\availability');
        $availability = new $elementclass($this, $section);

        $rv['section_availability'] = $renderer->render($availability);
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }

    /**
    public static function course_updated(int $courseid) {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid]);
        $format = course_get_format($course);

        // Get the format options.
        $options = $format->get_format_options();
        $assignment = $options['assignment'];

        // If the assignment is manual or default, we need to check if attributions of game elements are up-to-date.
        if (($assignment == 'default') || ($assignment == 'manual')) {
            $task = new check_format_options_changements();
            $task->set_custom_data(['courseid' => $courseid, 'assignment' => $assignment]);
            manager::queue_adhoc_task($task);
        }
    }
     */

    /**
     * Course-specific information to be output immediately above content on any course page
     *
     * See course_format::course_header() for usage
     *
     * @return null|renderable null for no output or object with data for plugin renderer
     * @throws coding_exception
     * @throws dml_exception
     */
    public function course_content_header() {
        global $PAGE, $DB;

        if (($PAGE->bodyid != 'page-course-view-ludilearn' && $PAGE->bodyid != 'page-course-view-section-ludilearn')
            && $PAGE->cm == null) {
            return null;
        }
        $section = optional_param('section', -1, PARAM_INT);
        $hideheader = optional_param('hideheader', false, PARAM_BOOL);
        if ($hideheader) {
            return null;
        }
        // If we are on section view page.
        if ($PAGE->bodyid == 'page-course-view-section-ludilearn') {
            $section = optional_param('id', -1, PARAM_INT);
            if ($section >= 0) {
                return new format_ludilearn_gameelement($PAGE->course->id, $section);
            }
        }
        if ($section >= 0) {
            $section = $DB->get_record('course_sections', ['course' => $this->courseid, 'section' => $section]);
            return new format_ludilearn_gameelement($PAGE->course->id, $section->id);
        }
        if ($PAGE->cm != null) {
            $cm = $DB->get_record('course_modules', ['id' => $PAGE->cm->id]);
            return new format_ludilearn_gameelement($PAGE->course->id, $cm->section, $cm->id);
        }

        return new format_ludilearn_gameelement($PAGE->course->id);
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 *
 * @return inplace_editable
 * @throws dml_exception
 */
function format_ludilearn_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'ludilearn'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * This function extends the navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course            The course to object for the report
 * @param stdClass $context           The context of the course
 *
 * @throws \core\exception\moodle_exception
 * @throws coding_exception
 */
function format_ludilearn_extend_navigation_course(navigation_node $navigation, stdClass $course,
    stdClass $context): void {
    global $USER, $CFG;
    $context = context_course::instance($course->id);
    if (has_capability('moodle/course:update', $context, $USER) && $course->format == 'ludilearn') {
        $url = new moodle_url("$CFG->wwwroot/course/format/ludilearn/settings_game_elements.php",
            ['id' => $course->id, "type" => "score", "hideheader" => 1]);
        $navigation->add(get_string('settingsname', 'format_ludilearn'), $url);
    }
}
