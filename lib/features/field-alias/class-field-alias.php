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
          $list = false;

          $_targets = explode( ',', $target );
          foreach( $_targets as $_target ) {
            $_target = trim( $_target );

            $_alias_value = $this->get_alias_value( $_target, $property[ 'ID' ] );
            if( is_array( $_alias_value ) ) {
              $list = true;
            }

            // Alias value found.
            if( $_alias_value ) {
              if( $list ) {
                $alias_values = array_merge( $alias_values, $_alias_value );
              } else {
                $alias_values[] = $_alias_value;
              }
            }

          }

          $property[ $_defined_field ] = $list && count( $alias_values ) > 1 ? $alias_values : implode( ', ', $alias_values );
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
       * @param int $post_id
       * @return mixed|void
       */
      public function get_alias_value( $target = false, $post_id ) {
        global $wpdb;

        $value = null;

        // STEP 1. Figure out to which instance belongs target: taxonomy or postmeta

        // We do not know the type of target
        // So try to figure it out.
        $type = null;
        $target = explode( '.', $target );

        // In an ideal world we would prefix all of our fields with tax_input/post_meta.
        // If we have prefix we know to which stuff the target belongs to, so:
        if( in_array( $target[0], array( 'tax_input', 'post_meta' ) ) ) {
          $type = $target[0];
          $target = array_shift( $target );
        }

        // If we have more than one value, it means it's hierarchic taxonomy
        // and we specify the parent terms
        else if ( count( $target ) > 1 ) {
          $type = 'tax_input';
        }

        // Try to figure out the type by
        // looking for the target in postmeta and taxonomy tables
        else {
          $meta_counts = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $wpdb->postmeta WHERE meta_key=%s AND post_id=%s;", $target[0], $post_id ) );
          $terms_counts = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $wpdb->term_taxonomy WHERE taxonomy=%s;", $target[0] ) );
          // What the hell to do here since we have the target in both tables?
          if( $meta_counts && $terms_counts ) {
            // Nothing.. Let's try to find value in both instances below.
          }
          // We are sure it's postmeta
          else if ( $meta_counts ) {
            $type = 'post_meta';
          }
          // We are sure it's a taxonomy!
          else if ( $terms_counts ) {
            $type = 'tax_input';
          }
        }

        // STEP 2. Get value(s)

        // Looke like target belongs to taxonomy
        if( $type == 'tax_input' ) {
          // Do not combine this condition with above one!
          // Or we break the logic for 'else'...
          if( WPP_F::verify_have_system_taxonomy( $target[0] ) ) {
            $terms = array();

            // Looks like we want to get child terms only
            if( !empty( $target[1] ) ) {
              $term = get_term_by( 'id', $target[1], $target[0] );
              if( !$term ) {
                $term = get_term_by( 'slug', $target[1], $target[0] );
              }
              if( $term ) {
                $term_ids = wp_get_object_terms( $post_id, $target[0], array( 'fields' => 'ids' ) );
                $term_query = new \WP_Term_Query( array(
                  'taxonomy' => $target[0],
                  'include'  => $term_ids,
                  'parent'   => $term->term_id,
                  'fields'   => 'id=>name'
                ) );
                if( !empty( $term_query->terms ) ) {
                  $terms = array_values( $term_query->terms );
                }
              }
            }

            else {
              $terms = wp_get_object_terms( $post_id, $target[0], array( 'fields' => 'names' ) );
            }

            if( !empty( $terms ) && !is_wp_error( $terms ) ) {
              $value = $terms;
            }
          }
        }

        // Looks like target belongs to postmeta
        else if( $type == 'post_meta' ) {
          $value = get_post_meta( $post_id, $target[0], true );
        }

        // Could not determine the type?
        // Try to find value in postmeta at first and then in taxonomy.
        else {

          $value = get_post_meta( $post_id, $target[0], true );
          if( !$value && WPP_F::verify_have_system_taxonomy( $target[0] ) ) {
            $terms = wp_get_object_terms( $post_id, $target[0], array( 'fields' => 'names' ) );
            if( !empty( $terms ) && !is_wp_error( $terms ) ) {
              $value = $terms;
            }
          }

        }

        return $value;

      }

    }

  }

}