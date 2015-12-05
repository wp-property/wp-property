<?php
/**
 * WP-Property Compatibility with WPML
 *
 *
 * @version 2.00
 * @author Fadi Yousef <frontend-expert@outlook.com>
 * @package WP-Property
 * @subpackage Functions
 */
 
namespace UsabilityDynamics\WPP {

class WPML{
	public $is_active;
	
	function __construct() {

		$this->is_active = ( function_exists('icl_object_id') ) ? true : false ;

	}
	/*
	* get properity posts count by language code
	* @param $lang string
	* @author Fadi Yousef  frontend-expert@outlook.com
	*/
	public function get_property_posts_count_bylang( $lang ){
		global $sitepress;
		$lang_now = $sitepress->get_current_language();
		$lang_changed = 0;
		if($lang_now != $lang){
			$sitepress->switch_lang($lang);
			$lang_changed = 1;
		}
		$args = array(
			'posts_per_page' => -1,
			'post_type' => 'property',
			'suppress_filters' => false
		);
		$result = new \WP_Query($args);
		if($lang_changed) $sitepress->switch_lang($lang_now);
		return $result->post_count;
	}
	/**
	* Display property Languages if WPML plugin is active
	*
	* @Author Fadi Yousef frontend-expert@outlook.com
	*/
	public function display_languages(){
		global $pagenow, $typenow;
		if( 'property' === $typenow && 'edit.php' === $pagenow && $this->is_active )
		{
			$curr_lang = apply_filters( 'wpml_current_language', NULL );
			$languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
			$all_count = 0;
			if ( !empty( $languages ) ) {?>
				<ul class="lang_subsubsub" style="clear:both">
					<?php foreach( $languages as $l ):
						$posts_count = $this->get_property_posts_count_bylang($l['language_code']);
						$all_count += intval($posts_count);
					?>
						<li class="<?php echo 'language_'.$l['language_code']; ?>">
							<a href="<?php echo '?post_type=property&page=all_properties&lang='.$l['language_code']; ?>" class="<?php echo ($l['active']) ? 'current' : 'lang'; ?>"><?php echo $l['translated_name']; ?>
							<span class="count">(<?php echo $posts_count; ?>)</span>
							</a>
						</li>
					<?php endforeach;?>
					<li class="language_all"><a href="?post_type=property&page=all_properties&lang=all" 
		  class="<?php if($curr_lang == 'all') echo 'current';  ?>"><?php echo __( 'All languages', 'sitepress' ).' ('.$all_count.')'; ?></a></li>
				</ul>
		<?php }	
		}	
	}
	 /*
	 * Get property posts IDs filtered by langauge
	 * @author Fadi Yousef frontend-expert@outlook.com
	 */
	  public function get_matching_ids_by_lang(){
		  global $wpdb;
		  $matching_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} LEFT JOIN {$wpdb->prefix}icl_translations ON 
		  ({$wpdb->posts}.ID = {$wpdb->prefix}icl_translations.element_id) 
		  WHERE post_type = 'property' 
		  AND {$wpdb->prefix}icl_translations.language_code ='".ICL_LANGUAGE_CODE."' GROUP BY ID " );
		  return $matching_ids;
	  }
	  /*
	  * get properties IDs by meta key
	  * @params:
	  * meta_key string
	  * @return array
	  * @author Fadi Yousef frontend-expert@outlook.com
	  */
	  public function filtering_matching_ids($meta_key = false,$specific = false,$matching_id_filter = false){
		global $wpdb;
		$sql_query = "SELECT post_id FROM {$wpdb->postmeta} 
		LEFT JOIN {$wpdb->prefix}icl_translations ON 
		({$wpdb->postmeta}.post_id = {$wpdb->prefix}icl_translations.element_id) WHERE ";
		if($matching_id_filter){
			$sql_query .="post_id IN ($matching_id_filter)";
		}
		if($meta_key){
			$sql_query .="AND meta_key ='$meta_key'";
		}
		if($specific){
			$sql_query .=" AND ".$specific;
		}
		$sql_query .= " AND {$wpdb->prefix}icl_translations.language_code ='".ICL_LANGUAGE_CODE."' GROUP BY post_id";

	  	$matching_ids = $wpdb->get_col($sql_query);
		return $matching_ids;
		
	  }
	  /*
	  *
	  *
	  */
	  public function add_string_translation($str_name){
	  	
	  }
}

}