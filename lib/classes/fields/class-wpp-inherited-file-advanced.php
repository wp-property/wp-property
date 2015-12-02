<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Inherited_File_Advanced_Field' ) )
{
	class RWMB_Wpp_Inherited_File_Advanced_Field extends RWMB_File_Field{
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
			// Uploaded files
			$html = self::get_uploaded_files( $meta, $field );

			return $html;
		}

		static function get_uploaded_files( $files, $field ){
			$classes = array( 'rwmb-file', 'rwmb-uploaded' );
			if ( count( $files ) <= 0 )
				$classes[] = 'hidden';
			$ol   = '<ul class="%s" data-field_id="%s">';
			$html = sprintf(
				$ol,
				implode( ' ', $classes ),
				$field['id']
			);

			foreach ( $files as $attachment_id ){
				$html .= self::file_html( $attachment_id );
			}

			$html .= '</ul>';

			return $html;
		}

		static function file_html( $attachment_id ){
			$li          = '
			<li id="item_%s">
				<div class="rwmb-icon">%s</div>
				<div class="rwmb-info">
					<a href="%s" target="_blank">%s</a>
					<p>%s</p>
				</div>
			</li>';

			$mime_type = get_post_mime_type( $attachment_id );

			return sprintf(
				$li,
				$attachment_id,
				@wp_get_attachment_image( $attachment_id, array( 60, 60 ), true ), // Wp genereate warning if image not found.
				wp_get_attachment_url( $attachment_id ),
				get_the_title( $attachment_id ),
				$mime_type
			);
		}

	}
}
