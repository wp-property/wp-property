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
				foreach ($_terms as $term) {
					$terms[] = array(
						'value' => $term[ 'term_id' ],
						'label' => $term[ 'name' ]
					);
				}
			} else {
				foreach ($_terms as $term) {
					$terms[] = $term->name;
				}
			}

			$options = $field['options'];
			$field_name = $field['field_name'];
			if(substr($field_name, -2) == '[]')
				$field_name = substr_replace($field_name, '', -2);

			if(is_array($meta)){
				$meta = array_values($meta);
				$meta = $meta[0];
			}

			$term_id  = '';
			$term_name  = '';
			if($meta){
				$term_id = $meta;
				$term = get_term( $term_id , $options['taxonomy'] );
				$term_name  = $term->name;
			}
			ob_start();
			?>
			<div class="rwmb-field wpp-property-type wpp_ui"
			     data-taxonomy="<?php echo $options['taxonomy'];?>"
			     data-terms='<?php echo json_encode($terms);?>'
				>
				<div class="clearfix term">
					<input
						type = "text"
						name="<?php echo $field_name;?>"
						class="ui-corner-left wpp-terms-input wpp-terms-term"
						autocomplete="off"
						value="<?php echo $term_name;?>"
						>
					<a tabindex="-1" title="Show All Items" class="ui-widget ui-state-default ui-button-icon-only select-combobox-toggle ui-corner-right" role="button">
						<span class="ui-button-icon-primary ui-icon ui-icon-triangle-1-s"></span>
					</a>
				</div>
			</div>
			<?php
			$html = ob_get_clean();

			return $html;

			if( is_taxonomy_hierarchical( $field['options']['taxonomy'] ) ) {
				return self::_html_hierarchical( $meta, $field );
			} else {
				return self::_html_default( $meta, $field );
			}

		}

		/**
		 * Standard meta retrieval
		 *
		 * @param int   $post_id
		 * @param bool  $saved
		 * @param array $field
		 *
		 * @return array
		 */
		static function meta( $post_id, $saved, $field )
		{
			$options = $field['options'];

			$meta = parent::meta( $post_id, $saved, $field );
			if (empty($meta)) {
				$_meta = get_post_meta($post_id, 'property_type', true);
				$term = get_term_by('slug', $_meta, 'wpp_listing_type');
				$meta = isset($term->term_id)? (array) $term->term_id : $meta;
			}
			return $meta;
		}

		/**
		 * Save meta value
		 *
		 * @param mixed $new
		 * @param mixed $old
		 * @param int   $post_id
		 * @param array $field
		 *
		 * @return string
		 */
		static function save( $new, $old, $post_id, $field ){
			$term_ids = array();
			
			if($new){
				// Checking for existing terms
				if(!$t = term_exists($new, $field['options']['taxonomy'])){
					// Inserting new new term.
					$t = wp_insert_term( $new, $field['options']['taxonomy'], array('parent'=>$p['term_id']));
				}

				if($t && !is_wp_error($t)){
					$term_ids[] = $t['term_id'];
				}
			}

			$term_ids = array_map( 'intval', $term_ids );
			wp_set_object_terms( $post_id, $term_ids, $field['options']['taxonomy'] );
		}

		/**
		 * Prepare_terms_hierarchicaly
		 *
		 * @param $terms
		 * @return array
		 */
		static public function prepare_terms_hierarchicaly($terms){
			$_terms = array();
			$return = array();

			if(count($terms) == 0)
				return $return;

			// Prepering terms
			foreach ($terms as $term) {
				$_terms[$term->parent][] = array('term_id' => $term->term_id, 'name' => $term->name);
			}

			// Making terms as hierarchical by prefix
			foreach ($_terms[0] as $term) { // $_terms[0] is parent or parentless terms
				$return[] = $term;
				self::get_children($term['term_id'], $_terms, $return, ( $term['name'] . ' -' ));
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
		static public function get_children($term_id, $terms, &$return, $prefix = "-"){
			if(isset($terms[$term_id])){
				foreach ($terms[$term_id] as $child) {
					$child['name'] = $prefix . " " . $child['name'];
					$return[] = $child;
					self::get_children($child['term_id'], $terms, $return, ( $prefix . ' ' . $child['name'] . ' -' ));
				}
			}
		}

	}
}
