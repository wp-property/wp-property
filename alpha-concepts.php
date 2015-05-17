<?php
/**
 * Plugin Name: WP-Property: Preview
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


add_filter( 'get_property_type_metadata', function( $value, $object_id, $meta_key, $single ) {

  die('post_meta!:' . $value);

  return $value;
}, 5, 4 );