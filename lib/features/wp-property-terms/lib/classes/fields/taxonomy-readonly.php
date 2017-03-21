<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Taxonomy_Readonly_Field' ) ){
	class RWMB_Wpp_Taxonomy_Readonly_Field extends RWMB_Field{
		/**
		 * Enqueue scripts and styles
		 *
		 * @return void
		 */
		static function admin_enqueue_scripts(){

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

			$taxonomy = $field['id'];
			$options = $field['options'];

			ob_start();
			$template = ud_get_wpp_terms()->path( 'static/views/admin/field-taxonomy-readonly.php', 'dir' );
			if( file_exists( $template ) ) {
				include( $template );
			}
			$html = ob_get_clean();

			/*
			ob_start();
			echo "<pre>";
			print_r($meta);
			echo "</pre>";
			echo "<pre>";
			print_r($field);
			echo "</pre>";
			$html = ob_get_clean();
			//*/

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
					else if($parent && !($p = term_exists($parent, $field['options']['taxonomy']))){
						// Inserting new new term.
						$p = wp_insert_term( $parent, $field['options']['taxonomy']);
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
					else if(!$t = term_exists($name, $field['options']['taxonomy'])){
						// Inserting new new term.
						$t 		= wp_insert_term( $name, $field['options']['taxonomy'], array('parent'=>$p['term_id']));
					}

					if(!is_wp_error($t))
						$term_ids[] = $t['term_id'];
				}
			}

			$term_ids = array_map( 'intval', $term_ids );
			wp_set_object_terms( $post_id, $term_ids, $field['options']['taxonomy'] );
		}
	}
}
