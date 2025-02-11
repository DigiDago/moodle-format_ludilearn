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
 * Ranking game element class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ranking extends game_element {

    /**
     * @var int
     */
    protected int $score;

    /**
     * @var int
     */
    protected int $maxscore;

    /**
     * @var stdClass
     */
    protected stdClass $ranking;

    /**
     * Constructor.
     *
     * @param int $id             Id of the game element.
     * @param int $courseid       Id of the course.
     * @param int $sectionid      Id of the section.
     * @param int $userid         Id of the user.
     * @param array $paramaters   Array of parameters.
     * @param array $cmparameters Array of cm parameters.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(int $id, int $courseid, int $sectionid, int $userid, array $paramaters, array $cmparameters) {
        parent::__construct($id, $courseid, $sectionid, $userid, $paramaters, $cmparameters);
        $this->type = 'ranking';

        $this->score = 0;
        $this->maxscore = 0;
        foreach ($cmparameters as $key => $value) {

            if (!$this->is_gradable($key) || !$this->is_activity_available_for_user($key, $this->userid)) {
                $cmparameters[$key]['gamified'] = false;
                continue;
            }

            if (!isset($value['gamified']) || $value['gamified']) {
                $cmparameters[$key]['gamified'] = true;

                // Search the ranking of the user.
                $ranking = $this->search_ranking($key);
                if ($ranking) {
                    $cmparameters[$key]['ranking'] = $ranking;
                }
                if (isset($value['score'])) {
                    $this->score += $value['score'];
                } else {
                    $cmparameters[$key]['score'] = 0;
                }
                if (!isset($value['maxscore'])) {
                    $cmparameters[$key]['maxscore'] = $this->get_grademax($key);
                }
                $this->maxscore += $cmparameters[$key]['maxscore'];

            } else {
                $cmparameters[$key]['gamified'] = false;
            }
        }

        // Search the global ranking of the user.
        $ranking = $this->search_global_ranking();
        $this->sectionparameters['ranking'] = $ranking;
        $this->ranking = $ranking;

        $this->cmparameters = $cmparameters;
        $this->sectionparameters['score'] = $this->score;
        $this->sectionparameters['maxscore'] = $this->maxscore;
    }

    /**
     * Get the score.
     *
     * @return int
     */
    public function get_score(): int {
        return $this->score;
    }

    /**
     * Get the max score.
     *
     * @return int
     */
    public function get_max_score(): int {
        return $this->maxscore;
    }

    /**
     * Get the general ranking.
     *
     * @return int The general ranking.
     */
    public function get_general_ranking(): int {
        return $this->ranking->user_rank;
    }

    /**
     * Check if the user is first.
     *
     * @return bool True if the user is first, false otherwise.
     */
    public function is_first(): bool {
        return $this->get_general_ranking() == 1;
    }

    /**
     * Get the score percentage.
     *
     * @return float
     */
    public function get_score_percentage(): float {
        return $this->maxscore > 0 ? $this->score / $this->maxscore : 0;
    }

    /**
     * Get the score percentage.
     *
     * @return string
     */
    public function get_score_percentage_formatted(): string {
        return number_format($this->get_score_percentage() * 100, 2) . '%';
    }

    /**
     * Get the score percentage.
     *
     * @return string
     */
    public function get_score_formatted(): string {
        return number_format($this->get_score(), 2);
    }

    /**
     * Get the score percentage.
     *
     * @return string
     */
    public function get_max_score_formatted(): string {
        return number_format($this->get_max_score(), 2);
    }

    /**
     * Get the score percentage.
     *
     * @return string
     */
    public function get_score_formatted_with_max_score(): string {
        return $this->get_score_formatted() . '/' . $this->get_max_score_formatted();
    }

    /**
     * Get the default parameters for a cm.
     *
     * @param string $moduletype The module type.
     * @param int $cmid The cm id.
     *
     * @return array The default parameters.
     */
    public static function get_cm_parameters_default(string $moduletype, int $cmid): array {
        $parameters['maxscore'] = 100;
        return array_merge($parameters, parent::get_cm_parameters_default($moduletype, $cmid));
    }

    /**
     * Get the type of a parameter.
     *
     * @param string $name Name of the parameter.
     * @return string Type of the parameter.
     */
    public static function get_cm_parameter_type(string $name): string {
        switch ($name) {
            case 'maxscore':
                // Get the default parameters of the game element.
                $result = 'number';
                break;
            default:
                $result = parent::get_cm_parameter_type($name);
        }
        return $result;
    }

    /**
     * Update score elements.
     *
     * @param int $courseid          The course id.
     * @param stdClass $coursemodule The course module.
     * @param string $modulename     The module name.
     * @param int $userid            The user id.
     * @param int $attemptid         The attempt id.
     *
     * @throws \dml_exception
     */
    public static function update_elements(int $courseid, stdClass $coursemodule, string $modulename, int $userid): void {
        global $DB;

        // Get game element.
        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'ranking']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {

            // Get grade.
            $grades = grade_get_grades($courseid, 'mod', $modulename, $coursemodule->instance, $userid);
            $score = 0;
            if (count($grades->items) > 0) {
                $score = $grades->items[0]->grades[$userid]->grade;
                if ($score == null) {
                    $score = 0;
                }
            }

            // Update the score or create it if it does not exist.
            $cmuser = $DB->get_record('format_ludilearn_cm_user',
                ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'score']);
            if ($cmuser) {
                // If the score is different from the previous one.
                if ($score != $cmuser->value) {
                    // Update the score.
                    $param = new stdClass();
                    $param->id = $cmuser->id;
                    $param->value = $score;
                    $DB->update_record('format_ludilearn_cm_user', $param);
                }
            } else {
                $DB->insert_record('format_ludilearn_cm_user', [
                    'attributionid' => $attribution->id,
                    'name' => 'score',
                    'cmid' => $coursemodule->id,
                    'value' => $score]);
            }
        }
    }

    /**
     * Update game elements when quiz has immediate feedback.
     *
     * @param int $quizid    The quiz id.
     * @param int $userid    The user id.
     * @param int $attemptid The attempt id.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function update_quiz_immediate_feedback(int $quizid, int $userid, int $attemptid = 0): void {
        global $DB;

        $manager = new manager();
        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        $module = $DB->get_record('modules', ['name' => 'quiz']);
        $coursemodule = $DB->get_record('course_modules',
            [
                'course' => $quiz->course,
                'module' => $module->id,
                'instance' => $quiz->id,
            ]
        );

        $gameelement = $DB->get_record('format_ludilearn_elements',
            ['sectionid' => $coursemodule->section, 'type' => 'ranking']);

        // Verify attribution.
        $attribution = $DB->get_record('format_ludilearn_attributio',
            ['gameelementid' => $gameelement->id, 'userid' => $userid]);
        if ($attribution) {

            // Get max score.
            $maxscore = $quiz->grade;

            // Calculate the score.
            $score = 0;
            $grademax = $quiz->grade;
            // Calculate the score.
            // Get grade.
            $grade = $manager->calculate_quiz_grade($quiz, $userid);
            if ($grade > 0) {
                $score = intval($grade * $maxscore / $grademax);
            }
            // Update the score or create it if it does not exist.
            $cmuser = $DB->get_record('format_ludilearn_cm_user',
                ['cmid' => $coursemodule->id, 'attributionid' => $attribution->id, 'name' => 'score']);
            if ($cmuser) {
                // If the score is different from the previous one.
                if ($score != $cmuser->value) {
                    // Update the score.
                    $param = new stdClass();
                    $param->id = $cmuser->id;
                    $param->value = $score;
                    $DB->update_record('format_ludilearn_cm_user', $param);
                }
            } else {
                $DB->insert_record('format_ludilearn_cm_user', [
                    'attributionid' => $attribution->id,
                    'name' => 'score',
                    'cmid' => $coursemodule->id,
                    'value' => $score]);
            }
        }
    }

    /**
     * Get a game element.
     *
     * @param int $courseid  The course ID.
     * @param int $sectionid The section ID.
     * @param int $userid    The user ID.
     *
     * @return ranking|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get(int $courseid, int $sectionid, int $userid): ?ranking {
        global $DB;

        $gameelementsql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND g.sectionid = :sectionid
                            AND a.userid = :userid AND g.type = :type';

        $gameelementreq = $DB->get_record_sql($gameelementsql,
            ['courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'type' => 'ranking']);

        if (!$gameelementreq) {
            return null;
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

        return new ranking($gameelementreq->gameelementid,
            $gameelementreq->courseid,
            $gameelementreq->sectionid,
            $gameelementreq->userid,
            $parameters,
            $cmparameters);
    }

    /**
     * Get the ranking of the user.
     *
     * @param int $cmid The cm id.
     * @return object The ranking of the user.
     */
    public function get_ranking(int $cmid): object {
        return $this->cmparameters[$cmid]['ranking'];
    }

    /**
     * Get the ranking of the user.
     *
     * @param int $cmid The cm id.
     *
     * @return mixed The ranking of the user.
     * @throws \dml_exception
     */
    protected function search_ranking(int $cmid): mixed {
        global $DB;

        // Get the ranking of all users sorted by score.
        $sql = "WITH
            UserScores AS (
                SELECT
                    a.userid,
                    cmu.value as score
                FROM {format_ludilearn_attributio} a
                INNER JOIN {user} u ON a.userid = u.id
                INNER JOIN {format_ludilearn_cm_user} cmu ON a.id = cmu.attributionid
                WHERE a.gameelementid = :gameelementid
                AND cmu.name = 'score'
                AND cmu.cmid = :cmid
            ),
            RankedScores AS (
                SELECT
                    RANK() OVER (ORDER BY score DESC) AS user_rank,
                    userid,
                    score
                FROM UserScores
            ),
            TargetUser AS (
                SELECT
                    userid,
                    score,
                    user_rank
                FROM RankedScores
                WHERE userid = :target_userid
            ),
            PrecedingUser AS (
                SELECT
                    userid,
                    score,
                    user_rank
                FROM RankedScores
                WHERE user_rank = (CASE
                        WHEN (SELECT user_rank FROM TargetUser) > 1
                        THEN (SELECT user_rank FROM TargetUser) - 1
                        ELSE NULL
                     END)
            ),
            PrecedingUser2 AS (
                SELECT
                    userid,
                    score,
                    user_rank
                FROM RankedScores
                WHERE user_rank = (CASE
                        WHEN (SELECT user_rank FROM TargetUser) > 2
                        THEN (SELECT user_rank FROM TargetUser) - 2
                        ELSE NULL
                     END)
            ),
            SucceedingUser AS (
                SELECT
                    userid,
                    score,
                    user_rank
                FROM RankedScores
                WHERE user_rank = (SELECT user_rank + 1 FROM TargetUser)
            ),
            SucceedingUser2 AS (
                SELECT
                    userid,
                    score,
                    user_rank
                FROM RankedScores
                WHERE user_rank = (SELECT user_rank + 2 FROM TargetUser)
            )
            SELECT
                t.userid AS target_userid,
                t.score AS target_user_score,
                t.user_rank AS user_rank,
                p.userid AS preceding_userid,
                p.score AS preceding_user_score,
                p.user_rank AS preceding_user_rank,
                p2.userid AS preceding2_userid,
                p2.score AS preceding2_user_score,
                p2.user_rank AS preceding2_user_rank,
                s.userid AS succeeding_userid,
                s.score AS succeeding_user_score,
                s.user_rank AS succeeding_user_rank,
                s2.userid AS succeeding2_userid,
                s2.score AS succeeding2_user_score,
                s2.user_rank AS succeeding2_user_rank
            FROM
                TargetUser t
                LEFT JOIN PrecedingUser p ON 1 = 1
                LEFT JOIN PrecedingUser2 p2 ON 1 = 1
                LEFT JOIN SucceedingUser s ON 1 = 1
                LEFT JOIN SucceedingUser2 s2 ON 1 = 1
            LIMIT 1";

        $params = [
            'gameelementid' => $this->get_id(),
            'cmid' => $cmid,
            'target_userid' => $this->userid,
        ];

        $ranking = $DB->get_record_sql($sql, $params);
        if (!$ranking) {
            // Search the ranking of 3 first users.
            $sql = "WITH
                UserScores AS (
                    SELECT
                        a.userid,
                        cmu.value as score
                    FROM {format_ludilearn_attributio} a
                    INNER JOIN {user} u ON a.userid = u.id
                    INNER JOIN {format_ludilearn_cm_user} cmu ON a.id = cmu.attributionid
                    WHERE a.gameelementid = :gameelementid
                    AND cmu.name = 'score'
                    AND cmu.cmid = :cmid
                ),
                RankedScores AS (
                    SELECT
                        RANK() OVER (ORDER BY score DESC) AS user_rank,
                        userid,
                        score
                    FROM UserScores
                ),
                FirstUser AS (
                    SELECT
                        userid,
                        score,
                        user_rank
                    FROM RankedScores
                    WHERE user_rank = 1
                ),
                SecondUser AS (
                    SELECT
                        userid,
                        score,
                        user_rank
                    FROM RankedScores
                    WHERE user_rank = 2
                ),
                ThirdUser AS (
                    SELECT
                        userid,
                        score,
                        user_rank
                    FROM RankedScores
                    WHERE user_rank = 3
                )
                SELECT
                    f.userid AS first_userid,
                    f.score AS first_user_score,
                    f.user_rank AS first_user_rank,
                    s.userid AS preceding_userid,
                    s.score AS preceding_user_score,
                    s.user_rank AS preceding_user_rank,
                    s2.userid AS preceding2_userid,
                    s2.score AS preceding2_user_score,
                    s2.user_rank AS preceding2_user_rank
                FROM
                    FirstUser f
                    LEFT JOIN SecondUser s ON 1 = 1
                    LEFT JOIN ThirdUser s2 ON 1 = 1
                LIMIT 1";
            $params = [
                'gameelementid' => $this->get_id(),
                'cmid' => $cmid,
            ];
            $ranking = $DB->get_record_sql($sql, $params);
            if (!$ranking) {
                $ranking = new stdClass();
                $ranking->preceding_userid = null;
                $ranking->preceding_user_score = null;
                $ranking->preceding_user_rank = null;
                $ranking->preceding2_userid = null;
                $ranking->preceding2_user_score = null;
                $ranking->preceding2_user_rank = null;
                $ranking->succeeding_userid = null;
                $ranking->succeeding_user_score = 0;
                $ranking->succeeding_user_rank = 2;
                $ranking->succeeding2_userid = null;
                $ranking->succeeding2_user_score = 0;
                $ranking->succeeding2_user_rank = 3;
            }

            $ranking->target_userid = $this->userid;
            $ranking->target_user_score = 0;
            $ranking->user_rank = null;
        }
        return $ranking;
    }

    /**
     * Get the global ranking of the user.
     *
     * @return mixed The ranking of the user.
     * @throws \dml_exception
     */
    protected function search_global_ranking(): mixed {
        global $DB;

        // Get the ranking of all users sort by score.
        $sql = "WITH UserScores AS (
                    SELECT
                        a.userid,
                        SUM(CAST(cmu.value AS DECIMAL(10,2))) as total_score
                    FROM {format_ludilearn_attributio} a
                    LEFT JOIN {format_ludilearn_cm_user} cmu ON a.id = cmu.attributionid
                    WHERE a.gameelementid = :gameelementid
                    AND cmu.name = 'score'
                    GROUP BY a.userid
                 ),
                RankedScores AS (
                    SELECT
                        RANK() OVER (ORDER BY total_score DESC) AS user_rank,
                        userid,
                        total_score
                    FROM UserScores
                ),
                TargetUser AS (
                    SELECT
                        userid,
                        total_score,
                        user_rank
                    FROM RankedScores
                    WHERE userid = :target_userid
                ),
                PrecedingUser AS (
                    SELECT
                        userid,
                        total_score,
                        user_rank
                    FROM RankedScores
                   WHERE user_rank = (CASE
                        WHEN (SELECT user_rank FROM TargetUser) > 1
                        THEN (SELECT user_rank FROM TargetUser) - 1
                        ELSE NULL
                     END)
                ),
                PrecedingUser2 AS (
                    SELECT
                        userid,
                        total_score,
                        user_rank
                    FROM RankedScores
                    WHERE user_rank = (CASE
                        WHEN (SELECT user_rank FROM TargetUser) > 2
                        THEN (SELECT user_rank FROM TargetUser) - 2
                        ELSE NULL
                     END)
                ),
                SucceedingUser AS (
                    SELECT
                        userid,
                        total_score,
                        user_rank
                    FROM RankedScores
                    WHERE user_rank = (SELECT user_rank + 1 FROM TargetUser)
                ),
                SucceedingUser2 AS (
                    SELECT
                        userid,
                        total_score,
                        user_rank
                    FROM RankedScores
                    WHERE user_rank = (SELECT user_rank + 2 FROM TargetUser)
                ),
                FirstUser AS (
                    SELECT
                        userid,
                        total_score,
                        user_rank
                    FROM RankedScores
                    WHERE user_rank = 1
                )
                SELECT
                    t.user_rank AS user_rank,
                    t.total_score AS user_total_score,
                    p.userid AS preceding_userid,
                    p.total_score AS preceding_user_total_score,
                    p.user_rank AS preceding_user_rank,
                    p2.userid AS preceding2_userid,
                    p2.total_score AS preceding2_user_total_score,
                    p2.user_rank AS preceding2_user_rank,
                    s.userid AS succeeding_userid,
                    s.total_score AS succeeding_user_total_score,
                    s.user_rank AS succeeding_user_rank,
                    s2.userid AS succeeding2_userid,
                    s2.total_score AS succeeding2_user_total_score,
                    s2.user_rank AS succeeding2_user_rank,
                    f.userid AS first_userid,
                    f.total_score AS first_user_total_score,
                    f.user_rank AS first_user_rank
                FROM
                    TargetUser t
                    LEFT JOIN PrecedingUser p ON 1 = 1
                    LEFT JOIN PrecedingUser2 p2 ON 1 = 1
                    LEFT JOIN SucceedingUser s ON 1 = 1
                    LEFT JOIN SucceedingUser2 s2 ON 1 = 1
                    LEFT JOIN FirstUser f ON 1 = 1
                LIMIT 1";

        $ranking = $DB->get_record_sql($sql, ['gameelementid' => $this->get_id(), 'target_userid' => $this->userid]);
        if (!$ranking) {
            // Search the rankinf of 3 first users.
            $sql = "WITH UserScores AS (
                        SELECT
                            a.userid,
                            SUM(CAST(cmu.value AS DECIMAL(10,2))) as total_score
                        FROM {format_ludilearn_attributio} a
                        LEFT JOIN {format_ludilearn_cm_user} cmu ON a.id = cmu.attributionid
                        WHERE a.gameelementid = :gameelementid
                        AND cmu.name = 'score'
                        GROUP BY a.userid
                    ),
                    RankedScores AS (
                        SELECT
                            RANK() OVER (ORDER BY total_score DESC) AS user_rank,
                            userid,
                            total_score
                        FROM UserScores
                    ),
                    FirstUser AS (
                        SELECT
                            userid,
                            total_score,
                            user_rank
                        FROM RankedScores
                        WHERE user_rank = 1
                    ),
                    SecondUser AS (
                        SELECT
                            userid,
                            total_score,
                            user_rank
                        FROM RankedScores
                        WHERE user_rank = 2
                    ),
                    ThirdUser AS (
                        SELECT
                            userid,
                            total_score,
                            user_rank
                        FROM RankedScores
                        WHERE user_rank = 3
                    )
                    SELECT
                        f.userid AS first_userid,
                        f.total_score AS first_user_total_score,
                        f.user_rank AS first_user_rank,
                        s.userid AS preceding_userid,
                        s.total_score AS preceding_user_total_score,
                        s.user_rank AS preceding_user_rank,
                        s2.userid AS preceding2_userid,
                        s2.total_score AS preceding2_user_total_score,
                        s2.user_rank AS preceding2_user_rank
                    FROM
                        FirstUser f
                        LEFT JOIN SecondUser s ON 1 = 1
                        LEFT JOIN ThirdUser s2 ON 1 = 1
                    LIMIT 1";
            $ranking = $DB->get_record_sql($sql, ['gameelementid' => $this->get_id()]);
            if (!$ranking) {
                $ranking = new stdClass();
                $ranking->preceding_userid = null;
                $ranking->preceding_user_total_score = 0;
                $ranking->preceding_user_rank = 2;
                $ranking->preceding2_userid = null;
                $ranking->preceding2_user_total_score = null;
                $ranking->preceding2_user_rank = 3;
                $ranking->succeeding_userid = null;
                $ranking->succeeding_user_total_score = 0;
                $ranking->succeeding_user_rank = 0;
                $ranking->succeeding2_userid = null;
                $ranking->succeeding2_user_total_score = 0;
                $ranking->succeeding2_user_rank = 0;
                $ranking->first_userid = null;
                $ranking->first_user_total_score = 0;
                $ranking->first_user_rank = null;
            }
            $ranking->user_rank = null;
            $ranking->user_total_score = 0;
        }
        return $ranking;
    }
}
