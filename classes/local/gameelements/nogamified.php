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

namespace format_ludilearn\local\gameelements;

use format_ludilearn\manager;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Nogamified game element class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nogamified extends game_element {

    /**
     * Constructor.
     *
     * @param int $id Id of the game element.
     * @param int $courseid Id of the course.
     * @param int $sectionid Id of the section.
     * @param int $userid Id of the user.
     * @param array $paramaters Array of parameters.
     * @param array $cmparameters Array of cm parameters.
     */
    public function __construct(int $id, int $courseid, int $sectionid, int $userid, array $paramaters, array $cmparameters) {
        parent::__construct($id, $courseid, $sectionid, $userid, $paramaters, $cmparameters);
        $this->type = 'nogamified';
        $this->cmparameters = $cmparameters;
    }

    /**
     * Get a game element.
     *
     * @param int $courseid  The course ID.
     * @param int $sectionid The section ID.
     * @param int $userid    The user ID.
     *
     * @return nogamified|null
     * @throws \dml_exception
     */
    public static function get(int $courseid, int $sectionid, int $userid): ?nogamified {
        global $DB;

        $gameelementsql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND g.sectionid = :sectionid
                            AND a.userid = :userid AND g.type = :type';

        $gameelementreq = $DB->get_record_sql($gameelementsql,
            ['courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'type' => 'nogamified']);

        if (!$gameelementreq) {
            $nogamifiedelement = $DB->get_record('format_ludilearn_elements',
                ['courseid' => $courseid,
                    'sectionid' => $sectionid,
                    'type' => 'nogamified']);
            $coursemodules = $DB->get_records('course_modules', ['course' => $courseid]);
            $cmsparams = [];
            foreach ($coursemodules as $coursemodule) {
                $cmsparams[$coursemodule->id]['id'] = $coursemodule->id;
                $cmsparams[$coursemodule->id]['condition'] = 'nogamification';

            }
            return new nogamified($nogamifiedelement->id,
                $courseid,
                $sectionid,
                $userid,
                ['condition' => 'nogamification'],
                $cmsparams);
        }
        // Get all cm of the section.
        $cms = $DB->get_records('course_modules', ['section' => $sectionid]);

        $params = ['gameelementid' => $gameelementreq->gameelementid, 'userid' => $userid];

        // Get game element parameters.
        $parameters = [];
        $sqlparameters = 'SELECT * FROM {format_ludilearn_params} section_params WHERE gameelementid = :gameelementid';
        $parametersreq = $DB->get_records_sql($sqlparameters, $params);
        foreach ($parametersreq as $parameterreq) {
            $parameters[$parameterreq->name] = $parameterreq->value;
        }

        $sqlgameeleuser = 'SELECT s.id, s.name, s.value
                    FROM {format_ludilearn_ele_user} s
                    INNER JOIN {format_ludilearn_attributio} a ON s.attributionid = a.id
                    WHERE a.gameelementid = :gameelementid
                    AND a.userid = :userid';
        $gameleuserreq = $DB->get_records_sql($sqlgameeleuser, $params);
        foreach ($gameleuserreq as $gameleuser) {
            $parameters[$gameleuser->name] = $gameleuser->value;
        }

        // Get cm parameters.
        $cmparameters = [];
        foreach ($cms as $cm) {
            $cmparameters[$cm->id] = [];
            $cmparameters[$cm->id]['id'] = $cm->id;
        }
        $sqlcmparameters = 'SELECT * FROM {format_ludilearn_cm_params} cm_params WHERE gameelementid = :gameelementid';
        $cmparametersreq = $DB->get_records_sql($sqlcmparameters, $params);
        foreach ($cmparametersreq as $cmparameterreq) {
            if (key_exists($cmparameterreq->cmid, $cmparameters)) {
                $cmparameters[$cmparameterreq->cmid][$cmparameterreq->name] = $cmparameterreq->value;
            }
        }

        $sqlcms = 'SELECT cm.id, cm.cmid, cm.name, cm.value
                    FROM {format_ludilearn_cm_user} cm
                    INNER JOIN {format_ludilearn_attributio} a ON cm.attributionid = a.id
                    WHERE a.gameelementid = :gameelementid
                    AND a.userid = :userid';
        $cmsreq = $DB->get_records_sql($sqlcms, $params);
        foreach ($cmsreq as $cmreq) {
            if (key_exists($cmreq->cmid, $cmparameters)) {
                $cmparameters[$cmreq->cmid][$cmreq->name] = $cmreq->value;
            }
        }

        return new nogamified($gameelementreq->gameelementid,
            $gameelementreq->courseid,
            $gameelementreq->sectionid,
            $gameelementreq->userid,
            $parameters,
            $cmparameters);
    }
}
