<?php

/**
 * Shortcode: [property_search]
 *
 * @since 2.1
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Search_Shortcode' ) ) {

    class Property_Search_Shortcode extends Shortcode {

      /**
       * Init
       */
      public function __construct() {

        $options = array(
            'id' => 'property_search',
            'params' => array(
              'searchable_attributes' => array(
                'name' => __( 'Searchable Attributes', ud_get_wp_property()->domain ),
                'description' => __( 'CSV attributes list', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => ''
              ),
              'searchable_property_types' => array(
                'name' => __( 'Searchable Property Types', ud_get_wp_property()->domain ),
                'description' => __( 'CSV types list', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => ''
              ),
              'pagination' => array(
                'name' => __( 'Pagination', ud_get_wp_property()->domain ),
                'description' => __( 'Use pagination?', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'on' => __( 'Yes', ud_get_wp_property()->domain ),
                  'off' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'on'
              ),
              'group_attributes' => array(
                'name' => __( 'Group Attributes', ud_get_wp_property()->domain ),
                'description' => __( 'Group Attributes?', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'on' => __( 'Yes', ud_get_wp_property()->domain ),
                  'off' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'off'
              ),
              'per_page' => array(
                'name' => __( 'Per Page', ud_get_wp_property()->domain ),
                'description' => __( 'Items per page', ud_get_wp_property()->domain ),
                'type' => 'number',
                'min' => 1,
                'default' => 10
              ),
              'strict_search' => array(
                'name' => __( 'Strict Search', ud_get_wp_property()->domain ),
                'description' => __( 'Use strict search', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              )
            ),
            'description' => __( 'Renders Property Search form', ud_get_wp_property()->domain ),
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
          'searchable_attributes' => '',
          'searchable_property_types' => '',
          'pagination' => 'on',
          'group_attributes' => 'off',
          'per_page' => '10',
          'strict_search' => 'false'
        ), $atts );

        return \WPP_Core::shortcode_property_search( $data );

      }

    }

    /**
     * Register
     */
    new Property_Search_Shortcode();

  }

}