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
                'name' => sprintf( __( 'Searchable %s Types', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
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
            'description' => sprintf( __( 'Renders %s Search form', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
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

        return $this->render( $data );

      }

      /**
       * Renders shortcode
       *
       * @since 1.04
       */
      public function render( $atts = "" ) {
        global $post, $wp_properties;
        $group_attributes = '';
        $per_page = '';
        $pagination = '';
        extract( shortcode_atts( array(
          'searchable_attributes' => '',
          'searchable_property_types' => '',
          'pagination' => 'on',
          'group_attributes' => 'off',
          'per_page' => '10',
          'strict_search' => 'false'
        ), $atts ) );

        if( empty( $searchable_attributes ) ) {

          //** get first 3 attributes to prevent people from accidentally loading them all (long query) */
          $searchable_attributes = array_slice( $wp_properties[ 'searchable_attributes' ], 0, 5 );

        } else {
          $searchable_attributes = explode( ",", $searchable_attributes );
        }

        $searchable_attributes = array_unique( $searchable_attributes );

        if( empty( $searchable_property_types ) ) {
          $searchable_property_types = $wp_properties[ 'searchable_property_types' ];
        } else {
          $searchable_property_types = explode( ",", $searchable_property_types );
        }

        $widget_id = $post->ID . "_search";

        ob_start();
        echo WPP_LEGACY_WIDGETS ? '<div class="wpp_shortcode_search">' : '<div class="wpp_shortcode_search_v2">';

        $search_args[ 'searchable_attributes' ] = $searchable_attributes;
        $search_args[ 'searchable_property_types' ] = $searchable_property_types;
        $search_args[ 'group_attributes' ] = ( $group_attributes == 'on' || $group_attributes == 'true' ? true : false );
        $search_args[ 'per_page' ] = $per_page;
        $search_args[ 'pagination' ] = $pagination;
        $search_args[ 'instance_id' ] = $widget_id;
        $search_args[ 'strict_search' ] = $strict_search;

        draw_property_search_form( $search_args );

        echo "</div>";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

      }


    }

    /**
     * Register
     */
    new Property_Search_Shortcode();

  }

}