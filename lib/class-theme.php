<?php
/**
 * Theme Functionality
 *
 * @class Theme
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Theme' ) ) {

    class Theme {

      /**
       * Adds wp-property-listing class in search results and property_overview pages
       *
       * @since 0.7260
       */
      public static function body_class( $classes ) {
        global $post, $wp_properties;

        if( strpos( $post->post_content, "property_overview" ) || ( is_search() && isset( $_REQUEST[ 'wpp_search' ] ) ) || ( $wp_properties[ 'configuration' ][ 'base_slug' ] == $post->post_name ) ) {
          $classes[ ] = 'wp-property-listing';
        }

        return $classes;
      }

    }

  }

}



