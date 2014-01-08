<?php
/**
 * Retrevie property attribute using shortcode.
 *
 *
 * @since 1.26.0
 *
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Attribute' ) ) {

    /**
     * Property Search Shortcode Class
     *
     */
    class Property_Attribute extends \UsabilityDynamics\WPP\Shortcode {

      /**
       * Initialize Shortcode
       * @param string $atts
       * @param string $content
       */
      function __construct( $atts = '', $content = '' ) {

      }

      /**
       * Render Shortcode
       *
       */
      static function shortcode_property_attribute( $atts = false ) {
        global $post, $property;

        $this_property = $property;

        if( empty( $this_property ) && $post->post_type == 'property' ) {
          $this_property = $post;
        }

        $this_property = (array) $this_property;

        if( !$atts ) {
          $atts = array();
        }

        $defaults = array(
          'property_id'      => $this_property[ 'ID' ],
          'attribute'        => '',
          'before'           => '',
          'after'            => '',
          'if_empty'         => '',
          'do_not_format'    => '',
          'make_terms_links' => 'false',
          'separator'        => ' ',
          'strip_tags'       => ''
        );

        $args = array_merge( $defaults, $atts );

        if( empty( $args[ 'attribute' ] ) ) {
          return false;
        }

        $attribute = $args[ 'attribute' ];

        if( $args[ 'property_id' ] != $this_property[ 'ID' ] ) {

          $this_property = \UsabilityDynamics\WPP\Utility::get_property( $args[ 'property_id' ] );

          if( $args[ 'do_not_format' ] != "true" ) {
            $this_property = prepare_property_for_display( $this_property );
          }

        } else {
          $this_property = $this_property;
        }

        if( is_taxonomy( $attribute ) && is_object_in_taxonomy( 'property', $attribute ) ) {
          foreach( wp_get_object_terms( $this_property[ 'ID' ], $attribute ) as $term_data ) {

            if( $args[ 'make_terms_links' ] == 'true' ) {
              $terms[ ] = '<a class="wpp_term_link" href="' . get_term_link( $term_data, $attribute ) . '"><span class="wpp_term">' . $term_data->name . '</span></a>';
            } else {
              $terms[ ] = '<span class="wpp_term">' . $term_data->name . '</span>';
            }
          }

          if( is_array( $terms ) && !empty( $terms ) ) {
            $value = implode( $args[ 'separator' ], $terms );
          }

        }

        //** Try to get value using get get_attribute() function */
        if( !$value && function_exists( 'get_attribute' ) ) {
          $value = get_attribute( $attribute, array(
            'return'          => 'true',
            'property_object' => $this_property
          ) );
        }

        if( !empty( $args[ 'before' ] ) ) {
          $return[ 'before' ] = html_entity_decode( $args[ 'before' ] );
        }

        $return[ 'value' ] = apply_filters( 'wpp_property_attribute_shortcode', $value, $this_property );

        if( $args[ 'strip_tags' ] == "true" && !empty( $return[ 'value' ] ) ) {
          $return[ 'value' ] = strip_tags( $return[ 'value' ] );
        }

        if( !empty( $args[ 'after' ] ) ) {
          $return[ 'after' ] = html_entity_decode( $args[ 'after' ] );
        }

        //** When no value is found */
        if( empty( $return[ 'value' ] ) ) {

          if( !empty( $args[ 'if_empty' ] ) ) {
            return $args[ 'if_empty' ];
          } else {
            return false;
          }
        }

        if( is_array( $return ) ) {
          return implode( '', $return );
        }

        return false;

      }

    }

  }

}