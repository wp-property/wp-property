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
       * Register all meta boxes here.
       *
       */
      public function register_meta_boxes( $meta_boxes ) {

        /* May be determine property_type to know which attributes should be hidden and which ones just readable. */
        $post = new \WP_Post( new \stdClass );
        if( isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ) {
          $p = get_property( $_REQUEST['post'], array(
            'get_children'          => 'true',
            'return_object'         => 'true',
            'load_gallery'          => 'false',
            'load_thumbnail'        => 'false',
            'load_parent'           => 'true'
          ) );
          if( !empty($p) ) {
            $post = $p;
          }
        }

        /* Register 'General Information' metabox for Edit Property page */
        $meta_box = $this->get_property_meta_box( array(
          'name' => __( 'General Information', ud_get_wp_property()->domain ),
        ), $post );
        if( $meta_box ) {
          $meta_boxes[] = $meta_box;
        }

        $groups = ud_get_wp_property( 'property_groups', array() );
        $property_stats_groups = ud_get_wp_property( 'property_stats_groups', array() );

        /* Register Meta Box for every Attributes Group separately */
        if ( !empty( $groups) && !empty( $property_stats_groups ) ) {
          foreach ( $groups as $slug => $group ) {
            /* There is no sense to add metabox if no one attribute assigned to group */
            if ( !in_array( $slug, $property_stats_groups ) ) {
              continue;
            }
            $meta_box = $this->get_property_meta_box( array_filter( array_merge( $group, array( 'id' => $slug ) ) ), $post );
            if( $meta_box ) {
              $meta_boxes[] = $meta_box;
            }
          }
        }

        return $meta_boxes;
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

          $attribute = \WPP_F::get_attribute_data( $slug );

          $description = array();
          $description[ ] = ( isset( $attribute[ 'numeric' ] ) || isset( $attribute[ 'currency' ] ) ? __( 'Numbers only.', ud_get_wp_property()->domain ) : '' );
          $description[ ] = ( !empty( $descriptions[ $slug ] ) ? $descriptions[ $slug ] : '' );

          /**
           * Prepare Input Type
           */
          $input_type = !empty( $input_types[ $slug ] ) ? $input_types[ $slug ] : 'text';
          //* May be convert input_type to valid type. */
          if( in_array( $slug, $geo_type_attributes ) ) {
            $input_type = 'custom_readonly';
            $description[] = __( 'The value is being generated automatically on Google Address Validation.', ud_get_wp_property()->domain );
          }
          if( in_array( $input_type, array( 'input' ) ) ) {
            $input_type = 'text';
          }
          if( in_array( $input_type, array( 'dropdown' ) ) ) {
            $input_type = 'select';
          }
          if( in_array( $input_type, array( 'checkbox' ) ) ) {
            $input_type = 'custom_checkbox';
          }
          //* Is current attribute inherited from parent? If so, set it as readonly!. */
          if(
            isset( $post->post_parent ) &&
            $post->post_parent > 0 &&
            isset( $post->property_type ) &&
            !empty( $inherited_attributes[ $post->property_type ] ) &&
            in_array( $slug, $inherited_attributes[ $post->property_type ] )
          ) {
            $input_type = 'custom_readonly';
            $description[] = sprintf( __( 'The value is inherited from Parent %s.', ud_get_wp_property()->domain ), \WPP_F::property_label() );
          }
          //** Is current attribute's value aggregated from child properties? If so, set it as readonly! */
          if( !empty( $aggregated_attributes ) && in_array( $slug, $aggregated_attributes ) ) {
            $input_type = 'custom_readonly';
            $description[] = sprintf( __( 'The value is aggregated from Child %s.', ud_get_wp_property()->domain ), \WPP_F::property_label( 'plural' ) );
          }

          /**
           * Check for pre-defined values
           */
          $options = array();
          if ( !empty( $predefined_values[ $slug ] ) && is_string( $predefined_values[ $slug ] ) ) {
            $_options = explode( ',', trim( $predefined_values[ $slug ] ) );
            if( $input_type == 'select' ) {
              $options[''] = __( 'Not Selected', ud_get_wp_property()->domain );
            }
            foreach( $_options as $option ) {
              $option = trim( preg_replace( "/\r|\n/", "", $option ) );
              $options[ esc_attr( $option ) ] = apply_filters( 'wpp_stat_filter_' . $slug, $option );
            }
          }

          /**
           * Well, init field now.
           */
          $field = array_filter( array(
            'id' => $slug,
            'name' => $label,
            'type' => $input_type,
            'desc' => implode( ' ', $description ),
            'options' => $options,
          ) );

          $fields[] = $field;

        }

        if( empty( $fields ) ) {
          return false;
        }

        $meta_box = apply_filters( 'wpp::rwmb_meta_box', array(
          'id'       => $group['id'],
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



        return false;
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
            'desc' => sprintf( __( '%s Attributes are related to Property Type. They can be aggregated, inherited or hidden after updating the current type.', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
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
