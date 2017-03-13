<?php

/**
 * Shortcode: [property_taxonomies]
 *
 * @since 2.0.5
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Property_Terms_Shortcode' ) ) {

    class Property_Terms_Shortcode extends Shortcode {

      /**
       * init
       */
      public function __construct() {

        $taxonomies= array();
        $_taxonomies = ud_get_wp_property( 'taxonomies', array() );
        if( !empty( $_taxonomies ) && is_array( $_taxonomies ) ) {
          foreach( $_taxonomies as $k => $v ) {
            $taxonomies[ $k ] = !empty( $v[ 'label' ] ) ? $v[ 'label' ] : $k;
          }
        }

        $options = array(
            'id' => 'property_terms',
            'params' => array(
              'title' => array(
                'name' => __( 'Title', ud_get_wp_property( 'domain' ) ),
                'description' => __( 'Will show title before terms.', ud_get_wp_property( 'domain' ) ),
                'type' => 'text',
                'default' => ''
              ),
              'property_id' => array(
                'name' => sprintf( __( '%s ID', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
                'description' => sprintf( __( 'If not empty, result will show particular %s, which ID is set.', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
                'type' => 'text',
                'default' => ''
              ),
              'taxonomy' => array(
                'name' => __( 'Taxonomy', ud_get_wp_property()->domain ),
                'description' => sprintf( __( 'Renders %s terms of particular taxonomy', ud_get_wp_property( 'domain' ) ), \WPP_F::property_label() ),
                'type' => 'select',
                'options' => $taxonomies,
              )
            ),
            'description' => sprintf( __( 'Renders %s Terms for specific taxonomy', ud_get_wp_property()->domain ), \WPP_F::property_label() ),
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
          'title' => '',
          'property_id' => '',
          'taxonomy' => '',
        ), $atts );

        if( empty( $data['taxonomy'] ) || !taxonomy_exists( $data['taxonomy'] ) ) {
          return;
        }

        if( empty( $data[ 'property_id' ] ) || !is_numeric( $data[ 'property_id' ] ) ) {
          global $post;
          if( !is_object( $post ) || empty( $post->ID ) ) {
            return;
          }
          $data[ 'property_id' ] = $post->ID;
        }

        return $this->get_template( 'property-terms', $data, false );

      }

    }

    new Property_Terms_Shortcode();

  }

}