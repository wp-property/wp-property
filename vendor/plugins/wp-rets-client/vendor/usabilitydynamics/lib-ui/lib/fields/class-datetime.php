<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Datetime')) {

		class Field_Datetime extends Field {
			/**
			 * Translate date format from jQuery UI datepicker to PHP date()
			 * It's used to store timestamp value of the field
			 * Missing:  'o' => '', '!' => '', 'oo' => '', '@' => '', "''" => "'"
			 * @var array
			 */
			static $date_format_translation = array(
				'd' => 'j', 'dd' => 'd', 'oo' => 'z', 'D' => 'D', 'DD' => 'l',
				'm' => 'n', 'mm' => 'm', 'M' => 'M', 'MM' => 'F', 'y' => 'y', 'yy' => 'Y',
			);

			/**
			 * Translate date format from jQuery UI datepicker to PHP date()
			 * It's used to store timestamp value of the field
			 * Missing: 't' => '', T' => '', 'm' => '', 's' => ''
			 * @var array
			 */
			static $time_format_translation = array(
				'H' => 'H', 'HH' => 'H', 'h' => 'H', 'hh' => 'H',
				'mm' => 'i', 'ss' => 's', 'l' => 'u', 'tt' => 'a', 'TT' => 'A',
			);

			/**
			 * Enqueue scripts and styles
			 *
			 * @return void
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
				 * Use 1 minified JS file for timepicker which contains all languages for simpilicity (in version < 4.4.2 we use separated JS files).
				 * The language is set in Javascript
				 *
				 * Note: we use full locale (de-DE) and fallback to short locale (de)
				 */
				$locale = str_replace('_', '-', get_locale());
				$locale_short = substr($locale, 0, 2);

				wp_register_script('jquery-ui-timepicker-i18n', Utility::path( "static/scripts/fields/jqueryui/jquery-ui-timepicker-addon-i18n.min.js", 'url' ), array('jquery-ui-timepicker'), '1.5.0', true);

				$date_paths = array('jqueryui/datepicker-i18n/jquery.ui.datepicker-' . $locale . '.js');
				if (strlen($locale) > 2) {
					// Also check alternate i18n filenames
					// (e.g. jquery.ui.datepicker-de.js instead of jquery.ui.datepicker-de-DE.js)
					$date_paths[] = 'jqueryui/datepicker-i18n/jquery.ui.datepicker-' . substr($locale, 0, 2) . '.js';
				}
				$deps = array('jquery-ui-timepicker-i18n');
				foreach ($date_paths as $date_path) {
					$path = Utility::path( 'static/scripts/fields/' . $date_path, 'dir' );
					if (file_exists($path)) {
						wp_register_script('jquery-ui-datepicker-i18n', Utility::path( 'static/scripts/fields/' . $date_path, 'url' ), array('jquery-ui-datepicker'), '1.8.17', true);
						$deps[] = 'jquery-ui-datepicker-i18n';
						break;
					}
				}

				wp_enqueue_script('rwmb-datetime', Utility::path( 'static/scripts/fields/datetime.js', 'url' ), $deps, false, true);
				wp_localize_script('rwmb-datetime', 'uisf_datetimepicker', array(
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
			static function html($meta, $field) {
				return sprintf(
					'<input type="text" class="uisf-datetime" data-extra="%s" name="%s" value="%s" id="%s" size="%s" data-options="%s">',
					$field->extra,
					$field->field_name,
					isset($field->timestamp) && $field->timestamp ? date(self::translate_format($field), $meta) : $meta,
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
					'timestamp' => false,
				));

				// Deprecate 'format', but keep it for backward compatible
				// Use 'js_options' instead
				$field['js_options'] = wp_parse_args($field['js_options'], array(
					'dateFormat' => empty($field['format']) ? 'yy-mm-dd' : $field['format'],
					'timeFormat' => 'HH:mm',
					'showButtonPanel' => true,
					'separator' => ' ',
				));

				return $field;
			}

			/**
			 * Returns a date() compatible format string from the JavaScript format
			 *
			 * @see http://www.php.net/manual/en/function.date.php
			 *
			 * @param array $field
			 *
			 * @return string
			 */
			static public function translate_format($field) {
				return strtr($field->js_options['dateFormat'], self::$date_format_translation)
				. $field->js_options['separator']
				. strtr($field->js_options['timeFormat'], self::$time_format_translation);
			}
		}
	}
}
