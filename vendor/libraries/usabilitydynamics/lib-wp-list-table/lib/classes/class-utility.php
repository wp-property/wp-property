<?php
/**
 * Helper.
 *
 */
namespace UsabilityDynamics\WPLT {

  if( !defined( 'ABSPATH' ) ) {
    die();
  }

  if (!class_exists('UsabilityDynamics\WPLT\Utility')) {

    class Utility {

      /**
       *  Return root path to library.
       */
      static public function path( $shortpath, $type = 'dir' ) {
        $path = false;
        switch( $type ) {
          case 'dir':
            $path = plugin_dir_path( __FILE__ );
            $path = wp_normalize_path( $path );
            break;
          case 'url':
            $path = plugin_dir_url( __FILE__ );
            break;
        }
        if( $path ) {
          $path = str_replace( 'lib/classes/', '', $path );
          $path .= ltrim( $shortpath, '/\\' );
        }
        return $path;
      }

    }

  }

}