<?php
/**
 * Plugin Name: WP-RETS Client
 * Plugin URI: https://usabilitydynamics.com
 * Description: WordPress Client for RETS.CI Service. Imports and synchronizes Properties data via XML-RPC/WP-REST.
 * Author: Usability Dynamics, Inc.
 * Version: 0.3.9
 * Text Domain: wp_rets_client
 * Author URI: http://usabilitydynamics.com
 *
 * Requires at least: 4.0
 * Tested up to: 4.9.5
 * Domain Path: /static/languages/
 * Author URI: https://www.usabilitydynamics.com
 * GitHub Plugin URI: usabilitydynamics/wp-rets-client
 * GitHub Branch: production
 *
 * Copyright 2012 - 2018 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */

if( !function_exists( 'ud_get_wp_rets_client' ) ) {

  /**
   * Returns WP-RETS Client Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 0.2.0
   * @param bool $key
   * @param null $default
   * @return
   */
  function ud_get_wp_rets_client( $key = false, $default = null ) {
    $instance = \UsabilityDynamics\WPRETSC\Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_wp_rets_client' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 0.2.0
   */
  function ud_check_wp_rets_client() {
    global $_ud_wp_rets_client_error;
    
    if( defined( 'WP_RETS_CLIENT_VENDOR_LOADED' ) ) {
      return true;
    }
    
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'wp_rets_client' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'wp_rets_client' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'wp_rets_client' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\UsabilityDynamics\WPRETSC\Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'wp_rets_client' ) );
      }
    } catch( Exception $e ) {
      $_ud_wp_rets_client_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( !function_exists( 'ud_wp_rets_client_message' ) ) {
  /**
   * Renders admin notes in case there are errors on plugin init
   *
   * @author Usability Dynamics, Inc.
   * @since 0.2.0
   */
  function ud_wp_rets_client_message() {
    global $_ud_wp_rets_client_error;
    if( !empty( $_ud_wp_rets_client_error ) ) {
      $message = sprintf( __( '<p><b>%s</b> can not be initialized. %s</p>', 'wp_rets_client' ), 'WP-RETS Client', $_ud_wp_rets_client_error );
      echo '<div class="error fade" style="padding:11px;">' . $message . '</div>';
    }
  }
  add_action( 'admin_notices', 'ud_wp_rets_client_message' );
}

if( ud_check_wp_rets_client() ) {
  //** Initialize. */
  ud_get_wp_rets_client();
}

/**
 * WP CLI Commands
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
  require_once( 'bin/wp-cli.php' );
}