<?php

/**
 * Shortcode: [property_overview]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Overview_Shortcode' ) ) {

    class Property_Overview_Shortcode extends Shortcode {

      /**
       * Init
       */
      public function __construct() {

        $options = array(
            'id' => 'property_overview',
            'params' => array(
              'strict_search' => array(
                'name' => __( 'Strict Search', ud_get_wp_property()->domain ),
                'description' => __( 'Use strict search or not.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'false'
              ),
              'show_children' => array(
                'name' => __( 'Show Children', ud_get_wp_property()->domain ),
                'description' => __( 'Show children or not.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'true'
              ),
              'child_properties_title' => array(
                'name' => __( 'Child Properties Title', ud_get_wp_property()->domain ),
                'description' => __( 'Title for child properties section.', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => __( 'Floor plans at location:', ud_get_wp_property()->domain )
              ),
              'fancybox_preview' => array(
                'name' => __( 'Fancybox Preview', ud_get_wp_property()->domain ),
                'description' => __( 'Use fancybox preview.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                ),
                'default' => 'true'
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
              )
            ),
            'description' => __( 'Renders Property Attributes', ud_get_wp_property()->domain ),
            'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {
        global $wp_properties;

        $data = shortcode_atts( array(
          'strict_search' => 'false',
          'show_children' => ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] : 'true' ),
          'child_properties_title' => __( 'Floor plans at location:', ud_get_wp_property()->domain ),
          'fancybox_preview' => $wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ],
          'bottom_pagination_flag' => ( isset( $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] ) && $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] == 'true' ? true : false ),
          'thumbnail_size' => $wp_properties[ 'configuration' ][ 'property_overview' ][ 'thumbnail_size' ],
          'sort_by_text' => __( 'Sort By:', ud_get_wp_property()->domain ),
          'sort_by' => 'post_date',
          'sort_order' => 'DESC',
          'template' => 'false',
          'ajax_call' => 'false',
          'disable_wrapper' => 'false',
          'sorter_type' => 'buttons',
          'sorter' => 'on',
          'pagination' => 'on',
          'hide_count' => 'false',
          'per_page' => 10,
          'starting_row' => 0,
          'unique_hash' => rand( 10000, 99900 ),
          'detail_button' => 'false',
          'stats' => '',
          'class' => 'wpp_property_overview_shortcode',
          'in_new_window' => 'false'

        ), $atts );

        return \WPP_Core::shortcode_property_overview( $data );

      }

    }

    /**
     * Register
     */
    new Property_Overview_Shortcode();

  }

}