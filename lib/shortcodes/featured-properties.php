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
                'name' => sprintf( __( '%s Type', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
                'description' => sprintf( __( '%s Type', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
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
            'description' => sprintf( __( 'Renders Featured %s', ud_get_wp_property()->domain ), \WPP_F::property_label( 'plural' ) ),
            'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       *
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {
        return $this->render( $atts );
      }

      /**
       * Displays featured properties
       *
       * Performs searching/filtering functions, provides template with $properties file
       * Retirms html content to be displayed after location attribute on property edit page
       *
       * @since 0.60
       *
       * @param string $listing_id Listing ID must be passed
       *
       * @uses \WPP_F::get_properties()
       *
       * @return string
       */
      public function render( $atts = false ) {
        global $wp_properties, $wpp_query, $post;

        if( !$atts ) {
          $atts = array();
        }
        $hide_count = '';
        $defaults = array(
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
        );

        $args = shortcode_atts( $defaults, $atts );

        //** Using "image_type" is obsolete */
        if( $args[ 'thumbnail_size' ] == $defaults[ 'thumbnail_size' ] && !empty( $args[ 'image_type' ] ) ) {
          $args[ 'thumbnail_size' ] = $args[ 'image_type' ];
        }

        //** Using "type" is obsolete. If property_type is not set, but type is, we set property_type from type */
        if( !empty( $args[ 'type' ] ) && empty( $args[ 'property_type' ] ) ) {
          $args[ 'property_type' ] = $args[ 'type' ];
        }

        // Convert shortcode multi-property-type string to array
        if( !empty( $args[ 'stats' ] ) ) {

          if( strpos( $args[ 'stats' ], "," ) ) {
            $args[ 'stats' ] = explode( ",", $args[ 'stats' ] );
          }

          if( !is_array( $args[ 'stats' ] ) ) {
            $args[ 'stats' ] = array( $args[ 'stats' ] );
          }

          foreach( $args[ 'stats' ] as $key => $stat ) {
            $args[ 'stats' ][ $key ] = trim( $stat );
          }

          $args[ 'stats' ] = array_flip( $args[ 'stats' ] );

        }

        /** We hide wrapper to use our custom one. */
        $args[ 'disable_wrapper' ] = 'true';

        $args[ 'featured' ] = 'true';
        $args[ 'template' ] = 'property-overview-featured-shortcode';
        $args[ 'unique_hash' ] = rand( 10000, 99900 );

        unset( $args[ 'image_type' ] );
        unset( $args[ 'type' ] );

        $_args = \UsabilityDynamics\Shortcode\Utility::prepare_args( $args );
        $result = do_shortcode( "[property_overview {$_args}]" );

        if( !empty( $result ) ) {
          $result = '<div id="wpp_shortcode_' . $args[ 'unique_hash' ] . '" class="' . $args[ 'class' ] . '">' . $result . '</div>';
        }

        return $result;
      }


    }

    /**
     * Register
     */
    new Featured_Properties_Shortcode();

  }

}