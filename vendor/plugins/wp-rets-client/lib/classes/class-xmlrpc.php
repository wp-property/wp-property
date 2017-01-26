<?php
/**
 * Bootstrap
 *
 * @since 0.2.0
 */
namespace UsabilityDynamics\WPRETSC {

  use WP_Query;

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
        $_methods[ 'wpp.removeDuplicatedMLS' ] = array( $this, 'rpc_remove_duplicated_mls' );
        $_methods[ 'wpp.modifiedHistogram' ] = array( $this, 'rpc_get_modified_histogram' );

        return $_methods;
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
          return wp_parse_args( $_REQUEST, $defaults ? $defaults : array() );
        }

        $wp_xmlrpc_server->escape( $args );

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
        remove_filter( 'transition_post_status', '_update_term_count_on_transition_post_status', 10 );

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
          $wp_xmlrpc_server->error = null;
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

        // ud_get_wp_rets_client()->write_log( 'Have system check [wpp.editProperty] request.' );

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
          "activePlugins" => self::get_plugins()
        ));


        // ud_get_wp_rets_client()->write_log( 'Have system check [wpp.editProperty] response:' . print_r($_response,true) );

        // not sure if needed here, but seems like good pratice.
        if( function_exists( 'restore_current_blog' ) ) {
          restore_current_blog();
        }

        // Send response to wherever.
        return $_response;

      }

      /**
       *
       * @param $args
       * @return array
       */
      public function rpc_edit_property( $args ) {
        global $wp_xmlrpc_server;

        $post_data = self::parseRequest( $args );
        if( !empty( $wp_xmlrpc_server->error ) ) {
          return $post_data;
        }


      //ud_get_wp_rets_client()->write_log( 'data' . print_r( $post_data, true ) );

        ud_get_wp_rets_client()->write_log( 'Have request wpp.editProperty request' );

        if( isset( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'meta_input' ][ 'wpp::rets_pk' ] = $post_data[ 'meta_input' ][ 'rets_id' ];
        }

        $post_data[ 'meta_input' ][ 'wpp_import_time' ] = time();

        if( !empty( $post_data[ 'meta_input' ][ 'rets_id' ] ) ) {
          $post_data[ 'ID' ] = ud_get_wp_rets_client()->find_property_by_rets_id( $post_data[ 'meta_input' ][ 'rets_id' ] );
        } else {
          return array( 'ok' => false, 'error' => "Property missing RETS ID.", "data" => $post_data );
        }

        // set post status to draft since it may be inserting for a while due to large amount of terms
        $post_data[ 'post_status' ] = 'draft';

        if( !empty( $post_data[ 'ID' ] ) ) {
          ud_get_wp_rets_client()->write_log( 'Running wp_insert_post for [' . $post_data[ 'ID' ] . '].' );
          $_post = get_post( $post_data[ 'ID' ] );
          // If post_date is not set wp_insert_post function sets the current datetime.
          // So we are preventing to do it by setting already existing post_date. peshkov@UD
          $post_data[ 'post_date' ] = $_post->post_date;
          // Status could be changed manually by administrator.
          // So we are preventing to publish property again in case it was trashed. peshkov@UD
          $post_data[ 'post_status' ] = $_post->post_status;

        } else {
          ud_get_wp_rets_client()->write_log( 'Running wp_insert_post for [new post].' );
        }

        $_post_id = wp_insert_post( $post_data, true );

        if( is_wp_error( $_post_id ) ) {
          ud_get_wp_rets_client()->write_log( 'wp_insert_post error <pre>' . print_r( $_post_id, true ) . '</pre>' );
          ud_get_wp_rets_client()->write_log( 'wp_insert_post $post_data <pre>' . print_r( $post_data, true ) . '</pre>' );

          return array(
            "ok" => false,
            "message" => "Unable to insert post",
            "error" => $_post_id->get_error_message()
          );

        } else {

          ud_get_wp_rets_client()->write_log( 'Inserted property post as draft ' . $_post_id );

          if(
            ( !isset( $post_data[ 'meta_input' ][ 'address_is_formatted' ] ) || !$post_data[ 'meta_input' ][ 'address_is_formatted' ] ) &&
            method_exists( 'WPP_F', 'revalidate_address' )
          ) {
            ud_get_wp_rets_client()->write_log( 'Revalidate address if it was not done yet' );
            $r = \WPP_F::revalidate_address( $_post_id, array( 'skip_existing' => 'false' ) );
            if( !empty( $r[ 'status' ] ) && $r[ 'status' ] !== 'updated' ) {
              ud_get_wp_rets_client()->write_log( 'Address validation failed: ' . $r[ 'status' ] );
            }
          }

        }

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

          foreach( $attached_media as $_single_media_item ) {
            // ud_get_wp_rets_client()->write_log( 'Deleting [' .  $_single_media_item->ID . '] media item.' );
            wp_delete_attachment( $_single_media_item->ID, true );
          }

          // delete all old attachments if the count of new media doesn't match up with old media
          if( count( $attached_media ) != count( $post_data[ 'meta_input' ][ 'rets_media' ] ) ) {
            ud_get_wp_rets_client()->write_log( 'For ['.$_post_id.'] property media count has changed. Before ['.count( $attached_media ).'], now ['.count( $post_data[ 'meta_input' ][ 'rets_media' ] ).'].' );
          }

          foreach( $post_data[ 'meta_input' ][ 'rets_media' ] as $media ) {

            if( in_array( $media[ 'url' ], $_already_attached_media ) ) {
              // ud_get_wp_rets_client()->write_log( "Skipping $media[url] because it's already attached to $_post_id" );
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

            // ud_get_wp_rets_client()->write_log( '$attach_id ' . $attach_id  . ' to ' . $_post_id );

            // set the item with order of 1 as the thumbnail
            if( (int)$media[ 'order' ] === 1 ) {
              //set_post_thumbnail( $_post_id, $attach_id );

              // No idea why but set_post_thumbnail() fails routinely as does update_post_meta, testing this method.
              delete_post_meta( $_post_id, '_thumbnail_id' );
              $_thumbnail_setting = add_post_meta( $_post_id, '_thumbnail_id', (int)$attach_id );

              if( $_thumbnail_setting ) {
                ud_get_wp_rets_client()->write_log( 'setting thumbnail [' . $attach_id . '] to post [' . $_post_id . '] because it has order of 1, result: ' );
              } else {
                ud_get_wp_rets_client()->write_log( 'Error! Failured at setting thumbnail [' . $attach_id . '] to post [' . $_post_id . ']' );
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
          ud_get_wp_rets_client()->write_log( 'Updating property post [' . $_post_id  . '].' );
        } else {
          ud_get_wp_rets_client()->write_log( 'Creating property post [' . $_post_id  . '].' );
        }

        $_update_post = wp_update_post( array(
          'ID' => $_post_id,
          // If post already was added to DB, probably its status was changed manually, so let's set the latest status. peshkov@UD
          'post_status' => ( !empty( $_post ) && !empty( $_post->post_status ) ? $_post->post_status : 'publish' )
        ) );

        if( !is_wp_error( $_update_post ) ) {
          ud_get_wp_rets_client()->write_log( 'Published property post [' . $_post_id  . '].' );
          /**
           * Do something after property is published
           */
          do_action( 'wrc_property_published', $_post_id );
        } else {
          ud_get_wp_rets_client()->write_log( 'Error publishing post ' . $_post_id );
          ud_get_wp_rets_client()->write_log( '<pre>' . print_r( $_update_post, true ) . '</pre>' );
        }

        return array(
          "ok" => true,
          "post_id" => $_post_id,
          "post" => get_post( $_post_id ),
          "permalink" => get_the_permalink( $_post_id )
        );

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

        ud_get_wp_rets_client()->write_log( 'Have wpp.deleteProperty request' );

        if( !$post_id || !is_numeric( $post_id ) ) {
          $log = 'No post ID provided';
          array_push( $response[ 'logs' ], $log );
          ud_get_wp_rets_client()->write_log( $log );
          return $response;
        }

        /**
         * Disable term counting
         */
        wp_defer_term_counting( true );

        ud_get_wp_rets_client()->write_log( "Checking post ID [$post_id]" );

        do_action( 'wrc_before_property_deleted', $post_id );

        if( FALSE === get_post_status( $post_id ) ) {

          ud_get_wp_rets_client()->write_log( "Post ID [$post_id] does not exist. Removing its postmeta and terms if exist" );

          // Looks like post was deleted. But postmeta ( and probably terms ) still exist... Remove it.
          wp_delete_object_term_relationships( $post_id, get_object_taxonomies( 'property' ) );
          $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post_id ) );

          $log = "Removed postmeta and terms for Property [{$post_id}].";
          array_push( $response[ 'logs' ], $log );
          ud_get_wp_rets_client()->write_log( $log );

          do_action( 'wrc_property_deleted', $post_id );

        } else {

          ud_get_wp_rets_client()->write_log( "Post [$post_id] found. Removing it." );

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
          ud_get_wp_rets_client()->write_log( $log );

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

        ud_get_wp_rets_client()->write_log( 'Have wpp.removeDuplicatedMLS request' );

        // Find all RETS IDs that have multiple posts associated with them.
        $query = "SELECT meta_value, COUNT(*) c FROM $wpdb->postmeta WHERE meta_key='rets_id' GROUP BY meta_value HAVING c > 1 ORDER BY c DESC";
        $_duplicates = $wpdb->get_col( $query );

        //$response[ 'query' ] = $wpdb->last_query;

        $log = "Found [" . count( $_duplicates ) . "] RETS IDs which have duplicated properties";
        array_push( $response[ 'logs' ], $log );
        ud_get_wp_rets_client()->write_log( $log );

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
          ud_get_wp_rets_client()->write_log( $log );

          $primary = 0;

          foreach( $post_ids as $post_id ) {

            /**
             * Disable term counting
             */
            wp_defer_term_counting( true );

            if( FALSE === get_post_status( $post_id ) ) {

              ud_get_wp_rets_client()->write_log( "Checking post ID [$post_id]" );

              ud_get_wp_rets_client()->write_log( "Post ID [$post_id] does not exist. Removing its postmeta and terms" );

              // Looks like post was deleted. But postmeta ( and probably terms ) still exist... Remove it.
              wp_delete_object_term_relationships( $post_id, get_object_taxonomies( 'property' ) );
              $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post_id ) );

              $log = "RETS ID [{$rets_id}]. Removed postmeta and terms for Property [{$post_id}].";
              array_push( $response[ 'logs' ], $log );
              ud_get_wp_rets_client()->write_log( $log );

            } else {

              if( !$primary ) {

                $primary = $post_id;
                continue;

              } else {

                ud_get_wp_rets_client()->write_log( "Checking post ID [$post_id]" );

                if( wp_delete_post( $post_id, true ) ) {
                  $log = "RETS ID [{$rets_id}]. Removed Property [{$post_id}]";
                } else {
                  $log = "RETS ID [{$rets_id}]. Property [{$post_id}] could not be removed";
                }

                array_push( $response[ 'logs' ], $log );
                ud_get_wp_rets_client()->write_log( $log );

              }

            }

            // Maybe remove post from ES.
            if( !empty( $data[ 'es_client' ] ) ) {

              ud_get_wp_rets_client()->write_log( "Removing post ID [$post_id] from Elasticsearch" );

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
        //wp_defer_term_counting( false );

        ud_get_wp_rets_client()->write_log( 'wpp.removeDuplicatedMLS Done' );

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
