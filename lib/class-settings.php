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
       * Default stored data.
       * It must be used for \UsabilityDynamics\WPP\Settings::commit
       */
      private $_db_data;
      
      /**
       * Path to json schemas.
       */
      private $_schemas_path;
    
      /**
       * Create Settings Instance
       *
       * @author potanin@UD
       * @since 2.0.0
       */
      static function define( ) {
        global $wp_properties;

        // STEP 1. Instantiate Settings object
        $_instance = new Settings( array(
          "store" => "options",
          "key" => "wpp_settings"
        ) );
        
        $_instance->_schemas_path = trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'static/schemas';
        
        // STEP 2. Prepare default data which is used for storing in DB.
        
        $_data = $_instance->get();
        
        if( empty( $_data ) ) {
          $_instance->set( $_instance->_get_default_settings() );
        }
        
        // Set Build Mode value
        $build_mode = isset( $_data[ 'configuration' ][ 'build_mode' ] ) ? $_data[ 'configuration' ][ 'build_mode' ] : false;
        $build_mode = ( $build_mode == 'true' ) ? true : false;
        $_instance->set( 'configuration.build_mode', $build_mode );
        
        // Default stored data.
        $_instance->_db_data = $_instance->get();
        
        // STEP 3. Update ( Upgrade ) Settings data with dynamic and computed values.
        
        if ( defined( 'WPP_DEBUG' ) && WPP_DEBUG ) {
          $_instance->set( 'configuration.developer_mode', 'true' );
          $_instance->set( 'configuration.build_mode', true );
        }
        
        // Set Schema now that paths are computed.
        $_instance->set_schema( $_instance->_schemas_path, '/system.settings.schema.json' );
        
        $_instance->_sync_data();
        
        // @note Hopefully temporary but this exposes settings to the legacy $wp_properties global variable.
        $wp_properties = $_instance->get();
        
        //echo "<pre>"; print_r( $wp_properties ); echo "</pre>";die();

        // Return Instance.
        return $_instance;

      }
      
      /**
       * Return array of WPP attributes, groups and types structure.
       *
       * @todo Taxonomy counts and some sort of uniqueness / quality score. - potanin@UD 8/14/12
       * @author potanin@UD
       * @author peshkov@UD
       */
      public function get_data_structure() {
        global $wpdb;
        
        $_data = $this->get();

        //** STEP 1. Init all neccessary variables before continue. */

        //** Default classification */
        $def_cl_slug = 'string';
        $def_cl = !empty( $_data[ '_attribute_classifications' ][ $def_cl_slug ] ) ? $_data[ '_attribute_classifications' ][ $def_cl_slug ] : false;
        //** Classification Taxonomy */
        $def_cl_tax_slug = 'taxonomy';
        $def_cl_tax = !empty( $_data[ '_attribute_classifications' ][ $def_cl_tax_slug ] ) ? $_data[ '_attribute_classifications' ][ $def_cl_tax_slug ] : false;
        //** Default group */
        $def_group_slug = 'wpp_main';
        $def_group = !empty( $_data[ '_predefined_groups' ][ $def_group_slug ] ) ? $_data[ '_predefined_groups' ][ $def_group_slug ] : false;

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

        $predefined_attributes = !empty( $_data[ '_predefined_attributes' ] ) ? $_data[ '_predefined_attributes' ] : array();

        foreach ( $predefined_attributes as $k => $v ) {
          $predefined_attributes[ $k ][ 'type' ] = in_array( $k, $system_attributes ) ? 'post' : ( isset( $predefined_attributes[ $k ][ 'meta' ] ) && !$predefined_attributes[ $k ][ 'meta' ] ? 'post' : 'meta' );
          if ( isset( $v[ 'classification' ] ) && !empty( $_data[ '_attribute_classifications' ][ $v[ 'classification' ] ] ) ) {
            $predefined_attributes[ $k ][ 'classification_label' ] = $_data[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'label' ];
            $predefined_attributes[ $k ][ 'classification_settings' ] = $_data[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'settings' ];
          }
          $predefined_attributes[ $k ] = array_merge( $default_attribute, $predefined_attributes[ $k ] );
        }

        $taxonomies = !empty( $_data[ 'taxonomies' ] ) ? (array) $_data[ 'taxonomies' ] : array();
        //** Add WP taxonomy 'category' to the predefined list */
        $taxonomies[ 'category' ] = Utility::object_to_array( get_taxonomy( 'category' ) );

        //** Add other taxonomies to the predefined list */
        foreach ( $taxonomies as $taxonomy => $taxonomy_data ) {
          $predefined_attributes[ $taxonomy ] = array_merge( $default_attribute, array_filter( array(
            'label' => $taxonomy_data[ 'label' ],
            'slug' => $taxonomy,
            'type' => 'taxonomy',
            'description' => __( 'The current attribute is just a link to the existing taxonomy.', 'wpp' ),
            'classification' => !empty( $def_cl_tax ) ? $def_cl_tax_slug : false,
            'classification_label' => !empty( $def_cl_tax ) ? $def_cl_tax[ 'label' ] : false,
            'classification_settings' => !empty( $def_cl_tax ) ? $def_cl_tax[ 'settings' ] : false,
          ) ) );
        }

        //** STEP 4. Get the main list of all property attributes and merge them with system and predefined attributes */

        $attributes = $this->get_attributes();

        foreach ( $attributes as $meta_key => $label ) {
          $_attribute = $this->get_attribute( $meta_key );

          $default = array_key_exists( $meta_key, $predefined_attributes ) ? $predefined_attributes[ $meta_key ] : $default_attribute;

          $return[ 'attributes' ][ $meta_key ] = \UsabilityDynamics\Utility::extend( $default, array_filter( array(
            'label' => $_attribute[ 'label' ],
            'slug' => $_attribute[ 'slug' ],
            'description' => isset( $_attribute[ 'description' ] ) ? $_attribute[ 'description' ] : false,
            'values' => !empty( $_attribute[ '_values' ] ) ? $_attribute[ '_values' ] : false,
            'group' => isset( $_attribute[ 'group_key' ] ) ? $_attribute[ 'group_key' ] : false,
            'reserved' => array_key_exists( $meta_key, $predefined_attributes ) ? true : false,
            'searchable' => isset( $_attribute[ 'searchable' ] ) ? $_attribute[ 'searchable' ] : false,
            'sortable' => isset( $_attribute[ 'sortable' ] ) ? $_attribute[ 'sortable' ] : false,
            'in_overview' => isset( $_attribute[ 'in_overview' ] ) ? $_attribute[ 'in_overview' ] : false,
            'disabled' => isset( $_attribute[ 'disabled' ] ) ? $_attribute[ 'disabled' ] : false,
            'search_input_type' => isset( $_attribute[ 'input_type' ] ) ? $_attribute[ 'input_type' ] : false,
            'admin_input_type' => isset( $_attribute[ 'data_input_type' ] ) ? $_attribute[ 'data_input_type' ] : false,
            'search_predefined' => isset( $_attribute[ 'predefined_search_values' ] ) ? $_attribute[ 'predefined_search_values' ] : false,
            'admin_predefined' => isset( $_attribute[ 'predefined_values' ] ) ? $_attribute[ 'predefined_values' ] : false,
            'path' => ( $_attribute[ 'group_key' ] ? $_attribute[ 'group_key' ] . '.' . $_attribute[ 'slug' ] : false ),
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
            $classification = !empty( $_data[ 'attribute_classification' ][ $meta_key ] ) ? $_data[ 'attribute_classification' ][ $meta_key ] : false;
            if ( $classification && isset( $_data[ '_attribute_classifications' ][ $classification ] ) ) {
              $return[ 'attributes' ][ $meta_key ][ 'classification' ] = $classification;
              $return[ 'attributes' ][ $meta_key ][ 'classification_label' ] = $_data[ '_attribute_classifications' ][ $classification ][ 'label' ];
              $return[ 'attributes' ][ $meta_key ][ 'classification_settings' ] = $_data[ '_attribute_classifications' ][ $classification ][ 'settings' ];
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
          if ( !empty( $_data[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'admin' ] ) ) {
            foreach ( (array) $_data[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'admin' ] as $slug => $label ) {
              $return[ 'attributes' ][ $k ][ 'admin_inputs' ][ ] = array( 'slug' => $slug, 'label' => $label );
            }
          }
          if ( !empty( $_data[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'search' ] ) ) {
            foreach ( (array) $_data[ '_attribute_classifications' ][ $v[ 'classification' ] ][ 'search' ] as $slug => $label ) {
              $return[ 'attributes' ][ $k ][ 'search_inputs' ][ ] = array( 'slug' => $slug, 'label' => $label );
            }
          }
        }

        //** STEP 5. Set property types and groups and return data */

        foreach ( (array) $_data[ 'property_types' ] as $slug => $label ) {
          $return[ 'types' ][ $slug ] = array(
            'label' => $label,
            'slug' => $slug,
            'meta' => $_data[ 'property_type_meta' ][ $slug ],
            'settings' => array(
              'geolocatable' => in_array( $slug, (array) $_data[ 'location_matters' ] ) ? true : false,
              'searchable' => in_array( $slug, (array) $_data[ 'searchable_property_types' ] ) ? true : false,
              'hierarchical' => in_array( $slug, (array) $_data[ 'hierarchical_property_types' ] ) ? true : false,
            ),
            'hidden_attributes' => (array) $_data[ 'hidden_attributes' ][ $slug ],
            'property_inheritance' => (array) $_data[ 'property_inheritance' ][ $slug ],
          );
        }

        $predefined_groups = !empty( $_data[ '_predefined_groups' ] ) ? $_data[ '_predefined_groups' ] : array();
        if ( !empty( $_data[ 'property_groups' ] ) ) {
          foreach ( (array) $_data[ 'property_groups' ] as $group_slug => $data ) {
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
       * Returns an array of all available attributes and meta keys
       *
       */
      public function get_attributes( $args = '', $extra_values = false ) {
        global $wpdb;

        $defaults = array(
          'use_optgroups' => 'false'
        );

        extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
        $use_optgroups = isset( $use_optgroups ) ? $use_optgroups : 'false';

        $property_stats = $this->get( 'property_stats' );
        $property_meta  = $this->get( 'property_meta' );
        
        $property_stats = is_array( $property_stats ) ? $property_stats : array();
        $property_meta = is_array( $property_meta ) ? $property_meta : array();
        $extra_values = is_array( $extra_values ) ? $extra_values : array();

        if( $use_optgroups == 'true' ) {
          $attributes[ 'Attributes' ] = $property_stats;
          $attributes[ 'Meta' ]       = $property_meta;
          $attributes[ 'Other' ]      = $extra_values;
        } else {
          $attributes = $property_stats + $property_meta + $extra_values;
        }
        
        $attributes = apply_filters( 'wpp_total_attribute_array', $attributes );

        if( !is_array( $attributes ) ) {
          $attributes = array();
        }

        return $attributes;

      }
      
      /**
       * Returns attribute information.
       * Checks wpp settings and returns a concise array of array-specific settings and attributes
       *
       * @updated 2.0
       * @version 1.17.3
       */
      public function get_attribute( $attribute = false ) {
        global $wpdb;
        
        $_data = $this->get();

        $return = array();

        if ( !$attribute ) {
          return false;
        }

        if ( wp_cache_get( $attribute, 'wpp_attribute_data' ) ) {
          return wp_cache_get( $attribute, 'wpp_attribute_data' );
        }

        //** Set post table keys ( wp_posts columns ) */
        $post_table_keys = array();
        $columns = $wpdb->get_results( "SELECT DISTINCT( column_name ) FROM information_schema.columns WHERE table_name = '{$wpdb->prefix}posts'", ARRAY_N );
        foreach ( $columns as $column ) {
          $post_table_keys[ ] = $column[ 0 ];
        }

        $ui_class = array( $attribute );

        $return[ 'storage_type' ] = in_array( $attribute, (array) $post_table_keys ) ? 'post_table' : 'meta_key';
        $return[ 'slug' ] = $attribute;

        if ( isset( $_data[ 'property_stats_descriptions' ][ $attribute ] ) ) {
          $return[ 'description' ] = $_data[ 'property_stats_descriptions' ][ $attribute ];
        }

        if ( isset( $_data[ 'property_stats_groups' ][ $attribute ] ) ) {
          $return[ 'group_key' ] = $_data[ 'property_stats_groups' ][ $attribute ];
          $return[ 'group_label' ] = $_data[ 'property_groups' ][ $_data[ 'property_stats_groups' ][ $attribute ] ][ 'name' ];
        }

        $return[ 'label' ] = $_data[ 'property_stats' ][ $attribute ];
        $return[ 'classification' ] = !empty( $_data[ 'attribute_classification' ][ $attribute ] ) ? $_data[ 'attribute_classification' ][ $attribute ] : 'string';

        $return[ 'is_stat' ] = ( !empty( $_data[ '_attribute_classifications' ][ $attribute ] ) && $_data[ '_attribute_classifications' ][ $attribute ] != 'detail' ) ? 'true' : 'false';

        if ( $return[ 'is_stat' ] == 'detail' ) {
          $return[ 'input_type' ] = 'textarea';
        }

        $ui_class[ ] = 'classification_' . $return[ 'classification' ];

        if ( isset( $_data[ 'searchable_attr_fields' ][ $attribute ] ) ) {
          $return[ 'input_type' ] = $_data[ 'searchable_attr_fields' ][ $attribute ];
          $ui_class[ ] = 'search_' . $return[ 'input_type' ];
        }

        if ( is_admin() && isset( $_data[ 'admin_attr_fields' ][ $attribute ] ) ) {
          $return[ 'data_input_type' ] = $_data[ 'admin_attr_fields' ][ $attribute ];
          $ui_class[ ] = 'admin_' . $return[ 'data_input_type' ];
        }

        if ( $_data[ 'configuration' ][ 'address_attribute' ] == $attribute ) {
          $return[ 'is_address_attribute' ] = 'true';
          $ui_class[ ] = 'address_attribute';
        }

        foreach ( (array) $_data[ 'property_inheritance' ] as $property_type => $type_data ) {
          if ( in_array( $attribute, (array) $type_data ) ) {
            $return[ 'inheritance' ][ ] = $property_type;
          }
        }

        $ui_class[ ] = $return[ 'data_input_type' ];

        if ( is_array( $_data[ 'predefined_values' ] ) && ( $predefined_values = $_data[ 'predefined_values' ][ $attribute ] ) ) {
          $return[ 'predefined_values' ] = $predefined_values;
          $return[ '_values' ] = (array) $return[ '_values' ] + explode( ',', $predefined_values );
        }

        if ( is_array( $_data[ 'predefined_search_values' ] ) && ( $predefined_values = $_data[ 'predefined_search_values' ][ $attribute ] ) ) {
          $return[ 'predefined_search_values' ] = $predefined_values;
          $return[ '_values' ] = (array) $return[ '_values' ] + explode( ',', $predefined_values );
        }

        if ( is_array( $_data[ 'sortable_attributes' ] ) && in_array( $attribute, (array) $_data[ 'sortable_attributes' ] ) ) {
          $return[ 'sortable' ] = true;
          $ui_class[ ] = 'sortable';
        }

        if ( is_array( $_data[ 'searchable_attributes' ] ) && in_array( $attribute, (array) $_data[ 'searchable_attributes' ] ) ) {
          $return[ 'searchable' ] = true;
          $ui_class[ ] = 'searchable';
        }

        if ( is_array( $_data[ 'column_attributes' ] ) && in_array( $attribute, (array) $_data[ 'column_attributes' ] ) ) {
          $return[ 'in_overview' ] = true;
          $ui_class[ ] = 'in_overview';
        }

        if ( is_array( $_data[ 'disabled_attributes' ] ) && in_array( $attribute, (array) $_data[ 'disabled_attributes' ] ) ) {
          $return[ 'disabled' ] = true;
          $ui_class[ ] = 'disabled';
        }

        //** Legacy. numeric, boolean and currency params should not be used anywhere more. peshkov@UD */
        if ( $return[ 'classification' ] == 'admin_note' ) {
          $return[ 'hidden_frontend_attribute' ] = true;
          $ui_class[ ] = 'fe_hidden';
        } else if ( $return[ 'classification' ] == 'currency' ) {
          $return[ 'currency' ] = true;
          $return[ 'numeric' ] = true;
        } else if ( $return[ 'classification' ] == 'area' ) {
          $return[ 'numeric' ] = true;
        } else if ( $return[ 'classification' ] == 'boolean' ) {
          $return[ 'boolean' ] = true;
        } else if ( $return[ 'classification' ] == 'numeric' ) {
          $return[ 'numeric' ] = true;
        }

        if ( in_array( $attribute, array_keys( (array) $_data[ '_predefined_attributes' ] ) ) ) {
          $return[ 'standard' ] = true;
          $ui_class[ ] = 'standard_attribute';
        }

        if ( empty( $return[ 'title' ] ) ) {
          $return[ 'title' ] = \UsabilityDynamics\Utility::de_slug( $return[ 'slug' ] );
        }

        $ui_class = array_filter( array_unique( $ui_class ) );
        $ui_class = array_map( create_function( '$class', 'return "wpp_{$class}";' ), $ui_class );
        $return[ 'ui_class' ] = implode( ' ', $ui_class );

        if ( is_array( $return[ '_values' ] ) ) {
          $return[ '_values' ] = array_unique( $return[ '_values' ] );
        }

        $return = apply_filters( 'wpp_attribute_data', array_filter( $return ) );

        wp_cache_add( $attribute, $return, 'wpp_attribute_data' );

        return $return;

      }

      /**
       * Returns the list of attributes belonging to geo classification which are searchable
       *
       * @return array
       * @author peshkov@UD
       */
      public function get_searchable_geo_parts() {
        $result = array();
        $_data = $this->get_data_structure();
        foreach ( (array) $_data[ 'attributes' ] as $k => $v ) {
          if ( $v[ 'classification' ] == 'geo' && isset( $v[ 'searchable' ] ) && $v[ 'searchable' ] ) {
            $result[ $k ] = $v[ 'label' ];
          }
        }
        return $result;
      }
      
      /**
       * Commit Settings to Storage.
       *
       * @TODO: implement saving data to storage.
       */
      public function commit() {
        // Convert to JSON String.
        //$_value = json_encode( $this->_data, JSON_FORCE_OBJECT );
        //$_value = \update_option( $this->_key, $_value );
        return $this;
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
       * Update ( Upgrade ) Settings data with dynamic and computed values
       *
       * @since 2.0
       */
      private function _sync_data( $args = array() ) {
      
        $_data = $this->get();
      
        $args = wp_parse_args( $args, array(
          'strip_protected_keys' => false,
          'stripslashes' => false,
          'sort' => false,
          'recompute' => ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ? false : $this->get( 'configuration.build_mode' ),
        ) );
        
        // System Settings
        $system_settings = array();
        $trns = !$args[ 'recompute' ] ? get_transient( 'wpp::system_settings' ) : false;
        if ( !empty( $trns ) && !in_array( $trns, array( '[]', 'null' ) ) ) {
          $system_settings = json_decode( $trns, true );
        } else {
          $system_settings = $this->_localize( json_decode( file_get_contents( $this->_schemas_path . '/system.settings.json' ), true ) );
          set_transient( 'wpp::system_settings', json_encode( $system_settings ), ( 60 * 60 * 24 ) );
        }
        
        // Default settings.
        $_data = \UsabilityDynamics\Utility::extend( array(
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
        ), $system_settings, $_data );
        
        // Filters are applied
        $_data[ 'configuration' ] = apply_filters( 'wpp_configuration', (array) ( !empty( $_data[ 'configuration' ] ) ? $_data[ 'configuration' ] : array() ) );
        $_data[ 'taxonomies' ] = apply_filters( 'wpp_taxonomies', ( !empty( $_data[ 'taxonomies' ] ) ? (array) $_data[ 'taxonomies' ] : array() ) );
        $_data[ 'property_stats_descriptions' ] = apply_filters( 'wpp_label_descriptions', (array) ( !empty( $_data[ 'property_stats_descriptions' ] ) ? $_data[ 'property_stats_descriptions' ] : array() ) );
        $_data[ 'location_matters' ] = apply_filters( 'wpp_location_matters', (array) ( !empty( $_data[ 'location_matters' ] ) ? $_data[ 'location_matters' ] : array() ) );
        $_data[ 'hidden_attributes' ] = apply_filters( 'wpp_hidden_attributes', (array) ( !empty( $_data[ 'hidden_attributes' ] ) ? $_data[ 'hidden_attributes' ] : array() ) );
        $_data[ 'image_sizes' ] = apply_filters( 'wpp_image_sizes', (array) ( !empty( $_data[ 'image_sizes' ] ) ? $_data[ 'image_sizes' ] : array() ) );
        $_data[ 'searchable_attributes' ] = apply_filters( 'wpp_searchable_attributes', (array) ( !empty( $_data[ 'searchable_attributes' ] ) ? $_data[ 'searchable_attributes' ] : array() ) );
        $_data[ 'searchable_property_types' ] = apply_filters( 'wpp_searchable_property_types', (array) ( !empty( $_data[ 'searchable_property_types' ] ) ? $_data[ 'searchable_property_types' ] : array() ) );
        $_data[ 'property_stats' ] = apply_filters( 'wpp_property_stats', (array) ( !empty( $_data[ 'property_stats' ] ) ? $_data[ 'property_stats' ] : array() ) );
        $_data[ 'property_types' ] = apply_filters( 'wpp_property_types', (array) ( !empty( $_data[ 'property_types' ] ) ? $_data[ 'property_types' ] : array() ) );
        $_data[ 'search_conversions' ] = apply_filters( 'wpp_search_conversions', (array) ( !empty( $_data[ 'search_conversions' ] ) ? $_data[ 'search_conversions' ] : array() ) );
        $_data[ 'property_inheritance' ] = apply_filters( 'wpp_property_inheritance', (array) ( !empty( $_data[ 'property_inheritance' ] ) ? $_data[ 'property_inheritance' ] : array() ) );
        $_data[ '_attribute_classifications' ] = apply_filters( 'wpp_attribute_classifications', (array) ( !empty( $_data[ '_attribute_classifications' ] ) ? $_data[ '_attribute_classifications' ] : array() ) );
        
        if ( $args[ 'stripslashes' ] ) {
          $_data = stripslashes_deep( $_data );
        }

        if ( $args[ 'sort' ] ) {
          ksort( $_data );
        }

        if ( $args[ 'strip_protected_keys' ] ) {
          $_data = \UsabilityDynamics\Utility::strip_protected_keys( $_data );
        }

        //** Get rid of disabled attributes */
        if ( is_array( $_data[ 'disabled_attributes' ] ) ) {
          foreach ( $_data[ 'disabled_attributes' ] as $attribute ) {
            if ( array_key_exists( $attribute, $_data[ 'property_stats' ] ) ) {
              if ( isset( $_data[ 'property_stats' ][ $attribute ] ) ) {
                unset( $_data[ 'property_stats' ][ $attribute ] );
              }
              if ( isset( $_data[ 'attribute_classification' ][ $attribute ] ) ) {
                unset( $_data[ 'property_stats' ][ $attribute ] );
              }
              if ( isset( $_data[ 'property_stats_groups' ][ $attribute ] ) ) {
                unset( $_data[ 'property_stats_groups' ][ $attribute ] );
              }
              if ( isset( $_data[ 'property_stats_descriptions' ][ $attribute ] ) ) {
                unset( $_data[ 'property_stats_descriptions' ][ $attribute ] );
              }
              if ( isset( $_data[ 'searchable_attr_fields' ][ $attribute ] ) ) {
                unset( $_data[ 'searchable_attr_fields' ][ $attribute ] );
              }
              if ( isset( $_data[ 'admin_attr_fields' ][ $attribute ] ) ) {
                unset( $_data[ 'admin_attr_fields' ][ $attribute ] );
              }
              if ( isset( $_data[ 'predefined_values' ][ $attribute ] ) ) {
                unset( $_data[ 'predefined_values' ][ $attribute ] );
              }
              if ( isset( $_data[ 'predefined_search_values' ][ $attribute ] ) ) {
                unset( $_data[ 'predefined_search_values' ][ $attribute ] );
              }
            }
          }
        }

        //** Set the list of frontend attributes */
        $_data[ 'frontend_property_stats' ] = $_data[ 'property_stats' ];
        //* System ( admin only ) attributes should not be showed. So we remove them from settings */
        foreach ( $_data[ 'frontend_property_stats' ] as $i => $stat ) {
          if ( isset( $_data[ 'attribute_classification' ][ $i ] ) ) {
            $classification = $_data[ '_attribute_classifications' ][ $_data[ 'attribute_classification' ][ $i ] ];
            if ( isset( $classification[ 'settings' ][ 'admin_only' ] ) && $classification[ 'settings' ][ 'admin_only' ] ) {
              unset( $_data[ 'frontend_property_stats' ][ $i ] );
            }
          }
        }
        
        // Update data
        $this->set( $_data );
        
        // Merges classifications with default settings
        $this->_update_attribute_classifications();
        
        // Adds taxonomies based on property attributes
        $this->_update_taxonomies();
        
        // Compute Settings
        $this->set( '_computed', $this->_get_computed( array( 'recompute' => $args[ 'recompute' ] ) ) );

        return $this->_data;
        
      }
      
      /**
       * Generates Computed Settings, saves to transient, and returns.
       * By default recomputing is disabled on AJAX requests.
       *
       * @author potanin@UD
       * @author peshkov@UD
       */
      private function _get_computed( $args = array() ) {

        $args = wp_parse_args( $args, array(
          'recompute' => ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ? false : $this->get( 'configuration.build_mode' ),
        ) );

        $trns = !$args[ 'recompute' ] ? get_transient( 'wpp::computed' ) : false;
        if ( !empty( $trns ) && $trns != '[]' ) {
          return json_decode( $trns, true );
        }

        /* Setup structure for system generated / API provided data */
        $_computed = array(
          'created' => time(),
          'data_structure' => $this->get_data_structure(),
          'searchable_geo_parts' => $this->get_searchable_geo_parts(),
          'primary_keys' => array(
            'post_title' => sprintf( __( '%1$s Title', 'wpp' ), Utility::property_label( 'singular' ) ),
            'post_type' => __( 'Post Type' ),
            "post_content" => sprintf( __( '%1$s Content', 'wpp' ), Utility::property_label( 'singular' ) ),
            'post_excerpt' => sprintf( __( '%1$s Excerpt', 'wpp' ), Utility::property_label( 'singular' ) ),
            'post_status' => sprintf( __( '%1$s Status', 'wpp' ), Utility::property_label( 'singular' ) ),
            'menu_order' => sprintf( __( '%1$s Order', 'wpp' ), Utility::property_label( 'singular' ) ),
            'post_date' => sprintf( __( '%1$s Date', 'wpp' ), Utility::property_label( "singular" ) ),
            'post_author' => sprintf( __( '%1$s Author', 'wpp' ), Utility::property_label( "singular" ) ),
            'post_date_gmt' => '',
            'post_parent' => '',
            'ping_status' => '',
            'comment_status' => '',
            'post_password' => ''
          ),
          'path' => array(
            'root' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ),
            'vendor' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'vendor',
            'templates' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'templates',
            'scripts' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'scripts',
            'styles' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'styles',
            'schema' => $this->_schemas_path,
            'data' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'static/data',
            'modules' => trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'vendor/usabilitydynamics'
          ),
          'url' => array(
            'root' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ),
            'vendor' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'vendor',
            'templates' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'templates',
            'scripts' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'scripts',
            'styles' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'styles',
            'schema' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'static/schemas',
            'data' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'static/data',
            'modules' => trailingslashit( plugin_dir_url( plugin_dir_path( __FILE__ ) ) ) . 'vendor/usabilitydynamics'
          ),
          'labels' => apply_filters( 'wpp_object_labels', array(
            'name'                => Utility::property_label( 'plural' ),
            'all_items'           => sprintf( __( 'All %1$s', 'wpp' ), Utility::property_label( 'plural' ) ),
            'singular_name'       => Utility::property_label( 'singular' ),
            'add_new'             => sprintf( __( 'Add %1$s', 'wpp' ), Utility::property_label( 'singular' ) ),
            'add_new_item'        => sprintf( __( 'Add New %1$s', 'wpp' ), Utility::property_label( 'singular' ) ),
            'edit_item'           => sprintf( __( 'Edit %1$s', 'wpp' ), Utility::property_label( 'singular' ) ),
            'new_item'            => sprintf( __( 'New %1$s', 'wpp' ), Utility::property_label( 'singular' ) ),
            'view_item'           => sprintf( __( 'View %1$s', 'wpp' ), Utility::property_label( 'singular' ) ),
            'search_items'        => sprintf( __( 'Search %1$s', 'wpp' ), Utility::property_label( 'plural' ) ),
            'not_found'           => sprintf( __( 'No %1$s found', 'wpp' ), Utility::property_label( 'plural' ) ),
            'not_found_in_trash'  => sprintf( __( 'No %1$s found in Trash', 'wpp' ), Utility::property_label( 'plural' ) ),
            'parent_item_colon'   => ''
          ) ),
        );

        $_computed = (array) apply_filters( 'wpp::computed', $_computed );

        set_transient( 'wpp::computed', json_encode( $_computed ), ( 60 * 60 * 24 ) );

        return $_computed;

      }
      
      /**
       * Localization functionality.
       * Replaces array's l10n data.
       * Helpful for localization of data which is stored in JSON files ( see /schemas )
       *
       * @param type $data
       *
       * @return type
       * @since 2.0
       * @author peshkov@UD
       */
      private function _localize( $data ) {

        if ( !is_array( $data ) ) return $data;

        //** The Localization's list. */
        $l10n = apply_filters( 'wpp::config::l10n', array(
          //** System (wp_posts) data */
          'post_title' => sprintf( __( '%1$s Title', 'wpp' ), ucfirst( Utility::property_label( 'singular' ) ) ),
          'post_type' => __( 'Post Type' ),
          'post_content' => sprintf( __( '%1$s Content', 'wpp' ), ucfirst( Utility::property_label( 'singular' ) ) ),
          'post_excerpt' => sprintf( __( '%1$s Excerpt', 'wpp' ), ucfirst( Utility::property_label( 'singular' ) ) ),
          'post_status' => sprintf( __( '%1$s Status', 'wpp' ), ucfirst( Utility::property_label( 'singular' ) ) ),
          'menu_order' => sprintf( __( '%1$s Order', 'wpp' ), ucfirst( Utility::property_label( 'singular' ) ) ),
          'post_date' => sprintf( __( '%1$s Date', 'wpp' ), ucfirst( Utility::property_label( "singular" ) ) ),
          'post_author' => sprintf( __( '%1$s Author', 'wpp' ), ucfirst( Utility::property_label( "singular" ) ) ),
          'post_date_gmt' => sprintf( __( '%1$s Date GMT', 'wpp' ), ucfirst( Utility::property_label( "singular" ) ) ),
          'post_parent' => sprintf( __( '%1$s Parent', 'wpp' ), ucfirst( Utility::property_label( "singular" ) ) ),
          'ping_status' => __( 'Ping Status', 'wpp' ),
          'comment_status' => __( 'Comment\'s Status', 'wpp' ),
          'post_password' => __( 'Password', 'wpp' ),

          //** Attributes Groups */
          'main' => __( 'General Information', 'wpp' ),

          //** Attributes and their descriptions */
          'price' => __( 'Price', 'wpp' ),
          'price_desc' => __( 'Numbers only', 'wpp' ),
          'bedrooms' => __( 'Bedrooms', 'wpp' ),
          'bedrooms_desc' => __( 'Numbers only', 'wpp' ),
          'bathrooms' => __( 'Bathrooms', 'wpp' ),
          'bathrooms_desc' => __( 'Numbers only', 'wpp' ),
          'phone_number' => __( 'Phone Number', 'wpp' ),
          'phone_number_desc' => __( '', 'wpp' ),
          'address' => __( 'Address', 'wpp' ),
          'address_desc' => __( 'Used by google validator', 'wpp' ),
          'area' => __( 'Area', 'wpp' ),
          'area_desc' => __( 'Numbers only', 'wpp' ),
          'deposit' => __( 'Deposit', 'wpp' ),
          'deposit_desc' => __( 'Numbers only', 'wpp' ),
          'geo_location' => __( 'Geo Location', 'wpp' ),
          'taxonomy' => __( 'Taxonomy', 'wpp' ),

          //** Property Types */
          'single_family_home' => __( 'Single Family Home', 'wpp' ),
          'building' => __( 'Building', 'wpp' ),
          'floorplan' => __( 'Floorplan', 'wpp' ),
          'farm' => __( 'Farm', 'wpp' ),

          //** Input types */
          'field_input' => __( 'Free Text', 'wpp' ),
          'field_dropdown' => __( 'Dropdown Selection', 'wpp' ),
          'field_textarea' => __( 'Textarea', 'wpp' ),
          'field_checkbox' => __( 'Checkbox', 'wpp' ),
          'field_multi_checkbox' => __( 'Multi-Checkbox', 'wpp' ),
          'field_range_input' => __( 'Text Input Range', 'wpp' ),
          'field_range_dropdown' => __( 'Range Dropdown', 'wpp' ),
          'field_date' => __( 'Date Picker', 'wpp' ),
          'range_date' => __( 'Range Date Picker', 'wpp' ),

          //** Attributes Classifications */
          'short_text' => __( 'Short Text', 'wpp' ),
          'used_for_short_desc' => __( 'Best used for short phrases and descriptions', 'wpp' ),

        ));

        //** Replace l10n entries */
        foreach ( $data as $k => $v ) {
          if ( is_array( $v ) ) {
            $data[ $k ] = self::_localize( $v );
          } elseif ( is_string( $v ) ) {
            if ( strpos( $v, 'l10n' ) !== false ) {
              preg_match_all( '/l10n\.([^\s]*)/', $v, $matches );
              if ( !empty( $matches[ 1 ] ) ) {
                foreach ( $matches[ 1 ] as $i => $m ) {
                  if ( key_exists( $m, $l10n ) ) {
                    $data[ $k ] = str_replace( $matches[ 0 ][ $i ], $l10n[ $m ], $data[ $k ] );
                  }
                }
              }
            }
          }
        }

        return $data;
      }
      
      /**
       * Returns default settings based on system ( schema/system.settings.json ) settings and on user choice ( default schema/default.settings.json )
       *
       * @param array settings. Schema
       *
       * @author peshkov@UD
       */
      private function _get_default_settings( $settings = false ) {

        //** STEP 1. Get default Settings from schema */
        $settings = $this->_localize( json_decode( file_get_contents( $this->_schemas_path . '/default.settings.json' ), true ) );

        //echo "<pre>"; print_r( $settings ); echo "</pre>";die();
        
        //** STEP 2. Create the data based on system settings */

        $system = self::_localize( json_decode( file_get_contents( $this->_schemas_path . '/system.settings.json' ), true ) );

        $_data = array(
          'configuration' => $system[ 'configuration' ],
          'property_stats' => array(),
          'attribute_classification' => array(),
          'property_stats_descriptions' => array(),

          'admin_attr_fields' => array(),
          'searchable_attr_fields' => array(),
          'sortable_attributes' => array(),
          'searchable_attributes' => array(),
          'column_attributes' => array(),
          'predefined_values' => array(),
          'predefined_search_values' => array(),

          'property_types' => array(),
          'searchable_property_types' => array(),
          'location_matters' => array(),

          'property_groups' => array(),
          'property_stats_groups' => array(),
        );

        //** Set default groups */
        foreach ( (array) $system[ '_predefined_groups' ] as $k => $v ) {
          $_data[ 'property_groups' ][ $v[ 'slug' ] ] = array(
            'name' => $v[ 'label' ]
          );
        }

        //** Begin to set default property attributes here */
        $predefined_attributes = (array) $system[ '_predefined_attributes' ];

        //** Add WP taxonomy 'category' to the predefined list */
        $taxonomies = array_merge( (array) $system[ 'taxonomies' ], array( 'category' => Utility::object_to_array( get_taxonomy( 'category' ) ) ) );
        
        //** Add other taxonomies to the predefined list */
        foreach ( $taxonomies as $taxonomy => $taxonomy_data ) {
          $predefined_attributes[ $taxonomy ] = array(
            'label' => $taxonomy_data[ 'label' ],
            'slug' => $taxonomy,
            'classification' => 'taxonomy',
            'description' => __( 'The current attribute is just a link to the existing taxonomy.', 'wpp' ),
            'meta' => true,
          );
        }

        foreach ( $predefined_attributes as $k => $v ) {
          if ( isset( $v[ 'meta' ] ) && $v[ 'meta' ] ) {
            $_data[ 'property_stats' ][ $v[ 'slug' ] ] = $v[ 'label' ];
            $_data[ 'attribute_classification' ][ $v[ 'slug' ] ] = $v[ 'classification' ];
            $_data[ 'property_stats_descriptions' ][ $v[ 'slug' ] ] = $v[ 'description' ];

            if ( !empty( $_data[ 'property_groups' ] ) ) {
              $_data[ 'property_stats_groups' ][ $v[ 'slug' ] ] = array_shift( array_keys( $_data[ 'property_groups' ] ) );
            }

            $clsf = !empty( $system[ '_attribute_classifications' ][ $v[ 'classification' ] ] ) ? $system[ '_attribute_classifications' ][ $v[ 'classification' ] ] : $system[ '_attribute_classifications' ][ 'string' ];

            $_data[ 'attribute_classification' ][ $v[ 'slug' ] ] = $clsf[ 'slug' ];

            if ( isset( $clsf[ 'settings' ][ 'editable' ] ) && $clsf[ 'settings' ][ 'editable' ] && !empty( $clsf[ 'admin' ] ) ) {
              $_data[ 'admin_attr_fields' ][ $v[ 'slug' ] ] = array_shift( array_keys( $clsf[ 'admin' ] ) );
            }

            if ( isset( $clsf[ 'settings' ][ 'searchable' ] ) && $clsf[ 'settings' ][ 'searchable' ] && !empty( $clsf[ 'search' ] ) ) {
              $_data[ 'searchable_attr_fields' ][ $v[ 'slug' ] ] = array_shift( array_keys( $clsf[ 'search' ] ) );
            }
          }
        }

        //** STEP 3. Merge with 'custom default' settings which can be got from specific json schema */

        //** Set Configuration */
        if ( isset( $settings[ 'configuration' ] ) ) {
          $_data[ 'configuration' ] = \UsabilityDynamics\Utility::extend( $_data[ 'configuration' ], (array) $settings[ 'configuration' ] );
        }

        //** Set Property types */
        if ( isset( $settings[ 'types' ] ) ) {

          $default_type = array(
            'slug' => '',
            'label' => '',
            'description' => '',
            'searchable' => false,
            'location_matters' => false,
          );

          foreach ( (array) $settings[ 'types' ] as $k => $v ) {
            $v = \UsabilityDynamics\Utility::extend( $default_type, $v );

            $_data[ 'property_types' ][ $v[ 'slug' ] ] = $v[ 'label' ];

            if ( $v[ 'searchable' ] ) {
              $_data[ 'searchable_property_types' ][ ] = $v[ 'slug' ];
            }

            if ( $v[ 'location_matters' ] ) {
              $_data[ 'location_matters' ][ ] = $v[ 'slug' ];
            }
          }
        }

        //** Set Groups */
        if ( isset( $settings[ 'groups' ] ) ) {
          $default_group = array(
            'slug' => '',
            'label' => '',
          );

          foreach ( $settings[ 'groups' ] as $k => $v ) {
            $v = \UsabilityDynamics\Utility::extend( $default_group, ( isset( $_data[ '_predefined_groups' ][ $v[ 'slug' ] ] ) ? $_data[ '_predefined_groups' ][ $v[ 'slug' ] ] : array() ), $v );
            $_data[ 'property_groups' ][ $v[ 'slug' ] ] = array(
              'name' => $v[ 'label' ],
            );
          }
        }

        //** Set attributes */
        if ( isset( $settings[ 'attributes' ] ) ) {

          $default_attribute = array(
            'slug' => '',
            'label' => '',
            'description' => '',
            'classification' => 'string',
            'searchable' => false,
            'sortable' => false,
            'in_overview' => false,
            'search_input_type' => 'input',
            'admin_input_type' => 'input',
            'group' => !empty( $_data[ 'property_groups' ] ) ? array_shift( array_keys( (array) $_data[ 'property_groups' ] ) ) : false,
          );

          foreach ( (array) $settings[ 'attributes' ] as $k => $v ) {
            $v = \UsabilityDynamics\Utility::extend( $default_attribute, ( isset( $predefined_attributes[ $v[ 'slug' ] ] ) ? $predefined_attributes[ $v[ 'slug' ] ] : array() ), $v );

            if ( !empty( $v[ 'slug' ] ) && !empty( $v[ 'label' ] ) ) {

              $_data[ 'property_stats' ][ $v[ 'slug' ] ] = $v[ 'label' ];
              $_data[ 'attribute_classification' ][ $v[ 'slug' ] ] = $v[ 'classification' ];
              $_data[ 'property_stats_descriptions' ][ $v[ 'slug' ] ] = $v[ 'description' ];

              if ( !empty( $v[ 'group' ] ) ) {
                $v[ 'group' ] = key_exists( $v[ 'group' ], $_data[ 'property_groups' ] ) ? $v[ 'group' ] : array_shift( array_keys( $_data[ 'property_groups' ] ) );
                $_data[ 'property_stats_groups' ][ $v[ 'slug' ] ] = $v[ 'group' ];
              }

              if ( $v[ 'searchable' ] && !in_array( $v[ 'slug' ], $_data[ 'searchable_attributes' ] ) ) {
                $_data[ 'searchable_attributes' ][ ] = $v[ 'slug' ];
              }

              if ( $v[ 'sortable' ] && !in_array( $v[ 'slug' ], $_data[ 'sortable_attributes' ] ) ) {
                $_data[ 'sortable_attributes' ][ ] = $v[ 'slug' ];
              }

              if ( $v[ 'in_overview' ] && !in_array( $v[ 'slug' ], $_data[ 'column_attributes' ] ) ) {
                $_data[ 'column_attributes' ][ ] = $v[ 'slug' ];
              }

              $clsf = !empty( $system[ '_attribute_classifications' ][ $v[ 'classification' ] ] ) ? $system[ '_attribute_classifications' ][ $v[ 'classification' ] ] : $system[ '_attribute_classifications' ][ 'string' ];

              $_data[ 'attribute_classification' ][ $v[ 'slug' ] ] = $clsf[ 'slug' ];

              if ( isset( $clsf[ 'settings' ][ 'editable' ] ) && $clsf[ 'settings' ][ 'editable' ] && !empty( $clsf[ 'admin' ] ) ) {
                $v[ 'admin_input_type' ] = !empty( $v[ 'admin_input_type' ] ) && key_exists( $v[ 'admin_input_type' ], $clsf[ 'admin' ] ) ? $v[ 'admin_input_type' ] : array_shift( array_keys( $clsf[ 'admin' ] ) );
                $_data[ 'admin_attr_fields' ][ $v[ 'slug' ] ] = $v[ 'admin_input_type' ];
              }

              if ( isset( $clsf[ 'settings' ][ 'searchable' ] ) && $clsf[ 'settings' ][ 'searchable' ] && !empty( $clsf[ 'search' ] ) ) {
                $v[ 'search_input_type' ] = !empty( $v[ 'search_input_type' ] ) && key_exists( $v[ 'search_input_type' ], $clsf[ 'search' ] ) ? $v[ 'search_input_type' ] : array_shift( array_keys( $clsf[ 'search' ] ) );
                $_data[ 'searchable_attr_fields' ][ $v[ 'slug' ] ] = $v[ 'search_input_type' ];
              }

            }
          }
        }

        return $_data;

      }
      
      /**
       * Merges classifications with default settings
       * Updates search/admin input data
       *
       * @uses UsabilityDynamics\WPP\Settings::_update_input_types()
       *
       * @param array $classifications
       * @return array
       * @author peshkov@UD
       * @since 2.0
       */
      private function _update_attribute_classifications() {
        $_data = $this->get( '_attribute_classifications' );
        if ( !is_array( $_data ) ) {
          $this->set( '_attribute_classifications', array() );
          return false;
        };
        foreach ( $_data as $k => $v ) {
          $v[ 'settings' ] = \UsabilityDynamics\Utility::extend( array(
            'searchable' => true,
            'editable' => true,
            'admin_only' => false,
            'system' => false,
            'can_be_disabled' => false,
            'admin_predefined_values' => true,
            'search_predefined_values' => true,
          ), $v[ 'settings' ] );

          // Update input types data
          if ( isset( $v[ 'search' ] ) ) {
            $v[ 'search' ] = $this->_update_input_types( $v[ 'search' ] );
          }
          if ( isset( $v[ 'admin' ] ) ) {
            $v[ 'admin' ] = $this->_update_input_types( $v[ 'admin' ] );
          }
          $this->set( "_attribute_classifications.{$k}", $v );
        }
        return true;
      }
      
      /**
       * Updates search/admin input types data.
       *
       * @param array $types
       * @return array
       * @author peshkov@UD
       * @since 2.0
       */
      private function _update_input_types( $types ) {
        $_data = $this->get( '_input_types' );
        if ( !empty( $_data ) && is_array( $types ) ) {
          $arr = array();
          foreach ( $types as $i => $label ) {
            if ( is_numeric( $i ) && key_exists( $label, (array)$_data ) ) {
              $arr[ $label ] = $_data[ $label ];
            } else {
              $arr[ $i ] = $label;
            }
          }
          $types = $arr;
        }
        return $types;
      }
      
      /**
       * Adds taxonomies based on property attributes ( where classification is taxonomy )
       *
       * @since 2.0
       * @author peshkov@UD
       */
      private function _update_taxonomies() {
        foreach ( $this->get( 'attribute_classification' ) as $k => $v ) {
          if ( $v === 'taxonomy' && !$this->get( "taxonomies.{$k}" ) ) {
            $this->set( "taxonomies.{$k}", array(
              'label' => $this->get( "property_stats.{$k}" ),
            ) );
          }
        }
      }
      
      /**
       * Deprecated.
       * Commit must be used instead.
       * @TODO: remove before release.
       *
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



