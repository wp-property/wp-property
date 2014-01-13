<?php
/**
 * Name: Single Property Map
 * ID: property_map
 * Type: shortcode
 * Group: WP-Property
 * Class: UsabilityDynamics\WPP\Shortcode_Map
 * Version: 2.0.0
 * Description: Displays property maps from single property pages.
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Shortcode_Map' ) ) {
    /**
     * 
     */
    class Shortcode_Map extends \UsabilityDynamics\Shortcode\Shortcode {
    
      public $id = 'property_map';
      
      public $group = 'WP-Property';
      
      public function __construct( $options = array() ) {
        $this->name = sprintf( __( 'Single %1$s Map', 'wpp' ), Utility::property_label( 'singular' ) );
        $this->description = sprintf( __( 'Displays %1$s maps from single %1$s pages.', 'wpp' ), Utility::property_label( 'singular' ) );
        $this->params = array(
          'width' => array(),
          'height ' => array(),
          'zoom_level' => array(),
          'hide_infobox' => array(),
          'property_id' => array(),
        );
        
        parent::__construct( $options );
      }

      public function call( $atts = "" ) {
        global $post, $property;

        if ( !$atts ) {
          $atts = array();
        }

        $defaults = array(
          'width' => '100%',
          'height' => '450px',
          'zoom_level' => '13',
          'hide_infobox' => 'false',
          'property_id' => false
        );

        $args = array_merge( $defaults, $atts );

        //** Try to get property if an ID is passed */
        if ( is_numeric( $args[ 'property_id' ] ) ) {
          $property = Utility::get_property( $args[ 'property_id' ] );
        }

        //** Load into $property object */
        if ( !isset( $property ) ) {
          $property = $post;
        }

        //** Convert to array */
        $property = (array) $property;

        //** Force map to be enabled here */
        $skip_default_google_map_check = true;

        $map_width = $args[ 'width' ];
        $map_height = $args[ 'height' ];
        $hide_infobox = ( $args[ 'hide_infobox' ] == 'true' ? true : false );

        //** Find most appropriate template */
        $template_found = Utility::get_template_part( array( "content-single-property-map", "property-map" ), array( WPP_Templates ) );
        if ( !$template_found ) {
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
