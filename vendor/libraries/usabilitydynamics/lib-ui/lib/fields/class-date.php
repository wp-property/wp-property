<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Date')) {

		class Field_Date extends Field {
			/**
			 * Enqueue scripts and styles
			 *
			 * @return void
			 */
			static public function admin_enqueue_scripts() {
				wp_register_style('jquery-ui-core', Utility::path( "static/styles/fields/jqueryui/jquery.ui.core.css", 'url' ), array(), '1.8.17');
				wp_register_style('jquery-ui-theme', Utility::path( "static/styles/fields/jqueryui/jquery.ui.theme.css",'url' ), array(), '1.8.17');
				wp_enqueue_style('jquery-ui-datepicker', Utility::path( "static/styles/fields/jqueryui/jquery.ui.datepicker.css",'url'), array('jquery-ui-core', 'jquery-ui-theme'), '1.8.17');

				// Load localized scripts
				$locale = str_replace('_', '-', get_locale());
				$file_paths = array('jqueryui/datepicker-i18n/jquery.ui.datepicker-' . $locale . '.js');
				// Also check alternate i18n filename (e.g. jquery.ui.datepicker-de.js instead of jquery.ui.datepicker-de-DE.js)
				if (strlen($locale) > 2)
					$file_paths[] = 'jqueryui/datepicker-i18n/jquery.ui.datepicker-' . substr($locale, 0, 2) . '.js';
				$deps = array('jquery-ui-datepicker');
				foreach ($file_paths as $file_path) {
					$path = Utility::path( 'static/scripts/fields/' . $file_path, 'dir' );
					if (file_exists($path)) {
						$path = Utility::path( 'static/scripts/fields/' . $file_path, 'dir' );
						wp_register_script('jquery-ui-datepicker-i18n', Utility::path( 'static/scripts/fields/' . $file_path, 'url' ), $deps, '1.8.17', true);
						$deps[] = 'jquery-ui-datepicker-i18n';
						break;
					}
				}

				wp_enqueue_script('uisf-date', Utility::path( 'static/scripts/fields/date.js', 'url' ), $deps, false, true);
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
					'<input type="text" class="uisf-date" data-extra="%s" name="%s" value="%s" id="%s" size="%s" data-options="%s" />',
					$field->extra,
					$field->field_name,
					$meta,
					isset($field->clone) && $field->clone ? '' : $field->id,
					$field->size,
					esc_attr(json_encode($field->js_options))
				);
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
					'size' => 30,
					'js_options' => array(),
				));

				// Deprecate 'format', but keep it for backward compatible
				// Use 'js_options' instead
				$field['js_options'] = wp_parse_args($field['js_options'], array(
					'dateFormat' => empty($field['format']) ? 'yy-mm-dd' : $field['format'],
					'showButtonPanel' => true,
				));

				return $field;
			}
		}
	}
}
