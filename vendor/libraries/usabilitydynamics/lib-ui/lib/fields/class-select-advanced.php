<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Select_Advanced')) {

		class Field_Select_Advanced extends Field_Select {

			/**
			 * Enqueue scripts and styles
			 *
			 * @return void
			 */
			static public function admin_enqueue_scripts() {

				//wp_enqueue_style('select2', Utility::path( 'static/styles/fields/select2/select2.css', 'url' ), array(), '3.2');
				wp_enqueue_style('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.2/css/select2.min.css', array(), false);
				wp_enqueue_style('uisf-select-advanced', Utility::path( 'static/styles/fields/select-advanced.css', 'url' ), array(), false);

				wp_register_script('select2', Utility::path( 'static/scripts/fields/select2/select2.js', 'url' ), array(), false, true);
				//wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0-rc.2/js/select2.js', array('jquery'), false, true);
				wp_enqueue_script('uisf-select-advanced', Utility::path( 'static/scripts/fields/select-advanced.js', 'url' ), array('select2'), false, true);
			}

			/**
			 * Get field HTML
			 *
			 * @param mixed $meta
			 * @param array $field
			 *
			 * @return string
			 */
			static public function html($meta, $field) {
				$html = sprintf(
					'<select class="uisf-select-advanced" name="%s" id="%s" data-extra="%s" size="%s"%s data-options="%s">',
					$field->field_name,
					$field->id,
					$field->extra,
					$field->size,
					$field->multiple ? ' multiple="multiple"' : '',
					esc_attr(json_encode($field->js_options))
				);

				$html .= self::options_html($field, $meta);

				$html .= '</select>';

				return $html;
			}

			/**
			 * Normalize parameters for field
			 *
			 * @param array $field
			 *
			 * @return array
			 */
			static public function normalize_field($field) {
				$field = parent::normalize_field($field);

				if( !isset( $field['js_options'] ) ) {
					$field['js_options'] = array();
				}

				$field['js_options'] = wp_parse_args($field['js_options'], array(
					'allowClear' => true,
					'width' => 'resolve',
					'placeholder' => $field['placeholder'],
					'_ajax_url' => isset( $field['url'] ) ? $field['url'] : false,
				));

				return $field;
			}
		}
	}

}
