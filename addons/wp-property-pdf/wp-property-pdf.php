<?php
/**
 * Add-on Name: WP-Property: PDF Flyer
 * Add-on URI: https://www.usabilitydynamics.com/product/wp-property-pdf
 * Description: The WP-Property PDF Flyer Add-on allows the website owner to quickly generate PDF flyers, or brochures, ready for printing or download. The feature comes with a standard PDF format and a user interface allows the administrator to select which information to display, what colors to use, image sizes, and a logo. For more advances users custom PDF template layouts can be used.
 * Author: Usability Dynamics, Inc.
 * Version: 3.0.4
 * Requires at least: 4.0
 * Tested up to: 4.8.1
 * Text Domain: wpp_pdf
 * Author URI: http://www.usabilitydynamics.com
 * GitHub Plugin URI: wp-property/wp-property-pdf
 * GitHub Branch: v3.0
 *
 * Copyright 2012 - 2020 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */

if( !function_exists( 'ud_get_wpp_pdf' ) ) {

  /**
   * Returns  Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 3.0.0
   */
  function ud_get_wpp_pdf( $key = false, $default = null ) {
    $instance = \UsabilityDynamics\WPP\PDF_Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_wpp_pdf' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 3.0.0
   */
  function ud_check_wpp_pdf() {
    global $_ud_wpp_pdf_error;
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'wpp_pdf' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'wpp_pdf' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'wpp_pdf' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\UsabilityDynamics\WPP\PDF_Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'wpp_pdf' ) );
      }
    } catch( Exception $e ) {
      $_ud_wpp_pdf_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( !function_exists( 'ud_my_wp_plugin_message' ) ) {
  /**
   * Renders admin notes in case there are errors on plugin init
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_wpp_pdf_message() {
    global $_ud_wpp_pdf_error;
    if( !empty( $_ud_wpp_pdf_error ) ) {
      $message = sprintf( __( '<p><b>%s</b> can not be initialized. %s</p>', 'wpp_pdf' ), 'WP-Property PDF Flyer', $_ud_wpp_pdf_error );
      echo '<div class="error fade" style="padding:11px;">' . $message . '</div>';
    }
  }
  add_action( 'admin_notices', 'ud_wpp_pdf_message' );
}

if( ud_check_wpp_pdf() ) {
  //** Initialize. */
  ud_get_wpp_pdf();
}