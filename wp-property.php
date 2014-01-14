<?php
/**
 * Plugin Name: WP-Property 2.0.0-beta
 * Plugin URI: http://usabilitydynamics.com/products/wp-property/
 * Description: Property and Real Estate Management Plugin for WordPress. Create a directory of real estate / rental properties and integrate them into you WordPress CMS.
 * Author: Usability Dynamics, Inc.
 * Version: 2.0.0-beta1
 * Author URI: http://usabilitydynamics.com
 * Network: False
 *
 * Copyright 2012-2014  Usability Dynamics, Inc.   ( email : info@usabilitydynamics.com )
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

global $wpp;
 
// Include Vendor and Initialize.
if( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
  include_once( __DIR__ . '/vendor/autoload.php' );
  $wpp = \UsabilityDynamics\WPP\Bootstrap::get_instance();
}


