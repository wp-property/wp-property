<?php

/**
 * Shortcode: [featured_properties]
 *
 * @since 2.1
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Featured_Properties_Shortcode' ) ) {

    class Featured_Properties_Shortcode extends Shortcode {

      /**
       * Init
       */
      public function __construct() {

        $options = array(
            'id' => 'featured_properties',
            'params' => array(
              'property_type' => array(
                'name' => __( 'Property Type', ud_get_wp_property()->domain ),
                'description' => __( 'Property Type', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => 'all'
              ),
              'type' => array(
                'name' => __( 'Type', ud_get_wp_property()->domain ),
                'description' => __( 'Type', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => ''
              ),
              'class' => array(
                'name' => __( 'Class', ud_get_wp_property()->domain ),
                'description' => __( 'CSS Class', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => 'shortcode_featured_properties'
              ),
              'per_page' => array(
                'name' => __( 'Per Page', ud_get_wp_property()->domain ),
                'description' => __( 'Items per page', ud_get_wp_property()->domain ),
                'type' => 'number',
                'default' => 6
              ),
              'sorter_type' => array(
                'name' => __( 'Sorter Type', ud_get_wp_property()->domain ),
                'description' => __( 'Sorter Type', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'none' => __( 'None', ud_get_wp_property()->domain ),
                  'buttons'  => __( 'Buttons', ud_get_wp_property()->domain ),
                  'dropdown'  => __( 'Dropdown', ud_get_wp_property()->domain )
                ),
                'default' => 'none'
              ),
              'show_children' => array(
                'name' => __( 'Show Children', ud_get_wp_property()->domain ),
                'description' => __( 'Show children or not.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'hide_count' => array(
                'name' => __( 'Hide Count', ud_get_wp_property()->domain ),
                'description' => __( 'Hide Count', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false'  => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'true'
              ),
              'fancybox_preview' => array(
                'name' => __( 'Fancybox Preview', ud_get_wp_property()->domain ),
                'description' => __( 'Use fancybox preview.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'bottom_pagination_flag' => array(
                'name' => __( 'Bottom Pagination', ud_get_wp_property()->domain ),
                'description' => __( 'Bottom Pagination.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'pagination' => array(
                'name' => __( 'Pagination', ud_get_wp_property()->domain ),
                'description' => __( 'Pagination', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'on' => __( 'On', ud_get_wp_property()->domain ),
                  'off'  => __( 'Off', ud_get_wp_property()->domain )
                ),
                'default' => 'off'
              ),
              'stats' => array(
                'name' => __( 'Stats', ud_get_wp_property()->domain ),
                'description' => __( 'CSV stats', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => ''
              ),
              'thumbnail_size' => array(
                'name' => __( 'Thumbnail Size', ud_get_wp_property()->domain ),
                'description' => __( 'Thumbnail Size.', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => 'thumbnail'
              )
            ),
            'description' => __( 'Renders Featured Properties', ud_get_wp_property()->domain ),
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
          'property_type' => 'all',
          'type' => '',
          'class' => 'shortcode_featured_properties',
          'per_page' => '6',
          'sorter_type' => 'none',
          'show_children' => 'false',
          'hide_count' => 'true',
          'fancybox_preview' => 'false',
          'bottom_pagination_flag' => 'false',
          'pagination' => 'off',
          'stats' => '',
          'thumbnail_size' => 'thumbnail'
        ), $atts );

        return \WPP_Core::shortcode_featured_properties( $data );

      }

    }

    /**
     * Register
     */
    new Featured_Properties_Shortcode();

  }

}