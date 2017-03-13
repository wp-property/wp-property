<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Range')) {

		class Field_Range extends Field {
			/**
			 * Enqueue styles
			 *
			 * @return void
			 */
			static public function admin_enqueue_scripts() {
				wp_enqueue_style('uisf-select-advanced', Utility::path( 'static/styles/fields/range.css', 'url' ), array(), false);
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
				return sprintf(
					'<input type="range" class="uisf-range" data-extra="%s" name="%s" id="%s" value="%s" min="%s" max="%s" step="%s" />',
					$field->extra,
					$field->field_name,
					$field->id,
					$meta,
					$field->min,
					$field->max,
					$field->step
				);
			}

			/**
			 * Normalize parameters for field.
			 *
			 * @param array $field
			 *
			 * @return array
			 */
			static public function normalize_field($field) {
				$field = wp_parse_args($field, array(
					'min' => 0,
					'max' => 10,
					'step' => 1,
				));

				return $field;
			}

		}
	}
}
