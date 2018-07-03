<?php
/**
 * Group field class.
 *
 * @package    Meta Box
 * @subpackage Meta Box Group
 */

/**
 * Class for group field.
 *
 * @package    Meta Box
 * @subpackage Meta Box Group
 */
class RWMB_Group_Field extends RWMB_Field {
	/**
	 * Queue to store the group fields' meta(s). Used to get child field meta.
	 *
	 * @var array
	 */
	protected static $meta_queue = array();

	/**
	 * Add hooks for sub-fields.
	 */
	public static function add_actions() {
		// Group field is the 1st param.
		$args = func_get_args();
		foreach ( $args[0]['fields'] as $field ) {
			RWMB_Field::call( $field, 'add_actions' );
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		// Group field is the 1st param.
		$args   = func_get_args();
		$fields = $args[0]['fields'];

		// Load clone script conditionally.
		foreach ( $fields as $field ) {
			if ( $field['clone'] ) {
				wp_enqueue_script( 'rwmb-clone', RWMB_JS_URL . 'clone.js', array( 'jquery-ui-sortable' ), RWMB_VER, true );
				break;
			}
		}

		// Enqueue sub-fields scripts and styles.
		foreach ( $fields as $field ) {
			RWMB_Field::call( $field, 'admin_enqueue_scripts' );
		}

		// Use helper function to get correct URL to current folder, which can be used in themes/plugins.
		list( , $url ) = RWMB_Loader::get_path( dirname( __FILE__ ) );
		wp_enqueue_style( 'rwmb-group', $url . 'group.css', '', '1.1.2' );
		wp_enqueue_script( 'rwmb-group', $url . 'group.js', array( 'jquery', 'underscore' ), '1.1.2', true );
	}

	/**
	 * Get group field HTML.
	 *
	 * @param mixed $meta  Meta value.
	 * @param array $field Field parameters.
	 *
	 * @return string
	 */
	public static function html( $meta, $field ) {
		ob_start();

		self::output_collapsible_elements( $field );

		// Add filter to child field meta value, make sure it's added only once.
		if ( empty( self::$meta_queue ) ) {
			add_filter( 'rwmb_field_meta', array( __CLASS__, 'child_field_meta' ), 10, 3 );
		}

		// Add group value to the queue.
		array_unshift( self::$meta_queue, $meta );

		foreach ( $field['fields'] as $child_field ) {
			$child_field['field_name']       = self::child_field_name( $field['field_name'], $child_field['field_name'] );
			$child_field['attributes']['id'] = self::child_field_id( $field, $child_field );

			self::call( 'show', $child_field, RWMB_Group::$saved );
		}

		// Remove group value from the queue.
		array_shift( self::$meta_queue );

		// Remove filter to child field meta value and reset class's parent field's meta.
		if ( empty( self::$meta_queue ) ) {
			remove_filter( 'rwmb_field_meta', array( __CLASS__, 'child_field_meta' ) );
		}

		return ob_get_clean();
	}

	/**
	 * Output collapsible elements for groups.
	 *
	 * @param array $field Group field parameters.
	 */
	protected static function output_collapsible_elements( $field ) {
		if ( ! $field['collapsible'] ) {
			return;
		}

		// Group title.
		$title_attributes = array(
			'class'        => 'rwmb-group-title',
			'data-options' => $field['group_title'],
		);
		if ( is_string( $field['group_title'] ) ) {
			$title_attributes['data-options'] = array(
				'type'    => 'text',
				'content' => $field['group_title'],
			);
		} else {
			$title_attributes['data-options']['type'] = isset( $field['group_title']['field'] ) ? 'field' : 'text';
		}
		$title = '';
		if ( 'text' === $title_attributes['data-options']['type'] ) {
			$title = $title_attributes['data-options']['content'];
		}
		echo '<h4 ', self::render_attributes( $title_attributes ), '>', $title, '</h4>'; // WPCS: XSS OK.

		// Collapse/expand icon.
		echo '<button aria-expanded="true" class="rwmb-group-toggle-handle button-link"><span class="rwmb-group-toggle-indicator" aria-hidden="true"></span></button>';
	}

	/**
	 * Change the way we get meta value for child fields.
	 *
	 * @param mixed $meta        Meta value.
	 * @param array $child_field Child field.
	 * @param bool  $saved       Has the meta box been saved.
	 *
	 * @return mixed
	 */
	public static function child_field_meta( $meta, $child_field, $saved ) {
		$group_meta = reset( self::$meta_queue );
		$child_id   = $child_field['id'];
		if ( isset( $group_meta[ $child_id ] ) ) {
			$meta = $group_meta[ $child_id ];
		}

		// Use $field['std'] only when the meta box hasn't been saved (i.e. the first time we run).
		$meta = ! $saved && isset( $child_field['std'] ) ? $child_field['std'] : $meta;

		// Escape attributes.
		$meta = self::call( $child_field, 'esc_meta', $meta );

		/**
		 * Make sure meta value is an array for clonable and multiple fields.
		 *
		 * @see RWMB_Field::meta()
		 */
		if ( $child_field['clone'] || $child_field['multiple'] ) {
			if ( ! is_array( $meta ) && ! empty( $meta ) ) {
				$meta = array( $meta );
			}
			if ( empty( $meta ) || ! is_array( $meta ) ) {
				/**
				 * Note: if field is clonable, $meta must be an array with values.
				 * so that the foreach loop in self::show() runs properly.
				 *
				 * @see RWMB_Field::show()
				 */
				$meta = $child_field['clone'] ? array( '' ) : array();
			}
		}

		return $meta;
	}

	/**
	 * Get meta value, make sure value is an array (of arrays if field is cloneable).
	 * Don't escape value.
	 *
	 * @param int   $post_id Post ID.
	 * @param bool  $saved   Is the meta box saved.
	 * @param array $field   Field parameters.
	 *
	 * @return mixed
	 */
	public static function meta( $post_id, $saved, $field ) {
		$meta = self::raw_meta( $post_id, $field );

		// Use $field['std'] only when the meta box hasn't been saved (i.e. the first time we run).
		$meta = ! $saved && '' === $meta ? $field['std'] : $meta;

		// Make sure returned value is an array.
		if ( empty( $meta ) ) {
			$meta = array();
		}

		// If cloneable, make sure each sub-value is an array.
		if ( $field['clone'] ) {
			// Make sure there's at least 1 sub-value.
			if ( empty( $meta ) ) {
				$meta[0] = array();
			}

			foreach ( $meta as $k => $v ) {
				$meta[ $k ] = (array) $v;
			}
		}

		return $meta;
	}

	/**
	 * Escape meta for field output. Just do nothing for group.
	 *
	 * @param array $meta Meta value.
	 *
	 * @return array
	 */
	public static function esc_meta( $meta ) {
		return $meta;
	}

	/**
	 * Set value of meta before saving into database.
	 *
	 * @param mixed $new     The submitted meta value.
	 * @param mixed $old     The existing meta value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field parameters.
	 *
	 * @return array
	 */
	public static function value( $new, $old, $post_id, $field ) {
		if ( empty( $field['fields'] ) || ! is_array( $field['fields'] ) ) {
			return array();
		}

		$child_fields = $field['fields'];
		if ( ! $new || ! is_array( $new ) ) {
			$new = array();
		}
		foreach ( $child_fields as $child_field ) {
			if ( ! in_array( $child_field['type'], array( 'file', 'image' ) ) ) {
				continue;
			}

			$value = RWMB_File_Field::value( '', '', $post_id, $child_field );
			$new[ $child_field['id'] ] = $value;
		}

		return self::sanitize( $new, $old, $post_id, $field );
	}

	/**
	 * Sanitize value of meta before saving into database.
	 *
	 * @param mixed $new     The submitted meta value.
	 * @param mixed $old     The existing meta value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field parameters.
	 *
	 * @return array
	 */
	public static function sanitize( $new, $old, $post_id, $field ) {
		$sanitized = array();

		if ( ! $new || ! is_array( $new ) ) {
			return $sanitized;
		}

		foreach ( $new as $key => $value ) {
			if ( is_array( $value ) && ! empty( $value ) ) {
				$value = self::sanitize( $value, '', '', array() );
			}
			if ( '' !== $value && array() !== $value ) {
				if ( is_int( $key ) ) {
					$sanitized[] = $value;
				} else {
					$sanitized[ $key ] = $value;
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Normalize group fields.
	 *
	 * @param array $field Field parameters.
	 *
	 * @return array
	 */
	public static function normalize( $field ) {
		$field           = parent::normalize( $field );
		$field['fields'] = RW_Meta_Box::normalize_fields( $field['fields'] );

		$field = wp_parse_args( $field, array(
			'collapsible'   => false,
			'save_state'    => false,
			'group_title'   => $field['clone'] ? __( 'Entry {#}', 'meta-box-group' ) : __( 'Entry', 'meta-box-group' ),
			'default_state' => 'expanded',
		) );

		if ( $field['collapsible'] ) {
			$field['class'] .= ' rwmb-group-collapsible';
		}
		// Add a new hidden field to save the collapse/expand state.
		if ( $field['save_state'] ) {
			$field['fields'][] = RWMB_Input_Field::normalize( array(
				'type'       => 'hidden',
				'id'         => '_state',
				'std'        => $field['default_state'],
				'class'      => 'rwmb-group-state',
				'attributes' => array(
					'data-current' => $field['default_state'],
				),
			) );
		}
		if ( ! $field['clone'] ) {
			$field['class'] .= ' rwmb-group-non-cloneable';
		}

		return $field;
	}

	/**
	 * Change child field name from 'child' to 'parent[child]'.
	 *
	 * @param string $parent Parent field's name.
	 * @param string $child  Child field's name.
	 *
	 * @return string
	 */
	protected static function child_field_name( $parent, $child ) {
		$pos  = strpos( $child, '[' );
		$pos  = false === $pos ? strlen( $child ) : $pos;
		$name = $parent . '[' . substr( $child, 0, $pos ) . ']' . substr( $child, $pos );

		return $name;
	}

	/**
	 * Change child field attribute id to from 'id' to 'parent_id'.
	 *
	 * @param array $parent      Parent field.
	 * @param array $child       Child field.
	 *
	 * @return string
	 */
	protected static function child_field_id( $parent, $child ) {
		$parent = isset( $parent['attributes']['id'] ) ? $parent['attributes']['id'] : $parent['id'];
		$child  = isset( $child['attributes']['id'] ) ? $child['attributes']['id'] : $child['id'];

		return "{$parent}_{$child}";
	}
}
