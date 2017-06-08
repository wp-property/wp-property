<?php
/**
 *  Adds [wpp_listing_category] taxonomy.
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use \WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Listing_Category' ) ) {

    class Taxonomy_WPP_Listing_Category {

      /**
       * Loads WPP_Listing_Category Taxonomy stuff
       */
      public function __construct(){

        // Break, if disabled.
        if ( !WPP_FEATURE_FLAG_WPP_LISTING_CATEGORY ) {
          return;
        }

        // Register taxonomy.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

      }

      /**
       * Register WPP_Listing_Category Taxonomy
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        $taxonomies['wpp_listing_category'] = array(
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
          'label' => __('Landing', ud_get_wp_property()->domain),
          'labels' => array(
            'name' => __('Landing Category', ud_get_wp_property()->domain),
            'singular_name' => __('Landing', ud_get_wp_property()->domain),
            'search_items' => _x('Search Landing', 'property location taxonomy', ud_get_wp_property()->domain),
            'all_items' => _x('All Landing Category', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item' => _x('Parent Landing', 'property location taxonomy', ud_get_wp_property()->domain),
            'parent_item_colon' => _x('Parent Landing', 'property location taxonomy', ud_get_wp_property()->domain),
            'edit_item' => _x('Edit Landing', 'property location taxonomy', ud_get_wp_property()->domain),
            'update_item' => _x('Update Landing', 'property location taxonomy', ud_get_wp_property()->domain),
            'add_new_item' => _x('Add New Landing', 'property location taxonomy', ud_get_wp_property()->domain),
            'new_item_name' => _x('New Landing', 'property location taxonomy', ud_get_wp_property()->domain),
            'not_found' => _x('No location found', 'property location taxonomy', ud_get_wp_property()->domain),
            'menu_name' => __('Landing', ud_get_wp_property()->domain),
          ),
          'query_var' => 'listings',
          'rewrite' => array('hierarchical' => true, 'slug' => 'listings' ),
          'wpp_term_meta_fields' => array(
            array( 'slug' => 'related_taxonomy', 'label' => __( 'Related Taxonomy')  ),
            array( 'slug' => 'related_type', 'label' => __( 'Related Type')  ),
            array( 'slug' => 'pattern', 'label' => __( 'URL Pattern')   ),
            array( 'slug' => 'url_path', 'label' => __( 'Path')   ),
            array( 'slug' => 'url_slug', 'label' => __( 'Slug')   )
          )
        );

        // @todo Add this properly.
        add_rewrite_rule('^listings\/([^&]+)\/?', 'index.php?wpp_listing_category=$matches[1]', 'top');

        return $taxonomies;

      }

    }

  }

}
