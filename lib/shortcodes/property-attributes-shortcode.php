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
          'include_clsf' => 'all',
          'title' => 'true',
          'stats_prefix' => sanitize_key( \WPP_F::property_label( 'singular' ) )
        ), $atts );

        return $this->get_template( 'property_attributes', $data, false );

      }

    }

    /**
     * Register
     */
    new Property_Attributes_Shortcode();

  }

}