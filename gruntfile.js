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

    // Locale.
    pot: {
      options:{
        package_name: '{%= name %}',
        package_version: '<%= package.version %>',
        text_domain: '{%= text_domain %}',
        dest: 'static/languages/',
        keywords: [ 'gettext', 'ngettext:1,2' ]
      },
      files:{
        src:  [ '**/*.php', 'lib/*.php' ],
        expand: true
      }
    },
    
    // Documentation.
    yuidoc: {
      compile: {
        name: '<%= package.name %>',
        description: '<%= package.description %>',
        version: '<%= package.version %>',
        url: '<%= package.homepage %>',
        options: {
          paths: 'lib',
          outdir: 'static/codex/'
        }
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
          'static/styles/wpp.admin.data.tables.css': [ 'static/styles/src/wpp.admin.data.tables.less' ],
          'static/styles/wpp.admin.jquery.ui.css': [ 'static/styles/src/wpp.admin.jquery.ui.less' ],
          
          'static/views/wp_properties.css': [ 'static/styles/src/wp_properties.less' ],
          'static/views/wp_properties-ie_7.css': [ 'static/styles/src/wp_properties-ie_7.less' ],
          'static/views/wp_properties-msie.css': [ 'static/styles/src/wp_properties-msie.less' ],
          
          'static/views/theme-specific/denali.css': [ 'static/views/theme-specific/src/denali.less' ],
          'static/views/theme-specific/fb_properties.css': [ 'static/views/theme-specific/src/fb_properties.less' ],
          'static/views/theme-specific/twentyeleven.css': [ 'static/views/theme-specific/src/twentyeleven.less' ],
          'static/views/theme-specific/twentyten.css': [ 'static/views/theme-specific/src/twentyten.less' ],
          'static/views/theme-specific/twentytwelve.css': [ 'static/views/theme-specific/src/twentytwelve.less' ]
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
          
          'static/views/wp_properties.dev.css': [ 'static/styles/src/wp_properties.less' ],
          'static/views/wp_properties-ie_7.dev.css': [ 'static/styles/src/wp_properties-ie_7.less' ],
          'static/views/wp_properties-msie.dev.css': [ 'static/styles/src/wp_properties-msie.less' ],
          
          'static/views/theme-specific/denali.dev.css': [ 'static/views/theme-specific/src/denali.less' ],
          'static/views/theme-specific/fb_properties.dev.css': [ 'static/views/theme-specific/src/fb_properties.less' ],
          'static/views/theme-specific/twentyeleven.dev.css': [ 'static/views/theme-specific/src/twentyeleven.less' ],
          'static/views/theme-specific/twentyten.dev.css': [ 'static/views/theme-specific/src/twentyten.less' ],
          'static/views/theme-specific/twentytwelve.dev.css': [ 'static/views/theme-specific/src/twentytwelve.less' ]
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
        tasks: [ 'less:production' ]
      },
      js: {
        files: [ 'static/scripts/src/*' ],
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
            cwd: 'static/scripts/src',
            src: [ '*.js' ],
            dest: 'static/scripts'
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
            dest: 'static/scripts'
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
        "static/cache",
        "cache"
      ],
      all: [
        "static/cache",
        "cache",
        "composer.lock"
      ]
    },

    // Execute Shell Commands.
    shell: {
      /**
       * Build project
       */
      build: {
        command: function( tag, build_type ) {
          return [
            'sh build.sh ' + tag + ' ' + build_type
          ].join( ' && ' );
        },
        options: {
          encoding: 'utf8',
          stderr: true,
          stdout: true
        }
      },
      /**
       * Runs PHPUnit test, creates code coverage and sends it to Scrutinizer
       */
      coverageScrutinizer: {
        command: [
          'grunt phpunit:circleci --coverage-clover=coverage.clover',
          'wget https://scrutinizer-ci.com/ocular.phar',
          'php ocular.phar code-coverage:upload --format=php-clover coverage.clover'
        ].join( ' && ' ),
        options: {
          encoding: 'utf8',
          stderr: true,
          stdout: true
        }
      },
      /**
       * Runs PHPUnit test, creates code coverage and sends it to Code Climate
       */
      coverageCodeClimate: {
        command: [
          'grunt phpunit:circleci --coverage-clover build/logs/clover.xml',
          'CODECLIMATE_REPO_TOKEN='+ process.env.CODECLIMATE_REPO_TOKEN + ' ./vendor/bin/test-reporter'
        ].join( ' && ' ),
        options: {
          encoding: 'utf8',
          stderr: true,
          stdout: true
        }
      },
      /**
       * Composer Install
       */
      install: {
        options: {
          stdout: true
        },
        command: 'composer install --no-dev'
      },
      /**
       * Composer Update
       */
      update: {
        options: {
          stdout: true
        },
        command: 'composer update --no-dev --prefer-source'
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