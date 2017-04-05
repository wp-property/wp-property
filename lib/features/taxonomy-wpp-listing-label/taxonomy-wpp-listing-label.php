<?php
/**
 *  Adds [wpp_listing_label] taxonomy.
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use \WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Listing_Label' ) ) {

    class Taxonomy_WPP_Listing_Label {

      /**
       * Loads WPP_Listing_Label Taxonomy stuff
       */
      public function __construct(){

        // Break, if disabled.
        if ( !WP_PROPERTY_FLAG_WPP_LISTING_LABEL ) {
          return;
        }

        // Register taxonomy.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

      }

      /**
       * Register WPP_Listing_Label Taxonomy
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        $taxonomies['wpp_listing_label'] = array(
          'default' => true,
          'readonly' => true,
          'system' => true,
          'meta' => true,
          'hidden' => false,
          'hierarchical' => false,
          'unique' => false,
          'public' => true,
          'show_in_nav_menus' => true,
          'show_ui' => false,
          'show_tagcloud' => false,
          'add_native_mtbox' => false,
          'label' => sprintf(_x('%s Labels', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
          'labels' => array(
            'name' => sprintf(_x('%s Labels', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'singular_name' => sprintf(_x('%s Label', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'search_items' => _x('Search  Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'all_items' => _x('All Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'parent_item' => _x('Parent Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'parent_item_colon' => _x('Parent Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'edit_item' => _x('Edit Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'update_item' => _x('Update Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'add_new_item' => _x('Add New Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'new_item_name' => _x('New Labels', 'property type taxonomy', ud_get_wp_property()->domain),
            'not_found' => sprintf(_x('No %s Label Found', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'menu_name' => sprintf(_x('%s Labels', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
          ),
          'query_var' => 'property-labels',
          'rewrite' => array('slug' => 'property-labels')
        );

        return $taxonomies;

      }

    }

  }

}
