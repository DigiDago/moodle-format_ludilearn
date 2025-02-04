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

namespace format_ludilearn\local\adaptation;

use Exception;

/**
 * Hexad scores class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui, Nihal Ouherrou
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hexad_scores {
    /**
     * @var float
     */
    public float $achiever;

    /**
     * @var float
     */
    public float $player;

    /**
     * @var float
     */
    public float $socialiser;

    /**
     * @var float
     */
    public float $freespirit;

    /**
     * @var float
     */
    public float $disruptor;

    /**
     * @var float
     */
    public float $philanthropist;

    /**
     * Constructor
     *
     * @param float $achiever Achiever score.
     * @param float $player Player score.
     * @param float $socialiser Socialiser score.
     * @param float $freespirit Free Spirit score.
     * @param float $disruptor Disruptor score.
     * @param float $philanthropist Philanthropist score.
     */
    public function __construct(float $achiever,
        float $player,
        float $socialiser,
        float $freespirit,
        float $disruptor,
        float $philanthropist) {
        $this->achiever = $achiever;
        $this->player = $player;
        $this->socialiser = $socialiser;
        $this->freespirit = $freespirit;
        $this->disruptor = $disruptor;
        $this->philanthropist = $philanthropist;
    }

    /**
     * Retrieve Hexad scores from the database based on the user's questionnaire responses.
     *
     * @param int $userid User ID.
     *
     * @return hexad_scores Hexad scores.
     * @throws \dml_exception
     */
    public static function from_database(int $userid): hexad_scores {
        global $DB;

        // Define the question correspondences for each Hexad type.
        $questioncorrespondences = [
            "achiever" => [5, 6],
            "player" => [9, 12],
            "socialiser" => [2, 4],
            "freeSpirit" => [7, 10],
            "disruptor" => [8, 11],
            "philanthropist" => [1, 3],
        ];

        // Initialize the Hexad scores.
        $hexadscores = new hexad_scores(0, 0, 0, 0, 0, 0);

        // Retrieve the scores for each Hexad type.
        foreach ($questioncorrespondences as $hexadtype => $questionids) {
            $questionidsstring = implode(',', $questionids);

            $query = "SELECT questionid, score
                FROM {format_ludilearn_answers}
                WHERE userid = :userid
                AND questionid IN ($questionidsstring)";
            $params = ['userid' => $userid];
            $scores = $DB->get_records_sql($query, $params);

            foreach ($scores as $score) {
                $questionscore = intval($score->score);
                // Add the score of each question to the total of the corresponding profile.
                $hexadscores->{$hexadtype} += $questionscore;
            }
        }

        // Initialize a new hexadscores object with the calculated total scores.
        return new hexad_scores(
            $hexadscores->achiever,
            $hexadscores->player,
            $hexadscores->socialiser,
            $hexadscores->freespirit,
            $hexadscores->disruptor,
            $hexadscores->philanthropist
        );
    }

    /**
     * Get the score for a specific Hexad type.
     *
     * @param string $typename Hexad type name.
     *
     * @return float Hexad score.
     * @throws Exception
     */
    public function get_value(string $typename): float {
        $typename = strtolower($typename);
        return match ($typename) {
            "achiever" => $this->achiever,
            "player" => $this->player,
            "socialiser" => $this->socialiser,
            "freespirit" => $this->freespirit,
            "disruptor" => $this->disruptor,
            "philanthropist" => $this->philanthropist,
            default => throw new Exception("Type not found in Hexad Data: " . $typename),
        };
    }
}
