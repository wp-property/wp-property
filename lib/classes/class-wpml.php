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
      add_filter( "wpp::search_attribute::label", array($this,"get_attribute_translation"),11,2 );
      add_filter( "wpp::attribute::label", array($this,"get_attribute_translation"),10,2 );
      add_filter( "wpp::groups::label", array($this,"get_groups_translation"),10,2 );
      add_filter( "wpp::attribute::value", array($this,"get_attribute_value_translation"),10,2 );
      add_filter( "wpp_stat_filter_property_type", array($this,"get_property_type_translation") );
      add_filter( "wpp_stat_filter_property_type_label", array($this,"get_property_type_translation") );
      add_filter( "wpp::taxonomies::labels", array($this,"get_property_taxonomies_translation") );
      add_action( "template_redirect", array( $this, "template_redirect" ) );
      add_action( "wpml_media_create_duplicate_attachment", array( $this, "wpml_media_meta_update") );
      add_filter( "admin_overview_post_statuses", array( $this, "add_lang_count") );
      add_filter( "wpp::all_properties::wp_query::args", array( $this, "lang_post_statuses") );
      add_action( "wplt::ajax_response_action", array($this, "wplt_ajax_response"));
    }

    /**
     * Maybe translate $wp_properties values
     *
     * @author peshkov@UD
     */
    public function template_redirect() {
      global $wp_properties;

      if(is_array(ud_get_wp_property()->get( 'property_groups' ))){
        foreach( ud_get_wp_property()->get( 'property_groups' ) as $k => $v ) {
          $v[ 'name' ] = $this->get_groups_translation( $v[ 'name' ], $k );
          ud_get_wp_property()->set( "property_groups.{$k}", $v );
        }
      }

      if( !ud_get_wp_property()->get( 'property_groups._other' ) ) {
        ud_get_wp_property()->set( "property_groups._other", array(
          'name' => $this->get_groups_translation( '', '0' )
        ) );
      }

      $wp_properties[ 'property_groups' ] = ud_get_wp_property()->get( 'property_groups' );

    }

    /**
     * get properity posts count by language code
     *
     * @param $lang string
     * @author Fadi Yousef  frontend-expert@outlook.com
     */
    public function get_property_posts_count_bylang( $lang, $post_status = '' ){
      $lang_now = apply_filters( 'wpml_current_language', NULL );
      $lang_changed = 0;
      if($lang_now != $lang){
        do_action( 'wpml_switch_language', $lang );
        $lang_changed = 1;
      }
      $args = array(
        'posts_per_page' => -1,
        'post_type' => 'property',
        'suppress_filters' => false
      );
      if($post_status){
        $args['post_status'] = $post_status;
      }
      $result = new \WP_Query($args);
      if($lang_changed) do_action( 'wpml_switch_language', $lang_now );
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
     * Add language count to property status filter on overview page.
     * 
     * @param $post_statuses: Post status from Admin_Overview::get_post_statuses();
     * 
     * @return $post_statuses
     */
    public function add_lang_count($post_statuses){
      $new_post_statuses = array();
      $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
      if ( !empty( $languages ) ) {
        foreach ($post_statuses as $post_status => $attr) {
          $new_post_statuses[$post_status] = $attr;
          foreach( $languages as $l ){
            if($count = $this->get_property_posts_count_bylang($l['language_code'], $post_status)){
              $new_post_statuses[$post_status . "_" . $l['default_locale']] = " - " . $l['native_name'] . " ($count) ";
            }
          }
        }
      }
      return $new_post_statuses;
    }

    /**
     * If lang code passes in $args[post_status] then replace it by any.
     * And switch lang by the lang code.
     * 
     * @param $args: come from submitted form.
     * 
     * @return $args
     */
    public function lang_post_statuses($args){
      global $sitepress;
      $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );

      if (!empty( $languages ) && !empty($args[ 'post_status' ])) {
        foreach( $languages as $l ){
          if($l['default_locale'] == $args[ 'post_status' ]){
            $args[ 'post_status' ] = 'any';
            $sitepress->switch_lang($l[ 'code' ]);
          }
        }
      }
      return $args;
    }

    /**
     * Reset the language by switching it to all.
     * Unless default language will be applied.
     */
    public function wplt_ajax_response(){
      global $sitepress;
      $sitepress->switch_lang('all');
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
      $language_code = apply_filters( 'wpml_current_language', NULL );
      $sql_query = "SELECT ID FROM {$wpdb->posts}
      LEFT JOIN {$wpdb->prefix}icl_translations ON
      ({$wpdb->posts}.ID = {$wpdb->prefix}icl_translations.element_id) WHERE ID IN ($matching_ids)";
      $sql_query .= " AND {$wpdb->prefix}icl_translations.language_code ='".$language_code."'";
      $sql_query .= " AND {$wpdb->prefix}icl_translations.element_type ='post_property'";
      $sql_query .= " GROUP BY ID";
      
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
    public function translate_property_types_attributes( $data ) {

      if( !empty( $data['wpp_settings'][ 'property_types' ] ) ) {

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

      }

      if( !empty(  $data['wpp_settings']['property_stats'] ) ) {

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

      }

      
      if( !empty( $data['wpp_settings']['property_meta'] ) ) {

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

      }

      // @todo: move it to 'WP-Property: Terms' plugin ( add-on ). peshkov@UD
      if( !empty( $data[ 'wpp_terms' ] ) ) {

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

      if( !empty( $data['wpp_settings']['property_groups'] ) ) {

        $groups_package = array(
          'kind' => 'Property Groups',
          'name' => 'custom-groups',
          'title' => 'Property Groups',
        );
        $property_groups = $data['wpp_settings']['property_groups'];

        $this->delete_strings_translation( $groups_package, $property_groups );

        foreach($property_groups as $key => $group){
          do_action('wpml_register_string', $group['name'] , $key , $groups_package , $group['name'] , 'LINE');
        }

      }

      if( !empty( $data['wpp_settings']['predefined_values'] ) ) {

        $attributes_values_package = array(
          'kind' => 'Property Attributes Values',
          'name' => 'custom-attributes-value',
          'title' => 'Property Attributes Values',
        );
        $attributes_values = $data['wpp_settings']['predefined_values'];

        $this->delete_strings_translation( $attributes_values_package, $attributes );

        foreach($attributes_values as $key => $value){
          if( $value ){
            do_action('wpml_register_string', $value , $key , $attributes_values_package , $value , 'LINE');
          }
        }

      }

    }

    /*
    * Get translated text for property types 
    * @auther Fadi Yousef
    */
    public function get_property_type_translation($v){
      global $wp_properties;
      $type_package = array(
        'kind' => 'Property Types',
        'name' => 'custom-types',
        'title' => 'Property Types',
      );

      $key = array_search($v,$wp_properties['property_types']);
      return ($key !== false) ? apply_filters( 'wpml_translate_string', $v,$key, $type_package ) : $v;
    }
    /*
    * Get translated text for property attributes 
    * @auther Fadi Yousef
    */
    public function get_attribute_translation($v,$attribute_slug=null){
      global $wp_properties;
      $attributes = $wp_properties['property_stats'];
      $property_types = $wp_properties['property_types'];
      $property_meta =  !empty( $wp_properties['property_meta'] ) ? $wp_properties['property_meta'] : array();
      $property_terms = $wp_properties['taxonomies'];
     
      if( $attr_key = array_search($v,$attributes) ){
        $attribute_slug = ($attribute_slug === null)? $attr_key : $attribute_slug;
        $attributes_package = array(
          'kind' => 'Property Attributes',
          'name' => 'custom-attributes',
          'title' => 'Property Attributes',
        );
        
        return apply_filters( 'wpml_translate_string', $v,$attribute_slug, $attributes_package );
        
      } elseif( $type_key = array_search($v,$property_types) ){
        $attribute_slug = ($attribute_slug === null)? $type_key : $attribute_slug;
        $type_package = array(
          'kind' => 'Property Types',
          'name' => 'custom-types',
          'title' => 'Property Types',
        );
        
        return apply_filters( 'wpml_translate_string', $v,$attribute_slug, $type_package );
        
      } elseif( $meta_key = array_search($v,$property_meta) ){
        $attribute_slug = ($attribute_slug === null)? $meta_key : $attribute_slug;
        $meta_package = array(
          'kind' => 'Property Meta',
          'name' => 'custom-meta',
          'title' => 'Property Meta',
        );
        
        return apply_filters( 'wpml_translate_string', $v,$attribute_slug, $meta_package );
        
      }elseif( $term_key = $this->get_term_slug_by_label($property_terms, $v) ){
        $attribute_slug = ($attribute_slug === null)? $term_key : $attribute_slug;
        
        $terms_package = array(
          'kind' => 'Property Term',
          'name' => 'custom-term',
          'title' => 'Property Term',
        );
        
        return apply_filters( 'wpml_translate_string', $v,$attribute_slug, $terms_package );

      }else{
        
        return $v;
      }
      
    }

    /**
     * Get term slug by label.
     * 
     * @param array $property_terms : The array of terms.
     * @param string $leabel : The leabel to search in $property_terms.
     * 
     * @return array of term slug found.
     */
    private function get_term_slug_by_label($property_terms, $leabel){
      if (is_array($property_terms)) {
        foreach ($property_terms as $tax => $v) {
          if ($v['label'] == $leabel)
            return $tax;
        }
      }
      return false;
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
    /**
     * Get translated text for property group
     * @auther Fadi Yousef
     */
    public function get_groups_translation($name,$slug){
      global $wp_properties;
      $property_groups = (isset($wp_properties['property_groups']) && is_array($wp_properties['property_groups']))?array_keys($wp_properties['property_groups']):array();
      if( array_search($slug,$property_groups,true) !== false ){
        $groups_package = array(
          'kind' => 'Property Groups',
          'name' => 'custom-groups',
          'title' => 'Property Groups',
        );
        return apply_filters( 'wpml_translate_string', $slug,$slug, $groups_package );

      }else{
        return  __( 'Other', ud_get_wp_property()->domain ) ;
      }
    }
    /**
     * Get translation for predefined property attribute value
     * @auther Fadi Yousef
     */
    public function get_attribute_value_translation($v, $attribute_slug = false){
      global $wp_properties;
      $not_translatable_atts = array('currency','number','oembed','datetime','date','time','color','image_advanced','file_advanced','file_input','checkbox');

      if( empty($wp_properties['predefined_values'][$attribute_slug]) || in_array($wp_properties["admin_attr_fields"][$attribute_slug],$not_translatable_atts)){
        return $v;
      }
      $v = ( is_array($v) )? implode(',', $v): $v;
      $attributes_values_package = array(
        'kind' => 'Property Attributes Values',
        'name' => 'custom-attributes-value',
        'title' => 'Property Attributes Values',
      );

      $default_values = explode(',', str_replace(', ', ',', $wp_properties['predefined_values'][$attribute_slug]) );
      $translated_values = explode(',',str_replace(', ', ',',apply_filters( 'wpml_translate_string', $v, $attribute_slug, $attributes_values_package )));

      if( $wp_properties['predefined_values'][$attribute_slug] == $v ){
        return apply_filters( 'wpml_translate_string', $v, $attribute_slug, $attributes_values_package );
      }elseif( !is_array($v) && (strpos( $v, ',' ) !== false) ){
        $selected_values = explode(',', str_replace(', ', ',',$v));
        $s_values_arr = array();
        foreach ($selected_values as $s_key => $s_value) {
          $value_pos = array_search( $s_value,$default_values );
          $s_values_arr[$s_key] = $translated_values[$value_pos];
        }
        return implode(',', $s_values_arr);
      }else{
        $value_pos = array_search( $v,$default_values );
        return $translated_values[$value_pos];
      }
      
    }

    public function wpml_media_meta_update($attachment_id, $duplicated_attachment_id){
      $_is_remote = get_post_meta($attachment_id, '_is_remote', true);
      if($_is_remote)
        update_post_meta($duplicated_attachment_id, '_is_remote', $_is_remote);
    }

  }

}
