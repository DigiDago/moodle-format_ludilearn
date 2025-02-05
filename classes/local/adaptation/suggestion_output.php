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

use format_ludilearn\manager;
use stdClass;

/**
 * Suggestion output class.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui, Nihal Ouherrou
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class suggestion_output {

    /**
     * Generate a suggestion based on affinities.
     *
     * @param int $userid          The user for whom the suggestion is generated.
     * @param int | null $courseid The course for which the suggestion is generated. Default is null.
     *
     * @return array An array containing the suggestion and sorted combined scores.
     * @throws \dml_exception
     */
    public static function generate_suggestion_based_on_affinities(int $userid, $courseid = null): array {
        global $DB;

        // Use static values.
        $file = file_get_contents(static_values::hexad_affinity_matrix());
        $staticruleshex = json_decode($file, true);
        $hscores = hexad_scores::from_database($userid);

        // Generate suggestion matrices based on user profiles and affinities.
        $sugmathex = self::matrix_suggestion($hscores, $staticruleshex);
        // Sort the sugMatHex and sugMatMot arrays based on values in descending order.
        arsort($sugmathex);

        // Initialize an empty array to store combined scores.
        $combinedscores = [];

        // Iterate over the sorted sugMatHex array.
        foreach ($sugmathex as $itemhexkey => $itemhexvalue) {
            // Get the scores for the common item.
            $hexadscore = $itemhexvalue;
            // Store the combined score in the combinedScores array.
            $combinedscores[$itemhexkey] = $hexadscore;
        }

        // Check if the combinedScores array is empty.
        if (empty($combinedscores)) {
            // Handle the case where combinedScores is empty.
            // For example, return a default value or generate an alternative suggestion.
            $suggestion = null;
        } else {
            // Sort the combinedScores array based on values in descending order.
            arsort($combinedscores);
            // Get the first item (highest score) from the sorted array.
            $suggestion = array_key_first($combinedscores);
        }

        // Convert the combinedScores array to JSON string.
        $combinedaffinitiesjson = json_encode($combinedscores);
        $manager = new manager();
        $gameelements = $manager->get_gameelements_auto($suggestion, $courseid);
        $profile = $DB->get_record('format_ludilearn_profile', ['userid' => $userid]);

        if ($profile) {
            $profile->userid = $userid;
            $profile->type = $suggestion;
            $profile->combinedaffinities = $combinedaffinitiesjson;
            $DB->update_record('format_ludilearn_profile', $profile);
        } else {
            $profile = new stdClass();
            $profile->userid = $userid;
            $profile->type = $suggestion;
            $profile->combinedaffinities = $combinedaffinitiesjson;
            $DB->insert_record('format_ludilearn_profile', $profile);
        }

        foreach ($gameelements as $gameelement) {
            $manager->attribution_game_element($gameelement->id, $userid);
        }

        // Return the suggestion and the combined scores sorted as an array.
        return [$suggestion, $combinedscores];
    }

    /**
     * Calculate the matrix suggestion based on user profiles and affinity matrix.
     *
     * @param hexad_scores $hexadscores The user's HEXAD scores.
     * @param array $affinitymatrix     The affinity matrix containing the static rules for game elements and profile
     *                                  values.
     *
     * @return array The calculated matrix suggestion for each game element.
     * @throws \Exception
     */
    public static function matrix_suggestion(hexad_scores $hexadscores, array $affinitymatrix): array {

        // Initialize an empty array to store the output values.
        $output = [];
        $gameelementslist = static_values::game_elements_list();
        $hexadprofile = static_values::hexad_profile();
        foreach ($gameelementslist as $gameelement) {
            // Initialize the output value for the current game element as 0.
            $output[$gameelement] = 0;

            // Calculate the suggestion based on the HEXAD profile.
            foreach ($hexadprofile as $profilevalue) {
                // Get the user's profile score for the current profile value.
                $userprofilescore = self::get_profile_value($profilevalue, $hexadscores);

                // Check if the key exists in the affinity matrix.
                if (isset($affinitymatrix[$gameelement][$profilevalue])) {
                    // Get the static rule from the affinity matrix.
                    $staticrule = round($affinitymatrix[$gameelement][$profilevalue], 2);
                    // Update the output value by multiplying the user's profile score with the static rule.
                    $output[$gameelement] += round($userprofilescore * $staticrule, 2);
                } else {
                    // Handle the case where the key doesn't exist in the affinity matrix.
                    // You can choose to skip, set a default value, or handle it differently.
                    // Here, we skip the calculation for this profile value.
                    continue;
                }
            }
        }

        return $output;
    }

    /**
     * Get the profile value for a given profile value and HEXAD scores.
     *
     * @param string $profilevalue     The profile value for which the score is calculated.
     * @param hexad_scores $hexadscore The user's HEXAD scores.
     *
     * @return float The calculated profile score.
     * @throws \Exception
     */
    public static function get_profile_value(string $profilevalue, hexad_scores $hexadscore): float {
        $hexadvalues = ["achiever", "player", "socialiser", "freespirit", "disruptor", "philanthropist"];

        $profilescore = 0;
        if (in_array($profilevalue, $hexadvalues)) {
            $profilescore = $hexadscore->get_value($profilevalue);
        }

        return $profilescore;
    }
}
