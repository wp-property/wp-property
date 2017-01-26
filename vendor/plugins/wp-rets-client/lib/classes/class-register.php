<?php
/**
 * Bootstrap
 *
 * @since 0.2.0
 */

namespace UsabilityDynamics\WPRETSC {

  if( !class_exists( '\UsabilityDynamics\WPRETSC\Register' )) {

    final class Register  {

      public function __construct() {
        /**
         * Maybe register site on UD
         */
        add_action( 'init', array( $this, 'maybe_register_site' ) );
        
      }

    /**
     * Register Site on UD if needed
     *
     */
    public function maybe_register_site() {


      // Do nothing on Ajax, XMLRPC or wp-cli requests.
      if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        return;
      }

      // Token already set, do not attempt registration unless it cleared.
      if( get_site_option('retsci_site_id') && get_site_option( 'retsci_site_public_key' ) ) {
        return;
      }


      $multisite = false;

      // Generate new secret token, unless already exists.
      $retsci_site_secret_token = get_site_option( 'retsci_site_secret_token' );

      if( !$retsci_site_secret_token ) {
        add_site_option('retsci_site_secret_token', $retsci_site_secret_token = md5(wp_generate_password(20)));
      }

      if (is_multisite()) {
        $site_url = network_site_url();
        $multisite = true;
      } else {
        $site_url = get_site_url();
      }

      // This will most likely not exist.
      $retsci_site_id = get_site_option('retsci_site_id');

      if( defined( 'UD_RETS_API_URL' ) ) {
        $url = UD_RETS_API_URL;
      } else {
        $url = 'https://api.usabilitydynamics.com/product/retsci/site/register/v2';
      }

      $find = array('http://', 'https://');
      $replace = '';
      $output = str_replace($find, $replace, $site_url);

      $args = array(
        'method' => 'POST',
        'timeout' => 10,
        'redirection' => 5,
        'httpversion' => '1.0',
        'headers' => array(),
        'body' => array(
          'host' => $output,
          'retsci_site_secret_token' => $retsci_site_secret_token,
          'retsci_site_id' => ($retsci_site_id ? $retsci_site_id : ''),
          'home_url' => $site_url,
          'xmlrpc_url' => site_url('/xmlrpc.php'),
          'user_id' => get_current_user_id(),
          'multisite' => $multisite,
          'message' => "Hello, I'm wp-rets-client plugin. Give me Site ID and Site Public Key, please."
        ),
      );

      $response = wp_remote_post($url, $args);

      if (wp_remote_retrieve_response_code($response) === 200 && !is_wp_error($response)) {

        $api_body = json_decode(wp_remote_retrieve_body($response));

        if (isset($api_body) && $api_body->retsci_site_secret_token === $retsci_site_secret_token) {
          if (isset($api_body->retsci_site_id)) {
            add_site_option('retsci_site_id', $api_body->retsci_site_id);
          }
          if (isset($api_body->retsci_site_public_key)) {
            add_site_option('retsci_site_public_key', $api_body->retsci_site_public_key);
          }
        }
      }
    }
    }
  }
}