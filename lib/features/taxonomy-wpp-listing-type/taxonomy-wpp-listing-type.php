<?php
/**
 * WPP_FEATURE_FLAG_WPP_LISTING_TYPE
 *
 * Bootstrap
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Listing_Type' ) ) {

    class Taxonomy_WPP_Listing_Type {

      /**
       * Loads all stuff for WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       */
      public function __construct(){
        global $wp_properties;

        // Meta Box fields
        add_filter( "wpp::rwmb_meta_box::field::parent_property", function( $field, $post ) {
          return null;
        }, 10, 2 );

        add_filter( "wpp::rwmb_meta_box::field::property_type", function( $field, $post ){
          $taxonomies = ud_get_wp_property( 'taxonomies', array() );

          $field = apply_filters( 'wpp::rwmb_meta_box::field', array_filter( array(
            'id' => 'wpp_listing_type',
            'name' => $taxonomies['wpp_listing_type']['label'],
            'type' => 'taxonomy', // Metabox field name
            'placeholder' => sprintf( __( 'Select %s Type', ud_get_wp_property()->domain ), WPP_F::property_label() ),
            'multiple' => false,
            'options' => array(
              'taxonomy' => 'wpp_listing_type',
              'type' => 'select', // Metabox filed to use in taxonomy
              'args' => array(),
            )
          ) ), 'wpp_listing_type', $post );
          return $field;
        }, 10, 2 );

        // Update taxonomy terms on saving property
        add_action( "save_property", function( $post_id ){
          // if wpp_listing_type is set then update property_type attribute.
          if(isset($_REQUEST[ 'wpp_listing_type' ]) && taxonomy_exists('wpp_listing_type')){
            $term = get_the_terms( $post_id, 'wpp_listing_type');
            if(is_object( $term[0] ) )
              update_post_meta( $post_id, 'property_type', $term[0]->slug);
          }
        } );

        add_action( 'created_wpp_listing_type', array($this, 'term_created_wpp_listing_type'), 10, 2 );
        add_action( 'edited_wpp_listing_type', array($this, 'term_created_wpp_listing_type'), 10, 2 );
        add_action( 'delete_wpp_listing_type', array($this, 'term_delete_wpp_listing_type'), 10, 4 );
        add_action( 'wpp_settings_save', array( $this, 'create_property_type_terms'), 10, 2 );

        add_filter('wpp_taxonomies', function( $taxonomies = array() ) {
          $taxonomies['wpp_listing_type'] = array(
            'default' => true,
            'readonly' => true,
            'system' => true,
            'hidden' => true,
            'hierarchical' => false,
            'unique' => true,
            'public' => true,
            'show_in_nav_menus' => true,
            'show_ui' => false,
            'show_tagcloud' => false,
            'add_native_mtbox' => false,
            'label' => sprintf(_x('%s Type', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'labels' => array(
              'name' => sprintf(_x('%s Type', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
              'singular_name' => sprintf(_x('%s Type', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
              'search_items' => _x('Search Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'all_items' => _x('All Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'parent_item' => _x('Parent Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'parent_item_colon' => _x('Parent Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'edit_item' => _x('Edit Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'update_item' => _x('Update Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'add_new_item' => _x('Add New Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'new_item_name' => _x('New Type', 'property type taxonomy', ud_get_wp_property()->domain),
              'not_found' => sprintf(_x('No %s type found', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
              'menu_name' => sprintf(_x('%s Type', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            ),
            'query_var' => 'type',
            'rewrite' => array('slug' => 'type')
          );
          return $taxonomies;
        }, 10 );

        add_action( 'wpp_init:end', function() {
          global $wp_properties;
          // Run activation task after plugin fully activated.
          if( get_option('wpp_activated') ){
            Taxonomy_WPP_Listing_Type::add_wpp_listing_type_from_existing_terms();
            Taxonomy_WPP_Listing_Type::create_property_type_terms( $wp_properties, $wp_properties );
            delete_option('wpp_activated');
          }
        } );

        add_filter( 'wpp:elastic:title_suggest', array( $this, 'elastic_title_suggest' ), 10, 3 );

      }

      /**
       * Migrates property types attributes to terms
       * It's moved from class-upgrade.php
       *
       */
      public function migrate_legacy_type_to_term(){
        global $wpdb;
        $pp = $wpdb->get_results("SELECT ID from {$wpdb->posts} WHERE post_type='property'");
        $wpp_settings = get_option('wpp_settings');

        register_taxonomy('wpp_listing_type', 'property_type');
        /* Generate Property type terms */
        foreach ($wpp_settings['property_types'] as $_term => $label) {
          $term = term_exists($label, 'wpp_listing_type');
          if (!$term) {
            $term = wp_insert_term($label, 'wpp_listing_type', array('slug' => $_term));
          }
        }

        if (!empty($pp)) {
          foreach ($pp as $p) {
            $property_type = get_post_meta($p->ID, 'property_type', true);
            if (!empty($property_type)) {
              wp_set_object_terms($p->ID, $property_type, 'wpp_listing_type');
            }
          }
        }

      }

      /**
       * Insert or update wpp_listing_type terms
       * Based on property_types on settings developer tab.
       * Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       *
       * @param $wpp_settings : New settings
       * @param $wp_properties : Old settings
       *
       */
      public static function create_property_type_terms( $wpp_settings, $wp_properties ) {
        $terms = get_terms(array(
          'taxonomy' => 'wpp_listing_type',
          'hide_empty' => false,
        ));

        /* Delete terms if not exist in $wpp_settings */
        if (!empty($terms) && !is_wp_error($terms))
          foreach ($terms as $_term) {
            if (!array_key_exists($_term->slug, $wpp_settings['property_types'])) {
              wp_delete_term($_term->term_id, 'wpp_listing_type');
            }
          }

        /* Generate Property type terms */
        foreach ($wpp_settings['property_types'] as $_term => $label) {

          $term = null;

          if(isset($wp_properties['property_types_term_id']) && isset($wp_properties['property_types_term_id'][$_term])) {
            $term = get_term($wp_properties['property_types_term_id'][$_term], 'wpp_listing_type', ARRAY_A);
          }

          if ( !is_wp_error($term) && isset($term['term_id'])) {
            if ($label != $term['name']) {
              $term = wp_update_term($term['term_id'], 'wpp_listing_type', array('name' => $label));
            }
          } // Find term by label
          elseif ($term == term_exists($label, 'wpp_listing_type')) {

          } else {
            $term = wp_insert_term($label, 'wpp_listing_type', array('slug' => $_term));
          }

          if (!is_wp_error($term) && isset($term['term_id'])) {
            $wpp_settings['property_types_term_id'][$_term] = $term['term_id'];
          }
        }
        return $wpp_settings;
      }

      /**
       * Add property type from terms if not already exists.
       * Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       *
       */
      public static function add_wpp_listing_type_from_existing_terms(){
        global $wp_properties;
        $updated = false;
        $terms = get_terms(array(
          'taxonomy' => 'wpp_listing_type',
          'hide_empty' => false,
        ));

        /* Add property type from terms */
        if (!empty($terms) && !is_wp_error($terms))
          foreach ($terms as $term) {
            if (!array_key_exists($term->slug, $wp_properties['property_types'])) {
              $wp_properties['property_types'][$term->slug] = $term->name;
              $wp_properties['property_types_term_id'][$term->slug] = $term->term_id;
              $updated = true;
            }
          }
        if ($updated) {
          update_option('wpp_settings', $wp_properties);
        }
      }

      /**
       * Add/update Property Type to $wp_properties when wpp_listing_type
       * created/updated outside of developer tab of settings page.
       * Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       *
       * @author Md. Alimuzzaman Alim
       *
       * @param int $term_id
       * @param int $tt_id
       *
       */
      public function term_created_wpp_listing_type($term_id, $tt_id){
        global $wp_properties;
        $term = get_term($term_id, 'wpp_listing_type');

        if(!in_array($term->slug, $wp_properties['property_types']) || $wp_properties['property_types'][$term->slug] != $term->name){

          $wp_properties['property_types'][$term->slug] = $term->name;
          $wp_properties['property_types_term_id'][$term->slug] = $term->term_id;

          ud_get_wp_property()->set('property_types', $wp_properties['property_types']);
          ud_get_wp_property()->set('property_types_term_id', $wp_properties['property_types_term_id']);
          update_option('wpp_settings', $wp_properties);
        }

      }

      /**
       * Remove Property Type from $wp_properties when wpp_listing_type
       * deleted outside of developer tab of settings page.
       * Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       *
       * @author Md. Alimuzzaman Alim
       *
       * @param int $term_id
       * @param int $tt_id
       * @param int $term
       *
       */
      public function term_delete_wpp_listing_type($term_id, $tt_id, $term){
        global $wp_properties;
        if(array_key_exists($term->slug, $wp_properties['property_types'])){
          unset($wp_properties['property_types'][$term->slug]);
          unset($wp_properties['property_types_term_id'][$term->slug]);

          ud_get_wp_property()->set('property_types', $wp_properties['property_types']);
          ud_get_wp_property()->set('property_types_term_id', $wp_properties['property_types_term_id']);
          update_option('wpp_settings', $wp_properties);
        }
      }

      /**
       * We apply contexts for title_suggest based on the [wpp_listing_type] taxonomy
       *
       * @param $title_suggest
       * @param $args
       * @param $post_id
       * @return mixed
       */
      public function elastic_title_suggest( $title_suggest, $args, $post_id ) {

        $terms = wp_get_object_terms( $post_id, 'wpp_listing_type' );

        if( empty( $terms ) ) {
          return $title_suggest;
        }

        $listing_type = array();
        foreach( $terms as $term ) {
          $listing_type[] = $term->slug;
          $listing_type[] = $term->name;
        }

        $listing_type = array_unique( $listing_type );

        if( empty( $listing_type ) ) {
          return $title_suggest;
        }

        if( !isset( $title_suggest[ 'contexts' ] ) ) {
          $title_suggest[ 'contexts' ] = array();
        }

        $title_suggest[ 'contexts' ][ 'listing_type' ] = $listing_type;

        return $title_suggest;
      }

    }

  }

}
