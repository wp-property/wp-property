<?php

/**
 * Property Meta Widget
 */
namespace UsabilityDynamics\WPP\Widgets {

  /**
   * Class PropertyMetaWidget
   *
   * @package UsabilityDynamics\WPP\Widgets
   */
  class PropertyMetaWidget extends \UsabilityDynamics\WPP\Widget {

    /**
     * @var string
     */
    public $shortcode_id = 'property_meta';

    /**
     * Init
     */
    public function __construct() {
      parent::__construct( 'wpp_property_meta', $name = sprintf( __( '%1s Meta', ud_get_wp_property()->domain ), \WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'Widget for Single Property page that renders property meta.', ud_get_wp_property()->domain ) ) );
    }

    /**
     * Widget body
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
      echo do_shortcode( '[property_meta '.$this->shortcode_args( $instance ).']' );
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
    if( class_exists( 'UsabilityDynamics\WPP\Widgets\PropertyMetaWidget' ) ) {
      register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyMetaWidget' );
    }
  });
}