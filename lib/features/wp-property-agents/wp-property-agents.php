<?php
/**
 * Plugin Name: WP-Property: Agents
 * Plugin URI: https://usabilitydynamics.com/product/wp-property-agents
 * Description: The WP-Property Real Estate Agents Add-on allows the website administrator to create new Real Estate agent accounts and associate them with properties. Multiple agents can be assigned to any given property and the agent information can be displayed in a widget placed on a property page. Furthermore, properties can be queried by the agent(s) to create agent-specific property listings pages.
 * Author: Usability Dynamics, Inc.
 * Version: 2.0.6
 * Requires at least: 4.0
 * Tested up to: 4.7.1
 * Text Domain: wpp_agents
 * Author URI: http://usabilitydynamics.com
 * Domain Path: /static/languages/
 * GitHub Plugin URI: wp-property/wp-property-agents
 * GitHub Branch: v2.0
 *
 * Copyright 2012 - 2017 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */

if( !function_exists( 'ud_get_wpp_agents' ) ) {

  /**
   * Returns  Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 2.0.0
   */
  function ud_get_wpp_agents( $key = false, $default = null ) {
    $instance = \UsabilityDynamics\WPP\Agents_Bootstrap::get_instance();
    return $key ? $instance->get( $key, $default ) : $instance;
  }

}

if( !function_exists( 'ud_check_wpp_agents' ) ) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 2.0.0
   */
  function ud_check_wpp_agents() {
    global $_ud_wpp_agents_error;
    
    if( defined( 'WPP_AGENTS_VENDOR_LOADED' ) ) {
      return true;
    }
    
    try {
      //** Be sure composer.json exists */
      $file = dirname( __FILE__ ) . '/composer.json';
      if( !file_exists( $file ) ) {
        throw new Exception( __( 'Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', 'wpp_agents' ) );
      }
      $data = json_decode( file_get_contents( $file ), true );
      //** Be sure PHP version is correct. */
      if( !empty( $data[ 'require' ][ 'php' ] ) ) {
        preg_match( '/^([><=]*)([0-9\.]*)$/', $data[ 'require' ][ 'php' ], $matches );
        if( !empty( $matches[1] ) && !empty( $matches[2] ) ) {
          if( !version_compare( PHP_VERSION, $matches[2], $matches[1] ) ) {
            throw new Exception( sprintf( __( 'Plugin requires PHP %s or higher. Your current PHP version is %s', 'wpp_agents' ), $matches[2], PHP_VERSION ) );
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
        require_once ( dirname( __FILE__ ) . '/vendor/autoload.php' );
      } else {
        throw new Exception( sprintf( __( 'Distributive is broken. %s file is missed. Try to remove and upload plugin again.', 'wpp_agents' ), dirname( __FILE__ ) . '/vendor/autoload.php' ) );
      }
      //** Be sure our Bootstrap class exists */
      if( !class_exists( '\UsabilityDynamics\WPP\Agents_Bootstrap' ) ) {
        throw new Exception( __( 'Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', 'wpp_agents' ) );
      }
    } catch( Exception $e ) {
      $_ud_wpp_agents_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if( !function_exists( 'ud_wpp_agents_message' ) ) {
  /**
   * Renders admin notes in case there are errors on plugin init
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_wpp_agents_message() {
    global $_ud_wpp_agents_error;
    if( !empty( $_ud_wpp_agents_error ) ) {
      $message = sprintf( __( '<p><b>%s</b> can not be initialized. %s</p>', 'wpp_agents' ), 'WP-Property Agents', $_ud_wpp_agents_error );
      echo '<div class="error fade" style="padding:11px;">' . $message . '</div>';
    }
  }
  add_action( 'admin_notices', 'ud_wpp_agents_message' );
}

if( ud_check_wpp_agents() ) {
  //** Initialize. */
  ud_get_wpp_agents();
}
