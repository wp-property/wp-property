<?php
/**
 * Plugin Name: WP-Property
 * Plugin URI: http://usabilitydynamics.com/products/wp-property/
 * Description: Property and Real Estate Management Plugin for WordPress.  Create a directory of real estate / rental properties and integrate them into you WordPress CMS.
 * Author: Usability Dynamics, Inc.
 * Version: 1.42.0
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

//** Global Usability Dynamics functions */
include_once 'lib/class-ud-api.php';

/** Loads general functions used by WP-Property */
include_once 'lib/class-wpp-functions.php';

/** Loads all the metaboxes for the property page */
include_once 'lib/class-wpp-ui.php';

/** Loads all the metaboxes for the property page */
include_once 'lib/class-wpp-core.php';

/** Load set of static methods for mail notifications */
include_once 'lib/class-wpp-mail.php';

/** Load in hooks that deal with legacy and backwards-compat issues */
include_once 'lib/class-wpp-legacy.php';

// Initiate the plugin
new WPP_Core(array(
  'id' => __FILE__,
  'root' => __DIR__,
  'basename' => plugin_basename( __FILE__ ),
  'directory' => dirname( plugin_basename( __FILE__ ) )
));

