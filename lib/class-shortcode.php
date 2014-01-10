<?php
/**
 * WP-Property Shortcode
 * Declares shortcodes.
 *
 * @author potanin@UD
 * @author peshkov@UD
 * @author korotkov@UD
 * @version 1.0.0
 * @package WP-Property
 * @subpackage Shortcodes
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Shortcode' ) ) {

    class Shortcode {
    
      /**
       * Unique identifier.
       * Can be set in constructor.
       *
       * @value string
       */
      var $id = '';
    
      /**
       * Shortcode params.
       * Can be set in constructor.
       * See param structure in self::_param_sync();
       *
       * @value array
       */
      var $params = array();
      
      /**
       * Shortcode description
       * Can be set in constructor.
       *
       * @value string
       */
      var $description = '';
      
      /**
       * Group
       * Example: 'WP-Property', 'WP-Invoice', etc
       * default is 'Default'.
       * Can be set in constructor.
       *
       * @value string
       */
      var $group = 'Default';
      
      /**
       * Constructor.
       * Inits shortcode and adds it to global variable $_shortcodes
       *
       */
      public function __construct( $options = array() ) {
        global $_shortcodes;
      
        if( !is_array( $_shortcodes ) ) {
          $_shortcodes = array();
        }
        
        // Set properties
        if( is_array( $options ) ) {
          foreach( $options as $k => $v ) {
            if( in_array( $k, array( 'id', 'params', 'description', 'group' ) ) ) {
              $this->{$k} = $v;
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
        $group = sanitize_key( $this->group );
        if( !isset( $_shortcodes[ $group ] ) || !is_array( $_shortcodes[ $group ] ) ) {
          $_shortcodes[ $group ] = array( 
            'name' => $this->group,
            'properties' => array(),
          );
        }
        $this->group = $group;
        array_push( $_shortcodes[ $group ][ 'properties' ], $this );
        
        // Now, we add shortcode to WP
        add_shortcode( $this->id, array( $this, 'call' ) );
        
        return $this;
      }
      
      /**
       * Must be rewritten by child class
       *
       */
      public function call( $params ) {
        
      }
      
      /**
       * Param's schema
       *
       */
      private function _param_sync( $k, $v ) {
        $v = wp_parse_args( $v, array(
          'key' => $k,
          'name' => '',
          'description' => '',
          'is_multiple' => false,
          'type' => 'string', // boolean, string, number
          'values' => array(),
          'default' => '', // default value description
        ) );
        return $v;
      }

      /**
       * Declare and Add Shortcodes
       *
       * @todo Integrate with \UsabilityDynamics\WPP\Utility::export_listings();
       * @since 2.0
       */
      static function initialize() {
        global $wp_properties, $shortcode_tags;

        $shortcodes = array_keys( (array) $shortcode_tags );

        $wp_properties[ 'shortcodes' ] = array(
          'map'            => 'property_map',
          'attribute'      => 'property_attribute',
          'attributes'     => 'property_attributes',
          'taxonomy_terms' => 'taxonomy_terms',
          'overview'       => 'property_overview',
          'search'         => 'property_search',
          'image'          => 'property_primary_image'
        );

        //** Add shortcodes with different variations */
        foreach( $wp_properties[ 'shortcodes' ] as $short_name => $function ) {
          add_shortcode( WPP_Object . '_' . $short_name, array( 'WPP_Shortcodes', $function ) );
          add_shortcode( WPP_Object . '-' . $short_name, array( 'WPP_Shortcodes', $function ) );
        }

        //** Non-dynamic Shortcodes */
        add_shortcode( 'featured_properties', array( 'WPP_Shortcodes', 'featured_properties' ) );
        add_shortcode( 'featured-properties', array( 'WPP_Shortcodes', 'featured_properties' ) );

        /** Shortcodes: Agents Feature Fallback */
        if( !class_exists( 'class_agents' ) ) {
          add_shortcode( 'agent_card', array( 'WPP_Shortcodes', 'missing_feature' ) );
        }

        /** Shortcode: Supermap Feature Fallback */
        if( !class_exists( 'class_wpp_supermap' ) ) {
          add_shortcode( 'supermap', array( 'WPP_Shortcodes', 'missing_feature' ) );
        }

        /** Shortcode: Slideshow Feature Fallback */
        if( !class_exists( 'class_wpp_slideshow' ) ) {
          add_shortcode( WPP_Object . '_slideshow', array( 'WPP_Shortcodes', 'missing_feature' ) );
          add_shortcode( WPP_Object . '_gallery', array( 'WPP_Shortcodes', 'missing_feature' ) );
          add_shortcode( 'global_ slideshow', array( 'WPP_Shortcodes', 'missing_feature' ) );
        }

        /* Load list-attachments shortcode if the List Attachments Shortcode plugin does not exist */
        if( !in_array( 'list-attachments', (array) $shortcodes ) ) {
          add_shortcode( 'list_attachments', array( 'WPP_Shortcodes', 'list_attachments' ) );
        }

        add_shortcode( 'property_body', array( 'WPP_Shortcodes', 'property_body' ) );

      }

      

      

    }

  }

}