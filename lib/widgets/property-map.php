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
    public $shortcode_id = 'property_map';

    /**
     * Init
     */
    public function __construct() {
      parent::__construct( 'wpp_property_map', $name = sprintf( __( '%1s Map', ud_get_wp_property()->domain ), \WPP_F::property_label() ), array( 'description' => __( 'Widget for Single Property page that renders property address map.', ud_get_wp_property()->domain ) ) );
    }

    /**
     * Widget body
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
      echo do_shortcode( '[property_map '.$this->shortcode_args( $instance ).']' );
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
  add_action( 'widgets_init', function() {

    /**
     * Register if class exists
     */
    if( class_exists( 'UsabilityDynamics\WPP\Widgets\PropertyMapWidget' ) ) {
      register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyMapWidget' );
    }
  });
}