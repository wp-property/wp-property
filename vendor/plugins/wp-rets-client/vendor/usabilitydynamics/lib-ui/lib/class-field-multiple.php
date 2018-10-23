<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Multiple')) {

		class Field_Multiple extends Field
		{
			/**
			 * Get meta value
			 * If field is cloneable, value is saved as a single entry in DB
			 * Otherwise value is saved as multiple entries
			 *
			 * @see "save" method for better understanding
			 *
			 * @param $post_id
			 * @param $saved
			 * @param $field
			 *
			 * @return array
			 */
			static function meta($post_id, $saved, $field) {
				$meta = get_post_meta($post_id, $field['id'], $field['clone']);
				$meta = (!$saved && '' === $meta || array() === $meta) ? $field['std'] : $meta;
				if (!is_array($meta)) {
					$meta = array();
				}

				// Escape values
				if ($field['clone']) {
					foreach ($meta as &$submeta) {
						$submeta = array_map('esc_attr', $submeta);
					}
				} else {
					$meta = array_map('esc_attr', $meta);
				}

				return $meta;
			}

			/**
			 * Normalize parameters for field
			 *
			 * @param array $field
			 *
			 * @return array
			 */
			static function normalize_field($field) {
				$field['multiple'] = true;
				if (!$field['clone'])
					$field['field_name'] .= '[]';

				return $field;
			}

			/**
			 * Get the field value
			 * If field is cloneable, value is saved as a single entry in DB
			 * Otherwise value is saved as multiple entries
			 *
			 * @param  array $field Field parameters
			 * @param  array $args Additional arguments. Not used for these fields.
			 * @param  int|null $post_id Post ID. null for current post. Optional.
			 *
			 * @return mixed Field value
			 */
			static function get_value($field, $args = array(), $post_id = null) {
				if (!$post_id)
					$post_id = get_the_ID();

				/**
				 * Get raw meta value in the database, no escape
				 * Very similar to self::meta() function
				 */
				$value = get_post_meta($post_id, $field['id'], $field['clone']);
				if (!is_array($value)) {
					$value = array();
				}

				return $value;
			}

			/**
			 * Output the field value
			 * Display option name instead of option value
			 *
			 * @param  array $field Field parameters
			 * @param  array $args Additional arguments. Not used for these fields.
			 * @param  int|null $post_id Post ID. null for current post. Optional.
			 *
			 * @return mixed Field value
			 */
			static function the_value($field, $args = array(), $post_id = null) {
				$value = self::get_value($field, $args, $post_id);
				if (!$value)
					return '';

				$output = '<ul>';
				if ($field['clone']) {
					foreach ($value as $subvalue) {
						$output .= '<li>';
						$output .= '<ul>';
						foreach ($subvalue as $option) {
							$output .= '<li>' . $field['options'][$option] . '</li>';
						}
						$output .= '</ul>';
						$output .= '</li>';
					}
				} else {
					foreach ($value as $option) {
						$output .= '<li>' . $field['options'][$option] . '</li>';
					}
				}
				$output .= '</ul>';

				return $output;
			}
		}
	}
}
