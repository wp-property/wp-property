<?php
/**
 * WP-Property Settings
 *
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Settings' ) ) {

    class Settings extends \UsabilityDynamics\Settings {
    
      /**
       * Create Settings Instance
       *
       * @author potanin@UD
       * @since 2.0.0
       */
      static function define( $args = array() ) {
        global $wp_properties;

        // Instantiate Settings.
        $_instance = new Settings( (array) $args );
        
        // Default settings.
        $_instance->set( array(
          'property_stats' => array(),
          'attribute_classification' => array(),
          'property_stats_descriptions' => array(),
          'admin_attr_fields' => array(),
          'searchable_attr_fields' => array(),
          'sortable_attributes' => array(),
          'searchable_attributes' => array(),
          'predefined_values' => array(),
          'predefined_search_values' => array(),
          'property_types' => array(),
          'property_groups' => array(),
          'property_stats_groups' => array(),
          'searchable_property_types' => array(),
          'hidden_attributes' => array(),
          'property_meta' => array(), // Depreciated element. It has not been used since 2.0 version
          'property_inheritance' => array(),
          'image_sizes' => array(),
        ) );

        // Compute Settings.
        $_instance->set( '_computed', array(
          'path' => array(
            'root' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ),
            'vendor' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'vendor',
            'templates' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'templates',
            'scripts' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'scripts',
            'styles' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'styles',
            'schema' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'static/schemas',
            'data' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'static/data',
            'modules' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'vendor/usabilitydynamics'
          ),
          "url" => array(
            'root' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ),
            'vendor' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'vendor',
            'templates' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'templates',
            'scripts' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'scripts',
            'styles' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'styles',
            'schema' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'static/schemas',
            'data' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'static/data',
            'modules' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'vendor/usabilitydynamics'
          )
        ));
        
        //$_instance->set( '_data_structure', $_instance->get_data_structure() );
        
        // Set Schema now that paths are computed.
        $_instance->set_schema( $_instance->get( '_computed.path.schema' ), '/system.settings.schema.json' );

        // @note Hopefully temporary but this exposes settings to the legacy $wp_properties global variable.
        $wp_properties = $_instance->get();
        
        //echo "<pre>"; print_r( $wp_properties ); echo "</pre>";die();

        // self::settings_action();

        // Return Instance.
        return $_instance;

      }
      
      /**
       * Just return data.
       *
       * @param      $data
       * @param bool $format
       *
       * @return array|mixed|string|void
       */
      public function _output( $data, $format = false ) {
        return $data;
      }
      
      /**
       * Return array of WPP attributes, groups and types structure.
       *
       * @todo Taxonomy counts and some sort of uniqueness / quality score. - potanin@UD 8/14/12
       * @author potanin@UD
       * @author peshkov@UD
       */
      protected function get_data_structure( ) {
        global $wpdb, $wp_properties;

        //** STEP 1. Init all neccessary variables before continue. */

        //** Default classification */
        $def_cl_slug = 'string';
        $def_cl = !empty( $wp_properties[ '_attribute_classifications' ][ $def_cl_slug ] ) ? $wp_properties[ '_attribute_classifications' ][ $def_cl_slug ] : false;
        //** Classification Taxonomy */
        $def_cl_tax_slug = 'taxonomy';
        $def_cl_tax = !empty( $wp_properties[ '_attribute_classifications' ][ $def_cl_tax_slug ] ) ? $wp_properties[ '_attribute_classifications' ][ $def_cl_tax_slug ] : false;
        //** Default group */
        $def_group_slug = 'wpp_main';
        $def_group = !empty( $wp_properties[ '_predefined_groups' ][ $def_group_slug ] ) ? $wp_properties[ '_predefined_groups' ][ $def_group_slug ] : false;

        $default_attribute = array(
          'label' => '',
          'slug' => '',
          'description' => '',
          // Classification
          'classification' => !empty( $def_cl ) ? $def_cl_slug : false,
          'classification_label' => !empty( $def_cl ) ? $def_cl[ 'label' ] : false,
          'classification_settings' => !empty( $def_cl ) ? $def_cl[ 'settings' ] : false,
          // Specific data
          'type' => 'meta', // Available values: 'post', 'meta', 'taxonomy'
          'values' => false,
          'group' => !empty( $def_group ) ? $def_group_slug : false,
          'path' => false, // {group}.{attribute}
          'reserved' => false, // It's predefined by WPP or not
          'system' => false, // true if attribute is system data ( e.g. wp_posts data, like post_title, etc )
          'admin_inputs' => array(),
          'search_inputs' => array(),
          // Settings
          'searchable' => false,
          'sortable' => false,
          'in_overview' => false,
          'disabled' => false,
          'search_input_type' => false,
          'admin_input_type' => false,
          'search_predefined' => false,
          'admin_predefined' => false,
          'path' => false,
        );

        $default_group = array(
          'label' => '',
          'slug' => '',
          'reserved' => false,
        );

        $return = array(
          'attributes' => array(),
          'groups' => array(),
          'types' => array(),
        );

        //** STEP 2. Prepare the list of 'post column' attributes. These attributes are system and cannot be created or edited by user */
        $system_attributes = array();
        //** Add to system attributes all specific WP data ( wp_posts columns ) */
        $columns = $wpdb->get_results( "SELECT DISTINCT( column_name ) FROM information_schema.columns WHERE table_name = '{$wpdb->posts}'", ARRAY_N );
        foreach ( $columns as $column ) {
          $system_attributes[ ] = $column[ 0 ];
        }

        //** STEP 3. Prepare the list of predefined attributes ( Taxonomies also are related to this list ). These attributes cannot be created by user. */

        $predefined_attributes = !empty( $wp_properties[ '_predefined_attributes' ] ) ? $wp_properties[ '_predefined_attributes' ] : array();

        foreach ( $predefined_attributes as $k => $v ) {
          $predefined_attributes[ $k ][ 'type' ] = in_array( $k, $system_attributes ) ? 'post' : ( isset( $predefined_attributes[ $k ][ 'meta' ] ) && !$predefined_attributes[ $k ][ 'meta' ] ? 'post' : 'meta' );
          if ( isset( $v[ 'classification' ] ) && !empty( $wp_properties[ '_attribute_classifications' ][ $v[ 'classification' ] ] ) ) {
            $predefined_attributes[ $k ][ 'classification_label' ] = $wp_properties[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'label' ];
            $predefined_attributes[ $k ][ 'classification_settings' ] = $wp_properties[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'settings' ];
          }
          $predefined_attributes[ $k ] = array_merge( $default_attribute, $predefined_attributes[ $k ] );
        }

        $taxonomies = !empty( $wp_properties[ 'taxonomies' ] ) ? (array) $wp_properties[ 'taxonomies' ] : array();
        //** Add WP taxonomy 'category' to the predefined list */
        $taxonomies[ 'category' ] = \UsabilityDynamics\Utility::object_to_array( get_taxonomy( 'category' ) );

        //** Add other taxonomies to the predefined list */
        foreach ( $taxonomies as $taxonomy => $taxonomy_data ) {
          $predefined_attributes[ $taxonomy ] = array_merge( $default_attribute, array_filter( array(
            'label' => $taxonomy_data[ 'label' ],
            'slug' => $taxonomy,
            'type' => 'taxonomy',
            'decription' => __( 'The current attribute is just a link to the existing taxonomy.', 'wpp' ),
            'classification' => !empty( $def_cl_tax ) ? $def_cl_tax_slug : false,
            'classification_label' => !empty( $def_cl_tax ) ? $def_cl_tax[ 'label' ] : false,
            'classification_settings' => !empty( $def_cl_tax ) ? $def_cl_tax[ 'settings' ] : false,
          ) ) );
        }

        //** STEP 4. Get the main list of all property attributes and merge them with system and predefined attributes */

        $attributes = self::get_total_attribute_array();

        foreach ( $attributes as $meta_key => $label ) {
          $_data = self::get_attribute_data( $meta_key );

          $default = array_key_exists( $meta_key, $predefined_attributes ) ? $predefined_attributes[ $meta_key ] : $default_attribute;

          $return[ 'attributes' ][ $meta_key ] = \UsabilityDynamics\Utility::extend( $default, array_filter( array(
            'label' => $_data[ 'label' ],
            'slug' => $_data[ 'slug' ],
            'description' => isset( $_data[ 'description' ] ) ? $_data[ 'description' ] : false,
            'values' => !empty( $_data[ '_values' ] ) ? $_data[ '_values' ] : false,
            'group' => isset( $_data[ 'group_key' ] ) ? $_data[ 'group_key' ] : false,
            'reserved' => array_key_exists( $meta_key, $predefined_attributes ) ? true : false,
            'searchable' => isset( $_data[ 'searchable' ] ) ? $_data[ 'searchable' ] : false,
            'sortable' => isset( $_data[ 'sortable' ] ) ? $_data[ 'sortable' ] : false,
            'in_overview' => isset( $_data[ 'in_overview' ] ) ? $_data[ 'in_overview' ] : false,
            'disabled' => isset( $_data[ 'disabled' ] ) ? $_data[ 'disabled' ] : false,
            'search_input_type' => isset( $_data[ 'input_type' ] ) ? $_data[ 'input_type' ] : false,
            'admin_input_type' => isset( $_data[ 'data_input_type' ] ) ? $_data[ 'data_input_type' ] : false,
            'search_predefined' => isset( $_data[ 'predefined_search_values' ] ) ? $_data[ 'predefined_search_values' ] : false,
            'admin_predefined' => isset( $_data[ 'predefined_values' ] ) ? $_data[ 'predefined_values' ] : false,
            'path' => ( $_data[ 'group_key' ] ? $_data[ 'group_key' ] . '.' . $_data[ 'slug' ] : false ),
            'classification' => array_key_exists( $meta_key, $predefined_attributes ) ? $predefined_attributes[ $meta_key ][ 'classification' ] : false,
            'classification_label' => array_key_exists( $meta_key, $predefined_attributes ) ? $predefined_attributes[ $meta_key ][ 'classification_label' ] : false,
            'classification_settings' => array_key_exists( $meta_key, $predefined_attributes ) ? $predefined_attributes[ $meta_key ][ 'classification_settings' ] : false,
          ) ) );

          //** Set specific data based on system and predefined attributes */
          $return[ 'attributes' ][ $meta_key ][ 'type' ] = in_array( $meta_key, $system_attributes ) ? 'post' : 'meta';
          if ( array_key_exists( $meta_key, $predefined_attributes ) ) {
            $return[ 'attributes' ][ $meta_key ][ 'type' ] = $predefined_attributes[ $meta_key ][ 'type' ];
          } else {
            /* Check if the slug exists in classifications, if so, override classification's settings */
            $classification = !empty( $wp_properties[ 'attribute_classification' ][ $meta_key ] ) ? $wp_properties[ 'attribute_classification' ][ $meta_key ] : false;
            if ( $classification && isset( $wp_properties[ '_attribute_classifications' ][ $classification ] ) ) {
              $return[ 'attributes' ][ $meta_key ][ 'classification' ] = $classification;
              $return[ 'attributes' ][ $meta_key ][ 'classification_label' ] = $wp_properties[ '_attribute_classifications' ][ $classification ][ 'label' ];
              $return[ 'attributes' ][ $meta_key ][ 'classification_settings' ] = $wp_properties[ '_attribute_classifications' ][ $classification ][ 'settings' ];
            }
          }
        }

        foreach ( $predefined_attributes as $k => $v ) {
          if ( !array_key_exists( $k, $return[ 'attributes' ] ) ) {
            $return[ 'attributes' ][ $k ] = $predefined_attributes[ $k ];
          }
        }

        //** Set specific data based on classification and type, etc */
        foreach ( $return[ 'attributes' ] as $k => $v ) {
          if ( $v[ 'type' ] === 'post' ) {
            $return[ 'attributes' ][ $k ][ 'system' ] = true;
            $return[ 'attributes' ][ $k ][ 'reserved' ] = true;
          }
          if ( array_key_exists( $k, $predefined_attributes ) ) {
            $return[ 'attributes' ][ $k ][ 'reserved' ] = true;
          }
          if ( !empty( $wp_properties[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'admin' ] ) ) {
            foreach ( (array) $wp_properties[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'admin' ] as $slug => $label ) {
              $return[ 'attributes' ][ $k ][ 'admin_inputs' ][ ] = array( 'slug' => $slug, 'label' => $label );
            }
          }
          if ( !empty( $wp_properties[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'search' ] ) ) {
            foreach ( (array) $wp_properties[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'search' ] as $slug => $label ) {
              $return[ 'attributes' ][ $k ][ 'search_inputs' ][ ] = array( 'slug' => $slug, 'label' => $label );
            }
          }
        }

        //** STEP 5. Set property types and groups and return data */

        foreach ( (array) $wp_properties[ 'property_types' ] as $slug => $label ) {
          $return[ 'types' ][ $slug ] = array(
            'label' => $label,
            'slug' => $slug,
            'meta' => $wp_properties[ 'property_type_meta' ][ $slug ],
            'settings' => array(
              'geolocatable' => in_array( $slug, (array) $wp_properties[ 'location_matters' ] ) ? true : false,
              'searchable' => in_array( $slug, (array) $wp_properties[ 'searchable_property_types' ] ) ? true : false,
              'hierarchical' => in_array( $slug, (array) $wp_properties[ 'hierarchical_property_types' ] ) ? true : false,
            ),
            'hidden_attributes' => (array) $wp_properties[ 'hidden_attributes' ][ $slug ],
            'property_inheritance' => (array) $wp_properties[ 'property_inheritance' ][ $slug ],
          );
        }

        $predefined_groups = !empty( $wp_properties[ '_predefined_groups' ] ) ? $wp_properties[ '_predefined_groups' ] : array();
        if ( !empty( $wp_properties[ 'property_groups' ] ) ) {
          foreach ( (array) $wp_properties[ 'property_groups' ] as $group_slug => $data ) {
            $default = array_key_exists( $group_slug, $predefined_groups ) ? array_merge( $default_group, $predefined_groups[ $group_slug ] ) : $default_group;
            $return[ 'groups' ][ $group_slug ] = \UsabilityDynamics\Utility::extend( $default, array_filter( array(
              'label' => $data[ 'name' ],
              'slug' => $group_slug,
            ) ) );
          }
        }
        foreach ( array_reverse( $predefined_groups ) as $k => $v ) {
          if ( !array_key_exists( $k, $return[ 'groups' ] ) ) {
            $return[ 'groups' ] = array( $k => array_merge( $default_group, $predefined_groups[ $k ] ) ) + $return[ 'groups' ];
          }
        }

        return \UsabilityDynamics\Utility::array_filter_deep( (array) $return );

      }

      /**
       * Loads settings into global variable
       * Also restores data from backup file.
       *
       * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
       *
       * As of 1.11 prevents removal of premium feature configurations that are not held in the settings page array
       *
       * 1.12 - added taxonomies filter: wpp_taxonomies
       * 1.14 - added backup from text file
       *
       * @param bool $force_db
       *
       * @return array|$wp_properties
       * @since 1.12
       */
      static function settings_action( $force_db = false ) {
        global $wp_properties;

        //** Handle backup */
        if( isset( $_REQUEST[ 'wpp_settings' ] ) &&
          wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'wpp_setting_save' ) &&
          !empty( $_FILES[ 'wpp_settings' ][ 'tmp_name' ][ 'settings_from_backup' ] )
        ) {
          $backup_file     = $_FILES[ 'wpp_settings' ][ 'tmp_name' ][ 'settings_from_backup' ];
          $backup_contents = file_get_contents( $backup_file );
          if( !empty( $backup_contents ) ) {
            $decoded_settings = json_decode( $backup_contents, true );
          }
          if( !empty( $decoded_settings ) ) {
            //** Allow features to preserve their settings that are not configured on the settings page */
            $wpp_settings = apply_filters( 'wpp_settings_save', $decoded_settings, $wp_properties );
            //** Prevent removal of featured settings configurations if they are not present */
            if( !empty( $wp_properties[ 'configuration' ][ 'feature_settings' ] ) ) {
              foreach( $wp_properties[ 'configuration' ][ 'feature_settings' ] as $feature_type => $preserved_settings ) {
                if( empty( $decoded_settings[ 'configuration' ][ 'feature_settings' ][ $feature_type ] ) ) {
                  $wpp_settings[ 'configuration' ][ 'feature_settings' ][ $feature_type ] = $preserved_settings;
                }
              }
            }
            update_option( 'wpp_settings', $wpp_settings );
            //** Load settings out of database to overwrite defaults from action_hooks. */
            $wp_properties_db = get_option( 'wpp_settings' );
            //** Overwrite $wp_properties with database setting */
            $wp_properties = array_merge( $wp_properties, $wp_properties_db );
            //** Reload page to make sure higher-end functions take affect of new settings */
            //** The filters below will be ran on reload, but the saving functions won't */
            if( $_REQUEST[ 'page' ] == 'property_settings' ) {
              unset( $_REQUEST );
              wp_redirect( admin_url( "edit.php?post_type=property&page=property_settings&message=updated" ) );
              exit;
            }
          }
        }

        if( $force_db ) {

          // Load settings out of database to overwrite defaults from action_hooks.
          $wp_properties_db = get_option( 'wpp_settings' );

          // Overwrite $wp_properties with database setting
          $wp_properties = array_merge( $wp_properties, $wp_properties_db );

        }

        add_filter( 'wpp_image_sizes', array( 'WPP_F', 'remove_deleted_image_sizes' ) );

        // Filers are applied
        $wp_properties[ 'configuration' ]             = apply_filters( 'wpp_configuration', $wp_properties[ 'configuration' ] );
        $wp_properties[ 'location_matters' ]          = apply_filters( 'wpp_location_matters', $wp_properties[ 'location_matters' ] );
        $wp_properties[ 'hidden_attributes' ]         = apply_filters( 'wpp_hidden_attributes', $wp_properties[ 'hidden_attributes' ] );
        $wp_properties[ 'descriptions' ]              = apply_filters( 'wpp_label_descriptions', $wp_properties[ 'descriptions' ] );
        $wp_properties[ 'image_sizes' ]               = apply_filters( 'wpp_image_sizes', $wp_properties[ 'image_sizes' ] );
        $wp_properties[ 'search_conversions' ]        = apply_filters( 'wpp_search_conversions', $wp_properties[ 'search_conversions' ] );
        $wp_properties[ 'searchable_attributes' ]     = apply_filters( 'wpp_searchable_attributes', $wp_properties[ 'searchable_attributes' ] );
        $wp_properties[ 'searchable_property_types' ] = apply_filters( 'wpp_searchable_property_types', $wp_properties[ 'searchable_property_types' ] );
        $wp_properties[ 'property_inheritance' ]      = apply_filters( 'wpp_property_inheritance', $wp_properties[ 'property_inheritance' ] );
        $wp_properties[ 'property_meta' ]             = apply_filters( 'wpp_property_meta', $wp_properties[ 'property_meta' ] );
        $wp_properties[ 'property_stats' ]            = apply_filters( 'wpp_property_stats', $wp_properties[ 'property_stats' ] );
        $wp_properties[ 'property_types' ]            = apply_filters( 'wpp_property_types', $wp_properties[ 'property_types' ] );
        $wp_properties[ 'taxonomies' ]                = apply_filters( 'wpp_taxonomies', ( isset( $wp_properties[ 'taxonomies' ] ) ? $wp_properties[ 'taxonomies' ] : array() ) );

        $wp_properties = stripslashes_deep( $wp_properties );

        return $wp_properties;

      }

    }

  }

}



