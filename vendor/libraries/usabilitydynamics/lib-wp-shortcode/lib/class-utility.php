<?php
/**
 * Help class
 *
 * @author potanin@UD
 * @author peshkov@UD
 * @author korotkov@UD
 *
 * @version 0.1.0
 * @package UsabilityDynamics
 * @subpackage Shortcode
 */
namespace UsabilityDynamics\Shortcode {

  if( !class_exists( 'UsabilityDynamics\Shortcode\Utility' ) ) {

    class Utility {

      /**
       * Parses passed directory for shortcodes files
       * includes and adds shortcodes if they exist
       * 
       * @param string $path
       * @param boolean $cache
       * @author peshkov@UD
       */
      static public function maybe_load_shortcodes( $path, $cache = true ) {
        if ( !is_dir( $path ) ) {
          return null;
        }
        
        $_shortcodes = wp_cache_get( 'shortcodes', 'usabilitydynamics' );
        if( !is_array( $_shortcodes ) ) {
          $_shortcodes = array();
        }
        
        if( $cache && !empty( $_shortcodes[ $path ] ) && is_array( $_shortcodes[ $path ] ) ) {
          foreach( $_shortcodes[ $path ] as $_shortcode ) {
            include_once( $path . "/" . $_shortcode[ 'file' ] );
            new $_shortcode[ 'headers' ][ 'class' ]();
          }
          return null;
        }
        
        $_shortcodes[ $path ] = array();
        
        if ( $dir = @opendir( $path ) ) {
          $headers = array(
            'name' => 'Name',
            'id' => 'ID',
            'type' => 'Type',
            'group' => 'Group',
            'class' => 'Class',
            'version' => 'Version',
            'description' => 'Description',
          );
          while ( false !== ( $file = readdir( $dir ) ) ) {
            $data = @get_file_data( $path . "/" . $file, $headers, 'shortcode' );
            if( $data[ 'type' ] == 'shortcode' && !empty( $data[ 'class' ] ) ) {
              include_once( $path . "/" . $file );
              if( class_exists( $data[ 'class' ] ) ) {
                array_push( $_shortcodes[ $path ], array(
                  'file' => $file,
                  'headers' => $data,
                ) );
                new $data[ 'class' ]();
              }
            }
          }
        }
        
        wp_cache_set( 'shortcodes', $_shortcodes, 'usabilitydynamics' );
        
      }
      
      /**
       * Prepare arguments for shortcode
       *
       * @param $instance
       * @return string
       * @since 0.1.2
       */
      static public function prepare_args( $instance ) {
        $args = array();
        if ( !empty( $instance ) && is_array( $instance ) ) {
          foreach( $instance as $name => $value ) {
            if ( is_array( $value ) ) {
              $value = implode( ',', array_keys( $value ) );
            }
            $args[] = $name . '="' . $value . '"';
          }
        }
        return implode( ' ', $args );
      }
    
    }

  }

}
