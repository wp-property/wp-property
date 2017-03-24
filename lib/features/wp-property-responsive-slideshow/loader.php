<?php
/**
 * Safe Loader for Plugin
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', function(){

  // If you want Plugin Bundle to be loaded
  // you must define [WP_PROPERTY_RESPONSIVE_SLIDESHOW] before action will be called
  if( !defined( 'WP_PROPERTY_RESPONSIVE_SLIDESHOW' ) ) {
    define( 'WP_PROPERTY_RESPONSIVE_SLIDESHOW', false );

  }

  /** Be sure plugin is not activated anywhere else */
  if( !function_exists( 'ud_get_wpp_resp_slideshow' ) && WP_PROPERTY_RESPONSIVE_SLIDESHOW ) {
    define( 'WPP_RESP_SLIDESHOW_VENDOR_LOADED', true );
    include_once( dirname( __FILE__ ) . '/wp-property-responsive-slideshow.php' );
  }

}, 1 );