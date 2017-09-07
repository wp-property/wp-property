<?php
/**
 * WP-Property Upgrade Handler
 *
 * @since 2.1.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Upgrade' ) ) {

    class Upgrade {

      /**
       * Run Upgrade Process
       *
       * @param $old_version
       * @param $new_version
       */
      static public function run( $old_version, $new_version ) {
        global $wpdb;

        self::do_backup( $old_version, $new_version );

        /**
         * WP-Property 1.42.4 and less compatibility
         */
        update_option( "wpp_version", $new_version );

        /**
         * Specific upgrade conditions.
         */
        switch( true ) {

          case ( version_compare( $old_version, '2.1.1', '<' ) ):
            /*
             * Enable Legacy Features
             */
            $settings = get_option( 'wpp_settings' );
            if( !empty( $settings[ 'configuration' ] ) ) {
              $settings[ 'configuration' ][ 'enable_legacy_features' ] = 'true';
            }
            update_option( 'wpp_settings', $settings );

          case ( version_compare( $old_version, '2.1.3', '<' ) ):
            /*
             * Set default pagination type 'slider'
             * to prevent issues on already existing sites.
             */
            $settings = get_option( 'wpp_settings' );
            $settings[ 'configuration' ][ 'property_overview' ][ 'pagination_type' ] = 'slider';
            update_option( 'wpp_settings', $settings );

          case ( version_compare( $old_version, '2.1.5', '<' ) ):
            /*
             * 'Images Upload' data entry has been removed, because it duplicates 'Image Upload'.
             * So all images_upload meta of properties should be moved to image_upload.
             */
            $wpdb->query( "
              UPDATE {$wpdb->postmeta}
	              SET meta_key='image_upload'
	              WHERE meta_key='images_upload'
		              AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type='property' );
            " );

          // To change the title key from "_widget_title" to "title"
          case ( version_compare( $old_version, '2.1.9', '<' ) ):
            $property_terms_widget = get_option('widget_wpp_property_terms');
            $property_terms_widget_updated = false;
            if(is_array($property_terms_widget)){
              foreach ($property_terms_widget as $id => $widget) {
                if(isset($widget['_widget_title'])){
                  $property_terms_widget[$id]['title'] = $widget['_widget_title'];
                  unset($property_terms_widget[$id]['_widget_title']);
                  $property_terms_widget_updated = true;
                }
              }
            }
            if($property_terms_widget_updated)
              update_option('widget_wpp_property_terms', $property_terms_widget);

          // Saving if this is a old install
          case ( version_compare( $old_version, '2.2.1', '<' ) ):
            update_option( 'wpp_legacy_2_2_0_2', $old_version );

        }
        /* Additional stuff can be handled here */
        do_action( 'wp-property::upgrade', $old_version, $new_version );
      }

      /**
       * Saves backup of WPP settings to uploads and to DB.
       *
       * @param $old_version
       * @param $new_version
       */
      static public function do_backup( $old_version, $new_version ) {
        /* Do automatic Settings backup! */
        $settings = get_option( 'wpp_settings' );

        if( !empty( $settings ) ) {

          /**
           * Fixes allowed mime types for adding download files on Edit Product page.
           *
           * @see https://wordpress.org/support/topic/2310-download-file_type-missing-in-variations-filters-exe?replies=5
           * @author peshkov@UD
           */
          add_filter( 'upload_mimes', function( $t ){
            if( !isset( $t['json'] ) ) {
              $t['json'] = 'application/json';
            }
            return $t;
          }, 99 );

          $filename = md5( 'wpp_settings_backup' ) . '.json';
          $upload = wp_upload_bits( $filename, null, json_encode( $settings ) );

          if( !empty( $upload ) && empty( $upload[ 'error' ] ) ) {
            if( isset( $upload[ 'error' ] ) ) unset( $upload[ 'error' ] );
            $upload[ 'version' ] = $old_version;
            $upload[ 'time' ] = time();
            update_option( 'wpp_settings_backup', $upload );
          }

        }
      }

    }

  }

}
