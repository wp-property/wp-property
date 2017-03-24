<?php
/**
 * Plugin Name: WP-Property: Supermap
 * Plugin URI: https://www.usabilitydynamics.com/product/wp-property
 * Description: WP-Property Super Map Add-on lets you put a large interactive map virtually anywhere in your WordPress setup. The map lets your visitors quickly view the location of all your properties, and filter them down by attributes.
 * Author: Usability Dynamics, Inc.
 * Version: 4.0.4
 * Requires at least: 4.0
 * Tested up to: 4.7.1
 * Text Domain: wpp_supermap
 * Author URI: http://www.usabilitydynamics.com
 * GitHub Plugin URI: wp-property/wp-property-supermap
 * GitHub Branch: v4.0
 *
 * Copyright 2012 - 2017 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */

if( !function_exists( 'ud_get_wpp_supermap' ) ) {

  /**
   * Returns  Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 4.0.0
   */
  function ud_get_wpp_supermap( $key = false, $default = null ) {
    $instance = \UsabilityDynamics\WPP\Supermap_Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_wpp_supermap' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 4.0.0
   */
  function ud_check_wpp_supermap() {
    global $_ud_wpp_supermap_error;
    
    if( defined( 'WPP_SUPERMAP_VENDOR_LOADED' ) ) {
      return true;
    }
    
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'wpp_supermap' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'wpp_supermap' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'wpp_supermap' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\UsabilityDynamics\WPP\Supermap_Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'wpp_supermap' ) );
      }
    } catch( Exception $e ) {
      $_ud_wpp_supermap_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( !function_exists( 'ud_wpp_supermap_message' ) ) {
  /**
   * Renders admin notes in case there are errors on plugin init
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_wpp_supermap_message() {
    global $_ud_wpp_supermap_error;
    if( !empty( $_ud_wpp_supermap_error ) ) {
      $message = sprintf( __( '<p><b>%s</b> can not be initialized. %s</p>', 'wpp_supermap' ), 'WP-Property Supermap', $_ud_wpp_supermap_error );
      echo '<div class="error fade" style="padding:11px;">' . $message . '</div>';
    }
  }
  add_action( 'admin_notices', 'ud_wpp_supermap_message' );
}

if( ud_check_wpp_supermap() ) {
  //** Initialize. */
  ud_get_wpp_supermap();
}
