<?php
/**
 * Meta Box UI
 *
 * @since 2.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Meta_Box' ) ) {

    class Meta_Box {

      /**
       * Constructor
       * Sets default data.
       *
       */
      public function __construct( $args = false ) {
        /* Be sure all required files are loaded. */
        add_action( 'admin_init', array( $this, 'load_files' ), 1 );
        /* Register all RWMB meta boxes */
        add_action( 'rwmb_meta_boxes', array( $this, 'register_meta_boxes' ) );
        //** Add metaboxes hook */
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1 );
      }

      /**
       * Register metaboxes.
       *
       * @global type $post
       * @global type $wpdb
       */
      function add_meta_boxes() {
        global $post, $wpdb;
        /**
         * Add metabox for child properties
         */
        if( isset( $post ) && $post->post_type == 'property' && $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = '{$post->ID}' AND post_status = 'publish' " ) ) {
          add_meta_box( 'wpp_property_children', sprintf( __( 'Child %s', ud_get_wp_property('domain') ), \WPP_F::property_label( 'plural' ) ), array( $this, 'render_child_properties_meta_box' ), 'property', 'advanced', 'high' );
        }

        add_meta_box( 'wpp_property_template', __( 'Template', ud_get_wp_property('domain') ), array( $this, 'render_template_meta_box' ), 'property', 'side', 'default' );
      }

      /**
       * May be loads all required RWMB Meta Box files
       */
      public function load_files() {
        // Stop here if Meta Box class doesn't exist
        if( !class_exists( '\RW_Meta_Box' ) ) {
          return;
        }

        // Init \RW_Meta_Box defines if needed
        if ( !defined( 'RWMB_VER' ) ) {
          $reflector = new \ReflectionClass( '\RW_Meta_Box' );
          $file = dirname( dirname( $reflector->getFileName() ) ) . '/meta-box.php';
          if( !file_exists( $file ) ) {
            return;
          }
          include_once( $file );
        }
      }

      /**
       * Loaded if this is a property page, and child properties exist.
       *
       * @version 1.26.0
       * @author Andy Potanin <andy.potanin@twincitiestech.com>
       * @package WP-Property
       */
      public function render_child_properties_meta_box( $post ) {

        $list_table = new Children_List_Table( array(
          'name' => 'wpp_edit_property_page',
          'per_page' => 10,
        ) );

        $list_table->prepare_items();
        $list_table->display();

      }

      /**
       * Adds Template Manager for Property
       *
       * @since 2.1.0
       * @author peshkov@UD
       * @param $post
       */
      public function render_template_meta_box( $post ) {

        $config = ud_get_wp_property( 'configuration.single_property', array() );
        $redeclare = get_post_meta( $post->ID, '_wpp_redeclare_template', true );

        if( !empty( $redeclare ) && $redeclare == 'true' ) {
          $template = get_post_meta( $post->ID, '_wpp_template', true );
          $page_template = get_post_meta( $post->ID, '_wpp_page_template', true );
        }

        if( empty( $template ) ) {
          $template = !empty( $config[ 'template' ] ) ? $config[ 'template' ] : 'property';
        }

        if( empty( $page_template ) ) {
          $page_template = !empty( $config[ 'page_template' ] ) ? $config[ 'page_template' ] : 'default';
        }

        $file = ud_get_wp_property()->path( 'static/views/admin/metabox-template.php', 'dir' );
        if( file_exists( $file ) ) {
          include( $file );
        }

      }

      /**
       * Register all meta boxes here.
       *
       */
      public function register_meta_boxes( $meta_boxes ) {
        $_meta_boxes = array();

        /* May be determine property_type to know which attributes should be hidden and which ones just readable. */
        $post = new \WP_Post( new \stdClass );

        $post_id = isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ? $_REQUEST['post'] : false;
        if( !$post_id && !empty( $_REQUEST['post_ID'] ) && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'editpost' ) {
          $post_id = $_REQUEST['post_ID'];
        }
        if( $post_id ) {
          $p = get_property( $post_id, array(
            'get_children'          => 'true',
            'return_object'         => 'true',
            'load_gallery'          => 'false',
            'load_thumbnail'        => 'false',
            'load_parent'           => 'true',
            'cache'                 => 'false'
          ) );
          if( !empty($p) ) {
            $post = $p;
          }
        }

        /* Register 'General Information' metabox for Edit Property page */
        $meta_box = $this->get_property_meta_box( array(
          'name' => __( 'General', ud_get_wp_property()->domain ),
        ), $post );

        $groups = ud_get_wp_property( 'property_groups', array() );
        $property_stats_groups = ud_get_wp_property( 'property_stats_groups', array() );

        if( $meta_box ) {
          $_meta_boxes[] = $meta_box;
        }

        /* Register Meta Box for every Attributes Group separately */
        if ( !empty( $groups) && !empty( $property_stats_groups ) ) {
          foreach ( $groups as $slug => $group ) {
            $meta_box = $this->get_property_meta_box( array_filter( array_merge( $group, array( 'id' => $slug ) ) ), $post );
            if( $meta_box ) {
              $_meta_boxes[] = $meta_box;
            }
          }
        }

        /**
         * Allow to customize our meta boxes via external add-ons ( modules, or whatever else ).
         */
        $_meta_boxes = apply_filters( 'wpp::meta_boxes', $_meta_boxes );

        if( !is_array( $_meta_boxes ) ) {
          return $meta_boxes;
        }

        /** Get rid of meta box without fields data. */
        foreach( $_meta_boxes as $k => $meta_box ) {
          if( empty( $meta_box[ 'fields' ] ) ) {
            unset( $_meta_boxes[ $k ] );
          }
        }

        /**
         *  Probably convert Meta Boxes to single one with tabs
         */
        $_meta_boxes = $this->maybe_convert_to_tabs( $_meta_boxes );
        if( is_array( $meta_boxes ) ) {
          $meta_boxes = $meta_boxes + $_meta_boxes;
        }

        return $meta_boxes;
      }

      /**
       * @param $meta_boxes
       * @return array
       */
      public function maybe_convert_to_tabs( $meta_boxes ) {

        if( ud_get_wp_property( 'configuration.disable_meta_box_tabs', false ) ) {
          return $meta_boxes;
        }

        if( count( $meta_boxes ) <= 1 ) {
          return $meta_boxes;
        }

        $meta_box = array(
          'id' => '_general',
          'title' => sprintf( __( '%s Details', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
          'pages' => array( 'property' ),
          'context' => 'advanced',
          'priority' => 'low',
          'tab_style' => 'left',
          'tabs' => array(),
          'fields' => array(),
        );

        $icons = apply_filters( 'wpp::meta_boxes::icons', array(
          '_general' => 'dashicons-admin-home',
        ) );

        foreach( $meta_boxes as $b ) {

          if( !isset( $meta_box['tabs'][ $b['id'] ] ) ) {
            $meta_box['tabs'][ $b['id'] ] = array(
              'label' => $b['title'],
              'icon' => array_key_exists($b['id'], $icons) ? $icons[$b['id']] : 'dashicons-admin-page',
            );
          }

          if( !empty( $b['fields'] ) && is_array( $b['fields'] ) ) {
            foreach( $b['fields'] as $field ) {
              array_push( $meta_box[ 'fields' ], array_merge( $field, array(
                'tab' => $b['id'],
              ) ) );
            }
          }

        }

        return array($meta_box);
      }

      /**
       * Prepare Property Meta Box based on Attributes' Groups for registration.
       *
       * @since 2.0
       * @author peshkov@UD
       */
      public function get_property_meta_box( $group = array(), $post = false ) {

        $group = wp_parse_args( $group, array(
          'id' => false,
          'name' => __( 'NO NAME', ud_get_wp_property()->domain ),
        ) );

        $fields = array();

        /**
         * Get all data we need to operate with.
         */
        $attributes = ud_get_wp_property( 'property_stats', array() );
        $geo_type_attributes = ud_get_wp_property( 'geo_type_attributes', array() );
        $hidden_attributes = ud_get_wp_property( 'hidden_attributes', array() );
        $inherited_attributes = ud_get_wp_property( 'property_inheritance', array() );

        $property_stats_groups = ud_get_wp_property( 'property_stats_groups', array() );

        $predefined_values = ud_get_wp_property( 'predefined_values', array() );
        $descriptions = ud_get_wp_property( 'descriptions', array() );
        $input_types = ud_get_wp_property( 'admin_attr_fields' );

        //** Detect attributes that were taken from a range of child properties. */
        $aggregated_attributes = !empty( $post->system[ 'upwards_inherited_attributes' ] ) ? (array)$post->system[ 'upwards_inherited_attributes' ] : array();

        /**
         * If group ID is not defined, it means that we're registering main Meta Box
         * So, here, we're adding custom fields for management!
         */
        if( $group['id'] == false ) {
          /* May be add Property Parent field - 'Falls Under' */
          $field = $this->get_parent_property_field( $post );
          if( $field ) {
            $fields[] = $field;
          }
          /* May be add Property Type field. */
          if( !array_key_exists( 'property_type', $attributes ) ) {
            $field = $this->get_property_type_field( $post );
            if( $field ) {
              $fields[] = $field;
            }
          }
          /* May be add Meta fields */
          foreach( ud_get_wp_property()->get( 'property_meta', array() ) as $slug => $label ) {
            $field = apply_filters( 'wpp::rwmb_meta_box::field', array_filter( array(
              'id' => $slug,
              'name' => $label,
              'type' => 'textarea',
              'desc' => __( 'Meta description.', ud_get_wp_property()->domain ),
            ) ), $slug, $post );
            if( $field ) {
              $fields[] = $field;
            }
          }
        }

        /**
         * Loop through all available attributes and determine if any of them must be added to current meta box.
         */
        foreach ( $attributes as $slug => $label ) {

          /**
           * Determine if we should add attribute's field in this meta box
           */

          //** Show ( or not ) attribute field on Edit property page for current Property. */
          if( !apply_filters( 'wpp::metabox::attribute::show', true, $slug, $post->ID ) ) {
            continue;
          }

          //* Determine if attribute is assigned to group. */
          if ( !empty( $property_stats_groups[ $slug ] ) && $property_stats_groups[ $slug ] != $group[ 'id' ] ) {
            continue;
          }

          //** Do not show attribute in group's meta box if it's not assigned to groups at all */
          if( empty( $property_stats_groups[ $slug ] ) && !empty( $group[ 'id' ] ) ) {
            continue;
          }

          //* Ignore Hidden Attributes */
          if (
            !empty( $post->property_type )
            && !empty( $hidden_attributes[ $post->property_type ] )
            && in_array( $slug, (array)$hidden_attributes[ $post->property_type ] )
          ) {
            continue;
          }

          /**
           * Looks like we have to add attribute's field to the current meta box.
           * So, setup data for it now.
           */

          //* HACK. If property_type is set as attribute, we register it here. */
          if( $slug == 'property_type' ) {
            $field = $this->get_property_type_field( $post );
            if( $field ) {
              $fields[] = $field;
            }
            continue;
          }

          $attribute = Attributes::get_attribute_data( $slug );

          $description = array();
          $description[ ] = ( isset( $attribute[ 'numeric' ] ) || isset( $attribute[ 'currency' ] ) ? __( 'Numbers only.', ud_get_wp_property()->domain ) : '' );
          $description[ ] = ( !empty( $descriptions[ $slug ] ) ? $descriptions[ $slug ] : '' );

          /**
           * PREPARE INPUT TYPE
           * May be convert input_type to valid type.
           */
          $input_type = !empty( $input_types[ $slug ] ) ? $input_types[ $slug ] : 'text';

          //* Geo Attributes */
          if( in_array( $slug, $geo_type_attributes ) ) {
            $input_type = 'wpp_readonly';
            $description[] = __( 'The value is being generated automatically on Google Address Validation.', ud_get_wp_property()->domain );
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'input' ) ) ) {
            $input_type = 'text';
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'dropdown' ) ) ) {
            $input_type = 'select';
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'checkbox' ) ) ) {
            $input_type = 'wpp_checkbox';
          }

          //* Legacy compatibility */
          if( in_array( $input_type, array( 'multi_checkbox' ) ) ) {
            $input_type = 'checkbox_list';
          }

          //* Fix currency */
          if( $input_type == 'currency' ) {
            $input_type = 'text'; // HTML5 does not allow to use float, so we have to use default 'text' here
            $description[] =  __( 'Currency.', ud_get_wp_property('domain') );
          }
          if( $input_type == 'number' ) {
            $input_type = 'text'; // HTML5 does not allow to use float, so we have to use default 'text' here
          }

          //** Determine if current attribute is used by Google Address Validator. */
          if( ud_get_wp_property( 'configuration.address_attribute' ) == $slug ) {
            $input_type = 'wpp_address';

            // Too obvious, I believe. -potanin@UD
            // $description[] = __( 'The value is being used by Google Address Validator to determine and prepare address to valid format. However you can set coordinates manually.', ud_get_wp_property()->domain );
          }

          //* Is current attribute inherited from parent? If so, set it as readonly!. */
          if(
            isset( $post->post_parent ) &&
            $post->post_parent > 0 &&
            isset( $post->property_type ) &&
            !empty( $inherited_attributes[ $post->property_type ] ) &&
            in_array( $slug, $inherited_attributes[ $post->property_type ] )
          ) {
            $input_type = 'wpp_inherited';
            $description[] = sprintf( __( 'The value is inherited from Parent %s.', ud_get_wp_property()->domain ), \WPP_F::property_label() );
          }

          //** Is current attribute's value aggregated from child properties? If so, set it as readonly! */
          if( !empty( $aggregated_attributes ) && in_array( $slug, $aggregated_attributes ) ) {
            $input_type = 'wpp_aggregated';
            $description[] = sprintf( __( 'The value is aggregated from Child %s.', ud_get_wp_property()->domain ), \WPP_F::property_label( 'plural' ) );
          }

          //** Determine if current attribute is used by Google Address Validator. */
          if( ud_get_wp_property( 'configuration.address_attribute' ) == $slug ) {
            if( $input_type == 'wpp_inherited' ) {
              $input_type = 'wpp_inherited_address';
            } else {
              $input_type = 'wpp_address';
              //$description[] = __( 'The value is being used by Google Address Validator to determine and prepare address to valid format. However you can set coordinates manually.', ud_get_wp_property()->domain );
            }
          }

          /**
           * Check for pre-defined values
           */
          $options = array();
          if( $input_type == 'select' ) {
            $options[''] = __( 'Not Selected', ud_get_wp_property()->domain );
          }
          if ( !empty( $predefined_values[ $slug ] ) && is_string( $predefined_values[ $slug ] ) ) {
            $_options = explode( ',', trim( $predefined_values[ $slug ] ) );
            foreach( $_options as $option ) {
              $option = trim( preg_replace( "/\r|\n/", "", $option ) );
              $options[ esc_attr( $option ) ] = apply_filters( 'wpp_stat_filter_' . $slug, $option );
            }
          }

          /**
           * Well, init field now.
           */
          $fields[] = apply_filters( 'wpp::rwmb_meta_box::field', array_filter( array(
            'id' => $slug,
            'name' => $label,
            'type' => $input_type,
            'desc' => implode( ' ', (array) $description ),
            'options' => $options,
          ) ), $slug, $post );

        }

        $meta_box = apply_filters( 'wpp::rwmb_meta_box', array(
          'id'       => !empty( $group['id'] ) ? $group['id'] : '_general',
          'title'    => $group['name'],
          'pages'    => array( 'property' ),
          'context'  => 'normal',
          'priority' => 'high',
          'fields'   => $fields,
        ), $group, $post );

        return $meta_box;
      }

      /**
       *
       */
      public function get_parent_property_field( $post ) {

        $field = array(
          'name' => __('Falls Under', ud_get_wp_property()->domain),
          'id' => 'parent_id',
          'type' => 'wpp_parent',
          'options' => admin_url( 'admin-ajax.php?action=wpp_autocomplete_property_parent' ),
        );

        return $field;
      }

      /**
       * Return RWMB Field for Property Type
       *
       */
      public function get_property_type_field( $post ) {

        $types = ud_get_wp_property( 'property_types', array() );

        if( empty( $types ) ) {
          return false;
        }

        if( count( $types ) > 1 ) {
          $types = array_merge( array( '' => __( 'No Selected', ud_get_wp_property()->domain ) ), $types );
          $field = array(
            'id' => 'property_type',
            'name' => sprintf( __( '%s Type', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
            // 'desc' => sprintf( __( '%s Attributes are related to Property Type. They can be aggregated, inherited or hidden after updating the current type.', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
            'type' => 'select',
            'options' => $types,
          );
        } else {
          $field = array(
            'id' => 'property_type',
            'type' => 'hidden',
            'std' => key($types),
          );
        }

        return $field;
      }

    }

  }

}
