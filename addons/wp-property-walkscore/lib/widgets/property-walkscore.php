<?php
/**
 * Widget: Walk Score
 *
 * @author peshkov@UD
 */

class Property_Walkscore_Widget extends WP_Widget {

  /**
   * Sets up the widgets name etc
   */
  public function __construct() {
    parent::__construct(
      'property_walkscore',
      __( 'Walk Score', ud_get_wpp_walkscore( 'domain' ) ),
      array( 'description' => sprintf( __( 'Renders Walk Score for current or specified %s.', ud_get_wpp_walkscore( 'domain' ) ), \WPP_F::property_label() ), ) // Args
    );
  }

  /**
   * Outputs the content of the widget
   *
   * @param array $args
   * @param array $instance
   */
  public function widget( $args, $instance ) {

    $view = !empty( $instance[ 'view' ] ) ? $instance[ 'view' ] : ud_get_wpp_walkscore( 'config.walkscore.view', 'text' );
    $type = !empty( $instance[ 'type' ] ) ? $instance[ 'type' ] : ud_get_wpp_walkscore( 'config.walkscore.type', 'free' );

    if( !empty( $instance[ 'property_id' ] ) && is_numeric( $instance[ 'property_id' ] ) ) {
      $property_id = $instance[ 'property_id' ];
    } else {
      global $post;
      if( empty( $post ) || !is_object( $post ) || !isset( $post->ID ) ) {
        return;
      }
      $property_id = $post->ID;
    }

    $html = do_shortcode( "[property_walkscore ws_view={$view} ws_type={$type} property_id={$property_id} ]" );

    if( empty( $html ) ) {
      return;
    }

    echo $args['before_widget'];

    echo $html;

    echo $args['after_widget'];
  }

  /**
   * Outputs the options form on admin
   *
   * @param array $instance The widget options
   */
  public function form( $instance ) {
    $property_id = ! empty( $instance['property_id'] ) ? $instance['property_id'] : '';
    $view = ! empty( $instance['view'] ) ? $instance['view'] : '';
    $type = ! empty( $instance['type'] ) ? $instance['type'] : '';
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'property_id' ); ?>"><?php _e( 'Property ID <i>(required, if it\'s used on non-property page)</i>:', ud_get_wpp_walkscore('domain') ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'property_id' ); ?>" name="<?php echo $this->get_field_name( 'property_id' ); ?>" type="text" value="<?php echo esc_attr( $property_id ); ?>">
    </p>
    <p>
    <p>
      <label for="<?php echo $this->get_field_id( 'view' ); ?>"><?php _e( 'View <i>(optional.)</i>:', ud_get_wpp_walkscore('domain') ); ?></label>
      <select name="<?php echo $this->get_field_name( 'view' ); ?>" class="widefat">
        <option <?php echo esc_attr( $view ) == 'text' ? 'selected="selected"' : ''; ?>>text</option>
        <option <?php echo esc_attr( $view ) == 'icon' ? 'selected="selected"' : ''; ?>>icon</option>
        <option <?php echo esc_attr( $view ) == 'badge' ? 'selected="selected"' : ''; ?>>badge</option>
      </select>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Type <i>(optional.)</i>:', ud_get_wpp_walkscore('domain') ); ?></label>
      <select name="<?php echo $this->get_field_name( 'type' ); ?>" class="widefat">
        <option <?php echo esc_attr( $type ) == 'free' ? 'selected="selected"' : ''; ?>>free</option>
        <option <?php echo esc_attr( $type ) == 'premium' ? 'selected="selected"' : ''; ?>>premium</option>
      </select>
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
    $instance['property_id'] = ( ! empty( $new_instance['property_id'] ) ) ? strip_tags( $new_instance['property_id'] ) : '';
    $instance['view'] = ( ! empty( $new_instance['view'] ) ) ? strip_tags( $new_instance['view'] ) : '';
    $instance['type'] = ( ! empty( $new_instance['type'] ) ) ? strip_tags( $new_instance['type'] ) : '';
    return $instance;
  }

}

add_action( 'widgets_init', function(){
  register_widget( 'Property_Walkscore_Widget' );
});