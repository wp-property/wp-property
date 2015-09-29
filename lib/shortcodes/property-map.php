<?php

/**
 * Shortcode: [property_address_map]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Map_Shortcode' ) ) {

    class Property_Map_Shortcode extends Shortcode {

      /**
       * Init
       */
      public function __construct() {

        $options = array(
            'id' => 'property_map',
            'params' => array(
              'width' => array(
                'name' => __( 'Width', ud_get_wp_property()->domain ),
                'description' => __( 'Set width of map. (e.g. 100%, 500px)', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => '100%'
              ),
              'height' => array(
                'name' => __( 'Height', ud_get_wp_property()->domain ),
                'description' => __( 'Set height of map. (e.g. 500px)', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => '450px'
              ),
              'zoom_level' => array(
                'name' => __( 'Zoom Level', ud_get_wp_property()->domain ),
                'description' => __( 'Set level of map zoom', ud_get_wp_property()->domain ),
                'type' => 'number',
                'min' => '1',
                'default' => '13'
              ),
              'hide_infobox' => array(
                'name' => __( 'Hide Infobox', ud_get_wp_property()->domain ),
                'description' => __( 'Set infobox hidden or not', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                    'true' => __( 'Yes', ud_get_wp_property()->domain ),
                    'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              )
            ),
            'description' => __( 'Renders Property Map', ud_get_wp_property()->domain ),
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
          'width' => '100%',
          'height' => '450px',
          'zoom_level' => '13',
          'hide_infobox' => 'false'
        ), $atts );

        wp_enqueue_script( 'google-maps' );

        return $this->get_template( 'property-map', $data, false );

      }

    }

    /**
     * Register
     */
    new Property_Map_Shortcode();

  }

}