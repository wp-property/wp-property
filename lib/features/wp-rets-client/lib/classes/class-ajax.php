<?php

namespace UsabilityDynamics\WPRETSC {

  if (!class_exists('UsabilityDynamics\WPRETSC\Ajax')) {

    /**
     * Class Ajax
     * @package UsabilityDynamics\WPRETSC
     */
    final class Ajax {

      public function __construct() {

        add_action( 'wp_ajax_wpp_retsci_signin', array( $this, 'ajax_retsci_signin' ) );
        add_action( 'wp_ajax_wpp_retsci_subscription', array( $this, 'ajax_retsci_subscription' ) );

      }

      /**
       *
       */
      public function ajax_retsci_signin() {

        check_ajax_referer( 'wpp_retsci_signin', 'security', 1 );

        $payload = $_POST['payload'];

        $response = wp_remote_post( $payload['api_url'] . '/v1/site/register', $request_data = array(
          'headers' => array(
            'content-type' => 'application/json'
          ),
          'body' => json_encode(array(
            'ud_site_id' => $payload['ud_site_id'],
            'ud_site_secret_token' => $payload['ud_site_secret_token'],
            'retsci_site_secret_token' => $payload['retsci_site_secret_token'],
            'user_email' => wp_get_current_user()->user_email,
            'rets_credentials' => array(
              'url' => $payload['credentials']['url'],
              'user' => $payload['credentials']['user'],
              'password' => $payload['credentials']['password']
            )
          ))
        ) );

        if ( is_wp_error( $response ) )
          wp_send_json_error( 'Something went wrong' );

        $response_body = json_decode(wp_remote_retrieve_body($response));

        if ( !empty( $response_body->ok ) && $response_body->ok == true ) {

          if ( !empty( $response_body->retsci_site_id ) )
            update_site_option( 'retsci_site_id', $response_body->retsci_site_id );

          if ( !empty( $response_body->retsci_site_public_key ) )
            update_site_option( 'retsci_site_public_key', $response_body->retsci_site_public_key );

            update_site_option( 'retsci_site_secret_token', $payload['retsci_site_secret_token'] );

        }

        wp_send_json($response_body);

      }

      public function ajax_retsci_subscription() {

        check_ajax_referer( 'wpp_retsci_subscription', 'security', 1 );

        $payload = $_POST['payload'];

        if (!get_site_option('rets_credential_url')) {
          add_site_option('rets_credential_url', $_POST['url']);
        }
        if (!get_site_option('rets_credential_user')) {
          add_site_option('rets_credential_url', $_POST['user']);
        }
        if (!get_site_option('rets_credential_url')) {
          add_site_option('rets_credential_url', $_POST['password']);
        }
        if (!get_site_option('rets_credential_url')) {
          add_site_option('rets_credential_url', $_POST['rets_version']);
        }

        $response = wp_remote_post( $payload['api_url'] . '/v1/blog/subscription', $request_data = array(
          'headers' => array(
            'content-type' => 'application/json'
          ),
          'body' => json_encode(array(
            'retsci_site_id' => $payload['retsci_site_id'],
            'retsci_site_secret_token' => $payload['retsci_site_secret_token'],
            'user_data' => json_encode($payload['user_data']),
            'rets_credentials' => array(
              'url' => $payload['credentials']['url'],
              'user' => $payload['credentials']['user'],
              'password' => $payload['credentials']['password'],
              'rets_version' => $payload['credentials']['rets_version'],
              'user_agent' => $payload['credentials']['user_agent'],
            )
          ))
        ) );

        if ( is_wp_error( $response ) )
          wp_send_json_error( 'Something went wrong' );

        $response_body = json_decode(wp_remote_retrieve_body($response));

        if ( !empty( $response_body->ok ) && $response_body->ok == true ) {
          
        }

        wp_send_json($response_body);
        
      }
    }
  }
}