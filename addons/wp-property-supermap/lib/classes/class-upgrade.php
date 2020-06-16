<?php
/**
 * WP-Property-Supermap Upgrade Handler
 *
 * @since 4.0.6
 * @author alim@UD
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Supermap_Upgrade' ) ) {

    class Supermap_Upgrade {

      /**
       * Run Upgrade Process
       *
       * @param $old_version
       * @param $new_version
       */
      static public function run( $old_version, $new_version ) {
        global $wpdb;

        /**
         * Specific upgrade conditions.
         */
        switch( true ) {

          case ( version_compare( $old_version, '4.0.6', '<' ) ):
            /*
             * Remove supermap_marker where it's set to default_google_map_marker.
             */
            $wpdb->query( "
              DELETE FROM {$wpdb->postmeta}
                WHERE meta_key = 'supermap_marker'
                  AND meta_value = 'default_google_map_marker' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type='property' );
            " );
        }
        /* Additional stuff can be handled here */
        do_action( 'wp-property-supermap::upgrade', $old_version, $new_version );
      }

    }

  }

}
