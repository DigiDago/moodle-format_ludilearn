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

namespace format_ludilearn;

use format_ludilearn\local\gameelements\score;
use stdClass;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Unit tests for the score class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers           \format_ludilearn\local\gameelements\score
 */
final class score_test extends \advanced_testcase {
    /**
     * Generate default data.
     *
     */
    public function generate_default(): stdClass {
        global $DB;

        $default = new stdClass();
        // Generate a course.
        $default->course = $this->getDataGenerator()->create_course();

        // Generate a user.
        $default->user = $this->getDataGenerator()->create_user();

        // Enrol the user in the course.
        $this->getDataGenerator()->enrol_user($default->user->id, $default->course->id, 'student');

        // Generate somes activites.
        $page = $this->getDataGenerator()->create_module('page', ['course' => $default->course->id, 'section' => 1]);
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $default->course->id, 'section' => 1]);
        $page2 = $this->getDataGenerator()->create_module('page', ['course' => $default->course->id, 'section' => 1]);
        $default->cms = [$page, $quiz, $page2];

        // Retrieve section.
        $default->section = $DB->get_record('course_sections', ['course' => $default->course->id, 'section' => 1]);

        return $default;
    }

    /**
     * Test for get() with a new course.
     *
     * @covers ::get
     */
    public function test_get_from_new_course(): void {
        global $DB;

        $this->resetAfterTest(true);

        $manager = new manager();

        // Generate default data.
        $default = $this->generate_default();

        // CMS ids.
        $cmsids = [];
        foreach ($default->cms as $cm) {
            $cmsids[] = $cm->cmid;
        }

        // Create game element.
        $gameelementid = score::create('score', $default->course->id, $default->section->id);

        // Assign the game element to the user.
        $manager->attribution_game_element($gameelementid, $default->user->id);

        // Genenerate args.
        $scoreargs = $this->generate_score_constructor_args(
            $default->course->id,
            $default->section->id,
            $default->user->id,
            $cmsids
        );

        // Create mocks.
        $score = $this->getMockBuilder(score::class)
            ->setConstructorArgs($scoreargs)
            ->onlyMethods(
                [
                    'is_completion_enabled',
                    'is_completed',
                    'is_gradable',
                    'is_activity_available_for_user',
                    'get_grademax',
                ]
            )
            ->getMock();
        $score->method('is_completion_enabled')
            ->willReturn(true);
        $score->method('is_completed')
            ->willReturn(true);
        $score->method('is_gradable')
            ->willReturn(true);
        $score->method('is_activity_available_for_user')
            ->willReturn(true);
        $score->method('get_grademax')
            ->willReturn(10.0);
        $score->__construct(
            $scoreargs['id'],
            $scoreargs['courseid'],
            $scoreargs['sectionid'],
            $scoreargs['userid'],
            $scoreargs['parameters'],
            $scoreargs['cmparameters']
        );

        // Check section parameters.
        $sectionparameters = $score->get_parameters();
        $this->assertEquals(80, $sectionparameters['multiplier']);
        $this->assertEquals(150, $sectionparameters['bonuscompletion']);
        $this->assertEquals(20, $sectionparameters['percentagecompletion']);
        $this->assertEquals(1520, $score->get_score());
        $this->assertEquals(1600, $score->get_max_score());
        $this->assertEquals(320, $score->get_total_bonus_completion());
    }

    /**
     * Generate the constructor arguments for the score class.
     *
     * @param int $courseid The course id.
     * @param int $sectionid The section id.
     * @param int $userid The user id.
     * @param array $cmsids The cm ids.
     *
     * @return array|null The constructor arguments.
     */
    public function generate_score_constructor_args(int $courseid, int $sectionid, int $userid, $cmsids): ?array {
        global $DB;

        $gameelementsql = 'SELECT * FROM {format_ludilearn_elements} g
                            INNER JOIN {format_ludilearn_attributio} a ON g.id = a.gameelementid
                            WHERE g.courseid = :courseid AND g.sectionid = :sectionid
                            AND a.userid = :userid AND g.type = :type';

        $gameelementreq = $DB->get_record_sql(
            $gameelementsql,
            [
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'type' => 'score',
            ]
        );

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
        for ($i = 0; $i < count($cmsids); $i++) {
            $id = $cmsids[$i];
            $cmparameters[$id] = [];
            $cmparameters[$id]['id'] = $id;
            $cmparameters[$id]['score'] = ($i + 1) * 5;

            // Ungamified the last cm.
            if ($i == 2) {
                $cmparameters[$id]['gamified'] = false;
            }
        }

        return ['id' => $gameelementreq->gameelementid,
                'courseid' => $gameelementreq->courseid,
                'sectionid' => $gameelementreq->sectionid,
                'userid' => $gameelementreq->userid,
                'parameters' => $parameters,
                'cmparameters' => $cmparameters];
    }
}
