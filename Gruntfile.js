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
/* jshint node: true, browser: false */

/**
 * Grunt for compile scss file.
 *
 * @package     format_ludilearn
 * @copyright   2025 Pimenko <support@pimenko.com><pimenko.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Grunt configuration
 */

module.exports = function(grunt) {
    // Configuration de Grunt.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Configuration de la tâche "sass".
        sass: {
            options: {
                implementation: require('sass-embedded'), // Utiliser "node-sass" comme implémentation Sass.
                style: 'compressed' // Utiliser le style compressé pour minifier le CSS.
            },
            dist: {
                files: {
                    './styles.css': 'scss/**/*.scss' // Chemin du fichier CSS généré à partir des fichiers SCSS.
                }
            }
        },

        // Configuration for the uglify task.
        uglify: {
            options: {
                mangle: {
                    keep_fnames: true,
                },
                banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            build: {
                files: [{
                    expand: true,
                    cwd: 'amd/src/',
                    src: ['*.js', '!*.min.js'],
                    dest: 'amd/build/',
                    ext: '.min.js'
                }]
            }
        },

        terser: {
            options: {
                mangle: {
                    keep_classnames: true,
                    keep_fnames: true
                }
            },
            my_target: {
                files: [{
                    expand: true,
                    cwd: 'amd/src/',
                    src: ['*.js', '!*.min.js'],
                    dest: 'amd/build/',
                    ext: '.min.js'
                }]
            }
        },

        // Watch task configuration.
        watch: {
            scripts: {
                files: ['amd/src/*.js'], // Source JavaScript files to watch.
                tasks: ['terser'], // Task to run on file changes.
                options: {
                    spawn: false
                }
            }
        }
    });

    // Charger les plugins Grunt.
    grunt.loadNpmTasks('grunt-terser');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Définir la tâche par défaut.
    grunt.registerTask('default', ['sass']);
    grunt.registerTask('js', ['uglify', 'watch']);
};
