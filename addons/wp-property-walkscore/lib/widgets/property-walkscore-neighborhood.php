<?php
/**
 * Widget: Walk Score Neighborhood Map
 *
 * @author peshkov@UD
 */

class Property_Walkscore_Neighborhood_Widget extends WP_Widget {

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {
    parent::__construct(
      'property_walkscore_neighborhood',
      __( 'Walk Score Neighborhood Map', ud_get_wpp_walkscore( 'domain' ) ),
      array( 'description' => sprintf( __( 'Renders Neighborhood Map for current or specified %s.', ud_get_wpp_walkscore( 'domain' ) ), \WPP_F::property_label() ), ) // Args
    );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function widget( $args, $instance ) {

    $html = '';
    if( !empty( $instance[ 'latitude' ] ) && !empty( $instance[ 'longitude' ] ) ) {
      $html = do_shortcode( "[property_walkscore_neighborhood ws_width=100% ws_layout=vertical ws_lat=\"{$instance[ 'latitude' ]}\" ws_lon=\"{$instance[ 'longitude' ]}\" ]" );
    } elseif( !empty( $instance[ 'property_id' ] ) ) {
      $html = do_shortcode( "[property_walkscore_neighborhood ws_width=100% ws_layout=vertical property_id={$instance[ 'property_id' ]}]" );
    } else {
      $html = do_shortcode( "[property_walkscore_neighborhood ws_width=100% ws_layout=vertical]" );
    }

    if( empty( $html ) ) {
      return;
    }

    echo $args['before_widget'];
    if ( ! empty( $instance['title'] ) ) {
      echo $args['before_title'] . apply_filters( 'property_walkscore_neighborhood::title', $instance['title'] ). $args['after_title'];
    }

    echo $html;

    echo $args['after_widget'];
  }

  /**
   * Outputs the options form on admin
   *
   * @param array $instance The widget options
   */
  public function form( $instance ) {
    $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
    $property_id = ! empty( $instance['property_id'] ) ? $instance['property_id'] : '';
    $latitude = ! empty( $instance['latitude'] ) ? $instance['latitude'] : '';
    $longitude = ! empty( $instance['longitude'] ) ? $instance['longitude'] : '';
    ?>
    <p><?php printf( __( 'Note, widget is using predefined options set on %sWalk Score Settings%s page. If you need to use custom settings, you should use %s shortcode instead.', ud_get_wpp_walkscore( 'domain' ) ), '<a href="' . admin_url( 'edit.php?post_type=property&page=walkscore' ) . '">', '</a>', '<code>[property_walkscore_neighborhood]</code>' ); ?></p>
    <p><?php _e( 'To prevent view issues widget shows vertical layout.', ud_get_wpp_walkscore( 'domain' ) ); ?></p>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title <i>(optional)</i>:', ud_get_wpp_walkscore( 'domain' ) ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'property_id' ); ?>"><?php _e( 'Property ID <i>(required, if it\'s used on non-property page)</i>:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'property_id' ); ?>" name="<?php echo $this->get_field_name( 'property_id' ); ?>" type="text" value="<?php echo esc_attr( $property_id ); ?>">
    </p>
    <p>
    <p>
      <label for="<?php echo $this->get_field_id( 'latitude' ); ?>"><?php _e( 'Custom Latitude <i>(optional.)</i>:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'latitude' ); ?>" name="<?php echo $this->get_field_name( 'latitude' ); ?>" type="text" value="<?php echo esc_attr( $latitude ); ?>">
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'longitude' ); ?>"><?php _e( 'Custom Longitude <i>(optional)</i>:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'longitude' ); ?>" name="<?php echo $this->get_field_name( 'longitude' ); ?>" type="text" value="<?php echo esc_attr( $longitude ); ?>">
    </p>
    <?php
  }

  /**
   * Processing widget options on save
   *
   * @param array $new_instance The new options
   * @param array $old_instance The previous options
   */
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['property_id'] = ( ! empty( $new_instance['property_id'] ) ) ? strip_tags( $new_instance['property_id'] ) : '';
    $instance['latitude'] = ( ! empty( $new_instance['latitude'] ) ) ? strip_tags( $new_instance['latitude'] ) : '';
    $instance['longitude'] = ( ! empty( $new_instance['longitude'] ) ) ? strip_tags( $new_instance['longitude'] ) : '';
    return $instance;
  }

}

add_action( 'widgets_init', function(){
  register_widget( 'Property_Walkscore_Neighborhood_Widget' );
});