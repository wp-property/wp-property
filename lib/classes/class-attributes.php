<?php
/**
 * Attributes Handler
 *
 * @since 2.0.0
 * @todo move default data to schemas. peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Attributes' ) ) {

    class Attributes extends Scaffold {

      /**
       * Constructor
       *
       */
      public function __construct() {

        // Standard attribute types.
        $_attribute_types = array(
          'input' => __( 'Short Text', ud_get_wp_property('domain') ),
          'textarea' => __( 'Textarea', ud_get_wp_property('domain') ),
          'checkbox' => __( 'Checkbox', ud_get_wp_property('domain') ),
          'datetime' => __( 'Date and Time', ud_get_wp_property('domain') ),
          'currency' => __( 'Currency', ud_get_wp_property('domain') ),
          'number' => __( 'Number', ud_get_wp_property('domain') )
        );

        // New attribut type that uses [wpp_categorical] taxonomy.
        if( WPP_FEATURE_FLAG_WPP_CATEGORICAL ) {
          $_attribute_types[ 'categorical-term' ] = __( 'Common Terms', ud_get_wp_property( 'domain' ) );
        }

        // Legacy attribute types we no longer use.
        if( WP_PROPERTY_LEGACY_ATTRIBUTE_INPUT_TYPES ) {

          $_attribute_types = array_merge( $_attribute_types, array(
            'wysiwyg' => __( 'Text Editor', ud_get_wp_property('domain') ),
            'dropdown' => __( 'Dropdown Selection', ud_get_wp_property('domain') ),
            'select_advanced' => __( 'Advanced Dropdown', ud_get_wp_property('domain') ),
            'multi_checkbox' => __( 'Multi-Checkbox', ud_get_wp_property('domain') ),
            'radio' => __( 'Radio', ud_get_wp_property('domain') ),
            'url' => __( 'URL', ud_get_wp_property('domain') ),
            'oembed' => __( 'Oembed', ud_get_wp_property('domain') ),
            'date' => __( 'Date picker', ud_get_wp_property('domain') ),
            'time' => __( 'Time picker', ud_get_wp_property('domain') ),
            'color' => __( 'Color picker', ud_get_wp_property('domain') ),
            'image_advanced' => __( 'Image upload', ud_get_wp_property('domain') ),
            'file_advanced' => __( 'Files upload', ud_get_wp_property('domain') ),
            'file_input' => __( 'File URL', ud_get_wp_property('domain') ),
          ));

        }

        /**
         * Add Available Attribute Types ( Meta Box Fields )
         */
        ud_get_wp_property()->set( 'attributes.types', $_attribute_types );

        /**
         * Set schema for searchable attributes types.
         */
        ud_get_wp_property()->set('attributes.searchable', array(
          'input' => array(
            'input'
          ),
          'textarea' => array(
            'input'
          ),
          'wysiwyg' => array(
            'input'
          ),
          'dropdown' => array(
            'dropdown',
            'multicheckbox',
          ),
          'select_advanced' => array(
            'dropdown',
            'multicheckbox',
          ),
          'checkbox' => array(
            'checkbox',
          ),
          'multi_checkbox' => array(
            'input',
            'dropdown',
            'multicheckbox',
          ),
          'radio' => array(
            'dropdown',
            'multicheckbox',
          ),
          'number' => array(
            'input',
            'dropdown',
            'range_input',
            'range_dropdown',
            'advanced_range_dropdown',
          ),
          'currency' => array(
            'input',
            'dropdown',
            'range_input',
            'range_dropdown',
            'advanced_range_dropdown',
          ),
          'url' => array(
            'input'
          ),
          'date' => array(
            'range_date'
          ),
        ));

        /**
         * Set supported type for default value.
         */
        ud_get_wp_property()->set('attributes.default', array(
          'input'           => 'text',
          'number'          => 'text',
          'currency'        => 'text',
          'url'             => 'text',
          'oembed'          => 'text',
          'textarea'        => 'textarea',
          'wysiwyg'         => 'textarea',
        ));

        /**
         * Set schema for multiple attributes types.
         */
        ud_get_wp_property()->set('attributes.multiple', array(
          'categorical-term',
          'multi_checkbox',
          'image_advanced',
          'file_advanced',
          'image_upload',
        ) );

        /** Fix numeric/currency logic */
        $this->fix_numeric_and_currency();

        /**
         * Prepare attribute's value to display
         */
        add_filter( 'wpp::attribute::display', array( $this, 'prepare_to_display' ), 99, 2 );

      }

      /**
       * Prepare attribute's value to be displayed on front end.
       * @param $value
       * @param $attribute
       * @return string
       */
      public function prepare_to_display( $value, $attribute ) {
        /**
         * Combine multiple values to string
         * if attribute is multiple.
         */
        $attribute = $this::get_attribute_data( $attribute );

        if( $attribute[ 'multiple' ] && is_array( $value ) ) {
          $value = implode( ', ', $value );
        }

        return $value;
      }

      /**
       * Adds numeric/currency compatibility with old WP-Property versions.
       */
      public function fix_numeric_and_currency(){
        global $wp_properties;
        $numeric_attributes = ud_get_wp_property( 'numeric_attributes', array() );
        $currency_attributes = ud_get_wp_property( 'currency_attributes', array() );

        foreach( ud_get_wp_property('admin_attr_fields', array()) as $key => $type ) {
          switch( $type ){
            case 'number':
              array_push( $numeric_attributes, $key );
              break;
            case 'currency':
              array_push( $numeric_attributes, $key );
              array_push( $currency_attributes, $key );
              break;
          }
        }
        $wp_properties[ 'numeric_attributes' ] = array_unique( $numeric_attributes );
        ud_get_wp_property()->set( 'numeric_attributes', $wp_properties[ 'numeric_attributes' ] );
        $wp_properties[ 'currency_attributes' ] = array_unique( $currency_attributes );
        ud_get_wp_property()->set( 'currency_attributes', $wp_properties[ 'currency_attributes' ] );
      }

      /**
       * Returns attribute information.
       *
       * Checks $wp_properties and returns a concise array of array-specific settings and attributes
       *
       * @todo Consider putting this into settings action, or somewhere, so it its only ran once, or adding caching
       * @version 1.17.3
       * @param bool $attribute
       * @param array $args
       * @return mixed
       */
      static public function get_attribute_data( $attribute = false, $args = array() ) {
        $wp_properties = ud_get_wp_property()->get();

        if( !$attribute ) {
          return;
        }

        $args = wp_parse_args($args, array(
          'use_cache' => true
        ));

        if( $args[ 'use_cache' ] && wp_cache_get( $attribute, 'wpp_attribute_data' ) ) {
          return wp_cache_get( $attribute, 'wpp_attribute_data' );
        }

        $post_table_keys = array(
          'post_author',
          'post_date',
          'post_date_gmt',
          'post_content',
          'post_title',
          'post_excerpt',
          'post_status',
          'comment_status',
          'ping_status',
          'post_password',
          'post_name',
          'to_ping',
          'pinged',
          'post_modified',
          'post_modified_gmt',
          'post_content_filtered',
          'post_parent',
          'guid',
          'menu_order',
          'post_type',
          'post_mime_type',
          'comment_count' );

        $ui_class = array( $attribute );

        if( in_array( $attribute, $post_table_keys ) ) {
          $return[ 'storage_type' ] = 'post_table';
        }

        $return[ 'slug' ] = $attribute;

        if( $attribute == 'property_type' ) {
          $return[ 'storage_type' ] = 'meta_key';
          $return[ 'label' ] = sprintf( __( '%s Type', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() );
        }

        if( isset( $wp_properties[ 'property_stats' ][ $attribute ] ) ) {
          $return[ 'is_stat' ]      = 'true';
          $return[ 'storage_type' ] = 'meta_key';
          $return[ 'label' ]        = $wp_properties[ 'property_stats' ][ $attribute ];
        }

        if( isset( $wp_properties[ 'property_meta' ][ $attribute ] ) ) {
          $return[ 'is_meta' ]         = 'true';
          $return[ 'storage_type' ]    = 'meta_key';
          $return[ 'label' ]           = $wp_properties[ 'property_meta' ][ $attribute ];
          $return[ 'input_type' ]      = 'textarea';
          $return[ 'data_input_type' ] = 'textarea';
        }

        if( isset( $wp_properties[ 'taxonomies' ][ $attribute ] ) ) {
          $return[ 'label' ]        = $wp_properties[ 'taxonomies' ][ $attribute ]['label'];
          $return[ 'readonly' ]        = $wp_properties[ 'taxonomies' ][ $attribute ]['readonly'];
          $return[ 'storage_type' ] = 'taxonomy';
          $categories = get_terms( $attribute, array('hide_empty' => 0, 'fields' => 'names') );
          if( is_wp_error( $categories ) ) {
            $categories = array();
          }
          $return['predefined_values'] = implode(', ', $categories);
        }

        if( isset( $wp_properties[ 'searchable_attr_fields' ][ $attribute ] ) ) {
          $return[ 'input_type' ] = $wp_properties[ 'searchable_attr_fields' ][ $attribute ];
          $ui_class[ ]            = $return[ 'input_type' ];
        }

        if( isset( $wp_properties[ 'admin_attr_fields' ][ $attribute ] ) ) {
          $return[ 'data_input_type' ] = $wp_properties[ 'admin_attr_fields' ][ $attribute ];
          $ui_class[ ]                 = $return[ 'data_input_type' ];
        }

        if( WPP_FEATURE_FLAG_WPP_CATEGORICAL ) {

          if( isset( $return[ 'data_input_type' ] ) && $return[ 'data_input_type' ] === 'categorical-term' ) {
            $return[ 'storage_type' ] = 'taxonomy';
            $return[ 'is_stat' ] = 'false';

            $_term = get_term_by( 'slug', $attribute, 'wpp_categorical', 'ARRAY_A' );

            $categories = get_terms( array(
              'child_of' => $_term['term_id'],
              'taxonomy' => 'wpp_categorical',
              'fields' => 'names'
            ));

            // @hack. To get it to set properly a few lines below.
            $wp_properties[ 'predefined_values' ][ $attribute ] =  implode( ', ', $categories );
          }

        }

        if( isset( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && $wp_properties[ 'configuration' ][ 'address_attribute' ] == $attribute ) {
          $return[ 'is_address_attribute' ] = 'true';
          $ui_class[ ]                      = 'address_attribute';
        }

        if( isset( $wp_properties[ 'property_inheritance' ] ) && is_array( $wp_properties[ 'property_inheritance' ] ) ) {
          foreach( $wp_properties[ 'property_inheritance' ] as $property_type => $type_data ) {
            if( in_array( $attribute, $type_data ) ) {
              $return[ 'inheritance' ][ ] = $property_type;
            }
          }
        }

        if( isset( $wp_properties[ 'predefined_values' ][ $attribute ] ) ) {
          $return[ 'predefined_values' ] = $wp_properties[ 'predefined_values' ][ $attribute ];
        }

        // Get group and group_label
        if( isset( $wp_properties[ 'property_stats_groups' ][ $attribute ] ) ) {
          $return[ 'group' ] = $wp_properties[ 'property_stats_groups' ][ $attribute ];

          if( isset( $return[ 'group' ] ) && $return[ 'group' ] ) {
            $return[ 'group_label' ] = $wp_properties[ 'property_groups' ][ $return[ 'group' ] ]['name'];
          }

        }

        if( isset( $wp_properties[ 'predefined_search_values' ][ $attribute ] ) ) {
          $return[ 'predefined_search_values' ] = $wp_properties[ 'predefined_search_values' ][ $attribute ];
        }

        if( isset( $wp_properties[ 'sortable_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'sortable_attributes' ] ) ) {
          $return[ 'sortable' ] = true;
          $ui_class[ ]          = 'sortable';
        }

        if( isset( $wp_properties[ 'hidden_frontend_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'hidden_frontend_attributes' ] ) ) {
          $return[ 'hidden_frontend_attribute' ] = true;
          $ui_class[ ]                           = 'fe_hidden';
        }

        if( isset( $wp_properties[ 'currency_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'currency_attributes' ] ) ) {
          $return[ 'currency' ] = true;
          $ui_class[ ]          = 'currency';
        }

        if( isset( $wp_properties[ 'numeric_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'numeric_attributes' ] ) ) {
          $return[ 'numeric' ] = true;
          $ui_class[ ]         = 'numeric';
        }

        if( isset( $wp_properties[ 'searchable_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'searchable_attributes' ] ) ) {
          $return[ 'searchable' ] = true;
          $ui_class[ ]            = 'searchable';
        }

        if( empty( $return[ 'title' ] ) ) {
          $return[ 'title' ] = \WPP_F::de_slug( $return[ 'slug' ] );
        }

        $return[ 'ui_class' ] = implode( ' wpp_', $ui_class );

        $multiple_attributes = ud_get_wp_property( 'attributes.multiple', array() );
        $return[ 'multiple' ] = false;
        if( isset( $return[ 'data_input_type' ] ) && in_array( $return[ 'data_input_type' ], (array)$multiple_attributes ) ) {
          $return[ 'multiple' ] = true;
        }

        $return = apply_filters( 'wpp_attribute_data', $return );

        wp_cache_add( $attribute, $return, 'wpp_attribute_data' );

        return $return;

      }

      /**
       * Returns valid attribute type.
       *
       * @see UsabilityDynamics\WPP\Attributes::get_valid_attribute_type()
       * @param bool $type //ud_get_wp_property()->set( 'attributes.types'
       * @return mixed
       */
      /** Maybe Convert input types to valid ones and prepare options. */
      public static function get_valid_attribute_type($type){
        switch($type) {
          case 'input':
            $type = 'text';
            break;
          case 'range_input':
          case 'range_dropdown':
          case 'advanced_range_dropdown':
          case 'dropdown':
            $type = 'select_advanced';
            break;
          case 'multi_checkbox':
            $type = 'checkbox_list';
            break;
          case 'image_upload':
            $type = 'image_advanced';
            break;
          case 'oembed':
            $type = 'OEmbed';
            break;
        }
        return $type;
      }

      /**
       * Check if attribute supports multiple values.
       *
       * @param $attribute
       * @return bool
       */
      public static function is_attribute_multi($attribute){
        $attribute_data = self::get_attribute_data($attribute);
        $multiple_attributes = ud_get_wp_property( 'attributes.multiple', array() );
        if( isset( $attribute_data[ 'data_input_type' ] ) && in_array( $attribute_data[ 'data_input_type' ], $multiple_attributes ) ) {
          return true;
        }
        return false;
      }

    }

  }

}
