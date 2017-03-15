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

        add_filter( 'wpp_get_property', array( $this, 'apply_property_alias' ), 50, 2 );
        add_filter( 'wpp_get_properties_query', array( $this, 'apply_properties_query_alias' ), 50 );
        // add_filter( 'get_post_metadata', array( $this, 'alias_get_post_metadata' ), 50, 4 );

        if( is_admin() ) {
          //add_action( "wpp::settings_developer_terms::advanced_option", array( $this, "draw_alias_option" ) );
        }

      }

      /**
       * Renders Alias option on Settings -> Developer -> Terms
       * in advanced settings
       */
      public function draw_alias_option() {
        include( ud_get_wp_property()->path( 'lib/features/field-alias/static/view/term-alias-option.php', 'dir' ) );
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
        foreach( (array) $this->get_alias_map() as $_defined_field => $_target ) {

          $_alias_value = null;

          //UsabilityDynamics\WPP\Attributes::get_attribute_data( $_defined_field );

          // In an ideal world we would prefix all of our fields with tax_input/post_mea.
          if( strpos( $_target, 'tax_input.' ) === 0 ) {
            $_term_group_match = explode( '.', $_target );
            $_alias_value = wp_get_object_terms( $property['ID'], $_term_group_match[1], array( 'fields' => 'names' ) );
          }

          // try meta, defined taxonomy
          if( !$_alias_value ) {
            $_alias_value  = isset( $property[ $_target ] ) ? $property[ $_target ] : null;
          }

          // Support for dynamic taxonomies.
          if( !$_alias_value ) {
            WPP_F::verify_have_system_taxonomy( $_target );
            $_alias_value = wp_get_object_terms( $property['ID'], $_target, array( 'fields' => 'names' ) );
          }

          // Alias value found.
          if( $_alias_value ) {
            $property[ $_defined_field ] = $_alias_value;
            $_result[] = "Applied target [$_target] alias to [$_defined_field] with values";
          }

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
       * Get Field Aliases
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

    }

  }

}
