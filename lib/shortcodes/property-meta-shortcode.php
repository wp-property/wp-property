<?php

/**
 * Shortcode: [property_meta]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Meta_Shortcode' ) ) {

    class Property_Meta_Shortcode extends WPP_Shortcode {

      /**
       * init
       */
      public function __construct() {

        $options = array(
            'id' => 'property_meta',
            'params' => array(),
            'description' => __( 'Renders Property Taxonomy Terms' ),
            'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {

        $data = shortcode_atts( array(
          'type' => 'property_feature',
          'format' => 'comma',
          'links' => true ),
        $atts );

        return $this->get_template( 'property_taxonomies', $data, false );

      }

    }

    /**
     * Register
     */
    new Property_Taxonomies_Shortcode();

  }

}