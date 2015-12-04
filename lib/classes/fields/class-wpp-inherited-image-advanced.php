<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Inherited_Image_Advanced_Field' ) ){
	class RWMB_Wpp_Inherited_Image_Advanced_Field extends RWMB_Image_Field{
		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		static function admin_enqueue_scripts(){
			parent::admin_enqueue_scripts();
		}


		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param array $field
		 *
		 * @return string
		 */
		static function html( $meta, $field ){
			// Uploaded images
			$html = self::get_uploaded_images( $meta, $field );
			return $html;
		}

		/**
		 * Get HTML markup for uploaded images
		 *
		 * @param array $images
		 * @param array $field
		 *
		 * @return string
		 */
		static function get_uploaded_images( $images, $field ){
			$reorder_nonce = wp_create_nonce( "rwmb-reorder-images_{$field['id']}" );
			$delete_nonce  = wp_create_nonce( "rwmb-delete-file_{$field['id']}" );
			$classes       = array( 'rwmb-images', 'rwmb-uploaded', 'readonly' );
			if ( count( $images ) <= 0 )
				$classes[] = 'hidden';
			$ul   = '<ul class="%s" data-field_id="%s" >';
			$html = sprintf(
				$ul,
				implode( ' ', $classes ),
				$field['id']
			);

			foreach ( $images as $image )
			{
				$html .= self::img_html( $image );
			}

			$html .= '</ul>';

			return $html;
		}

		/**
		 * Get HTML markup for ONE uploaded image
		 *
		 * @param int $image Image ID
		 *
		 * @return string
		 */
		static function img_html( $image ){
			$li          = '
				<li id="item_%s">
					<img src="%s" />
				</li>
			';

			$src  = wp_get_attachment_image_src( $image, 'thumbnail' );
			$src  = $src[0];

			return sprintf(
				$li,
				$image,
				$src
			);
		}
		/**
		 * Get field value
		 * It's the combination of new (uploaded) images and saved images
		 *
		 * @param array $new
		 * @param array $old
		 * @param int   $post_id
		 * @param array $field
		 *
		 * @return array|mixed
		 */
		static function value( $new, $old, $post_id, $field ){
			$new = (array) $new;

			return array_unique( array_merge( $old, $new ) );
		}


	}
}
