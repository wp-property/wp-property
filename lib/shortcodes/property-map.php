<?php
/**
 * Displays a map for the current property.
 *
 * Must be used on a property page, or within a property loop where the global $post or $property variable is for a property object.
 *
 * @since 1.26.0
 *
 */

namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Map' ) ) {

    /**
     * Property Search Shortcode Class
     *
     */
    class Property_Map extends \UsabilityDynamics\WPP\Shortcode {

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
      static function shortcode_property_map( $atts = false ) {
        global $post, $property;

        if( !$atts ) {
          $atts = array();
        }

        $defaults = array(
          'width'        => '100%',
          'height'       => '450px',
          'zoom_level'   => '13',
          'hide_infobox' => 'false',
          'property_id'  => false
        );

        $args = array_merge( $defaults, $atts );

        //** Try to get property if an ID is passed */
        if( is_numeric( $args[ 'property_id' ] ) ) {
          $property = WPP_F::get_property( $args[ 'property_id' ] );
        }

        //** Load into $property object */
        if( !isset( $property ) ) {
          $property = $post;
        }

        //** Convert to array */
        $property = (array) $property;

        //** Force map to be enabled here */
        $skip_default_google_map_check = true;

        $map_width    = $args[ 'width' ];
        $map_height   = $args[ 'height' ];
        $hide_infobox = ( $args[ 'hide_infobox' ] == 'true' ? true : false );

        //** Find most appropriate template */
        $template_found = WPP_F::get_template_part( array( "content-single-property-map", "property-map" ), array( WPP_Templates ) );
        if( !$template_found ) {
          return false;
        }
        ob_start();
        include $template_found;
        $html = ob_get_contents();
        ob_end_clean();

        $html = apply_filters( 'shortcode_property_map_content', $html, $args );

        return $html;
      }

    }

  }

}