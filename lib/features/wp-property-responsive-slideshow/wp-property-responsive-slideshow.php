<?php
/**
 * Plugin Name: WP-Property: Responsive Slideshow
 * Plugin URI: https://www.usabilitydynamics.com/product/wp-property-responsive-slideshow
 * Description: Responsive Slideshow for WP-Property plugin
 * Author: Usability Dynamics, Inc.
 * Version: 1.2.0
 * Requires at least: 4.0
 * Tested up to: 4.7
 * Text Domain: wpp_resp_slideshow
 * Author URI: https://www.usabilitydynamics.com
 * Domain Path: /static/languages/
 * GitHub Plugin URI: wp-property/wp-property-responsive-slideshow
 * GitHub Branch: v1.0
 *
 * Copyright 2012 - 2017 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */

if( !function_exists( 'ud_get_wpp_resp_slideshow' ) ) {

  /**
   * Returns WP-Property: Responsive Slideshow Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_get_wpp_resp_slideshow( $key = false, $default = null ) {
    $instance = \UsabilityDynamics\WPP\RS_Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_wpp_resp_slideshow' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_check_wpp_resp_slideshow() {
    global $_ud_wpp_resp_slideshow_error;
    
    if( defined( 'WPP_RESP_SLIDESHOW_VENDOR_LOADED' ) ) {
      return true;
    }
    
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'wpp_resp_slideshow' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'wpp_resp_slideshow' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'wpp_resp_slideshow' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\UsabilityDynamics\WPP\RS_Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'wpp_resp_slideshow' ) );
      }
    } catch( Exception $e ) {
      $_ud_wpp_resp_slideshow_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( !function_exists( 'ud_wpp_resp_slideshow_message' ) ) {
  /**
   * Renders admin notes in case there are errors on plugin init
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_wpp_resp_slideshow_message() {
    global $_ud_wpp_resp_slideshow_error;
    if( !empty( $_ud_wpp_resp_slideshow_error ) ) {
      $message = sprintf( __( '<p><b>%s</b> can not be initialized. %s</p>', 'wpp_resp_slideshow' ), 'WP-Property: Responsive Slideshow', $_ud_wpp_resp_slideshow_error );
      echo '<div class="error fade" style="padding:11px;">' . $message . '</div>';
    }
  }
  add_action( 'admin_notices', 'ud_wpp_resp_slideshow_message' );
}

if( ud_check_wpp_resp_slideshow() ) {
  //** Initialize. */
  ud_get_wpp_resp_slideshow();
}
