<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Select')) {

		class Field_Select extends Field {

			/**
			 * Enqueue scripts and styles
			 *
			 * @return void
			 */
			static public function admin_enqueue_scripts() {
				wp_enqueue_style('uisf-select', Utility::path( 'static/styles/fields/select.css', 'url' ), array(), false);
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
					'<select class="uisf-select" name="%s" id="%s" data-extra="%s" size="%s"%s>',
					$field->field_name,
					$field->id,
					$field->extra,
					$field->size,
					$field->multiple ? ' multiple="multiple"' : ''
				);

				$html .= self::options_html($field, $meta);

				$html .= '</select>';

				return $html;
			}

			/**
			 * Get meta value
			 * If field is cloneable, value is saved as a single entry in DB
			 * Otherwise value is saved as multiple entries (for backward compatibility)
			 *
			 * @see "save" method for better understanding
			 *
			 * TODO: A good way to ALWAYS save values in single entry in DB, while maintaining backward compatibility
			 *
			 * @param $post_id
			 * @param $saved
			 * @param $field
			 *
			 * @return array
			 */
			public function meta($post_id, $saved, $field) {
				$single = $field['clone'] || !$field['multiple'];
				$meta = get_post_meta($post_id, $field['id'], $single);
				$meta = (!$saved && '' === $meta || array() === $meta) ? $field['std'] : $meta;

				$meta = array_map('esc_attr', (array)$meta);

				return $meta;
			}

			/**
			 * Normalize parameters for field
			 *
			 * @param array $field
			 *
			 * @return array
			 */
			static public function normalize_field($field) {
				$field = wp_parse_args($field, array(
					'desc' => '',
					'name' => $field['id'],
					'size' => $field['multiple'] ? 5 : 0,
					'placeholder' => '',
				));
				if (!$field['clone'] && $field['multiple'])
					$field['field_name'] .= '[]';

				return $field;
			}

			/**
			 * Creates html for options
			 *
			 * @param array $field
			 * @param mixed $meta
			 *
			 * @return array
			 */
			static public function options_html($field, $meta) {
				$html = '';
				if ($field->placeholder) {
					$show_placeholder = ('select' == $field->type) // Normal select field
						|| (isset($field->field_type) && 'select' == $field->field_type) // For 'post' field
						|| (isset($field->display_type) && 'select' == $field->display_type); // For 'taxonomy' field
					$html = $show_placeholder ? "<option value=''>{$field->placeholder}</option>" : '<option></option>';
				}

				$option = '<option value="%s"%s>%s</option>';

				foreach ($field->options as $value => $label) {
					$html .= sprintf(
						$option,
						$value,
						selected(in_array($value, (array)$meta), true, false),
						$label
					);
				}

				return $html;
			}
		}
	}

}
