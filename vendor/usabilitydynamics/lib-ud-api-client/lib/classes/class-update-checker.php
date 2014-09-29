<?php
/**
 * Update Checker
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Update_Checker' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class Update_Checker {
    
      /**
       *
       */
      public static $version = '1.0.0';
      
      /**
       * URL to access the Update API Manager.
       */
      private $upgrade_url; 
      
      /**
       * same as plugin slug. if a theme use a theme name like 'twentyeleven'
       */
      private $plugin_name;
      
      /**
       * Path to plugin file
       */
      private $plugin_file; 
      
      /**
       * Software Title
       */
      private $product_id;
      
      /**
       * API License Key
       */
      private $api_key;
      
      /**
       * License Email
       */
      private $activation_email; 
      
      /**
       * URL to renew a license
       */
      private $renew_license_url;
      
      /**
       * Instance ID (unique to each blog activation)
       */
      private $instance;
      
      /**
       * blog domain name
       */
      private $blog;
      
      /**
       *
       */
      private $software_version;
      
      /**
       * 'theme' or 'plugin'
       */
      private $plugin_or_theme;
      
      /**
       * localization for translation
       */
      private $text_domain;
      
      /**
       * Used to send any extra information.
       */
      private $extra;
      
      /**
       * Errors
       */
      public $errors;
      
      /**
       * Error handler.
       *
       */
      public $errors_callback;

      /**
       * Constructor.
       *
       * @access public
       * @since  1.0.0
       * @return void
       */
      public function __construct( $args, $errors_callback = false ) {
        //** API data */
        $this->upgrade_url 			  = isset( $args[ 'upgrade_url' ] ) ? $args[ 'upgrade_url' ] : false;
        $this->plugin_name 			  = isset( $args[ 'plugin_name' ] ) ? $args[ 'plugin_name' ] : false;
        $this->plugin_file 			  = isset( $args[ 'plugin_file' ] ) ? $args[ 'plugin_file' ] : false;
        $this->product_id 			  = isset( $args[ 'product_id' ] ) ? $args[ 'product_id' ] : false;
        $this->api_key 				    = isset( $args[ 'api_key' ] ) ? $args[ 'api_key' ] : false;
        $this->activation_email   = isset( $args[ 'activation_email' ] ) ? $args[ 'activation_email' ] : false;
        $this->renew_license_url 	= isset( $args[ 'renew_license_url' ] ) ? $args[ 'renew_license_url' ] : false;
        $this->instance 			    = isset( $args[ 'instance' ] ) ? $args[ 'instance' ] : false;
        $this->software_version 	= isset( $args[ 'software_version' ] ) ? $args[ 'software_version' ] : false;
        $this->text_domain 			  = isset( $args[ 'text_domain' ] ) ? $args[ 'text_domain' ] : false;
        $this->extra 				      = isset( $args[ 'extra' ] ) ? $args[ 'extra' ] : false;
        
        /**
         * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
         * so only the host portion of the URL can be sent. For example the host portion might be
         * www.example.com or example.com. http://www.example.com includes the scheme http,
         * and the host www.example.com.
         * Sending only the host also eliminates issues when a client site changes from http to https,
         * but their activation still uses the original scheme.
         * To send only the host, use a line like the one below:
         */
        $this->blog = str_ireplace( array( 'http://', 'https://' ), '', home_url() );
        
        $this->errors_callback = $errors_callback;
        
        /**
         * More info:
         * function set_site_transient moved from wp-includes/functions.php
         * to wp-includes/option.php in WordPress 3.4
         *
         * set_site_transient() contains the pre_set_site_transient_{$transient} filter
         * {$transient} is either update_plugins or update_themes
         *
         * Transient data for plugins and themes exist in the Options table:
         * _site_transient_update_themes
         * _site_transient_update_plugins
         */
         
        /**
         * Plugin Updates
         */
        //** Check For Plugin Updates */
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
        //** Check For Plugin Information to display on the update details page */
        add_filter( 'plugins_api', array( $this, 'request' ), 10, 3 );
      }

      /**
       * Upgrade API URL
       *
       */
      private function create_upgrade_api_url( $args ) {
        $upgrade_url = add_query_arg( 'wc-api', 'upgrade-api', $this->upgrade_url );
        return $upgrade_url . '&' . http_build_query( $args );
      }

      /**
       * Check for updates against the remote server.
       *
       * @access public
       * @since  1.0.0
       * @param  object $transient
       * @return object $transient
       */
      public function update_check( $transient ) {
        if ( empty( $transient->checked ) ) {
          return $transient;
        }
        
        $args = array(
          'request' => 'pluginupdatecheck',
          'plugin_name' => $this->plugin_name,
          //'version' => $transient->checked[$this->plugin_name],
          'version' => $this->software_version,
          'product_id' => $this->product_id,
          'api_key' => $this->api_key,
          'activation_email' => $this->activation_email,
          'instance' => $this->instance,
          'domain' => $this->blog,
          'software_version' => $this->software_version,
          'extra' => $this->extra,
          //** Add nocache hack. We must be sure we do not get CACHE result. peshkov@UD */
          'nocache' => rand( 10000, 99999 ),
        );

        //** Check for a plugin update */
        $response = $this->plugin_information( $args );
        //** Displays an admin error message in the WordPress dashboard */
        $this->check_response_for_errors( $response );
        //** Set version variables */
        if ( isset( $response ) && is_object( $response ) && $response !== false ) {
          //** New plugin version from the API */
          $new_ver = (string)$response->new_version;
          //** Current installed plugin version */
          $curr_ver = (string)$this->software_version;
          //$curr_ver = (string)$transient->checked[$this->plugin_name];
        }

        //** If there is a new version, modify the transient to reflect an update is available */
        if ( isset( $new_ver ) && isset( $curr_ver ) ) {
          if ( $response !== false && version_compare( $new_ver, $curr_ver, '>' ) ) {
            $transient->response[$this->plugin_file] = $response;
          }
        }
        //echo "<pre>"; print_r( $this ); echo "</pre>"; die();
        return $transient;
      }

      /**
       * Sends and receives data to and from the server API
       *
       * @access public
       * @since  1.0.0
       * @return object $response
       */
      public function plugin_information( $args ) {
        $target_url = $this->create_upgrade_api_url( $args );
        $request = wp_remote_get( $target_url );
        if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
          return false;
        }
        $response = unserialize( wp_remote_retrieve_body( $request ) );
        //echo "<pre>"; print_r( $response ); echo "</pre>"; die();
        if ( is_object( $response ) ) {
          return $response;
        } else {
          return false;
        }
      }

      /**
       * Generic request helper.
       *
       * @access public
       * @since  1.0.0
       * @param  array $args
       * @return object $response or boolean false
       */
      public function request( $false, $action, $args ) {
        //** Check if this plugins API is about this plugin */
        if ( isset( $args->slug ) ) {
          //** Check if this plugins API is about this plugin */
          if ( $args->slug != $this->plugin_name ) {
            return $false;
          }
        } else {
          return $false;
        }

        $args = array(
          'request' => 'plugininformation',
          'plugin_name' =>	$this->plugin_name,
          //'version' =>	$version->checked[$this->plugin_name],
          'version' =>	$this->software_version,
          'product_id' =>	$this->product_id,
          'api_key' =>	$this->api_key,
          'activation_email' =>	$this->activation_email,
          'instance' =>	$this->instance,
          'domain' =>	$this->blog,
          'software_version' => $this->software_version,
          'extra' => $this->extra,
          //** Add nocache hack. We must be sure we do not get CACHE result. peshkov@UD */
          'nocache' => rand( 10000, 99999 ),
        );

        $response = $this->plugin_information( $args );

        //** If everything is okay return the $response */
        if ( isset( $response ) && is_object( $response ) && $response !== false ) {
          return $response;
        }
      }

      /**
       * Displays an admin error message in the WordPress dashboard
       * @param  array $response
       * @return string
       */
      public function check_response_for_errors( $response ) {

        $this->errors = array();
      
        if ( ! empty( $response ) ) {

          $plugins = get_plugins();
          $plugin_name = isset( $plugins[$this->plugin_name] ) ? $plugins[$this->plugin_name]['Name'] : $this->plugin_name;
          
          if ( isset( $response->errors['no_key'] ) && $response->errors['no_key'] == 'no_key' && isset( $response->errors['no_subscription'] ) && $response->errors['no_subscription'] == 'no_subscription' ) {
          
            $this->errors[] = sprintf( __( 'A license key for %s could not be found. Maybe you forgot to enter a license key when setting up %s, or the key was deactivated in your account. You can reactivate or purchase a license key from your account <a href="%s" target="_blank">Licences</a>.', $this->text_domain ), $plugin_name, $plugin_name, $this->renew_license_url );
            $this->errors[] = sprintf( __( 'A subscription for %s could not be found. You can purchase a subscription from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url );

          } else if ( isset( $response->errors['exp_license'] ) && $response->errors['exp_license'] == 'exp_license' ) {

            $this->errors[] = sprintf( __( 'The license key for %s has expired. You can reactivate or purchase a license key from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url );

          }  else if ( isset( $response->errors['hold_subscription'] ) && $response->errors['hold_subscription'] == 'hold_subscription' ) {

            $this->errors[] = sprintf( __( 'The subscription for %s is on-hold. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url );

          } else if ( isset( $response->errors['cancelled_subscription'] ) && $response->errors['cancelled_subscription'] == 'cancelled_subscription' ) {

            $this->errors[] = sprintf( __( 'The subscription for %s has been cancelled. You can renew the subscription from your account <a href="%s" target="_blank">dashboard</a>. A new license key will be emailed to you after your order has been completed.', $this->text_domain ), $plugin_name, $this->renew_license_url );

          } else if ( isset( $response->errors['exp_subscription'] ) && $response->errors['exp_subscription'] == 'exp_subscription' ) {

            $this->errors[] = sprintf( __( 'The subscription for %s has expired. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url ) ;

          } else if ( isset( $response->errors['suspended_subscription'] ) && $response->errors['suspended_subscription'] == 'suspended_subscription' ) {

            $this->errors[] = sprintf( __( 'The subscription for %s has been suspended. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url ) ;

          } else if ( isset( $response->errors['pending_subscription'] ) && $response->errors['pending_subscription'] == 'pending_subscription' ) {

            $this->errors[] = sprintf( __( 'The subscription for %s is still pending. You can check on the status of the subscription from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url ) ;

          } else if ( isset( $response->errors['trash_subscription'] ) && $response->errors['trash_subscription'] == 'trash_subscription' ) {

            $this->errors[] = sprintf( __( 'The subscription for %s has been placed in the trash and will be deleted soon. You can purchase a new subscription from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url ) ;

          } else if ( isset( $response->errors['no_subscription'] ) && $response->errors['no_subscription'] == 'no_subscription' ) {

            $this->errors[] = sprintf( __( 'A subscription for %s could not be found. You can purchase a subscription from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url );

          } else if ( isset( $response->errors['no_activation'] ) && $response->errors['no_activation'] == 'no_activation' ) {

            $this->errors[] = sprintf( __( '%s has not been activated. Go to the settings page and enter the license key and license email to activate %s.', $this->text_domain ), $plugin_name, $plugin_name ) ;

          } else if ( isset( $response->errors['no_key'] ) && $response->errors['no_key'] == 'no_key' ) {

            $this->errors[] = sprintf( __( 'A license key for %s could not be found. Maybe you forgot to enter a license key when setting up %s, or the key was deactivated in your account. You can reactivate or purchase a license key from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $plugin_name, $this->renew_license_url );

          } else if ( isset( $response->errors['download_revoked'] ) && $response->errors['download_revoked'] == 'download_revoked' ) {

            $this->errors[] = sprintf( __( 'Download permission for %s has been revoked possibly due to a license key or subscription expiring. You can reactivate or purchase a license key from your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url ) ;

          } else if ( isset( $response->errors['switched_subscription'] ) && $response->errors['switched_subscription'] == 'switched_subscription' ) {

            $this->errors[] = sprintf( __( 'You changed the subscription for %s, so you will need to enter your new API License Key in the settings page. The License Key should have arrived in your email inbox, if not you can get it by logging into your account <a href="%s" target="_blank">dashboard</a>.', $this->text_domain ), $plugin_name, $this->renew_license_url ) ;

          }

        }
        
        if( !empty( $this->errors ) ) {
          add_action('admin_notices', array( $this, 'print_errors') );
        }

      }
      
      /**
       * Maybe print admin notices
       */
      public function print_errors() {
        if( !empty( $this->errors ) && is_array( $this->errors ) ) {
          foreach( $this->errors as $error ) {
            echo '<div id="message" class="error"><p>' . $error . '</p></div>';
          }
        }
      }

      
    }
  
  }
  
}