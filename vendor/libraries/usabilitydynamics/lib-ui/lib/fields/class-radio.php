<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Radio')) {

		class Field_Radio extends Field {
			/**
			 * Get field HTML
			 *
			 * @param mixed $meta
			 * @param array $field
			 *
			 * @return string
			 */
			static public function html($meta, $field) {
				$html = array();
				$tpl = '<label><input type="radio" class="rwmb-radio" data-extra="%s" name="%s" value="%s"%s> %s</label>';

				foreach ($field->options as $value => $label) {
					$html[] = sprintf(
						$tpl,
						$field->extra,
						$field->field_name,
						$value,
						checked($value, $meta, false),
						$label
					);
				}

				return implode(' ', $html);
			}
			
		}
	}
}