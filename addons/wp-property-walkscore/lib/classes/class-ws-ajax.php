<?php
/**
 * AJAX Handler
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\WS_Ajax' ) ) {

    final class WS_Ajax {

      /**
       * The list of wp_ajax_{name} actions
       *
       * @var array
       */
      var $actions = array(
        'wpp_ws_get_properties_ids',
        'wpp_ws_update_walkscore'
      );

      /**
       * The list of wp_ajax_nopriv_{name} actions
       *
       * @var array
       */
      var $nopriv_actions = array(
        'wpp_ws_get_properties_ids',
        'wpp_ws_update_walkscore'
      );

      /**
       * Init AJAX actions
       *
       * @author peshkov@UD
       */
      public function __construct(){

        /**
         * Maybe extend the list by external modules.
         */
        $this->actions = apply_filters( 'wpp::walkscore::ajax_actions', $this->actions );
        $this->nopriv_actions = apply_filters( 'wpp::walkscore::ajax_nopriv_actions', $this->nopriv_actions );

        foreach( $this->actions as $action ) {
          add_action( 'wp_ajax_' . $action, array( $this, 'request' ) );
        }

        foreach( $this->nopriv_actions as $action ) {
          add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'request' ) );
        }

      }

      /**
       * Handles AJAX request
       *
       * @author peshkov@UD
       */
      public function request() {

        $response = array(
          'message' => '',
          'html' => '',
        );

        try{

          $action = $_REQUEST[ 'action' ];

          /** Determine if the current class has the method to handle request */
          if( is_callable( array( $this, 'action_'. $action ) ) ) {
            $response = call_user_func_array( array( $this, 'action_' . $action ), array( $_REQUEST ) );
          }
          /** Determine if external function exists to handle request */
          elseif ( is_callable( 'action_' . $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          }
          elseif ( is_callable( $action ) ) {
            $response = call_user_func_array( $action, array( $_REQUEST ) );
          }
          /** Oops! */
          else {
            throw new \Exception( __( 'Incorrect Request' ) );
          }

        } catch( \Exception $e ) {
          wp_send_json_error( $e->getMessage() );
        }

        wp_send_json_success( $response );

      }

      /**
       * Sends json.
       * Use it if custom response should be sent.
       *
       * @note Why not use wp_send_json()?
       *
       * @param array $response
       * @author peshkov@UD
       */
      public function send_json( $response ) {
        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
          wp_die();
        } else {
          die;
        }
      }

      /**
       * Returns All Properties IDs
       */
      public function action_wpp_ws_get_properties_ids( $args = false ) {
        global $wpdb;

        /* Even do not continue if API key is not provided. */
        $api_key = ud_get_wpp_walkscore( 'config.api.key' );
        if( empty( $api_key ) ) {
          throw new \Exception( __( 'Walk Score API key is not set. Check your Walk Score API key option.', ud_get_wpp_walkscore('domain') ) );
        }

        /* Determine if address attribute exists */
        $attribute = ud_get_wp_property( 'configuration.address_attribute' );
        if( empty( $attribute ) ) {
          throw new \Exception( __( 'Address attribute is not set. Check your WP-Property Settings.', ud_get_wpp_walkscore('domain') ) );
        }

        /* Get the list of All Properties */
        $ids = $wpdb->get_results("
          SELECT ID as post_id, post_title
            FROM {$wpdb->posts}
              WHERE post_type = 'property'
                AND post_status IN ( 'publish', 'private' )
        ", ARRAY_A );

        /* Get the IDs list of properties which already have Walk Score */
        $extra_ids = $wpdb->get_col("
          SELECT post_id
            FROM {$wpdb->postmeta}
              WHERE meta_key = '_ws_walkscore'
                AND meta_value != ''
        ");

        if( !is_array($ids) ) {
          $ids = array();
        }

        /* Ignore properties which already have Walk Score */
        foreach( $ids as $k => $v ) {
          if( in_array( $v[ 'post_id' ], $extra_ids ) ) {
            unset( $ids[$k] );
          }
        }

        if( empty( $ids ) ) {
          throw new \Exception( sprintf( __( 'No %s with missed Walk Score found.', ud_get_wpp_walkscore('domain') ), \WPP_F::property_label('plural') ) );
        }

        return array(
          'ids' => $ids,
          'total' => count($ids),
        );

      }

      /**
       * Updates Walk Score for Property
       */
      public function action_wpp_ws_update_walkscore( $args = false ) {

        $args = wp_parse_args( $args, array(
          'post_id' => false,
          'post_title' => '',
        ) );

        if( empty( $args[ 'post_id' ] ) || !is_numeric( $args[ 'post_id' ] ) ) {
          throw new \Exception( $this->generate_error_message( __( 'Post ID is not provided or invalid.', ud_get_wpp_walkscore('domain') ), $args[ 'post_id' ], $args[ 'post_title' ] ) );
        }

        if( empty( $args[ 'post_title' ] ) ) {
          $post = get_post( $args[ 'post_id' ], ARRAY_A );
          if( empty( $post[ 'post_title' ] ) ) {
            throw new \Exception( $this->generate_error_message( __( 'Post ID is invalid or Title is not set.', ud_get_wpp_walkscore('domain') ), $args[ 'post_id' ], $args[ 'post_title' ] ) );
          }
          $args[ 'post_title' ] = $post[ 'post_title' ];
        }

        $post_type = get_post_type( $args[ 'post_id' ] );
        if( !$post_type || $post_type !== 'property' ) {
          throw new \Exception( $this->generate_error_message( __( 'Post ID is invalid.', ud_get_wpp_walkscore('domain') ), $args[ 'post_id' ], $args[ 'post_title' ] ) );
        }

        /* Even do not continue if API key is not provided. */
        $api_key = ud_get_wpp_walkscore( 'config.api.key' );
        if( empty( $api_key ) ) {
          throw new \Exception( $this->generate_error_message( __( 'Walk Score API key is not set. Check your Walk Score API key option.', ud_get_wpp_walkscore('domain') ), $args[ 'post_id' ], $args[ 'post_title' ] ) );
        }

        /* Determine if address attribute exists */
        $attribute = ud_get_wp_property( 'configuration.address_attribute' );
        if( empty( $attribute ) ) {
          throw new \Exception( $this->generate_error_message( __( 'Address attribute is not set. Check your WP-Property Settings.', ud_get_wpp_walkscore('domain') ), $args[ 'post_id' ], $args[ 'post_title' ] ) );
        }

        $address = get_post_meta( $args[ 'post_id' ], $attribute, true );
        $lat = get_post_meta( $args[ 'post_id' ], 'latitude', true );
        $lon = get_post_meta( $args[ 'post_id' ], 'longitude', true );

        if( ( empty( $lat ) || empty( $lon ) ) && empty( $address ) ) {
          throw new \Exception( $this->generate_error_message( sprintf( __( '%s does not have address.', ud_get_wpp_walkscore('domain') ), \WPP_F::property_label() ), $args[ 'post_id' ], $args[ 'post_title' ] ) );
        }

        /* Do our API request to WalkScore */
        $response = WS_API::get_score( array(
          'address' => $address,
          'lat' => $lat,
          'lon' => $lon
        ), $args[ 'post_id' ], true );

        /** // Response Example
        $response = array(
        'status' => '1',
        'walkscore' => '63',
        'description' => "walker's paradise",
        'updated' => '2009-12-25 03:40:16.006257',
        'logo_url' => 'https://cdn.walk.sc/images/api-logo.png',
        'more_info_icon' => 'https://cdn.walk.sc/images/api-more-info.gif',
        'ws_link' => 'http://www.walkscore.com/score/1119-8th-Avenue-Seattle-WA-98101/lat=47.6085/lng=-122.3295/?utm_source=myrealtysite.com&utm_medium=ws_api&utm_campaign=ws_api',
        'help_link' => 'https://www.redfin.com/how-walk-score-works',
        'snapped_lat' => '47.6085',
        'snapped_lon' => '-122.3295',
        );
        // */

        if( !empty( $response ) ) {
          update_post_meta( $args[ 'post_id' ], '_ws_walkscore', $response[ 'walkscore' ] );
          update_post_meta( $args[ 'post_id' ], '_ws_walkscore_response', $response );

          return sprintf( __( 'Walk Score has been successfully set for %s %s', ud_get_wpp_walkscore('domain') ), '"' . $args[ 'post_title' ] . '"', '(<a target="_blank" href="' . admin_url( 'post.php?post=' . $args[ 'post_id' ] . '&action=edit' ) . '">' . $args[ 'post_id' ] . '</a>)' );

        } else {
          WS_API::store_error_log( $args[ 'post_id' ] );
          $log = WS_API::get_error_log( $args[ 'post_id' ] );
          WS_API::clear_error_log( $args[ 'post_id' ] );

          if( is_array($log) ) {
            $log = implode( ' ', $log );
          }

          throw new \Exception( sprintf( __( 'Error occurred on request to Walk Score\'s API for getting score for %s %s: %s', ud_get_wpp_walkscore('domain') ), '"' . $args[ 'post_title' ] . '"', '(<a target="_blank" href="' . admin_url( 'post.php?post=' . $args[ 'post_id' ] . '&action=edit' ) . '">' . $args[ 'post_id' ] . '</a>)', $log ) );

        }

      }

      /**
       * Generates Error information for output.
       *
       * @param $error
       * @param $post_id
       * @param $post_title
       * @return string
       */
      public function generate_error_message( $error, $post_id, $post_title ) {
          return sprintf( __( 'Error occurred for %s %s %s: %s', ud_get_wpp_walkscore('domain') ), \WPP_F::property_label(), '"' . $post_title . '"', '(<a target="_blank" href="' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '">' . $post_id . '</a>)', $error );
      }

    }

  }

}
