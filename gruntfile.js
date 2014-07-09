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
          'css/wpp.admin.css': [ 'css/src/wpp.admin.less' ],
          'css/wpp.admin.data.tables.css': [ 'css/src/wpp.admin.data.tables.less' ],
          'css/wpp.admin.jquery.ui.css': [ 'css/src/wpp.admin.jquery.ui.less' ],
          
          'templates/wp_properties.css': [ 'css/src/wp_properties.less' ],
          'templates/wp_properties-ie_7.css': [ 'css/src/wp_properties-ie_7.less' ],
          'templates/wp_properties-msie.css': [ 'css/src/wp_properties-msie.less' ],
          
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
          'css/wpp.admin.dev.css': [ 'css/src/wpp.admin.less' ],
          'css/wpp.admin.data.tables.dev.css': [ 'css/src/wpp.admin.data.tables.less' ],
          'css/wpp.admin.jquery.ui.dev.css': [ 'css/src/wpp.admin.jquery.ui.less' ],
          
          'templates/wp_properties.dev.css': [ 'css/src/wp_properties.less' ],
          'templates/wp_properties-ie_7.dev.css': [ 'css/src/wp_properties-ie_7.less' ],
          'templates/wp_properties-msie.dev.css': [ 'css/src/wp_properties-msie.less' ],
          
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
          extension: '.js,.php',
          outdir: 'static/codex/',
          "paths": [
            "./core",
            "./js"
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
        files: [ 'css/src/*.less' ],
        tasks: [ 'less:production' ]
      },
      js: {
        files: [ 'css/src/*' ],
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
        files: [
          {
            expand: true,
            cwd: 'js/src',
            src: [ '*.js' ],
            dest: 'js'
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
          'js/*.*',
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