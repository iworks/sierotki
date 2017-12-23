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

		// BUILD patterns to exclude code for specific builds.
		replaces: {
			patterns: [
				{ match: /BUILDTIME/g, replace: buildtime },
				{ match: /IWORKS_OPTIONS_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
				{ match: /IWORKS_RATE_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
				{ match: /PLUGIN_VERSION/g, replace: '<%= pkg.version %>' }
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
				dest: './release/<%= pkg.version %>/'
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
			pot_dir: 'languages/', // With trailing slash.
			textdomain: 'sierotki',
		},

		dir: 'sierotki/'
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
				'assets/js/src/**/*.js',
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
					cwd: 'assets/js/',
					dest: 'assets/js/',
					ext: '.js',
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
					cwd: 'assets/css/',
					dest: 'assets/css/',
					ext: '.css',
					extDot: 'last',
					flatten: false
				}]
			}
		},

		concat_css: {
			options: {
				// Task-specific options go here. 
			},
			all: {
				src: [
					"assets/css/*.css",
					"!assets/css/style.css"
				],
				dest: "assets/css/style.css"
			},
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
				banner: '/*!\n'+
					'Theme Name: <%= pkg.title %>\n' +
					'Theme URI: http://int505.pl/\n' +
					'Author: Marcin Pietrzak\n' +
					'Author URI: http://iworks.pl/\n' +
					'Description: <%= pkg.description %>\n' +
					'Version: <%= pkg.version %>\n' +
					'License: GNU General Public License v2 or later\n' +
					'Text Domain: <%= pkg.name %>\n' +
					' */\n'
			},
			minify: {
				expand: true,
				src: 'style.css',
				cwd: 'assets/css/',
				dest: '',
				ext: '.css',
				extDot: 'last'
			}
		},


		// WATCH - Watch filesystem for changes during development.
		watch:  {
			sass: {
				files: ['assets/sass/**/*.scss'],
				tasks: ['css' ],
				options: {
					debounceDelay: 500
				}
			},

			scripts: {
				files: ['assets/js/src/**/*.js', 'assets/js/vendor/**/*.js'],
				//tasks: ['jshint', 'concat', 'uglify' ],
				tasks: [ 'concat', 'uglify' ],
				options: {
					debounceDelay: 500
				}
			}
		},

		// BUILD - Create a zip-version of the plugin.
		compress: {
			target: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: './release/<%= pkg.version %>/',
				src: [ '**/*' ]
			}
		},

		// BUILD - update the translation index .po file.
		makepot: {
			target: {
				options: {
					cwd: '',
					domainPath: conf.translation.pot_dir,
					exclude: conf.translation.ignore_files,
					mainFile: 'style.css',
					potComments: '',
					potFilename: conf.translation.textdomain + '.pot',
					potHeaders: {
						poedit: true, // Includes common Poedit headers.
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					processPot: null, // A callback function for manipulating the POT file.
					type: 'wp-theme', // wp-plugin or wp-theme
					updateTimestamp: true, // Whether the POT-Creation-Date should be updated without other changes.
					updatePoFiles: true // Whether to update PO files in the same directory as the POT file.
				}
			}
		},

		po2mo: {
			files: {
				src: 'languages/sierotki-pl_PL.po',
				dest: 'languages/sierotki-pl_PL.mo'
			},
		},

		// BUILD: Replace conditional tags in code.
		replace: {
			target: {
				options: {
					patterns: conf.replaces.patterns
				},
				files: [conf.replaces.files]
			}
		},

		clean: {
			options: { force: true },
			release: {
				options: { force: true },
				src: ['./release', './release/*', './release/**']
			}
		},

		copy: {
			release: {
				expand: true,
				src: [
					'*',
					'**',
					'!node_modules',
					'!node_modules/*',
					'!node_modules/**',
					'!bitbucket-pipelines.yml',
					'!.idea', // PHPStorm settings
					'!.git',
					'!Gruntfile.js',
					'!package.json',
					'!tests/*',
					'!tests/**',
					'!assets/js/src',
					'!assets/js/src/*',
					'!assets/js/src/**',
					'!assets/sass',
					'!assets/sass/*',
					'!assets/sass/**',
					'!phpcs.xml.dist',
					'!README.md'
				],
				dest: './release/<%= pkg.version %>/',
				noEmpty: true
			},
		}

	} );

	// Test task.
	grunt.registerTask( 'hello', 'Test if grunt is working', function() {
		grunt.log.subhead( 'Hi there :)' );
		grunt.log.writeln( 'Looks like grunt is installed!' );
	});

	grunt.registerTask( 'release', 'Generating release copy', function() {
		grunt.task.run( 'clean');
		grunt.task.run( 'js');
		grunt.task.run( 'css');
		grunt.task.run( 'makepot');
		grunt.task.run( 'po2mo');
		grunt.task.run( 'copy' );
		grunt.task.run( 'replace' );
		grunt.task.run( 'compress' );
	});

	// Default task.

	grunt.registerTask( 'default', ['clean:temp', 'jshint', 'concat', 'uglify', 'sass', 'autoprefixer', 'concat_css', 'cssmin'] );
	grunt.registerTask( 'js', [ 'concat', 'uglify'] );
	grunt.registerTask( 'css', ['sass', 'autoprefixer', 'concat_css', 'cssmin'] );
	//grunt.registerTask( 'test', ['phpunit', 'jshint'] );

	grunt.task.run( 'clear' );
	grunt.util.linefeed = '\n';
};
