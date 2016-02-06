<?php

/**
 * Class ChildPropertiesWidget
 *
 * Deprecated Widget. Enable Legacy Features option on Settings page to activate it.
 */
class ChildPropertiesWidget extends \UsabilityDynamics\WPP\Widget {

  /**
   * Constructor
   */
  function __construct() {
    parent::__construct( false, $name = sprintf( __( 'Child %1s', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'Show child properties (if any) for currently displayed property', ud_get_wp_property()->domain ) ) );
  }

  /**
   * @see WP_Widget::widget
   */
  function widget( $args, $instance ) {
    global $post, $wp_properties;

    if ( !isset( $post->ID ) ) {
      return;
    }

    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract( $args );

    $instance = apply_filters( 'ChildPropertiesWidget', $instance );

    $data = array(
      'instance' => $instance,
      'before_title' => $before_title,
      'after_title' => $after_title,
      'title' => isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '',
      'show_title' => isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false,
      'image_type' => isset( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : '',
      'hide_image' => isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false,
      'stats' => isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array(),
      'address_format' => isset( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : '',
      'amount_items' => !empty( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : 5,
      'sort_by' => isset( $instance[ 'sort_by' ] ) ? $instance[ 'sort_by' ] : 'date',
      'sort_order' => isset( $instance[ 'sort_order' ] ) ? $instance[ 'sort_order' ] : 'DESC',
    );

    if ( !empty( $data[ 'image_type' ] ) ) {
      $data[ 'image_size' ] = WPP_F::image_sizes( $data[ 'image_type' ] );
    }

    $data[ 'properties' ] = get_posts( array(
        'post_type' => 'property',
        'numberposts' => $data[ 'amount_items' ],
        'post_status' => 'publish',
        'post_parent' => $post->ID,
        'orderby' => $data[ 'sort_by' ],
        'order' => $data[ 'sort_order' ],
        'suppress_filters' => 0
    ) );

    if ( count( $data[ 'properties' ] ) < 1 ) {
      return;
    }

    echo $before_widget;
    $this->get_template( 'child-properties', $data );
    echo $after_widget;
  }

  /**
   * Update
   *
   * @param array $new_instance
   * @param array $old_instance
   * @return array
   */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /**
   * Form
   *
   * @param array $instance
   */
  function form( $instance ) {
    global $wp_properties;
    $title = isset( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : '';
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $address_format = !empty( $instance[ 'address_format' ] ) ? esc_attr( $instance[ 'address_format' ] ) : "[street_number] [street_name], [city], [state]";
    $image_type = isset( $instance[ 'image_type' ] ) ? esc_attr( $instance[ 'image_type' ] ) : false;
    $amount_items = !empty( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : 5;
    $property_stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array();
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $enable_more = isset( $instance[ 'enable_more' ] ) ? $instance[ 'enable_more' ] : false;
    $enable_view_all = isset( $instance[ 'enable_view_all' ] ) ? $instance[ 'enable_view_all' ] : false;
    $sort_by = isset( $instance[ 'sort_by' ] ) ? $instance[ 'sort_by' ] : false;
    $sort_order = isset( $instance[ 'sort_order' ] ) ? $instance[ 'sort_order' ] : false;
    ?>
    <script type="text/javascript">
      //hide and show dropdown whith thumb settings
      jQuery( document ).ready( function ( $ ) {
        $( 'input.check_me_child' ).change( function () {
          if ( $( this ).attr( 'checked' ) !== true ) {
            $( 'p#choose_thumb_child' ).css( 'display', 'block' );
          } else {
            $( 'p#choose_thumb_child' ).css( 'display', 'none' );
          }
        } )
      } );
    </script>
    <p><?php _e( 'The widget will not be displayed if the currently viewed property has no children.', ud_get_wp_property()->domain ); ?></p>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', ud_get_wp_property()->domain ); ?>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
               name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>">
        <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me_child"
               name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
               value="on" <?php if ( $hide_image == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Hide Images?', ud_get_wp_property()->domain ); ?>
      </label>
    </p>
    <p id="choose_thumb_child" <?php
    if ( $hide_image !== 'on' )
      echo 'style="display:block;"';
    else
      echo 'style="display:none;"';
    ?>>
    <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', ud_get_wp_property()->domain ); ?>
      <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
    </label>

    <p>
      <label for="<?php echo $this->get_field_id( 'amount_items' ); ?>"><?php _e( 'Listings to display?', ud_get_wp_property()->domain ); ?>
        <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
               name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
               value="<?php echo $amount_items; ?>"/>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'sort_by' ); ?>"><?php _e( 'Sort By', ud_get_wp_property()->domain ); ?>
      <input id="<?php echo $this->get_field_id( 'sort_by' ); ?>"
             name="<?php echo $this->get_field_name( 'sort_by' ); ?>" type="text"
             value="<?php echo $sort_by; ?>"/>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'sort_order' ); ?>"><?php _e( 'Sort Order', ud_get_wp_property()->domain ); ?>
        <select id="<?php echo $this->get_field_id( 'sort_order' ); ?>" name="<?php echo $this->get_field_name( 'sort_order' ); ?>">
          <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected="selected"' : ''; ?>>ASC</option>
          <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected="selected"' : ''; ?>>DESC</option>
        </select>
      </label>
    </p>

    <p><?php _e( 'Select the stats you want to display', ud_get_wp_property()->domain ); ?></p>
    <ul class="wpp-multi-checkbox-wrapper">
      <li>
        <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
          <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
                 name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
                 value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
          <?php _e( 'Title', ud_get_wp_property()->domain ); ?>
        </label>
      </li>
      <?php foreach ( $wp_properties[ 'property_stats' ] as $stat => $label ): ?>
        <li>
          <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>">
            <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
                   name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
                <?php if ( is_array( $property_stats ) && in_array( $stat, $property_stats ) ) echo " checked "; ?> />

            <?php echo $label; ?>
          </label>
        </li>
      <?php endforeach; ?>
    </ul>
    <p>
      <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', ud_get_wp_property()->domain ); ?>
        <textarea style="width: 100%" id="<?php echo $this->get_field_id( 'address_format' ); ?>"
                  name="<?php echo $this->get_field_name( 'address_format' ); ?>"><?php echo $address_format; ?></textarea>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_more' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_more' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_more' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_more == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "More" link?', ud_get_wp_property()->domain ); ?>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "View All" link?', ud_get_wp_property()->domain ); ?>
      </label>
    </p>

  <?php
  }
}

/**
 * Register widget
 */
add_action( 'widgets_init', function() {
  if( ud_get_wp_property( 'configuration.enable_legacy_features' ) == 'true' ) {
    register_widget( "ChildPropertiesWidget" );
  }
});