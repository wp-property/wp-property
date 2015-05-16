<?php
/**
 * Plugin Name: WP-Property: Alpha Preview
 * Plugin URI: http://usabilitydynamics.com/products/wp-property/
 * Description: Preview
 * Author: Usability Dynamics, Inc.
 * Version: 1.0.0
 * Text Domain: wpp
 * Author URI: http://usabilitydynamics.com
 *
 * Copyright 2012 - 2015 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */


add_filter( 'init', function() {
  global $wp_post_types;

  //die( '<pre>' . print_r( $wp_post_types, true ) . '</pre>' );

}, 20);

add_filter( '__siteorigin_panels_settings_fields', function( $value, $object_id, $meta_key, $single ) {

});

add_filter( '__update_postmeta', function( $value, $object_id, $meta_key, $single ) {

});

add_filter( 'get_post_metadata', function( $value, $object_id, $meta_key, $single ) {

  if( $meta_key !== 'property_type' ) {
    return $value;
  }

  //die('seting $object_id type to :' . $value);

  return $value;

}, 5, 4 );

