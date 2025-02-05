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

/**
 * Format Ludilearn plugin report page.
 *
 * @package          format_ludilearn
 * @copyright        2025 Pimenko <support@pimenko.com><pimenko.com>
 * @author           Jordan Kesraoui
 * @license          http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require('../../../config.php');
require_once('lib.php');
global $PAGE, $DB, $CFG, $OUTPUT;

$context = context_system::instance();
require_login();
require_capability('moodle/role:manage', $context);
$PAGE->set_url(new moodle_url("$CFG->wwwroot/course/format/ludilearn/elementmanual.php", []));
$PAGE->set_context($context);
$PAGE->set_title('Saisie manuelle de l\'élément de jeu');
$PAGE->set_heading(
    'Saisie manuelle de l\'élément de jeu'
);
$PAGE->add_body_class('limitedwidth');

$renderer = $PAGE->get_renderer('format_ludilearn');

echo $OUTPUT->header();

// HTML de formulaire l'association d'un élément de jeu à un utilisateur (id).
// L'élément de jeu est choisi dans une liste déroulante.
// L'élément de jeu est associé à un utilisateur (id).
$html = '<form action="elementmanual.php" method="post">
<label for="element">Choisir un élément de jeu :</label>
<select name="element" id="element">
<option value="score" selected>Score</option>
<option value="badge">Badge</option>
<option value="avatar">Avatar</option>
<option value="progress">Progression</option>
<option value="ranking">Ranking</option>
<option value="timer">Timer</option>
<option value="nogamified">Non Gamifié</option>
</select>
<label for="id">Choisir un utilisateur :</label>
<input type="text" id="id" name="id">
<input type="submit" value="Valider">
</form>';

echo $html;

if (isset($_POST['element']) && isset($_POST['id'])) {
    $user = $DB->get_record('user', ['id' => $_POST['id']]);
    if (!$user) {
        echo 'Utilisateur non trouvé';
        die();
    }

    $ludilearnprofile = $DB->get_record('format_ludilearn_profile', ['userid' => $_POST['id']]);
    if ($ludilearnprofile) {
        $DB->delete_records('format_ludilearn_profile', ['userid' => $_POST['id']]);
    }

    $ludilearnprofile = new stdClass();
    $ludilearnprofile->type = $_POST['element'];
    $ludilearnprofile->userid = $_POST['id'];
    $ludilearnprofile->combinedaffinities = 'manual';
    $DB->insert_record('format_ludilearn_profile', $ludilearnprofile);
    echo 'L\'élément de jeu a bien été associé à l\'utilisateur';
}
echo $OUTPUT->footer();
