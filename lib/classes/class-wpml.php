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
  
    function __construct() {

      add_filter( 'wpp::get_properties::matching_ids',array($this, 'filtering_matching_ids') );
      add_action( 'wpp::above_list_table',array($this, 'display_languages' ) );
      add_action( 'wpp::save_settings',array($this, 'translate_property_types_attributes'),10,1 );
      add_filter( "wpp::search_attribute::label", array($this,"get_attribute_translation") );
      add_filter( "wpp::attribute::label", array($this,"get_attribute_translation") );
      add_filter( "wpp_stat_filter_property_type", array($this,"get_property_type_translation") );
      add_filter( "wpp::taxonomies::labels", array($this,"get_property_taxonomies_translation") );
    }

    /**
     * get properity posts count by language code
     *
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
      if( 'property' === $typenow && 'edit.php' === $pagenow )
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

    /**
     * get properties IDs by meta key
     *
     * @params:
     * meta_key string
     * @return array
     * @author Fadi Yousef frontend-expert@outlook.com
     */
    public function filtering_matching_ids( $matching_ids ){
      global $wpdb;

      $matching_ids = implode(',',$matching_ids);
      $sql_query = "SELECT ID FROM {$wpdb->posts}
      LEFT JOIN {$wpdb->prefix}icl_translations ON
      ({$wpdb->posts}.ID = {$wpdb->prefix}icl_translations.element_id) WHERE ID IN ($matching_ids)";
      $sql_query .= " AND {$wpdb->prefix}icl_translations.language_code ='".ICL_LANGUAGE_CODE."' GROUP BY ID";

      return $wpdb->get_col($sql_query);
    }

    /**
     * Add dynamic elements to translation
     *
     * @params
     * @package_name - string // types under developer tab
     * @str_name - string // the element need translation
     * @author Fadi Yousef frontend-expert@outlook.com
     */
    public function translate_property_types_attributes( $data ){
      
      $type_package = array(
        'kind' => 'Property Types',
        'name' => 'custom-types',
        'title' => 'Property Types',
      );
      
      $types = $data['wpp_settings'][ 'property_types' ];
      
      $this->delete_strings_translation( $type_package, $types );
      
      foreach($types as $key => $type){
        do_action('wpml_register_string', $type , $key , $type_package , $type , 'LINE'); 
      }
      
      $attributes_package = array(
        'kind' => 'Property Attributes',
        'name' => 'custom-attributes',
        'title' => 'Property Attributes',
      );
      $attributes = $data['wpp_settings']['property_stats'];
      
      $this->delete_strings_translation( $attributes_package, $attributes );
      
      foreach($attributes as $key => $attibute){
        do_action('wpml_register_string', $attibute , $key , $attributes_package , $attibute , 'LINE'); 
      }
      
      $meta_package = array(
        'kind' => 'Property Meta',
        'name' => 'custom-meta',
        'title' => 'Property Meta',
      );
      $metas = $data['wpp_settings']['property_meta'];
      
      $this->delete_strings_translation( $meta_package, $metas );
      
      foreach($metas as $key => $meta){
        do_action('wpml_register_string', $meta , $key , $meta_package , $meta , 'LINE'); 
      }
      
      $terms_package = array(
        'kind' => 'Property Term',
        'name' => 'custom-term',
        'title' => 'Property Term',
      );
      $wpp_terms = $data['wpp_terms']['taxonomies'];
      
      $this->delete_strings_translation( $terms_package, $wpp_terms );
      
      foreach($wpp_terms as $key => $term){
        do_action('wpml_register_string', $term['label'] , $key , $terms_package , $term['label'] , 'LINE'); 
      }
      
    }
    /*
    * Get translated text for property types 
    * @auther Fadi Yousef
    */
    public function get_property_type_translation($v){
      $type_package = array(
        'kind' => 'Property Types',
        'name' => 'custom-types',
        'title' => 'Property Types',
      );
      return apply_filters( 'wpml_translate_string', $v,$v, $type_package );
    }
    /*
    * Get translated text for property attributes 
    * @auther Fadi Yousef
    */
    public function get_attribute_translation($v){
      global $wp_properties;

      $attributes = $wp_properties['property_stats'];
      $property_types = $wp_properties['property_types'];
      $property_meta = $wp_properties['property_meta'];
      $property_terms = $wp_properties['taxonomies'];
      
      if( $attr_key = array_search($v,$attributes) ){

        $attributes_package = array(
          'kind' => 'Property Attributes',
          'name' => 'custom-attributes',
          'title' => 'Property Attributes',
        );
        return apply_filters( 'wpml_translate_string', $v,$v, $attributes_package );
        
      } elseif( $type_key = array_search($v,$property_types) ){

        $type_package = array(
          'kind' => 'Property Types',
          'name' => 'custom-types',
          'title' => 'Property Types',
        );
        return apply_filters( 'wpml_translate_string', $v,$v, $type_package );
        
      } elseif( $meta_key = array_search($v,$property_meta) ){
        $meta_package = array(
          'kind' => 'Property Meta',
          'name' => 'custom-meta',
          'title' => 'Property Meta',
        );
        return apply_filters( 'wpml_translate_string', $v,$v, $meta_package );
        
      }elseif( $term_key = array_search($v,$property_terms) ){

        $terms_package = array(
          'kind' => 'Property Term',
          'name' => 'custom-term',
          'title' => 'Property Term',
        );
        return apply_filters( 'wpml_translate_string', $v,$v, $terms_package );

      }else{

        return;

      }
      
    }

    /**
     * Get translated text for property meta
     * @auther Fadi Yousef
     */
    public function get_property_meta_translation($v){
      $meta_package = array(
        'kind' => 'Property Meta',
        'name' => 'custom-meta',
      'title' => 'Property Meta',
      );
      return apply_filters( 'wpml_translate_string', $v,$v, $meta_package );
    }

    /**
     * delete string keys from translation when deleted from the setting
     * @auther Fadi Yousef frontend-expert@outlook.com
     */
    public function delete_strings_translation($package,$values){
      global $wpdb;
      $context = str_replace(' ','-',strtolower( $package['kind'] ) ).'-'.$package['name'];
      $sql = "SELECT name FROM {$wpdb->prefix}icl_strings WHERE context ='{$context}'";
      $result = $wpdb->get_col($sql);
      foreach($result as $key => $item){
        if( array_key_exists($item,$values) == false ){
          icl_unregister_string ($context,$item);
        }
      }
     
    }
    /*
    * translate taxonomies labels in $wp_properties['taxonomies'] directly
    * auther Fadi Yousef
    */
    public function get_property_taxonomies_translation($taxonomies){
      $terms_package = array(
        'kind' => 'Property Term',
        'name' => 'custom-term',
        'title' => 'Property Term',
      );
      
      foreach( $taxonomies as $key => $tax ){
        $taxonomies[$key]['label'] = apply_filters( 'wpml_translate_string', $key,$key, $terms_package );
      }
      return $taxonomies;
    }
    
  }

}