<?php
/**
 * System Taxonomy(ies)
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\System_Taxonomy' ) ) {

    class System_Taxonomy {

      /**
       * Loads System Taxonomies stuff
       */
      public function __construct(){

        // Init standard (system) taxonomies.
        add_filter('wpp_taxonomies', array( $this, 'define_taxonomies'), 10 );

      }

      /**
       * Standard Taxonomies that can not be modified with Terms editor.
       *
       * @todo The [wpp_listing_category] should match the property base slug. Will need to make sure there is no conflict with property post type's single pages. - potanin@UD
       *
       * @param array $taxonomies
       * @return array
       */
      public function define_taxonomies( $taxonomies = array() ) {

        // Add [wpp_schools] taxonomy.
        if ( WPP_FEATURE_FLAG_WPP_SCHOOLS ) {
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
        }

        // Add [wpp_listing_policy] taxonomy.
        if ( WPP_FEATURE_FLAG_WPP_LISTING_POLICY ) {
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
        }

        // Add [wpp_listing_label] taxonomy.
        if ( WP_PROPERTY_FLAG_WPP_LISTING_LABEL ) {
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
        }

        // Generic [wpp_categorical] taxonomy for multiple terms.
        if ( WPP_FEATURE_FLAG_WPP_CATEGORICAL ) {
          $taxonomies['wpp_categorical'] = array(
            'default' => true,
            'readonly' => true,
            'system' => true,
            'meta' => true,
            'hidden' => false,
            'hierarchical' => true,
            'unique' => false,
            'public' => true,
            'show_in_menu' => false,
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
        }

        // Add [wpp_listing_category] taxonomy.
        if ( WPP_FEATURE_FLAG_WPP_LISTING_CATEGORY ) {

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

        }

        return $taxonomies;

      }


    }

  }

}
