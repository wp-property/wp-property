<?php
/**
 * Plugin Name: WP-Property
 * Plugin URI: http://usabilitydynamics.com/products/wp-property/
 * Description: Property and Real Estate Management Plugin for WordPress.  Create a directory of real estate / rental properties and integrate them into you WordPress CMS.
 * Author: Usability Dynamics, Inc.
 * Version: 2.0.0
 * Text Domain: wpp
 * Author URI: http://usabilitydynamics.com
 *
 * Copyright 2012 - 2014 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

if( !function_exists( 'ud_get_wp_property' ) ) {

  if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once ( __DIR__ . '/vendor/autoload.php' );
  }

  /** This Version  */
  define( 'WPP_Version', '2.0.0' );
  /** Get Directory - not always wp-property */
  define( 'WPP_Directory', dirname( plugin_basename( __FILE__ ) ) );
  /** Path for Includes */
  define( 'WPP_Path', plugin_dir_path( __FILE__ ) );
  /** Path for front-end links */
  define( 'WPP_URL', plugin_dir_url( __FILE__ ) . 'static/' );
  /** Directory path for includes of template files  */
  define( 'WPP_Templates', WPP_Path . 'static/templates' );

  /**
   * Returns WP_Property object
   *
   * @author peshkov@UD
   * @since 2.0.0
   */
  function ud_get_wp_property( $key = false, $default = null ) {
    if( class_exists( '\UsabilityDynamics\WPP\Bootstrap' ) ) {
      $instance = \UsabilityDynamics\WPP\Bootstrap::get_instance();
      return $key ? $instance->get( $key, $default ) : $instance;
    }
    return false;
  }

}

//** Initialize. */
ud_get_wp_property();