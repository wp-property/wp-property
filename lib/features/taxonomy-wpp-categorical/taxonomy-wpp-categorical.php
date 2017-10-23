<?php
/**
 *  Adds [wpp_categorical] taxonomy.
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use \WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Taxonomy_WPP_Categorical' ) ) {

    class Taxonomy_WPP_Categorical {

      /**
       * Loads WPP_Categorical Taxonomy stuff
       */
      public function __construct(){

        // Break, if disabled.
        if ( !WPP_FEATURE_FLAG_WPP_CATEGORICAL ) {
          return;
        }

        // Register taxonomy.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

      }

      /**
       * Register WPP_Categorical Taxonomy
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        $taxonomies['wpp_categorical'] = array(
          'default' => true,
          'readonly' => true,
          'system' => true,
          'meta' => true,
          'hidden' => false,
          'hierarchical' => true,
          'unique' => false,
          'public' => true,
          'show_in_menu' => true,
          'show_in_nav_menus' => true,
          'show_ui' => false,
          'show_tagcloud' => false,
          'add_native_mtbox' => false,
          'label' => __( 'Categories', ud_get_wp_property()->domain) ,
          'labels' => array(
            'name' => sprintf(_x('%s Category', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'singular_name' => sprintf(_x('%s Category', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'search_items' => _x('Search Category', 'property type taxonomy', ud_get_wp_property()->domain),
            'all_items' => _x('All Categories', 'property type taxonomy', ud_get_wp_property()->domain),
            'parent_item' => _x('Parent Category', 'property type taxonomy', ud_get_wp_property()->domain),
            'parent_item_colon' => _x('Parent Category', 'property type taxonomy', ud_get_wp_property()->domain),
            'edit_item' => _x('Edit Category', 'property type taxonomy', ud_get_wp_property()->domain),
            'update_item' => _x('Update Category', 'property type taxonomy', ud_get_wp_property()->domain),
            'add_new_item' => _x('Add New Category', 'property type taxonomy', ud_get_wp_property()->domain),
            'new_item_name' => _x('New Category', 'property type taxonomy', ud_get_wp_property()->domain),
            'not_found' => sprintf(_x('No %s Category found', 'property type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'menu_name' => __( 'Categories', ud_get_wp_property()->domain) ,
          ),
          'query_var' => 'property-category',
          'rewrite' => array('slug' => 'property-category')
        );

        return $taxonomies;

      }

    }

  }

}
