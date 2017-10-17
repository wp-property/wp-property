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

      static $taxonomy = 'wpp_listing_type';
      static $sub_taxonomy = 'wpp_listing_subtype';
      static $meta_property_type = 'property_type';

      /**
       * Loads all stuff for WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       */
      public function __construct(){

        add_filter( "wpp::rwmb_meta_box::field::property_type", function( $field, $post ){
          $taxonomies = ud_get_wp_property( 'taxonomies', array() );

          $field = apply_filters( 'wpp::rwmb_meta_box::field', array_filter( array(
            'id' => 'wpp_listing_type',
            'name' => $taxonomies['wpp_listing_type']['label'],
            'type' => 'wpp_property_type', // Metabox field type
            'placeholder' => sprintf( __( 'Select %s Type', ud_get_wp_property()->domain ), WPP_F::property_label() ),
            'multiple' => false,
            'options' => array(
              'taxonomy' => 'wpp_listing_type',
              'type' => 'select',
              'args' => array(),
            )
          ) ), 'wpp_listing_type', $post );
          return $field;
        }, 10, 2 );

        // Update legacy property_type on saving property
        add_action( "save_property", array( $this, 'save_property' ), 10, 1 );

        add_action( 'created_wpp_listing_type', array($this, 'term_created_wpp_listing_type'), 10, 2 );
        add_action( 'edited_wpp_listing_type', array($this, 'term_created_wpp_listing_type'), 10, 2 );
        add_action( 'pre_delete_term', array($this, 'pre_delete_term'), 10, 4 );
        add_action( 'wpp_settings_save', array( $this, 'sync_property_type_terms'), 10, 2 );

        add_filter('wpp_taxonomies', function( $taxonomies = array() ) {
          $taxonomies[ self::$taxonomy ] = array(
            'default' => true,
            'readonly' => false,
            'system' => true,
            'hidden' => true,
            'hierarchical' => true,
            'unique' => true,
            'public' => true,
            'show_in_nav_menus' => true,
            'show_ui' => false,
            'show_in_menu' => true,
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
          $taxonomies[ self::$sub_taxonomy ] = array(
            'default' => true,
            'readonly' => false,
            'system' => true,
            'hidden' => true,
            'hierarchical' => false,
            'unique' => false,
            'public' => true,
            'show_in_nav_menus' => true,
            'show_ui' => false,
            'show_in_menu' => true,
            'show_tagcloud' => false,
            'add_native_mtbox' => false,
            "admin_searchable" => true,
            'label' => sprintf(_x('%s Sub Type', 'property sub type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            'labels' => array(
              'name' => sprintf(_x('%s Sub Type', 'property sub type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
              'singular_name' => sprintf(_x('%s Sub Type', 'property sub type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
              'search_items' => _x('Search Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'all_items' => _x('All Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'parent_item' => _x('Parent Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'parent_item_colon' => _x('Parent Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'edit_item' => _x('Edit Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'update_item' => _x('Update Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'add_new_item' => _x('Add New Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'new_item_name' => _x('New Type', 'property sub type taxonomy', ud_get_wp_property()->domain),
              'not_found' => sprintf(_x('No %s Sub type found', 'property sub type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
              'menu_name' => sprintf(_x('%s Sub Type', 'property sub type taxonomy', ud_get_wp_property()->domain), WPP_F::property_label()),
            ),
            'query_var' => 'sub_type',
            'rewrite' => array('slug' => 'sub_type')
          );
          return $taxonomies;
        }, 10 );

        add_filter( 'wpp:elastic:title_suggest', array( $this, 'elastic_title_suggest' ), 10, 3 );

        // Add our custom class to Property Types table on Settings page
        // So we could hide 'add/delete options' in property types UI.
        add_filter( 'wpp::css::wpp_inquiry_property_types::classes', function( $classes ) {
          if( is_string( $classes ) ) $classes .= " active-wpp-listing-type-terms";
          else if ( is_array( $classes ) ) array_push( $classes, "active-wpp-listing-type-terms" );
          return $classes;
        } );
        add_action('admin_enqueue_scripts', function() {
          global $current_screen;
          if( $current_screen->id == 'property_page_property_settings' ) {
            wp_enqueue_style( 'wpp-terms-listing-type-settings', ud_get_wp_property()->path( 'lib/features/taxonomy-wpp-listing-type/static/styles/wpp.terms.listing_type.settings.css', 'url' ) );
          }
        });

        // Add our custom class to Property Types table on Settings page
        // So we could hide 'add/delete options' in property types UI.
        add_filter( 'wpp::css::wpp_inquiry_property_types::classes', function( $classes ) {
          if( is_string( $classes ) ) $classes .= " active-wpp-listing-type-terms";
          else if ( is_array( $classes ) ) array_push( $classes, "active-wpp-listing-type-terms" );
          return $classes;
        } );
        add_action('admin_enqueue_scripts', function() {
          global $current_screen;
          if( $current_screen->id == 'property_page_property_settings' ) {
            wp_enqueue_style( 'wpp-terms-listing-type-settings', ud_get_wp_property()->path( 'lib/features/taxonomy-wpp-listing-type/static/styles/wpp.terms.listing_type.settings.css', 'url' ) );
          }
        });

        // WP-CLI commands:
        // `wp property scroll --do-action=wpp_listing_type`
        add_action( 'wpp::cli::scroll::wpp_listing_type', array( $this, 'cli_update_post_property_type' ), 10, 2 );
        // `wp property trigger --do-action=upgrade_property_types`
        add_action( 'wpp::cli::trigger::upgrade_property_types', array( $this, 'cli_sync_property_types' ), 10, 1 );

        // May be fix property_type
        // It must be equal to wpp_listing_type term association
        add_action( 'updated_postmeta', function( $meta_id, $object_id, $meta_key, $meta_value ) {
          if( $meta_key == self::$meta_property_type && get_post_type( $object_id ) == 'property' ){
            self::update_old_property_type( $object_id, $meta_value );
          }
        }, 10, 4 );

        // May be fix property_type
        // It must be equal to wpp_listing_type term association
        add_action( 'set_object_terms', function( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
          if( self::$taxonomy == $taxonomy ) {
            self::update_old_property_type( $object_id, get_post_meta( $object_id, 'property_type', true ) );
          }
        }, 10, 6 );

      }

      /**
       * Updates old wpp property type to match
       * new term of wpp_listing_type taxonomy
       *
       * @param $post_id
       * @param string $old_value
       */
      static public function update_old_property_type( $post_id, $old_value = "" ) {
        $term = self::get_property_direct_term( $post_id );
        if( !$term ) {
          return;
        }
        if( $term ) {
          $property_type = self::get_meta_property_type( $term );
          if( !empty( $property_type ) && $property_type !== $old_value ) {
            update_post_meta( $post_id, 'property_type', $property_type );
          }
        }
      }

      /**
       * Setup property_type on saving post
       *
       * @param $post_id
       */
      public function save_property( $post_id ) {
        self::update_old_property_type( $post_id );
      }

      /**
       * On WPP Settings Update event
       * we're syncing property types with wpp_listing_type terms
       *
       *
       */
      public function sync_property_type_terms( $wpp_settings, $wp_properties ) {

        remove_action( 'created_wpp_listing_type', array( $this, 'term_created_wpp_listing_type' ), 10, 2 );
        remove_action( 'edited_wpp_listing_type', array( $this, 'term_created_wpp_listing_type' ), 10, 2 );

        $terms = get_terms( self::$taxonomy, [
          'hide_empty' => false
        ]);

        // Add missing terms

        $property_types = $wpp_settings[ 'property_types' ];
        $terms = $this->get_terms_hierarchicaly( $terms, '/' );

        foreach( $terms as $term ) {
          // It must not happen, actually
          if( !isset( $term->meta[ self::$meta_property_type ] ) ) {
            continue;
          }
          $slug = $term->meta[ self::$meta_property_type ];

          if( !isset( $property_types[ $slug ] ) ) {
            $property_types[ $slug ] = $term->name;
            update_term_meta( $term->term_id, self::$meta_property_type, $slug );
          }
        }

        ksort($property_types);

        foreach( $property_types as $slug => $label ){
          $exist = false;
          foreach( $terms as $term ) {
            if( $term->meta[ self::$meta_property_type ] == $slug ) {
              $exist = true;
            }
          }
          if( !$exist ) {
            $term = term_exists($slug, self::$taxonomy);
            if (!$term) {
              unset( $property_types[$slug] );
            }
          }
        }

        ksort($property_types);

        $wpp_settings[ 'property_types' ] = $property_types;

        return $wpp_settings;

      }

      /**
       *
       * WP-CLI: `wp property trigger --do-action=upgrade_property_types`
       *
       * Used on action: 'wpp::cli::trigger::upgrade_property_types'
       *
       */
      public function cli_sync_property_types( $args ) {

        remove_action( 'created_wpp_listing_type', array( $this, 'term_created_wpp_listing_type' ), 10, 2 );
        remove_action( 'edited_wpp_listing_type', array( $this, 'term_created_wpp_listing_type' ), 10, 2 );

        $terms = get_terms( self::$taxonomy, [
          'hide_empty' => false
        ]);

        if( is_wp_error( $terms ) ) {
          \WP_CLI::error( $terms->get_error_message() );
          return;
        }

        if( empty( $terms ) ) {
          \WP_CLI::log( 'No terms found' );
          return;
        }

        \WP_CLI::log( 'STEP 1. Merging property types with wpp_listing_type_taxonomy terms...' );

        $terms = $this->get_terms_hierarchicaly( $terms, '/' );
        $property_types = ud_get_wp_property( 'property_types' );

        foreach( $terms as $term ) {
          // It must not happen, actually
          if( !isset( $term->meta[ self::$meta_property_type ] ) ) {
            continue;
          }
          $slug = $term->meta[ self::$meta_property_type ];
          if( !isset( $property_types[ $slug ] ) ) {
            \WP_CLI::log( sprintf( __( 'Creating property type [%s]. Slug [%s]. Term ID [%s]' ), $term->name, $slug, $term->term_id ) );
            $property_types[ $slug ] = $term->name;
            update_term_meta( $term->term_id, self::$meta_property_type, $slug );
          }

          // if 'force' argument provided, we update property types labels as well
          else if( isset( $args[ 'force' ] ) ) {
            \WP_CLI::log( sprintf( __( 'Forcing to update property type with slug [%s]. Term ID [%s]. Old label [%s]. New label [%s]' ), $slug, $term->term_id, $property_types[ $slug ], $term->name ) );
            $property_types[ $slug ] = $term->name;
          }

        }

        \WP_CLI::log( 'STEP 2. Merging wpp_listing_type_taxonomy terms with property types...' );

        foreach( $property_types as $slug => $label ){
          $exist = false;
          foreach( $terms as $term ) {
            if( $term->meta[ self::$meta_property_type ] == $slug ) {
              $exist = true;
            }
          }
          if( !$exist ) {
            \WP_CLI::log( sprintf( __( 'No term assigned to property type [%s]. Trying to assign term' ), $slug ) );
            $term = term_exists($slug, self::$taxonomy);
            if (!$term) {
              $term = wp_insert_term( $label, self::$taxonomy, array(
                'slug' => $slug,
                'description' => 'Assigned property_type [' . $slug . ']'
              ));
              update_term_meta( $term['term_id'], self::$meta_property_type, $slug );
            } else {
              // Honestly it had not to happen...
              // Removing property type to exclude conflicts...
              \WP_CLI::log( sprintf( __( 'Conflict between term and property type [%s]' ), $slug ) );
              unset( $property_types[$slug] );
            }

          }
        }

        \WP_CLI::log( 'STEP 3. Updating wpp_settings..' );

        asort($property_types);
        print_r( $property_types );
        ud_get_wp_property()->set( 'property_types', $property_types );
        $wpp_settings = ud_get_wp_property()->get();
        update_option('wpp_settings', $wpp_settings);

      }

      /**
       * Updates/fixes property type of current property based on wpp_listing_type.
       * If property type does not exist, - it creates it.
       *
       * Note: the functions must be called ONLY on WP-CLI running
       *
       * See: wp-property/bin/wp-cli.php
       * Used on action: 'wpp::cli::scroll:wpp_listing_type'
       *
       * WP-CLI command: `wp property scroll --do-action=wpp_listing_type`
       *
       * @param $post_id
       */
      public function cli_update_post_property_type( $post_id, $args ) {
        $term = self::get_property_direct_term( $post_id );
        if( !$term ) {
          \WP_CLI::log( sprintf( __( 'No wpp_listing_type term found for [%s] property' ), $post_id ) );
          return;
        }
        $term = self::get_term( $term->term_id );
        if( isset( $term->meta[ self::$meta_property_type ] ) ) {
          \WP_CLI::log( sprintf( __( 'Updating property_type [%s] for [%s] property' ), $term->meta[ self::$meta_property_type ], $post_id ) );
          update_post_meta( $post_id, 'property_type', $term->meta[ self::$meta_property_type ] );
        }
      }

      /**
       * Migrates property types attributes to terms
       * It's moved from class-upgrade.php
       *
       * @note This must be ran after the 'init' hook since we call 'register_taxonomy'
       */
      public static function migrate_legacy_type_to_term(){

        //@TODO: disabled for now. It must be refactored. -> Term must be assigned to property type by it's meta 'property_type'. peshkov@UD
        return;

        global $wpdb, $wp_properties;

        $pp = $wpdb->get_results("SELECT ID from {$wpdb->posts} WHERE post_type='property'");

        /* Generate Property type terms */
        foreach ($wp_properties['property_types'] as $_term => $label) {
          $term = term_exists($label, self::$taxonomy);
          if (!$term) {
            $term = wp_insert_term($label, self::$taxonomy, array('slug' => $_term));
          }
        }

        if (!empty($pp)) {
          foreach ($pp as $p) {
            $property_type = get_post_meta($p->ID, 'property_type', true);
            if (!empty($property_type)) {
              wp_set_object_terms($p->ID, $property_type, self::$taxonomy );
            }
          }
        }

      }

      /**
       * Add/update Property Type to $wp_properties when wpp_listing_type
       * created/updated outside of developer tab of settings page.
       * Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       *
       * @param int $term_id
       * @param int $taxonomy_id
       *
       */
      public function term_created_wpp_listing_type( $term_id, $taxonomy_id ){
        global $wp_properties;

        $term = self::get_term( $term_id );
        // Should not happen
        if(!$term) {
          return;
        }

        $slug = get_term_meta( $term_id, self::$meta_property_type, true );
        if(!$slug) {
          // Should not happen
          if( !isset( $term->meta[ self::$meta_property_type ] ) ) {
            return;
          }
          $slug = $term->meta[ self::$meta_property_type ];
          update_term_meta( $term_id, self::$meta_property_type, $slug );
        }

        $_property_types = $property_types = ud_get_wp_property( 'property_types' );

        if( !isset( $property_types[ $slug ] ) ) {
          $property_types[ $slug ] = $term->name;
        }

        if( array_diff( $property_types, $_property_types ) ) {
          ud_get_wp_property()->set( 'property_types', $property_types );
          $wp_properties = ud_get_wp_property()->get();
          update_option('wpp_settings', $wp_properties);
        }

      }

      /**
       * Remove Property Type from $wp_properties when wpp_listing_type
       * deleted outside of developer tab of settings page.
       * Feature Flag: WPP_FEATURE_FLAG_WPP_LISTING_TYPE
       *
       * Note, we must use 'pre_delete_term' hook, because we have to get
       * term's meta data before it will be deleted.....
       *
       * @param int $term_id
       * @param string $taxonomy
       *
       */
      public function pre_delete_term($term_id, $taxonomy){
        global $wp_properties;

        // Ignore non-wpp_listing_type taxonomy terms
        if( self::$taxonomy !== $taxonomy ) {
          return;
        }

        $slug = get_term_meta( $term_id, self::$meta_property_type, true );
        $property_types = ud_get_wp_property( 'property_types' );

        if( isset( $property_types[$slug] ) ) {
          unset( $property_types[$slug] );
          $wp_properties = ud_get_wp_property()->get();
          $wp_properties['property_types'] = $property_types;
          update_option('wpp_settings', $wp_properties);
        }

      }

      /**
       * Returns term with extended label (based on hierarchic values )
       * and with meta property_type
       *
       * @TODO: need to get term with more sexy way instead of looping through all existing terms....
       *
       * @param $term_id
       * @param string $prefix
       * @return null
       */
      static public function get_term( $term_id, $prefix = '/' ) {
        $term = null;

        $terms = get_terms( self::$taxonomy, [
          'hide_empty' => false
        ]);
        $terms = self::get_terms_hierarchicaly( $terms, $prefix );
        foreach( $terms as $_term ){
          if( $_term->term_id == $term_id ) {
            $term = $_term;
            break;
          }
        }

        return $term;

      }

      /**
       * Get direct property term ( the direct child term of hierarchic structure )
       *
       * @param $post_id
       * @return object|null
       */
      static public function get_property_direct_term( $post_id ) {
        $terms = wp_get_object_terms( $post_id, self::$taxonomy );
        $_terms = array();
        $map = array();
        $term = null;

        foreach( $terms as $_term ) {
          $_terms[ $_term->term_id ] = $_term;
          if( !$_term->parent ) {
            $term = $_term;
          }
          if( $_term->parent ) {
            $map[ $_term->parent ] = $_term->term_id;
          }
        }

        if( $term ) {
          while( isset( $map[ $term->term_id ] ) && isset( $_terms[ $map[ $term->term_id ] ] ) ) {
            $term = $_terms[ $map[ $term->term_id ] ];
          }
        }

        return $term;
      }

      /**
       * Returns assigned property_type (slug) of term
       * If property_type meta does not exist, generate it from current term's name
       *
       * @param $term
       * @return mixed
       */
      static public function get_meta_property_type( $term ){
        $slug = get_term_meta( $term->term_id, self::$meta_property_type, true );
        if( !$slug ) {
          $slug = preg_replace( '/[\-]/', '_', sanitize_title( $term->name ) );
        }
        return $slug;
      }

      /**
       * Prepare terms hierarchicaly
       *
       * @param $terms
       * @return array
       */
      static public function get_terms_hierarchicaly($terms, $prefix = '/'){
        $_terms = array();
        $return = array();

        if(count($terms) == 0)
          return $return;

        // Prepering terms
        foreach ($terms as $term) {
          $term->meta = array(
            self::$meta_property_type => self::get_meta_property_type( $term )
          );
          $_terms[$term->parent][] = $term;
        }

        // Making terms as hierarchical by prefix
        foreach ($_terms[0] as $term) { // $_terms[0] is parent or parentless terms
          $return[] = $term;
          self::get_children($term->term_id, $_terms, $return, ( $term->name . ' ' . $prefix ));
        }

        return $return;
      }

      /**
       * Helper function for prepare_terms_hierarchicaly
       *
       * @param $term_id
       * @param $terms
       * @param $return
       * @param string $prefix
       */
      static public function get_children($term_id, $terms, &$return, $prefix = "/"){
        if(isset($terms[$term_id])){
          foreach ($terms[$term_id] as $child) {
            $child->name = $prefix . " " . $child->name;
            $child->meta[ self::$meta_property_type ] = self::get_meta_property_type( $child );
            $return[] = $child;
            self::get_children($child->term_id, $terms, $return, ( $prefix . ' ' . $child->name . ' ' . $prefix ));
          }
        }
      }

      /**
       * We apply contexts for title_suggest based on the [wpp_listing_type] taxonomy
       *
       * Used by: elasticsearch feature.
       *
       * @param $title_suggest
       * @param $args
       * @param $post_id
       * @return mixed
       */
      public function elastic_title_suggest( $title_suggest, $args, $post_id ) {

        $types = wp_get_object_terms( $post_id, self::$taxonomy );
        $subtypes = wp_get_object_terms( $post_id, self::$sub_taxonomy );

        if( empty( $types ) ) {
          return $title_suggest;
        }

        $listing_type = array();
        foreach( $types as $type ) {
          $listing_type[ sanitize_title( 'slug-' . $type->slug ) ] = $type->slug;
          $listing_type[ sanitize_title( 'name-' . $type->name ) ] = $type->name;

          // Combine Property Types and Property Sub Types
          if( !empty( $subtypes ) && is_array( $subtypes ) ) {

            foreach( $subtypes as $subtype ) {

              if( !isset( $listing_type[ sanitize_title( 'slug-' . $subtype->slug ) ] ) ) {
                $listing_type[ sanitize_title( 'slug-' . $subtype->slug ) ] = $subtype->slug;
              }

              if( !isset( $listing_type[ sanitize_title( 'name-' . $subtype->name ) ] ) ) {
                $listing_type[ sanitize_title( 'name-' . $subtype->name ) ] = $subtype->name;
              }

              $combined_slug = sanitize_title( $type->slug . '-' . $subtype->slug );
              $combined_value = sanitize_title( $type->name . '-' . $subtype->name );

              $listing_type[ sanitize_title( 'slug-' . $combined_slug ) ] = $combined_slug;
              $listing_type[ sanitize_title( 'name-' . $combined_value ) ] = $type->name . ' ' . $subtype->name;
            }

          }

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