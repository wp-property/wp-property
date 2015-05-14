<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Time')) {

		class Field_Time extends Field {
			/**
			 * Enqueue scripts and styles
			 *
			 * @return    void
			 */
			static public function admin_enqueue_scripts() {
				wp_register_style('jquery-ui-core', Utility::path( "static/styles/fields/jqueryui/jquery.ui.core.css", 'url' ), array(), '1.8.17');
				wp_register_style('jquery-ui-theme', Utility::path( "static/styles/fields/jqueryui/jquery.ui.theme.css", 'url' ), array(), '1.8.17');
				wp_register_style('jquery-ui-datepicker', Utility::path( "static/styles/fields/jqueryui/jquery.ui.datepicker.css", 'url' ), array('jquery-ui-core', 'jquery-ui-theme'), '1.8.17');
				wp_register_style('jquery-ui-slider', Utility::path( "static/styles/fields/jqueryui/jquery.ui.slider.css", 'url' ), array('jquery-ui-core', 'jquery-ui-theme'), '1.8.17');
				wp_enqueue_style('jquery-ui-timepicker', Utility::path( "static/styles/fields/jqueryui/jquery-ui-timepicker-addon.min.css", 'url' ), array('jquery-ui-datepicker', 'jquery-ui-slider'), '1.5.0');

				wp_register_script('jquery-ui-timepicker', Utility::path( "static/scripts/fields/jqueryui/jquery-ui-timepicker-addon.min.js", 'url' ), array('jquery-ui-datepicker', 'jquery-ui-slider'), '1.5.0', true);

				/**
				 * Localization
				 * Use 1 minified JS file which contains all languages for simpilicity (in version < 4.4.2 we use separated JS files).
				 * The language is set in Javascript
				 *
				 * Note: we use full locale (de-DE) and fallback to short locale (de)
				 */
				$locale = str_replace('_', '-', get_locale());
				$locale_short = substr($locale, 0, 2);
				wp_register_script('jquery-ui-timepicker-i18n', Utility::path( "static/scripts/fields/jqueryui/jquery-ui-timepicker-addon-i18n.min.js", 'url' ), array('jquery-ui-timepicker'), '1.5.0', true);

				wp_enqueue_script('uisf-time', Utility::path( 'static/scripts/fields/time.js', 'url' ), array('jquery-ui-timepicker-i18n'), false, true);
				wp_localize_script('uisf-time', 'uisf_timepicker', array(
					'locale' => $locale,
					'localeShort' => $locale_short,
				));
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
					'<input type="text" class="uisf-time" data-extra="%s" name="%s" value="%s" id="%s" size="%s" data-options="%s">',
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
					'showButtonPanel' => true,
					'timeFormat' => empty($field['format']) ? 'HH:mm' : $field['format'],
				));

				return $field;
			}
		}
	}
}
