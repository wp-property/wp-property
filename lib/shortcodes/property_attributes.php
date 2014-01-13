<?php
/**
 * Name: Property Attributes
 * ID: property_attributes
 * Type: shortcode
 * Group: WP-Property
 * Class: UsabilityDynamics\WPP\Shortcode_Attributes
 * Version: 2.0.0
 * Description: Renders the list of all property attributes.
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Shortcode_Attributes' ) ) {
    /**
     * @todo Make sure the label/title is rendered correctly when grouped and ungrouped. - potanin@UD
     * @todo Improve so shortcode arguments are passed to draw_stats - potanin@UD 5/24/12
     * @uses draw_stats
     * @since 1.35.0
     */
    class Shortcode_Attributes extends \UsabilityDynamics\Shortcode\Shortcode {
    
      public $id = 'property_attributes';
      
      public $group = 'WP-Property';
      
      public function __construct( $options = array() ) {
        $this->name = sprintf( __( '%1$s Attributes', 'wpp' ), Utility::property_label( 'singular' ) );
        $this->description = sprintf( __( 'Renders the list of all %1$s attributes.', 'wpp' ), Utility::property_label( 'singular' ) );
        $this->params = array(
          'property_id' => array(),
          'title ' => array(),
          'group' => array(),
          'sort_by_groups' => array(),
        );
        
        parent::__construct( $options );
      }

      public function call( $atts = "" ) {
        global $wp_properties, $property;

        if( is_admin() && !DOING_AJAX ) {
          return sprintf( __( '%1$s Attributes', 'wpp' ), Utility::property_label( 'singular' ) );
        }

        $atts = shortcode_atts( array(
          'property_id'    => $property[ 'ID' ],
          'title'          => false,
          'group'          => false,
          'sort_by_groups' => !empty( $wp_properties[ 'property_groups' ] ) && $wp_properties[ 'configuration' ][ 'property_overview' ][ 'sort_stats_by_groups' ] == 'true' ? true : false
        ), $atts );

        $html[ ] = draw_stats( "return=true&make_link=true&group={$atts[group]}&title={$atts['title']}&sort_by_groups={$atts['sort_by_groups']}", $property );

        return implode( '', (array) $html );
      }
    
    }
  }

}
