<?php
/**
 *  Adds [wpp_listing_policy] taxonomy.
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use \WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Listing_Policy' ) ) {

    class Taxonomy_WPP_Listing_Policy {

      /**
       * Loads WPP_Listing_Policy Taxonomy stuff
       */
      public function __construct(){

        // Break, if disabled.
        if ( !WPP_FEATURE_FLAG_WPP_LISTING_POLICY ) {
          return;
        }

        // Register taxonomy.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

      }

      /**
       * Register WPP_Listing_Policy Taxonomy
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        $taxonomies['wpp_listing_policy'] = array(
          'default' => true,
          'readonly' => true,
          'system' => true,
          'meta' => true,
          'hidden' => false,
          'hierarchical' => false,
          'unique' => false,
          'public' => false,
          'show_in_nav_menus' => false,
          'show_ui' => false,
          'show_tagcloud' => false,
          'add_native_mtbox' => false,
          'label' => sprintf(_x('%s Policy', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
          'labels' => array(
            'name' => sprintf(_x('%s Policy', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'singular_name' => sprintf(_x('%s Policy', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'search_items' => _x('Search Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'all_items' => _x('All Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'parent_item' => _x('Parent Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'parent_item_colon' => _x('Parent Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'edit_item' => _x('Edit Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'update_item' => _x('Update Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'add_new_item' => _x('Add New Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'new_item_name' => _x('New Policy', 'property type taxonomy', ud_get_wp_property()->domain),
            'not_found' => sprintf(_x('No %s Policy found', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'menu_name' => sprintf(_x('%s Policy', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
          )
        );

        return $taxonomies;

      }

    }

  }

}
