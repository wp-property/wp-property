/**
 * Build Plugin.
 *
 * @author potanin@UD
 * @version 1.2.1
 * @param grunt
 */
module.exports = function( grunt ) {

  // Build Configuration.
  grunt.initConfig({

    // Get Package.
    pkg: grunt.file.readJSON( 'composer.json' ),

    // Compile Core and Template Styles.
    less: {
      production: {
        options: {
          yuicompress: true,
          relativeUrls: true
        },
        files: {
          'styles/wpp.admin.data.tables.css': [ 'styles/src/wpp.admin.data.tables.less' ],
          'styles/wpp.admin.css': [ 'styles/src/wpp.admin.less' ],
          'styles/wpp.jquery.ui.css': [ 'styles/src/wpp.jquery.ui.less' ],
          'templates/wpp.css': [ 'styles/src/wpp.less' ],
          'templates/wpp.msie.css': [ 'styles/src/wpp.msie.less' ],
          'templates/wpp.msie.7.css': [ 'styles/src/wpp.msie.7.less' ],
          'templates/theme-specific/denali.css': [ 'templates/theme-specific/src/denali.less' ],
          'templates/theme-specific/fb_properties.css': [ 'templates/theme-specific/src/fb_properties.less' ],
          'templates/theme-specific/twentyeleven.css': [ 'templates/theme-specific/src/twentyeleven.less' ],
          'templates/theme-specific/twentyten.css': [ 'templates/theme-specific/src/twentyten.less' ],
          'templates/theme-specific/twentytwelve.css': [ 'templates/theme-specific/src/twentytwelve.less' ]
        }
      },
      development: {
        options: {
          yuicompress: false,
          relativeUrls: true
        },
        files: {
          'styles/wpp.admin.data.tables.dev.css': [ 'styles/src/wpp.admin.data.tables.less' ],
          'styles/wpp.admin.dev.css': [ 'styles/src/wpp.admin.less' ],
          'styles/wpp.jquery.ui.dev.css': [ 'styles/src/wpp.jquery.ui.less' ],
          'templates/wpp.css': [ 'styles/src/wpp.less' ],
          'templates/wpp.msie.css': [ 'styles/src/wpp.msie.less' ],
          'templates/wpp.msie.7.css': [ 'styles/src/wpp.msie.7.less' ],
          'templates/theme-specific/denali.dev.css': [ 'templates/theme-specific/src/denali.less' ],
          'templates/theme-specific/fb_properties.dev.css': [ 'templates/theme-specific/src/fb_properties.less' ],
          'templates/theme-specific/twentyeleven.dev.css': [ 'templates/theme-specific/src/twentyeleven.less' ],
          'templates/theme-specific/twentyten.dev.css': [ 'templates/theme-specific/src/twentyten.less' ],
          'templates/theme-specific/twentytwelve.dev.css': [ 'templates/theme-specific/src/twentytwelve.less' ]
        }
      }
    },

    // Generate YUIDoc documentation.
    yuidoc: {
      compile: {
        name: '<%= pkg.name %>',
        description: '<%= pkg.description %>',
        version: '<%= pkg.version %>',
        url: '<%= pkg.homepage %>',
        options: {
          extension: '.scripts,.php',
          outdir: 'static/codex/',
          "paths": [
            "./lib",
            "./scripts"
          ]
        }
      }
    },

    // Watch for Development.
    watch: {
      options: {
        interval: 100,
        debounceDelay: 500
      },
      less: {
        files: [ 'styles/src/*.less' ],
        tasks: [ 'less:production' ]
      },
      js: {
        files: [ 'scripts/src/*' ],
        tasks: [ 'uglify:production' ]
      }
    },

    // Minify Core and Template Scripts.
    uglify: {
      production: {
        options: {
          mangle: false,
          beautify: false
        },
        files: [
          {
            'templates/wpp.js': [ 'scripts/src/wpp.js' ]
          },
          {
            expand: true,
            cwd: 'scripts/src',
            src: [ '*.js' ],
            dest: 'scripts'
          }
        ]
      },
      development: {
        options: {
          mangle: false,
          beautify: true
        },
        files: [
          {
            'templates/wpp.dev.js': [ 'scripts/src/wpp.js' ]
          },
          {
            expand: true,
            cwd: 'scripts/src',
            src: [ '*.js' ],
            dest: 'scripts'
          }
        ]
      }
    },

    // Generate Markdown Documentation.
    markdown: {
      all: {
        files: [
          {
            expand: true,
            src: 'readme.md',
            dest: 'static/',
            ext: '.html'
          }
        ],
        options: {
          markdownOptions: {
            gfm: true,
            codeLines: {
              before: '<span>',
              after: '</span>'
            }
          }
        }
      }
    },

    // Clean Directories.
    clean: {
      temp: [
        "cache"
      ],
      all: [
        "cache",
        "vendor",
        "composer.lock"
      ]
    },

    // Commit to Git.
    gitcommit: {
      options: {
        message: 'Automatic push.',
        ignoreEmpty: true
      },
      files: {
        src: [
          'images/*.*',
          'languages/*.*',
          'lib/*.*',
          'scripts/*.*',
          'static/*.*',
          'static/codex/*.*',
          'static/codex/files/*.*',
          'styles/*.*',
          'templates/*.*',
          '*.*'
        ]
      }
    },

    // Pust to Git.
    gitpush: {
      development: {
        options: {
          branch: 'development'
        }
      },
      master: {
        options: {
          branch: 'master'
        }
      }
    },

    // Execute Shell Commands.
    shell: {
      install: {
        command: 'composer install',
        options: {
          stdout: true
        }
      },
      update: {
        command: 'composer update',
        options: {
          stdout: true
        }
      }
    }

  });

  // Load NPM Tasks.
  grunt.loadNpmTasks( 'grunt-contrib-symlink' );
  grunt.loadNpmTasks( 'grunt-contrib-yuidoc' );
  grunt.loadNpmTasks( 'grunt-contrib-uglify' );
  grunt.loadNpmTasks( 'grunt-contrib-watch' );
  grunt.loadNpmTasks( 'grunt-contrib-less' );
  grunt.loadNpmTasks( 'grunt-contrib-concat' );
  grunt.loadNpmTasks( 'grunt-contrib-clean' );
  grunt.loadNpmTasks( 'grunt-markdown' );
  grunt.loadNpmTasks( 'grunt-git' );
  grunt.loadNpmTasks( 'grunt-shell' );

  // Register NPM Tasks.
  grunt.registerTask( 'default', [ 'markdown', 'less:production', 'yuidoc', 'uglify:production' ] );

  // Install Library.
  grunt.registerTask( 'install', [ 'markdown', 'less:production', 'yuidoc', 'uglify:production' ] );

  // Prepare for Distribution.
  grunt.registerTask( 'make-distribution', [ 'markdown', 'less:production', 'yuidoc', 'uglify:production' ] );

  // Prepare and Push to Git.
  grunt.registerTask( 'commit', [ 'clean:temp', 'markdown', 'less:production', 'yuidoc', 'uglify:production', 'gitcommit', 'gitpush:development'  ] );

  // Prepare and Push to Git master.
  grunt.registerTask( 'commit-master', [ 'clean:temp', 'markdown', 'less:production', 'yuidoc', 'uglify:production', 'gitcommit', 'gitpush:master'  ] );

  // Development Mode.
  grunt.registerTask( 'dev', [ 'symlink:dev', 'watch' ] );

};