<?php
/**
 *  Adds [wpp_location] taxonomy.
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Location' ) ) {

    class Taxonomy_WPP_Location {

      /**
       * Loads WPP_Location Taxonomy stuff
       */
      public function __construct(){

        // Break, if disabled.
        if ( !WPP_FEATURE_FLAG_WPP_LISTING_LOCATION ) {
          return;
        }

        // Init standard (system) taxonomies.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

      }

      /**
       * Register WPP_Location Taxonomy
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        $taxonomies['wpp_location'] = array(
          'default' => true,
          'readonly' => true,
          'system' => true,
          'meta' => true,
          'hidden' => false,
          'hierarchical' => true,
          'public' => true,
          'show_in_nav_menus' => true,
          'show_in_menu' => false,
          'show_ui' => false,
          'show_tagcloud' => false,
          'add_native_mtbox' => false,
          'label' => __('Location', ud_get_wp_property()->domain),
          'labels' => array(
            'name' => __('Locations', ud_get_wp_property()->domain),
            'singular_name' => __('Location', ud_get_wp_property()->domain),
            'search_items' => _x('Search Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'all_items' => _x('All Locations', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item' => _x('Parent Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item_colon' => _x('Parent Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'edit_item' => _x('Edit Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'update_item' => _x('Update Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'add_new_item' => _x('Add New Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'new_item_name' => _x('New Location', 'property location taxonomy', ud_get_wp_property()->domain),
            'not_found' => _x('No location found', 'property location taxonomy', ud_get_wp_property()->domain),
            'menu_name' => __('Locations', ud_get_wp_property()->domain),
          ),
          'query_var' => 'location',
          'rewrite' => array('slug' => 'location')
        );

        return $taxonomies;

      }


    }

  }

}
