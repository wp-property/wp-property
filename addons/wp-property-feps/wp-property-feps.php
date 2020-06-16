<?php
/**
 * Add-on Name: WP-Property: FEPS
 * Add-on URI: https://www.usabilitydynamics.com/product/wp-property-feps
 * Description: Front End Property Submission (FEPS) lets you create forms and display them on the front-end of the website. The forms may be used by visitors to submit properties, which are then held for approval.
 * Author: Usability Dynamics, Inc.
 * Version: 3.0.8
 * Requires at least: 4.0
 * Tested up to: 4.9.6
 * Text Domain: wpp_feps
 * Author URI: http://www.usabilitydynamics.com
 * GitHub Plugin URI: wp-property/wp-property-feps
 * GitHub Branch: v3.0
 *
 * Copyright 2012 - 2020 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */
if ( !defined( 'WPP_FEPS_Version' ) ) define( 'WPP_FEPS_Version', '3.0.8' );
if ( !defined( 'FEPS_VIEW_PAGE' ) ) define( 'FEPS_VIEW_PAGE', 'my_feps_listings' );
if ( !defined( 'FEPS_EDIT_PAGE' ) ) define( 'FEPS_EDIT_PAGE', 'feps_edit_page' );
if ( !defined( 'FEPS_SPC_PAGE' ) ) define( 'FEPS_SPC_PAGE', 'feps_spc_page' );

//** Specific FEPS meta data */
if ( !defined( 'FEPS_META_FORM' ) ) define( 'FEPS_META_FORM', 'wpp::feps::form_id' );
if ( !defined( 'FEPS_META_PLAN' ) ) define( 'FEPS_META_PLAN', 'wpp::feps::subscription_plan' );
if ( !defined( 'FEPS_META_EXPIRED' ) ) define( 'FEPS_META_EXPIRED', 'wpp::feps::expired_time' );
if ( !defined( 'FEPS_USER_CREDITS' ) ) define( 'FEPS_USER_CREDITS', 'wpp::feps::credits' );
if ( !defined( 'FEPS_RENEW_PLAN' ) ) define( 'FEPS_RENEW_PLAN', 'wpp::feps::renew_plan' );

/** Path for Includes */
if( !defined( 'WPP_FEPS_Path' ) ) {
  define( 'WPP_FEPS_Path', plugin_dir_path( __FILE__ ) );
}

/** Path for front-end links */
if( !defined( 'WPP_FEPS_URL' ) ) {
  define( 'WPP_FEPS_URL', plugin_dir_url( __FILE__ ) );
}
if( !function_exists( 'ud_get_wpp_feps' ) ) {

  /**
   * Returns  Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 3.0.0
   */
  function ud_get_wpp_feps( $key = false, $default = null ) {
    $instance = \UsabilityDynamics\WPP\FEPS_Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_wpp_feps' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 3.0.0
   */
  function ud_check_wpp_feps() {
    global $_ud_wp_property_error;
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'wpp_feps' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'wpp_feps' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'wpp_feps' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\UsabilityDynamics\WPP\FEPS_Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'wpp_feps' ) );
      }
    } catch( Exception $e ) {
      $_ud_wp_property_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( ud_check_wpp_feps() ) {
  //** Initialize. */
  ud_get_wpp_feps();
}