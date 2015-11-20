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
      parent::__construct( 'wpp_property_meta', $name = sprintf( __( '%1s Meta', ud_get_wp_property()->domain ), \WPP_F::property_label() ), array( 'description' => __( 'Widget for Single Property page that renders property meta.', ud_get_wp_property()->domain ) ) );
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

      $title = isset( $instance[ '_widget_title' ] ) ? $instance[ '_widget_title' ] : '';

      $output .= $before_widget;
      $_shortcode_return = do_shortcode( '[property_meta '.$this->shortcode_args( $instance ).']' );
      if ( !empty( $title )) {
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
      ?>
      <p>
        <label class="widefat" for="<?php echo $this->get_field_id( '_widget_title' ); ?>"><?php _e( 'Title', ud_get_wp_property( 'domain' ) ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( '_widget_title' ); ?>"
               name="<?php echo $this->get_field_name( '_widget_title' ); ?>" type="text"
               value="<?php echo !empty( $instance[ '_widget_title' ] ) ? $instance[ '_widget_title' ] : ''; ?>"/>
        <span class="description"><?php _e( 'Widget\'s Title', ud_get_wp_property( 'domain' ) ); ?></span>
      </p>
      <?php
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
    register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyMetaWidget' );
  });
}