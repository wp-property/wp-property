<?php
/**
 * Safe Loader for Plugin
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', function(){

  /** Be sure plugin is not activated anywhere else */
  if( !class_exists( 'BE_Gallery_Metabox' ) ) {
    include_once( dirname( __FILE__ ) . '/gallery-metabox.php' );
  }

}, 99 );