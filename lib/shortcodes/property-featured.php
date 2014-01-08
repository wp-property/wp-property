<?php
/**
 * Displays featured properties
 *
 * Performs searching/filtering functions, provides template with $properties file
 * Retirms html content to be displayed after location attribute on property edit page
 *
 * @todo Consider making this function depend on shortcode_property_overview() more so pagination and sorting functions work.
 *
 * @since 0.60
 *
 * @param bool $atts
 *
 * @internal param string $listing_id Listing ID must be passed
 *
 * @return string
 * @uses Utility::get_properties()
 */

namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Featured' ) ) {

    /**
     * Property Search Shortcode Class
     *
     */
    class Featured extends \UsabilityDynamics\WPP\Shortcode {

      /**
       * Initialize Shortcode
       *
       * @param string $atts
       * @param string $content
       */
      function __construct( $atts = '', $content = '' ) {

      }

      static function shortcode_featured_properties( $atts = false ) {
        global $wp_properties, $wpp_query, $post;

        $default_property_type = Utility::get_most_common_property_type();

        if( !$atts ) {
          $atts = array();
        }
        $hide_count = '';
        $defaults   = array(
          'property_type'          => '',
          'type'                   => '',
          'class'                  => 'shortcode_featured_properties',
          'per_page'               => '6',
          'sorter_type'            => 'none',
          'show_children'          => 'false',
          'hide_count'             => true,
          'fancybox_preview'       => 'false',
          'bottom_pagination_flag' => 'false',
          'pagination'             => 'off',
          'stats'                  => '',
          'thumbnail_size'         => 'thumbnail'
        );

        $args = array_merge( $defaults, $atts );

        //** Using "image_type" is obsolete */
        if( $args[ 'thumbnail_size' ] == $defaults[ 'thumbnail_size' ] && !empty( $args[ 'image_type' ] ) ) {
          $args[ 'thumbnail_size' ] = $args[ 'image_type' ];
        }

        //** Using "type" is obsolete. If property_type is not set, but type is, we set property_type from type */
        if( !empty( $args[ 'type' ] ) && empty( $args[ 'property_type' ] ) ) {
          $args[ 'property_type' ] = $args[ 'type' ];
        }

        if( empty( $args[ 'property_type' ] ) ) {
          $args[ 'property_type' ] = $default_property_type;
        }

        // Convert shortcode multi-property-type string to array
        if( !empty( $args[ 'stats' ] ) ) {

          if( strpos( $args[ 'stats' ], "," ) ) {
            $args[ 'stats' ] = explode( ",", $args[ 'stats' ] );
          }

          if( !is_array( $args[ 'stats' ] ) ) {
            $args[ 'stats' ] = array( $args[ 'stats' ] );
          }

          foreach( $args[ 'stats' ] as $key => $stat ) {
            $args[ 'stats' ][ $key ] = trim( $stat );
          }

        }

        $args[ 'disable_wrapper' ] = 'true';
        $args[ 'featured' ]        = 'true';
        $args[ 'template' ]        = 'featured-shortcode';

        unset( $args[ 'image_type' ] );
        unset( $args[ 'type' ] );

        $result = WPP_Core::shortcode_property_overview( $args );

        return $result;
      }

    }

  }

}