<?php
/**
 * Walk Score Attribute Handler
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\WS_Attribute' ) ) {

    class WS_Attribute {

      /**
       * Constructor.
       *
       */
      public function __construct() {

        /**
         * Adds predefined WP-Property 'Walk Score' attribute
         */
        add_action( 'init', array( $this, 'setup_walkscore_attribute' ) );

        /**
         * Removes Attribute from RWMB Meta Boxes ( prohibit ability to edit value of attribute )
         */
        add_filter( 'wpp::rwmb_meta_box::field', array( $this, 'remove_rwmb_meta_box_field' ), 99, 3 );

        /**
         * Adds Note to 'Walk Score 'Attribute on Developer Tab on WP-Property Settings page
         */
        add_action( 'wpp::property_attributes::attribute_name', array( $this, 'add_notice_on_attribute_field' ), 1, 1 );

        /* Handle Scripts and Styles */
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

      }

      /**
       * Adds attribute directly to WP-Property settings if it does not exist.
       * Attribute is required and CAN NOT BE REMOVED!!
       *
       */
      public function setup_walkscore_attribute() {
        global $wp_properties;

        $attributes = ud_get_wp_property( 'property_stats', array() );

        /** Add Walk Score Attribute if it does not exist */
        if( !array_key_exists( '_ws_walkscore', $attributes ) ) {

          $attributes = array_merge( array(
            '_ws_walkscore' => __( 'Walk Score', ud_get_wpp_walkscore('domain') )
          ), $attributes );

          /* Set boolean 'false' at first to save the correct order on set new values. */
          ud_get_wp_property()->set( 'property_stats', false );
          ud_get_wp_property()->set( 'property_stats', $attributes );

        }

        /** Be sure that Walk Score is numeric. */
        /** We prohibit to select Data Entry manually for this attribute. */
        ud_get_wp_property()->set( 'admin_attr_fields._ws_walkscore', 'number' );

        /** Be sure that Walk Score has any selected search input. */
        $search_input = ud_get_wp_property( 'searchable_attr_fields._ws_walkscore' );
        if( empty( $search_input ) ) {
          ud_get_wp_property()->set( 'searchable_attr_fields._ws_walkscore', 'range_input' );
        }

        $wp_properties = ud_get_wp_property()->get();

      }

      /**
       * Removes Attribute from RWMB Meta Boxes ( prohibit ability to edit value of attribute )
       *
       */
      public function remove_rwmb_meta_box_field( $field, $slug, $post ) {
        if( $slug == '_ws_walkscore' && is_numeric( $post->ID ) && $post->post_type == 'property' ) {
          $field['type'] = 'hidden';
        }
        return $field;
      }

      /**
       * Adds Note to 'Walk Score 'Attribute on Developer Tab on WP-Property Settings page
       *
       */
      public function add_notice_on_attribute_field( $slug ) {
        if( $slug == '_ws_walkscore' ) {
          echo "<li class=\"wpp_development_advanced_option\"><div class=\"wpp_notice\"><span>";
          printf( __( 'Note! The current attribute is predefined and used by %s Add-on. You can not remove it or change its Data Entry from \'Number\' to another value.', ud_get_wpp_walkscore('domain') ), '<strong>WP-Property: Walk Score</strong>' );
          echo "<br/><hr/>";
          printf( __( 'Be aware, the value of attribute can not be set manually on Edit %s page.', ud_get_wpp_walkscore('domain') ), \WPP_F::property_label() );
          echo "</span></div></li>";
        }
      }

      /**
       *
       */
      public function admin_enqueue_scripts() {
        $screen = get_current_screen();

        switch( $screen->id ) {

          case 'property_page_property_settings':

            wp_enqueue_style( 'property-walkscore-attribute', ud_get_wpp_walkscore()->path( 'static/styles/admin/property-walkscore-attribute.css', 'url' ), array(), ud_get_wpp_walkscore( 'version' ) );

            break;

        }

      }

    }

  }

}
