<?php
/**
 * Name: Taxonomy Terms
 * ID: taxonomy_terms
 * Type: shortcode
 * Group: WP-Property
 * Class: UsabilityDynamics\WPP\Shortcode_Taxonomy_Terms
 * Version: 2.0.0
 * Description: Renders the list of terms from all property taxonomies, grouped by taxonomy
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Shortcode_Taxonomy_Terms' ) ) {
    /**
     * @todo Add support to recognize requested taxonomy and default to all, as well as some other shortcode-configured settings - potanin@UD 5/24/12
     * @since 1.35.0
     */
    class Shortcode_Taxonomy_Terms extends \UsabilityDynamics\Shortcode\Shortcode {
    
      public $id = 'taxonomy_terms';
      
      public $group = 'WP-Property';
      
      public function __construct( $options = array() ) {
        $this->name = __( 'Taxonomy Terms', 'wpp' );
        $this->description = sprintf( __( 'Renders the list of terms from all %1$s taxonomies, grouped by taxonomy.', 'wpp' ), Utility::property_label( 'singular' ) );
        $this->params = array(
          'property_id' => array(),
          'title ' => array(),
          'taxonomy' => array(),
        );
        
        parent::__construct( $options );
      }

      public function call( $atts = "" ) {
        global $wp_properties, $post, $property;

        if( is_admin() && !DOING_AJAX ) {
          return sprintf( __( '%1$s Taxonomy Terms', 'wpp' ), Utility::property_label( 'singular' ) );
        }

        $atts = shortcode_atts( array(
          'property_id' => $property[ 'ID' ],
          'title'       => false,
          'taxonomy'    => ''
        ), $atts );

        foreach( (array) $wp_properties[ 'taxonomies' ] as $tax_slug => $tax_data ) {

          $terms = get_features( "property_id={$atts['property_id']}&type={$tax_slug}&format=list&links=true&return=true" );

          if( !empty( $terms ) ) {

            $html[ ] = '<div class="' . wpp_css( 'attribute_list::list_item', 'wpp_attributes', true ) . '">';

            if( $atts[ 'title' ] ) {
              $html[ ] = '<h2 class="wpp_list_title">' . $tax_data[ 'labels' ][ 'name' ] . '</h2>';
            }

            $html[ ] = '<ul class="' . wpp_css( 'attribute_list::list_item', 'wpp_feature_list wpp_attribute_list wpp_taxonomy_terms', true ) . '">';
            $html[ ] = $terms;
            $html[ ] = '</ul>';

            $html[ ] = '</div>'; /* .wpp_attributes */

          }

        }

        return implode( '', (array) $html );
      }
    
    }
  }

}
