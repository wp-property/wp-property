<?php
if( !class_exists( 'UD_Cloud' ) ) {

  /**
   * UD Cloud API
   * Establishes UD Cloud Connection and Pushes Objects as defined in Configuration on Update
   *
   * @version 1.1.2
   * @author potanin@UD
   * @package UD
   * @supackage UD_Cloud
   */
class UD_Cloud extends UD_Functions {

	static $version = '1.0.2';
	static $client = 'UD_Cloud/1.0.2';
	static $defaults = 'ud_cloud.config.json';
	static $option = 'ud_cloud';
	static $url = 'http://cloud.usabilitydynamics.com';
	static $document_type = 'default';
	static $site_uid = false;
	static $public_key = false;
	static $api_key = false;
	static $customer_key = false;

	/**
	 * Initializes Bridge by Adding Filters
	 *
	 * @since 1.0.0
	 * @author potanin@UD
	 */
	static function initialize( $args = array() ) {

		self::$site_uid = UD_SaaS::get_key( 'site_uid' );
		self::$public_key = UD_SaaS::get_key( 'public_key' );
		self::$customer_key = UD_SaaS::get_key( 'customer_key' );
    self::$api_key = UD_SaaS::get_key( 'api_key' );

		if( $args[ 'post_types' ] ) {
			update_option( self::$option . '::post_types', $args[ 'post_types' ] );
		}

		add_filter( 'save_post', array( __CLASS__, 'index' ), 100 );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'post_submitbox_misc_actions' ) );
	}

	/**
	 * Synchronized listings with Cloud Searching Service
	 *
	 * @todo Add extend() to args. - potanin@UD 10/08/12
   * @version 1.1
	 * @since 2.0
	 */
	static function index( $posts = false, $args = false ) {

		if( !defined( 'UD_Site_UID' ) ) {
			return new WP_Error( __METHOD__, 'Unable to index, Site UID not defined.' );
		}

		if( !$posts ) {
			return new WP_Error( __METHOD__, 'Unable to index, no documents passed.' );
		}

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return $posts;
		}

		self::timer_start( __METHOD__ );

		if( ( is_object( $posts ) && isset( $posts->ID ) ) || ( is_array( $posts ) && isset( $posts[ 'ID' ] ) ) ) {

			if( wp_is_post_revision( $posts ) ) {
				return null;
			}

			$posts = array( (object) $posts );
		}

		if( is_numeric( $posts ) ) {
			$posts = get_post( $posts );

			if( wp_is_post_revision( $posts ) ) {
        return null;
			}

			$posts = array( $posts );
		}

    //** Why json_decode - json_encode? */
		$posts = (array) json_decode( json_encode( $posts ) );

		$post_types = (array) get_option( self::$option . '::post_types' );

    //** Get post type from one of the posts */
    $current_type = '';
    if ( count( $posts ) && $posts[0]->post_type ) {
      $current_type = $posts[0]->post_type;
    }

    $args = $args ? $args : self::get_configuration( $current_type );

		foreach( (array) $posts as $count => $post ) {

			if( !isset( $document_type ) || !$document_type ) {
				$document_type = $post->post_type ? $post->post_type : self::$document_type;
			}

			if( !in_array( $post->post_type, $post_types ) ) {

				if( get_post_meta( $post->ID, 'ud_cloud::synchronized', true ) ) {
					self::delete( $post->ID, $post->post_type );
				}

				unset( $posts[ $count ] );
				continue;
			}

			$posts[ $count ]->_id = $post->id ? $post->id : $post->ID;

			foreach( (array) self::strip_protected_keys( (array) get_metadata( 'post', $post->ID ) ) as $meta_key => $values ) {
				$posts[ $count ]->{$meta_key} = array_shift( array_values( $values ) );
			}

			$posts[ $count ]->terms = $posts[ $count ]->terms ? $posts[ $count ]->terms : wp_get_object_terms( $post->ID, get_object_taxonomies( $post->post_type ) );
			$posts[ $count ]->comments = $posts[ $count ]->comments ? $posts[ $count ]->comments : get_comments( array( 'post_id' => $post->ID ) );

			$args->documents[ ] = apply_filters( 'ud_cloud::document', $posts[ $count ] );

		}

		$args->documents = self::array_filter_deep( $args->documents );

		// No documents - either none matched post type, or were removed during QC
		if( !$args->documents ) {
			return null;
		}

    // Private API call can use Customer Key
		$post_url = implode( '/', array( self::$url, self::$site_uid, ( isset( $document_type ) && !empty( $document_type ) ? $document_type : 'default' ), '?key=' . self::$customer_key ) );

		if( is_wp_error( $_response = wp_remote_post( $post_url, array( 'timeout' => 60, 'body' => $args ) ) ) ) {
			return self::log( $_response );
		}

		$_response[ 'post_url' ] = $post_url;
		$_response[ 'timer' ] = self::timer_stop( __METHOD__ );
		$_response[ 'body' ] = (object) array_filter( (array) json_decode( $_response[ 'body' ], true ) );

		switch( true ) {

			case $_response[ 'response' ][ 'code' ] == 500:
				return self::log( new WP_Error( __METHOD__, 'UD Cloud API error occured during an attempt to index.', $_response ) );
				break;

			case ( is_object( $_response[ 'body' ] ) && !$_response[ 'body' ]->success && $_response[ 'body' ]->message ):
				return self::log( new WP_Error( __METHOD__, $_response[ 'body' ]->message, $_response ) );
				break;

		}

    $count = 0;
		foreach( (array) $_response[ 'body' ]->documents as $item_id => $item ) {
      $count++;
			try {

				if( $item[ 'error' ] ) {
					throw new Exception( 'An error occurred with the following message: ' + $item[ 'error' ] );
				}

				if( !$item[ '_id' ] ) {
					throw new Exception( 'The document could not be properly indexed.' );
				}

				if( $item_id != $item[ '_id' ] ) {
					throw new Exception( 'Item ID mismatch detected.' );
				}

				update_post_meta( $posts[ $count ]->ID, 'ud_cloud::_id', $item[ '_id' ] );
				update_post_meta( $posts[ $count ]->ID, 'ud_cloud::_version', $item[ '_version' ] );
				update_post_meta( $posts[ $count ]->ID, 'ud_cloud::synchronized', time() );

			} catch( Exception $e ) {
				update_post_meta( $posts[ $count ]->ID, 'ud_cloud::_id', $item[ '_id' ] );
				delete_post_meta( $posts[ $count ]->ID, 'ud_cloud::synchronized' );
				self::object_log( $posts[ $count ]->ID, $e->getMessage() );
			}

		}

		if( $_response[ 'body' ]->meta ) {
			update_option( self::$option . '::meta', array_merge( (array) get_option( self::$option . '::meta' ), $_response[ 'body' ]->meta ) );
		}

		self::log( 'Cloud Update process complete in ' . self::timer_stop( __METHOD__ ) . ' seconds.' );

		return $_response[ 'body' ]->success ? $_response[ 'body' ] : new WP_Error( 'UD_Cloud::index', 'Unknown error occured during indexing.', $_response );

	}

	/**
	 * Delete Document from Cloud
	 *
	 * @action before_delete_post|wp_trash_post
	 * @todo Must use DELETE method for request, also uses wrong key.
	 */
	static function delete( $id, $type = '' ) {
		wp_remote_request( implode( '/', array( self::$url, self::$site_uid, $type, $id, '_delete?key=' . self::$customer_key ) ), array( 'method' => 'GET' ) );
		delete_post_meta( $id, 'ud_cloud::_id' );
		delete_post_meta( $id, 'ud_cloud::_version' );
		delete_post_meta( $id, 'ud_cloud::synchronized' );
	}

	/**
	 * Updates system log.
	 *
	 * @since 1.0.0
	 * @author potanin@UD
	 */
	static function log( $data ) {

		if( defined( 'WP_DEBUG' ) && WP_DEBUG && is_wp_error( $data ) ) {
			wp_die( '<h1>Debug Log</h1><pre>' . print_r( $data, true ) . '</pre>' );
		}

		return $data;

	}

	/**
	 * Updates object log
	 *
	 * @since 1.0.1
	 */
	static function object_log( $id, $data ) {
		add_post_meta( $id, 'ud_cloud::log', $data );
		return $id;
	}

	/**
	 * Load Configuration Data from Option, stored as JSON
	 *
	 * @version 1.0.0
	 * @since 1.0.0
	 * @author potanin@UD
	 */
	static function get_configuration( $type = '' ) {

    $defaults = array(
      'documents' => array(),
      'meta' => array(
        'callback_url' => admin_url( 'admin-ajax.php?action=ud::UD_Cloud&api_key=' . self::$api_key ),
        'user_ip' => $_SERVER[ 'REMOTE_ADDR' ],
        'client_ip' => $_SERVER[ 'SERVER_ADDR' ],
        'client' => self::$client,
        'defaults' => array(),
        'acl' => array()
      ),
      'schema' => array()
    );

    $configuration = array();

		try {

      //** Search for specific type config */
      if ( file_exists( $_static = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "ud_cloud.$type.config.json" ) ) {

        $configuration = json_decode( file_get_contents( $_static ), true );

      } else {

        //** If there is no specific - use default if it exists */
        if( file_exists( $_static = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . self::$defaults ) || file_exists( $_static = self::$defaults ) ) {
          $configuration = json_decode( file_get_contents( $_static ), true );
        } else {
          $configuration = maybe_unserialize( json_decode( get_option( self::$option ), true ) );
        }

      }

		} catch( Exception $e ) {
			self::log( $e );
		}

    return json_decode( json_encode( self::extend( (array) $configuration, $defaults ) ) );

	}

	/**
	 * Get information about information stored in cloud, synchronization status, etc.
	 *
	 * @since 2.0
	 * @author potanin@UD
	 */
	static function status() {
    $response = wp_remote_get( add_query_arg( array( 'site_uid' => self::get_key( 'site_uid' ), 'public_key' => self::get_key( 'public_key' ) ), 'http://cloud.usabilitydynamics.com/status.json' ) );
		if( !is_wp_error( $response ) ) {
			$response = $response[ 'body' ];
		}
    return $response;
	}

	/**
	 * Initializes Bridge by Adding Filters
	 *
	 * @since 1.0.0
	 * @author potanin@UD
	 */
	static function post_submitbox_misc_actions() {
		global $post;

		if( $synchronized = get_post_meta( $post->ID, 'ud_cloud::synchronized', true ) ) {
			$synchronized = human_time_diff( $synchronized ) . ' ago';

			if( $synchronized == '1 min ago' ) {
				$synchronized = 'Just now';
			}

		}

		if( !$synchronized && !in_array( $post->post_type, (array) get_option( self::$option . '::post_types' ) ) ) {
			return;
		}

		$html = array();

		$html[ ] = '<div class="misc-pub-section curtime">';

		if( $synchronized ) {
			$html[ ] = '<span id="timestamp">Cloud Synchronization: <b>' . $synchronized . '</b></span>';
		} else {
			$html[ ] = '<span id="timestamp">Pending Cloud Synchronization.</span>';
		}

    $html[ ] = '</div>';

    echo implode( '', $html );

  }

}

}