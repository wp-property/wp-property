<?php
/**
 * Safe Loader for Plugin
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', function(){

  // If you want Plugin Bundle to be loaded
  // you must define  WPP_SUPERMAP_VENDOR_LOAD before action will be called
  if( !defined( 'WPP_SUPERMAP_VENDOR_LOAD' ) ) {
    define( 'WPP_SUPERMAP_VENDOR_LOAD', false );
  }

  /** Be sure plugin is not activated anywhere else */
  if( !function_exists( 'ud_get_wpp_supermap' ) && WPP_SUPERMAP_VENDOR_LOAD ) {
    define( 'WPP_SUPERMAP_VENDOR_LOADED', true );
    include_once( dirname( __FILE__ ) . '/wp-property-supermap.php' );
  }

}, 1 );