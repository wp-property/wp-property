<?php
/**
 * Plugin Name: WP-Property
 * Plugin URI: http://usabilitydynamics.com/products/wp-property/
 * Description: Property and Real Estate Management Plugin for WordPress.  Create a directory of real estate / rental properties and integrate them into you WordPress CMS.
 * Author: Usability Dynamics, Inc.
 * Version: 1.42.2
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

/** This Version  */
define( 'WPP_Version', '1.42.2' );

/** Get Directory - not always wp-property */
define( 'WPP_Directory', dirname( plugin_basename( __FILE__ ) ) );

/** Path for Includes */
define( 'WPP_Path', plugin_dir_path( __FILE__ ) );

/** Path for front-end links */
define( 'WPP_URL', plugin_dir_url( __FILE__ ) );

/** Directory path for includes of template files  */
define( 'WPP_Templates', WPP_Path . 'templates' );

/** Directory path for includes of template files  */
define( 'WPP_Premium', WPP_Path . 'core/premium' );

//** Global Usability Dynamics functions */
include_once WPP_Path . 'core/ud_api.php';

/** Loads built-in plugin metadata and allows for third-party modification to hook into the filters. Has to be included here to run after template functions.php */
include_once WPP_Path . 'action_hooks.php';

/** Defaults filters and hooks */
include_once WPP_Path . 'default_api.php';

/** Loads general functions used by WP-Property */
include_once WPP_Path . 'core/class_functions.php';

/** Loads Admin Tools feature */
include_once WPP_Path . 'core/class_admin_tools.php';

/** Loads export functionality */
include_once WPP_Path . 'core/class_property_export.php';

/** Loads all the metaboxes for the property page */
include_once WPP_Path . 'core/ui/class_ui.php';

/** Loads all the metaboxes for the property page */
include_once WPP_Path . 'core/class_core.php';

/** Bring in the RETS library */
include_once WPP_Path . 'core/class_rets.php';

/** Load set of static methods for mail notifications */
include_once WPP_Path . 'core/class_mail.php';

/** Load in hooks that deal with legacy and backwards-compat issues */
include_once WPP_Path . 'core/class_legacy.php';

// Register activation hook -> has to be in the main plugin file
register_activation_hook( __FILE__,array( 'WPP_F', 'activation' ) );

// Register activation hook -> has to be in the main plugin file
register_deactivation_hook( __FILE__,array( 'WPP_F', 'deactivation' ) );

// Initiate the plugin
add_action( "after_setup_theme", create_function( '', 'new WPP_Core;' ) );