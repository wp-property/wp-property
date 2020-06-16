<?php
/**
 * Client Walk Score API
 *
 * @namespace UsabilityDynamics
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\WS_API' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class WS_API {

      /**
       *
       */
      protected static $api_url = 'http://api.walkscore.com/';

      /**
       * @var
       */
      protected static $errors = array();

      /**
       * Get Score
       */
      public static function get_score( $args, $property_id, $error_log = false ) {
        $key = ud_get_wpp_walkscore( 'config.api.key' );
        if( empty( $key ) ) {
          if( $error_log ) self::log_request_error( __( 'Can not make request to Walk Score since API key is not set.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
          return false;
        }
        $api_url = add_query_arg( 'wsapikey', ud_get_wpp_walkscore( 'config.api.key' ), 'http://api.walkscore.com/score' );
        $args = wp_parse_args( $args, array(
          'address' => '',
          'lat' => '',
          'lon' => '',
        ) );
        $args[ 'format' ] = 'json';

        $api_url .= '&';
        foreach ($args AS $key=>$value)
          $api_url .= $key.'='.urlencode($value).'&';
        $target = rtrim($api_url, '&');

        $response = self::_request( $target, $property_id, $error_log );
        if( !empty( $response ) ) {
          if( !isset( $response[ 'status' ] ) ) {
            return false;
          }
          switch( $response[ 'status' ] ) {
            case '2':
              if( $error_log ) self::log_request_error( __( 'Score is being calculated and is not currently available.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
              return false;
              break;
            case '30':
              if( $error_log ) self::log_request_error( __( 'Invalid latitude/longitude.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
              return false;
              break;
            case '31':
              if( $error_log ) self::log_request_error( __( 'Walk Score API internal error.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
              return false;
              break;
            case '40':
              if( $error_log ) self::log_request_error( __( 'Your <strong>Walk Score and Public Transit API key</strong> is invalid.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
              return false;
              break;
            case '41':
              if( $error_log ) self::log_request_error( __( 'Your daily API quota has been exceeded.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
              return false;
              break;
            case '42':
              if( $error_log ) self::log_request_error( __( 'Your IP address has been blocked.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
              return false;
              break;
          }
        }
        return $response;
      }

      /**
       *
       * @author peshkov@UD
       */
      private static function _request( $target_url, $property_id, $error_log ) {
        //echo "<pre>"; print_r( $target_url ); echo "</pre>"; //die();
        $request = wp_remote_get( $target_url, array( 'timeout' => 15, 'sslverify' => false ) );
        //echo "<pre>"; print_r( $request ); echo "</pre>"; die();
        if( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
          if( $error_log ) self::log_request_error( __( 'There was an error making request to Walk Score.', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
        } else {
          $response = wp_remote_retrieve_body( $request );
          $response = @json_decode( $response, true );
          //echo "<pre>"; print_r( $response ); echo "</pre>"; die();
          if( empty( $response ) || !is_array( $response ) ) {
            if( $error_log ) self::log_request_error( __( 'There was an error making request to Walk Score', ud_get_wpp_walkscore( 'domain' ) ), $property_id );
          } else {
            return $response;
          }
        }
        return false;
      }
      
      /**
       * Log an error from an API request.
       *
       * @access private
       * @since 1.0.0
       * @param string $error
       */
      public static function log_request_error ( $error, $property_id ) {
        if( !isset( self::$errors[ $property_id ] ) ) self::$errors[ $property_id ] = array();
        self::$errors[ $property_id ][] = $error;
      }
      
      /**
       * Store logged errors in a temporary transient, such that they survive a page load.
       *
       * @since  1.0.0
       * @return  void
       */
      public static function store_error_log ( $property_id ) {
        if( isset( self::$errors[ $property_id ] ) ) {
          set_transient( 'ws-request-error-' . $property_id, self::$errors[ $property_id ] );
        }
      }
      
      /**
       * Get the current error log.
       *
       * @since  1.0.0
       * @return  void
       */
      public static function get_error_log ( $property_id ) {
        return get_transient( 'ws-request-error-' . $property_id );
      }
      
      /**
       * Clear the current error log.
       *
       * @since  1.0.0
       * @return  void
       */
      public static function clear_error_log ( $property_id ) {
        return delete_transient( 'ws-request-error-' . $property_id );
      }
    
    }
  
  }
  
}