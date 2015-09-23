<?php

/**
 * Shortcode: [property_description]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Description_Shortcode' ) ) {

    class Property_Description_Shortcode extends Shortcode {

      /**
       * init
       */
      public function __construct() {

        $options = array(
            'id' => 'property_description',
            'params' => array(),
            'description' => __( 'Renders Property Description', ud_get_wp_property()->domain ),
            'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {

        global $post;

        ob_start();
        echo $post->post_content;
        return ob_get_clean();

      }

    }

    /**
     * Register
     */
    new Property_Description_Shortcode();

  }

}