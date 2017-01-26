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

    }
  }
}