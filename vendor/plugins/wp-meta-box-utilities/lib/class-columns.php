<?php
/*
 * Utility Columns
 *
 */

namespace UsabilityDynamics\MetaBox {

	// Prevent loading this file directly
	defined('ABSPATH') || exit;

	if (!class_exists('UsabilityDynamics\MetaBox\Columns')) {

		class Columns {

			/**
			 * Add hooks to meta box
			 *
			 */
			function __construct() {
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
				add_filter('rwmb_normalize_field', array($this, 'normalize_field'));
			}

			/**
			 * Enqueue scripts and styles for tabs
			 *
			 * @return void
			 */
			public function admin_enqueue_scripts() {
				// Enqueue scripts and styles for post edit screen only
				$screen = get_current_screen();
				if ('post' != $screen->base)
					return;

				wp_enqueue_style('rwmb-columns', Bootstrap::path( 'static/styles/columns.css', 'url' ) );
			}

			/**
			 * Add column class to field and output opening/closing div for row
			 *
			 * @param array $field
			 *
			 * @return array
			 */
			function normalize_field($field) {
				static $total_columns = 0;

				if (empty($field['columns']))
					return $field;

				// Column class
				if (empty($field['class']))
					$field['class'] = '';
				$field['class'] .= ' rwmb-column rwmb-column-' . $field['columns'];

				// First column: add .first class and opening div
				if (0 == $total_columns) {
					$field['class'] .= ' rwmb-column-first';
					$field['before'] = '<div class="rwmb-row">' . $field['before'];
				}

				$total_columns += $field['columns'];

				// Last column: add .last class, closing div and reset total count
				if (12 == $total_columns) {
					$field['class'] .= ' rwmb-column-last';
					$field['after'] .= '</div>';
					$total_columns = 0;
				}

				$field['class'] = trim($field['class']);

				return $field;
			}
		}
	}

}
