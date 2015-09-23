<?php

/**
 * Property Map Widget
 */
namespace UsabilityDynamics\WPP\Widgets {

  /**
   * Class PropertyMapWidget
   *
   * @package UsabilityDynamics\WPP\Widgets
   */
  class PropertyMapWidget extends \UsabilityDynamics\WPP\Widget {

    /**
     * @var string
     */
    public $shortcode_id = 'property_address_map';

    /**
     * Init
     */
    public function __construct() {
      parent::__construct( 'wpp_property_map', $name = sprintf( __( '%1s Map', ud_get_wp_property()->domain ), \WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'Widget for Single Property page that renders property address map.', ud_get_wp_property()->domain ) ) );
    }

    /**
     * Widget body
     *
     * @param array $args
     * @param array $instance
     *
     * @todo: Consider widget options
     */
    public function widget( $args, $instance ) {

      $args = array();

      if ( !empty( $instance ) && is_array( $instance ) ) {
        foreach( $instance as $attr_name => $attr_value ) {
          $args[] = $attr_name . '="'.$attr_value.'"';
        }
      }

      echo do_shortcode( '[property_address_map '.implode( ' ', $args ).']' );
    }

    /**
     * Update handler
     *
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    public function update( $new_instance, $old_instance ) {
      return $new_instance;
    }
  }

  /**
   * Register this widget
   */
  add_action( 'wpp_register_widgets', function() {

    /**
     * Register if class exists
     */
    if( class_exists( 'UsabilityDynamics\WPP\Widgets\PropertyMapWidget' ) ) {
      register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyMapWidget' );
    }
  });
}