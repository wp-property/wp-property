<?php
/*
Plugin Name: Meta Box Utilities
Plugin URI: http://www.deluxeblogtips.com/meta-box
Description: Set of Utilities to extend default Meta Box plugin functionality in WordPress.
Version: 1.0.0
*/

namespace UsabilityDynamics\MetaBox {

	// Prevent loading this file directly
	defined( 'ABSPATH' ) || exit;

	if (!class_exists('UsabilityDynamics\MetaBox\Bootstrap')) {

		class Bootstrap {

			/**
			 *
			 */
			public function __construct() {
				add_action( 'plugins_loaded', array( $this, 'load' ), 99 );
			}

			/**
			 * Loads all Utilities
			 * if Meta Box plugin is enabled.
			 *
			 */
			public function load() {

				/**
				 * Determine if Meta Box logic is available.
				 *
				 */
				if( !class_exists('RW_Meta_Box') ) {
					return;
				}

				/** Loading Utils */

				// Tabs
				if( !class_exists('RWMB_Tabs') ) {
					if( !class_exists( 'UsabilityDynamics\MetaBox\Tabs' ) ) {
						include_once( $this::path( 'lib/class-tabs.php' ) );
					}
					new Tabs();
				}

				// Columns
				if( !class_exists('RWMB_Columns') ) {
					if( !class_exists( 'UsabilityDynamics\MetaBox\Columns' ) ) {
						include_once( $this::path( 'lib/class-columns.php' ) );
					}
					new Columns();
				}

				// Group
				if( !class_exists('RWMB_Group') ) {
					if( !class_exists( 'UsabilityDynamics\MetaBox\Group' ) ) {
						include_once( $this::path( 'lib/class-group.php' ) );
					}
					new Group();
				}

			}

			/**
			 * Returns dir|url path to file
			 *
			 */
			static public function path( $shortpath, $type = 'dir' ) {
				$path = false;
				switch( $type ) {
					case 'dir':
						$path = plugin_dir_path( __FILE__ );
						$path = wp_normalize_path( $path );
						break;
					case 'url':
						$path = plugin_dir_url( __FILE__ );
						break;
				}
				if( $path ) {
					$path .= ltrim( $shortpath, '/\\' );
				}
				return $path;
			}

		}

	}

	new Bootstrap();

}
