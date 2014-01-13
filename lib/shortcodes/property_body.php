<?php
/**
 * Name: Property Body
 * ID: property_body
 * Type: shortcode
 * Group: WP-Property
 * Class: UsabilityDynamics\WPP\Shortcode_Property_Body
 * Version: 2.0.0
 * Description: Single Property's details. This element replicates a large part what the legacy property.php template did.
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Shortcode_Property_Body' ) ) {
    /**
     * Section that is inserted into the_content area on single listing pages.
     * This element replicates a large part what the legacy property.php template did.
     * This is a very basic example, this shortcode would need to assume a lot of defaults to assist with providing the children shortcodes with enough arguments.
     * This could be called on a single listing page, but also within a search result loop.
     */
    class Shortcode_Property_Body extends \UsabilityDynamics\Shortcode\Shortcode {
    
      public $id = 'property_body';
      
      public $group = 'WP-Property';
      
      public function __construct( $options = array() ) {
        $this->name = sprintf( __( '%1$s Body', 'wpp' ), Utility::property_label( 'singular' ) );
        $this->description = sprintf( __( 'Single %1$s\'s details. This element replicates a large part what the legacy property.php template did.', 'wpp' ), Utility::property_label( 'singular' ) );
        
        parent::__construct( $options );
      }

      public function call( $atts = "" ) {
        $parts = array();
        $parts[] = do_shortcode( '[property_image]' );
        $parts[] = do_shortcode( '[property_attributes]' );
        $parts[] = do_shortcode( '[property_taxonomy_terms]' );
        $parts[] = do_shortcode( '[property_map]' );
        return implode( '', $parts );
      }
    
    }
  }

}
