<?php
/**
 * UD API Registration
 *
 */
namespace UsabilityDynamics\SAAS_UTIL {

  if( !class_exists( 'UsabilityDynamics\SAAS_UTIL\Register' ) ) {

    class Register {

      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       */
      protected static $instance = null;

      /**
       * Contains all UD products,
       * which must be registered.
       *
       * @var array
       */
      protected $products = array();

      /**
       * Site ID.
       *
       * @var null
       */
      protected $site_id = null;

      /**
       * Public Key.
       *
       * @var null
       */
      protected $public_key = null;

      /**
       * Secret token, generated by this site.
       *
       * @var null
       */
      protected $secret_token = null;

      /**
       * Registration API Url.
       *
       * Can be overwritten using UD_API_REGISTER_URL constant.
       *
       * @var null
       */
      protected $_api_url = null;

      /**
       * Register constructor.
       *
       */
      protected function __construct() {
        if( defined( 'UD_API_REGISTER_URL' ) && UD_API_REGISTER_URL ) {
          $this->_api_url = untrailingslashit( UD_API_REGISTER_URL );
        } else {
          $this->_api_url = 'https://api.usabilitydynamics.com/product';
        }
        $this->public_key = get_site_option( 'ud_site_secret_token', null );
        $this->site_id = get_site_option( 'ud_site_id', null );
        $this->secret_token = get_site_option( 'ud_site_public_key', null );

        if( !$this->public_key || !$this->site_id || !$this->secret_token ) {
          add_action( 'admin_init', array( $this, 'register_site' ), 10 );
        }
        add_action( 'admin_init', array( $this, 'register_products' ), 11 );
      }

      /**
       * Register site
       *
       * Options Used:
       *
       * - ud_site_secret_token - Generated by site and only known by site and UD API.
       * - ud_site_id - Provided to site by UD API, secret.
       * - ud_site_public_key - Provided to site by UD API, not secret, used for front-end API requests.
       *
       */
      public function register_site() {
        global $wpdb;

        // Determine if Registration is temporarily disabled.
        $_state = get_transient('ud_registration_state');
        if( $_state && is_array( $_state ) && $_state[ 'registration-backoff' ] ) {
          return;
        }
        // Set registration back-off to avoid this being ran multiple times.
        set_transient('ud_registration_state', array( 'registration-backoff' => true, 'time' => time() ), 3600 );

        // Create site secret token, record in DB.
        update_site_option( 'ud_site_secret_token', $this->secret_token = md5( wp_generate_password( 20 ) ) );

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'timestamp' => time(),
            'host' => str_replace( array( 'http://', 'https://' ), '', is_multisite() ? network_site_url() : get_site_url() ),
            'ud_site_secret_token' => $this->secret_token,
            'ud_site_public_key' => $this->public_key,
            'ud_site_id' => $this->site_id,
            'db_hash' => md5( defined( 'DB_NAME' ) ? DB_NAME : null ) . '-' . md5( isset( $wpdb->prefix ) ? $wpdb->prefix : null),
            'deployment_hash' => md5( is_multisite() ? network_site_url() : get_site_url() ) . '-' . md5( defined( 'DB_NAME' ) ? DB_NAME : null ) . '-' . md5( isset( $wpdb->prefix ) ? $wpdb->prefix : null),
            'home_url' => is_multisite() ? network_site_url() : get_site_url(),
            'xmlrpc_url' => site_url( '/xmlrpc.php' ),
            'rest_url' => site_url( function_exists( 'rest_get_url_prefix' ) ? rest_get_url_prefix() : null ),
            'multisite' => is_multisite()
          )
        );

        $response = wp_remote_post( $this->_api_url . "/site/register/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ) );

          if( isset( $api_body ) && $api_body->ok && $api_body->data->ud_site_secret_token === $this->secret_token ) {

            if( isset( $api_body->data->ud_site_id ) ) {
              update_site_option( 'ud_site_id', $api_body->data->ud_site_id );
            }

            if( isset( $api_body->data->ud_site_public_key ) ) {
              update_site_option( 'ud_site_public_key', $api_body->data->ud_site_public_key );
            }

          }

        }

        // Update transient with response detail.
        set_transient('ud_registration_state', array(
          'registration-backoff' => true,
          'time' => time(),
          'request' => array(
            'url' => $this->_api_url . "/site/register/v1",
            'body' => $args['body']
          ) ,
          'response' => isset( $api_body ) ? $api_body : null,
          'responseStatus' => wp_remote_retrieve_response_code( $response )
        ), 3600 );

      }

      /**
       *
       */
      public function register_products() {

        if( empty( $this->products ) || !is_array( $this->products ) ) {
          return;
        }

        $prefix = 'ud_';
        $products = array();

        // Loop products and determine which ones should be registered/updated
        foreach( $this->products as $product => $meta ) {

          $hash = md5( serialize( $meta ) );

          // Determine if we already registered product
          // And there is nothing new to update
          $options = get_option( $prefix.$product );
          if( $options == $hash ) {
            continue;
          }

          // Determine if Product registration/update is temporarily disabled.
          $_state = get_transient( $prefix.$product );
          if( $_state && is_array( $_state ) && $_state[ 'registration-backoff' ] ) {
            continue;
          }
          // Set registration back-off to avoid this being ran multiple times.
          set_transient( $prefix.$product, array( 'registration-backoff' => true, 'time' => time() ), 3600 );

          $products[ $product ] = $meta;

        }

        // Break here, if nothing to update
        if( empty( $products ) ) {
          return;
        }

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'timestamp' => time(),
            'ud_site_secret_token' => $this->secret_token,
            'ud_site_id' => $this->site_id,
            'blog_id' => get_current_blog_id(),
            'user_id' => get_current_user_id(),
            'user_email' => wp_get_current_user()->user_email,
            'home_url' => get_site_url(),
            'products' => $products
          )
        );

        $response = wp_remote_post( $this->_api_url . "/blog/update/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ) );

          // on Success we update options for every product
          // and their transients
          if( isset( $api_body ) && $api_body->ok === true ) {

            foreach( $products as $product => $meta ) {
              $hash = md5( serialize( $meta ) );
              update_option( $prefix.$product, $hash );

              // Update transient with response detail.
              set_transient( $prefix.$product, array(
                'registration-backoff' => true,
                'time' => time(),
                'request' => array(
                  'url' => $this->_api_url . "/blog/update/v1",
                  'body' => $args['body']
                ) ,
                'response' =>isset( $api_body ) ? $api_body : null,
                'responseStatus' => wp_remote_retrieve_response_code( $response )
              ), 3600 );
            }

          }

        }

      }

      /**
       * Adds product for UD registration
       *
       * Determines if site/product should be registered on current request:
       * - Makes sure registration is not being called too late.
       * - It's admin panel
       * - It's not AJAX or XMLRPC request
       *
       * @param $product
       * @param array $meta
       */
      public static function product( $product, $meta = array() ) {
        // We ignore registration logic on front end
        // It should be run only on admin side.
        if( !is_admin() ) {
          return;
        }
        // Be sure it's not AJAX or XMLRPC request
        if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
          return;
        }
        if ( did_action( 'init' ) ) {
          return _doing_it_wrong( __FUNCTION__, __( 'Calling [UsabilityDynamics\SAAS_UTIL\Register] too late, shold be called before [init] action.' ), '1.0.2' );
        }

        $instance = self::get_instance();

        if( !is_array( $meta ) ) {
          $meta = array();
        }

        $instance->products[ $product ] = array(
          "custom_meta" => $meta
        );

      }

      /**
       * Get all available subscriptions for particular SaaS product
       *
       * @param $product SaaS Product ID
       * @return array
       */
      public static function get_subscriptions( $product ) {

        $instance = self::get_instance();

        $data = null;

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'ud_site_secret_token' => $instance->secret_token,
            'ud_site_id' => $instance->site_id,
            'blog_id' => get_current_blog_id(),
          )
        );

        $response = wp_remote_post( $instance->_api_url . "/" . $product . "/subscriptions/list/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ), true );

          if( isset( $api_body ) && $api_body['ok'] && !empty( $api_body['data'] ) ) {
            $data = $api_body['data'];
          }

        }

        if ( !$data ) {
          $message = ( !empty( $api_body ) && !empty( $api_body['message'] ) ) ? (string)$api_body['message'] : "Failed getting available Subscriptions";
          $data = is_wp_error( $response ) ? $response : ( new \WP_Error( 'fail', $message ) );
        }

        return $data;

      }

      /**
       * Get all current subscriptions for particular product
       *
       * @param $product SaaS Product ID
       * @return array
       */
      public static function get_current_subscriptions( $product ) {

        $instance = self::get_instance();

        $data = null;

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'ud_site_secret_token' => $instance->secret_token,
            'ud_site_id' => $instance->site_id,
            'blog_id' => get_current_blog_id()
          )
        );

        $response = wp_remote_post( $instance->_api_url . "/" . $product . "/subscriptions/current/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ), true );

          if( isset( $api_body ) && $api_body['ok'] && !empty( $api_body['data'] ) ) {
            $data = $api_body['data'];
          }

        }

        if ( !$data ) {
          $message = ( !empty( $api_body ) && !empty( $api_body['message'] ) ) ? (string)$api_body['message'] : "Failed getting current active Subscriptions";
          $data = is_wp_error( $response ) ? $response : ( new \WP_Error( 'fail', $message ) );
        }

        return $data;

      }

      /**
       * Add Subscription for particular product
       *
       * @param $product SaaS Product ID
       * @param $subscription Subscription ID
       * @return array
       */
      public static function add_subscription( $product, $subscription ) {

        $instance = self::get_instance();

        $data = null;

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'ud_site_secret_token' => $instance->secret_token,
            'ud_site_id' => $instance->site_id,
            'blog_id' => get_current_blog_id(),
            'subscription_id' => $subscription
          )
        );

        $response = wp_remote_post( $instance->_api_url . "/" . $product . "/subscription/add/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ), true );

          if( isset( $api_body ) && $api_body['ok'] && !empty( $api_body['data'] ) ) {
            $data = $api_body['data'];
          }

        }

        if ( !$data ) {
          $message = ( !empty( $api_body ) && !empty( $api_body['message'] ) ) ? (string)$api_body['message'] : "Failed adding Subscription";
          $data = is_wp_error( $response ) ? $response : ( new \WP_Error( 'fail', $message ) );
        }

        return $data;

      }

      /**
       * Removes Subscription for particular product.
       * Note, if no subscriptions left, default one will be set!
       *
       * @param $product SaaS Product ID
       * @param $subscription Subscription ID
       * @return array
       */
      public static function delete_subscription( $product, $subscription ) {

        $instance = self::get_instance();

        $data = null;

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'ud_site_secret_token' => $instance->secret_token,
            'ud_site_id' => $instance->site_id,
            'blog_id' => get_current_blog_id(),
            'subscription_id' => $subscription
          )
        );

        $response = wp_remote_post( $instance->_api_url . "/" . $product . "/subscription/delete/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ), true );

          if( isset( $api_body ) && $api_body['ok'] && !empty( $api_body['data'] ) ) {
            $data = $api_body['data'];
          }

        }

        if ( !$data ) {
          $message = ( !empty( $api_body ) && !empty( $api_body['message'] ) ) ? (string)$api_body['message'] : "Failed removing Subscription";
          $data = is_wp_error( $response ) ? $response : ( new \WP_Error( 'fail', $message ) );
        }

        return $data;

      }

      /**
       * Returns Billing Information for particular Blog based on Product
       *
       * @param $product SaaS Product ID
       * @return array
       */
      public static function get_billing( $product ) {
        $instance = self::get_instance();

        $data = null;

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'ud_site_secret_token' => $instance->secret_token,
            'ud_site_id' => $instance->site_id,
            'blog_id' => get_current_blog_id()
          )
        );

        $response = wp_remote_post( $instance->_api_url . "/" . $product . "/billing/get/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ), true );

          if( isset( $api_body ) && $api_body['ok'] && !empty( $api_body['data'] ) ) {
            $data = $api_body['data'];
          }

        }

        if ( !$data ) {
          $message = ( !empty( $api_body ) && !empty( $api_body['message'] ) ) ? (string)$api_body['message'] : "Failed retrieving Billing Information";
          $data = is_wp_error( $response ) ? $response : ( new \WP_Error( 'fail', $message ) );
        }

        return $data;
      }

      /**
       * Add/Updates Billing Information
       *
       * @param $product
       * @param $card
       * @return array
       */
      public static function update_billing( $product, $card ) {
        $instance = self::get_instance();

        $data = null;

        $card = wp_parse_args( $card, array(
          // Required
          "number" => null, // string
          "exp_month" => null, // integer
          "exp_year" => null, // integer
          // Optional
          "address_city" => null, // string
          "address_country" => null, // string
          "address_line1" => null, // string
          "address_line2" => null, // string
          "address_state" => null, // string
          "address_zip" => null, // string
        ) );

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'ud_site_secret_token' => $instance->secret_token,
            'ud_site_id' => $instance->site_id,
            'blog_id' => get_current_blog_id(),
            'card' => $card
          )
        );

        $response = wp_remote_post( $instance->_api_url . "/" . $product . "/billing/update/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ), true );

          if( isset( $api_body ) && $api_body['ok'] && !empty( $api_body['data'] ) ) {
            $data = $api_body['data'];
          }

        }

        if ( !$data ) {
          $message = ( !empty( $api_body ) && !empty( $api_body['message'] ) ) ? (string)$api_body['message'] : "Failed updating Billing Information";
          $data = is_wp_error( $response ) ? $response : ( new \WP_Error( 'fail', $message ) );
        }

        return $data;
      }

      /**
       * Removes Billing Information
       * Resets all subscriptions to basic (free) ones.
       *
       * @param $product
       * @return array
       */
      public static function delete_billing( $product ) {
        $instance = self::get_instance();

        $data = null;

        $args = array(
          'method' => 'POST',
          'timeout' => 10,
          'redirection' => 5,
          'httpversion' => '1.0',
          //'headers' => array(),
          'body' => array(
            'ud_site_secret_token' => $instance->secret_token,
            'ud_site_id' => $instance->site_id,
            'blog_id' => get_current_blog_id()
          )
        );

        $response = wp_remote_post( $instance->_api_url . "/" . $product . "/billing/delete/v1", $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ), true );

          if( isset( $api_body ) && $api_body['ok'] && !empty( $api_body['data'] ) ) {
            $data = $api_body['data'];
          }

        }

        if ( !$data ) {
          $message = ( !empty( $api_body ) && !empty( $api_body['message'] ) ) ? (string)$api_body['message'] : "Failed removing Billing Information";
          $data = is_wp_error( $response ) ? $response : ( new \WP_Error( 'fail', $message ) );
        }

        return $data;
      }

      /**
       *
       */
      private static function get_instance() {
        if( null === self::$instance ) {
          self::$instance = new self();
        }
        return self::$instance;
      }

    }

  }

}
