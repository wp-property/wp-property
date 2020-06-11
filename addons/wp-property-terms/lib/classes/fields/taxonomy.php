<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Taxonomy_Field' ) ){
	class RWMB_Wpp_Taxonomy_Field extends RWMB_Taxonomy_Field{
		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		static function admin_enqueue_scripts(){
			RWMB_Select_Advanced_Field::admin_enqueue_scripts();
			RWMB_Wpp_Select_Advanced_Field::admin_enqueue_scripts();
			RWMB_Wpp_Select_Combobox_Field::admin_enqueue_scripts();
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
			$options = $field['options'];

			$html = '';

			switch ( $options['type'] ){
				case 'select_tree':
				case 'select_advanced':
					if($field['multiple'] == true){
						$html = RWMB_Wpp_Select_Advanced_Field::html( $meta, $field );
					}
					else{ // if it's not  multiple using default select advance field
						$html = RWMB_Wpp_Select_Combobox_Field::html( $meta, $field );
					}
					break;
				case 'select':
				default:
					$html = RWMB_Wpp_Select_Combobox_Field::html( $meta, $field );
			}

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
      $field_taxonomy = is_array($field['taxonomy']) ? $field['taxonomy'][0] : $field['taxonomy'];
			if(empty( $new ) || count($new) == 0){
				$new = null;
			}
			else{
				foreach ($new as $key => $term) {
					$name		= $term['term'];
					if(!$name)
						continue;
					$parent 	= (isset($term['parent']) && $term['parent'])?$term['parent']:0;

					// It's id so remove prefix and use id.
					if(strpos($parent, 'tID_') !== false){
						$id 	= intval(str_replace('tID_', '', $parent));
						$p	= array('term_id'=>$id);
					}
					// Doing another check before insert.
					else if($parent && !($p = term_exists($parent, $field_taxonomy))){
						// Inserting new new term.
						$p = wp_insert_term( $parent, $field_taxonomy);
					}
					else{
						$p = array('term_id'=> 0);
					}
					// It's id so remove prefix and use id.
					if(strpos($name, 'tID_') !== false){
						$id 	= intval(str_replace('tID_', '', $name));
						$t		= array('term_id'=>$id);
					}
					// Doing another check before insert.
					else if(!$t = term_exists($name, $field_taxonomy)){
						// Inserting new new term.
						$t 		= wp_insert_term( $name, $field_taxonomy, array('parent'=>$p['term_id']));
					}

					if(!is_wp_error($t))
						$term_ids[] = $t['term_id'];
				}
			}

			$term_ids = array_map( 'intval', $term_ids );
			wp_set_object_terms( $post_id, $term_ids, $field_taxonomy );
		}
	}
}
