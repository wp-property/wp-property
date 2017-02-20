<?php

namespace UsabilityDynamics\SAAS_UTIL {

  if( !class_exists( 'UsabilityDynamics\SAAS_UTIL\Register' ) ) {

    class Register {

      /**
       * @var string
       */
      private $api_url = 'https://api.usabilitydynamics.com/product/%product%/site/register/v1';

      /**
       * Register constructor.
       * @param $product_slug
       */
      public function __construct( $product_slug ) {

        if ( did_action( 'init' ) ) {
          return _doing_it_wrong( __FUNCTION__, 'Too late...' );
        }

        if ( get_site_option( 'ud_site_secret_token' ) ) {
          return;
        }

        $this->maybe_register( $product_slug );

      }

      /**
       * Register site if needed
       * @param $product_slug
       */
      private function maybe_register( $product_slug ) {

        update_site_option( 'ud_site_secret_token', $ud_site_secret_token = md5( wp_generate_password( 20 ) ) );

        if ( is_multisite() ) {

          $site_url = network_site_url();
        } else {

          $site_url = get_site_url();
        }

        $ud_site_id = get_site_option( 'ud_site_id' );

        $host = str_replace( array( 'http://', 'https://' ), '', $site_url );

        $args = array(
            'method' => 'POST',
            'timeout' => 10,
            'redirection' => 5,
            'httpversion' => '1.0',
            'headers' => array(),
            'body' => array(
                'host' => $host,
                'ud_site_secret_token' => $ud_site_secret_token,
                'ud_site_id' => ( $ud_site_id ? $ud_site_id : '' ),
                'home_url' => $site_url,
                'message' => "Hey, I'm WordPress site with UD product. I need a key."
            )
        );

        $response = wp_remote_post( str_replace( '%product%', $product_slug, $this->api_url ), $args );

        if( wp_remote_retrieve_response_code( $response ) === 200 && !is_wp_error( $response ) ) {

          $api_body = json_decode( wp_remote_retrieve_body( $response ) );

          if( isset( $api_body ) && $api_body->ud_site_secret_token === $ud_site_secret_token ) {

            if( isset( $api_body->ud_site_id ) ) {
              update_site_option( 'ud_site_id', $api_body->ud_site_id );
            }

            if( isset( $api_body->ud_site_public_key ) ) {
              update_site_option( 'ud_site_public_key', $api_body->ud_site_public_key );
            }

          }

        }

      }

    }
  }
}