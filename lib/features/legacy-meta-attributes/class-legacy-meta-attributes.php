<?php
/**
 * WP_PROPERTY_LEGACY_META_ATTRIBUTES
 *
 * Bootstrap
 *
 * @since 2.3
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if( !class_exists( 'UsabilityDynamics\WPP\Legacy_Meta_attibutes' ) ) {

    class Legacy_Meta_Attributes
    {

      /**
       * Loads all stuff for WP_PROPERTY_LEGACY_META_ATTRIBUTES
       */
      public function __construct() {

        add_filter('wpp_total_attribute_array', function( $attributes, $args ) {
          $meta = ud_get_wp_property( 'property_meta', null );
          if( !empty( $meta ) ) {
            if ( $args['use_optgroups'] == 'true') {
              $attributes['Meta'] = $meta;
            } else {
              $attributes = $attributes + (array) $meta;
            }
          }
          return $attributes;
        }, 10, 2 );

        add_filter( 'wpp::settings_developer::tabs', function( $developer_tabs ) {
          $meta = ud_get_wp_property( 'property_meta', null );
          if( !empty( $meta ) ) {
            $developer_tabs[ 'meta' ] = array(
              'label' => __( 'Meta', ud_get_wp_property()->domain ),
              'template' => ud_get_wp_property()->path( 'static/views/admin/settings-developer-meta.php', 'dir' ),
              'order' => 20
            );
          }
          return $developer_tabs;
        }, 10 );

      }

    }

  }

}
