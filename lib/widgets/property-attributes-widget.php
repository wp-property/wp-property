<?php

/**
 * Class Property_Attributes_Widget
 */
class Property_Attributes_Widget extends WP_Widget {

  /**
   * Initialize the widget.
   *
   * @since 1.31.0
   */
  function __construct() {

    $property_label = strtolower( WPP_F::property_label() );

    add_filter('wpp_get_attribute', array( $this, 'property_type_handler' ), 10, 2);

    parent::__construct(
        'wpp_property_attributes',
        sprintf( __( '%1s Attributes', 'wpp' ), WPP_F::property_label() ),
        array(
          'classname' => 'wpp_property_attributes',
          'description' => sprintf( __( 'Display a list of selected %1s attributes when loaded on a single %2s page.', 'wpp' ), $property_label, $property_label )
        ),
        array(
          'width' => 300
        )
    );

  }

  /**
   * Property type
   *
   * @param $val
   * @param $args
   * @return mixed
   */
  function property_type_handler( $val, $args ) {
    if ( $args['attribute'] == 'property_type' ) {
      return $args['property']['property_type_label'];
    }
    return $val;
  }

  /**
   * Handles any special functions when the widget is being updated.
   *
   * @since 1.31.0
   */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /**
   * Renders the widget on the front-end.
   *
   * @since 1.31.0
   */
  function widget( $args, $instance ) {
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    $html = array();
    extract( $args );

    if ( empty( $instance ) ) {
      return;
    }

    $html[ ] = $before_widget;

    if ( !empty( $instance[ 'title' ] ) ) {
      $html[ ] = $before_title . $instance[ 'title' ] . $after_title;
    }

    $show_labels = ( isset( $instance[ 'show_labels' ] ) && $instance[ 'show_labels' ] == 'true' ) ? true : false;

    unset( $instance[ 'show_labels' ] );
    unset( $instance[ 'title' ] );

    foreach ( $instance as $slug => $option ) {

      if ( $option != 'true' ) {
        continue;
      }

      $value = get_attribute( $slug, array( 'return' => 'true' ) );

      if ( empty( $value ) ) {
        continue;
      }

      if ( $show_labels ) {
        $attribute = UsabilityDynamics\WPP\Attributes::get_attribute_data( $slug );
      }

      $attributes[ ] = '<li class="' . $slug . '">' . ( $show_labels ? '<span class="attribute">' . $attribute[ 'label' ] . '<span class="separator">:</span> </span>' : '' ) . '</span><span class="value">' . $value . '</span></li>';
    }

    if ( !empty( $attributes ) ) {
      $html[ 'attributes' ] = '<ul class="wpp_widget_attribute_list">' . implode( '', $attributes ) . '</ul>';
    }

    $html[ ] = $after_widget;

    if ( !empty( $html[ 'attributes' ] ) ) {
      echo implode( '', $html );
    } else {
      return false;
    }

  }

  /**
   * Renders widget UI in control panel.
   *
   *
   * @todo Needs to make use of sortable attributes.
   * @uses $current_screen global variable
   * @since 1.31.0
   */
  function form( $instance ) {
    global $wp_properties;

    $main_options = array();
    $widget_options = ( is_array( $instance ) ? $instance : array() );

    foreach ( WPP_F::get_total_attribute_array() as $slug => $label ) {
      $main_options[ $slug ] = UsabilityDynamics\WPP\Attributes::get_attribute_data( $slug );
    }

    //** We don't want to mix in the title into our array */
    unset( $widget_options[ 'title' ] );
    unset( $widget_options[ 'show_labels' ] );

    //** Cycle through saved attributes, to maintain order, and remove any that are no longer in main list */
    foreach ( $widget_options as $slug => $selected ) {
      if ( !in_array( $slug, array_keys( $main_options ) ) ) {
        unset( $widget_options[ $slug ] );
      } else {
        $widget_options[ $slug ] = $main_options[ $slug ];
      }
    }

    //** Cycle through main options, and add any to array that are not already there */
    foreach ( $main_options as $slug => $data ) {
      if ( !in_array( $slug, array_keys( $widget_options ) ) ) {
        $widget_options[ $slug ] = $data;
      }
    }

    ?>
    <div class="wpp_widget" data-widget="property_attributes_widget" data-widget_number="<?php echo $this->number; ?>">
      <p>
        <label for="<?php echo $this->get_field_id( 'widget_title' ); ?>"><?php _e( 'Title:' ); ?>
          <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                 name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                 value="<?php echo !empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : ''; ?>"/>
        </label>
      </p>

      <p><?php _e( 'Attributes to Display:', 'wpp' ); ?></p>

      <div class="wpp_subtle_tabs wp-tab-panel">
        <ul class="wpp_sortable_attributes">
          <?php foreach ( $widget_options as $slug => $data ) { ?>
            <li class="wpp_attribute_wrapper">
              <input type="hidden" name="<?php echo $this->get_field_name( $slug ); ?>" value="false"/>
              <input class="checkbox" type="checkbox" <?php if( isset( $instance[ $slug ] ) ) checked( $instance[ $slug ], 'true' ) ?>
                     id="<?php echo $this->get_field_id( $slug ); ?>"
                     name="<?php echo $this->get_field_name( $slug ); ?>" value="true"/>
              <label for="<?php echo $this->get_field_id( $slug ); ?>"><?php echo $data[ 'label' ]; ?></label>
            </li>
          <?php } ?>
        </ul>
      </div>
      <ul>
        <li>
          <input class="checkbox" type="checkbox" <?php if( isset( $instance[ 'show_labels' ] ) ) checked( $instance[ 'show_labels' ], 'true' ) ?>
                 id="<?php echo $this->get_field_id( 'show_labels' ); ?>"
                 name="<?php echo $this->get_field_name( 'show_labels' ); ?>" value="true"/>
          <label
              for="<?php echo $this->get_field_id( 'show_labels' ); ?>"><?php _e( 'Display attributes labels.', 'wpp' ); ?></label>
        </li>
      </ul>
    </div>
  <?php
  }

}

/**
 * Register widget
 */
add_action( 'widgets_init', function() {
  if( class_exists( 'Property_Attributes_Widget' ) ) {
    register_widget( "Property_Attributes_Widget" );
  }
});