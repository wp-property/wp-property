<?php
/**
 * Add-on Name: WP-Property: Slideshow
 * Add-on URI: https://www.usabilitydynamics.com/product/wp-property-slideshow
 * Description: The slideshow Add-on allows you to insert a slideshow into any property page, home page, or virtually anywhere in your blog.
 * Author: Usability Dynamics, Inc.
 * Version: 4.0.2
 * Requires at least: 4.0
 * Tested up to: 4.9.6
 * Text Domain: wpp_slideshow
 * Author URI: http://www.usabilitydynamics.com
 * Domain Path: /static/languages/
 * GitHub Plugin URI: wp-property/wp-property-slideshow
 * GitHub Branch: v4.0
 *
 * Copyright 2012 - 2020 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */

/** Get Directory - not always wp-property */
if( !defined( 'WPPS_Directory' ) ) {
  define( 'WPPS_Directory', dirname( plugin_basename( __FILE__ ) ) );
}

/** Path for Includes */
if( !defined( 'WPPS_Path' ) ) {
  define( 'WPPS_Path', plugin_dir_path( __FILE__ ) );
}

/** Path for front-end links */
if( !defined( 'WPPS_URL' ) ) {
  define( 'WPPS_URL', plugin_dir_url( __FILE__ ) . 'static/' );
}

/** Directory path for includes of template files  */
if( !defined( 'WPPS_Templates' ) ) {
  define( 'WPPS_Templates', WPPS_Path . 'static/views' );
}

if( !function_exists( 'ud_get_wpp_slideshow' ) ) {

  /**
   * Returns  Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 4.0.0
   */
  function ud_get_wpp_slideshow( $key = false, $default = null ) {
    $instance = \UsabilityDynamics\WPP\Slideshow_Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_wpp_slideshow' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 4.0.0
   */
  function ud_check_wpp_slideshow() {
    global $_ud_wp_property_error;
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'wpp_slideshow' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'wpp_slideshow' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'wpp_slideshow' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\UsabilityDynamics\WPP\Slideshow_Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'wpp_slideshow' ) );
      }
    } catch( Exception $e ) {
      $_ud_wp_property_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( ud_check_wpp_slideshow() ) {
  //** Initialize. */
  ud_get_wpp_slideshow();
}