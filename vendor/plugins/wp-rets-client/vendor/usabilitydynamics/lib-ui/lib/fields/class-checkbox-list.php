<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Checkbox_List')) {

		class Field_Checkbox_List extends Field_Multiple {
			/**
			 * Get field HTML
			 *
			 * @param mixed $meta
			 * @param array $field
			 *
			 * @return string
			 */
			static public function html($meta, $field) {
				$meta = (array)$meta;
				$html = array();
				$tpl = '<label><input type="checkbox" class="uisf-checkbox-list" data-extra="%s" name="%s" value="%s"%s> %s</label>';

				foreach ($field->options as $value => $label) {
					$html[] = sprintf(
						$tpl,
						$field->extra,
						$field->field_name,
						$value,
						checked(in_array($value, $meta), 1, false),
						$label
					);
				}

				return implode('<br>', $html);
			}
		}
	}
}
