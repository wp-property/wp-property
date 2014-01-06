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

    // Compile LESS in app.css
    less: {
      production: {
        options: {
          yuicompress: true,
          relativeUrls: true
        },
        files: [
          {
            expand: true,
            cwd: 'css/src',
            src: [ '*.less' ],
            dest: 'css'
          }
        ]
      },
      development: {
        options: {
          relativeUrls: true
        },
        files: [
          {
            expand: true,
            cwd: 'css/src',
            src: [ '*.less' ],
            dest: 'css'
          }
        ]
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
          paths: 'core',
          outdir: 'static/codex/'
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
        files: [ 'css/src/*.less' ],
        tasks: [ 'less' ]
      },
      js: {
        files: [ 'js/*' ],
        tasks: [ 'uglify' ]
      }
    },

    // Minify and Move Core Scripts.
    uglify: {
      production: {
        options: {
          mangle: false,
          beautify: false
        },
        files: [
          {
            expand: true,
            cwd: 'js/src',
            src: [ '*.js' ],
            dest: 'js'
          }
        ]
      },
      development: {
        options: {
          mangle: false,
          beautify: true
        },
        files: {
          //'application/scripts/app.dev.js': [ 'application/scripts/src/app.js' ],
          //'application/scripts/network.dev.js': [ 'application/scripts/src/network.js' ]
        }
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
      all: [
        "vendor",
        "composer.lock"
      ]
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
  grunt.loadNpmTasks( 'grunt-shell' );

  // Register NPM Tasks.
  grunt.registerTask( 'default', [ 'markdown', 'less:production', 'yuidoc', 'uglify:production' ] );

  // Development Mode.
  grunt.registerTask( 'dev', [ 'symlink:dev', 'watch' ] );

};