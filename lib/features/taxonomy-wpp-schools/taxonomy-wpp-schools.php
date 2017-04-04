<?php
/**
 *  Adds [wpp_schools] taxonomy.
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use \WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Schools' ) ) {

    class Taxonomy_WPP_Schools {

      /**
       * Loads WPP_Schools Taxonomy stuff
       */
      public function __construct(){

        // Break, if disabled.
        if ( !WPP_FEATURE_FLAG_WPP_SCHOOLS ) {
          return;
        }

        // Register taxonomy.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

      }

      /**
       * Register WPP_Schools Taxonomy
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        $taxonomies['wpp_schools'] = array(
          'default' => true,
          'readonly' => true,
          'system' => true,
          'meta' => true,
          'hidden' => false,
          'hierarchical' => true,
          'public' => true,
          'show_in_nav_menus' => true,
          'show_in_menu' => true,
          'show_ui' => false,
          'show_tagcloud' => false,
          'add_native_mtbox' => false,
          'label' => __('School', ud_get_wp_property()->domain),
          'labels' => array(
            'name' => __('Schools', ud_get_wp_property()->domain),
            'singular_name' => __('School', ud_get_wp_property()->domain),
            'search_items' => _x('Search School', 'property location taxonomy', ud_get_wp_property()->domain),
            'all_items' => _x('All Schools', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item' => _x('Parent School', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item_colon' => _x('Parent School', 'property location taxonomy', ud_get_wp_property()->domain),
            'edit_item' => _x('Edit School', 'property location taxonomy', ud_get_wp_property()->domain),
            'update_item' => _x('Update School', 'property location taxonomy', ud_get_wp_property()->domain),
            'add_new_item' => _x('Add New School', 'property location taxonomy', ud_get_wp_property()->domain),
            'new_item_name' => _x('New School', 'property location taxonomy', ud_get_wp_property()->domain),
            'not_found' => _x('No location found', 'property location taxonomy', ud_get_wp_property()->domain),
            'menu_name' => __('Schools', ud_get_wp_property()->domain),
          ),
          'query_var' => 'schools',
          'rewrite' => array('slug' => 'schools')
        );

        return $taxonomies;

      }

    }

  }

}
