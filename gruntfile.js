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
          'styles/wpp-data-tables.css': [ 'styles/src/wpp-data-tables.less' ],
          'styles/wp_properties_admin.css': [ 'styles/src/wp_properties_admin.less' ],
          'styles/jquery-ui.css': [ 'styles/src/jquery-ui.less' ],
          'templates/wp_properties.css': [ 'styles/src/wp_properties.less' ],
          'templates/wp_properties-ie_7.css': [ 'styles/src/wp_properties-ie_7.less' ],
          'templates/wp_properties-msie.css': [ 'styles/src/wp_properties-msie.less' ],
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
          'styles/wpp-data-tables.dev.css': [ 'styles/src/wpp-data-tables.less' ],
          'styles/wp_properties_admin.dev.css': [ 'styles/src/wp_properties_admin.less' ],
          'styles/jquery-ui.dev.css': [ 'styles/src/jquery-ui.less' ],
          'templates/wp_properties.dev.css': [ 'styles/src/wp_properties.less' ],
          'templates/wp_properties-ie_7.dev.css': [ 'styles/src/wp_properties-ie_7.less' ],
          'templates/wp_properties-msie.dev.css': [ 'styles/src/wp_properties-msie.less' ],
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
        tasks: [ 'less' ]
      },
      js: {
        files: [ 'scripts/*' ],
        tasks: [ 'uglify' ]
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
            'templates/wp_properties.js': [ 'scripts/src/wp_properties.js' ]
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
            'templates/wp_properties.dev.js': [ 'scripts/src/wp_properties.js' ]
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

  // Prepare for Distribution.
  grunt.registerTask( 'make-distribution', [ 'markdown', 'less', 'yuidoc', 'uglify' ] );

  // Prepare and Push to Git.
  grunt.registerTask( 'push', [ 'clean:temp', 'markdown', 'less', 'yuidoc', 'uglify', 'gitcommit', 'gitpush:development'  ] );

  // Prepare and Push to Git master.
  grunt.registerTask( 'push-master', [ 'clean:temp', 'markdown', 'less', 'yuidoc', 'uglify', 'gitcommit', 'gitpush:master'  ] );

  // Development Mode.
  grunt.registerTask( 'dev', [ 'symlink:dev', 'watch' ] );

};