<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_User')) {

		class Field_User extends Field_Select_Advanced {

			/**
			 * Get field HTML
			 *
			 * @param mixed $meta
			 * @param array $field
			 *
			 * @return string
			 */
			static public function html($meta, $field) {
				$field->options = self::get_options($field);

				switch ($field->field_type) {

					case 'select':
						$html = Field_Select::html($meta, $field);
						break;

					case 'select_advanced':
					default:
					$html = Field_Select_Advanced::html($meta, $field);

				}

				return $html;
			}

			/**
			 * Normalize parameters for field
			 *
			 * @param array $field
			 *
			 * @return array
			 */
			static function normalize_field($field) {

				$default_post_type = __('User');

				$field = wp_parse_args($field, array(
					'field_type' => 'select_advanced',
					'parent' => false,
					'query_args' => array(),
				));

				$field['std'] = empty($field['std']) ? sprintf(__('Select a %s', 'meta-box'), $default_post_type) : $field['std'];

				$field['query_args'] = wp_parse_args($field['query_args'], array(
					'orderby' => 'display_name',
					'order' => 'asc',
					'role' => '',
					'fields' => 'all',
				));

				switch ($field['field_type']) {

					case 'select':
						$field = Field_Select::normalize_field($field);
						break;

					case 'select_advanced':
					default:
					$field = Field_Select_Advanced::normalize_field($field);

				}

				return $field;
			}

			/**
			 * Get meta value
			 * If field is cloneable, value is saved as a single entry in DB
			 * Otherwise value is saved as multiple entries (for backward compatibility)
			 *
			 * @see "save" method for better understanding
			 *
			 * @param $post_id
			 * @param $saved
			 * @param $field
			 *
			 * @return array
			 */
			public function meta($post_id, $saved, $field) {
				if (isset($field['parent']) && $field['parent']) {
					$post = get_post($post_id);

					return $post->post_parent;
				}

				return parent::meta($post_id, $saved, $field);
			}

			/**
			 * Get users
			 *
			 * @param array $field
			 *
			 * @return array
			 */
			static public function get_options($field) {
				$results = get_users($field->query_args);
				$options = array();
				foreach ($results as $result) {
					$options[$result->ID] = $result->display_name;
				}
				return $options;
			}
		}
	}
}
