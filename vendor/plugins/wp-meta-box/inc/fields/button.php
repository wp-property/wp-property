<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Button_Field' ) )
{
	class RWMB_Button_Field extends RWMB_Field
	{

    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts()
    {
      wp_enqueue_style( 'rwmb-button', RWMB_CSS_URL . 'button.css', array(), RWMB_VER );
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
			return sprintf(
				'<a href="#" id="%s" class="button %s" data-options="%s">%s</a>',
				$field['id'],
        esc_attr( $field['class'] ? $field['class'] : 'hide-if-no-js' ),
				esc_attr( $field['options'] ),
				$field['std']
			);
		}

		/**
		 * Normalize parameters for field
		 *
		 * @param array $field
		 *
		 * @return array
		 */
		static function normalize_field( $field )
		{
			$field['std'] = $field['std'] ? $field['std'] : __( 'Click me', 'meta-box' );

			return $field;
		}
	}
}
