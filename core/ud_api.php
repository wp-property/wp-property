<?php
/**
 * UD API Distributable - Common Functions Used in Usability Dynamics, Inc. Products.
 *
 * @copyright Copyright (c) 2010 - 2014, Usability Dynamics, Inc.
 * @license https://usabilitydynamics.com/services/theme-and-plugin-eula/
 * @link http://api.usabilitydynamics.com/readme/ud_api.txt UD API Changelog
 *
 * @version 1.1
 */

if ( !class_exists( 'UD_API' ) ) {

  define( 'UD_API_Transdomain', 'UD_API_Transdomain' );

  /**
   * Used for performing various useful functions applicable to different plugins.
   *
   * @class UD_API
   * @package UsabilityDynamics
   */
  class UD_API {

    /**
     * Generate prefix based on class calling a function. Requires PHP >=  5.3
     *
     * Examples:
     * My_Class => my_
     * NoSlug => noslug_
     * UD_API => ud_
     *
     * @todo Would like a more elegant solution to determining calling class that works in PHP < 5.3 - potanin@UD 6/18/12
     * @mehtod prefixed
     * @for UD_API
     * @since 1.0.1
     */
    static public function prefixed( $annex = '' ) {

      if ( version_compare( phpversion(), 5.3 ) < 0 || !function_exists( 'get_called_class' ) ) {
        foreach ( debug_backtrace() as $step ) {
          if ( isset( $step[ 'class' ] ) ) {
            $_called_class = $step[ 'class' ];
            break;
          }
        }
      } else {
        $_called_class = get_called_class();
      }

      return strtolower( $_called_class == __CLASS__ ? 'ud' : strpos( $_called_class, '_' ) ? reset( explode( '_', $_called_class ) ) : $_called_class ) . '_' . $annex;

    }

    /**
     * Handler for general API calls to UD
     *
     * @since 1.0.0
     * @author potanin@UD
     */
    static public function get_service( $args = '' ) {

      $args = wp_parse_args( $args, array(
        'service' => false,
        'args' => array(),
        'method' => 'POST',
        'timeout' => 60,
        'sslverify' => false
      ) );

      if ( !$args[ 'service' ] ) {
        return new WP_Error( 'error', sprintf( __( 'API service not specified.', UD_API_Transdomain ) ) );
      }

      $response = wp_remote_request( add_query_arg( 'api', get_option( 'ud_api_key' ), trailingslashit( 'http://api.usabilitydynamics.com' ) . trailingslashit( $args[ 'service' ] ) ), array(
        'method' => $args[ 'method' ],
        'timeout' => $args[ 'timeout' ],
        'sslverify' => $args[ 'sslverify' ],
        'body' => array(
          'args' => $args[ 'args' ]
        ) ) );

      if ( is_wp_error( $response ) ) {
        return $response;
      }

      if ( $response[ 'response' ][ 'code' ] == 200 ) {
        return json_decode( $response[ 'body' ] ) ? json_decode( $response[ 'body' ] ) : $response[ 'body' ];
      } else {
        return new WP_Error( 'error', sprintf( __( 'API Failure: %1s.', UD_API_Transdomain ), $response[ 'response' ][ 'message' ] ) );
      }

    }
    
    /**
     * Parses Query.
     * HACK. The current logic solves the issue of max_input_vars in the case if query is huge.
     * 
     * @see parse_str() Default PHP function
     * @param mixed $request
     * @version 1.0
     * @author peshkov@UD
     */
    static public function parse_str( $request ) {
      $data = array();
      $tokens = explode( "&", $request );
      foreach ( $tokens as $token ) {
        $token = str_replace( '%2B', md5( '%2B' ), $token );
        $arr = array();
        parse_str( $token, $arr );
        array_walk_recursive( $arr, create_function( '&$value,$key', '$value = str_replace( md5( "%2B" ), "+", $value );' ) );
        $data = self::extend( $data, $arr );
      }
      return $data;
    }

    /**
     * Port of jQuery.extend() function.
     *
     * @since 1.0.3
     * @version 0.1
     */
    static public function extend() {
      //$arrays = array_reverse( func_get_args() );
      $arrays = func_get_args();
      $base = array_shift( $arrays );
      if( !is_array( $base ) ) $base = empty( $base ) ? array() : array( $base );
      foreach( (array) $arrays as $append ) {
        if( !is_array( $append ) ) $append = array( $append );
        foreach( (array) $append as $key => $value ) {
          if( !array_key_exists( $key, $base ) and !is_numeric( $key ) ) {
          $base[ $key ] = $append[ $key ];
          continue;
          }
          if( @is_array( $value ) or ( isset( $base[ $key ] ) && @is_array( $base[ $key ] ) ) ) {
            $base[ $key ] = self::extend( @$base[ $key ], @$append[ $key ] );
          } else if( is_numeric( $key ) ) {
          if( !in_array( $value, $base ) ) $base[] = $value;
          } else {
          $base[ $key ] = $value;
          }
        }
      }
      return $base;
    }
    
    /**
     * Sanitizes data.
     * Prevents shortcodes and XSS adding!
     *
     * @author peshkov@UD
     */
    static public function sanitize_request( $data ) {
      if( is_array( $data ) ) {
        foreach( $data as $k => $v ) {
          $data[ $k ] = self::sanitize_request( $v );
        }
      } else {
        $data = strip_shortcodes( $data );
        $data = filter_var( $data, FILTER_SANITIZE_STRING );
      }
      return $data;
    }

    /**
     * Converts slashes for Windows paths.
     *
     * @since 1.0.0
     * @source Flawless
     * @author potanin@UD
     */
    static public function fix_path( $path ) {
      return str_replace( '\\', '/', $path );
    }

    /**
     * Applies trim() function to all values in an array
     *
     * @source WP-Property
     * @since 0.6.0
     */
    static function trim_array( $array = array() ) {

      foreach ( (array)$array as $key => $value ) {
        $array[ $key ] = trim( $value );
      }

      return $array;

    }

    /**
     * Returns image sizes for a passed image size slug
     *
     * @source WP-Property
     * @since 0.5.4
     * @returns array keys: 'width' and 'height' if image type sizes found.
     */
    static public function image_sizes( $type = false, $args = '' ) {
      global $_wp_additional_image_sizes;

      $image_sizes = (array)$_wp_additional_image_sizes;

      $image_sizes[ 'thumbnail' ] = array(
        'width' => intval( get_option( 'thumbnail_size_w' ) ),
        'height' => intval( get_option( 'thumbnail_size_h' ) )
      );

      $image_sizes[ 'medium' ] = array(
        'width' => intval( get_option( 'medium_size_w' ) ),
        'height' => intval( get_option( 'medium_size_h' ) )
      );

      $image_sizes[ 'large' ] = array(
        'width' => intval( get_option( 'large_size_w' ) ),
        'height' => intval( get_option( 'large_size_h' ) )
      );

      foreach ( (array)$image_sizes as $size => $data ) {
        $image_sizes[ $size ] = array_filter( (array)$data );
      }

      return array_filter( (array)$image_sizes );

    }

    /**
     * Insert array into an associative array before a specific key
     *
     * @source http://stackoverflow.com/questions/6501845/php-need-help-inserting-arrays-into-associative-arrays-at-given-keys
     * @author potanin@UD
     */
    static public function array_insert_before( $array, $key, $new ) {
      $array = (array)$array;
      $keys = array_keys( $array );
      $pos = (int)array_search( $key, $keys );
      return array_merge(
        array_slice( $array, 0, $pos ),
        $new,
        array_slice( $array, $pos )
      );
    }

    /**
     * Insert array into an associative array after a specific key
     *
     * @source http://stackoverflow.com/questions/6501845/php-need-help-inserting-arrays-into-associative-arrays-at-given-keys
     * @author potanin@UD
     */
    static public function array_insert_after( $array, $key, $new ) {
      $array = (array)$array;
      $keys = array_keys( $array );
      $pos = (int)array_search( $key, $keys ) + 1;
      return array_merge(
        array_slice( $array, 0, $pos ),
        $new,
        array_slice( $array, $pos )
      );
    }

    /**
     * Gracefully Die on Fatal Errors
     *
     * To Enable:  add_filter( 'wp_die_handler', function() { return 'ud_graceful_death'; } , 10, 3 );
     *
     * @author potanin@UD
     */
    static public function ud_graceful_death( $message, $title = '', $args = array() ) {
      $defaults = array( 'response' => 500 );
      $r = wp_parse_args( $args, $defaults );
      $backtrace = debug_backtrace();

      if ( $backtrace[ 2 ][ 'function' ] == 'wp_die' ) {

        switch ( $message ) {

          case 'You do not have sufficient permissions to access this page.':
            $original_message = $message;
            $message = array();
            $message[ ] = '<li class="title">Access Denied</li>';
            $message[ ] = '<li class="message">' . $original_message . '</li>';
            $message = '<ul>' . implode( (array)$message ) . '</li>';
            break;

        }

      }

      if ( !headers_sent() ) {
        status_header( $r[ 'response' ] );
        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
      } else {
        echo '<div class="ud_inline_fatal_error">' . $message . '</div>';
        die();
      }

      if ( empty( $title ) ) {
        $title = function_exists( '__' ) ? __( 'UD Error', UD_API_Transdomain ) : 'UD Error';
      }

      $output = array();
      $output[ ] = '<!DOCTYPE html>';
      $output[ ] = '<!-- Ticket #11289, IE bug fix: always pad the error page with enough characters such that it is greater than 512 bytes, even after gzip compression abcdefghijklmnopqrstuvwxyz1234567890aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz11223344556677889900abacbcbdcdcededfefegfgfhghgihihjijikjkjlklkmlmlnmnmononpopoqpqprqrqsrsrtstsubcbcdcdedefefgfabcadefbghicjkldmnoepqrfstugvwxhyz1i234j567k890laabmbccnddeoeffpgghqhiirjjksklltmmnunoovppqwqrrxsstytuuzvvw0wxx1yyz2z113223434455666777889890091abc2def3ghi4jkl5mno6pqr7stu8vwx9yz11aab2bcc3dd4ee5ff6gg7hh8ii9j0jk1kl2lmm3nnoo4p5pq6qrr7ss8tt9uuvv0wwx1x2yyzz13aba4cbcb5dcdc6dedfef8egf9gfh0ghg1ihi2hji3jik4jkj5lkl6kml7mln8mnm9ono-->';
      $output[ ] = '<html xmlns="http://www.w3.org/1999/xhtml" class="graceful_death">';
      $output[ ] = '<head>';
      $output[ ] = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
      $output[ ] = '<title>' . $title . '</title>';
      $output[ ] = '<link rel="stylesheet" id="wp-admin-css"  href="' . admin_url( '/css/wp-admin.css' ) . '" type="text/css" media="all" />';
      $output[ ] = '</head>' . $message . '</body></html>';

      die( implode( '', (array)$output ) );

    }

    /**
     * Attemp to convert a plural US word into a singular.
     *
     * @todo API Service Candidate since we ideally need a dictionary reference.
     * @author potanin@UD
     */
    static public function depluralize( $word ) {
      $rules = array( 'ss' => false, 'os' => 'o', 'ies' => 'y', 'xes' => 'x', 'oes' => 'o', 'ies' => 'y', 'ves' => 'f', 's' => '' );

      foreach ( array_keys( $rules ) as $key ) {

        if ( substr( $word, ( strlen( $key ) * -1 ) ) != $key )
          continue;

        if ( $key === false )
          return $word;

        return substr( $word, 0, strlen( $word ) - strlen( $key ) ) . $rules[ $key ];

      }

      return $word;

    }

    /**
     * Convert bytes into the logical unit of measure based on size.
     *
     * @source Flawless
     * @since 1.0.0
     * @author potanin@UD
     */
    static public function format_bytes( $bytes, $precision = 2 ) {
      $kilobyte = 1024;
      $megabyte = $kilobyte * 1024;
      $gigabyte = $megabyte * 1024;
      $terabyte = $gigabyte * 1024;

      if ( ( $bytes >= 0 ) && ( $bytes < $kilobyte ) ) {
        return $bytes . ' B';

      } elseif ( ( $bytes >= $kilobyte ) && ( $bytes < $megabyte ) ) {
        return round( $bytes / $kilobyte, $precision ) . ' KB';

      } elseif ( ( $bytes >= $megabyte ) && ( $bytes < $gigabyte ) ) {
        return round( $bytes / $megabyte, $precision ) . ' MB';

      } elseif ( ( $bytes >= $gigabyte ) && ( $bytes < $terabyte ) ) {
        return round( $bytes / $gigabyte, $precision ) . ' GB';

      } elseif ( $bytes >= $terabyte ) {
        return round( $bytes / $terabyte, $precision ) . ' TB';
      } else {
        return $bytes . ' B';
      }
    }

    /**
     * Used to enable/disable/print SQL log
     *
     * Usage:
     * self::sql_log( 'enable' );
     * self::sql_log( 'disable' );
     * $queries= self::sql_log( 'print_log' );
     *
     * @since 0.1.0
     */
    static public function sql_log( $action = 'attach_filter' ) {
      global $wpdb;

      if ( !in_array( $action, array( 'enable', 'disable', 'print_log' ) ) ) {
        $wpdb->ud_queries[ ] = array( $action, $wpdb->timer_stop(), $wpdb->get_caller() );
        return $action;
      }

      if ( $action == 'enable' ) {
        add_filter( 'query', array( 'UD_API', 'sql_log' ), 75 );
      }

      if ( $action == 'disable' ) {
        remove_filter( 'query', array( 'UD_API', 'sql_log' ), 75 );
      }

      if ( $action == 'print_log' ) {
        $result = array();
        foreach ( (array)$wpdb->ud_queries as $query ) {
          $result[ ] = $query[ 0 ] ? $query[ 0 ] . ' (' . $query[ 1 ] . ')' : $query[ 2 ];
        }
        return $result;
      }

    }

    /**
     * Helpder function for figuring out if another specific function is a predecesor of current function.
     *
     * @since 1.0.0
     * @author potanin@UD
     */
    static public function _backtrace_function( $function = false ) {

      foreach ( debug_backtrace() as $step ) {
        if ( $function && $step[ 'function' ] == $function ) {
          return true;
        }
      }

    }

    /**
     * Helpder function for figuring out if a specific file is a predecesor of current file.
     *
     * @since 1.0.0
     * @author potanin@UD
     */
    static public function _backtrace_file( $file = false ) {

      foreach ( debug_backtrace() as $step ) {
        if ( $file && basename( $step[ 'file' ] ) == $file ) {
          return true;
        }
      }

    }
    
    /**
     * Adds logs to file in uploads directory if constant UD_FILE_DEBUG_LOG defined
     *
     * peshkov@UD
     */
    static public function maybe_debug_log( $message, $instance = '' ) {
      if( defined( 'UD_FILE_DEBUG_LOG' ) && UD_FILE_DEBUG_LOG ) {
        $uploads_dir = wp_upload_dir();
        $logdir = $uploads_dir[ 'basedir' ] . '/logs';
        $logfile = $logdir . '/ud_debug ' . ( !empty( $instance ) ? '_' . $instance : '' ) . '.log';
        if( !is_dir( $logdir ) ) {
          if( !wp_mkdir_p( $logdir ) ) {
            return false;
          }
        }
        $message = date( '[d M H:i:s]' ) . ' : ' . $message . PHP_EOL;
        if( error_log( $message, 3, $logfile ) ) {
          return true;
        }
      }
      return false;
    }

    /**
     * Parse standard WordPress readme file
     *
     * @author potanin@UD
     */
    static public function parse_readme( $readme_file = false ) {

      if ( !$readme_file || !is_file( $readme_file ) ) {
        return false;
      }

      $_api_response = self::get_service( array(
        'service' => 'parser',
        'args' => array(
          'string' => file_get_contents( $readme_file ),
          'type' => 'readme' )
      ) );

      if ( is_wp_error( $_api_response ) ) {
        return false;
      } else {
        return is_wp_error( $_api_response ) ? false : $_api_response;
      }

    }

    /**
     * Fixed serialized arrays which sometimes get messed up in WordPress
     *
     * @source http://shauninman.com/archive/2008/01/08/recovering_truncated_php_serialized_arrays
     */
    static public function repair_serialized_array( $serialized ) {
      $tmp = preg_replace( '/^a:\d+:\{/', '', $serialized );
      return self::repair_serialized_array_callback( $tmp ); // operates on and whittles down the actual argument
    }

    /**
     * The recursive function that does all of the heavy lifing. Do not call directly.
     *
     *
     */
    static public function repair_serialized_array_callback( &$broken ) {

      $data = array();
      $index = null;
      $len = strlen( $broken );
      $i = 0;

      while ( strlen( $broken ) ) {
        $i++;
        if ( $i > $len ) {
          break;
        }

        if ( substr( $broken, 0, 1 ) == '}' ) // end of array
        {
          $broken = substr( $broken, 1 );
          return $data;
        } else {
          $bite = substr( $broken, 0, 2 );
          switch ( $bite ) {
            case 's:': // key or value
              $re = '/^s:\d+:"([^\"]*)";/';
              if ( preg_match( $re, $broken, $m ) ) {
                if ( $index === null ) {
                  $index = $m[ 1 ];
                } else {
                  $data[ $index ] = $m[ 1 ];
                  $index = null;
                }
                $broken = preg_replace( $re, '', $broken );
              }
              break;

            case 'i:': // key or value
              $re = '/^i:(\d+);/';
              if ( preg_match( $re, $broken, $m ) ) {
                if ( $index === null ) {
                  $index = (int)$m[ 1 ];
                } else {
                  $data[ $index ] = (int)$m[ 1 ];
                  $index = null;
                }
                $broken = preg_replace( $re, '', $broken );
              }
              break;

            case 'b:': // value only
              $re = '/^b:[01];/';
              if ( preg_match( $re, $broken, $m ) ) {
                $data[ $index ] = (bool)$m[ 1 ];
                $index = null;
                $broken = preg_replace( $re, '', $broken );
              }
              break;

            case 'a:': // value only
              $re = '/^a:\d+:\{/';
              if ( preg_match( $re, $broken, $m ) ) {
                $broken = preg_replace( '/^a:\d+:\{/', '', $broken );
                $data[ $index ] = self::repair_serialized_array_callback( $broken );
                $index = null;
              }
              break;

            case 'N;': // value only
              $broken = substr( $broken, 2 );
              $data[ $index ] = null;
              $index = null;
              break;
          }
        }
      }

      return $data;
    }

    /**
     * Determine if an item is in array and return checked
     *
     * @since 0.5.0
     */
    static public function checked_in_array( $item, $array ) {

      if ( is_array( $array ) && in_array( $item, $array ) ) {
        echo ' checked="checked" ';
      }

    }

    /**
     * Check if the current WP version is older then given parameter $version.
     * @param string $version
     * @since 1.0.0
     * @author peshkov@UD
     */
    static public function is_older_wp_version( $version = '' ) {
      if ( empty( $version ) || (float)$version == 0 ) return false;
      $current_version = get_bloginfo( 'version' );
      /** Clear version numbers */
      $current_version = preg_replace( "/^([0-9\.]+)-(.)+$/", "$1", $current_version );
      $version = preg_replace( "/^([0-9\.]+)-(.)+$/", "$1", $version );
      return ( (float)$current_version < (float)$version ) ? true : false;
    }

    /**
     * Determine if any requested template exists and return path to it.
     *
     * == Usage ==
     * The function will search through: STYLESHEETPATH, TEMPLATEPATH, and any custom paths you pass as second argument.
     *
     * $best_template = UD_API::get_template_part( array(
     *   'template-ideal-match',
     *   'template-default',
     * ), array( PATH_TO_MY_TEMPLATES );
     *
     * Note: load_template() extracts $wp_query->query_vars into the loaded template, so to add any global variables to the template, add them to
     * $wp_query->query_vars prior to calling this function.
     *
     * @param mixed $name List of requested templates. Will be return the first found
     * @param array $path [optional]. Method tries to find template in theme, but also it can be found in given list of pathes.
     * @param array $opts [optional]. Set of additional params: 
     *   - string $instance. Template can depend on instance. For example: facebook, PDF, etc. Uses filter: ud::template_part::{instance}
     *   - boolean $load. if true, rendered HTML will be returned, in other case, only found template's path.
     * @load boolean [optional]. If true and a template is found, the template will be loaded via load_template() and returned as a string
     * @author peshkov@UD
     * @version 1.1
     */
    static public function get_template_part( $name, $path = array(), $opts = array() ) {
      $name = (array)$name;
      $template = "";

      /**
       * Set default instance.
       * Template can depend on instance. For example: facebook, PDF, etc.
       */
      $instance = apply_filters( "ud::current_instance", "default" );

      $opts = wp_parse_args( $opts, array(
        'instance' => $instance,
        'load' => false,
      ) );
      
      //** Allows to add/change templates storage directory. */
      $path = apply_filters( "ud::template_part::path", $path, $name, $opts );

      foreach ( $name as $n ) {
        $n = "{$n}.php";
        $template = locate_template( $n, false );
        if ( empty( $template ) && !empty( $path ) ) {
          foreach ( (array)$path as $p ) {
            if ( file_exists( $p . "/" . $n ) ) {
              $template = $p . "/" . $n;
              break( 2 );
            }
          }
        }
        if ( !empty( $template ) ) break;
      }

      $template = apply_filters( "ud::template_part::{$opts['instance']}", $template, array( 'name' => $name, 'path' => $path, 'opts' => $opts ) );
      
      //** If match and load was requested, get template and return */
      if( !empty( $template ) && $opts[ 'load' ] == true ) {
        ob_start();
        load_template( $template, false );
        return ob_get_clean();
      }

      return !empty( $template ) ? $template : false;
    }

    /**
     * The goal of function is going through specific filters and return (or print) classes.
     * This function should not be called directly.
     * Every ud plugin/theme should have own short function ( wrapper ) for calling it. E.g., see: wpp_css().
     * So, use it in template as: <div id="my_element" class="<?php wpp_css("{name_of_template}::my_element"); ?>"> </div>
     *
     * Arguments:
     *  - instance [string] - UD plugin|theme's slug. E.g.: wpp, denali, wpi, etc
     *  - element [string] - specific element in template which will use the current classes.
     *    Element should be called as {template}::{specific_name_of_element}. Where {template} is name of template,
     *    where current classes will be used. This standart is optional. You can set any element's name if you want.
     *  - classes [array] - set of classes which will be used for element.
     *  - return [boolean] - If false, the function prints all classes like 'class1 class2 class3'
     *
     * @param array $args
     * @author peshkov@UD
     * @version 0.1
     */
    static public function get_css_classes( $args = array() ) {
      $classes = '';
      $instance = '';
      $element = '';
      $return = '';
      //** Set arguments */
      $args = wp_parse_args( (array)$args, array(
        'classes' => array(),
        'instance' => '',
        'element' => '',
        'return' => false,
      ) );

      extract( $args );

      //** Cast (set correct types) to avoid issues */
      if ( !is_array( $classes ) ) {
        $classes = trim( $classes );
        $classes = str_replace( ',', ' ', $classes );
        $classes = explode( ' ', $classes );
      }

      foreach ( $classes as &$c ) $c = trim( $c );
      $instance = (string)$instance;
      $element = (string)$element;

      //** Now go through the filters */
      $classes = apply_filters( "$instance::css::$element", $classes, $args );

      if ( !$return ) {
        echo implode( " ", (array)$classes );
      }

      return $classes;
    }

    /**
     * Return simple array of column tables in a table
     *
     * @version 0.6
     */
    static public function get_column_names( $table ) {

      global $wpdb;

      $table_info = $wpdb->get_results( "SHOW COLUMNS FROM $table" );

      if ( empty( $table_info ) ) {
        return array();
      }

      foreach ( (array)$table_info as $row ) {
        $columns[ ] = $row->Field;
      }

      return $columns;

    }

    /**
     * Creates a Quick-Access table for post
     *
     * @param $table_name Can be anything but for consistency should use Post Type slug.
     * @param $args
     *    - update - Either existing Post Type or ID of a post.  Post Type will trigger update for all posts.
     *
     * @author potanin@UD
     * @version 0.6
     */
    static public function update_qa_table( $table_name = false, $args = false ) {
      global $wpdb;

      $args = array_filter( wp_parse_args( $args, array(
        'table_name' => $wpdb->base_prefix . 'ud_qa_' . $table_name,
        'drop_current' => false,
        'attributes' => array(),
        'update' => array(),
        'debug' => false
      ) ) );

      $return = array();

      if ( $args[ 'debug' ] ) {
        self::sql_log( 'enable' );
      }

      /* Remove current table */
      if ( $args[ 'drop_current' ] ) {
        $wpdb->query( "DROP TABLE {$args[table_name]}" );
      }

      /* Check if this table exists */
      if ( $wpdb->get_var( "SHOW TABLES LIKE '{$args[table_name]}' " ) != $args[ 'table_name' ] ) {
        $wpdb->query( "CREATE TABLE {$args[table_name]} (
          post_id mediumint(9) NOT NULL,
          ud_last_update timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY post_id ( post_id ) ) ENGINE = MyISAM" );
      }

      $args[ 'current_columns' ] = self::get_column_names( $args[ 'table_name' ] );

      /* Add attributes, if they don't exist, to table */
      foreach ( (array)$args[ 'attributes' ] as $attribute => $type ) {

        $type = is_array( $type ) ? $type[ 'type' ] : $type;

        if ( $type == 'taxonomy' ) {
          $wpdb->query( "ALTER TABLE {$args[table_name] } ADD {$attribute}_ids VARCHAR( 512 ) NULL DEFAULT NULL, COMMENT '{$type}', ADD FULLTEXT INDEX ( {$attribute}_ids ) ;" );
          $wpdb->query( "ALTER TABLE {$args[table_name] } ADD {$attribute} VARCHAR( 512 ) NULL DEFAULT NULL, COMMENT '{$type}', ADD FULLTEXT INDEX ( {$attribute} )" );
        } else {
          $wpdb->query( "ALTER TABLE {$args[table_name] } ADD {$attribute} VARCHAR( 512 ) NULL DEFAULT NULL, COMMENT '{$type}', ADD FULLTEXT INDEX ( {$attribute} )" );
        }

      }

      /* If no update requested, leave */
      if ( !$args[ 'update' ] ) {
        return true;
      }

      /* Determine update type and initiate updater */
      foreach ( (array)$args[ 'update' ] as $update_type ) {

        if ( is_numeric( $update_type ) ) {

          $insert_id = self::update_qa_table_item( $update_type, $args );

          if ( !is_wp_error( $insert_id ) ) {
            $return[ 'updated' ][ ] = $insert_id;
          } else {
            $return[ 'error' ][ ] = $insert_id->get_error_message();
          }

        }

        if ( post_type_exists( $update_type ) ) {
          foreach ( (object)$wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = '{$update_type}' " ) as $post_id ) {

            $insert_id = self::update_qa_table_item( $post_id, $args );

            if ( !is_wp_error( $insert_id ) ) {
              $return[ 'updated' ][ ] = $insert_id;
            } else {
              $return[ 'error' ][ ] = $insert_id->get_error_message();
            }

          }
        }

      }

      if ( $args[ 'debug' ] ) {
        self::sql_log( 'disable' );
        $return[ 'debug' ] = self::sql_log( 'print_log' );
      }

      return $return;

    }

    /**
     * Update post data in QA table
     *
     * @author potanin@UD
     * @version 0.6
     */
    static public function update_qa_table_item( $post_id = false, $args ) {
      global $wpdb;

      $types = array();

      /* Organize requested  meta by type */
      foreach ( (array)$args[ 'attributes' ] as $attribute_key => $type ) {

        $type = is_array( $type ) ? $type[ 'type' ] : $type;

        $types[ $type ][ ] = $attribute_key;
        $types[ $type ] = array_filter( (array)$types[ $type ] );
      }

      /* Get Primary Data */
      if ( !empty( $types[ 'primary' ] ) ) {
        $insert = $wpdb->get_row( "SELECT ID as post_id, " . implode( ', ', $types[ 'primary' ] ) . " FROM {$wpdb->posts} WHERE ID = {$post_id} ", ARRAY_A );
      }

      /* Get Meta Data */
      if ( !empty( $types[ 'post_meta' ] ) ) {
        foreach ( (object)$wpdb->get_results( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$post_id} AND meta_key IN ( '" . implode( "', '", $types[ 'post_meta' ] ) . "' ); " ) as $row ) {
          $insert[ $row->meta_key ] .= $row->meta_value . ',';
        }
        /* Remove leading/trailing commas */
        foreach ( (array)$types[ 'post_meta' ] as $type ) {
          $insert[ $type ] = trim( $insert[ $type ], ',' );
        }
      }

      if ( !empty( $types[ 'taxonomy' ] ) ) {
        foreach ( (object)$wpdb->get_results( "
        SELECT {$wpdb->term_taxonomy}.term_id, taxonomy, name FROM {$wpdb->terms}
        LEFT JOIN {$wpdb->term_taxonomy} on {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
        LEFT JOIN {$wpdb->term_relationships} on {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id
        WHERE object_id = $post_id AND taxonomy IN ( '" . implode( "', '", $types[ 'taxonomy' ] ) . "' ); " ) as $row ) {
          $insert[ $row->taxonomy . '_ids' ] .= $row->term_id . ',';
          $insert[ $row->taxonomy ] .= $row->name . ',';
        }

        /* Loop again, removing trailing/leading commas */
        foreach ( (array)$types[ 'taxonomy' ] as $taxonomy ) {
          $insert[ $taxonomy ] = trim( $insert[ $taxonomy ], ',' );
          $insert[ $taxonomy . '_ids' ] = trim( $insert[ $taxonomy . '_ids' ], ',' );
        }
      }

      $insert = array_filter( (array)$insert );

      if ( $wpdb->get_var( "SELECT post_id FROM {$args['table_name']} WHERE post_id = {$post_id} " ) == $post_id ) {
        $wpdb->update( $args[ 'table_name' ], $insert, array( 'post_id' => $post_id ) );
        $response = $post_id;
      } else {
        if ( $wpdb->insert( $args[ 'table_name' ], $insert ) ) {
          $response = $wpdb->insert_id;
        }
      }

      return $response ? $response : new WP_Error( 'error', $wpdb->print_error() ? $wpdb->print_error() : __( 'Unknown error.' . $wpdb->last_query ) );

    }

    /**
     * Merges any number of arrays / parameters recursively,
     *
     * Replacing entries with string keys with values from latter arrays.
     * If the entry or the next value to be assigned is an array, then it
     * automagically treats both arguments as an array.
     * Numeric entries are appended, not replaced, but only if they are
     * unique
     *
     * @source http://us3.php.net/array_merge_recursive
     * @version 0.4
     */
    static public function array_merge_recursive_distinct() {
      $arrays = func_get_args();
      $base = array_shift( $arrays );
      if ( !is_array( $base ) ) $base = empty( $base ) ? array() : array( $base );
      foreach ( (array)$arrays as $append ) {
        if ( !is_array( $append ) ) $append = empty( $append ) ? array() : array( $append );
        foreach ( (array)$append as $key => $value ) {
          if ( !array_key_exists( $key, $base ) and !is_numeric( $key ) ) {
            $base[ $key ] = $append[ $key ];
            continue;
          }
          if ( @is_array( $value ) && isset( $base[ $key ] ) && isset( $append[ $key ] ) && is_array( $base[ $key ] ) && is_array( $append[ $key ] ) ) {
            $base[ $key ] = self::array_merge_recursive_distinct( $base[ $key ], $append[ $key ] );
          } else if ( is_numeric( $key ) ) {
            if ( !in_array( $value, $base ) ) $base[ ] = $value;
          } else {
            $base[ $key ] = $value;
          }
        }
      }
      return $base;
    }

    /**
     * Returns a URL to a post object based on passed variable.
     *
     * If its a number, then assumes its the id, If it resembles a slug, then get the first slug match.
     *
     * @since 1.0
     * @param string $title A page title, although ID integer can be passed as well
     * @return string The page's URL if found, otherwise the general blog URL
     */
    static public function post_link( $title = false ) {
      global $wpdb;

      if ( !$title )
        return get_bloginfo( 'url' );

      if ( is_numeric( $title ) )
        return get_permalink( $title );

      if ( $id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '$title'  AND post_status='publish'" ) )
        return get_permalink( $id );

      if ( $id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE LOWER(post_title) = '" . strtolower( $title ) . "'   AND post_status='publish'" ) )
        return get_permalink( $id );

    }

    /**
     * Add an entry to the plugin-specifig log.
     *
     * Creates log if one does not exist.
     *
     * = USAGE =
     * self::log( "Settings updated" );
     *
     */
    static public function log( $message = false, $args = array() ) {
      $prefix = '';
      $type = '';
      $object = '';
      $args = wp_parse_args( $args, array(
        'type' => 'default',
        'object' => false,
        'prefix' => 'ud',
        'instance' => 'UD',
      ) );
      extract( $args );
      $log = "{$prefix}_log";

      $user_id = '';
      if ( did_action( 'init' ) ) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
      }

      $this_log = get_option( $log );

      if ( empty( $this_log ) ) {
        $this_log = array();
        $entry = array(
          'time' => time(),
          'message' => __( 'Log Started.', UD_API_Transdomain ),
          'user' => $user_id,
          'type' => $type,
          'instance' => $instance,
        );
      }

      if ( $message ) {
        $entry = array(
          'time' => time(),
          'message' => $message,
          'user' => $type == 'system' ? 'system' : $user_id,
          'type' => $type,
          'object' => $object,
          'instance' => $instance,
        );
      }

      if ( !is_array( $entry ) ) {
        return false;
      }

      array_push( $this_log, $entry );

      $this_log = array_filter( $this_log );

      update_option( $log, $this_log );

      return true;
    }

    /**
     * Used to get the current plugin's log created via UD class
     *
     * If no log exists, it creates one, and then returns it in chronological order.
     *
     * Example to view log:
     * <code>
     * print_r( self::get_log() );
     * </code>
     *
     * $param string Event description
     * @uses get_option()
     * @uses update_option()
     * @return array Using the get_option function returns the contents of the log.
     *
     */
    static public function get_log( $args = false ) {
      $prefix = '';
      $args = wp_parse_args( $args, array(
        'limit' => 20,
        'prefix' => 'ud'
      ) );
      extract( $args );

      $log = "{$prefix}_log";
      $this_log = get_option( $log );

      if ( empty( $this_log ) ) {
        $this_log = self::log( false, array( 'prefix' => $prefix ) );
      }

      $entries = (array)get_option( $log );

      $entries = array_reverse( $entries );

      $entries = array_slice( $entries, 0, $args[ 'args' ] ? $args[ 'args' ] : 10 );

      return $entries;

    }

    /**
     * Delete UD log for this plugin.
     *
     * @uses update_option()
     */
    static public function delete_log( $args = array() ) {
      $prefix = '';
      $args = wp_parse_args( $args, array(
        'prefix' => 'ud'
      ) );
      extract( $args );

      $log = "{$prefix}_log";

      delete_option( $log );
    }

    /**
     * Creates Admin Menu page for UD Log
     *
     * @todo Need to make sure this will work if multiple plugins utilize the UD classes
     * @see function show_log_page
     * @since 1.0
     * @uses add_action() Calls 'admin_menu' hook with an anonymous ( lambda-style ) function which uses add_menu_page to create a UI Log page
     */
    static public function add_log_page() {
      if ( did_action( 'admin_menu' ) ) {
        _doing_it_wrong( __FUNCTION__, sprintf( __( 'You cannot call UD_API::add_log_page() after the %1$s hook.' ), 'init' ), '3.4' );
        return false;
      }
      add_action( 'admin_menu', create_function( '', "add_menu_page( __( 'Log' ,UD_API_Transdomain ), __( 'Log',UD_API_Transdomain ), current_user_can( 'manage_options' ), 'ud_log', array( 'UD_API', 'show_log_page' ) );" ) );
    }

    /**
     * !DISABLED. Displays the UD UI log page.
     *
     * @todo Add button or link to delete log
     * @todo Add nonce to clear_log functions
     * @todo Should be refactored to implement adding LOG tabs for different instances (wpp, wpi, wp-crm). peshkov@UD
     *
     * @since 1.0.0
     */
    static public function show_log_page() {

      if ( $_REQUEST[ 'ud_action' ] == 'clear_log' ) {
        self::delete_log();
      }

      $output = array();

      $output[ ] = '<style type="text/css">.ud_event_row b { background:none repeat scroll 0 0 #F6F7DC; padding:2px 6px;}</style>';

      $output[ ] = '<div class="wrap">';
      $output[ ] = '<h2>' . __( 'Log Page for', UD_API_Transdomain ) . ' ud_log ';
      $output[ ] = '<a href="' . admin_url( "admin.php?page=ud_log&ud_action=clear_log" ) . '" class="button">' . __( 'Clear Log', UD_API_Transdomain ) . '</a></h2>';

      //die( '<pre>' . print_r( self::get_log() , true ) . '</pre>' );

      $output[ ] = '<table class="widefat"><thead><tr>';
      $output[ ] = '<th style="width: 150px">' . __( 'Timestamp', UD_API_Transdomain ) . '</th>';
      $output[ ] = '<th>' . __( 'Type', UD_API_Transdomain ) . '</th>';
      $output[ ] = '<th>' . __( 'Instance', UD_API_Transdomain ) . '</th>';
      $output[ ] = '<th>' . __( 'Event', UD_API_Transdomain ) . '</th>';
      $output[ ] = '<th>' . __( 'User', UD_API_Transdomain ) . '</th>';
      $output[ ] = '<th>' . __( 'Related Object', UD_API_Transdomain ) . '</th>';
      $output[ ] = '</tr></thead>';

      $output[ ] = '<tbody>';

      foreach ( (array)self::get_log() as $event ) {
        $output[ ] = '<tr class="ud_event_row">';
        $output[ ] = '<td>' . self::nice_time( $event[ 'time' ] ) . '</td>';
        $output[ ] = '<td>' . $event[ 'type' ] . '</td>';
        $output[ ] = '<td>' . ( isset( $event[ 'instance' ] ) ? $event[ 'instance' ] : '' ) . '</td>';
        $output[ ] = '<td>' . $event[ 'message' ] . '</td>';
        $output[ ] = '<td>' . ( is_numeric( $event[ 'user' ] ) ? get_userdata( $event[ 'user' ] )->display_name : __( 'None' ) ) . '</td>';
        $output[ ] = '<td>' . $event[ 'object' ] . '</td>';
        $output[ ] = '</tr>';
      }

      $output[ ] = '</tbody></table>';

      $output[ ] = '</div>';

      echo implode( '', (array)$output );

    }

    /**
     * Turns a passed string into a URL slug
     *
     * Argument 'check_existance' will make the function check if the slug is used by a WordPress post
     *
     * @param string $content
     * @param string $args Optional list of arguments to overwrite the defaults.
     * @since 1.0
     * @uses add_action() Calls 'admin_menu' hook with an anonymous (lambda-style) function which uses add_menu_page to create a UI Log page
     * @return string
     */
    static public function create_slug( $content, $args = false ) {
      $separator = '';
      $defaults = array(
        'separator' => '-',
        'check_existance' => false
      );

      extract( wp_parse_args( $args, $defaults ) );

      $content = preg_replace( '~[^\\pL0-9_]+~u', $separator, $content ); // substitutes anything but letters, numbers and '_' with separator
      $content = trim( $content, $separator );
      $content = iconv( "utf-8", "us-ascii//TRANSLIT", $content ); // TRANSLIT does the whole job
      $content = strtolower( $content );
      $slug = preg_replace( '~[^-a-z0-9_]+~', '', $content ); // keep only letters, numbers, '_' and separator

      return $slug;
    }

    /**
     * Convert a slug to a more readable string
     *
     * @since 1.3
     * @return string
     */
    static public function de_slug( $string ) {
      return ucwords( str_replace( "_", " ", $string ) );
    }

    /**
     * Returns location information from Google Maps API call
     *
     * @version 1.1
     * @since 1.0.0
     * @return object
     */
    static public function geo_locate_address( $address = false, $localization = "en", $return_obj_on_fail = false, $latlng = false ) {

      if ( !$address && !$latlng ) {
        return false;
      }

      if ( is_array( $address ) ) {
        return false;
      }

      $return = new stdClass();

      $address = urlencode( $address );

      $url = str_replace( " ", "+", "http://maps.google.com/maps/api/geocode/json?" . ( ( is_array( $latlng ) ) ? "latlng={$latlng['lat']},{$latlng['lng']}" : "address={$address}" ) . "&sensor=true&language={$localization}" );

      $obj = ( json_decode( wp_remote_fopen( $url ) ) );

      if ( $obj->status != "OK" ) {

        // Return Google result if needed instead of just false
        if ( $return_obj_on_fail ) {
          return $obj;
        }

        return false;

      }

      $results = $obj->results;
      $results_object = $results[ 0 ];
      $geometry = $results_object->geometry;

      $return->formatted_address = $results_object->formatted_address;
      $return->latitude = $geometry->location->lat;
      $return->longitude = $geometry->location->lng;

      // Cycle through address component objects picking out the needed elements, if they exist
      foreach ( (array)$results_object->address_components as $ac ) {

        // types is returned as an array, look through all of them
        foreach ( (array)$ac->types as $type ) {
          switch ( $type ) {

            case 'street_number':
              $return->street_number = $ac->long_name;
              break;

            case 'route':
              $return->route = $ac->long_name;
              break;

            case 'locality':
              $return->city = $ac->long_name;
              break;

            case 'administrative_area_level_3':
              if ( empty( $return->city ) )
                $return->city = $ac->long_name;
              break;

            case 'administrative_area_level_2':
              $return->county = $ac->long_name;
              break;

            case 'administrative_area_level_1':
              $return->state = $ac->long_name;
              $return->state_code = $ac->short_name;
              break;

            case 'country':
              $return->country = $ac->long_name;
              $return->country_code = $ac->short_name;
              break;

            case 'postal_code':
              $return->postal_code = $ac->long_name;
              break;

            case 'sublocality':
              $return->district = $ac->long_name;
              break;

          }
        }
      }

      //** API Callback */
      $return = apply_filters( 'ud::geo_locate_address', $return, $results_object, $address, $localization );

      //** API Callback (Legacy) - If no actions have been registered for the new hook, we support the old one. */
      if ( !has_action( 'ud::geo_locate_address' ) ) {
        $return = apply_filters( 'geo_locate_address', $return, $results_object, $address, $localization );
      }

      return $return;

    }

    /**
     * Returns avaliability of Google's Geocoding Service based on time of last returned status OVER_QUERY_LIMIT
     * @uses const self::blocking_for_new_validation_interval
     * @uses option ud::geo_locate_address_last_OVER_QUERY_LIMIT
     * @param type $update used to set option value in time()
     * @return boolean
     * @author odokienko@UD
     */
    static public function available_address_validation( $update = false ) {
      global $wpdb;

      if ( empty( $update ) ) {

        $last_error = (int)get_option( 'ud::geo_locate_address_last_OVER_QUERY_LIMIT' );
        if ( !empty( $last_error ) && ( time() - (int)$last_error ) < 2 ) {
          sleep( 1 );
        }
        /*if (!empty($last_error) && (((int)$last_error + self::blocking_for_new_validation_interval ) > time()) ){
          sleep(1);
          //return false;
        }else{
          //** if last success validation was less than a seccond ago we will wait for 1 seccond
          $last = $wpdb->get_var("
            SELECT if(DATE_ADD(FROM_UNIXTIME(pm.meta_value), INTERVAL 1 SECOND) < NOW(), 0, UNIX_TIMESTAMP()-pm.meta_value) LAST
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key='wpp::last_address_validation'
            LIMIT 1
          ");
          usleep((int)$last);
        }*/
      } else {
        update_option( 'ud::geo_locate_address_last_OVER_QUERY_LIMIT', time() );
        return false;
      }

      return true;
    }

    /**
     * Returns date and/or time using the WordPress date or time format, as configured.
     *
     * @param string $time Date or time to use for calculation.
     * @param string $args List of arguments to overwrite the defaults.
     *
     * @uses wp_parse_args()
     * @uses get_option()
     * @return string|bool Returns formatted date or time, or false if no time passed.
     * @updated 3.0
     */
    static public function nice_time( $time = false, $args = false ) {

      $args = wp_parse_args( $args, array(
        'format' => 'date_and_time'
      ) );

      if ( !$time ) {
        return false;
      }

      if ( $args[ 'format' ] == 'date' ) {
        return date( get_option( 'date_format' ), $time );
      }

      if ( $args[ 'format' ] == 'time' ) {
        return date( get_option( 'time_format' ), $time );
      }

      if ( $args[ 'format' ] == 'date_and_time' ) {
        return date( get_option( 'date_format' ), $time ) . ' ' . date( get_option( 'time_format' ), $time );
      }

      return false;

    }

    /**
     * Depreciated. Displays the numbers of days elapsed between a provided date and today.
     *
     * @deprecated 3.4.0
     * @author potanin@UD
     */
    static public function days_since( $from, $to = false ) {
      _deprecated_function( __FUNCTION__, '3.4', 'human_time_diff' );
      human_time_diff( $from, $to );
    }

    /**
     * Depreciated.
     *
     * @deprecated 3.4.0
     * @author potanin@UD
     */
    static public function is_url( $url ) {
      _deprecated_function( __FUNCTION__, '3.4', 'esc_url' );
      return preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url );
    }

    /**
     * Wrapper function to send notification with WP-CRM or without one
     * @param mixed $args['user']
     * @param sting $args['trigger_action']
     * @param sting $args['data']             aka $notification_data
     * @param sting $args['crm_log_message']
     * @param sting $args['subject']          using in email notification
     * @param sting $args['message']          using in email notification
     * @uses self::replace_data()
     * @uses wp_crm_send_notification()
     * @return boolean false if notification was not sent successfully
     * @autor odokienko@UD
     */
    static public function send_notification( $args = array() ) {

      $args = wp_parse_args( $args, array(
        'ignore_wp_crm' => false,
        'user' => false,
        'trigger_action' => false,
        'data' => array(),
        'message' => '',
        'subject' => '',
        'crm_log_message' => ''
      ) );

      if ( is_numeric( $args[ 'user' ] ) ) {
        $args[ 'user' ] = get_user_by( 'id', $args[ 'user' ] );
      } elseif ( filter_var( $args[ 'user' ], FILTER_VALIDATE_EMAIL ) ) {
        $args[ 'user' ] = get_user_by( 'email', $args[ 'user' ] );
      } elseif ( is_string( $args[ 'user' ] ) ) {
        $args[ 'user' ] = get_user_by( 'login', $args[ 'user' ] );
      }

      if ( !is_object( $args[ 'user' ] ) || empty( $args[ 'user' ]->data->user_email ) ) {
        return false;
      }

      if ( function_exists( 'wp_crm_send_notification' ) &&
        empty( $args[ 'ignore_wp_crm' ] )
      ) {

        if ( !empty( $args[ 'crm_log_message' ] ) ) {
          wp_crm_add_to_user_log( $args[ 'user' ]->ID, self::replace_data( $args[ 'crm_log_message' ], $args[ 'data' ] ) );
        }

        if ( !empty( $args[ 'trigger_action' ] ) && is_callable( 'WP_CRM_N', 'get_trigger_action_notification' ) ) {
          $notifications = WP_CRM_N::get_trigger_action_notification( $args[ 'trigger_action' ] );
          if ( !empty( $notifications ) ) {
            return wp_crm_send_notification( $args[ 'trigger_action' ], $args[ 'data' ] );
          }
        }

      }

      if ( empty( $args[ 'message' ] ) ) {
        return false;
      }

      return wp_mail( $args[ 'user' ]->data->user_email, self::replace_data( $args[ 'subject' ], $args[ 'data' ] ), self::replace_data( $args[ 'message' ], $args[ 'data' ] ) );

    }

    /**
     * Replace in $str all entries of keys of the given $values
     * where each key will be rounded by $brackets['left'] and $brackets['right']
     * with the relevant values of the $values
     * @param string|array $str
     * @param array $values
     * @param array $brackets
     * @return string|array
     * @author odokienko@UD
     */
    static public function replace_data( $str = '', $values = array(), $brackets = array( 'left' => '[', 'right' => ']' ) ) {
      $values = (array)$values;
      $replacements = array_keys( $values );
      array_walk( $replacements, create_function( '&$val', '$val = "' . $brackets[ 'left' ] . '".$val."' . $brackets[ 'right' ] . '";' ) );
      return str_replace( $replacements, array_values( $values ), $str );
    }

    /**
     * Gets complicated html entity e.g. Table and ou|ol
     * and removes whitespace characters include new line.
     * we should to do this before use nl2br
     *
     * @author odokienko@UD
     */
    static public function cleanup_extra_whitespace( $content ) {

      $content = preg_replace_callback( '~<(?:table|ul|ol )[^>]*>.*?<\/( ?:table|ul|ol )>~ims', create_function( '$matches', 'return preg_replace(\'~>[\s]+<((?:t[rdh]|li|\/tr|/table|/ul ))~ims\',\'><$1\',$matches[0]);' ), $content );

      return $content;
    }

    /**
     * Wrapper for json_encode function.
     * Emulates JSON_UNESCAPED_UNICODE.
     *
     * @param type $arr
     * @return JSON
     * @author peshkov@UD
     */
    static public function json_encode( $arr ) {
      // convmap since 0x80 char codes so it takes all multibyte codes (above ASCII 127). So such characters are being "hidden" from normal json_encoding
      array_walk_recursive( $arr, create_function( '&$item, $key', 'if (is_string($item)) $item = mb_encode_numericentity($item, array (0x80, 0xffff, 0, 0xffff), "UTF-8");' ) );
      return mb_decode_numericentity( json_encode( $arr ), array( 0x80, 0xffff, 0, 0xffff ), 'UTF-8' );
    }

  }

}

