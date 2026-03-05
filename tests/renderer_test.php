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

use format_ludilearn\local\adaptation\hexad_scores;
use format_ludilearn\local\gameelements\score;
use format_ludilearn\output\renderer;
use stdClass;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Unit tests for the renderer class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers           \format_ludilearn\output\renderer
 */
final class renderer_test extends \advanced_testcase {
    /**
     * Generate data.
     */
    public function generate_data(): stdClass {
        global $DB;

        $data = new stdClass();
        // Generate a course.
        $data->course = $this->getDataGenerator()->create_course();

        // Generate a user.
        $data->user = $this->getDataGenerator()->create_user();

        // Enrol the user in the course.
        $this->getDataGenerator()->enrol_user($data->user->id, $data->course->id, 'student');

        // Insert question answers.
        $answers = [
            (object)[
                'questionid' => 1,
                'userid' => $data->user->id,
                'score' => 7,
            ],
            (object)[
                'questionid' => 2,
                'userid' => $data->user->id,
                'score' => 7,
            ],
            (object)[
                'questionid' => 3,
                'userid' => $data->user->id,
                'score' => 4,
            ],
            (object)[
                'questionid' => 4,
                'userid' => $data->user->id,
                'score' => 7,
            ],
            (object)[
                'questionid' => 5,
                'userid' => $data->user->id,
                'score' => 7,
            ],
            (object)[
                'questionid' => 6,
                'userid' => $data->user->id,
                'score' => 7,
            ],
            (object)[
                'questionid' => 7,
                'userid' => $data->user->id,
                'score' => 7,
            ],
            (object)[
                'questionid' => 8,
                'userid' => $data->user->id,
                'score' => 1,
            ],
            (object)[
                'questionid' => 9,
                'userid' => $data->user->id,
                'score' => 5,
            ],
            (object)[
                'questionid' => 10,
                'userid' => $data->user->id,
                'score' => 7,
            ],
            (object)[
                'questionid' => 11,
                'userid' => $data->user->id,
                'score' => 1,
            ],
            (object)[
                'questionid' => 12,
                'userid' => $data->user->id,
                'score' => 5,
            ],
        ];
        $DB->insert_records('format_ludilearn_answers', $answers);

        return $data;
    }


    /**
     * Test for render_gameprofile().
     *
     * @covers ::render_gameprofile
     */
    public function test_renderer_gameprofile(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $data = $this->generate_data();
        $USER->id = $data->user->id;

        $page = new \moodle_page();
        $renderer = new renderer($page, '');
        $datafortemplate = $renderer->data_for_gameprofile($data->course->id);

        // Check the hexad scores.
        $this->assertEquals(14, $datafortemplate->hexadscores->achiever);
        $this->assertEquals(10, $datafortemplate->hexadscores->player);
        $this->assertEquals(14, $datafortemplate->hexadscores->socialiser);
        $this->assertEquals(14, $datafortemplate->hexadscores->freespirit);
        $this->assertEquals(2, $datafortemplate->hexadscores->disruptor);
        $this->assertEquals(11, $datafortemplate->hexadscores->philanthropist);

        // Check percentage.
        $this->assertEquals('22%', $datafortemplate->hexadscores->achieverpercentage);
        $this->assertEquals('15%', $datafortemplate->hexadscores->playerpercentage);
        $this->assertEquals('22%', $datafortemplate->hexadscores->socialiserpercentage);
        $this->assertEquals('22%', $datafortemplate->hexadscores->freespiritpercentage);
        $this->assertEquals('3%', $datafortemplate->hexadscores->disruptorpercentage);
        $this->assertEquals('16%', $datafortemplate->hexadscores->philanthropistpercentage);
    }
}
