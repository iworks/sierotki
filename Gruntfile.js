/*global require*/

/**
 * When grunt command does not execute try these steps:
 *
 * - delete folder 'node_modules' and run command in console:
 *   $ npm install
 *
 * - Run test-command in console, to find syntax errors in script:
 *   $ grunt hello
 */

module.exports = function(grunt) {

	// Load all grunt tasks.
	require('load-grunt-tasks')(grunt);

	var buildtime = new Date().toISOString();
	var buildyear = 1900 + new Date().getYear();

	/**
	 * excludes
	 */
	var excludeCopyFiles = [
		'**',
		'!**/bitbucket-pipelines.yml',
		'!contributing.md',
		'!**/css/less/**',
		'!**/css/sass/**',
		'!**/css/src/**',
		'!.editorconfig',
		'!.git*',
		'!.git/**',
		'!**/Gruntfile.js',
		'!**/img/src/**',
		'!**/js/src/**',
		'!**/LICENSE',
		'!LICENSE',
		'!**/*.map',
		'!node_modules/**',
		'!**/package.json',
		'!package-lock.json',
		'!postcss.config.js',
		'!**/README.md',
		'!README.md',
		'!release/**',
		'!.sass-cache/**',
		'!**/tests/**',
		'!webpack.config.js',
	];

	var excludeCopyFilesGIT = excludeCopyFiles.slice(0).concat(
		[
			'!includes/pro/**',
			'!readme.txt',
		]
	);

	var excludeCopyFilesWPorg = excludeCopyFiles.slice(0).concat(
		[
			'!assets/sass/**',
			'!assets/scripts/src/**',
			'!assets/scss/**',
			'!assets/styles/frontend/**',
			'!includes/iworks/orphans/class-iworks-orphans-github.php',
			'!includes/pro/**',
			'!languages/*.mo',
			'!languages/*.po',
		]
	);

	var conf = {
		// Concatenate those JS files into a single file (target: [source, source, ...]).

		replace_patterns: [{
			match: /AUTHOR_NAME/g,
			replace: '<%= pkg.author[0].name %>'
		}, {
			match: /AUTHOR_URI/g,
			replace: '<%= pkg.author[0].uri %>'
		}, {
			match: /BUILDTIME/g,
			replace: buildtime
		}, {
			match: /IWORKS_RATE_TEXTDOMAIN/g,
			replace: '<%= pkg.name %>'
		}, {
			match: /IWORKS_OPTIONS_TEXTDOMAIN/g,
			replace: '<%= pkg.name %>'
		}, {
			match: /PLUGIN_DESCRIPTION/g,
			replace: '<%= pkg.description %>'
		}, {
			match: /PLUGIN_GITHUB_WEBSITE/g,
			replace: '<%= pkg.repository.website %>'
		}, {
			match: /PLUGIN_NAME/g,
			replace: '<%= pkg.name %>'
		}, {
			match: /PLUGIN_REQUIRES_PHP/g,
			replace: '<%= pkg.requires.PHP %>'
		}, {
			match: /PLUGIN_REQUIRES_WORDPRESS/g,
			replace: '<%= pkg.requires.WordPress %>'
		}, {
			match: /PLUGIN_TESTED_WORDPRESS/g,
			replace: '<%= pkg.tested.WordPress %>'
		}, {
			match: /PLUGIN_TAGLINE/g,
			replace: '<%= pkg.tagline %>'
		}, {
			match: /PLUGIN_TAGS/g,
			replace: '<%= pkg.tags.join(", ") %>'
		}, {
			match: /PLUGIN_TILL_YEAR/g,
			replace: buildyear
		}, {
			match: /PLUGIN_TITLE/g,
			replace: '<%= pkg.title %>'
		}, {
			match: /PLUGIN_URI/g,
			replace: '<%= pkg.homepage %>'
		}, {
			match: /PLUGIN_VERSION/g,
			replace: '<%= pkg.version %>'
		}, {
			match: /^Version: .+$/g,
			replace: 'Version: <%= pkg.version %>'
		}],

		plugin_dir: '',
		plugin_file: '<%= pkg.name %>.php',

		// Regex patterns to exclude from transation.
		translation: {
			ignore_files: [
				'README.md',
				'.git*',
				'vendor/.*', // External libraries.
				'includes/external/.*', // External libraries.
				'node_modules/.*',
				'(^.php)', // Ignore non-php files.
				'release/.*', // Temp release files.
				'.sass-cache/.*',
				'tests/.*', // Unit testing.
				'.editorconfig', // editor configuration
			],
			pot_dir: 'languages/', // With trailing slash.
			textdomain: '<%= pkg.name %>',
		},
	};

	// Project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		// BUILD - Remove previous build version and temp files.
		clean: {
			wporg: {
				src: [
					'release/wporg/<%= pkg.version %>',
					'release/wporg/<%= pkg.name %>.zip'
				]
			},
			github: {
				src: [
					'release/github/<%= pkg.version %>',
					'release/github/<%= pkg.name %>.zip'
				]
			},
			temp: {
				src: ['**/*.tmp', '**/.afpDeleted*', '**/.DS_Store'],
				dot: true,
				filter: 'isFile'
			}
		},

		// BUILD - update the translation index .po file.
		makepot: {
			target: {
				options: {
					domainPath: conf.translation.pot_dir,
					exclude: conf.translation.ignore_files,
					mainFile: conf.plugin_file,
					potFilename: conf.translation.textdomain + '.pot',
					potHeaders: {
						poedit: true, // Includes common Poedit headers.
						'project-id-version': '<%= pkg.version %>',
						'language-team': 'iWorks <support@iworks.pl>',
						'last-translator': '<%= pkg.translator.name %> <<%= pkg.translator.email %>>',
						'report-msgid-bugs-to': 'http://iworks.pl',
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					exclude: ['node_modules', '.git', '.sass-cache', 'release'],
					type: 'wp-plugin',
					updateTimestamp: true,
					updatePoFiles: true
				}
			}
		},

		potomo: {
			dist: {
				options: {
					poDel: false
				},
				files: [{
					expand: true,
					cwd: conf.translation.pot_dir,
					src: ['*.po'],
					dest: conf.translation.pot_dir,
					ext: '.mo',
					nonull: true
				}]
			}
		},

		copy: {
			// Copy the plugin to a versioned release directory
			wporg: {
				src: excludeCopyFilesWPorg,
				dest: 'release/wporg/<%= pkg.version %>/<%= pkg.name %>/'
			},
			github: {
				src: excludeCopyFilesGIT,
				dest: 'release/github/<%= pkg.version %>/<%= pkg.name %>/'
			}
		},

		// BUILD: Replace conditional tags in code.
		replace: {
			options: {
				patterns: conf.replace_patterns
			},
			files: {
				expand: true,
				src: [
					'release/*/<%= pkg.version %>/<%= pkg.name %>/**',
					'!release/*/**/*.gif',
					'!release/*/**/images/**',
					'!release/*/**/*.jpg',
					'!release/*/**/languages/*.mo',
					'!release/*/**/*.png',
					'!release/*/**/*.webp',
				],
				dest: '.'
			}
		},

		compress: {
			wporg: {
				options: {
					mode: 'zip',
					archive: './release/wporg/<%= pkg.name %>.zip'
				},
				expand: true,
				cwd: 'release/wporg/<%= pkg.version %>/',
				src: ['**/*'],
				dest: '.'
			},
			github: {
				options: {
					mode: 'zip',
					archive: './release/github/<%= pkg.name %>.zip'
				},
				expand: true,
				cwd: 'release/github/<%= pkg.version %>/',
				src: ['**/*'],
				dest: '.'
			}
		},

		checktextdomain: {
			options: {
				text_domain: ['<%= pkg.name %>', 'IWORKS_RATE_TEXTDOMAIN', 'IWORKS_OPTIONS_TEXTDOMAIN'],
				keywords: [ //List keyword specifications
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: ['<%= pkg.name %>.php', 'includes/**/*.php'],
				expand: true,
			},
		},
	});

	grunt.registerTask('notes', 'Show release notes', function() {
		grunt.log.subhead('Release notes');
		grunt.log.writeln('  1. Check FORUM for open threads');
		grunt.log.writeln('  2. REPLY to forum threads + unsubscribe');
		grunt.log.writeln('  3. Update the TRANSLATION files');
		grunt.log.writeln('  4. Generate ARCHIVE');
		grunt.log.writeln('  5. Check ARCHIVE structure - it should be a folder with plugin name');
		grunt.log.writeln('  6. INSTALL on a clean WordPress installation');
		grunt.log.writeln('  7. RELEASE the plugin on WordPress.org!');
		grunt.log.writeln('  8. Add git tag!');
		grunt.log.writeln('  9. RELEASE the plugin on GitHub!');
		grunt.log.writeln(' 10. RELEASE the plugin!');
	});

	// Default task.

	grunt.registerTask('default', ['clean:temp']);
	grunt.registerTask('i18n', ['checktextdomain', 'makepot']);

	grunt.registerTask(
		'build',
		[
			'default',
			'i18n',
			'clean:wporg',
			'copy:wporg',
			'replace',
			'compress:wporg'
		],
	);
	grunt.registerTask(
		'build:github',
		[
			'default',
			'i18n',
			'potomo',
			'clean:github',
			'copy:github',
			'replace',
			'compress:github'
		]
	);
	grunt.registerTask('release', ['build:wporg', 'build:github', 'notes' ]);
	grunt.registerTask('test', ['phpunit', 'jshint', 'notes']);
	grunt.util.linefeed = '\n';
};
