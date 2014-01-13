<?php
/**
 * Name: Featured Properties
 * ID: featured_properties
 * Type: shortcode
 * Group: WP-Property
 * Class: UsabilityDynamics\WPP\Shortcode_Featured_Properties
 * Version: 2.0.0
 * Description: Queries only those properties that have been given Featured status.
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Shortcode_Featured_Properties' ) ) {
    /**
     * Displays featured properties
     *
     * Performs searching/filtering functions, provides template with $properties file
     * Retirms html content to be displayed after location attribute on property edit page
     *
     * @todo Consider making this function depend on shortcode_property_overview() more so pagination and sorting functions work.
     * @since 0.60
     * @param string $listing_id Listing ID must be passed
     */
    class Shortcode_Featured_Properties extends \UsabilityDynamics\Shortcode\Shortcode {
    
      public $id = 'featured_properties';
      
      public $group = 'WP-Property';
      
      public function __construct( $options = array() ) {
        $this->name = sprintf( __( 'Featured %1$s', 'wpp' ), Utility::property_label( 'plural' ) );
        $this->description = sprintf( __( 'Queries only those %1$s that have been given Featured status.', 'wpp' ), Utility::property_label( 'plural' ) );
        $this->params = array(
          'property_type' => array(),
          'class ' => array(),
          'per_page' => array(),
          'sorter_type' => array(),
          'show_children' => array(),
          'hide_count' => array(),
          'bottom_pagination_flag' => array(),
          'pagination' => array(),
          'stats' => array(),
          'thumbnail_size' => array(),
        );
        
        parent::__construct( $options );
      }

      public function call( $atts = "" ) {
        global $wp_properties, $wpp_query, $post;

        $default_property_type = Utility::get_most_common_property_type();

        if ( !$atts ) {
          $atts = array();
        }
        $hide_count = '';
        $defaults = array(
          'property_type' => '',
          'type' => '',
          'class' => 'shortcode_featured_properties',
          'per_page' => '6',
          'sorter_type' => 'none',
          'show_children' => 'false',
          'hide_count' => true,
          'fancybox_preview' => 'false',
          'bottom_pagination_flag' => 'false',
          'pagination' => 'off',
          'stats' => '',
          'thumbnail_size' => 'thumbnail'
        );

        $args = array_merge( $defaults, $atts );

        //** Using "image_type" is obsolete */
        if ( $args[ 'thumbnail_size' ] == $defaults[ 'thumbnail_size' ] && !empty( $args[ 'image_type' ] ) ) {
          $args[ 'thumbnail_size' ] = $args[ 'image_type' ];
        }

        //** Using "type" is obsolete. If property_type is not set, but type is, we set property_type from type */
        if ( !empty( $args[ 'type' ] ) && empty( $args[ 'property_type' ] ) ) {
          $args[ 'property_type' ] = $args[ 'type' ];
        }

        if ( empty( $args[ 'property_type' ] ) ) {
          $args[ 'property_type' ] = $default_property_type;
        }

        // Convert shortcode multi-property-type string to array
        if ( !empty( $args[ 'stats' ] ) ) {

          if ( strpos( $args[ 'stats' ], "," ) ) {
            $args[ 'stats' ] = explode( ",", $args[ 'stats' ] );
          }

          if ( !is_array( $args[ 'stats' ] ) ) {
            $args[ 'stats' ] = array( $args[ 'stats' ] );
          }

          foreach ( $args[ 'stats' ] as $key => $stat ) {
            $args[ 'stats' ][ $key ] = trim( $stat );
          }

        }

        $args[ 'disable_wrapper' ] = 'true';
        $args[ 'featured' ] = 'true';
        $args[ 'template' ] = 'featured-shortcode';

        unset( $args[ 'image_type' ] );
        unset( $args[ 'type' ] );

        $_shrt = \UsabilityDynamics\Shortcode\Manager::get_by( 'id', 'property_overview' );

        return is_object( $_shrt ) ? $_shrt->call( $args ) : '';
      }
    
    }
  }

}
