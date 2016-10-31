<?php

/**
 * Shortcode: [property_attributes]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Attributes_Shortcode' ) ) {

    class Property_Attributes_Shortcode extends Shortcode {

      /**
       * Init
       */
      public function __construct() {

        $attributes = ud_get_wp_property( 'property_stats', array() );

        /*
        $hidden_attributes = ud_get_wp_property( 'hidden_frontend_attributes', array() );
        foreach( $attributes as $k => $v ) {
          if( in_array( $k, $hidden_attributes ) ) {
            unset( $attributes[$k] );
          }
        }
        //*/

        $options = array(
            'id' => 'property_attributes',
            'params' => array(
              'sort_by_groups' => array(
                'name' => __( 'Sort by groups', ud_get_wp_property()->domain ),
                'description' => __( 'Sort attributes by groups or not', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                )
              ),
              'display' => array(
                'name' => __( 'Display', ud_get_wp_property()->domain ),
                'description' => __( 'The way of displaying attributes', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'list' => __( 'Simple List', ud_get_wp_property()->domain ),
                  'dl_list' => __( 'Definitions List', ud_get_wp_property()->domain ),
                  'plain_list' => __( 'Plain List', ud_get_wp_property()->domain ),
                  'detail' => __( 'Detailed List', ud_get_wp_property()->domain )
                )
              ),
              'show_true_as_image' => array(
                'name' => __( 'Show "True" as image', ud_get_wp_property()->domain ),
                'description' => __( 'Display boolean attributes like checkbox image.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'false' => __( 'No', ud_get_wp_property()->domain ),
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                )
              ),
              'make_link' => array(
                'name' => __( 'Make link', ud_get_wp_property()->domain ),
                'description' => __( 'Make URLs into clickable links', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                )
              ),
              'hide_false' => array(
                'name' => __( 'Hide false', ud_get_wp_property()->domain ),
                'description' => __( 'Hide attributes with false value', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'false' => __( 'No', ud_get_wp_property()->domain ),
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                )
              ),
              'return_blank' => array(
                'name' => __( 'Return Blank', ud_get_wp_property()->domain ),
                'description' => __( 'Omit blank values or not.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'false' => __( 'No', ud_get_wp_property()->domain ),
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                )
              ),
              'include' => array(
                'name' => __( 'Include', ud_get_wp_property()->domain ),
                'description' => __( 'The list of attributes to be included. If no attribute checked, all available attributes will be shown.', ud_get_wp_property()->domain ),
                'type' => 'multi_checkbox',
                'options' => $attributes,
              ),
              'exclude' => array(
                'name' => __( 'Exclude', ud_get_wp_property()->domain ),
                'description' => __( 'The list of attributes which will not be shown.', ud_get_wp_property()->domain ),
                'type' => 'multi_checkbox',
                'options' => $attributes,
              ),
            ),
            'description' => sprintf( __( 'Renders %s Attributes List', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
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
          'sort_by_groups' => 'true',
          'display' => 'list',
          'show_true_as_image' => 'false',
          'make_link' => 'true',
          'hide_false' => 'false',
          'first_alt' => 'false',
          'return_blank' => 'false',
          'include' => '',
          'exclude' => '',
        ), $atts );

        return $this->get_template( 'property-attributes', $data, false );

      }

    }

    /**
     * Register
     */
    new Property_Attributes_Shortcode();

  }

}