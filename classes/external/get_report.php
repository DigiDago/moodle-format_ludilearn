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

namespace format_ludilearn\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_course;
use external_multiple_structure;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_ludilearn\local\gameelements\game_element;
use format_ludilearn\manager;
use stdClass;

/**
 * Class for get report.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author      Jordan Kesraoui
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_report extends external_api {

    /**
     * Execute the webservice.
     *
     * @param int $courseid   Id of the course.
     * @param string $contain Contain string in firstname or lastname.
     * @param int $limit      Result limit.
     * @param int $offset     Resultat limit offset.
     * @param string $sort    Field sort.
     *
     * @return array The web service return.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function execute(int $courseid, string $contain, int $limit, int $offset, string $sort): array {
        global $DB;

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('format/ludilearn:manage', $context);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $users = [];
        $params = [
                'courseid' => $courseid,
                'sort' => $sort,
        ];
        $sql = "SELECT u.id, u.firstname, u.lastname, u.username
                    FROM {user} u INNER JOIN {user_enrolments} ue ON u.id = ue.userid
                    INNER JOIN {enrol} e ON ue.enrolid = e.id
                    WHERE e.courseid = :courseid";
        if (!empty($contain)) {
            $lastnamelikecontain = $DB->sql_like('lastname', ':contain');
            $firstnamelikecontain = $DB->sql_like('firstname', ':contain');
            $usernamelikecontain = $DB->sql_like('username', ':contain');
            $sql .= " AND ({${$lastnamelikecontain}} OR {${$firstnamelikecontain}} OR {${$usernamelikecontain}}')";
            $params['contain'] = '%' . $contain . '%';
        }
        $sql .= " ORDER BY :sort";
        $usersenrolled = $DB->get_records_sql($sql, $params, $offset, $limit);
        foreach ($usersenrolled as $userrenrolled) {
            $user = new stdClass();
            $user->id = $userrenrolled->id;
            $user->username = $userrenrolled->username;
            $user->firstname = $userrenrolled->firstname;
            $user->lastname = $userrenrolled->lastname;
            $gameelements = game_element::get_all($courseid, $user->id);
            $firstgameelement = reset($gameelements);
            $user->gameelement = 'N/A';
            if ($firstgameelement) {
                $user->gameelement = get_string($firstgameelement->get_type(), 'format_ludilearn');
            }
            $user->progression = \core_completion\progress::get_course_progress_percentage($course, $user->id);
            if ($user->progression) {
                $user->progression = intval($user->progression) . '%';
            } else {
                $user->progression = '0%';
            }
            $lastaccess = $DB->get_record('user_lastaccess',
                ['userid' => $user->id, 'courseid' => $courseid]
            );
            if ($lastaccess) {
                $user->lastaccess = date('d-m-Y H:i:s', $lastaccess->timeaccess);
            } else {
                $user->lastaccess = get_string('never');
            }
            $users[] = $user;
        }

        $usersenrolledwithoutlimit = $DB->get_records_sql($sql, $params);

        return [
            'users' => $users,
            'countWithoutLimit' => count($usersenrolledwithoutlimit),
        ];
    }

    /**
     * Get webservice parameters structure.
     *
     * @return external_function_parameters The webservice parameters structure.
     */
    public static function execute_parameters(): external_function_parameters {
        $parameters = [
            'courseid' => new external_value(
                PARAM_INT,
                'Course ID',
                VALUE_REQUIRED
            ),
            'contain' => new external_value(
                PARAM_TEXT,
                'Contain string in firstname or lastname',
                false
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Result limit',
                false
            ),
            'offset' => new external_value(
                PARAM_INT,
                'Resultat limit offset',
                false
            ),
            'sort' => new external_value(PARAM_TEXT,
                'Field sort',
                false
            ),
        ];
        return new external_function_parameters($parameters);
    }

    /**
     * Get webservice return structure.
     *
     * @return external_single_structure The webservice return structure.
     */
    public static function execute_returns(): external_single_structure {
        $keys = [
            'users' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'id' => new external_value(
                            PARAM_INT,
                            'User ID',
                            VALUE_REQUIRED
                        ),
                        'firstname' => new external_value(
                            PARAM_TEXT,
                            'First name',
                            VALUE_REQUIRED
                        ),
                        'lastname' => new external_value(
                            PARAM_TEXT,
                            'Last name',
                            VALUE_REQUIRED
                        ),
                        'username' => new external_value(
                            PARAM_TEXT,
                            'Username',
                            VALUE_REQUIRED
                        ),
                        'gameelement' => new external_value(
                            PARAM_TEXT,
                            'Game element',
                            VALUE_REQUIRED
                        ),
                        'progression' => new external_value(
                            PARAM_TEXT,
                            'Progression',
                            VALUE_REQUIRED
                        ),
                        'lastaccess' => new external_value(
                            PARAM_TEXT,
                            'Last access',
                            VALUE_REQUIRED
                        ),
                    ]
                ),
                'Report of the users',
                VALUE_REQUIRED
            ),
            'countWithoutLimit' => new external_value(PARAM_INT, 'Total count of users'),
        ];

        return new external_single_structure(
            $keys,
            'get_report'
        );
    }
}
