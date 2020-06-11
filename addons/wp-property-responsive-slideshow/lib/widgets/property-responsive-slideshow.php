<?php

/**
 * Property Responsive Slideshow Widget
 */
namespace UsabilityDynamics\WPP {

  /**
   * Class Property_Responsive_Slideshow_Widget
   *
   */
  class Property_Responsive_Slideshow_Widget extends Widget {

    /**
     * @var string
     */
    public $shortcode_id = 'property_responsive_slideshow';

    /**
     * Init
     */
    public function __construct() {
      parent::__construct(
        'property_responsive_slideshow',
        $name = __( 'Property Responsive Slideshow', ud_get_wpp_resp_slideshow()->domain ),
        array( 'description' => __( 'Renders Property Responsive Slideshow', ud_get_wpp_resp_slideshow()->domain ) )
      );
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

      $title = isset( $instance[ '_widget_title' ] ) ? $instance[ '_widget_title' ] : '';

      echo $before_widget;
      if ( !empty( $title ) ) {
        echo $before_title . $title . $after_title;
      }
      echo do_shortcode( '[property_responsive_slideshow '.$this->shortcode_args( $instance ).']' );
      echo $after_widget;
    }

    /**
     * Renders form based on Shortcode's params
     *
     * @param array $instance
     */
    public function form( $instance ) {
      $grid_image_size = isset( $instance[ 'grid_image_size' ] ) ? esc_attr( $instance[ 'grid_image_size' ] ) : 'medium';
      ?>
      <p>
        <label class="widefat" for="<?php echo $this->get_field_id( '_widget_title' ); ?>"><?php _e( 'Title', ud_get_wpp_resp_slideshow()->domain ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id( '_widget_title' ); ?>"
               name="<?php echo $this->get_field_name( '_widget_title' ); ?>" type="text"
               value="<?php echo !empty( $instance[ '_widget_title' ] ) ? $instance[ '_widget_title' ] : ''; ?>"/>
        <span class="description"><?php _e( 'Widget\'s Title', ud_get_wpp_resp_slideshow()->domain ); ?></span>
      </p>
      <?php
      parent::form( $instance );
      ?>
      <p>
        <label for="<?php echo $this->get_field_id( 'grid_image_size' ); ?>"><?php _e( 'Image size for grid:', ud_get_wp_property()->domain ); ?>
          <?php \WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'grid_image_size' ) . "&selected=" . $grid_image_size ); ?>
        </label>
        <span class="description"><?php _e( 'Image size for 12grid and 12mosaic. Will not apply on first image.', ud_get_wpp_resp_slideshow()->domain ); ?></span>
      </p>
      <?php
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
    register_widget( 'UsabilityDynamics\WPP\Property_Responsive_Slideshow_Widget' );
  });
}