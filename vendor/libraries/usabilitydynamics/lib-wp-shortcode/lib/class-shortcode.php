<?php
/**
 * Shortcode class
 * Declares shortcodes.
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

  if( !class_exists( 'UsabilityDynamics\Shortcode\Shortcode' ) ) {

    class Shortcode {
    
      /**
       * Unique identifier.
       * Can be set in constructor.
       *
       * @value string
       */
      public $id = '';
    
      /**
       * Shortcode params.
       * Can be set in constructor.
       * See param structure in self::_param_sync();
       *
       * @value array
       */
      public $params = array();
      
      /**
       * Shortcode description
       * Can be set in constructor.
       *
       * @value string
       */
      public $description = '';
      
      /**
       * Group
       * Example: 'WP-Property', 'WP-Invoice', etc
       * default is 'Default'.
       * Can be set in constructor.
       *
       * @value array
       */
      public $group = array( 
        'id' => 'default' ,
        'name' => 'Default',
      );
      
      /**
       *
       */
      private $errors = array();
      
      /**
       * Constructor.
       * Inits shortcode and adds it to global variable $_shortcodes
       *
       */
      public function __construct( $options = array() ) {
        
        // Set properties
        if( is_array( $options ) ) {
          foreach( $options as $k => $v ) {
            if( in_array( $k, array( 'id', 'params', 'description', 'group' ) ) ) {
              if( $k == 'group' ) {
                $this->group = array( 
                  'id' => sanitize_key( $v ),
                  'name' => $v,
                );
              } else {
                $this->{$k} = $v;
              }
            }
          }
        }
        // All params must have the same structure
        if( is_array( $this->params ) ) {
          foreach( $this->params as $k => $val ) {
            $this->params[ $k ] = $this->_param_sync( $k, $val );
          }
        }
        
        // Add current shortcode to global variable
        $r = Manager::add( $this );
        if( is_wp_error( $r ) ) {
          $this->errors[] = $r;
        }

      }
      
      /**
       * Must be rewritten by child class
       *
       */
      public function call( $params ) {
        return null;
      }
      
      /**
       * Param's schema
       *
       */
      private function _param_sync( $k, $v ) {
        $v = wp_parse_args( $v, array(
          'id' => $k,
          'name' => '',
          'description' => '',
          'is_multiple' => false,
          'type' => 'string', // boolean, string, number
          'enum' => array(),
          'default' => '', // default value description
        ) );
        return $v;
      }
      
      /**
       * Determine if there are errors
       */
      public function has_error() {
        return !empty( $this->errors ) ? true : false;
      }
      
      /**
       * Returns the list of errors
       */
      public function get_errors() {
        return $this->errors;
      }

    }

  }

}