/**
 * Build Plugin.
 *
 * @author potanin@UD
 * @version 1.2.1
 * @param grunt
 */
module.exports = function( grunt ) {

  // Require Utility Modules.
  var joinPath      = require( 'path' ).join;
  var resolvePath   = require( 'path' ).resolve;
  var findup        = require( 'findup-sync' );

  // Determine Paths.
  var _paths = {
    composer: findup( 'composer.json' ),
    package: findup( 'package.json' ),
    vendor: findup( 'vendor' ),
    languages: findup( 'static/languages' ),
    codex: findup( 'static/codex' ),
    styles: findup( 'static/styles' ),
    scripts: findup( 'static/scripts' ),
    phpTests: findup( 'static/test/php' ),
    jsTests: findup( 'static/test/js' )
  };

  // Build Configuration.
  grunt.initConfig({

    // Ready Composer Meta.
    meta: grunt.file.readJSON( 'composer.json' ),

    // Get Package.
    settings: grunt.file.readJSON( 'composer.json' ).extra,

    // Locale.
    pot: {
      options:{
        package_name: '<%= settings.name %>',
        package_version: '<%= settings.version %>',
        text_domain: '<%= settings.name %>',
        dest: 'static/languages/',
        keywords: [ 'gettext', 'ngettext:1,2' ]
      },
      files:{
        src:  [ 'lib/*.php' ],
        expand: true
      }
    },

    // Compile Core and Template Styles.
    less: {
      production: {
        options: {
          yuicompress: true,
          relativeUrls: true
        },
        files: {
          'static/styles/wpp.admin.css': [ 'static/styles/src/wpp.admin.less' ],
          'static/styles/wpp.admin.jquery.ui.css': [ 'static/styles/src/wpp.admin.jquery.ui.less' ],
          
          'static/styles/wp_properties.css': [ 'static/styles/src/wp_properties.less' ],
          'static/styles/wp_properties-ie_7.css': [ 'static/styles/src/wp_properties-ie_7.less' ],
          'static/styles/wp_properties-msie.css': [ 'static/styles/src/wp_properties-msie.less' ],
          
          'static/styles/theme-specific/denali.css': [ 'templates/theme-specific/src/denali.less' ],
          'static/styles/theme-specific/fb_properties.css': [ 'templates/theme-specific/src/fb_properties.less' ],
          'static/styles/theme-specific/twentyeleven.css': [ 'templates/theme-specific/src/twentyeleven.less' ],
          'static/styles/theme-specific/twentyten.css': [ 'templates/theme-specific/src/twentyten.less' ],
          'static/styles/theme-specific/twentytwelve.css': [ 'templates/theme-specific/src/twentytwelve.less' ]
        }
      },
      development: {
        options: {
          yuicompress: false,
          relativeUrls: true
        },
        files: {
          'static/styles/wpp.admin.dev.css': [ 'static/styles/src/wpp.admin.less' ],
          'static/styles/wpp.admin.data.tables.dev.css': [ 'static/styles/src/wpp.admin.data.tables.less' ],
          'static/styles/wpp.admin.jquery.ui.dev.css': [ 'static/styles/src/wpp.admin.jquery.ui.less' ],
          
          'static/styles/wp_properties.dev.css': [ 'static/styles/src/wp_properties.less' ],
          'static/styles/wp_properties-ie_7.dev.css': [ 'static/styles/src/wp_properties-ie_7.less' ],
          'static/styles/wp_properties-msie.dev.css': [ 'static/styles/src/wp_properties-msie.less' ],
          
          'static/styles/theme-specific/denali.dev.css': [ 'templates/theme-specific/src/denali.less' ],
          'static/styles/theme-specific/fb_properties.dev.css': [ 'templates/theme-specific/src/fb_properties.less' ],
          'static/styles/theme-specific/twentyeleven.dev.css': [ 'templates/theme-specific/src/twentyeleven.less' ],
          'static/styles/theme-specific/twentyten.dev.css': [ 'templates/theme-specific/src/twentyten.less' ],
          'static/styles/theme-specific/twentytwelve.dev.css': [ 'templates/theme-specific/src/twentytwelve.less' ]
        }
      }
    },

    // Generate YUIDoc documentation.
    yuidoc: {
      compile: {
        name: '<%= settings.name %>',
        description: '<%= settings.description %>',
        version: '<%= settings.version %>',
        url: '<%= settings.homepage %>',
        options: {
          extension: '.js,.php',
          outdir: 'static/codex/',
          "paths": [
            "./lib",
            "./static/scripts"
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
        files: [ 'static/styles/src/*.less' ],
        tasks: [ 'less' ]
      },
      scripts: {
        files: [ 'static/scripts/src/*' ],
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
            expand: true,
            cwd: 'static/scripts/src',
            src: [ '*.js' ],
            dest: 'static/scripts',
            rename: function renameScript( dest, src ) {
              return joinPath( dest, src.replace( '.js', '.js' ) );
            }
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
            cwd: 'static/scripts/src',
            src: [ '*.js' ],
            dest: 'static/scripts',
            rename: function renameScript( dest, src ) {
              return joinPath( dest, src.replace( '.js', '.dev.js' ) );
            }
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
  grunt.loadNpmTasks( 'grunt-pot' );

  // Register NPM Tasks.
  grunt.registerTask( 'default', [ 'markdown', 'less', 'yuidoc', 'uglify' ] );

  // Install Library.
  grunt.registerTask( 'install', [ 'markdown', 'less', 'yuidoc', 'uglify' ] );

  // Prepare for Distribution.
  grunt.registerTask( 'make-distribution', [ 'markdown', 'less', 'yuidoc', 'uglify' ] );

  // Prepare and Push to Git.
  grunt.registerTask( 'commit', [ 'clean:temp', 'markdown', 'less', 'yuidoc', 'uglify' ] );

  // Prepare and Push to Git master.
  grunt.registerTask( 'commit-master', [ 'clean:temp', 'markdown', 'less', 'yuidoc', 'uglify' ] );

  // Development Mode.
  grunt.registerTask( 'dev', [ 'symlink:dev', 'watch' ] );

};