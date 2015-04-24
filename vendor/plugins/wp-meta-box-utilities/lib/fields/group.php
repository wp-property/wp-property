<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'RWMB_Group_Field' ) )
{
	class RWMB_Group_Field extends RWMB_Field
	{
		/**
		 * Store the "parent" field's meta
		 * Used to get child field meta
		 *
		 * @var array
		 */
		static $meta;

		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		static function admin_enqueue_scripts()
		{
			wp_enqueue_style( 'rwmb-group', plugins_url( 'group.css', __FILE__ ) );
		}

		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param array $field
		 *
		 * @return string
		 */
		static function html( $meta, $field )
		{
			// Get parent field and filter to child field meta value
			self::$meta = $meta;
			add_filter( 'rwmb_field_meta', array( __CLASS__, 'child_field_meta' ), 10, 3 );

			ob_start();

			foreach ( $field['fields'] as $child_field )
			{
				$child_field['field_name'] = self::child_field_name( $field['field_name'], $child_field['field_name'] );
				call_user_func( array( RW_Meta_Box::get_class_name( $child_field ), 'show' ), $child_field, RWMB_Group::$saved );
			}

			// Remove filter to child field meta value and reset class's parent field's meta
			remove_filter( 'rwmb_field_meta', array( __CLASS__, 'child_field_meta' ) );
			self::$meta = null;

			return ob_get_clean();
		}

		/**
		 * Change the way we get meta value for child fields
		 *
		 * @param mixed $meta        Meta value
		 * @param array $child_field Child field
		 * @param bool  $saved       Has the meta box been saved?
		 *
		 * @return mixed
		 */
		static function child_field_meta( $meta, $child_field, $saved )
		{
			$meta = '';
			$id = $child_field['id'];
			if ( isset( self::$meta[$id] ) )
			{
				$meta = self::$meta[$id];
			}
			elseif ( !$saved && isset( $child_field['std'] ) )
			{
				$meta = $child_field['std'];
			}
			elseif ( $child_field['multiple'] )
			{
				$meta = array();
			}
			return $meta;
		}

		/**
		 * Get meta value, make sure value is an array (of arrays if field is cloneable)
		 * Don't escape value
		 *
		 * @param int   $post_id
		 * @param bool  $saved
		 * @param array $field
		 *
		 * @return mixed
		 */
		static function meta( $post_id, $saved, $field )
		{
			$meta = get_post_meta( $post_id, $field['id'], true ); // Always save as single value

			// Use $field['std'] only when the meta box hasn't been saved (i.e. the first time we run)
			$meta = !$saved && '' === $meta ? $field['std'] : $meta;

			// Make sure returned value is an array
			if ( empty( $meta ) )
				$meta = array();

			// If cloneable, make sure each sub-value is an array
			if ( $field['clone'] )
			{
				// Make sure there's at least 1 sub-value
				if ( empty( $meta ) )
					$meta[0] = array();

				foreach ( $meta as $k => $v )
				{
					$meta[$k] = (array) $v;
				}
			}

			return $meta;
		}

		/**
		 * Change child field name to form parent[child]
		 *
		 * @param string $parent Parent field's name
		 * @param string $child  Child field's name
		 *
		 * @return string
		 */
		static function child_field_name( $parent, $child )
		{
			$pos = strpos( $child, '[' );
			$pos = false === $pos ? strlen( $child ) : $pos;
			$name = $parent . '[' . substr( $child, 0, $pos ) . ']' . substr( $child, $pos );

			return $name;
		}

		/**
		 * Change add clone button
		 *
		 * @return string $html
		 */
		static function add_clone_button()
		{
			return '<a href="#" class="rwmb-button button-primary add-clone">' . __( '+ Add more', 'rwmb' ) . '</a>';
		}
	}
}
