<?php
/**
 * Conditional Logic Main Class
 */
class MB_Conditional_Logic
{
	public function __construct()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		add_action( 'rwmb_before', array( $this, 'insert_meta_box_conditions' ) );

		add_filter( 'rwmb_wrapper_html', array( $this, 'insert_field_conditions' ), 10, 3 );
	}

	/**
	 * Enqueue Conditional Logic script and pass conditional logic values to it
	 * 
	 * @param  String $hook Page
	 * 
	 * @return void
	 */
	public function enqueue( $hook )
	{
		$conditions 		= $this->get_all_conditions();
  
		list( , $url ) = RWMB_Loader::get_path( dirname( dirname( __FILE__ ) ) );
		// backward compatibility
		$url = defined( 'MBC_JS_URL' ) ? MBC_JS_URL : $url . 'assets/js/';

		wp_register_script( 'conditional-logic', $url . 'conditional-logic.js', array(), '1.4', true );

		wp_localize_script( 'conditional-logic', 'conditions', $conditions );
		
		wp_enqueue_script( 'conditional-logic' );
	}

	/**
	 * Insert data-conditions="{JSON}" to Meta Boxes or Fields.
	 *
	 * @since  1.2
	 * 
	 * @return void
	 */
	public function insert_meta_box_conditions( $obj )
	{
        $toggle_type = 'display';
        
        if  ( ! empty( $obj->meta_box['toggle_type'] ) && in_array($toggle_type, array( 'display', 'visibility' ) ) ) {
            $toggle_type = $obj->meta_box['toggle_type'];
        }
        
        echo '<div class="mbc-toggle-type" style="display: none; visibility: hidden" data-toggle_type="' . $toggle_type . '"></div>';
        
		if ( empty( $obj->meta_box ) || ( empty( $obj->meta_box['visible'] ) && empty( $obj->meta_box['hidden'] ) ) )
			return;

		$conditions = array( 'visible', 'hidden' );

		foreach ( $conditions as $index => $visibility )
		{
			unset( $conditions[$index] );

			if ( isset( $obj->meta_box[$visibility] ) )
				$conditions[$visibility] = $this->parse_condition( $obj->meta_box[$visibility] );
		}
		
		echo '<div style="display: none; visibility: hidden" class="rwmb-conditions" data-conditions="'. esc_attr( json_encode( $conditions ) ) . '"></div>';
	}

	public function insert_field_conditions( $begin, $field, $meta )
	{
		if ( empty( $field['visible'] ) && empty( $field['hidden'] ) )
			return $begin;

		$conditions = array( 'visible', 'hidden' );

		foreach ( $conditions as $index => $visibility )
		{
			unset( $conditions[$index] );

			if ( isset( $field[$visibility] ) )
				$conditions[$visibility] = $this->parse_condition( $field[$visibility] );
		}

		$begin .= '<div style="display: none; visibility: hidden" class="rwmb-conditions" data-conditions="'. esc_attr( json_encode( $conditions ) ) .'"></div>';

		return $begin;	
	}

	/**
	 * Get all attached conditional logic on fields or meta boxes
	 * 
	 * @return Mixed All attached conditional logic
	 */
	public function get_all_conditions()
	{
		$outside_conditions = apply_filters( 'rwmb_outside_conditions', array() );

		$conditions = array();

		foreach ( $outside_conditions as $field_id => $field_conditions )
		{
			if ( empty( $field_id ) ) 
				continue;

			if ( ! empty( $field_conditions['visible'] ) )
				$conditions[$field_id]['visible'] = $this->parse_condition( $field_conditions['visible'] );

			if ( ! empty( $field_conditions['hidden'] ) )
				$conditions[$field_id]['hidden'] = $this->parse_condition( $field_conditions['hidden'] );
		}

		return $conditions;	
	}

	/**
	 * Parse various style of a collection to JS readable
	 * 
	 * @param  Mixed $condition Condition
	 * 
	 * @return Array
	 */
	public function parse_condition( $condition )
	{
		if ( ! is_array( $condition ) ) 
			return;

		$relation = ( isset( $condition['relation'] ) && in_array( $condition['relation'], array('and', 'or') ) ) 
					? $condition['relation'] : 'and';

		$condition_to_normalize = $condition;
		if ( isset( $condition['when'] ) && is_array( $condition['when'] ) )
			$condition_to_normalize = $condition['when'];
		
		$when = $this->get_normalized_criteria( $condition_to_normalize );

		return compact( 'when', 'relation' );
	}

	private function get_normalized_criteria( $condition )
	{
		$normalized = array();

		foreach ( $condition as $criteria )
		{
			if ( is_array( $criteria ) )
			{
				$normalized[] = $this->normalize_criteria( $criteria );
			}
			else 
			{
				$normalized[] = $this->normalize_criteria( $condition );
				break;
			}
		}

		return $normalized;
	}

	/**
	 * If criteria has different format than normally, reformat it
	 * 
	 * @param  array $criteria Criteria to be formatted
	 * @return array Criteria after formatted
	 */
	public function normalize_criteria( $criteria )
	{
		$criteria_length = count( $criteria );
		
		if ( $criteria_length === 2 ) 
			$criteria = array($criteria[0], '=', $criteria[1]);

		// Convert slug to id if conditional logic defined using slug for terms
		if ( strrpos($criteria[0], 'slug:', -strlen($criteria[0])) !== false )
		{
			$criteria[0] = ltrim( $criteria[0], 'slug:' );

			$criteria[2] = $this->slug_to_id( $criteria[2] );
		}

		return $criteria;
	}

	/**
	 * Convert slug to id
	 * 
	 * @param  Array $slugs Array of slugs
	 * 
	 * @return Array Array of ids
	 */
	private function slug_to_id( $slugs )
	{
		global $wpdb;
		
		$slugs 		= (array) $slugs;
		$sql 		= "SELECT term_id FROM {$wpdb->terms} WHERE slug IN(".implode(', ', array_fill(0, count($slugs), '%s')).")";
		$prepared 	= call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $slugs));

		return array_map('intval', $wpdb->get_col($prepared));
	}
}