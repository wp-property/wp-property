<?php
// Prevent loading this file directly
// Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Property_Type_Field' ) ){
	class RWMB_Wpp_Property_Type_Field extends RWMB_Taxonomy_Field{
		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		static function admin_enqueue_scripts(){
			wp_enqueue_style( 'field-wpp-property-type', ud_get_wp_property()->path( 'static/styles/fields/wpp-property-type.css' ), array(),  ud_get_wp_property('version'));
			wp_enqueue_script( 'field-wpp-property-type', ud_get_wp_property()->path( 'static/scripts/fields/wpp-property-type.js' ), array( 'jquery', 'jquery-ui-autocomplete', 'underscore' ), ud_get_wp_property('version'), true );
		}

		/**
		 * Get field HTML
		 *
		 * @param $field
		 * @param $meta
		 *
		 * @return string
		 */
		static function html( $meta, $field ){

			$terms = array();
			$_terms = get_terms( array(
				'taxonomy' => 'wpp_listing_type',
				'hide_empty' => false,
			) );

			if( is_taxonomy_hierarchical( $field['options']['taxonomy'] ) ) {
				$_terms = self::prepare_terms_hierarchicaly( $_terms );
			}

			foreach ($_terms as $term) {
				$terms[] = array(
					'value' => $term->term_id,
					'label' => $term->name
				);
			}

			$term = false;
			foreach( $_terms as $_term ) {
				if( $meta == $_term->term_id ) {
					$term = $_term;
					break;
				}
			}

			$options = $field['options'];
			$field_name = $field['field_name'];

			if(substr($field_name, -2) == '[]')
				$field_name = substr_replace($field_name, '', -2);

			ob_start();
			?>
			<div class="rwmb-field wpp-property-type wpp_ui"
			     data-taxonomy="<?php echo $options['taxonomy'];?>"
			     data-terms='<?php echo json_encode($terms);?>'
				>
				<div class="clearfix term">
					<input
						type = "hidden"
						name="<?php echo $field_name;?>"
						class="ui-corner-left wpp-terms-term wpp-terms-term-id"
						autocomplete="off"
						value="<?php echo $term ? $term->term_id : '';?>"
						>
					<input
						type = "text"
						class="ui-corner-left wpp-terms-input wpp-terms-term wpp-terms-term-label"
						autocomplete="off"
						value="<?php echo $term ? $term->name : '';?>"
						>
					<a tabindex="-1" title="Show All Items" class="ui-widget ui-state-default ui-button-icon-only select-combobox-toggle ui-corner-right" role="button">
						<span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span>
					</a>
				</div>
			</div>
			<?php
			$html = ob_get_clean();

			return $html;

		}

		/**
		 * Meta retrieval
		 *
		 * @param int   $post_id
		 * @param bool  $saved
		 * @param array $field
		 *
		 * @return array
		 */
		static function meta( $post_id, $saved, $field )
		{
			$taxonomy = $field['options']['taxonomy'];
			$terms = wp_get_object_terms( $post_id, $taxonomy );

			if( !$terms || !count( $terms ) ) {
				// @TODO: add back compatibility with property_type meta if no term provided
				return '';
			}

			$meta = false;
			$map = array();
			$_terms = array();

			foreach( $terms as $term ) {
				$_terms[ $term->term_id ] = $term;
				if( !$term->parent ) {
					$meta = $term;
				}
				if( $term->parent ) {
					$map[ $term->parent ] = $term->term_id;
				}
			}

			if( $meta ) {
				while( isset( $map[ $meta->term_id ] ) && isset( $_terms[ $map[ $meta->term_id ] ] ) ) {
					$meta = $_terms[ $map[ $meta->term_id ] ];
				}
			} else {
				return '';
			}

			return $meta->term_id;

		}

		/**
		 * Assign terms to post
		 *
		 * @param mixed $new
		 * @param mixed $old
		 * @param int   $post_id
		 * @param array $field
		 *
		 * @return string
		 */
		static function save( $new, $old, $post_id, $field ){

			if( $new !== $old ){

				$taxonomy = $field['options']['taxonomy'];

				$term = get_term_by('id', $new, $taxonomy);

				// Prevent adding non existing listing type term!
				if( !$term || is_wp_error($term) ) {
					return false;
				}

				$terms = [];

				array_push($terms, $term->term_id);

				if( $term->parent ) {
					$parent = get_term_by('id', $term->parent, $taxonomy);
					array_push( $terms, $parent->term_id );
					while ($parent->parent != '0'){
						$term_id = $parent->parent;
						$parent  = get_term_by( 'id', $term_id, $taxonomy);
						array_push( $terms, $parent->term_id );
					}
				}

				$terms = array_reverse( $terms );
				$terms = array_map( 'intval', $terms );

				wp_set_object_terms( $post_id, $terms, $taxonomy );

			}

		}

		/**
		 * Prepare_terms_hierarchicaly
		 *
		 * @param $terms
		 * @return array
		 */
		static public function prepare_terms_hierarchicaly($terms, $prefix = '>'){
			$_terms = array();
			$return = array();

			if(count($terms) == 0)
				return $return;

			// Prepering terms
			foreach ($terms as $term) {
				$_terms[$term->parent][] = (object)array('term_id' => $term->term_id, 'name' => $term->name);
			}

			// Making terms as hierarchical by prefix
			foreach ($_terms[0] as $term) { // $_terms[0] is parent or parentless terms
				$return[] = $term;
				self::get_children($term->term_id, $_terms, $return, ( $term->name . ' ' . $prefix ));
			}

			return $return;
		}

		/**
		 * Helper function for prepare_terms_hierarchicaly
		 *
		 * @param $term_id
		 * @param $terms
		 * @param $return
		 * @param string $prefix
		 */
		static public function get_children($term_id, $terms, &$return, $prefix = ">"){
			if(isset($terms[$term_id])){
				foreach ($terms[$term_id] as $child) {
					$child->name = $prefix . " " . $child->name;
					$return[] = $child;
					self::get_children($child->term_id, $terms, $return, ( $prefix . ' ' . $child->name . ' >' ));
				}
			}
		}

	}
}
