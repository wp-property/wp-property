<?php
/**
 * Plugin Name: Meta Box Show Hide
 * Plugin URI: https://metabox.io/plugins/meta-box-show-hide/
 * Description: Easily show/hide meta boxes by various conditions using JavaScript.
 * Version: 1.0.2
 * Author: Rilwis
 * Author URI: http://www.deluxeblogtips.com
 * License: GPL2+
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MB_Show_Hide' ) )
{
	/**
	 * This class controls toggling meta boxes via JS
	 * All meta boxes are included, but the job of showing/hiding them are handled via JS
	 */
	class MB_Show_Hide
	{
		/**
		 * Add hooks when class is loaded
		 */
		public function __construct()
		{
			add_action( 'rwmb_before', array( $this, 'js_data' ) );
			add_action( 'rwmb_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		/**
		 * Output data for Javascript in data-show, data-hide attributes
		 * Data is output as a .mb-show-hide inside the meta box
		 * JS will read this data and process
		 *
		 * @param RW_Meta_Box $obj The meta box object
		 */
		public function js_data( RW_Meta_Box $obj )
		{
			$meta_box = $obj->meta_box;
			$keys     = array( 'show', 'hide' );
			$data     = '';

			foreach ( $keys as $e )
			{
				if ( ! empty( $meta_box[$e] ) )
				{
					$data .= ' data-' . $e . '="' . esc_attr( json_encode( $meta_box[$e] ) ) . '"';
				}
			}

			if ( $data )
			{
				// Use <script> tag to prevent browser render, thus improves performance.
				echo '<script type="text/html" class="mb-show-hide"' . $data . '></script>';
			}
		}

		/**
		 * Enqueue plugin scripts
		 */
		public function enqueue()
		{
			list( , $url ) = RWMB_Loader::get_path( dirname( __FILE__ ) );
			wp_enqueue_script( 'mb-show-hide', $url . 'show-hide.js', array( 'jquery' ), '1.0.2', true );
		}
	}

	if ( is_admin() )
	{
		new MB_Show_Hide;
	}
}
