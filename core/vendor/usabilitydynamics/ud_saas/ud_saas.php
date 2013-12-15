<?php
if ( !class_exists( 'UD_SaaS' ) ) {

  /**
   * UD SaaS Functions
   *
   * Description: UD SaaS functions
   *
   * @author team@UD
   * @version 0.1
   * @package UD
   * @subpackage Functions
   */
  class UD_SaaS {

    /**
     * Returns one of several keys. Different keys are used for different things.
     *
     * api - key for API requests to site
     *
     * site_uid - site ( blog ) unique ID. Only set your site UID if you know what you're doing. The key should only be used on one website at a time to avoid conflicts.
     * For instance, if you have a staging and a production environment with synchronized databases, you can use the same Site UID on both sites.
     *
     * public_key - Public key is used on the front-end of the site to access data that requires a subscription. The public key is restricted to specified IP addresses
     * and and is therefore safe for front-end use. The public key is provided by the UD API service after the site requesting it has been verified. This key is required to access
     * most restricted and premium functionality.
     *
     * customer_key - private keys issued to individuals directly, may be used on multiple sites and in most cases can take place of a missing site_uid. Never shown publicly
     * in clear text. It can be used in md5 format when a public_key does not exist, or has been rejected.
     *
     * @updated 2.0
     * @author potanin@UD
     */
    static function get_key( $type = 'api', $args = false ) {

      $args = wp_parse_args( $args, array( 'force_check' => false ) );

      $key = false;

      switch ( $type ) {

        /**
         * API Keys must be manually entered into the Settings UI or it can be set as define in wp-config.php
         *
         */
        case 'api_key':
          if ( !defined( 'UD_API_Key' ) ) define( 'UD_API_Key', get_option( 'ud::api_key' ) );
          $key = UD_API_Key;
          break;

        /**
         * Site UID must be manually generated into the Settings UI or it can be set as define in wp-config.php
         */
        case 'site_uid':
          if ( !defined( 'UD_Site_UID' ) ) define( 'UD_Site_UID', get_option( 'ud::site_uid' ) );
          $key = UD_Site_UID;
          break;

        /**
         * Requires site verification.
         */
        case 'public_key':
          if ( !defined( 'UD_Public_Key' ) ) define( 'UD_Public_Key', get_option( 'ud::public_key' ) );
          $key = UD_Public_Key;
          break;

        /**
         * Customer Keys must be manually entered into the Settings UI. Customer Keys are given out during purchases, to beta testers,
         * and in other non-automated situations.
         */
        case 'customer_key':
          if ( is_multisite() ) {
            if ( !defined( 'UD_Customer_Key' ) ) {
              /**
               * Customer key must be the same for all blogs
               * It's stored in wp_options of blog #1.
               */
              switch_to_blog( 1 );
              define( 'UD_Customer_Key', get_option( 'ud::customer_key' ) );
              restore_current_blog();
            }
          } else {
            if ( !defined( 'UD_Customer_Key' ) ) define( 'UD_Customer_Key', get_option( 'ud::customer_key' ) );
          }
          $key = UD_Customer_Key;
          break;

      }

      return $key;

    }

    /**
     * Determines if SaaS settings are available
     *
     * @author peshkov@UD
     * @return boolean
     */
    static function is_saas_cap_available() {
      $result = !is_multisite() || is_super_admin() ? true : false;
      $result = apply_filters( 'ud::saas_cap_available', $result );
      return $result;
    }

    /**
     * Determines if current site ( blog ) has capabilities for the passed premium feature
     * @TODO: finish implementation
     *
     * @return boolean
     * @author peshkov@UD
     */
    static function site_has_license( $pf ) {
      $enabled = false;

      $customer_key = self::get_key( 'customer_key' );
      //$site_uid = self::get_key( 'site_uid' );

      if ( empty( $customer_key ) ) {
        return $enabled;
      }

      //** We must do request to api.usabilitydynamics.com here */
      $enabled = true;

      $enabled = apply_filters( 'ud::has_license', $enabled, $pf );

      return $enabled;
    }

    /**
     * Handler for general API calls to UD
     *
     * On Errors, the data response includes request URL, request body, and response headers / body.
     *
     * @updated 1.0.3
     * @since 1.0.0
     * @author potanin@UD
     */
    static function get_service( $service = false, $resource = '', $args = array(), $settings = array() ) {

      if ( $_query = parse_url( $service, PHP_URL_QUERY ) ) {
        $service = str_replace( '?' . $_query, '', $service );
      }

      if ( !$service ) {
        return new WP_Error( 'error', sprintf( __( 'API service not specified.', UD_Transdomain ) ) );
      }

      $request = array_filter( wp_parse_args( $settings, array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode( 'api_key:' . get_option( 'ud::customer_key' ) ),
          'Accept' => 'application/json'
        ),
        'timeout' => 120,
        'stream' => false,
        'sslverify' => false,
        'source' => ( is_ssl() ? 'https' : 'http' ) . '://api.usabilitydynamics.com',
      ) ) );

      foreach ( (array)$settings as $set ) {

        switch ( $set ) {

          case 'json':
            $request[ 'headers' ][ 'Accept' ] = 'application/json';
            break;

          case 'encrypted':
            $request[ 'headers' ][ 'Encryption' ] = 'Enabled';
            break;

          case 'xml':
            $request[ 'headers' ][ 'Accept' ] = 'application/xml';
            break;

        }

      }

      if ( !empty( $request[ 'filename' ] ) && file_exists( $request[ 'filename' ] ) ) {
        $request[ 'stream' ] = true;
      }

      $request_url = trailingslashit( $request[ 'source' ] );
      unset( $request[ 'source' ] );

      if ( $settings[ 'method' ] == 'POST' ) {
        $response = wp_remote_post( $request_url = $request_url . $service . '/' . $resource, array_merge( $request, array( 'body' => $args ) ) );
      } else {
        $response = wp_remote_get( $request_url = $request_url . $service . '/' . $resource . ( is_array( $args ) ? '?' . _http_build_query( $args, null, '&' ) : $args ), $request );
      }

      if ( !is_wp_error( $response ) ) {

        /** If content is streamed, must rely on message codes */
        if ( $request[ 'stream' ] ) {

          switch ( $response[ 'response' ][ 'code' ] ) {

            case 200:
              return true;
              break;

            default:
              unlink( $request[ 'filename' ] );
              return false;
              break;
          }

        }

        switch ( true ) {

          /* |Disabled until issue with RETS API is not resolved| case ( intval( $response[ 'headers' ][ 'content-length' ] ) === 0 ):
            return new WP_Error( 'self::ger_service' , __( 'API did not send back a valid response.' ), array(
              'request_url' => $request_url,
              'request_body' => $request,
              'headers' => $response[ 'headers' ],
              'body' => $response[ 'body' ]
            ));
          break;*/

          case ( $response[ 'response' ][ 'code' ] == 404 ):
            return new WP_Error( 'ud_api', __( 'API Not Responding. Please contact support.' ), array(
              'request_url' => $request_url,
              'request_body' => $request,
              'headers' => $response[ 'headers' ]
            ) );
            break;

          case ( strpos( $response[ 'headers' ][ 'content-type' ], 'text/html' ) !== false ):
            return $response[ 'body' ];
            break;

          case ( strpos( $response[ 'headers' ][ 'content-type' ], 'application/json' ) !== false ):
            $json = @json_decode( $response[ 'body' ] );
            if ( !is_object( $json ) ) return new WP_Error( 'UD_Functions::get_service', __( 'An unknown error occurred while trying to make an API request to Usability Dynamics. Please contact support', 'wpp' ), array( 'response' => $response[ 'body' ] ) );
            return $json->success === false ? new WP_Error( 'UD_Functions::get_service', $json->message, $json->data ) : $json;
            break;

          case ( strpos( $response[ 'headers' ][ 'content-type' ], 'application/xml' ) !== false ):
            return $response[ 'body' ];
            break;

          default:
            return new WP_Error( 'ud_api', __( 'An unknown error occurred while trying to make an API request to Usability Dynamics. Please contact support.', 'wpp' ) );
            break;

        }

      } else {
        if ( !empty( $request[ 'filename' ] ) && is_file( $request[ 'filename' ] ) ) {
          unlink( $request[ 'filename' ] );
        }
        return $response;
      }

    }

  }

}