<?php
/**
 * WP List Table Loader
 *
 * @since 1.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPLT {

  if( !class_exists( 'UsabilityDynamics\WPLT\Bootstrap' ) ) {

    final class Bootstrap {

      /**
       * Loader
       *
       * @author peshkov@UD
       */
      public function __construct(){

        if ( defined('DOING_AJAX') && DOING_AJAX ) {
          // Load AJAX Handler once!
          if( !defined( 'WP_LIST_TABLE_AJAX' ) ) {
            define( 'WP_LIST_TABLE_AJAX', true );
            new Ajax();
          }
        }

      }

    }

  }

}
