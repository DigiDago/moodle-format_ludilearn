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
 * Plugin strings are defined here.
 *
 * @package     format_ludimoodle
 * @category    string
 * @copyright   2023 Pimenko <support@pimenko.com><pimenko.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Ludimoodle';
$string['assignment'] = 'Mode d\'attribution des éléments de jeu';
$string['assignment_help'] = 'L\'attribution détermine comment les éléments de jeu sont assignés aux apprenants. Les 3 modes d\'attribution sont :<br>
* Automatique : via l\'algorithme Ludimoodle qui attribue automatiquement un élément de jeu selon le profil de l\'apprenant (appliqué sur tous les cours). Si vos apprenants accèdent à un cours ludifié pour la première fois, ils auront à remplir un questionnaire qui prendra 5 minutes.<br>
* Manuel : en sélectionnant un élément de jeu de votre choix pour tous les apprenants et pour tout le cours.<br>
* Par section : en sélectionnant en élément de jeu de votre choix pour tous les apprenants pour chaque section de cours.';
$string['default'] = 'Par défaut';
$string['manual'] = 'Manuellement pour tout le cours';
$string['automatic'] = 'Automatique : basé sur un questionnaire et l\'algorithme LudiMoodle';
$string['bysection'] = 'Par section';
$string['assignmentbysection'] = 'Attribution des éléments de jeu par section';
$string['world'] = 'Univers';
$string['world_help'] = 'L\'univers est le design qui est utilisé pour les éléments de jeu. Choisissez le design le plus pertinent en fonction de vos apprenants.';
$string['school'] = 'Écolier';
$string['professional'] = 'Professionnel';
$string['highschool'] = 'Lycéen';
$string['addsections'] = 'Ajouter des sections';
$string['currentsection'] = 'Cette section';
$string['editsection'] = 'Éditer la section';
$string['editsectionname'] = 'Modifier le nom de la section';
$string['deletesection'] = 'Supprimer la section';
$string['newsectionname'] = 'Nouveau nom de la section {$a}';
$string['sectionname'] = 'Section';
$string['settingsname'] = 'Personnalisation des éléments de jeu LudiMoodle';
$string['section0name'] = 'Général';
$string['hidefromothers'] = 'Cacher la section';
$string['showfromothers'] = 'Afficher la section';
$string['check_format_options_changements'] = 'Vérifier les changements d\'options du format';
$string['score'] = 'Score';
$string['maxscore'] = 'Score maximum';
$string['default_game_element'] = 'Élément de jeu par défaut';
$string['default_game_element_help'] = 'L\'élément de jeu par défaut est celui qui est appliqué à tous les apprenants et à toutes les sections du cours.';
$string['lesson'] = 'Leçon';
$string['editgameeleements'] = 'Paramétrer les éléments de jeu';
$string['gameelements'] = 'Éléments de jeu';
$string['gameelement'] = 'Élément de jeu';
$string['parameters'] = 'Paramètrage';
$string['saved'] = 'Enregistrer';
$string['error'] = 'Erreur';
$string['updatescore'] = 'Mise à jour de l\'élément de jeu score';
$string['condition'] = 'Condition';
$string['completion'] = 'Achèvement';
$string['grade'] = 'Note';
$string['nogamification'] = 'Pas de ludification';
$string['nogamified'] = 'Non ludifié';
$string['badge'] = 'Badge';
$string['maxserie'] = 'Série maximum';
$string['progress'] = 'Progression';
$string['gameprofile'] = 'Profil de joueur';
$string['questionnaire'] = 'Questionnaire';
$string['questionshexad'] = '<h3>Vous allez accéder à un cours ludifié.</h3>
<p>Aidez-nous à l\'adapter à votre profil de joueur <a href="https://gamified.uk/UserTypeTest2023/user-type-test.php" target="_blank">HEXAD-12</a> en indiquant à quel point chaque phrase vous correspond (1 : Pas du tout d\'accord, 7 : Totalement d\'accord)</p>';
$string['questionnaire:question1'] = 'Cela me rend heureux de pouvoir aider les autres';
$string['questionnaire:question2'] = 'J\'apprécie les activités de groupe';
$string['questionnaire:question3'] = 'Le bien-être des autres m\'est important';
$string['questionnaire:question4'] = 'J\'aime faire partie d\'une équipe';
$string['questionnaire:question5'] = 'J\'aime gérer des tâches difficiles';
$string['questionnaire:question6'] = 'J\'aime sortir victorieux de circonstances difficiles';
$string['questionnaire:question7'] = 'Être indépendant est une chose importante pour moi';
$string['questionnaire:question8'] = 'Je n\'aime pas suivre les règles';
$string['questionnaire:question9'] = 'Si la récompense est suffisante, je ferai des efforts';
$string['questionnaire:question10'] = 'Il est important pour moi de suivre ma propre voie';
$string['questionnaire:question11'] = 'Je me perçois comme étant rebelle';
$string['questionnaire:question12'] = 'Les récompenses sont un bon moyen de me motiver';
$string['avatar'] = 'Avatar';
$string['items'] = 'Éléments';
$string['itemunlocked'] = 'Éléments débloqués';
$string['inventory'] = 'Inventaire';
$string['rightarm'] = 'Bras droit';
$string['leftarm'] = 'Bras gauche';
$string['head'] = 'Tête';
$string['face'] = 'Visage';
$string['body'] = 'Corps';
$string['others'] = 'Autres';
$string['decoration'] = 'Décoration';
$string['picture'] = 'Tableau';
$string['lamp'] = 'Lampe';
$string['sportstuff'] = 'Équipement de sport';
$string['pet'] = 'Animal de compagnie';
$string['desk'] = 'Bureau';
$string['tshirt'] = 'T-shirt';
$string['ball'] = 'Ballon';
$string['bed'] = 'Lit';
$string['hull'] = 'Coque';
$string['sail'] = 'Voile';
$string['flag'] = 'Drapeau';
$string['figurehead'] = 'Figure de proue';
$string['propulsion'] = 'Propulsion';
$string['theme'] = 'Thème';
$string['timer'] = 'Temps';
$string['besttime'] = 'Meilleur temps';
$string['averagetime'] = 'Temps moyen';
$string['reference_time'] = 'Temps de référence';
$string['ranking'] = 'Classement';
$string['report'] = 'Rapport';
$string['progression'] = 'Progression';
$string['missinganswers'] = 'Cette question doit être complétée';
$string['equip'] = 'Équiper';
$string['equiped'] = 'Équipé';
$string['first'] = 'er';
$string['second'] = 'ème';
$string['third'] = 'ème';
$string['th'] = 'ème';
$string['me'] = 'Moi';
$string['gamify'] = 'Gamifier';
$string['notgamify'] = 'Ne pas ludifier';
$string['seconds'] = 'secondes';
$string['ofpenalties'] = 'de pénalités';
$string['globalsettings'] = 'Réglages globaux';
$string['editsettingssuccess'] = 'Les modifications apportées ont bien été appliquées';
$string['editsettingsfailed'] = 'Une erreur est apparue. Les modifications apportées n\'ont pas été appliquées';
$string['settings:multiplier'] = 'Multiplicateur';
$string['settings:multiplier_help'] = 'Le multiplicateur est un coefficient appliqué uniformément à tous les points gagnés dans le cours, pour renforcer l\'aspect ludique du système de score.<br>
Par défaut, sa valeur est 1. En l\'augmentant (par exemple à 2 ou 10), vous ajuster tous les scores obtenus.<br>
Cette option permet de créer une dimension plus ludique sans modifier l\'équilibre entre les activités du cours';
$string['settings:percentagecompletion'] = 'Pourcentage additionnel pour les activités notées avec achèvement';
$string['settings:percentagecompletion_help'] = 'Ce paramètre définit un bonus supplémentaire accordé lorsqu\'un apprenant termine une activité qui est à la fois notée et qui a un statut d\'achèvement (par exemple les devoirs, les quiz, les leçons).<br>
Le bonus est calculé comme un pourcentage de la note maximale de l\'activité. Par exemple, si ce pourcentage est fixé à 10% :<br>
* Un devoir noté sur 100 points donnera un bonus de 10 points à l\'achèvement.<br>
* Un quiz noté sur 50 points donnera un bonus de 5 points à l\'achèvement.<br>
Ce bonus s\'ajoute à la note obtenue, récompensant ainsi les apprenants pour avoir terminé l\'activité, indépendamment de leur performance.';
$string['settings:bonuscompletion'] = 'Bonus d\'achèvement';
$string['settings:bonuscompletion_help'] = 'Le bonus d\'achèvement est un nombre de points fixes accordés lorsqu\'un apprenant achève une activité qui n\'a pas de note et l\'achèvement d\'activité.<br>
Ce bonus s\'applique de manière uniforme à toutes les activités du cours qui ont seulement l\'option d\'achèvement activée (par exemple, consulter une ressource ou participer à un forum non noté). Utiliser ce bonus encourage les apprenants à interagir avec tous les éléments du cours, même ceux qui ne sont pas notés directement.';
$string['settings:badgegold'] = 'Seuil pour le badge or';
$string['settings:badgegold_help'] = 'Ce paramètre définit le pourcentage minimum de la note qu\'un apprenant doit obtenir pour recevoir le badge or dans une activité notée. Par exemple, si le seuil est fixé à 90% :<br>
* Pour une activité notée sur 100 points, l\'apprenant devra obtenir au moins 90 points.<br>
* Pour un quiz noté sur 50 points, l\'apprenant devra obtenir au moins 45 points.';
$string['settings:badgesilver'] = 'Seuil pour le badge argent';
$string['settings:badgesilver_help'] = 'Ce paramètre définit le pourcentage minimum de la note qu\'un apprenant doit obtenir pour recevoir le badge argent dans une activité notée. Par exemple, si le seuil est fixé à 85% :<br>
* Pour une activité notée sur 100 points, l\'apprenant devra obtenir au moins 85 points.<br>
* Pour un quiz noté sur 50 points, l\'apprenant devra obtenir au moins 42,5 points.';
$string['settings:badgebronze'] = 'Seuil pour le badge bronze';
$string['settings:badgebronze_help'] = 'Ce paramètre définit le pourcentage minimum de la note qu\'un apprenant doit obtenir pour recevoir le badge bronze dans une activité notée. Par exemple, si le seuil est fixé à 70% :<br>
* Pour une activité notée sur 100 points, l\'apprenant devra obtenir au moins 70 points.<br>
* Pour un quiz noté sur 50 points, l\'apprenant devra obtenir au moins 35 points.';
$string['settings:nosetting'] = 'Cet élément de jeu fonctionne automatiquement en se basant sur la structure et les paramètres existants de votre cours. Il ne nécessite pas de configuration supplémentaire et il n\'y a pas d\'options à ajuster.';
$string['settings:penalties'] = 'Pénalités par point perdu';
$string['settings:penalties_help'] = 'Ce paramètre définit le nombre de secondes ajoutées au temps final du quiz pour chaque réponse fausse à une question de l\'apprenant. Chaque point perdu ajoute le temps du paramètre pénalité.<br>
Par exemple, si la pénalité est fixée à 20 secondes et qu\'un apprenant obtient 0 point à une question sur 2 points,  40 secondes seront ajoutées à son temps final.';
$string['settings:thresholdtoearn'] = 'Seuil pour débloquer un élément d\'avatar';
$string['settings:thresholdtoearn_help'] = 'Ce paramètre définit le score minimum (en pourcentage) qu\'un apprenant doit obtenir dans une activité notée pour débloquer un nouvel élément. Par exemple, si le seuil est fixé à 80% :<br>
* Un score de 81%  dans un quiz débloquera un élément d\'avatar.<br>
* Un score de 79%  ne débloquera rien, même si l\'activité est complétée.<br>
Ajustez ce seuil en fonction de la difficulté de vos activités et de la fréquence à laquelle vous souhaitez que les apprenants débloquent de nouveaux éléments.';
$string['settings:scoredescription'] = '<p> L\'élément de jeu score permet aux apprenants d\'accumuler des points en réalisant des activités du cours. Son fonctionnement s\'adapte automatiquement aux différents types d\'activités proposées.<br><br>
1. Pour les activités notées, le score est directement basé sur la note obtenue. Par exemple, une note de 16 sur 20 se traduit par 16 points.<br>
Un coefficient multiplicateur est appliqué pour transformer ces points en score comme dans les jeux. Par exemple, un coefficient de 80 affichera 1280 points dans le système de score.<br>
2. Pour les activités avec uniquement de l\'achèvement, un nombre fixe appelé bonus d\'achèvement est attribuée lorsque les activités sont achevées. Par exemple, à la fin de l\'activité, l\'apprenant obtient 150 points.<br>
3. Dans le cas des activités combinant note et achèvement, le score prend en compte à la fois la note et un pourcentage additionnel ajouté à la note. Par exemple, achever l\'activité ajoutera 20% de points supplémentaires par rapport au score total pouvant être atteint.  </p>';
$string['settings:badgedescription'] = '<p>Cet élément de jeu récompense les apprenants avec des badges en réalisant des activités   . Il s\'adapte automatiquement aux différents types d\'activités du cours<br><br>
1. Pour les activités notées, trois niveaux de badges (Or, Argent, Bronze) sont attribués automatiquement lorsque la note de l\'apprenant atteint ou dépasse le seuil défini pour chaque niveau.<br>
2. Dans le cas des activités non notées mais avec un achèvement d\'activité, les apprenants obtiennent directement le badge Or lorsqu\'ils complètent l\'activité.<br>
3. Dans le cas d\'une activité notée et avec de l\'achèvement d\'activité, les apprenants pourront obtenir l\'un des 3 niveaux de badges (Or, argent, Bronze) et un badge bonus lié à l\'achèvement.</p>';
$string['settings:progressiondescription'] = '<p> L\'élément de jeu progression affiche l\'avancement global de l\'apprenant dans le cours. Il s\'adapte automatiquement aux différents types d\'activités.<br><br>
1. Pour les activités notées, la progression correspond directement au pourcentage de la note obtenue. Par exemple, si un apprenant obtient 80% à un quiz, sa progression pour cette activité sera de 80%.<br>
2. Pour les activités avec uniquement de l\'achèvement, la progression augmente de 100%  dès que l\'activité est marquée comme terminée.<br>
3. Pour les activités qui combinent note et achèvement, c\'est la note obtenue qui détermine la progression, l\'achèvement n\'a pas d\'impact supplémentaire.</p>';
$string['settings:avatardescription'] = '<p>Cet élément de jeu permet aux apprenants de gagner des objets ou accessoires et de personnaliser leur avatar dans le cours. Les apprenants débloquent ces éléments lorsqu\'ils progressent dans le cours, qu\'ils peuvent ensuite choisir d\'activer ou non selon leurs préférences.<br><br>
1. Pour les activités notées, le déblocage des éléments est basé sur l\'obtention d\'un score supérieur au seuil pour gagner un élément. Par exemple, si le seuil est fixé à 80%, l\'apprenant devra obtenir une note supérieure à 80% dans une activité notée pour débloquer un nouvel élément.<br>
2. Pour les activités avec uniquement de l\'achèvement, un élément est débloqué lorsque l\'activité a le statut achevé.<br>
3. Pour les activités qui combinent note et achèvement, c\'est la note obtenue qui est utilisée. L\'achèvement d\'activité n\'a pas d\'impact supplémentaire.</p>';
$string['settings:timerdescription'] = '<p>L\'élément de jeu chronomètre ajoute une dimension temporelle  aux quiz de votre cours. <strong>Il fonctionne exclusivement avec les activités de type test (ou quiz)</strong>. L\'élément affiche le temps mis par l\'apprenant pour réaliser le test, des pénalités en temps supplémentaire étant appliquées en cas d\'erreur.<br>
- Le temps utilisé par l\'apprenant est comptabilisé au niveau du quiz<br>
- Le meilleur temps de l\'apprenant est enregistré et affiché dans le quiz, la moyenne des temps aux quiz est affiché au niveau de la section de cours.<br>
- En cas d\'erreur aux quiz, du temps supplémentaire (pénalité) est ajouté.</p>';
$string['settings:rankingdescription'] = '<p> L\'élément de jeu classement offre une vue comparative des performances des apprenants par rapport aux autres apprenants dans le cours. Il s\'adapte automatiquement aux différents types d\'activités du cours.<br>
1. Pour les activités notées, la note obtenue est directement convertie en points qui contribuent au classement. Par exemple, une note de 80% sur 100 points se traduit par 80 points dans le classement. Si 6 apprenants ont obtenu une meilleure note, il sera affiché en 7ème position dans le classement.<br>
2. Les activités avec uniquement de l\'achèvement n\'ont pas d\'impact sur le classement.<br>
3. Dans le cas des activités combinant note et achèvement, uniquement la note est prise en compte pour le classement.</p>';
$string['settings:nogamifieddescription'] = '<p>L\'élément de jeu non ludifié apparait sur toutes les activités et les ressources de cours sans ludification.</p>';
$string['gameprofile_desc'] = 'D\'après vos réponses, voici votre profil de joueur HEXAD-12 :';
$string['achiever'] = 'Accomplisseur';
$string['player'] = 'Joueur';
$string['socialiser'] = 'Socialiseur';
$string['freeSpirit'] = 'Esprit libre';
$string['disruptor'] = 'Disrupteur';
$string['philanthropist'] = 'Philanthrope';
$string['backtocourse'] = 'Retour au cours';
$string['achiever_desc'] = 'Motivé par la compétence et la maîtrise';
$string['player_desc'] = 'Motivé par la récompense';
$string['socialiser_desc'] = 'Motivé par les relations sociales et le sentiment d’appartenance';
$string['freeSpirit_desc'] = 'Motivé par l’autonomie';
$string['disruptor_desc'] = 'Motivé par le changement';
$string['philanthropist_desc'] = 'Motivé par le sens et l\’utilité';
$string['partner_text'] = '<p>Développé par <a href="https://pimenko.com" target="_blank">Pimenko</a>.<br>
Pour les retours techniques, merci d\'utiliser le <a href="https://github.com/DigiDago/moodle-format_ludimoodle" target="_blank">répertoire Github</a><br><br>
<b>À propos de Ludimoodle+</b><br><br>
Le projet LudiMoodle+, porté par l\'Université de Lyon, bénéficie d\'une aide de l\'État gérée par l\'Agence nationale de la recherche au titre de France 2030 portant la référence « ANR-22-FRAN-0005 »</p>';
$string['partner_text2'] = '<p><b>Les partenaires du projet</b></p>';