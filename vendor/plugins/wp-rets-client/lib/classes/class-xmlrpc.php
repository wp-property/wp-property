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
       * Add custom XML-RPC methods
       *
       * @param $_methods
       * @return mixed
       */
      public function xmlrpc_methods( $_methods ) {

        $_methods[ 'wpp.systemCheck' ] = array( $this, 'rpc_system_check' );
        $_methods[ 'wpp.deleteProperty' ] = array( $this, 'rpc_delete_property' );
        $_methods[ 'wpp.editProperty' ] = array( $this, 'rpc_edit_property' );
        $_methods[ 'wpp.createProperty' ] = array( $this, 'create_property' );
        $_methods[ 'wpp.updateProperty' ] = array( $this, 'update_property' );
        $_methods[ 'wpp.removeDuplicatedMLS' ] = array( $this, 'rpc_remove_duplicated_mls' );
        $_methods[ 'wpp.modifiedHistogram' ] = array( $this, 'rpc_get_modified_histogram' );
        $_methods[ 'wpp.flushCache' ] = array( $this, 'rpc_flush_cache' );

        return $_methods;
      }

      /**
       * Initialize REST routes for our internal XML-RPC routes.
       *
       * @author potanin@UD
       */
      public function api_init( ) {

        register_rest_route( 'wp-rets-client/v1', '/systemCheck', array(
          'methods' => 'GET',
          'callback' => array( $this, 'rpc_system_check' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/deleteProperty', array(
          'methods' => 'DELETE',
          'callback' => array( $this, 'rpc_delete_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/editProperty', array(
          'methods' => 'POST',
          'callback' => array( $this, 'rpc_edit_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/updateProperty', array(
          'methods' => 'POST',
          'callback' => array( $this, 'update_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/createProperty', array(
          'methods' => 'POST',
          'callback' => array( $this, 'create_property' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/removeDuplicates', array(
          'methods' => 'POST',
          'callback' => array( $this, 'rpc_remove_duplicated_mls' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/getHistogram', array(
          'methods' => 'GET',
          'callback' => array( $this, 'rpc_get_modified_histogram' ),
        ) );

        register_rest_route( 'wp-rets-client/v1', '/flushCache', array(
          'methods' => 'GET',
          'callback' => array( $this, 'rpc_flush_cache' ),
        ) );

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

              return array(
                'ok' => false,
                'error' => "Unable to login.",
                'username' => isset( $_SERVER[ 'HTTP_X_ACCESS_USER' ] ) ? $_SERVER[ 'HTTP_X_ACCESS_USER' ] : '',
                'password' => isset( $_SERVER[ 'HTTP_X_ACCESS_PASSWORD' ] ) ? $_SERVER[ 'HTTP_X_ACCESS_PASSWORD' ] : '',
              );

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

        if( !get_site_option( 'ud_site_id' ) || !get_site_option( 'ud_site_secret_token' ) ) {
          return false;
        }

        if( $site_id === get_site_option( 'ud_site_id' ) && $secret_token === get_site_option( 'ud_site_secret_token' ) ) {

          if( isset ( $wp_xmlrpc_server ) ) {
            $wp_xmlrpc_server->error = null;
          }

          return true;

        }

        return false;

      }

      /**
       * Return list of plugins.
       *
       * @author potanin@UD
       * @param $args
       * @return array
       */
      static public function get_plugins( $args = null ) {

        $_active = wp_get_active_and_valid_plugins();
        $result = array();

        foreach( $_active as $_plugin ) {
          $result[] = basename( dirname($_plugin) );
        }

        return $result;
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

        // swets blog

        $post_data = self::parseRequest( $args );
        if( !empty( $post_data['error'] ) ) {
          return $post_data;
        }

        $_response = self::send(array(
          "ok" => true,
          "home_url" => home_url(),
          "blog_id" => get_current_blog_id(),
          "themeName" => wp_get_theme()->get( 'Name' ),
          "themeVersion" => wp_get_theme()->get( 'Version' ),
          "stylesheet" => get_option( 'stylesheet' ),
          "template" => get_option( 'stylesheet' ),
          "post_types" => get_post_types(),
          "activePlugins" => self::get_plugins(),
          "support" => array(
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
        return $_response;

      }

      /**
       * Create New Property Object
       *
       * @param $args
       * @return array
       */
      public function create_property( $args ) {
        global $wp_xmlrpc_server;

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return $post_data;
        }

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
          return array( 'ok' => false, 'error' => "Property missing RETS ID.", "data" => $post_data );
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

        $_post_id = wp_insert_post( $post_data, true );

        if( is_wp_error( $_post_id ) ) {
          ud_get_wp_rets_client()->write_log( 'wp_insert_post error <pre>' . print_r( $_post_id, true ) . '</pre>', 'error' );
          ud_get_wp_rets_client()->write_log( 'wp_insert_post $post_data <pre>' . print_r( $post_data, true ) . '</pre>', 'error' );

          return array(
            "ok" => false,
            "message" => "Unable to insert post.",
            "error" => $_post_id->get_error_message()
          );
        }

        // Insert all the terms and creates taxonomies.
        // self::insert_terms( $_post_id, $_post_data_tax_input, $post_data );

        if( !empty( $post_data[ 'meta_input' ][ 'rets_media' ] ) && is_array( $post_data[ 'meta_input' ][ 'rets_media' ] ) ) {

          $_already_attached_media = array();
          $_new_media = array();

          $attached_media = get_attached_media( 'image', $_post_id );

          // get simple url litst of already attached media
          if( $attached_media ) {

            foreach( (array)$attached_media as $_attached_media_id => $_media ) {
              $_already_attached_media[ $_attached_media_id ] = $_media->guid;
            }

          }

          // delete all old attachments if the count of new media doesn't match up with old media
          if( count( $attached_media ) != count( $post_data[ 'meta_input' ][ 'rets_media' ] ) ) {

            ud_get_wp_rets_client()->write_log( 'For ['.$_post_id.'] property media count has changed. Before ['.count( $attached_media ).'], now ['.count( $post_data[ 'meta_input' ][ 'rets_media' ] ).'].', 'debug' );

            //ud_get_wp_rets_client()->write_log( 'Deleting [' .  $_single_media_item->ID . '] media item.', 'debug' );
            foreach( $attached_media as $_single_media_item ) {
              ud_get_wp_rets_client()->write_log( 'Deleting [' .  $_single_media_item->ID . '] media item. (Skipping)', 'debug' );
              // wp_delete_attachment( $_single_media_item->ID, true );
            }


          }

          foreach( $post_data[ 'meta_input' ][ 'rets_media' ] as $media ) {

            if( in_array( $media[ 'url' ], $_already_attached_media ) ) {
              ud_get_wp_rets_client()->write_log( "Skipping [" . $media['url'] . "] because it's already attached to [" . $_post_id . "]", 'debug' );
            }

            // attach media if a URL is set and it isn't already attached

            $filetype = wp_check_filetype( basename( $media[ 'url' ] ), null );

            $attachment = array(
              'guid' => $media[ 'url' ],
              'post_mime_type' => ( !empty( $filetype[ 'type' ] ) ? $filetype[ 'type' ] : 'image/jpeg' ),
              'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $media[ 'url' ] ) ),
              'post_content' => '',
              'post_status' => 'inherit',
              'menu_order' => $media[ 'order' ] ? ( (int)$media[ 'order' ] ) : null
            );

            $attach_id = wp_insert_attachment( $attachment, $media[ 'url' ], $_post_id );

            $_new_media[] = $media[ 'url' ];

            update_post_meta( $attach_id, '_is_remote', '1' );

            // set the item with order of 1 as the thumbnail
            if( (int)$media[ 'order' ] === 1 ) {
              //set_post_thumbnail( $_post_id, $attach_id );

              // No idea why but set_post_thumbnail() fails routinely as does update_post_meta, testing this method.
              delete_post_meta( $_post_id, '_thumbnail_id' );
              $_thumbnail_setting = add_post_meta( $_post_id, '_thumbnail_id', (int)$attach_id );

              if( $_thumbnail_setting ) {
                ud_get_wp_rets_client()->write_log( 'Setting thumbnail [' . $attach_id . '] to post [' . $_post_id . '] because it has order of 1, result: ', 'debug' );
              } else {
                ud_get_wp_rets_client()->write_log( 'Error! Failured at setting thumbnail [' . $attach_id . '] to post [' . $_post_id . ']', 'error' );
              }

              //die('dying early!' );
            }

            // old logic of first checking that a new media url exists
            if( !empty( $media[ 'url' ] ) && !in_array( $media[ 'url' ], $_already_attached_media ) ) {
            }

          }

          // newly inserted media is in $_new_media
          // old media is in $_already_attached_media
          // we get media that was attached before but not in new media

        }

        // We dont need to store this once the Media inserting is working well, besides we can always get it from api. - potanin@UD
        if( isset( $post_data[ 'meta_input' ][ 'rets_media' ] ) ) {
          unset( $post_data[ 'meta_input' ][ 'rets_media' ] );
        }

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
          do_action( 'wrc_property_published', $_post_id );

        } else {
          ud_get_wp_rets_client()->write_log( 'Error publishing post ' . $_post_id, 'error' );
          ud_get_wp_rets_client()->write_log( '<pre>' . print_r( $_update_post, true ) . '</pre>', 'error' );
        }

        // Term counts can/may be updated now.
        wp_defer_term_counting( false );

        ud_get_wp_rets_client()->write_log( 'Term counting complete for [' . $_post_id . '].', 'info' );
        return array(
          "ok" => true,
          "post_id" => $_post_id,
          "post" => get_post( $_post_id ),
          "permalink" => isset( $_permalink ) ? $_permalink : null
        );

      }

      /**
       * Update Existing Property
       *
       * @param $args
       * @return array
       */
      public function update_property( $args ) {
        global $wp_xmlrpc_server;

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return $post_data;
        }

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.updateProperty] request.', 'debug' );

        if( !empty( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'ID' ] = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'meta_input' ][ 'rets_id' ] );
        } else {
          return array( 'ok' => false, 'error' => "Property missing RETS ID.", "data" => $post_data );
        }

        // ud_get_wp_rets_client()->write_log( print_r($post_data,true), 'debug' );

        self::insert_property_terms( $post_data[ 'ID' ], $post_data['tax_input'], $post_data );

        return array(
          "ok" => true,
          "post_id" => $post_data[ 'ID' ],
          "post" => get_post( $post_data[ 'ID' ] ),
          "permalink" => get_the_permalink( $post_data[ 'ID' ] )
        );
      }

      /**
       * Create or Update Property
       *
       * @param $args
       * @return array
       */
      public function rpc_edit_property( $args ) {
        global $wp_xmlrpc_server;

        $post_data = self::parseRequest( $args );

        if( ( isset( $wp_xmlrpc_server ) && !empty( $wp_xmlrpc_server->error ) ) || isset( $post_data['error'] ) ) {
          return $post_data;
        }

        ud_get_wp_rets_client()->write_log( 'Have request [wpp.editProperty] request.', 'debug' );

        // Defer term counting until method called again.
        wp_defer_term_counting( true );

        if( isset( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'meta_input' ][ 'wpp::rets_pk' ] = $post_data[ 'meta_input' ][ 'rets_id' ];
        }

        $post_data[ 'meta_input' ][ 'wpp_import_time' ] = time();

        if( !empty( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'ID' ] = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'meta_input' ][ 'rets_id' ] );
        } else {
          return array( 'ok' => false, 'error' => "Property missing RETS ID.", "data" => $post_data );
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

        $_post_id = wp_insert_post( $post_data, true );

        if( is_wp_error( $_post_id ) ) {
          ud_get_wp_rets_client()->write_log( 'wp_insert_post error <pre>' . print_r( $_post_id, true ) . '</pre>', 'error' );
          ud_get_wp_rets_client()->write_log( 'wp_insert_post $post_data <pre>' . print_r( $post_data, true ) . '</pre>', 'error' );

          return array(
            "ok" => false,
            "message" => "Unable to insert post.",
            "error" => $_post_id->get_error_message()
          );
        }

        // Insert all the terms and creates taxonomies.
        self::insert_property_terms( $_post_id, $_post_data_tax_input, $post_data );

        if( !empty( $post_data[ 'meta_input' ][ 'rets_media' ] ) && is_array( $post_data[ 'meta_input' ][ 'rets_media' ] ) ) {

          $_already_attached_media = array();
          $_new_media = array();

          $attached_media = get_attached_media( 'image', $_post_id );

          // get simple url litst of already attached media
          if( $attached_media ) {

            foreach( (array)$attached_media as $_attached_media_id => $_media ) {
              $_already_attached_media[ $_attached_media_id ] = $_media->guid;
            }

          }

          // delete all old attachments if the count of new media doesn't match up with old media
          if( count( $attached_media ) != count( $post_data[ 'meta_input' ][ 'rets_media' ] ) ) {

            ud_get_wp_rets_client()->write_log( 'For ['.$_post_id.'] property media count has changed. Before ['.count( $attached_media ).'], now ['.count( $post_data[ 'meta_input' ][ 'rets_media' ] ).'].', 'debug' );

            //ud_get_wp_rets_client()->write_log( 'Deleting [' .  $_single_media_item->ID . '] media item.', 'debug' );
            foreach( $attached_media as $_single_media_item ) {
              ud_get_wp_rets_client()->write_log( 'Deleting [' .  $_single_media_item->ID . '] media item.', 'debug' );
              wp_delete_attachment( $_single_media_item->ID, true );
            }


          }

          foreach( $post_data[ 'meta_input' ][ 'rets_media' ] as $media ) {

            if( in_array( $media[ 'url' ], $_already_attached_media ) ) {
              ud_get_wp_rets_client()->write_log( "Skipping [" . $media['url'] . "] because it's already attached to [" . $_post_id . "]", 'debug' );
            }

            // attach media if a URL is set and it isn't already attached

            $filetype = wp_check_filetype( basename( $media[ 'url' ] ), null );

            $attachment = array(
              'guid' => $media[ 'url' ],
              'post_mime_type' => ( !empty( $filetype[ 'type' ] ) ? $filetype[ 'type' ] : 'image/jpeg' ),
              'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $media[ 'url' ] ) ),
              'post_content' => '',
              'post_status' => 'inherit',
              'menu_order' => $media[ 'order' ] ? ( (int)$media[ 'order' ] ) : null
            );

            $attach_id = wp_insert_attachment( $attachment, $media[ 'url' ], $_post_id );

            $_new_media[] = $media[ 'url' ];

            update_post_meta( $attach_id, '_is_remote', '1' );

            // set the item with order of 1 as the thumbnail
            if( (int)$media[ 'order' ] === 1 ) {
              //set_post_thumbnail( $_post_id, $attach_id );

              // No idea why but set_post_thumbnail() fails routinely as does update_post_meta, testing this method.
              delete_post_meta( $_post_id, '_thumbnail_id' );
              $_thumbnail_setting = add_post_meta( $_post_id, '_thumbnail_id', (int)$attach_id );

              if( $_thumbnail_setting ) {
                ud_get_wp_rets_client()->write_log( 'Setting thumbnail [' . $attach_id . '] to post [' . $_post_id . '] because it has order of 1, result: ', 'debug' );
              } else {
                ud_get_wp_rets_client()->write_log( 'Error! Failured at setting thumbnail [' . $attach_id . '] to post [' . $_post_id . ']', 'error' );
              }

              //die('dying early!' );
            }

            // old logic of first checking that a new media url exists
            if( !empty( $media[ 'url' ] ) && !in_array( $media[ 'url' ], $_already_attached_media ) ) {
            }

          }

          // newly inserted media is in $_new_media
          // old media is in $_already_attached_media
          // we get media that was attached before but not in new media

        }

        // We dont need to store this once the Media inserting is working well, besides we can always get it from api. - potanin@UD
        if( isset( $post_data[ 'meta_input' ][ 'rets_media' ] ) ) {
          unset( $post_data[ 'meta_input' ][ 'rets_media' ] );
        }

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
            $_message[] = 'Updated property [' . $_post_id  . '] in [' . timer_stop() . '] seconds with [' .$_post_status .'] status.';
          } else {
            $_message[] = 'Created property [' . $_post_id  . '] in [' . timer_stop() . '] seconds with [' .$_post_status .'] status.';
          }

          if( $_post_status === 'publish' ) {
            $_message[] = 'View at ['.$_permalink.']';
          }

          ud_get_wp_rets_client()->write_log( join( " ", $_message ), 'info' );

          /**
           * Do something after property is published
           */
          do_action( 'wrc_property_published', $_post_id );

        } else {
          ud_get_wp_rets_client()->write_log( 'Error publishing post ' . $_post_id, 'error' );
          ud_get_wp_rets_client()->write_log( '<pre>' . print_r( $_update_post, true ) . '</pre>', 'error' );
        }

        // Term counts can/may be updated now.
        wp_defer_term_counting( false );

        return array(
          "ok" => true,
          "post_id" => $_post_id,
          "post" => get_post( $_post_id ),
          "permalink" => isset( $_permalink ) ? $_permalink : null
        );

      }

      /**
       * Creates taxonomies and terms. Also handles hierarchies.
       *
       * @author potanin@UD
       * @param $_post_id
       * @param $_post_data_tax_input
       * @param array $post_data
       */
      static public function insert_property_terms( $_post_id, $_post_data_tax_input, $post_data = array() ) {

        ud_get_wp_rets_client()->write_log( "Have [" . count( $_post_data_tax_input ) . "] taxonomies to process.", 'debug' );

        foreach( (array) $_post_data_tax_input as $tax_name => $tax_tags ) {
          ud_get_wp_rets_client()->write_log( "Starting to process [$tax_name] taxonomy.", 'debug' );

          // Ignore these taxonomies if we support [wpp_listing_location].
          if( defined( 'WPP_FEATURE_FLAG_WPP_LISTING_LOCATION' ) && WPP_FEATURE_FLAG_WPP_LISTING_LOCATION && in_array( $tax_name, array( 'rets_location_state', 'rets_location_county', 'rets_location_city', 'rets_location_route' ) ) ) {
            ud_get_wp_rets_client()->write_log( "Skipping [$tax_name] taxonomy, we have [wpp_listing_location] enabled.", 'debug' );
            continue;
          }

          // Avoid hierarchical taxonomies since they do not allow simple-value passing.
          WPP_F::verify_have_system_taxonomy( $tax_name, array( 'hierarchical' => false ) );

          // If WP-Property location flag is enabled, and we're doing the [wpp_listing_location] taxonomy, and the WPP_F::update_location_terms method is callable, process our wpp_listing_location terms.
          if( defined( 'WPP_FEATURE_FLAG_WPP_LISTING_LOCATION' ) && WPP_FEATURE_FLAG_WPP_LISTING_LOCATION && $tax_name === 'wpp_listing_location' && is_callable(array( 'WPP_F', 'update_location_terms' ) ) ) {
            ud_get_wp_rets_client()->write_log( 'Handling [wpp_listing_location] taxonomy for [' . $_post_id .'] listing.', 'debug' );

            $_geo_tag_fields = array(
              "state" => isset( $_post_data_tax_input["rets_location_state"] ) ? reset( $_post_data_tax_input["rets_location_state"] ) : null,
              "county" => isset( $_post_data_tax_input["rets_location_county"] ) ? reset( $_post_data_tax_input["rets_location_county"] ) : null,
              "city" => isset( $_post_data_tax_input["rets_location_city"] ) ? reset( $_post_data_tax_input["rets_location_city"] ) : null,
              "route" => isset( $_post_data_tax_input["rets_location_route"] ) ? reset( $_post_data_tax_input["rets_location_route"] ) : null,
            );

            $_location_terms = WPP_F::update_location_terms( $_post_id, (object) $_geo_tag_fields);

            if( is_wp_error( $_location_terms ) ) {
              ud_get_wp_rets_client()->write_log( "Failed to insert location terms for[" .$_post_id."] property, got [" . $_location_terms->get_error_message() . " ] error", 'error' );
            } else {
              ud_get_wp_rets_client()->write_log( "Inserted " . count( $_location_terms ) . " location terms for [" .$_post_id."] property.", 'info' );
            }

            // Avoid re-adding whatever fields were passed by real [wpp_listing_location]
            continue;

          }

          if( is_taxonomy_hierarchical( $tax_name ) ) {
            ud_get_wp_rets_client()->write_log( "Handling hierarchical taxonomy [$tax_name].", 'debug' );

            $_terms = array();

            foreach( $tax_tags as $_term_name ) {

              if( is_object( $_term_name ) || is_array( $_term_name ) ) {

                if( isset( $_term_name[ '_id'] ) ) {
                  ud_get_wp_rets_client()->write_log( "Have hierarchical object term [$tax_name] with [" . $_term_name[ '_id'] . "] _id.", 'debug' );
                  $_insert_result = WPP_F::insert_terms($_post_id, array($_term_name), array( '_taxonomy' => $tax_name ) );
                  ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_insert_result['set_terms'] ) . "] terms for [$tax_name] taxonomy.", 'debug' );
                }

                continue;
              }

              ud_get_wp_rets_client()->write_log( "Handling inserting term [$_term_name] for [$tax_name].", 'debug' );

              $_term_parts = explode( ' > ', $_term_name );

              $_term_parent_value = $_term_parts[0];

              if( isset( $_term_parts[1] ) && $_term_parts[1] ) {
                $_term_child_value = $_term_parts[1];
              } else {
                $_term_child_value = null;
              }

              $_term = get_term_by( 'slug', sanitize_title( $_term_name ), $tax_name, ARRAY_A );
              $_term_parent = get_term_by( 'slug', sanitize_title( $_term_parent_value ), $tax_name, ARRAY_A );

              if( is_wp_error( $_term_parent ) ) {
                ud_get_wp_rets_client()->write_log( "Error inserting term [$tax_name]: " . $_term_parent->get_error_message(), 'error' );
                //continue;
              }

              if( !$_term_parent ) {
                ud_get_wp_rets_client()->write_log( "Did not find parent term [$tax_name] - [$_term_parent_value].", 'warn' );

                $_term_parent = wp_insert_term( $_term_parent_value, $tax_name, array(
                  "slug" => sanitize_title( $_term_parent_value )
                ));

                if( is_wp_error( $_term_parent ) ) {
                  ud_get_wp_rets_client()->write_log( "Error creating term [$_term_parent_value] with [" . $_term_parent->get_error_message() ."].", 'error' );
                } else {
                  ud_get_wp_rets_client()->write_log( "Created parent term [$_term_parent_value] with [" . $_term_parent['term_id'] ."].", 'info' );
                }

              }

              if( $_term_parent && !$_term && isset( $_term_parts ) && $_term_child_value  ) {

                ud_get_wp_rets_client()->write_log( "Did not find child term [$_term_child_value] with slug [" .sanitize_title( $_term_name ) . "].", 'info' );
                $_term = wp_insert_term( $_term_name, $tax_name, array(
                  "parent" => $_term_parent['term_id'],
                  "slug" => sanitize_title( $_term_name ),
                  "description" => $_term_child_value
                ));

                // add_term_meta();

                if( $_term && !is_wp_error( $_term ) ) {

                  $_child_term_name_change = wp_update_term( $_term['term_id'], $tax_name, array(
                    'name' => $_term_parent_value,
                    'slug' => sanitize_title( $_term_name )
                  ));


                }

                ud_get_wp_rets_client()->write_log( "Created child term [$_term_name] with [" . $_term['term_id'] ."] for [$_term_parent_value] parent.", 'debug' );
              }

              if( $_term_parent && $_term_parent['term_id'] ) {
                $_terms[] = $_term_parent['term_id'];
              }

              if( $_term && $_term['term_id'] ) {
                // ud_get_wp_rets_client()->write_log( "Did not find and could not create child term [$_term_parent_value] using [".sanitize_title( $_term_parts[1] )."] slug" );
                $_terms[] = $_term['term_id'];
              }

            }

            if( isset( $_terms ) && !empty( $_terms ) ) {
              $_inserted_terms = wp_set_post_terms( $_post_id, $_terms, $tax_name );
              ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_inserted_terms ) . "] terms.", 'info' );
            }

          }

          if( !is_taxonomy_hierarchical( $tax_name ) ) {
            ud_get_wp_rets_client()->write_log( "Handling non-hierarchical taxonomy [$tax_name].", 'debug' );

            $_terms = array();

            // check each tag, make sure its NOT an an array.
            foreach( $tax_tags as $_term_name ) {

              // Item is an array, which means this entry includes term meta.
              if( is_object( $_term_name ) || is_array( $_term_name ) && isset( $_term_name[ '_id'] ) ) {
                $_insert_result = WPP_F::insert_terms($_post_id, array($_term_name), array( '_taxonomy' => $tax_name ) );
                ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_insert_result['set_terms'] ) . "] non-hierarchical terms for [$tax_name] taxonomy with [" . $_term_name[ '_id'] . "] _id.", 'debug' );
              } else {
                $_terms[] = $_term_name;
              }

            }

            if( isset( $_terms ) && !empty( $_terms ) ) {
              $_inserted_terms = wp_set_post_terms( $_post_id, $_terms, $tax_name );
              ud_get_wp_rets_client()->write_log( "Inserted [" . count( $_inserted_terms ) . "] terms into [$tax_name] taxonomy.", "debug" );
            }

          }

        }

      }

      /**
       *
       * @param $args
       * @return array
       */
      public function rpc_delete_property( $args ) {
        global $wp_xmlrpc_server, $wpdb;

        $data = self::parseRequest( $args );
        if( !empty( $wp_xmlrpc_server->error ) ) {
          return $data;
        }

        $response = array(
          "ok" => true,
          "request" => $data,
          "logs" => array(),
        );

        $post_id = 0;
        if( is_numeric( $data ) ) {
          $post_id = $data;
        } else if( !empty( $data[ 'id' ] ) ) {
          $post_id = $data[ 'id' ];
          ud_get_wp_rets_client()->logfile = !empty( $data[ 'logfile' ] ) ? $data[ 'logfile' ] : ud_get_wp_rets_client()->logfile;
        }

        ud_get_wp_rets_client()->write_log( 'Have wpp.deleteProperty request.', 'info' );

        if( !$post_id || !is_numeric( $post_id ) ) {
          $log = 'No post ID provided';
          array_push( $response[ 'logs' ], $log );
          ud_get_wp_rets_client()->write_log( $log, 'info' );
          return $response;
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

          $log = "Removed postmeta and terms for Property [{$post_id}].";
          array_push( $response[ 'logs' ], $log );
          ud_get_wp_rets_client()->write_log( $log, 'debug' );

          do_action( 'wrc_property_deleted', $post_id );

        } else {

          ud_get_wp_rets_client()->write_log( "Post [$post_id] found. Removing it.", "info" );

          if( wp_delete_post( $post_id, true ) ) {
            $log = "Removed Property [{$post_id}]";
            /**
             * Do something after property is deleted
             */
            do_action( 'wrc_property_deleted', $post_id );
          } else {
            $log = "Property [{$post_id}] could not be removed";
            $response[ "ok" ] = false;
          }

          array_push( $response[ 'logs' ], $log );
          ud_get_wp_rets_client()->write_log( $log, 'debug' );

        }

        return $response;

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
          return $data;
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
          return $response;
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

        return $response;

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
       * Flush Cache.
       *
       * Mostly a placeholder for future.
       *
       * @author potanin@UD
       * @param null $args
       * @return null
       */
      public function rpc_flush_cache( $args = null ) {

        $args = self::parseRequest( $args, array(
          'taxonomies' => true
        ) );

        foreach( array( 'wpp_categorical') as $taxonomy ) {
          wp_cache_delete( 'all_ids', $taxonomy );
          wp_cache_delete( 'get', $taxonomy );
          delete_option( "{$taxonomy}_children" );
          _get_term_hierarchy( $taxonomy );

        }

        return self::send( array(
          "ok" => true
        ) );

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

      /**
       * Handle Sending Response
       *
       * @todo Make this handle both XMLRPC and REST.
       * @author potanin@UD
       * @param null $data
       * @return null
       */
      static public function send( $data = null ) {

        // Do nothing if we really are RPC.
        if( defined( 'XMLRPC_REQUEST' ) ) {
          return $data;
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

        die( json_encode( $data, JSON_PRETTY_PRINT ) );

      }

    }

  }

}
