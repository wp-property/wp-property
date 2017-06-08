<?php
/**
 * Shortcode Manager class
 * Manage shortcodes
 * 
 * @author peshkov@UD
 * @version 0.1.0
 * @package UsabilityDynamics
 * @subpackage Shortcode
 */
namespace UsabilityDynamics\Shortcode {

  if( !class_exists( 'UsabilityDynamics\Shortcode\Manager' ) ) {

    class Manager {
    
      /**
       * The list of available shortcodes
       *
       * @type array
       * @author peshkov@UD
       */
      private static $shortcodes = array();
    
      /**
       * Get array of available shortcodes objects.
       *
       * @param array $options. 'group' - get shortcodes for passed group. Default is false. 'grouped' - get shortcodes goruped by groupes.
       * @return array
       * @author peshkov@UD
       */
      static public function get( $options = array() ) {
        
        $shortcodes = array();
      
        $options = wp_parse_args( $options, array(
          'group' => false,
          'grouped' => false,
        ) );
        
        if( !empty( $options[ 'group' ] ) ) {
          $group = sanitize_key( $options[ 'group' ] );
          foreach( self::$shortcodes as $k => $v ) {
            if( $v->group[ 'id' ] == $group ) {
              $shortcodes[ $k ] = $v;
            }
          }
        } else {
          if ( $options[ 'grouped' ] ) {
            foreach( self::$shortcodes as $k => $v ) {
              if( !isset( $shortcodes[ $v->group[ 'id' ] ] ) || !is_array( $shortcodes[ $v->group[ 'id' ] ] ) ) {
                $shortcodes[ $v->group[ 'id' ] ] = array(
                  'name' => $v->group[ 'name' ],
                  'properties' => array(),
                );
              }
              $shortcodes[ $v->group[ 'id' ] ][ 'properties' ][ $k ] = $v;
            }
          } else {
            $shortcodes = self::$shortcodes;
          }
        }
        
        return $shortcodes;
      }
      
      /**
       * Returns shortcode's object by passed property ( key )
       *
       * @param string $property.
       * @param string $value.
       * @param boolean $single. Returns the list of objects or single object
       * @return mixed Array or Object
       * @author peshkov@UD
       */
      static public function get_by( $property, $value, $single = true ) {
        $_shortcodes = array();
        foreach( self::$shortcodes as $k => $v ) {
          if( isset( $v->{$property} ) && $v->{$property} == $value ) {
            array_push( $_shortcodes, $v );
            if( $single ) {
              break;
            }
          }
        }
        return $single ? ( !empty( $_shortcodes[0] ) ? $_shortcodes[0] : null ) : $_shortcodes;
      }
      
      /**
       * Adds shortcode object to shortcodes list.
       *
       * @param UsabilityDynamics\Shortcode\Shortcode $shortcode
       * @return boolean
       * @author peshkov@UD
       */
      static public function add( $shortcode ) {
        
        // Determine if passed param is valid
        try {
          //@TODO: check if class or base class of object is Shortcode.
          if( !is_object( $shortcode ) ) {
            throw new \Exception( __( 'Param is not an object or doesn\'t extend UsabilityDynamics\\Shortcode\\Shortcode class' ) );
          }
          if ( key_exists( $shortcode->id, self::$shortcodes ) ) {
            throw new \Exception( __( 'Shortcode is already added. It can not be added twice.' ) );
          }
          if ( shortcode_exists( $shortcode->id ) ) {
            throw new \Exception( __( 'Shortcode with provided tag is already registered in Wordpress. You must remove existing shortcode before adding new one.' ) );
          }
        } catch ( \Exception $e ) {
          return new \WP_Error( 'error', __( 'Shortcode can not be added' ) . ': ' . $e->getMessage() );
        }
        
        self::$shortcodes[ $shortcode->id ] = $shortcode;
        
        // Now, we add shortcode to WP
        add_shortcode( $shortcode->id, array( $shortcode, 'call' ) );
        
        return true;
      }
      
      /**
       * Removes shortcode from the list and unregister it in WP
       *
       * @param string $id Unique ID of shortcode
       * @return boolean
       * @author peshkov@UD
       */
      static public function remove( $id ) {
        
        if( !key_exists( $id, self::$shortcodes ) ) {
          return false;
        }
        // Remove from the manager list
        unset( self::$shortcodes[ $id ] );
        // Remove from Wordpress
        if ( shortcode_exists( $id ) ) { 
          remove_shortcode( $id );
        }
        
        return true;
      }

    }

  }

}
