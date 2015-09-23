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
  class PropertyMetaWidget extends \WP_Widget {

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
     *
     * @todo: Consider widget options
     */
    public function widget( $args, $instance ) {
      echo do_shortcode( '[property_meta]' );
    }

    /**
     * Form handler
     *
     * @param array $instance
     * @return bool
     *
     * @todo: Do options form
     */
    public function form($instance) {

      $_shortcode = \UsabilityDynamics\Shortcode\Manager::get_by( 'id', 'property_meta' );

      if ( is_object( $_shortcode ) && !empty( $_shortcode->params ) ) {

        echo '<pre>';
        print_r( $_shortcode->params );
        echo '</pre>';

      } else {
        _e( '<p>No options available.</p>', ud_get_wp_property()->domain );
      }

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
    if( class_exists( 'UsabilityDynamics\WPP\Widgets\PropertyMetaWidget' ) ) {
      register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyMetaWidget' );
    }
  });
}