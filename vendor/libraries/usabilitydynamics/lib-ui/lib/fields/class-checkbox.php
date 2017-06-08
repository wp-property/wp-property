<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Checkbox')) {

		class Field_Checkbox extends Field {
			/**
			 * Enqueue scripts and styles
			 *
			 * @return void
			 */
			static public function admin_enqueue_scripts() {
				wp_enqueue_style('rwmb-checkbox', Utility::path( 'static/styles/fields/checkbox.css', 'url'), array(), false);
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
				$desc = $field->desc ? "<span id='{$field->id}_description' class='description'>{$field->desc}</span>" : '';
				return sprintf(
					'<label><input type="checkbox" class="uisf-checkbox" data-extra="%s" name="%s" id="%s" value="1" %s> %s</label>',
					$field->extra,
					$field->field_name,
					$field->id,
					checked(!empty($meta), 1, false),
					$desc
				);
			}

		}
	}
}
