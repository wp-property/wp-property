/**
 * Build Plugin.
 *
 * @author potanin@UD
 * @version 1.2.1
 * @param grunt
 */
module.exports = function( grunt ) {

  // Automatically Load Tasks.
  require( 'load-grunt-tasks' )( grunt, {
    pattern: 'grunt-*',
    config: './package.json',
    scope: 'devDependencies'
  });

  // Build Configuration.
  grunt.initConfig({

    // Get Package.
    package: grunt.file.readJSON( 'composer.json' ),

    // Compile Core and Template Styles.
    less: {
      production: {
        options: {
          yuicompress: true,
          relativeUrls: true
        },
        files: {
          'static/css/wpp.admin.css': [ 'static/css/src/wpp.admin.less' ],
          'static/css/wpp.admin.data.tables.css': [ 'static/css/src/wpp.admin.data.tables.less' ],
          'static/css/wpp.admin.jquery.ui.css': [ 'static/css/src/wpp.admin.jquery.ui.less' ],
          
          'static/templates/wp_properties.css': [ 'static/css/src/wp_properties.less' ],
          'static/templates/wp_properties-ie_7.css': [ 'static/css/src/wp_properties-ie_7.less' ],
          'static/templates/wp_properties-msie.css': [ 'static/css/src/wp_properties-msie.less' ],
          
          'static/templates/theme-specific/denali.css': [ 'static/templates/theme-specific/src/denali.less' ],
          'static/templates/theme-specific/fb_properties.css': [ 'static/templates/theme-specific/src/fb_properties.less' ],
          'static/templates/theme-specific/twentyeleven.css': [ 'static/templates/theme-specific/src/twentyeleven.less' ],
          'static/templates/theme-specific/twentyten.css': [ 'static/templates/theme-specific/src/twentyten.less' ],
          'static/templates/theme-specific/twentytwelve.css': [ 'static/templates/theme-specific/src/twentytwelve.less' ]
        }
      },
      development: {
        options: {
          yuicompress: false,
          relativeUrls: true
        },
        files: {
          'static/css/wpp.admin.dev.css': [ 'static/css/src/wpp.admin.less' ],
          'static/css/wpp.admin.data.tables.dev.css': [ 'static/css/src/wpp.admin.data.tables.less' ],
          'static/css/wpp.admin.jquery.ui.dev.css': [ 'static/css/src/wpp.admin.jquery.ui.less' ],
          
          'static/templates/wp_properties.dev.css': [ 'static/css/src/wp_properties.less' ],
          'static/templates/wp_properties-ie_7.dev.css': [ 'static/css/src/wp_properties-ie_7.less' ],
          'static/templates/wp_properties-msie.dev.css': [ 'static/css/src/wp_properties-msie.less' ],
          
          'static/templates/theme-specific/denali.dev.css': [ 'static/templates/theme-specific/src/denali.less' ],
          'static/templates/theme-specific/fb_properties.dev.css': [ 'static/templates/theme-specific/src/fb_properties.less' ],
          'static/templates/theme-specific/twentyeleven.dev.css': [ 'static/templates/theme-specific/src/twentyeleven.less' ],
          'static/templates/theme-specific/twentyten.dev.css': [ 'static/templates/theme-specific/src/twentyten.less' ],
          'static/templates/theme-specific/twentytwelve.dev.css': [ 'static/templates/theme-specific/src/twentytwelve.less' ]
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
            "./lib",
            "./static/js"
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
        files: [ 'static/css/src/*.less' ],
        tasks: [ 'less:production' ]
      },
      js: {
        files: [ 'static/css/src/*' ],
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
            cwd: 'static/js/src',
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
            cwd: 'static/js/src',
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
            dest: 'static/codex',
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
        "static/cache"
      ],
      all: [
        "static/cache",
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

  // Register NPM Tasks.
  grunt.registerTask( 'default', [ 'markdown', 'less:production', 'yuidoc', 'uglify:production' ] );

  // Install Library.
  grunt.registerTask( 'install', [ 'markdown', 'less:production', 'yuidoc', 'uglify:production' ] );

  // Prepare for Distribution.
  grunt.registerTask( 'make-distribution', [ 'markdown', 'less:production', 'yuidoc', 'uglify:production' ] );

};