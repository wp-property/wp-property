<?php

/**
 * Property Terms Widget
 */
namespace UsabilityDynamics\WPP\Widgets {

  /**
   * Class PropertyTermsWidget
   *
   * @package UsabilityDynamics\WPP\Widgets
   */
  class PropertyTermsWidget extends \UsabilityDynamics\WPP\Widget {

    /**
     * @var string
     */
    public $shortcode_id = 'property_terms';

    /**
     * Init
     */
    public function __construct() {
      parent::__construct( WPP_LEGACY_WIDGETS ? 'wpp_property_terms' : 'wpp_property_terms_v2', $name = sprintf( __( '%1s Terms', ud_get_wp_property()->domain ), \WPP_F::property_label() ), array( 'description' => __( 'Renders property terms for specific taxonomy.', ud_get_wp_property()->domain ) ) );
    }

    /**
     * Widget body
     *
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
      $before_widget = '';
      $after_widget = '';
      $before_title = '';
      $after_title = '';
      extract( $args );
      $output = "";

      $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';

      // Clearing title to avoid duplicate in widget.
      $instance[ 'title' ] = '';
      $output .= $before_widget;
      $_shortcode_return = do_shortcode( '[property_terms '.$this->shortcode_args( $instance ).']' );
      if ( !empty( $title ) ) {
        $output .= $before_title . $title . $after_title;
      }
      $output .= $_shortcode_return;
      $output .= $after_widget;
      if( trim($_shortcode_return) != '')
        echo $output;
    }

    /**
     * Renders form based on Shortcode's params
     *
     * @param array $instance
     */
    public function form( $instance ) {
      parent::form( $instance );
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
    register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyTermsWidget' );
  });
}