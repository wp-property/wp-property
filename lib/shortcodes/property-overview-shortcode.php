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
                  'dl_list' => __( 'Definitions List', ud_get_wp_property()->domain ),
                  'list' => __( 'Simple List', ud_get_wp_property()->domain ),
                  'plain_list' => __( 'Plain List', ud_get_wp_property()->domain ),
                  'detail' => __( 'Detailed List', ud_get_wp_property()->domain )
                )
              ),
              'show_true_as_image' => array(
                'name' => __( 'Show "True" as image', ud_get_wp_property()->domain ),
                'description' => __( 'Display boolean attributes like checkbox or not.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                )
              ),
              'make_link' => array(
                'name' => __( 'Make link', ud_get_wp_property()->domain ),
                'description' => __( 'Make links of attribute terms', ud_get_wp_property()->domain ),
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
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                )
              ),
              'first_alt' => array(
                'name' => __( 'First Alt', ud_get_wp_property()->domain ),
                'description' => __( 'Make first attribute to be alt one.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                )
              ),
              'return_blank' => array(
                'name' => __( 'Return Blank', ud_get_wp_property()->domain ),
                'description' => __( 'Omit blank values or not.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                )
              ),
              'include' => array(
                'name' => __( 'Include', ud_get_wp_property()->domain ),
                'description' => __( 'CSV of attribute slugs to be included.', ud_get_wp_property()->domain ),
                'type' => 'text'
              ),
              'exclude' => array(
                'name' => __( 'Exclude', ud_get_wp_property()->domain ),
                'description' => __( 'CSV of attribute slugs to be excluded.', ud_get_wp_property()->domain ),
                'type' => 'text'
              ),
              'include_clsf' => array(
                'name' => __( 'Include Classifications', ud_get_wp_property()->domain ),
                'description' => __( 'CSV of classifications to be included.', ud_get_wp_property()->domain ),
                'type' => 'text'
              ),
              'title' => array(
                'name' => __( 'Title', ud_get_wp_property()->domain ),
                'description' => __( 'Title.', ud_get_wp_property()->domain ),
                'type' => 'select',
                'options' => array(
                  'true' => __( 'Yes', ud_get_wp_property()->domain ),
                  'false' => __( 'No', ud_get_wp_property()->domain )
                )
              ),
              'stats_prefix' => array(
                'name' => __( 'Stats Prefix', ud_get_wp_property()->domain ),
                'description' => __( 'Class prefix for stats items', ud_get_wp_property()->domain ),
                'type' => 'text'
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