<?php

/**
 * Shortcode: [property_meta]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Meta_Shortcode' ) ) {

    class Property_Meta_Shortcode extends Shortcode {

      /**
       * init
       */
      public function __construct() {

        $meta = ud_get_wp_property( 'property_meta', array() );

        $options = array(
            'id' => 'property_meta',
            'params' => array(
              'property_id' => array(
                'name' => __( 'Property ID', ud_get_wp_property()->domain ),
                'description' => __( 'If not empty, result will show particular property, which ID is set.', ud_get_wp_property()->domain ),
                'type' => 'text',
                'default' => ''
              ),
              'include' => array(
                'name' => __( 'Include', ud_get_wp_property()->domain ),
                'description' => __( 'The list of meta attributes to be included. If no meta checked, all available meta attributes will be shown.', ud_get_wp_property()->domain ),
                'type' => 'multi_checkbox',
                'options' => $meta,
              ),
            ),
            'description' => sprintf( __( 'Renders %s Meta', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
            'group' => 'WP-Property'
        );

        parent::__construct( $options );
      }

      /**
       * @param string $atts
       * @return string|void
       */
      public function call( $atts = "" ) {

        $atts = shortcode_atts( array(
          'property_id' => '',
          'include' => ''
        ), $atts );

        $meta = array();
        if( !empty( $atts[ 'include' ] ) ) {
          $include = explode( ',', $atts[ 'include' ] );
          foreach( $include as $k ) {
            $k = trim( $k );
            $v = ud_get_wp_property( "property_meta.{$k}" );
            if( !empty( $v ) ) {
              $meta[ $k ] = $v;
            }
          }
        } else {
          $meta = ud_get_wp_property( 'property_meta' );
        }

        if( !empty( $atts[ 'property_id' ] ) && is_numeric( $atts[ 'property_id' ] ) ) {
          $post_id = $atts[ 'property_id' ];
        } else {
          global $post;
          $post_id = $post->ID;
        }

        return $this->get_template( 'property-meta', array(
          'meta' => $meta,
          'post_id' => $post_id,
        ), false );

      }

    }

    /**
     * Register
     */
    new Property_Meta_Shortcode();

  }

}