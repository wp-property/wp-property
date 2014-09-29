<?php
/**
 * Client API
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\API' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class API extends Scaffold {
    
      /**
       * 
       */
      protected $api_url;

      /**
       * 
       */
      protected $errors;
      
      /**
       * 
       */
      protected $token;
      
      /**
       *
       */
      public function __construct( $args ) {
        parent::__construct( $args );
        $this->api_url = isset( $args[ 'api_url' ] ) ? $args[ 'api_url' ] : false;
        $this->token = isset( $args[ 'token' ] ) ? $args[ 'token' ] : false;
      }
      
      /**
       * Activate Product
       */
      public function activate( $args, $product = false, $error_log = true ) {
        $args[ 'request' ] = 'activation';
        return $this->request( $args, $product, $error_log );
      }

      /**
       * Deactivate Product
       */
      public function deactivate( $args, $product = false, $error_log = true ) {
        $args[ 'request' ] = 'deactivation';
        return $this->request( $args, $product, $error_log );
      }

      /**
       * Checks if the software is activated or deactivated
       * @param  array $args
       * @return array
       */
      public function status( $args, $product = false, $error_log = false ) {
        $args[ 'request' ] = 'status';
        return $this->request( $args, $product, $error_log );
      }
      
      /**
       * API Key URL
       */
      protected function create_software_api_url( $args ) {
        $api_url = add_query_arg( 'wc-api', 'am-software-api', $this->api_url );
        return $api_url . '&' . http_build_query( $args );
      }
      
      /**
       *
       * @author peshkov@UD
       */
      protected function request( $args, $product, $error_log ) {
        $product = wp_parse_args( $product, array(
          'product_name' => __( 'UsabilityDynamics Product', $this->domain ),
        ) );
        $args = wp_parse_args( $args, array(
          'request' 		=> '',
          'product_id' 	=> '',
          'instance' 		=> '',
          'email'       => '',
          'licence_key' => '',
          'platform' 	  => $this->blog,
          //** Add nocache hack. We must be sure we do not get CACHE result. peshkov@UD */
          'nocache' => rand( 10000, 99999 ),
        ) );
        $target_url = $this->create_software_api_url( $args );
        //echo "<pre>"; print_r( $target_url ); echo "</pre>"; die();
        $request = wp_remote_get( $target_url );
        if( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
          if( $error_log ) $this->log_request_error( sprintf( __( 'There was an error making %s request for %s. Could not do request to UsabilityDynamics.', $this->domain ), $args[ 'request' ], $product[ 'product_name' ] ) );
        } else {
          $response = wp_remote_retrieve_body( $request );
          $response = @json_decode( $response, true );
          //echo "<pre>"; print_r( $response ); echo "</pre>"; die();
          if( empty( $response ) || !is_array( $response ) ) {
            if( $error_log ) $this->log_request_error( sprintf( __( 'There was an error making %s request for %s, please try again', $this->domain ), $args[ 'request' ], $product[ 'product_name' ] ) );
          } elseif( !empty( $response[ 'error' ] ) ) {
            if( $error_log ) $this->log_request_error( sprintf( __( 'There was an error making %s request for %s: %s.' ), $args[ 'request' ], $product[ 'product_name' ], $response[ 'error' ] ) );
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
      public function log_request_error ( $error ) {
        $this->errors[] = $error;
      }
      
      /**
       * Store logged errors in a temporary transient, such that they survive a page load.
       * @since  1.0.0
       * @return  void
       */
      public function store_error_log () {
        set_transient( $this->token . '-request-error', $this->errors );
      }
      
      /**
       * Get the current error log.
       *
       * @since  1.0.0
       * @return  void
       */
      public function get_error_log () {
        return get_transient( $this->token . '-request-error' );
      }
      
      /**
       * Clear the current error log.
       *
       * @since  1.0.0
       * @return  void
       */
      public function clear_error_log () {
        return delete_transient( $this->token . '-request-error' );
      }
    
    }
  
  }
  
}