<?php
/**
 * Bootstrap
 *
 * @since 0.2.0
 */
namespace UsabilityDynamics\WPRETSC {

  use WP_Query;
  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPRETSC\XMLRPC' ) ) {

    final class XMLRPC {

      /**
       * Constructor
       *
       */
      public function __construct() {

        // Expose methods as RESTful endpoints.
        add_action( 'wp_ajax_nopriv_/rets-ci/histogram',        array( 'UsabilityDynamics\WPRETSC\XMLRPC', 'rpc_get_modified_histogram' ) );
        add_action( 'wp_ajax_nopriv_/rets-ci/histogramDetail',  array( 'UsabilityDynamics\WPRETSC\XMLRPC', 'rpc_get_modified_detail' ) );

        // Prevent blocking XML-RPC by third party plugins or themes
        add_filter( 'xmlrpc_enabled', '__return_true', 100 );

        // add ability to get wpp_settings so we can extra mapping settings
        add_filter( 'xmlrpc_blog_options', function ( $options ) {

          $options[ 'wpp_settings' ] = array(
            'desc' => __( 'WP-Property options.', ud_get_wp_rets_client()->domain ),
            'readonly' => true,
            'option' => 'wpp_settings'
          );

          return $options;

        } );

        // Add custom XML-RPC methods
        add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ) );

        // REST API
        add_action( 'rest_api_init', array( $this, 'api_init' ), 100 );

      }

      /**
       * Parse XML-RPC request
       * Make sure credentials are valid.
       * @param null $args
       * @param null $defaults
       * @return array
       */
      static public function parseRequest( $args = null, $defaults = null ) {
        global $wp_xmlrpc_server;

        // Do nothing for non-xmlrpc.
        if( !defined( 'XMLRPC_REQUEST' ) || ( defined( 'XMLRPC_REQUEST' ) && !XMLRPC_REQUEST ) ) {

          if( is_callable( array( $args, 'get_json_params' ) ) ) {

            if( !self::token_login( isset( $_SERVER[ 'HTTP_X_ACCESS_USER' ] ) ? $_SERVER[ 'HTTP_X_ACCESS_USER' ] : null, isset( $_SERVER[ 'HTTP_X_ACCESS_PASSWORD' ] ) ? $_SERVER[ 'HTTP_X_ACCESS_PASSWORD' ] : null ) ) {

              http_response_code( 401 );

              return array_filter(array(
                'ok' => false,
                'error' => "Unable to login.",
                'errorCode' => 401,
                'username' => isset( $_SERVER[ 'HTTP_X_ACCESS_USER' ] ) ? $_SERVER[ 'HTTP_X_ACCESS_USER' ] : '',
                'password' => isset( $_SERVER[ 'HTTP_X_ACCESS_PASSWORD' ] ) ? $_SERVER[ 'HTTP_X_ACCESS_PASSWORD' ] : ''
              ));

            }

            return $args->get_json_params();

          }

          return wp_parse_args( $_REQUEST, $defaults ? $defaults : array() );

        }

        if( isset( $wp_xmlrpc_server ) ) {
          $wp_xmlrpc_server->escape( $args );
        }

        // @note Shouldn't this be done automatically elsewhere?
        if( $args[0] && (int)$args[0] !== 1 ) {
          switch_to_blog($args[0]);
        }

        if( !$wp_xmlrpc_server->login( $args[ 1 ], $args[ 2 ] ) && !self::token_login( $args[ 1 ], $args[ 2 ] ) ) {

          return array(
            'ok' => false,
            'error' => isset( $wp_xmlrpc_server->error ) ? $wp_xmlrpc_server->error : 'Invalid credentials.',
            'username' => $args[ 1 ],
            'password' => $args[ 2 ],
          );

        }

        // remove filter which slows down updates significantly. (experimental)
        // remove_filter( 'transition_post_status', '_update_term_count_on_transition_post_status', 10 );

        // Return blank array of nothing provided so auth does not fail.
        return $args[ 3 ] ? $args[ 3 ] : array();

      }

      /**
       * Add custom XML-RPC methods
       *
       * @param $_methods
       * @return mixed
       */
      public function xmlrpc_methods( $_methods ) {

        //
        $_methods[ 'wpp.systemCheck' ]          = array( $this, 'rpc_system_check' );
        $_methods[ 'wpp.systemPing' ]           = array( $this, 'rpc_system_ping' );

        // Flush/clean up data (properties, terms, )
        $_methods[ 'wpp.cleanupProcess' ]       = array( $this, 'rpc_cleanup_process' );

        //
        $_methods[ 'wpp.getPostIdByMlsId' ]     = array( $this, 'rpc_get_post_id_by_mls_id' );
        $_methods[ 'wpp.removeDuplicates' ]     = array( $this, 'rpc_remove_duplicated_mls' );
        $_methods[ 'wpp.modifiedHistogram' ]    = array( $this, 'rpc_get_modified_histogram' );

        // Property management
        $_methods[ 'wpp.createProperty' ]       = array( $this, 'rpc_create_property' );
        $_methods[ 'wpp.editProperty' ]         = array( $this, 'rpc_edit_property' );
        $_methods[ 'wpp.updateProperty' ]       = array( $this, 'rpc_update_property' );
        $_methods[ 'wpp.insertMedia' ]          = array( $this, 'rpc_insert_media' );
        $_methods[ 'wpp.getProperty' ]          = array( $this, 'rpc_get_property' );
        $_methods[ 'wpp.deleteProperty' ]       = array( $this, 'rpc_delete_property' );
        $_methods[ 'wpp.trashProperty' ]        = array( $this, 'rpc_trash_property' );

        // Schedule stats/listings for data integrity
        $_methods[ 'wpp.scheduleStats' ]        = array( $this, 'rpc_get_schedule_stats' );
        $_methods[ 'wpp.scheduleListings' ]     = array( $this, 'rpc_get_schedule_listings' );

        return $_methods;
      }

      /**
       * Initialize REST routes for our internal XML-RPC routes.
       *
       * https://usabilitydynamics-www-marcusrealty-com-production.c.rabbit.ci/wp-json/wp-rets-client/v1/cleanup/status
       * https://usabilitydynamics-www-marcusrealty-com-production.c.rabbit.ci/wp-json/wp-rets-client/v1/cleanup/process
       *
       * @author potanin@UD
       */
      public function api_init( ) {

        register_rest_route( 'wp-rets-client/v1', '/systemCheck', array(
          'methods' => 'GET',
          'callback' => array( $this, 'rpc_system_check' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/systemPing', array(
          'methods' => 'GET',
          'callback' => array( $this, 'rpc_system_ping' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/deleteProperty', array(
          'methods' => array( 'POST', 'GET' ),
          'callback' => array( $this, 'rpc_delete_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/trashProperty', array(
          'methods' => array( 'POST', 'GET' ),
          'callback' => array( $this, 'rpc_trash_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/getPostIdByMlsId', array(
          'methods' => array( 'POST', 'GET' ),
          'callback' => array( $this, 'rpc_get_post_id_by_mls_id' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/editProperty', array(
          'methods' => 'POST',
          'callback' => array( $this, 'rpc_edit_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/updateProperty', array(
          'methods' => 'POST',
          'callback' => array( $this, 'rpc_update_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/getProperty', array(
          'methods'   => array('GET', 'POST' ),
          'callback'  => array( $this, 'rpc_get_property' ),
          'args'      => array(
            'ID' => array(
              'default' => null,
            ),
            'mls_number' => array(
              'default' => null
            ),
            'detail' => array(
              'default' => true
            ),
          )
        ) );

        register_rest_route( 'wp-rets-client/v1', '/createProperty', array(
          'methods' => 'POST',
          'callback' => array( $this, 'rpc_create_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/removeDuplicates', array(
          'methods' => 'POST',
          'callback' => array( $this, 'rpc_remove_duplicated_mls' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/getHistogram', array(
          'methods' => 'GET',
          'callback' => array( $this, 'rpc_get_modified_histogram' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/cleanupProcess', array(
          'methods' => 'GET',
          'callback' => array( $this, 'rpc_cleanup_process' ),
        ));

        register_rest_route( 'wp-rets-client/v1', '/scheduleStats', array(
          'methods' => array( 'POST', 'GET' ),
          'callback' => array( $this, 'rpc_get_schedule_stats' ),
        ));

        register_rest_route( 'wp-rets-client/v1', '/scheduleListings', array(
          'methods' => array( 'POST', 'GET' ),
          'args'            => array(
            'per_page' => array(
              'default' => 10,
              'sanitize_callback' => 'absint',
            ),
            'offset' => array(
              'default' => 0,
              'sanitize_callback' => 'absint',
            ),
            'unique' => array(
              'default' => 'wpp::rets_pk'
            ),
            'post_status' => array(
              'default' => array( 'publish', 'private', 'future', 'draft' )
            ),
            'schedule_id' => array(
              'default' => false,
              'sanitize_callback' => 'sanitize_title',
            ),
            'order' => array(
              'default' => 'asc',
              'sanitize_callback' => 'sanitize_title',
            ),
            'detail' => array(
              'default' => false,
              'sanitize_callback' => 'sanitize_title',
            ),
            'slug' => array(
              'default' => false,
              'sanitize_callback' => 'sanitize_title',
            )
          ),
          'callback' => array( $this, 'rpc_get_schedule_listings' ),
        ));

        register_rest_route( 'wp-rets-client/v1', '/insertMedia', array(
          'methods' => 'POST',
          'callback' => array( $this, 'rpc_insert_media' ),
          'args'      => array(
            'post_id' => array(
              'default' => null,
            ),
            'mls_number' => array(
              'default' => null
            ),
            '_media' => array(
              'default' => null
            ),
          )

        ) );

      }

      /**
       * Login with UD Site ID and Secret Token.
       *
       * @author potanin@UD
       * @param null $site_id
       * @param null $secret_token
       * @return bool
       */
      static public function token_login( $site_id = null, $secret_token = null ) {
        global $wp_xmlrpc_server;

        if( !$site_id || !$secret_token ) {
          return false;
        }

        $ud_site_id = get_site_option( 'ud_site_id' );
        $ud_site_secret_token = get_site_option( 'ud_site_secret_token' );

        if( defined( 'WP_UD_SITE_ID' ) && WP_UD_SITE_ID ) {
          $ud_site_id = WP_UD_SITE_ID;
        }

        if( defined( 'WP_UD_SITE_SECRET_TOKEN' ) && WP_UD_SITE_SECRET_TOKEN ) {
          $ud_site_secret_token = WP_UD_SITE_SECRET_TOKEN;
        }

        if( !$ud_site_id || !$ud_site_secret_token ) {
          return false;
        }

        if( $site_id === $ud_site_id && $secret_token === $ud_site_secret_token ) {

          if( isset ( $wp_xmlrpc_server ) ) {
            $wp_xmlrpc_server->error = null;
          }

          return true;

        }

        return false;

      }

      /**
       * Handle Sending Response
       *
       * @author potanin@UD
       * @param array $response
       * @return null
       */
      static public function send( $response = array() ) {

        if( is_wp_error( $response ) ) {
          $response = array(
            'ok' => false,
            'version' => ud_get_wp_rets_client()->get_version(),
            'error' => $response->get_error_message(),
          );
        } else if( !is_array( $response ) ) {
          $response = array();
        }

        $response = wp_parse_args( $response, array(
          'ok' => true,
          'version' => ud_get_wp_rets_client()->get_version(),
          'error' => null,
          'message' => null,
          'data' => array()
        ) );

        // Do nothing if we really are RPC.
        if( defined( 'XMLRPC_REQUEST' ) ) {
          return $response;
        }

        wp_send_json( $response );

      }

      /**
       *
       * The [wpp_import_time] meta is [checked]
       *
       * - rets_ok - we now set [rets_primary_key] but historical it was [wpp::rets_pk] which we still support
       * - rets_mls_number - removed for now since it wasnt always standard.
       * - rets_id - the unique meta key we use to find property. seems to be same as [wpp::rets_pk]
       *
       * /wp-json/wp-rets-client/v1/schedule/listings?order=desc&schedule_id=1463079227
       * /wp-json/wp-rets-client/v1/schedule/listings?type=index
       * /wp-json/wp-rets-client/v1/schedule/listings?type=index&unique=rets_id
       *
       * - type - mls_numbers - will return a summary of post_id -> rets_id. No consideration given to post_status or schedule.
       *
       * @param $request_data
       *
       * @return array
       */
      public function rpc_get_schedule_listings( $request_data ) {
        global $wp_xmlrpc_server, $wpdb;

        $post_data = self::parseRequest( $request_data );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return self::send($post_data);
        }

        // handle wp-json reqests
        if( is_callable( array( $request_data, 'get_param' ) ) ) {

          $post_data = wp_parse_args($post_data, array(
            'type' => $request_data->get_param( 'type' ),
            'per_page' => $request_data->get_param( 'per_page' ),
            'offset' => $request_data->get_param( 'offset' ),
            'post_status' => $request_data->get_param( 'post_status' ),
            'order' => $request_data->get_param( 'order' ),
            'unique' => $request_data->get_param( 'unique' )
          ));

        };

        // Quick summary of all listings, fetched by a meta key.
        if( $post_data['type'] === 'index' ) {
          $_per_page = $post_data[ 'per_page' ];
          $offset = $post_data[ 'offset' ];
          $unique_key = $post_data[ 'unique' ];

          if( is_string( $post_data[ 'post_status' ] ) ) {
            $_post_status = explode( ',', $post_data[ 'post_status' ] );
            $post_status = join( "','", $_post_status );
          }

          if( is_array( $post_data[ 'post_status' ] ) ) {
            $post_status = join( "','", $post_data[ 'post_status' ] );
          }

          $_queries = array(
            "all" => "SELECT post_id, meta_value as unique_field, post_status, post_date, post_modified FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id=ID WHERE meta_key='$unique_key' AND post_status IN ('$post_status') LIMIT $offset, $_per_page;",
            "total" => "SELECT count( post_id ) FROM $wpdb->postmeta LEFT JOIN $wpdb->posts ON post_id=ID WHERE meta_key='$unique_key' AND post_status IN ('$post_status')  LIMIT $offset, $_per_page;"
          );

          $_total = $wpdb->get_var( $_queries['total' ]);

          $_list = $wpdb->get_results( $_queries['all' ] );

          $_result = array(
            'ok' => true,
            'total' => intval($_total),
            'data' => $_list,
            'unique' => $unique_key,
            'offset' => $post_data[ 'offset' ],
            'post_status' => explode( "','", $post_status ),
            'per_page' => $post_data[ 'per_page' ],
            'time' => timer_stop(),
          );

          ud_get_wp_rets_client()->write_log( "Using query [" . $_queries['all'] . "] to get index list." );

          return self::send($_result);
        }

        $_query = array(
          'post_status' => $post_data[ 'post_status' ],
          'post_type' => 'property',
          'posts_per_page' => $post_data[ 'per_page' ],
          'update_post_meta_cache' => false,
          'update_post_term_cache' => false,
          'orderby' => 'modified',
          'order' => strtoupper( $post_data[ 'order' ] ),
          'tax_query' => array(
            array(
              'taxonomy' => 'rets_schedule',
              'field'    => 'slug',
              'terms'    => $post_data[ 'schedule_id' ],
            ),
          ),
        );

        if( $post_data[ 'offset' ] ) {
          $_query['offset'] = $post_data[ 'offset' ];
        }

        //error_log(print_r($_query,true));

        $_query = array_merge( $_query, array(
          'meta_key' => 'wpp_import_time',
          'orderby' => 'meta_value_num',
        ));

        $query = new WP_Query($_query);

        $_listings = array();

        foreach( $query->posts as $_item ) {

          $_wpp_import_time = get_post_meta( $_item->ID, 'wpp_import_time', true ) ;

          $_listings[] = array(
            "id" => $_item->ID,
            //"title" => $_item->post_title,
            "status" => $_item->post_status,
            "created" => $_item->post_date,
            "checked" => $_wpp_import_time,
            "checked_human" => human_time_diff( $_wpp_import_time ) . " ago",
            "modified" => $_item->post_modified,
            "rets" => array(
              "rets_id" => get_post_meta( $_item->ID, 'rets_id', true ),
              //"query" => get_post_meta( $_item->ID, 'rets_query', true ),
              //"primary_key" => get_post_meta( $_item->ID, 'rets_primary_key', true ),
              "primary_key" => get_post_meta( $_item->ID, 'wpp::rets_pk', true ),
              //"primary_key" => get_post_meta( $_item->ID, 'wpp::rets_pk', true ),
              //"mls_number" => get_post_meta( $_item->ID, 'rets_mls_number', true ),
              "modified_datetime" => get_post_meta( $_item->ID, 'rets_modified_datetime', true ),
            )
          );

        }

        return self::send( array(
          'ok' => true,
          'per_page' => $post_data[ 'per_page' ],
          'schedule' => $post_data[ 'schedule_id' ],
          'total' => intval( $query->found_posts ),
          'data' => $_listings,
          'time' => timer_stop()
        ) );

      }

      /**
       * Summary broken down by schedules.
       *
       *
       * @return array
       */
      public function rpc_get_schedule_stats() {

        $_stats = Utility::get_schedule_stats(array(
          'cache' => false
        ));

        return self::send( array(
          'ok' => true,
          'kind' => isset($_GET['kind']) ? $_GET['kind'] : '',
          'message' => 'There are [' . count( $_stats['terms'] ) . '] schedules with [' . $_stats['total'] . '] total listings.',
          'data' => $_stats['data'],
          'time' => timer_stop()
        ) );

      }

      /**
       * Process Taxonomy Cleanup
       *
       * @return array
       */
      public function rpc_cleanup_process( $args ) {
        global $_terms_counts_details;

        $_terms_counts_details = array();
        $response = array();

        $data = self::parseRequest( $args );
        if( !empty( $data['error'] ) ) {
          return self::send( $data );
        }

        Utility::write_log( "rpc_cleanup_process", "info" );

        add_action( 'wrc::_update_terms_counts_helper::done', function( $terms, $query, $error ) {
          global $_terms_counts_details;
          array_push( $_terms_counts_details, array(
            "taxonomy" => $query["taxonomy"],
            "terms" => !empty($terms) ? count( $terms ) : 0,
            "error" => !empty($error) && is_wp_error($error) ? $error->get_error_message() : null
          ) );
        }, 10, 3 );

        $response = Utility::update_terms_counts();
        if( !is_wp_error( $response ) ) {
          $response = array(
            "message" => sprintf( __( "%s taxonomies were cleaned up.", ud_get_wp_rets_client()->domain ), count($_terms_counts_details ) ),
            "data" => $_terms_counts_details
          );
        }

        return self::send( $response );

      }

      /**
       * Basic System Information.
       *
       * @author potanin@UD
       * @param $args
       * @return array
       */
      public function rpc_system_check( $args ) {
        global $wp_xmlrpc_server;

        ud_get_wp_rets_client()->write_log( 'Have system check [wpp.systemCheck] request.', 'debug' );

        $post_data = self::parseRequest( $args );
        if( !empty( $post_data['error'] ) ) {
          return self::send($post_data);
        }

        $_response = self::send(array(
          "ok" => true,
          "version" => ud_get_wp_rets_client()->get_version(),
          "home_url" => home_url(),
          "blog_id" => get_current_blog_id(),
          "themeName" => wp_get_theme()->get( 'Name' ),
          "themeVersion" => wp_get_theme()->get( 'Version' ),
          "stylesheet" => get_option( 'stylesheet' ),
          "template" => get_option( 'stylesheet' ),
          "post_types" => get_post_types(),
          "activePlugins" => Utility::get_plugins(),
          "time" => timer_stop(),
          "support" => array(
            "insert_media",
            "schedule_stats",
            "schedule_listings",
            "get_property",
            "create_property",
            "update_property",
            "edit_property"
          )
        ));

        // not sure if needed here, but seems like good pratice.
        if( function_exists( 'restore_current_blog' ) ) {
          restore_current_blog();
        }

        // Send response to wherever.
        return self::send($_response);

      }

      /**
       * Minimal check.
       *
       */
      public function rpc_system_ping( $args ) {
        global $wp_xmlrpc_server;

        ud_get_wp_rets_client()->write_log( 'Have system ping [wpp.systemPing] request.', 'debug' );

        // swets blog

        $_response = self::send(array(
          "ok" => true,
          "time" => timer_stop()
        ));

        // Send response to wherever.
        return $_response;

      }

      /**
       * Create New Property Object
       *
       * @param $args
       * @return array
       */
      public function rpc_create_property( $args ) {
        global $wp_xmlrpc_server;

        add_filter( 'ep_sync_insert_permissions_bypass', '__return_true', 99, 2 );

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return self::send($post_data);
        }

        do_action('wrc::manage_property::before_update');

        $options = wp_parse_args( isset( $post_data['_options'] ) ? $post_data['_options'] : array(), array(
          'skipTermCounting' => false,
          'skipTermUpdates' => false,
          'skipMediaUpdate' => false,
          'skipSlideshowImages' => true,
          'createWPPAttributes' => false,
          'createWPPTerms' => false
        ));

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.createProperty] request.', 'debug' );

        // Defer term counting until method called again.
        wp_defer_term_counting( true );

        if( isset( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'meta_input' ][ 'wpp::rets_pk' ] = $post_data[ 'meta_input' ][ 'rets_id' ];
        }

        $post_data[ 'meta_input' ][ 'wpp_import_time' ] = time();

        if( !empty( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'ID' ] = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'meta_input' ][ 'rets_id' ] );
        } else {
          return self::send(array( 'ok' => false, 'error' => "Property missing RETS ID.", "data" => $post_data ));
        }

        $_new_post_status = $post_data[ 'post_status' ];

        // set post status to draft since it may be inserting for a while due to large amount of terms
        $post_data[ 'post_status' ] = 'draft';

        if( !empty( $post_data[ 'ID' ] ) ) {
          ud_get_wp_rets_client()->write_log( 'Running wp_insert_post for [' . $post_data[ 'ID' ] . '].', 'debug' );
          $_post = get_post( $post_data[ 'ID' ] );
          // If post_date is not set wp_insert_post function sets the current datetime.
          // So we are preventing to do it by setting already existing post_date. peshkov@UD
          $post_data[ 'post_date' ] = $_post->post_date;
          // Status could be changed manually by administrator.
          // So we are preventing to publish property again in case it was trashed. peshkov@UD
          $post_data[ 'post_status' ] = $_post->post_status;

        } else {
          ud_get_wp_rets_client()->write_log( 'Running wp_insert_post for [new post].', 'debug' );
        }

        $_post_data_tax_input = $post_data['tax_input'];

        $post_data['tax_input'] = array();

        // Ensure we have lat/log meta fields. @note May be a better place to set this up?
        if( ( !isset( $post_data[ 'meta_input' ][ 'latitude' ] ) || !$post_data[ 'meta_input' ][ 'latitude' ] ) && isset( $post_data['_system']['location']['lat'] ) ) {
          $post_data[ 'meta_input' ][ 'latitude' ] = $post_data['_system']['location']['lat'];
          $post_data[ 'meta_input' ][ 'longitude' ] = $post_data['_system']['location']['lon'];
          ud_get_wp_rets_client()->write_log( 'Inserted lat/lon from _system ' . $post_data['_system']['location']['lat'], 'debug' );
        }

        // Backwards compat, can be removed shortly...
        if( isset( $post_data[ 'meta_input' ][ 'rets_media' ] ) && !isset( $post_data[ '_media' ] ) ) {
          $post_data['_media'] = array( 'items' => $post_data[ 'meta_input' ][ 'rets_media' ] );
          unset( $post_data[ 'meta_input' ][ 'rets_media' ] );
        }

        $_post_id = wp_insert_post( $post_data, true );

        if( is_wp_error( $_post_id ) ) {
          ud_get_wp_rets_client()->write_log( 'wp_insert_post error <pre>' . print_r( $_post_id, true ) . '</pre>', 'error' );
          //ud_get_wp_rets_client()->write_log( 'wp_insert_post $post_data <pre>' . print_r( $post_data, true ) . '</pre>', 'error' );

          return self::send(array(
            "ok" => false,
            "message" => "Unable to insert post.",
            "error" => $_post_id->get_error_message()
          ));
        }

        // Insert all the terms and creates taxonomies.
        if( !isset( $options[ 'skipTermUpdates' ] ) || !$options[ 'skipTermUpdates' ] ) {
          Utility::insert_property_terms( $_post_id, $_post_data_tax_input, $post_data );
          do_action( 'wrc::manage_property::taxonomies', $_post_data_tax_input, $options );
        }

        if( !isset( $options[ 'skipMediaUpdate' ] ) || !$options[ 'skipMediaUpdate' ] ) {
          Utility::insert_media( $_post_id, $post_data[ '_media' ] );
        }

        if( !isset( $options[ 'skipSlideshowImages' ] ) || !$options[ 'skipSlideshowImages' ] ) {
          Utility::insert_slideshow_images( $_post_id );
        }

        do_action( 'wrc::manage_property::postmeta', $post_data, $options );

        if( $_post_id ) {
          ud_get_wp_rets_client()->write_log( 'Updating property post [' . $_post_id  . '].', 'debug' );
        } else {
          ud_get_wp_rets_client()->write_log( 'Creating property post [' . $_post_id  . '].', 'debug' );
        }

        $_post_status = ( !empty( $_post ) && !empty( $_post->post_status ) ? $_post->post_status : 'publish' );

        if( isset( $_new_post_status ) ) {
          $_post_status  = $_new_post_status;
        }

        // If post already was added to DB, probably its status was changed manually, so let's set the latest status. peshkov@UD
        $_update_post = wp_update_post( array(
          'ID' => $_post_id,
          'post_status' => $_post_status
        ) );

        if( !is_wp_error( $_update_post ) ) {

          $_permalink = get_the_permalink( $_post_id );

          $_message = array();

          if( isset( $_post_id ) && !is_wp_error( $_post_id ) && isset( $post_data[ 'ID' ] ) && $post_data[ 'ID' ] === $_post_id ) {
            $_message[] = 'Updated property [' . $_post_id  . '] in [' . timer_stop() . '] seconds with [' .$_post_status .'] status using [wpp.createProperty] method.';
          } else {
            $_message[] = 'Created property [' . $_post_id  . '] in [' . timer_stop() . '] seconds with [' .$_post_status .'] status. using [wpp.createProperty] method.';
          }

          if( $_post_status === 'publish' ) {
            $_message[] = 'View at ['.$_permalink.']';
          }

          ud_get_wp_rets_client()->write_log( join( " ", $_message ), 'info' );

          /**
           * Do something after property is published
           */
          do_action( 'wrc_property_published', $_post_id, $post_data );

        } else {
          ud_get_wp_rets_client()->write_log( 'Error publishing post ' . $_post_id, 'error' );
          ud_get_wp_rets_client()->write_log( '<pre>' . print_r( $_update_post, true ) . '</pre>', 'error' );
        }

        if( isset( $options[ 'skipTermCounting' ] ) && $options[ 'skipTermCounting' ] ) {
          ud_get_wp_rets_client()->write_log( 'Skipping term counts for [' . $_post_id  . '] update.', 'debug' );
        } else {
          ud_get_wp_rets_client()->write_log( 'Updating deferred term counts [' . $_post_id  . '].', 'debug' );
          wp_defer_term_counting( false );
          ud_get_wp_rets_client()->write_log( 'Term count complete [' . $_post_id  . '].', 'debug' );
        }

        ud_get_wp_rets_client()->write_log( 'Term counting complete for [' . $_post_id . '].', 'info' );

        ud_get_wp_rets_client()->flush_cache( $_post_id );

        return self::send(array(
          "ok" => true,
          "post_id" => $_post_id,
          "post" => get_post( $_post_id ),
          "permalink" => isset( $_permalink ) ? $_permalink : null
        ));

      }

      /**
       * Update Existing Property
       *
       * @param $args
       * @return array
       */
      public function rpc_update_property( $args ) {
        global $wp_xmlrpc_server, $wpdb;

        add_filter( 'ep_sync_insert_permissions_bypass', '__return_true', 99, 2 );

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return self::send($post_data);
        }

        do_action('wrc::manage_property::before_update');

        $options = wp_parse_args( isset( $post_data['_options'] ) ? $post_data['_options'] : array(), array(
          'skipTermCounting' => false,
          'skipTermUpdates' => false,
          'skipMediaUpdate' => false,
          'skipSlideshowImages' => true,
          'createWPPAttributes' => false,
          'createWPPTerms' => false
        ));

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.updateProperty] request.', 'info' );

        //if( !empty( $post_data[ 'ID' ] ) ) {}

        if( !isset( $post_data[ 'ID' ] ) && !empty( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'ID' ] = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'meta_input' ][ 'rets_id' ] );
        }

        if( !isset( $post_data[ 'ID' ] ) ) {
          return self::send(array( 'ok' => false, 'error' => "Property missing RETS ID.", "data" => $post_data ));
        }

        // update import time
        $post_data[ 'meta_input' ][ 'wpp_import_time' ] = time();

        if( isset( $post_data[ 'post_status' ] ) ) {
          $wpdb->update( $wpdb->posts, array( 'post_status' => $post_data[ 'post_status' ] ), array( 'ID' => $post_data['ID' ] ) );
        }

        if( isset( $post_data[ 'post_title' ] ) ) {
          $wpdb->update( $wpdb->posts, array( 'post_title' => $post_data[ 'post_title' ] ), array( 'ID' => $post_data['ID' ] ) );
        }

        if( isset( $post_data[ 'post_content' ] ) ) {
          $wpdb->update( $wpdb->posts, array( 'post_content' => $post_data[ 'post_content' ] ), array( 'ID' => $post_data['ID' ] ) );
        }

        foreach( (array) $post_data[ 'meta_input' ] as $_meta_key => $_meta_value ) {
          update_post_meta( $post_data['ID' ], $_meta_key, $_meta_value );
        }

        do_action( 'wrc::manage_property::postmeta', $post_data, $options );

        if( (isset( $options[ 'skipTermUpdates' ] ) || !$options[ 'skipTermUpdates' ]) && isset($post_data[ 'tax_input' ]) ) {
          Utility::insert_property_terms( $post_data[ 'ID' ], $post_data[ 'tax_input' ], $post_data );
          do_action( 'wrc::manage_property::taxonomies', $post_data[ 'tax_input' ], $options );
          ud_get_wp_rets_client()->write_log( 'Updated terms.', 'debug' );
        }

        ud_get_wp_rets_client()->write_log( 'Property update finished, clearing cache.', 'debug' );

        //if( function_exists( 'ep_sync_post' ) ) {
        //  ep_sync_post( $post_data[ 'ID' ] );
        //}

        /**
         * Do something after property is published
         */
        do_action( 'wrc_property_published', $post_data[ 'ID' ], $post_data );

        ud_get_wp_rets_client()->flush_cache( $post_data[ 'ID' ] );

        return self::send(array(
          "ok" => true,
          "post_id" => $post_data[ 'ID' ],
          //"post" => get_post( $post_data[ 'ID' ] ),
          "permalink" => get_the_permalink( $post_data[ 'ID' ] ),
          "time" => timer_stop()
        ));

      }

      /**
       * Create or Update Property
       *
       * @param $args
       * @return array
       */
      public function rpc_edit_property( $args ) {
        global $wp_xmlrpc_server;

        add_filter( 'ep_sync_insert_permissions_bypass', '__return_true', 99, 2 );

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return self::send($post_data);
        }

        do_action('wrc::manage_property::before_update');

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.editProperty] request.', 'info' );

        $options = wp_parse_args( isset( $post_data['_options'] ) ? $post_data['_options'] : array(), array(
          'skipTermCounting' => false,
          'skipTermUpdates' => false,
          'skipMediaUpdate' => false,
          'skipSlideshowImages' => true,
          'createWPPAttributes' => false,
          'createWPPTerms' => false,
          'resetPostName' => false
        ));

        // Defer term counting until method called again.
        wp_defer_term_counting( true );

        if( isset( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'meta_input' ][ 'wpp::rets_pk' ] = $post_data[ 'meta_input' ][ 'rets_id' ];
        }

        $post_data[ 'meta_input' ][ 'wpp_import_time' ] = time();

        if( !empty( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'ID' ] = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'meta_input' ][ 'rets_id' ] );
        } else {
          return self::send(array( 'ok' => false, 'error' => "Property missing RETS ID.", "data" => $post_data ));
        }

        $_new_post_status = isset( $post_data[ 'post_status' ] ) ? $post_data[ 'post_status' ] : 'publish';

        // set post status to draft since it may be inserting for a while due to large amount of terms
        $post_data[ 'post_status' ] = 'draft';

        if( !empty( $post_data[ 'ID' ] ) ) {
          ud_get_wp_rets_client()->write_log( 'Running wp_insert_post for [' . $post_data[ 'ID' ] . '].', 'debug' );
          $_post = get_post( $post_data[ 'ID' ] );
          $post_data[ 'post_date' ] = $_post->post_date;
          $post_data[ 'post_status' ] = $_post->post_status;

          // Set empty post name for refreshing it on updating post
          if($options['resetPostName']){
            $post_data[ 'post_name' ] = '';
          }

        } else {
          ud_get_wp_rets_client()->write_log( 'Running wp_insert_post for [new post].', 'debug' );
        }

        $_post_data_tax_input = $post_data['tax_input'];

        //ud_get_wp_rets_client()->write_log( "TAX INPUT: " . json_encode( $_post_data_tax_input ), 'info' );

        $post_data['tax_input'] = array();

        // legacy support.
        if( isset( $post_data[ 'meta_input' ][ 'rets_media' ] ) && !isset( $post_data['_media'] )) {
          $post_data['_media'] = array( 'items' => $post_data[ 'meta_input' ][ 'rets_media' ] );
          unset( $post_data[ 'meta_input' ][ 'rets_media' ] );
        }

        // Ensure we have lat/log meta fields. @note May be a better place to set this up?
        if( ( !isset( $post_data[ 'meta_input' ][ 'latitude' ] ) || !$post_data[ 'meta_input' ][ 'latitude' ] ) && isset( $post_data['_system']['location']['lat'] ) ) {
          $post_data[ 'meta_input' ][ 'latitude' ] = $post_data['_system']['location']['lat'];
          $post_data[ 'meta_input' ][ 'longitude' ] = $post_data['_system']['location']['lon'];
          ud_get_wp_rets_client()->write_log( 'Inserted lat/lon from _system ' . $post_data['_system']['location']['lat'], 'debug' );
        }

        //error_log(print_r($post_data,true));

        $_post_id = wp_insert_post( $post_data, true );

        if( is_wp_error( $_post_id ) ) {
          ud_get_wp_rets_client()->write_log( 'wp_insert_post error <pre>' . print_r( $_post_id, true ) . '</pre>', 'error' );
          ud_get_wp_rets_client()->write_log( 'wp_insert_post $post_data <pre>' . print_r( $post_data, true ) . '</pre>', 'error' );

          return self::send(array(
            "ok" => false,
            "message" => "Unable to insert post.",
            "error" => $_post_id->get_error_message()
          ));
        }

        // Insert all the terms and creates taxonomies.
        if( !isset( $options[ 'skipTermUpdates' ] ) || !$options[ 'skipTermUpdates' ] ) {
          Utility::insert_property_terms( $_post_id, $_post_data_tax_input, $post_data );
          do_action( 'wrc::manage_property::taxonomies', $_post_data_tax_input, $options );
        }

        if( !isset( $options[ 'skipMediaUpdate' ] ) || !$options[ 'skipMediaUpdate' ] ) {
          Utility::insert_media( $_post_id, $post_data[ '_media' ] );
        }

        if( !isset( $options[ 'skipSlideshowImages' ] ) || !$options[ 'skipSlideshowImages' ] ) {
          Utility::insert_slideshow_images( $_post_id );
        }

        do_action( 'wrc::manage_property::postmeta', $post_data, $options );

        if( $_post_id ) {
          ud_get_wp_rets_client()->write_log( 'Updating property post [' . $_post_id  . '].', 'debug' );
        } else {
          ud_get_wp_rets_client()->write_log( 'Creating property post [' . $_post_id  . '].', 'debug' );
        }

        $_post_status = ( !empty( $_post ) && !empty( $_post->post_status ) ? $_post->post_status : 'publish' );

        if( isset( $_new_post_status ) ) {
          $_post_status  = $_new_post_status;
        }

        // If post already was added to DB, probably its status was changed manually, so let's set the latest status. peshkov@UD
        $_update_post = wp_update_post( array(
          'ID' => $_post_id,
          'post_status' => $_post_status
        ) );

        if( !is_wp_error( $_update_post ) ) {

          $_permalink = get_the_permalink( $_post_id );

          $_message = array();

          if( isset( $_post ) && $_post ) {
            $_message[] = 'Updated property [' . $post_data[ 'meta_input' ][ 'rets_id' ] . '], post ID [' . $_post_id  . '] in [' . timer_stop() . '] seconds with [' .$_post_status .'] status.';
          } else {
            $_message[] = 'Created property [' . $post_data[ 'meta_input' ][ 'rets_id' ]  . '], post ID [' . $_post_id  . '] in [' . timer_stop() . '] seconds with [' .$_post_status .'] status.';
          }

          if( $_post_status === 'publish' ) {
            $_message[] = 'View at ['.$_permalink.']';
          }

          ud_get_wp_rets_client()->write_log( join( " ", $_message ), 'info' );

          /**
           * Do something after property is published
           */
          do_action( 'wrc_property_published', $_post_id, $post_data );

        } else {
          ud_get_wp_rets_client()->write_log( 'Error publishing post ' . $_post_id, 'error' );
          ud_get_wp_rets_client()->write_log( '<pre>' . print_r( $_update_post, true ) . '</pre>', 'error' );
        }

        if( isset( $options[ 'skipTermCounting' ] ) && $options[ 'skipTermCounting' ] ) {
          ud_get_wp_rets_client()->write_log( 'Skipping term counts for [' . $_post_id  . '] update.', 'debug' );
        } else {
          ud_get_wp_rets_client()->write_log( 'Updating deferred term counts [' . $_post_id  . '].', 'debug' );
          wp_defer_term_counting( false );
          ud_get_wp_rets_client()->write_log( 'Term count complete [' . $_post_id  . '].', 'debug' );
        }

        $_response = array(
          "ok" => true,
          "post_id" => $_post_id,
          "permalink" => isset( $_permalink ) ? $_permalink : null
        );

        ud_get_wp_rets_client()->write_log( 'Sending [wpp.editProperty] response.', 'debug' );

        ud_get_wp_rets_client()->flush_cache( $_post_id );

        return self::send($_response);

      }

      /**
       * Get property by ID ro mls_number
       *
       *
       *
       *
       * @param $args
       * @return array
       *
       */
      public function rpc_get_property( $args ) {
        global $wp_xmlrpc_server;

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return self::send($post_data);
        }

        if( method_exists( $args, 'get_param' ) ) {
          $post_data['ID'] = $args->get_param( 'ID' );
          $post_data['mls_number'] = $args->get_param( 'mls_number' );
          $post_data['detail'] = $args->get_param( 'detail' );
        }

        // ud_get_wp_rets_client()->write_log( 'Have request [wpp.getProperty] request.', 'debug' );

        $_post_id = null;

        if( is_array($post_data ) && isset( $post_data['ID'] ) ) {
          $_post_id = $post_data['ID'];
        } elseif( is_array( $post_data ) && isset( $post_data[ 'mls_number' ]))  {
          $_post_id = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'mls_number' ] );
        } else {
          $_post_id = $post_data;
        }

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.getProperty] request using [' . $_post_id . '] post_id.', 'debug' );

        $_post = get_post( $_post_id );

        $_resposne = array(
          "ok" => $_post ? true : false,
          "exists" => $_post ? true : false,
          "time" => timer_stop()
        );

        if( $_post ) {
          $_resposne["post_id"] = intval( $_post_id );
          $_resposne["post_status"] = $_post->post_status;

          if( isset( $post_data['detail'] ) ) {
            $_resposne[ "permalink" ] = $_post ? get_permalink( $_post_id ) : null;
            $_resposne[ 'meta_input' ] = array(
              'modification_timestamp' => get_post_meta( $_post_id, 'rets_modified_datetime', true ),
              'rets_listed_date' => get_post_meta( $_post_id, 'rets_listed_date', true ),
              'rets_id' => get_post_meta( $_post_id, 'rets_id', true ),
              'rets_schedule' => get_post_meta( $_post_id, 'rets_schedule', true ),
              'wpp_import_time' => get_post_meta( $_post_id, 'wpp_import_time', true ),
              'mls_number' => get_post_meta( $_post_id, 'mls_number', true ),
              //'attachment' => wp_count_attachments()
            );
          }

        }

        ud_get_wp_rets_client()->write_log( 'Completed [wpp.getProperty] request.', 'debug' );

        return self::send($_resposne);

      }

      /**
       * Delete Property.
       *
       * @param $args
       * @return array
       */
      public function rpc_delete_property( $args ) {
        global $wp_xmlrpc_server, $wpdb, $wrc_rets_id;

        add_filter( 'ep_sync_delete_permissions_bypass', '__return_true', 99, 2 );

        $data = self::parseRequest( $args );

        ud_get_wp_rets_client()->logfile = !empty( $data[ 'logfile' ] ) ? $data[ 'logfile' ] : ud_get_wp_rets_client()->logfile;

        if( !empty( $wp_xmlrpc_server->error ) ) {
          return self::send($data);
        }

        $response = array(
          "ok" => true,
          "request" => $data
        );

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.deleteProperty].', 'info' );

        $post_id = 0;

        if( !empty( $data[ 'post_id' ] ) ) {
          $post_id = $data[ 'post_id' ];
          $wrc_rets_id = get_post_meta( $post_id, 'rets_id', true );
        } else if( !empty( $data[ 'rets_id' ] ) ) {
          $wrc_rets_id = $data[ 'rets_id' ];
          $post_id = ud_get_wp_rets_client()->find_property_by_rets_id( $wrc_rets_id );
          /**
           * It's normal behaviour to not detect post ID when we know only MLS ID,
           * because the MLS property may not fetch poller's filter and, as fact, does not exist in WordPress at all
           * so we just break here without any error.
           * peshkov@UD
           */
          if( !is_numeric( $post_id ) ){
            ud_get_wp_rets_client()->write_log( "No post found by MLS ID [$wrc_rets_id]. Ignoring [wpp.deleteProperty] request.", 'info' );
            return self::send($response);
          }
        }

        if( !$wrc_rets_id || !is_numeric( $post_id ) ) {
          ud_get_wp_rets_client()->write_log(  'No Post found', 'info' );
          $response['ok'] = false;
          return self::send($response);
        }

        /**
         * Disable term counting
         */
        // wp_defer_term_counting( true );

        ud_get_wp_rets_client()->write_log( "Checking post ID [$post_id]" );

        do_action( 'wrc_before_property_deleted', $post_id );

        if( FALSE === get_post_status( $post_id ) ) {

          ud_get_wp_rets_client()->write_log( "Post ID [$post_id] does not exist. Removing its postmeta and terms if exist.", 'info' );

          // Looks like post was deleted. But postmeta ( and probably terms ) still exist... Remove it.
          wp_delete_object_term_relationships( $post_id, get_object_taxonomies( 'property' ) );

          $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post_id ) );

          ud_get_wp_rets_client()->write_log( "Removed postmeta and terms for Property [{$post_id}].", 'info' );

          do_action( 'wrc_property_deleted', $post_id );

        } else {

          ud_get_wp_rets_client()->write_log( "Post [$post_id] found. Removing it.", "info" );

          if( wp_delete_post( $post_id, false ) ) {
            do_action( 'wrc_property_deleted', $post_id );
            $response[ "ok" ] = true;
          } else {
            ud_get_wp_rets_client()->write_log( "Property [{$post_id}] could not be removed", 'debug' );
            $response[ "ok" ] = false;
          }

        }

        ud_get_wp_rets_client()->write_log( "Finished removing [$post_id].  MLS ID [$wrc_rets_id]", "info" );

        $response['time' ] = timer_stop();

        ud_get_wp_rets_client()->flush_cache( $post_id );

        return self::send($response);

      }

      /**
       * Quick status change, real removal to occur later.
       *
       * @param $args
       * @return array
       */
      public function rpc_trash_property( $args ) {
        global $wp_xmlrpc_server, $wpdb, $wrc_rets_id;

        add_filter( 'ep_sync_insert_permissions_bypass', '__return_true', 99, 2 );
        add_filter( 'ep_sync_delete_permissions_bypass', '__return_true', 99, 2 );

        $data = self::parseRequest( $args );

        ud_get_wp_rets_client()->logfile = !empty( $data[ 'logfile' ] ) ? $data[ 'logfile' ] : ud_get_wp_rets_client()->logfile;

        if( !empty( $wp_xmlrpc_server->error ) ) {
          return self::send($data);
        }

        $response = array(
          "ok" => true,
          "request" => $data
        );

        $post_id = 0;

        if( !empty( $data[ 'post_id' ] ) ) {
          $post_id = $data[ 'post_id' ];
          $wrc_rets_id = get_post_meta( $post_id, 'rets_id', true );
        } else if( !empty( $data[ 'rets_id' ] ) ) {
          $wrc_rets_id = $data[ 'rets_id' ];
          $post_id = ud_get_wp_rets_client()->find_property_by_rets_id( $wrc_rets_id );
          /**
           * It's normal behaviour to not detect post ID when we know only MLS ID,
           * because the MLS property may not fetch poller's filter and, as fact, does not exist in WordPress at all
           * so we just break here without any error.
           * peshkov@UD
           */
          if( !is_numeric( $post_id ) ){
            ud_get_wp_rets_client()->write_log( "No post found by MLS ID [$wrc_rets_id]. Ignoring [wpp.trashProperty] request.", 'info' );
            return self::send($response);
          }
        }

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.trashProperty]. Post id: ' . $post_id, 'info' );

        if( !$post_id || !is_numeric( $post_id ) ) {
          ud_get_wp_rets_client()->write_log(  'No post ID detected', 'info' );
          $response['ok'] = false;
          return self::send($response);
        }

        ud_get_wp_rets_client()->write_log( "Checking post ID [$post_id].", 'info' );

        // We must do 'trash' post using native function, instead of direct SQL requests....
        // because of different bugs and issues with property status on end.... peshkov@UD
        wp_trash_post( $post_id );

        ud_get_wp_rets_client()->write_log( $wpdb->last_error, 'info' );

        ud_get_wp_rets_client()->write_log( "Property [$post_id] trashed. MLS ID [$wrc_rets_id]", 'info' );

        $response['time' ] = timer_stop();

        ud_get_wp_rets_client()->flush_cache( $post_id );

        return self::send($response);

      }

      /**
       * Get post ID by mls number
       *
       * @param $args
       * @return array
       */
      public function rpc_get_post_id_by_mls_id( $args ) {
        global $wp_xmlrpc_server, $wpdb;

        $data = self::parseRequest( $args );

        if( !empty( $wp_xmlrpc_server->error ) ) {
          return self::send($data);
        }

        $response = array(
          "ok" => true,
          "request" => $data
        );

        $post_id = 0;

        if( isset($data['id']) && !empty( $data[ 'id' ] ) ) {
          $post_id = $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='".( defined( 'RETS_ID_KEY' ) ? RETS_ID_KEY : 'wpp::rets_pk' )."' AND meta_value={$data['id']};" );
        }

        $response['post_id'] = $post_id;

        ud_get_wp_rets_client()->write_log( 'Have [wpp.getPostIdByMlsId] request.', 'info' );

        if( !$post_id || !is_numeric( $post_id ) ) {
          ud_get_wp_rets_client()->write_log(  'No post ID provided for mls id:' . $data['id'] , 'info' );
          $response['ok'] = false;
          return self::send($response);
        }

        ud_get_wp_rets_client()->write_log( 'Returned post_id:' . $post_id, 'info' );

        return self::send($response);

      }

      /**
       * Insert media for a property.
       *
       * @param $args
       * @return array
       */
      public function rpc_insert_media( $args ) {

        add_filter( 'ep_sync_insert_permissions_bypass', '__return_true', 99, 2 );

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          ud_get_wp_rets_client()->write_log( 'Failed [wpp.insertMedia] request.', 'debug' );
          return self::send($post_data);
        }

        if( is_callable( array( $args, 'get_param' ) ) ) {

          $post_data = wp_parse_args($post_data, array_filter(array(
            'post_id' => $args->get_param( 'post_id' ),
            'rets_id' => $args->get_param( 'rets_id' ),
            '_media' => $args->get_param( 'media' )
          )));

        };

        $post_id = null;

        if( !empty( $post_data['post_id' ] ) ) {
          $post_id = $post_data['post_id'];
        } else if( !empty( $post_data['ID' ] ) ) {
          $post_id = $post_data['ID' ];
        }

        // try go get post_id by mls_number, if it passed
        if( !$post_id ) {
          $post_id = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'rets_id' ] );
        }

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.insertMedia] request for ['  . $post_id . '].', 'info' );

        if( !$post_id || empty( $post_data[ '_media' ] ) ) {
          return array( 'ok' => false );
        }

        $_result = Utility::insert_media( $post_id, $post_data[ '_media' ] );

        return self::send(array(
          'ok' => true,
          'result' => $_result
        ));

      }

      /**
       * Removes properties with duplicated MLS
       *
       * @param $args
       * @return array
       */
      public function rpc_remove_duplicated_mls( $args ) {
        global $wp_xmlrpc_server, $wpdb;

        $data = self::parseRequest( $args );
        if( !empty( $wp_xmlrpc_server->error ) ) {
          return self::send($data);
        }

        ud_get_wp_rets_client()->logfile = !empty( $data[ 'logfile' ] ) ? $data[ 'logfile' ] : ud_get_wp_rets_client()->logfile;

        $response = array(
          "ok" => true,
          "total" => 0,
          "request" => $data,
          "removed" => array(),
          "logs" => array(),
        );

        ud_get_wp_rets_client()->write_log( 'Have wpp.removeDuplicatedMLS request', "info" );

        // Find all RETS IDs that have multiple posts associated with them.
        $query = "SELECT meta_value, COUNT(*) c FROM $wpdb->postmeta WHERE meta_key='rets_id' GROUP BY meta_value HAVING c > 1 ORDER BY c DESC";
        $_duplicates = $wpdb->get_col( $query );

        //$response[ 'query' ] = $wpdb->last_query;

        $log = "Found [" . count( $_duplicates ) . "] RETS IDs which have duplicated properties";
        array_push( $response[ 'logs' ], $log );
        ud_get_wp_rets_client()->write_log( $log, 'debug' );

        if( empty( $_duplicates ) ) {
          return self::send($response);
        } else {
          $response[ 'total' ] = count( $_duplicates );
        }

        $step = 0;

        foreach( $_duplicates as $rets_id ) {

          $post_ids = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='rets_id' AND meta_value='{$rets_id}' ORDER BY post_id DESC;" );

          $log = "Found [" . ( count( $post_ids ) - 1 ) . "] duplications for RETS ID [{$rets_id}]";
          array_push( $response[ 'logs' ], $log );
          ud_get_wp_rets_client()->write_log( $log, 'info' );

          $primary = 0;

          foreach( $post_ids as $post_id ) {

            /**
             * Disable term counting
             */
            wp_defer_term_counting( true );

            if( FALSE === get_post_status( $post_id ) ) {

              ud_get_wp_rets_client()->write_log( "Checking post ID [$post_id].", 'info' );

              // Looks like post was deleted. But postmeta ( and probably terms ) still exist... Remove it.
              wp_delete_object_term_relationships( $post_id, get_object_taxonomies( 'property' ) );
              $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post_id ) );

              $log = "RETS ID [{$rets_id}]. Removed postmeta and terms for Property [{$post_id}].";
              array_push( $response[ 'logs' ], $log );
              ud_get_wp_rets_client()->write_log( $log, 'debug' );

            } else {

              if( !$primary ) {

                $primary = $post_id;
                continue;

              } else {

                ud_get_wp_rets_client()->write_log( "Checking post ID [$post_id]", 'info' );

                if( wp_delete_post( $post_id, true ) ) {
                  $log = "RETS ID [{$rets_id}]. Removed Property [{$post_id}]";
                } else {
                  $log = "RETS ID [{$rets_id}]. Property [{$post_id}] could not be removed";
                }

                array_push( $response[ 'logs' ], $log );
                ud_get_wp_rets_client()->write_log( $log, 'debug' );

              }

            }

            // Maybe remove post from ES.
            if( !empty( $data[ 'es_client' ] ) ) {

              ud_get_wp_rets_client()->write_log( "Removing post ID [$post_id] from Elasticsearch", 'info' );

              wp_remote_request( trailingslashit( $data[ 'es_client' ] ) . $post_id, array(
                'method' => 'DELETE',
                'blocking' => false
              ) );
            }

            array_push( $response[ 'removed' ], $post_id );

          }

          $step++;

          if( !empty( $data[ 'limit' ] ) && $data[ 'limit' ] <= $step ) {
            break;
          }

        }

        // @todo: probably term counting should be executed via different way. Because it takes forever to update counts.... peshkov@UD
        wp_defer_term_counting( false );

        ud_get_wp_rets_client()->write_log( 'wpp.removeDuplicatedMLS Done', 'info' );

        return self::send($response);

      }

      /**
       * Create Modified Histogram
       *
       *
       * interval: year, month, week, day
       *
       * rets_modified_datetime - 2016-08-05T20:28:00
       *
       * curl "localhost/wp-admin/admin-ajax.php?action=/rets-ci/histogram&schedule=1460050294&2016-07-01&endDate=2016-07-06"
       *
       *
       *
       * @param $args
       * @author potanin@UD
       * @return null
       */
      public function rpc_get_modified_histogram( $args = null ) {
        global $wpdb;

        // not sure if we can query by date ranges since its meta
        $args = self::parseRequest( $args, array(
          'interval' => 'day',
          'startDate' => '2016-07-01',
          'endDate' => '2016-08-01',
          'dateMetaField' => 'rets_modified_datetime', // rets_listed_date
          'cacheKey' => null,
          'noCache' => false
        ) );

        $args[ 'cacheKey' ] = join( '-', array( 'histogram', $args[ 'schedule' ], str_replace( '-', '', $args[ 'startDate' ] ), str_replace( '-', '', $args[ 'endDate' ] ) ) );

        $_range = array();

        // send cached histogram
        if( !$args[ 'noCache' ] && wp_cache_get( $args[ 'cacheKey' ], 'wpp' ) ) {
          return self::send( array(
            "schedule" => $args['schedule'],
            "data" => wp_cache_get( $args[ 'cacheKey' ], 'wpp' ),
            "time" => timer_stop(),
            "cached" => true
          ) );
        }

        // Build an array of dates ranging from start to end. Then get modifiec counts for each of those days.
        foreach( Utility::build_date_range( $args[ 'startDate' ], $args[ 'endDate' ] ) as $startDate ) {
          $_range[ $startDate ] = count( Utility::query_modified_listings( array( "startDate" => $startDate, 'schedule' => $args[ 'schedule' ], 'dateMetaField' => $args['dateMetaField'] ) ) );
        }

        wp_cache_set( $args[ 'cacheKey' ], $_range, 'wpp' );

        // send non-cached histogram
        return self::send( array(
          "schedule" => $args['schedule'],
          "data" => $_range,
          "time" => timer_stop(),
          "cached" => false
        ) );

        // die( 'Found [' . count( $query->posts ) . '] posts for [' . $data['schedule'] . '] schedule, using [' . DB_NAME . '] database in [' . timer_stop() . '] seconds.' );

      }

      /**
       * Allows you to get property detail histogram properties.
       *
       *
       *
       * @author potanin@UD
       * @param null $args
       * @return null
       */
      public function rpc_get_modified_detail( $args = null ) {

        // not sure if we can query by date ranges since its meta
        $args = self::parseRequest( $args, array(
          'interval' => 'day',
          'startDate' => '2016-07-01',
          'endDate' => '2016-08-01',
          'cacheKey' => null,
          'noCache' => false
        ) );

        $_test_range = Utility::query_modified_listings( array(
          "startDate" => $args[ 'startDate' ],
          "endDate" => $args[ 'endDate' ],
          'schedule' => $args[ 'schedule' ]
        ));

        $_detail = array();

        foreach( $_test_range as $data ) {

          $_detail[] = array(
            'post_id' => $data->ID,
            'schedule' => get_post_meta( $data->ID, 'wpp_import_schedule_id', true ),
            'L_UpdateDate' => get_post_meta( $data->ID, 'rets_modified_datetime', true ),
            'L_ListingID' => get_post_meta( $data->ID, 'rets_id', true )
          );

        }

        return self::send($_detail);

      }

    }

  }

}