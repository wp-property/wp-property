<?php
/**
 * Field Alias
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Field_Alias' ) ) {

    class Field_Alias {

      /**
       * Loads all stuff for WP_PROPERTY_FIELD_ALIAS
       */
      public function __construct(){
        //
        add_filter( 'wpp_get_property', array( $this, 'apply_property_alias' ), 50, 2 );
        //
        add_filter( 'wpp_get_properties_query', array( $this, 'apply_properties_query_alias' ), 50 );
        //
        // add_filter( 'get_post_metadata', array( $this, 'alias_get_post_metadata' ), 50, 4 );

        if( is_admin() ) {

          /**
           * Edit Property Page: Meta Box Fields
           */

          // We must use readonly field for our aliases
          add_filter( 'wpp::rwmb_meta_box::field', function( $field ) {
            $alias = $this->get_alias_map( $field[ 'id' ] );
            if( !empty( $alias ) ) {
              $field[ 'type' ] = 'wpp_alias';
            }
            return $field;
          } );

          /**
           * Developer Tab UI
           */

          // Add alias UI for Attributes
          add_action( "wpp::settings::developer::attributes::item_advanced_options", array( $this, "draw_alias_option" ) );
          add_filter( "wpp::settings::developer::attributes", function( $data ) {
            $attributes = ud_get_wp_property()->get('property_stats', array());
            $filtered_field_alias = array();
            foreach( $attributes as $slug => $_data ){
              $filtered_field_alias[$slug] = $this->get_alias_map( $slug ) ;
            }
            $data[ 'filtered_field_alias'] = $filtered_field_alias;
            return $data;
          }, 100 );

          // Add alias UI for Taxonomies ( Terms )
          /* Decided to not add aliases for Taxonomies to prevent a lot of issues.
          add_action( "wpp::settings::developer::terms::item_advanced_options", array( $this, "draw_alias_option" ) );
          add_filter( "wpp::settings::developer::terms", function( $data ) {
            if( empty( $data[ 'config' ]['taxonomies'] ) ) {
              return $data;
            }
            $filtered_field_alias = array();
            foreach( $data[ 'config' ]['taxonomies'] as $slug => $_data ){
              $filtered_field_alias[$slug] = $this->get_alias_map( $slug ) ;
            }
            $data[ 'filtered_field_alias'] = $filtered_field_alias;
            return $data;
          }, 100 );
          //*/

        }

      }

      /**
       * Renders Alias option for Attributes and Taxonomies
       * on Settings page (Developer Tab)
       */
      public function draw_alias_option() {
        include( ud_get_wp_property()->path( 'lib/features/field-alias/static/view/alias-option.php', 'dir' ) );
      }

      /**
       * Apply field alias to property object.
       *
       * - Alias will overwrite actual if alias exists, regardless of if actual exists.
       *
       * @param $property
       * @param $args
       * @return mixed
       */
      public function apply_property_alias( $property, $args ) {

        $_result = array();

        // add terms to object.
        // apply alias logic.
        foreach( (array) $this->get_alias_map() as $_defined_field => $target ) {

          $alias_values = array();

          $_targets = explode( ',', $target );
          foreach( $_targets as $_target ) {
            $_target = trim( $_target );

            $_alias_value = $this->get_alias_value( $_target, $property->ID );

            // Alias value found.
            if( $_alias_value ) {
              $alias_values[] = $_alias_value;
            }

          }

          $property[ $_defined_field ] = $alias_values;
          $_result[] = "Applied target [$target] alias to [$_defined_field] with values";

        }

        WPP_F::debug( 'apply_property_alias', array( 'id' => $property['ID']) );

        // die( '<pre>' . print_r( $_result, true ) . '</pre>' );
        //WPP_F::debug( 'apply_property_alias:detail',$_result );

        return $property;


      }

      /**
       * Apply field aliases to property query.
       *
       * @param $query
       * @return mixed
       */
      public function apply_properties_query_alias( $query ) {

        $_result = array();

        foreach( (array) $this->get_alias_map() as $_alias => $_target ) {

          if( isset( $query[ $_alias ] ) ) {
            $query[ $_target ] = $query[ $_alias ];
            $_result[] = "Applied target [$_target] alias to [$_alias].";
            unset( $query[ $_alias ] );
          }

        }

        //WPP_F::debug( 'apply_properties_query_alias', array( 'query' => $query, 'result' => $_result ) );

        return $query;

      }

      /**
       * Direct Meta Override
       *
       * @param $false
       * @param $object_id
       * @param $meta_key
       * @param $single
       * @return mixed
       */
      public function alias_get_post_metadata( $false, $object_id, $meta_key, $single ) {

        if( $meta_key === 'short_address' ) {
          return get_post_meta( $object_id, 'formatted_address_simple', $single );
        }

        if( $meta_key === 'address' ) {
          return get_post_meta( $object_id, 'formatted_address', $single );
        }

        return $false;

      }


      /**
       * Returns alias key for passed attribute
       *
       *
       * @param bool $field
       * @return mixed|void
       */
      public function get_alias_map( $field = false ) {
        global $wp_properties;

        $field_alias = apply_filters( 'wpp:field_alias', isset( $wp_properties[ 'field_alias' ] ) ? array_filter( $wp_properties[ 'field_alias' ] ) : array() );

        if( isset( $field ) && $field ) {
          return isset( $field_alias[ $field ] ) ? $field_alias[ $field ] : null;
        }

        return (array) $field_alias;

      }


      /**
       * Returns Alias value
       *
       *
       * @param bool $target
       * @return mixed|void
       */
      public function get_alias_value( $target = false, $post_id ) {
        $value = null;

        // In an ideal world we would prefix all of our fields with tax_input/post_meta.
        // If we have prefix we know to which stuff the target belongs to, so:

        // Check taxonomy
        if( strpos( $target, 'tax_input.' ) === 0 ) {
          $_term_group_match = explode( '.', $target );
          if( taxonomy_exists( $_term_group_match[1] ) ) {
            $value = wp_get_object_terms( $post_id, $_term_group_match[1], array( 'fields' => 'names' ) );
          }
          return $value;
        }

        // Check post meta
        if( strpos( $target, 'post_meta.' ) === 0 ) {
          $_term_group_match = explode( '.', $target );
          $value = get_post_meta( $post_id, $_term_group_match[1], true );
          return $value;
        }

        if( !$value && taxonomy_exists( $target ) ) {
          $value = wp_get_object_terms( $post_id, $target, array( 'fields' => 'names' ) );
        }

        // try meta, defined taxonomy
        if( !$value ) {
          $value = get_post_meta( $post_id, $target, true );
        }

        return $value;

      }

    }

  }

}
