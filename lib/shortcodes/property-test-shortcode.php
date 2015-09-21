<?php

namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Test_Shortcode' ) ) {

    class Property_Test_Shortcode extends \UsabilityDynamics\Shortcode\Shortcode {

      public function __construct() {

        $options = array(
            'id' => 'property_test',
            'params' => array(

                'test' => array(
                    'name' => __( 'Test' ),
                    'description' => __( 'Test' )
                )

            ),
            'description' => __( 'Renders Test' ),
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
            'test' => false
        ), $atts );

        return print_r( $data, 1 );

      }

    }

    new Property_Test_Shortcode();

  }

}