<?php

/**
 * Property Overview Widget
 */
namespace UsabilityDynamics\WPP\Widgets {

  /**
   * Class PropertyTaxonomiesWidget
   *
   * @package UsabilityDynamics\WPP\Widgets
   */
  class PropertyOverviewWidget extends \UsabilityDynamics\WPP\Widget {

    /**
     * @var string
     */
    public $shortcode_id = 'property_overview';

    /**
     * Init
     */
    public function __construct() {
      parent::__construct( 'wpp_property_overview', $name = sprintf( __( '%1s Overview', ud_get_wp_property()->domain ), \WPP_F::property_label( 'singular' ) ), array( 'description' => __( 'Property Overview Widget', ud_get_wp_property()->domain ) ) );
    }

    /**
     * Widget body
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
      echo do_shortcode( '[property_overview '.$this->shortcode_args( $instance ).']' );
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
    if( class_exists( 'UsabilityDynamics\WPP\Widgets\PropertyOverviewWidget' ) ) {
      register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyOverviewWidget' );
    }
  });
}