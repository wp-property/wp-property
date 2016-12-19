<?php
// Prevent loading this file directly
// Feature Flag: WPP_FEATURE_FLAG_WPP_TYPE
defined( 'ABSPATH' ) || exit;
require_once RWMB_FIELDS_DIR . 'checkbox-list.php';

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
						'taxonomy' => 'wpp_type',
						'hide_empty' => false,
					) );
			$options = $field['options'];
			$field_name = $field['field_name'];
			if(substr($field_name, -2) == '[]')
				$field_name = substr_replace($field_name, '', -2);

			foreach ($_terms as $term) {
				$terms[] = $term->name;
			}

			$meta     = array_values($meta);
			$term_id  = '';
			$term_name  = '';
			if(isset($meta[0])){
				$term_id = $meta[0];
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
	}
}
