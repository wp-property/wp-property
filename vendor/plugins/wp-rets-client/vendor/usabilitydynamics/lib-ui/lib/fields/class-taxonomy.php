<?php

namespace UsabilityDynamics\UI {

	if (!class_exists('UsabilityDynamics\UI\Field_Taxonomy')) {

		class Field_Taxonomy extends Field {
			/**
			 * Enqueue scripts and styles
			 *
			 * @return void
			 */
			static public function admin_enqueue_scripts() {
				Field_Select_Advanced::admin_enqueue_scripts();
			}

			/**
			 * Add default value for 'taxonomy' field
			 *
			 * @param $field
			 *
			 * @return array
			 */
			static function normalize_field($field) {
				$default_args = array(
					'hide_empty' => false,
				);

				// Set default args
				$field['options']['args'] = !isset($field['options']['args']) ? $default_args : wp_parse_args($field['options']['args'], $default_args);

				$tax = get_taxonomy($field['options']['taxonomy']);
				$field['placeholder'] = empty($field['placeholder']) ? sprintf(__('Select a %s'), $tax->labels->singular_name) : $field['placeholder'];

				switch ($field['options']['type']) {
					case 'select_advanced':
						$field = Field_Select_Advanced::normalize_field($field);
						break;
					case 'checkbox_list':
					case 'checkbox_tree':
						$field = Field_Checkbox_List::normalize_field($field);
						break;
					case 'select':
					case 'select_tree':
						$field = Field_Select::normalize_field($field);
						break;
					default:
						$field['options']['type'] = 'select';
						$field = Field_Select::normalize_field($field);
				}

				if (in_array($field['options']['type'], array('checkbox_tree', 'select_tree'))) {
					if (isset($field['options']['args']['parent'])) {
						$field['options']['parent'] = $field['options']['args']['parent'];
						unset($field['options']['args']['parent']);
					} else {
						$field['options']['parent'] = 0;
					}
				}

				return $field;
			}

			/**
			 * Get field HTML
			 *
			 * @param $field
			 * @param $meta
			 *
			 * @return string
			 */
			static function html($meta, $field) {
				$options = $field->options;
				$terms = get_terms($options['taxonomy'], $options['args']);

				$field->options = self::get_options($terms);
				$field->display_type = $options['type'];

				$html = '';

				switch ($options['type']) {
					case 'checkbox_list':
						$html = Field_Checkbox_List::html($meta, $field);
						break;
					case 'checkbox_tree':
						$elements = self::process_terms($terms);
						$html .= self::walk_checkbox_tree($meta, $field, $elements, $options['parent'], true);
						break;
					case 'select_tree':
						$elements = self::process_terms($terms);
						$html .= self::walk_select_tree($meta, $field, $elements, $options['parent'], true);
						break;
					case 'select_advanced':
						$html = Field_Select_Advanced::html($meta, $field);
						break;
					case 'select':
					default:
						$html = Field_Select::html($meta, $field);
				}

				return $html;
			}

			/**
			 * Walker for displaying checkboxes in tree format
			 *
			 * @param      $meta
			 * @param      $field
			 * @param      $elements
			 * @param int $parent
			 * @param bool $active
			 *
			 * @return string
			 */
			static function walk_checkbox_tree($meta, $field, $elements, $parent = 0, $active = false) {
				if (!isset($elements[$parent]))
					return '';
				$terms = $elements[$parent];
				$field['options'] = self::get_options($terms);
				$hidden = $active ? '' : 'hidden';

				$html = "<ul class = 'rw-taxonomy-tree {$hidden}'>";
				$li = '<li><label><input type="checkbox" name="%s" value="%s"%s> %s</label>';
				foreach ($terms as $term) {
					$html .= sprintf(
						$li,
						$field->field_name,
						$term->term_id,
						checked(in_array($term->term_id, $meta), true, false),
						$term->name
					);
					$html .= self::walk_checkbox_tree($meta, $field, $elements, $term->term_id, $active && in_array($term->term_id, $meta)) . '</li>';
				}
				$html .= '</ul>';

				return $html;
			}

			/**
			 * Walker for displaying select in tree format
			 *
			 * @param        $meta
			 * @param        $field
			 * @param        $elements
			 * @param int $parent
			 * @param bool $active
			 *
			 * @return string
			 */
			static function walk_select_tree($meta, $field, $elements, $parent = 0, $active = false) {
				if (!isset($elements[$parent]))
					return '';
				$terms = $elements[$parent];
				$field->options = self::get_options($terms);

				$classes = array('rw-taxonomy-tree');
				$classes[] = $active ? 'active' : 'disabled';
				$classes[] = "uisf-taxonomy-{$parent}";

				$html = '<div class="' . implode(' ', $classes) . '">';
				$html .= RWMB_Select_Field::html($meta, $field);
				foreach ($terms as $term) {
					$html .= self::walk_select_tree($meta, $field, $elements, $term->term_id, $active && in_array($term->term_id, $meta));
				}
				$html .= '</div>';

				return $html;
			}

			/**
			 * Processes terms into indexed array for walker functions
			 *
			 * @param $terms
			 *
			 * @internal param $field
			 * @return array
			 */
			static function process_terms($terms) {
				$elements = array();
				foreach ($terms as $term) {
					$elements[$term->parent][] = $term;
				}

				return $elements;
			}

			/**
			 * Get options for selects, checkbox list, etc via the terms
			 *
			 * @param array $terms Array of term objects
			 *
			 * @return array
			 */
			static function get_options($terms = array()) {
				$options = array();
				foreach ($terms as $term) {
					$options[$term->term_id] = $term->name;
				}

				return $options;
			}

			/**
			 * Standard meta retrieval
			 *
			 * @param int $post_id
			 * @param bool $saved
			 * @param array $field
			 *
			 * @return array
			 */
			static function meta($post_id, $saved, $field) {
				$options = $field['options'];

				$meta = wp_get_post_terms($post_id, $options['taxonomy']);
				$meta = is_array($meta) ? $meta : (array)$meta;
				$meta = wp_list_pluck($meta, 'term_id');

				return $meta;
			}
		}
	}
}
