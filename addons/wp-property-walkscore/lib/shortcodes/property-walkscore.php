<?php
/**
 * Shortcode: [property_walkscore]
 * Template: static/views/shortcodes/property_walkscore.php
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Walkscore_Shortcode' ) ) {

    class Property_Walkscore_Shortcode extends WS_Shortcode {

      /**
       * Constructor
       */
      public function __construct() {

        $options = array(
          'id' => 'property_walkscore',
          'params' => array(

            'property_id' => array(
              'name' => __( 'Property ID', ud_get_wpp_walkscore()->domain ),
              'description' => __( 'Optional. If not set, the current post ID is used.', ud_get_wpp_walkscore()->domain ),
            ),

            'ws_view' => array(
              'name' => __( 'View', ud_get_wpp_walkscore()->domain ),
              'description' => __( 'Optional. Available values "text", "icon", "badge".', ud_get_wpp_walkscore()->domain ),
            ),

            'ws_type' => array(
              'name' => __( 'Type', ud_get_wpp_walkscore()->domain ),
              'description' => __( 'Optional. Available values "free", "premium".', ud_get_wpp_walkscore()->domain ),
            ),

          ),
          'description' => __( 'Renders Walk Score', ud_get_wpp_walkscore()->domain ),
          'group' => 'WP-Property',
        );

        parent::__construct( $options );

      }

      /**
       *  Renders Shortcode
       */
      public function call( $atts = "" ) {

        $data = shortcode_atts( array(
          'property_id' => false,
          'ws_view' => ud_get_wpp_walkscore( 'config.walkscore.view', 'text' ),
          'ws_type' => ud_get_wpp_walkscore( 'config.walkscore.type', 'free' ),
        ), $atts );

        if( !empty( $data[ 'property_id' ] ) && is_numeric( $data[ 'property_id' ] ) ) {
          $data['walkscore'] = get_post_meta( $data[ 'property_id' ], '_ws_walkscore', true );
          $data['walkscore_data'] = get_post_meta( $data[ 'property_id' ], '_ws_walkscore_response', true );
        } else {
          global $post;
          if( empty( $post ) || !is_object( $post ) || !isset( $post->ID ) ) {
            return;
          }
          $data['walkscore'] = get_post_meta( $post->ID, '_ws_walkscore', true );
          $data['walkscore_data'] = get_post_meta( $post->ID, '_ws_walkscore_response', true );
        }

        if( empty( $data['walkscore'] ) || empty( $data['walkscore_data'] ) ) {
          return;
        }

        if( !in_array( $data[ 'ws_view' ], array( 'text', 'icon', 'badge' ) ) ) {
          return;
        }

        if( !in_array( $data[ 'ws_type' ], array( 'free', 'premium' ) ) ) {
          return;
        }

        $data[ 'link' ] = ( $data[ 'ws_type' ] == 'premium' ?
          $data['walkscore_data']['ws_link'] : $data['walkscore_data']['help_link'] );


        wp_enqueue_style( 'property-walkscore', ud_get_wpp_walkscore()->path( 'static/styles/walk-score.css', 'url' ), array(), ud_get_wpp_walkscore( 'version' ) );

        return $this->get_template( 'property_walkscore', $data, false );

      }

    }

    new Property_Walkscore_Shortcode();

  }

}

