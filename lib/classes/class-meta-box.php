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
            'get_children'          => 'false',
            'return_object'         => 'true',
            'load_gallery'          => 'false',
            'load_thumbnail'        => 'false',
            'load_parent'           => 'false'
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
       *
       *
       */
      public function get_property_meta_box( $group = array(), $post = false ) {

        $group = wp_parse_args( $group, array(
          'id' => false,
          'name' => __( 'NO NAME', ud_get_wp_property()->domain ),
        ) );

        $fields = array();

        $attributes = ud_get_wp_property( 'property_stats', array() );
        $disabled_attributes = ud_get_wp_property( 'geo_type_attributes', array() );
        $hidden_attributes = ud_get_wp_property( 'hidden_attributes', array() );

        $property_stats_groups = ud_get_wp_property( 'property_stats_groups', array() );

        $predefined_values = ud_get_wp_property( 'predefined_values', array() );
        $descriptions = ud_get_wp_property( 'descriptions', array() );
        $input_types = ud_get_wp_property( 'admin_attr_fields' );

        foreach ( $attributes as $slug => $label ) {

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

          $attribute = \WPP_F::get_attribute_data( $slug );

          $description = array();
          $description[ ] = ( isset( $attribute[ 'numeric' ] ) || isset( $attribute[ 'currency' ] ) ? __( 'Numbers only.', ud_get_wp_property()->domain ) : '' );
          $description[ ] = ( !empty( $descriptions[ $slug ] ) ? $descriptions[ $slug ] : '' );

          //* Check input type */
          $input_type = !empty( $input_types[ $slug ] ) ? $input_types[ $slug ] : 'text';
          //* May be convert input_type to valid name. */
          if( in_array( $input_type, array( 'input' ) ) ) $input_type = 'text';
          elseif( in_array( $input_type, array( 'dropdown' ) ) ) $input_type = 'select';

          //** Check for pre-defined values */
          $options = array();
          if ( !empty( $predefined_values[ $slug ] ) ) {
            $options = str_replace( array( ', ', ' ,' ), array( ',', ',' ), trim( $predefined_values[ $slug ] ) );
            $options = explode( ',', $options );
          }

          $field = array_filter( array(
            'id' => $slug,
            'name' => $label,
            'type' => $input_type,
            'desc' => implode( '', $description ),
            'options' => $options,
          ) );

          switch( $input_type ) {

            case 'select':
            case 'select_advanced':



              break;

          }

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


    }

  }

}
