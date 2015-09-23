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
  class PropertyMapWidget extends \WP_Widget {

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
     * Form handler
     *
     * @param array $instance
     * @return bool
     *
     * @todo: Do options form
     */
    public function form($instance) {

      $_shortcode = \UsabilityDynamics\Shortcode\Manager::get_by( 'id', 'property_address_map' );

      if ( is_object( $_shortcode ) && !empty( $_shortcode->params ) && is_array( $_shortcode->params ) ) {

        foreach( $_shortcode->params as $param ) : ?>

          <p>
            <label class="widefat" for="<?php echo $this->get_field_id( $param['id'] ); ?>"><?php echo $param['name']; ?></label>

            <?php
              switch( $param['type'] ) {
                case 'text':
            ?>
                  <input class="widefat" id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                    name="<?php echo $this->get_field_name( $param['id'] ); ?>" type="text"
                    value="<?php echo !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default']; ?>"/>
            <?php
                  break;
                case 'number':
                ?>
                  <input class="widefat" id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                    min="<?php echo $param['min']; ?>"
                    name="<?php echo $this->get_field_name( $param['id'] ); ?>" type="number"
                    value="<?php echo !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default']; ?>"/>
                <?php
                  break;
                case 'select':
            ?>
                  <select class="widefat" id="<?php echo $this->get_field_id( $param['id'] ); ?>"
                    name="<?php echo $this->get_field_name( $param['id'] ); ?>">
            <?php
                  if ( !empty( $param['options'] ) && is_array( $param['options'] ) ) {
                    foreach( $param['options'] as $opt_name => $opt_label ) {
            ?>
                      <option value="<?php echo $opt_name; ?>" <?php selected( $opt_name, !empty( $instance[ $param['id'] ] ) ? $instance[ $param['id'] ] : $param['default'] ) ?>><?php echo $opt_label; ?></option>
            <?php
                    }
                  }
            ?>
                  </select>
            <?php
                  break;
            ?>
            <?php } ?>
          </p>

        <?php endforeach;

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
    if( class_exists( 'UsabilityDynamics\WPP\Widgets\PropertyMapWidget' ) ) {
      register_widget( 'UsabilityDynamics\WPP\Widgets\PropertyMapWidget' );
    }
  });
}