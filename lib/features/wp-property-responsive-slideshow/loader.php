<?php
/**
 * Safe Loader for Plugin
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', function(){

  // If you want Plugin Bundle to be loaded
  // you must define  WPP_SUPERMAP_VENDOR_LOAD before action will be called
  if( !defined( 'WPP_RESP_SLIDESHOW_VENDOR_LOAD' ) ) {
    define( 'WPP_RESP_SLIDESHOW_VENDOR_LOAD', false );
  }

  /** Be sure plugin is not activated anywhere else */
  if( !function_exists( 'ud_get_wpp_resp_slideshow' ) && WPP_RESP_SLIDESHOW_VENDOR_LOAD ) {
    define( 'WPP_RESP_SLIDESHOW_VENDOR_LOADED', true );
    include_once( dirname( __FILE__ ) . '/wp-property-resp-slideshow.php' );
  }

}, 1 );