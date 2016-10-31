<?php
/*
 * Utility Group
 *
 */

namespace UsabilityDynamics\MetaBox {

	// Prevent loading this file directly
	defined('ABSPATH') || exit;

	if (!class_exists('UsabilityDynamics\MetaBox\Group')) {

		class Group {
			/**
			 * Indicate that the meta box is saved or not
			 * This variable is used inside group field to show child fields
			 *
			 * @var bool
			 */
			static $saved = false;

			/**
			 * Add hooks to meta box
			 *
			 */
			public function __construct() {
				if (!is_admin())
					return;

				// Make sure Meta Box files are loaded, because we extend base field class
				add_action('plugins_loaded', array($this, 'load_files'));

				add_action('rwmb_before', array($this, 'set_saved'));
				add_action('rwmb_after', array($this, 'unset_saved'));
			}

			/**
			 * Load field group class
			 *
			 * @return array
			 */
			public function load_files() {
				if (!class_exists('RWMB_Group_Field')) {
					require_once Bootstrap::path('group.php');
				}
			}

			/**
			 * Check if current meta box is saved
			 * This variable is used inside group field to show child fields
			 *
			 * @param $obj
			 *
			 * @return void
			 */
			public function set_saved($obj) {
				global $post;
				self::$saved = \RW_Meta_Box::has_been_saved($post->ID, $obj->fields);
			}

			/**
			 * Unset 'saved' variable, to be ready for next meta box
			 *
			 * @return void
			 */
			public function unset_saved() {
				self::$saved = false;
			}
		}
	}
}
