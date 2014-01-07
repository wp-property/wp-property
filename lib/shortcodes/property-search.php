<?php
/**
 * Returns the property search widget
 *
 *
 * @since 1.04
 *
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Search' ) ) {

    /**
     * Property Search Shortcode Class
     *
     */
    class Property_Search extends \UsabilityDynamics\WPP\Shortcode {

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
      static function shortcode_property_search( $atts = "" ) {
        global $post, $wp_properties;
        $group_attributes = '';
        $per_page         = '';
        $pagination       = '';
        extract( shortcode_atts( array(
          'searchable_attributes'     => '',
          'searchable_property_types' => '',
          'pagination'                => 'on',
          'group_attributes'          => 'off',
          'per_page'                  => '10'
        ), $atts ) );

        if( empty( $searchable_attributes ) ) {

          //** get first 3 attributes to prevent people from accidentally loading them all (long query) */
          $searchable_attributes = array_slice( $wp_properties[ 'searchable_attributes' ], 0, 5 );

        } else {
          $searchable_attributes = explode( ",", $searchable_attributes );
        }

        $searchable_attributes = array_unique( $searchable_attributes );

        if( empty( $searchable_property_types ) ) {
          $searchable_property_types = $wp_properties[ 'searchable_property_types' ];
        } else {
          $searchable_property_types = explode( ",", $searchable_property_types );
        }

        $widget_id = $post->ID . "_search";

        ob_start();
        echo '<div class="wpp_shortcode_search">';

        $search_args[ 'searchable_attributes' ]     = $searchable_attributes;
        $search_args[ 'searchable_property_types' ] = $searchable_property_types;
        $search_args[ 'group_attributes' ]          = ( $group_attributes == 'on' || $group_attributes == 'true' ? true : false );
        $search_args[ 'per_page' ]                  = $per_page;
        $search_args[ 'pagination' ]                = $pagination;
        $search_args[ 'instance_id' ]               = $widget_id;

        draw_property_search_form( $search_args );

        echo "</div>";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

      }

    }

  }

}