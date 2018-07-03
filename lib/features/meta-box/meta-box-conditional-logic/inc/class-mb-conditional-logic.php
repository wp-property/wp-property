<?php
/**
 * The main class of the plugin.
 *
 * @package    Meta Box
 * @subpackage Meta Box Conditional Logic
 */

/**
 * Conditional Logic Main Class
 */
class MB_Conditional_Logic {
	/**
	 * Initialize.
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		add_action( 'rwmb_before', array( $this, 'insert_meta_box_conditions' ) );

		add_filter( 'rwmb_wrapper_html', array( $this, 'insert_field_conditions' ), 10, 3 );
	}

	/**
	 * Enqueue Conditional Logic script and pass conditional logic values to it
	 */
	public function enqueue() {
		$conditions = $this->get_all_conditions();

		list( , $url ) = RWMB_Loader::get_path( dirname( dirname( __FILE__ ) ) );

		if ( ! is_admin() && ! defined( 'MB_FRONTEND_SUBMISSION_DIR' ) ) {
			return;
		}

		// Backward compatibility.
		$url = defined( 'MBC_JS_URL' ) ? MBC_JS_URL : $url . 'js/';

		wp_register_script( 'conditional-logic', $url . 'conditional-logic.js', array(), '1.5', true );

		wp_localize_script( 'conditional-logic', 'conditions', $conditions );

		wp_enqueue_script( 'conditional-logic' );
	}

	/**
	 * Insert field condition.
	 *
	 * @param string $begin The beginning HTML output of the field.
	 * @param array  $field Field settings.
	 * @param mixed  $meta  Field meta value.
	 *
	 * @return string
	 */
	public function insert_field_conditions( $begin, $field, $meta ) {
		if ( empty( $field['visible'] ) && empty( $field['hidden'] ) ) {
			return $begin;
		}

		$conditions = array( 'visible', 'hidden' );

		foreach ( $conditions as $index => $visibility ) {
			unset( $conditions[ $index ] );

			if ( isset( $field[ $visibility ] ) ) {
				$conditions[ $visibility ] = $this->parse_condition( $field[ $visibility ] );
			}
		}

		$begin .= '<script type="html/template" class="rwmb-conditions" data-conditions="' . esc_attr( wp_json_encode( $conditions ) ) . '"></script>';

		return $begin;
	}

	/**
	 * Get all attached conditional logic on fields or meta boxes
	 *
	 * @return Mixed All attached conditional logic
	 */
	public function get_all_conditions() {
		$outside_conditions = apply_filters( 'rwmb_outside_conditions', array() );

		$conditions = array();

		foreach ( $outside_conditions as $field_id => $field_conditions ) {
			if ( empty( $field_id ) ) {
				continue;
			}

			if ( ! empty( $field_conditions['visible'] ) ) {
				$conditions[ $field_id ]['visible'] = $this->parse_condition( $field_conditions['visible'] );
			}

			if ( ! empty( $field_conditions['hidden'] ) ) {
				$conditions[ $field_id ]['hidden'] = $this->parse_condition( $field_conditions['hidden'] );
			}
		}

		return $conditions;
	}

	/**
	 * Parse various style of a collection to JS readable
	 *
	 * @param  Mixed $condition Condition.
	 *
	 * @return array
	 */
	public function parse_condition( $condition ) {
		if ( ! is_array( $condition ) ) {
			return array(
				'when'     => array(),
				'relation' => 'and',
			);
		}

		$relation = ( isset( $condition['relation'] ) && in_array( $condition['relation'], array( 'and', 'or' ) ) )
			? $condition['relation'] : 'and';

		$condition_to_normalize = $condition;
		if ( isset( $condition['when'] ) && is_array( $condition['when'] ) ) {
			$condition_to_normalize = $condition['when'];
		}

		$when = $this->get_normalized_criteria( $condition_to_normalize );

		return compact( 'when', 'relation' );
	}

	/**
	 * Normalize all condition criteria.
	 *
	 * @param array $condition List of condition criteria.
	 *
	 * @return array
	 */
	private function get_normalized_criteria( $condition ) {
		$normalized = array();

		foreach ( $condition as $criteria ) {
			if ( is_array( $criteria ) ) {
				$normalized[] = $this->normalize_criteria( $criteria );
			} else {
				$normalized[] = $this->normalize_criteria( $condition );
				break;
			}
		}

		return $normalized;
	}

	/**
	 * Insert data-conditions="{JSON}" to Meta Boxes or Fields.
	 *
	 * @param RW_Meta_Box $obj The meta box object.
	 *
	 * @since  1.2
	 */
	public function insert_meta_box_conditions( $obj ) {
		$toggle_type = 'display';

		if ( ! empty( $obj->meta_box['toggle_type'] ) && in_array( $toggle_type, array( 'display', 'visibility' ) ) ) {
			$toggle_type = $obj->meta_box['toggle_type'];
		}

		echo '<script type="html/template" class="mbc-toggle-type" data-toggle_type="' . esc_attr( $toggle_type ) . '"></script>';

		if ( empty( $obj->meta_box ) || ( empty( $obj->meta_box['visible'] ) && empty( $obj->meta_box['hidden'] ) ) ) {
			return;
		}

		$conditions = array( 'visible', 'hidden' );

		foreach ( $conditions as $index => $visibility ) {
			unset( $conditions[ $index ] );

			if ( isset( $obj->meta_box[ $visibility ] ) ) {
				$conditions[ $visibility ] = $this->parse_condition( $obj->meta_box[ $visibility ] );
			}
		}

		echo '<script type="html/template" class="rwmb-conditions" data-conditions="' . esc_attr( wp_json_encode( $conditions ) ) . '"></script>';
	}

	/**
	 * If criteria has different format than normally, reformat it
	 *
	 * @param  array $criteria Criteria to be formatted.
	 *
	 * @return array Criteria after formatted.
	 */
	public function normalize_criteria( $criteria ) {
		$criteria_length = count( $criteria );

		if ( 2 === $criteria_length ) {
			$criteria = array( $criteria[0], '=', $criteria[1] );
		}

		// Convert slug to id if conditional logic defined using slug for terms.
		if ( strrpos( $criteria[0], 'slug:', - strlen( $criteria[0] ) ) !== false ) {
			$criteria[0] = ltrim( $criteria[0], 'slug:' );

			$criteria[2] = $this->slug_to_id( $criteria[2] );
		}

		return $criteria;
	}

	/**
	 * Convert slug to id.
	 *
	 * @param  array $slugs Array of slugs.
	 *
	 * @return array Array of ids.
	 */
	private function slug_to_id( $slugs ) {
		global $wpdb;

		$slugs    = (array) $slugs;
		$sql      = "SELECT term_id FROM {$wpdb->terms} WHERE slug IN(" . implode( ', ', array_fill( 0, count( $slugs ), '%s' ) ) . ')';
		$prepared = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $slugs ) );

		return array_map( 'intval', $wpdb->get_col( $prepared ) );
	}
}
