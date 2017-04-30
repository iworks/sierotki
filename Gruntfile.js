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

module.exports = function( grunt ) {
	// Show elapsed time at the end.
	require( 'time-grunt' )(grunt);

	// Load all grunt tasks.
	require( 'load-grunt-tasks' )(grunt);

	var buildtime = new Date().toISOString();

	var conf = {

		// Concatenate those JS files into a single file (target: [source, source, ...]).
		js_files_concat: {
		},

		// SASS files to process. Resulting CSS files will be minified as well.
		css_files_compile: {
		},

		// BUILD branches.
		plugin_branches: {
			exclude_pro: [
				'./README.MD',
				'./README.md',
				'./readme.txt',
				'./Gruntfile.js',
				'./package.json',
				'./vendor/iworks/rate/README.md',
			],
			exclude_free: [
				'./changelog.txt',
				'./README.MD',
				'./README.md',
				'./Gruntfile.js',
				'./package.json',
				'./vendor/iworks/rate/README.md',
			],
			include_files: [
				'**',
				'!css/src/**',
				'!js/src/**',
				'!js/vendor/**',
				'!img/src/**',
				'!node_modules/**',
				'!build/**',
				'!tests/**',
				'!**/css/src/**',
				'!**/css/sass/**',
				'!**/js/src/**',
				'!**/js/vendor/**',
				'!**/img/src/**',
				'!**/node_modules/**',
				'!**/**.log',
				'!**/tests/**',
				'!**/release/*.zip',
				'!release/*.zip',
				'!**/release/**',
				'!release/**',
				'!**/Gruntfile.js',
				'!**/package.json',
				'!**/build/**',
				'!.sass-cache/**',
				'!.git/**',
				'!.git',
				'!.log',
			],
			base: 'master',
			pro: 'sierotki-pro',
			free: 'sierotki-free',
		},

		// BUILD patterns to exclude code for specific builds.
		plugin_patterns: {
			pro: [
				{ match: /PLUGIN_VERSION/g, replace: '<%= pkg.version %>' },
				{ match: /BUILDTIME/g, replace: buildtime },
				{ match: /IWORKS_RATE_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
				{ match: /\/\* start:pro \*\//g, replace: '' },
				{ match: /\/\* end:pro \*\//g, replace: '' },
				{ match: /\/\* start:free \*[^]+?\* end:free \*\//mg, replace: '' },
			],
			free: [
				{ match: /PLUGIN_VERSION/g, replace: '<%= pkg.version %>' },
				{ match: /BUILDTIME/g, replace: buildtime },
				{ match: /IWORKS_RATE_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
				{ match: /\/\* start:free \*\//g, replace: '' },
				{ match: /\/\* end:free \*\//g, replace: '' },
				{ match: /\/\* start:pro \*[^]+?\* end:pro \*\//mg, replace: '' },
			],
			// Files to apply above patterns to (not only php files).
			files: {
				expand: true,
				src: [
					'**/*.php',
					'**/*.css',
					'**/*.js',
					'**/*.html',
					'**/*.txt',
					'!node_modules/**',
					'!lib/**',
					'!docs/**',
					'!release/**',
					'!Gruntfile.js',
					'!build/**',
					'!tests/**',
					'!.git/**'
				],
				dest: './'
			}
		},

		// Regex patterns to exclude from transation.
		translation: {
			ignore_files: [
				'node_modules/.*',
				'(^.php)',         // Ignore non-php files.
				'inc/external/.*', // External libraries.
				'release/.*',      // Temp release files.
				'tests/.*',        // Unit testing.
			],
			pot_dir: 'lang/', // With trailing slash.
			textdomain: 'custom-sidebars',
		},

		dev_plugin_file: 'sierotki.php',
		dev_plugin_dir: 'custom-sidebars/'
	};

	// Project configuration
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		// JS - Concat .js source files into a single .js file.
		concat: {
			options: {
				stripBanners: true,
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			scripts: {
				files: conf.js_files_concat
			}
		},


		// JS - Validate .js source code.
		jshint: {
			all: [
				'Gruntfile.js',
				'js/src/**/*.js',
			],
			options: {
				curly:   true,
				eqeqeq:  true,
				immed:   true,
				latedef: true,
				newcap:  true,
				noarg:   true,
				sub:     true,
				undef:   true,
				boss:    true,
				eqnull:  true,
				globals: {
					exports: true,
					module:  false
				}
			}
		},


		// JS - Uglyfies the source code of .js files (to make files smaller).
		uglify: {
			all: {
				files: [{
					expand: true,
					src: ['*.js', '!*.min.js'],
					cwd: 'js/',
					dest: 'js/',
					ext: '.min.js',
					extDot: 'last'
				}],
				options: {
					banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
						' * <%= pkg.homepage %>\n' +
						' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
						' * Licensed GPLv2+' +
						' */\n',
					mangle: {
						except: ['jQuery']
					}
				}
			}
		},


		// TEST - Run the PHPUnit tests.
		/* -- Not used right now...
		phpunit: {
			classes: {
				dir: ''
			},
			options: {
				bin: 'phpunit',
				bootstrap: 'tests/php/bootstrap.php',
				testsuite: 'default',
				configuration: 'tests/php/phpunit.xml',
				colors: true,
				//tap: true,
				//testdox: true,
				//stopOnError: true,
				staticBackup: false,
				noGlobalsBackup: false
			}
		},
		*/


		// CSS - Compile a .scss file into a normal .css file.
		sass:   {
			all: {
				options: {
					'sourcemap=none': true, // 'sourcemap': 'none' does not work...
					unixNewlines: true,
					style: 'expanded'
				},
				files: conf.css_files_compile
			}
		},


		// CSS - Automaticaly create prefixed attributes in css file if needed.
		//       e.g. add `-webkit-border-radius` if `border-radius` is used.
		autoprefixer: {
			options: {
				browsers: ['last 2 version', 'ie 8', 'ie 9'],
				diff: false
			},
			single_file: {
				files: [{
					expand: true,
					src: ['**/*.css', '!**/*.min.css'],
					cwd: 'css/',
					dest: 'css/',
					ext: '.css',
					extDot: 'last',
					flatten: false
				}]
			}
		},


		// CSS - Required for CSS-autoprefixer and maybe some SCSS function.
		compass: {
			options: {
			},
			server: {
				options: {
					debugInfo: true
				}
			}
		},


		// CSS - Minify all .css files.
		cssmin: {
			options: {
				banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
					' * <%= pkg.homepage %>\n' +
					' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
					' * Licensed GPLv2+' +
					' */\n'
			},
			minify: {
				expand: true,
				src: ['*.css', '!*.min.css'],
				cwd: 'css/',
				dest: 'css/',
				ext: '.min.css',
				extDot: 'last'
			}
		},


		// WATCH - Watch filesystem for changes during development.
		watch:  {
			sass: {
				files: ['css/src/**/*.scss'],
				tasks: ['sass', 'autoprefixer'],
				options: {
					debounceDelay: 500
				}
			},

			scripts: {
				files: ['js/src/**/*.js', 'js/vendor/**/*.js'],
				tasks: ['jshint', 'concat'],
				options: {
					debounceDelay: 500
				}
			}
		},


		// BUILD - Remove previous build version and temp files.
		clean: {
			temp: {
				src: [
					'**/*.tmp',
					'**/.afpDeleted*',
					'**/.DS_Store',
				],
				dot: true,
				filter: 'isFile'
			},
			release_pro: {
				src: [
					'release/<%= pkg.version %>-pro/',
					'release/<%= pkg.name %>-pro-<%= pkg.version %>.zip',
				],
			},
			release_free: {
				src: [
					'release/<%= pkg.version %>-free/',
					'release/<%= pkg.name %>-free-<%= pkg.version %>.zip',
				],
			},
			pro: conf.plugin_branches.exclude_pro,
			free: conf.plugin_branches.exclude_free
		},


		// BUILD - Copy all plugin files to the release subdirectory.
		copy: {
			pro: {
				src: conf.plugin_branches.include_files,
				dest: 'release/<%= pkg.version %>-pro/'
			},
			free: {
				src: conf.plugin_branches.include_files,
				dest: 'release/<%= pkg.version %>-free/'
			},
		},


		// BUILD - Create a zip-version of the plugin.
		compress: {
			pro: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-pro-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>-pro/',
				src: [ '**/*' ],
				dest: conf.dev_plugin_dir
			},
			free: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-free-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>-free/',
				src: [ '**/*' ],
				dest: conf.dev_plugin_dir
			},
		},


		// BUILD - update the translation index .po file.
		makepot: {
			target: {
				options: {
					cwd: '',
					domainPath: conf.translation.pot_dir,
					exclude: conf.translation.ignore_files,
					mainFile: conf.dev_plugin_file,
					potFilename: conf.translation.textdomain + '.pot',
					potHeaders: {
						poedit: true, // Includes common Poedit headers.
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					type: 'wp-plugin' // wp-plugin or wp-theme
				}
			}
		},

		// BUILD: Replace conditional tags in code.
		replace: {
			pro: {
				options: {
					patterns: conf.plugin_patterns.pro
				},
				files: [conf.plugin_patterns.files]
			},
			free: {
				options: {
					patterns: conf.plugin_patterns.free
				},
				files: [conf.plugin_patterns.files]
			}
		},

		// BUILD: Git control (check out branch).
		gitcheckout: {
			pro: {
				options: {
					verbose: true,
					branch: conf.plugin_branches.pro,
					overwrite: true
				}
			},
			free: {
				options: {
					branch: conf.plugin_branches.free,
					overwrite: true
				}
			},
			base: {
				options: {
					branch: conf.plugin_branches.base
				}
			}
		},

		// BUILD: Git control (add files).
		gitadd: {
			pro: {
				options: {
				verbose: true, all: true }
			},
			free: {
				options: { all: true }
			},
		},

		// BUILD: Git control (commit changes).
		gitcommit: {
			pro: {
				verbose: true,
				options: {
					message: 'Built from: ' + conf.plugin_branches.base,
					allowEmpty: true
				},
				files: { src: ['.'] }
			},
			free: {
				options: {
					message: 'Built from: ' + conf.plugin_branches.base,
					allowEmpty: true
				},
				files: { src: ['.'] }
			},
		},

	} );

	// Test task.
	grunt.registerTask( 'hello', 'Test if grunt is working', function() {
		grunt.log.subhead( 'Hi there :)' );
		grunt.log.writeln( 'Looks like grunt is installed!' );
	});

	// Plugin build tasks
	grunt.registerTask( 'build', 'Run all tasks.', function(target) {
		var build = [], i, branch;

		if ( target ) {
			build.push( target );
		} else {
			build = ['pro', 'free'];
		}

		// First run unit tests.
		/* -- Not used right now...
		grunt.task.run( 'phpunit' );
		*/

		// Run the default tasks (js/css/php validation).
		grunt.task.run( 'default' );

		// Generate all translation files (same for pro and free).
		grunt.task.run( 'makepot' );

		for ( i in build ) {
			branch = build[i];
			grunt.log.subhead( 'Update product branch [' + branch + ']...' );

			// Checkout the destination branch.
			grunt.task.run( 'gitcheckout:' + branch );

			// Remove code and files that does not belong to this version.
			grunt.task.run( 'replace:' + branch );
			grunt.task.run( 'clean:' + branch );

			// Add the processes/cleaned files to the target branch.
			grunt.task.run( 'gitadd:' + branch );
			grunt.task.run( 'gitcommit:' + branch );

			// Create a distributable zip-file of the plugin branch.
			grunt.task.run( 'clean:release_' + branch );
			grunt.task.run( 'copy:' + branch );
			grunt.task.run( 'compress:' + branch );

			grunt.task.run( 'gitcheckout:base');
		}
	});

	// Default task.

	grunt.registerTask( 'default', ['clean:temp', 'jshint', 'concat', 'uglify', 'sass', 'autoprefixer', 'cssmin'] );
	//grunt.registerTask( 'test', ['phpunit', 'jshint'] );

	grunt.task.run( 'clear' );
	grunt.util.linefeed = '\n';
};
