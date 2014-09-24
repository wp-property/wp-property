<?php
/**
 * Property Attributes widget.
 *
 * @author potanin@UD
 * @since 1.32.0
 */
class Property_Attributes_Widget extends WP_Widget {

  /**
   * Initialize the widget.
   *
   * @since 1.31.0
   */
  function __construct() {

    $property_label = strtolower( WPP_F::property_label() );

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
        $attribute = WPP_F::get_attribute_data( $slug );
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
      $main_options[ $slug ] = WPP_F::get_attribute_data( $slug );
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
 * Other Properties Widget
 */
class OtherPropertiesWidget extends WP_Widget {

  function OtherPropertiesWidget() {

    $property_label = strtolower( WPP_F::property_label( 'plural' ) );

    parent::__construct(
      false,
      sprintf( __( 'Other %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ),
      array(
        'description' => sprintf( __( 'Display a list of %1s that share a parent with the currently displayed property.', 'wpp' ), $property_label )
      ),
      array(
        'width' => 300
      )
    );

  }

  /** @see WP_Widget::widget */
  function widget( $args, $instance ) {
    global $post, $wp_properties, $property;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract( $args );

    $instance = apply_filters( 'OtherPropertiesWidget', $instance );
    $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $image_type = isset( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : false;
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : false;
    $address_format = isset( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : '';
    $amount_items = !empty( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : 0;
    $show_properties_of_type = isset( $instance[ 'show_properties_of_type' ] ) ? $instance[ 'show_properties_of_type' ] : false;
    $shuffle_order = isset( $instance[ 'shuffle_order' ] ) ? $instance[ 'shuffle_order' ] : false;

    if ( !isset( $post->ID ) || ( $post->post_parent == 0 && $show_properties_of_type != 'on' ) ) {
      return;
    }

    $this_property = (array)$property;

    if ( $post->post_parent ) {
      $properties = get_posts( array(
        'numberposts' => $amount_items,
        'post_type' => 'property',
        'post_status' => 'publish',
        'post_parent' => $post->post_parent,
        'exclude' => $post->ID
      ) );
    } else {
      $properties = WPP_F::get_properties( "property_type={$this_property['property_type']}&pagi=0--$amount_items" );
    }

    if ( empty( $properties ) ) {
      return;
    }

    $html[ ] = $before_widget;
    $html[ ] = "<div class='wpp_other_properties_widget'>";

    if ( $title ) {
      $html[ ] = $before_title . $title . $after_title;
    }

    if ( $shuffle_order == 'on' ) {
      shuffle( $properties );
    }


    ob_start();

    foreach ( $properties as $single ) {

      $property_id = is_object( $single ) ? $single->ID : $single;

      $this_property = WPP_F::get_property( $property_id, 'return_object=true' );

      $this_property = prepare_property_for_display( $this_property );

      $image = ( isset( $this_property->featured_image ) && !empty( $image_type ) ) ? wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) ) : false;

      ?>
      <div class="property_widget_block apartment_entry clearfix" style="<?php echo( !empty( $image[ 'width' ] ) ? 'width: ' . ( $image[ 'width' ] + 5 ) . 'px;' : '' ); ?>">
        <?php if ( $hide_image !== 'on' && !empty( $image ) ) : ?>
          <a class="sidebar_property_thumbnail thumbnail" href="<?php echo $this_property->permalink; ?>">
            <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>" src="<?php echo $image[ 'link' ]; ?>" alt=""/>
          </a>
        <?php endif; ?>

        <?php if ( $show_title == 'on' ) { ?>
          <p class="title"><a
              href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
        <?php } ?>

        <ul class="wpp_widget_attribute_list">
          <?php
          if ( is_array( $stats ) ) {
            foreach ( $stats as $stat ) {
              if( !isset( $this_property->$stat ) ) {
                continue;
              }
              switch( true ) {
                case ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && $wp_properties[ 'configuration' ][ 'address_attribute' ] == $stat ):
                  $content = wpp_format_address_attribute( $this_property->$stat, $this_property, $address_format );
                  break;
                case ( $stat == 'property_type' ):
                  $content = nl2br( apply_filters( "wpp_stat_filter_property_type_label", $this_property->property_type_label ) );
                  break;
                default:
                  $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}", $this_property->$stat ) );
                  break;
              }
              if ( empty( $content ) ) {
                continue;
              }
              ?>
              <li class="<?php echo $stat ?>">
                <span class="attribute"><?php echo $wp_properties[ 'property_stats' ][ $stat ]; ?>:</span>
                <span class="value"><?php echo $content; ?></span>
              </li>
            <?php
            }
          } ?>
        </ul>

        <?php if ( $instance[ 'enable_more' ] == 'on' ) {
          echo '<p class="more"><a href="' . $this_property->permalink . '" class="btn btn-info">' . __( 'More', 'wpp' ) . '</a></p>';
        } ?>

      </div>

      <?php
      unset( $this_property );
    }

    $html[ 'widget_content' ] = ob_get_contents();
    ob_end_clean();

    if ( $instance[ 'enable_view_all' ] == 'on' ) {
      $html[ ] = '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', 'wpp' ) . '</a></p>';
    }

    $html[ ] = '</div>';

    $html[ ] = $after_widget;

    if ( !empty( $html[ 'widget_content' ] ) ) {
      echo implode( '', $html );
    }

  }

  /** @see WP_Widget::update */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /** @see WP_Widget::form */
  function form( $instance ) {

    global $wp_properties;
    $title = isset( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : '';
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $amount_items = !empty( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : 5;
    $address_format = !empty( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : '[street_number] [street_name], [city], [state]';
    $image_type = isset( $instance[ 'image_type' ] ) ? esc_attr( $instance[ 'image_type' ] ) : false;
    $property_stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array();
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $enable_more = isset( $instance[ 'enable_more' ] ) ? $instance[ 'enable_more' ] : false;
    $enable_view_all = isset( $instance[ 'enable_view_all' ] ) ? $instance[ 'enable_view_all' ] : false;
    $show_properties_of_type = isset( $instance[ 'show_properties_of_type' ] ) ? $instance[ 'show_properties_of_type' ] : false;
    $shuffle_order = isset( $instance[ 'shuffle_order' ] ) ? $instance[ 'shuffle_order' ] : false;

    ?>
    <script type="text/javascript">

      jQuery( document ).ready( function ( $ ) {
        jQuery( 'input.check_me_other' ).live( 'change', function () {
          var parent = jQuery( this ).closest( '.widget-content' );
          if ( jQuery( this ).is( ':checked' ) ) {
            jQuery( 'p.choose_thumb_other', parent ).hide();
          } else {
            jQuery( 'p.choose_thumb_other', parent ).show();
          }
        } )
      } );

    </script>
    <p><?php _e( 'The widget will show other properties that share a parent with the currently displayed property.', 'wpp' ); ?></p>

    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
             name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
    </p>

    <p>
      <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me_other"
             name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
             value="on" <?php if ( $hide_image == 'on' ) echo "checked='checked'"; ?> />
      <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>"><?php _e( 'Hide images.', 'wpp' ); ?></label>
    </p>

    <p
      class="choose_thumb_other" <?php echo ( $hide_image !== 'on' ) ? 'style="display:block;"' : 'style="display:none;"'; ?>>
      <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', 'wpp' ); ?></label>
      <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
    </p>

    <p>
      <label
        for="<?php echo $this->get_field_id( 'amount_items' ); ?>"><?php _e( 'Listings to display?', 'wpp' ); ?></label>
      <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
             name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
             value="<?php echo $amount_items; ?>"/>
    </p>

    <p><?php _e( 'Select the attributes you want to display: ', 'wpp' ); ?></p>

    <ul class="wp-tab-panel">
      <li>
        <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
               name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
               value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
        <label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Title', 'wpp' ); ?></label>
      </li>

      <?php foreach ( $wp_properties[ 'property_stats' ] as $stat => $label ) { ?>
        <li>
          <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
                 name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox"
                 value="<?php echo $stat; ?>" <?php if ( is_array( $property_stats ) && in_array( $stat, $property_stats ) ) echo ' checked="checked" '; ?> />
          <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"><?php echo $label; ?></label>
        </li>
      <?php } ?>

    </ul>

    <div>
      <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', 'wpp' ); ?>
        <textarea style="width: 100%" id="<?php echo $this->get_field_id( 'address_format' ); ?>"
                  name="<?php echo $this->get_field_name( 'address_format' ); ?>"><?php echo $address_format; ?></textarea>
      </label>
    </div>

    <ul>
      <li>
        <input id="<?php echo $this->get_field_id( 'enable_more' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_more' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_more == 'on' ) echo " checked='checked';"; ?> />
        <label
          for="<?php echo $this->get_field_id( 'enable_more' ); ?>"><?php _e( 'Show "More" link?', 'wpp' ); ?></label>
      </li>
      <li>
        <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
        <label
          for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"><?php _e( 'Show "View All" link?', 'wpp' ); ?></label>
      </li>
      <li>
        <input id="<?php echo $this->get_field_id( 'show_properties_of_type' ); ?>"
               name="<?php echo $this->get_field_name( 'show_properties_of_type' ); ?>" type="checkbox"
               value="on" <?php checked( 'on', $show_properties_of_type ); ?> />
        <label
          for="<?php echo $this->get_field_id( 'show_properties_of_type' ); ?>"><?php _e( 'If property has no parent, show other properties of same type.', 'wpp' ); ?></label>
      </li>
      <li>
        <input id="<?php echo $this->get_field_id( 'shuffle_order' ); ?>"
               name="<?php echo $this->get_field_name( 'shuffle_order' ); ?>" type="checkbox"
               value="on" <?php checked( 'on', $shuffle_order ); ?> />
        <label
          for="<?php echo $this->get_field_id( 'shuffle_order' ); ?>"><?php _e( 'Randomize order of displayed properties.', 'wpp' ); ?></label>
      </li>
    </ul>

  <?php
  }

}

/**
 * Child Properties Widget
 */
class ChildPropertiesWidget extends WP_Widget {
  /**
   * Constructor
   */
  function ChildPropertiesWidget() {
    parent::WP_Widget( false, $name = sprintf( __( 'Child %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'Show child properties (if any) for currently displayed property', 'wpp' ) ) );
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
    $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $image_type = isset( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : '';
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array();
    $address_format = isset( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : '';
    $amount_items = !empty( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : 5;

    if ( !empty( $image_type ) ) {
      $image_size = WPP_F::image_sizes( $image_type );
    }

    $attachments = get_posts( array(
      'post_type' => 'property',
      'numberposts' => $amount_items,
      'post_status' => 'publish',
      'post_parent' => $post->ID,
    ) );

    // Bail out if no children
    if ( count( $attachments ) < 1 ) {
      return;
    }

    echo $before_widget;
    echo "<div class='wpp_child_properties_widget'>";

    if ( $title ) {
      echo $before_title . $title . $after_title;
    }

    foreach ( $attachments as $attached ) {
      $this_property = WPP_F::get_property( $attached->ID, 'return_object=true' );
      $image = isset( $this_property->featured_image ) ? wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) ) : false;
      $width = ( !empty( $image_size[ 'width' ] ) ? $image_size[ 'width' ] : ( !empty( $image[ 'width' ] ) ? $image[ 'width' ] : '' ) );
      $height = ( !empty( $image_size[ 'height' ] ) ? $image_size[ 'height' ] : ( !empty( $image[ 'height' ] ) ? $image[ 'height' ] : '' ) );
      ?>
      <div class="property_widget_block apartment_entry clearfix"
           style="<?php echo( $width ? 'width: ' . ( $width + 5 ) . 'px;' : '' ); ?>">
        <?php if ( $hide_image !== 'on' ) : ?>
          <?php if ( !empty( $image ) ): ?>
            <a class="sidebar_property_thumbnail thumbnail" href="<?php echo $this_property->permalink; ?>">
              <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>"
                   src="<?php echo $image[ 'link' ]; ?>"
                   alt="<?php echo sprintf( __( '%s at %s for %s', 'wpp' ), $this_property->post_title, $this_property->location, $this_property->price ); ?>"/>
            </a>
          <?php else: ?>
            <div class="wpp_no_image" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;"></div>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ( $show_title == 'on' ): ?>
          <p class="title"><a
              href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
        <?php endif; ?>
        <ul class="wpp_widget_attribute_list">
          <?php if ( is_array( $stats ) ): ?>
            <?php foreach ( $stats as $stat ): ?>
              <?php 
              if( !isset( $this_property->$stat ) ) {
                continue;
              }
              switch( true ) {
                case ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && $wp_properties[ 'configuration' ][ 'address_attribute' ] == $stat ):
                  $content = wpp_format_address_attribute( $this_property->$stat, $this_property, $address_format );
                  break;
                case ( $stat == 'property_type' ):
                  $content = nl2br( apply_filters( "wpp_stat_filter_property_type_label", $this_property->property_type_label ) );
                  break;
                default:
                  $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}", $this_property->$stat ) );
                  break;
              }
              if ( empty( $content ) ) {
                continue;
              }
              ?>
              <li class="<?php echo $stat ?>"><span class='attribute'><?php echo $wp_properties[ 'property_stats' ][ $stat ]; ?>:</span> <span class='value'><?php echo $content; ?></span></li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>

        <?php if ( $instance[ 'enable_more' ] == 'on' ) : ?>
          <p class="more"><a href="<?php echo $this_property->permalink; ?>"
                             class="btn btn-info"><?php _e( 'More', 'wpp' ); ?></a></p>
        <?php endif; ?>
      </div>
      <?php
      unset( $this_property );
    }

    if ( $instance[ 'enable_view_all' ] == 'on' ) {
      echo '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', 'wpp' ) . '</a></p>';
    }
    echo '<div class="clear"></div>';
    echo '</div>';
    echo $after_widget;
  }

  /** @see WP_Widget::update */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /** @see WP_Widget::form */
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
    <p><?php _e( 'The widget will not be displayed if the currently viewed property has no children.', 'wpp' ); ?></p>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
               name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
      </label>
    </p>


    <p>
      <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>">
        <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me_child"
               name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
               value="on" <?php if ( $hide_image == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Hide Images?', 'wpp' ); ?>
      </label>
    </p>
    <p id="choose_thumb_child" <?php
    if ( $hide_image !== 'on' )
      echo 'style="display:block;"';
    else
      echo 'style="display:none;"';
    ?>>
      <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', 'wpp' ); ?>
        <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
      </label>

    <p>
      <label for="<?php echo $this->get_field_id( 'amount_items' ); ?>"><?php _e( 'Listings to display?', 'wpp' ); ?>
        <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
               name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
               value="<?php echo $amount_items; ?>"/>
      </label>
    </p>




    <p><?php _e( 'Select the stats you want to display', 'wpp' ); ?></p>
    <p>
      <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
        <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
               name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
               value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Title', 'wpp' ); ?>
      </label>
    </p>
    <?php foreach ( $wp_properties[ 'property_stats' ] as $stat => $label ): ?>
      <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>">
        <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
               name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
          <?php if ( is_array( $property_stats ) && in_array( $stat, $property_stats ) ) echo " checked "; ?> />

        <?php echo $label; ?>
      </label><br/>
    <?php endforeach; ?>


    <p>
      <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', 'wpp' ); ?>
        <textarea style="width: 100%" id="<?php echo $this->get_field_id( 'address_format' ); ?>"
                  name="<?php echo $this->get_field_name( 'address_format' ); ?>"><?php echo $address_format; ?></textarea>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_more' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_more' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_more' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_more == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "More" link?', 'wpp' ); ?>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "View All" link?', 'wpp' ); ?>
      </label>
    </p>



  <?php
  }

}

/**
 * Lookup Widget
 */
class FeaturedPropertiesWidget extends WP_Widget {

  /**
   * constructor
   */
  function FeaturedPropertiesWidget() {
    parent::WP_Widget( false, $name = sprintf( __( 'Featured %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'List of properties that were marked as Featured', 'wpp' ) ) );
  }

  /**
   * @see WP_Widget::widget
   *
   */
  function widget( $args, $instance ) {
    global $wp_properties;
    
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    
    extract( $args );
    
    $title = apply_filters( 'widget_title', $instance[ 'title' ] );
    $instance = apply_filters( 'FeaturedPropertiesWidget', $instance );
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $image_type = isset( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : false;
    $amount_items = isset( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : false;
    $stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : false;
    $address_format = isset( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : false;
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $amount_items = isset( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : false;
    $random_items = isset( $instance[ 'random_items' ] ) ? $instance[ 'random_items' ] : false;
    $property_stats = isset( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array();

    if ( empty( $address_format ) ) {
      $address_format = "[street_number] [street_name], [city], [state]";
    }
    if ( !$image_type ) {
      $image_type = '';
    } else {
      $image_size = WPP_F::image_sizes( $image_type );
    }

    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = __( 'Property Type', 'wpp' );
    }

    $random_sort = $random_items == 1 ? '&sort_by=random' : '';
    $all_featured = WPP_F::get_properties( "featured=true&property_type=all&pagi=0--{$amount_items}{$random_sort}" );

    /** Bail out if no children */
    if ( !$all_featured ) {
      return;
    }

    //** The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique */
    /*
    //Removed since this will cause problems with jQuery Tabs in Denali.
    $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
    //*/

    echo $before_widget;
    echo "<div class='wpp_featured_properties_widget'>";

    if ( $title ) {
      echo $before_title . $title . $after_title;
    }

    $count = 0;

    foreach ( $all_featured as $featured ) {
      if ( $amount_items == $count ) {
        continue;
      }
      $count++;
      $this_property = WPP_F::get_property( $featured, 'return_object=true' );
      
      if( !empty( $this_property->featured_image ) ) {
        $image = wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) );
        $width = ( !empty( $image_size[ 'width' ] ) ? $image_size[ 'width' ] : ( !empty( $image[ 'width' ] ) ? $image[ 'width' ] : '' ) );
        $height = ( !empty( $image_size[ 'height' ] ) ? $image_size[ 'height' ] : ( !empty( $image[ 'height' ] ) ? $image[ 'height' ] : '' ) );
      }
      
      ?>
      <div class="property_widget_block clearfix" style="<?php echo( $width ? 'width: ' . ( $width + 5 ) . 'px;' : '' ); ?> min-height: <?php echo $height; ?>px;">
        <?php if ( $hide_image !== 'on' ) : ?>
          <?php if ( !empty( $image ) ) : ?>
            <a class="sidebar_property_thumbnail thumbnail" href="<?php echo $this_property->permalink; ?>">
              <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>" src="<?php echo $image[ 'link' ]; ?>" alt=""/></a>
          <?php else : ?>
            <div class="wpp_no_image" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;"></div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ( $show_title == 'on' ): ?>
          <p class="title"><a
              href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
        <?php endif; ?>

        <ul class="wpp_widget_attribute_list">
          <?php if ( is_array( $stats ) ): ?>
            <?php foreach ( $stats as $stat ):
              if( !isset( $this_property->$stat ) ) {
                continue;
              }
              switch( true ) {
                case ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && $wp_properties[ 'configuration' ][ 'address_attribute' ] == $stat ):
                  $content = wpp_format_address_attribute( $this_property->$stat, $this_property, $address_format );
                  break;
                case ( $stat == 'property_type' ):
                  $content = nl2br( apply_filters( "wpp_stat_filter_property_type_label", $this_property->property_type_label ) );
                  break;
                default:
                  $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}", $this_property->$stat ) );
                  break;
              }
              if ( empty( $content ) ) {
                continue;
              }
              ?>
              <li class="<?php echo $stat ?>"><span class='attribute'><?php echo $property_stats[ $stat ]; ?>:</span>
                <span class='value'><?php echo $content; ?></span></li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
        <?php if ( isset( $instance[ 'enable_more' ] ) && $instance[ 'enable_more' ] == 'on' ) : ?>
          <p class="more"><a href="<?php echo $this_property->permalink; ?>" class="btn btn-info"><?php _e( 'More', 'wpp' ); ?></a></p>
        <?php endif; ?>
      </div>
      <?php
      unset( $this_property );
    }
    if ( isset( $instance[ 'enable_view_all' ] ) && $instance[ 'enable_view_all' ] == 'on' ) {
      echo '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', 'wpp' ) . '</a></p>';
    }
    echo '<div class="clear"></div>';
    echo '</div>';
    echo $after_widget;
  }

  /**
   * @see WP_Widget::update
   */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /**
   * @see WP_Widget::form
   */
  function form( $instance ) {
    global $wp_properties;
    $title = isset( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : false;
    $image_type = isset( $instance[ 'image_type' ] ) ? esc_attr( $instance[ 'image_type' ] ) : false;
    $amount_items = isset( $instance[ 'amount_items' ] ) ? esc_attr( $instance[ 'amount_items' ] ) : false;
    $instance_stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : false;
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $address_format = isset( $instance[ 'address_format' ] ) ? esc_attr( $instance[ 'address_format' ] ) : false;
    $enable_more = isset( $instance[ 'enable_more' ] ) ? $instance[ 'enable_more' ] : false;
    $enable_view_all = isset( $instance[ 'enable_view_all' ] ) ? $instance[ 'enable_view_all' ] : false;
    $random_items = isset( $instance[ 'random_items' ] ) ? $instance[ 'random_items' ] : false;
    $property_stats = isset( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : false;

    if ( empty( $address_format ) ) {
      $address_format = "[street_number] [street_name],[city], [state]";
    }

    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = __( 'Property Type', 'wpp' );
    }
    ?>
    <script type="text/javascript">
      //hide and show dropdown whith thumb settings
      jQuery( document ).ready( function ( $ ) {
        jQuery( 'input.check_me_featured' ).change( function () {
          if ( jQuery( this ).attr( 'checked' ) !== true ) {
            jQuery( 'p#choose_thumb_featured' ).css( 'display', 'block' );
          } else {
            jQuery( 'p#choose_thumb_featured' ).css( 'display', 'none' );
          }
        } );
      } );
    </script>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
               name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>">
        <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me_featured"
               name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
               value="on" <?php if ( $hide_image == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Hide Images?', 'wpp' ); ?>
      </label>
    </p>
    <p
      id="choose_thumb_featured" <?php echo( $hide_image !== 'on' ? 'style="display:block;"' : 'style="display:none;"' ); ?>>
      <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', 'wpp' ); ?>
        <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'amount_items' ); ?>"><?php _e( 'Listings to display?', 'wpp' ); ?>
        <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
               name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
               value="<?php echo ( empty( $amount_items ) ) ? 5 : $amount_items; ?>"/>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'random_items' ); ?>">
        <input id="<?php echo $this->get_field_id( 'random_items' ); ?>"
               name="<?php echo $this->get_field_name( 'random_items' ); ?>" type="checkbox"
               value="1" <?php if ( !empty( $random_items ) ) echo ' checked="checked"'; ?> />
        <?php _e( 'Display properties in random order?', 'wpp' ); ?>
      </label>
    </p>
    <p><?php _e( 'Select the stats you want to display', 'wpp' ) ?></p>
    <p>
      <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
        <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
               name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
               value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Title', 'wpp' ); ?>
      </label>
      <?php foreach ( $property_stats as $stat => $label ): ?>
        <br/>
        <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>">
          <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
                 name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
          <?php if ( is_array( $instance_stats ) && in_array( $stat, $instance_stats ) ) echo " checked "; ?>">
          <?php echo $label; ?>
        </label>
      <?php endforeach; ?>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', 'wpp' ); ?>
        <textarea style="width: 100%" id="<?php echo $this->get_field_id( 'address_format' ); ?>"
                  name="<?php echo $this->get_field_name( 'address_format' ); ?>"><?php echo $address_format; ?></textarea>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_more' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_more' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_more' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_more == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "More" link?', 'wpp' ); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "View All" link?', 'wpp' ); ?>
      </label>
    </p>
  <?php
  }

}

/**
 * Default function to use in template directly
 *
 * @param $args
 * @param $custom
 */
function wpp_featured_properties( $args = false, $custom = false ) {
  if ( !$args ) {
    $args = array(
      'before_title' => '<h3>',
      'after_title' => '</h3>',
      'before_widget' => '',
      'after_widget' => ''
    );
  }
  $default = array(
    'title' => __( 'Featured Properies', 'wpp' ),
    'image_type' => 'thumbnail',
    'stats' => array( 'bedrooms', 'location', 'price' ),
    'address_format' => '[street_number] [street_name], [city], [state]'
  );
  if ( $custom ) {
    $default = array_merge( $default, (array)$custom );
  }
  FeaturedPropertiesWidget::widget( $args, $default );
}

/**
 * Latest properties Widget
 */
class LatestPropertiesWidget extends WP_Widget {

  /**
   * constructor
   */
  function LatestPropertiesWidget() {
    parent::WP_Widget( false, $name = sprintf( __( 'Latest %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'List of the latest properties created on this site', 'wpp' ) ) );
  }

  /**
   * @see WP_Widget::widget
   */
  function widget( $args, $instance ) {
    global $wp_properties;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract( $args );

    $instance = apply_filters( 'LatestPropertiesWidget', $instance );
    $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
    $stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array();
    $image_type = !empty( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : '';
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $address_format = isset( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : '';
    $property_stats = isset( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array();

    if ( !empty( $image_type ) ) {
      $image_size = WPP_F::image_sizes( $image_type );
    }

    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = __( 'Property Type', 'wpp' );
    }

    $arg = array(
      'post_type' => 'property',
      'numberposts' => ( !empty( $instance[ 'amount_items' ] ) ? $instance[ 'amount_items' ] : 5 ),
      'post_status' => 'publish',
      'post_parent' => null, // any parent
      'order' => 'DESC',
      'orderby' => 'post_date'
    );

    $postslist = get_posts( $arg );

    echo $before_widget;
    echo "<div class='wpp_latest_properties_widget'>";

    if ( $title ) {
      echo $before_title . $title . $after_title;
    }

    foreach ( $postslist as $post ) {
      $this_property = WPP_F::get_property( $post->ID, 'return_object=true' );
      $image = isset( $this_property->featured_image ) ? wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) ) : false;
      $width = ( !empty( $image_size[ 'width' ] ) ? $image_size[ 'width' ] : ( !empty( $image[ 'width' ] ) ? $image[ 'width' ] : '' ) );
      $height = ( !empty( $image_size[ 'height' ] ) ? $image_size[ 'height' ] : ( !empty( $image[ 'height' ] ) ? $image[ 'height' ] : '' ) );
      ?>
      <div class="property_widget_block latest_entry clearfix"
           style="<?php echo( $width ? 'width: ' . ( $width + 5 ) . 'px;' : '' ); ?>">
        <?php if ( $hide_image !== 'on' ) { ?>
          <?php if ( !empty( $image ) ) : ?>
            <a class="sidebar_property_thumbnail latest_property_thumbnail thumbnail"
               href="<?php echo $this_property->permalink; ?>">
              <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>"
                   src="<?php echo $image[ 'url' ]; ?>"
                   alt="<?php echo sprintf( __( '%s at %s for %s', 'wpp' ), $this_property->post_title, $this_property->location, $this_property->price ); ?>"/>
            </a>
          <?php else : ?>
            <div class="wpp_no_image" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;"></div>
          <?php endif; ?>
        <?php
        }
        if ( $show_title == 'on' ) {
          echo '<p class="title"><a href="' . $this_property->permalink . '">' . $post->post_title . '</a></p>';
        }

        echo '<ul class="wpp_widget_attribute_list">';
        if ( is_array( $stats ) ) {
          foreach ( $stats as $stat ) {
            if( !isset( $this_property->$stat ) ) {
              continue;
            }
            switch( true ) {
              case ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && $wp_properties[ 'configuration' ][ 'address_attribute' ] == $stat ):
                $content = wpp_format_address_attribute( $this_property->$stat, $this_property, $address_format );
                break;
              case ( $stat == 'property_type' ):
                $content = nl2br( apply_filters( "wpp_stat_filter_property_type_label", $this_property->property_type_label ) );
                break;
              default:
                $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}", $this_property->$stat ) );
                break;
            }
            if ( empty( $content ) ) {
              continue;
            }
            echo '<li class="' . $stat . '"><span class="attribute">' . $property_stats[ $stat ] . ':</span> <span class="value">' . $content . '</span></li>';
          }
        }
        echo '</ul>';

        if ( isset( $instance[ 'enable_more' ] ) && $instance[ 'enable_more' ] == 'on' ) {
          echo '<p class="more"><a href="' . $this_property->permalink . '" class="btn btn-info">' . __( 'More', 'wpp' ) . '</a></p>';
        }
        ?>
      </div>
      <?php
      unset( $this_property );
    }

    if ( isset( $instance[ 'enable_view_all' ] ) && $instance[ 'enable_view_all' ] == 'on' ) {
      echo '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', 'wpp' ) . '</a></p>';
    }
    echo '<div class="clear"></div>';
    echo '</div>';
    echo $after_widget;
  }

  /**
   * @see WP_Widget::update
   */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /**
   * @see WP_Widget::form
   */
  function form( $instance ) {
    global $wp_properties;

    $title = isset( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : '';
    $amount_items = isset( $instance[ 'amount_items' ] ) ? esc_attr( $instance[ 'amount_items' ] ) : false;
    $address_format = !empty( $instance[ 'address_format' ] ) ? $instance[ 'address_format' ] : "[street_number] [street_name],[city], [state]";
    $instance_stats = isset( $instance[ 'stats' ] ) ? $instance[ 'stats' ] : array();
    $image_type = isset( $instance[ 'image_type' ] ) ? esc_attr( $instance[ 'image_type' ] ) : false;
    $hide_image = isset( $instance[ 'hide_image' ] ) ? $instance[ 'hide_image' ] : false;
    $show_title = isset( $instance[ 'show_title' ] ) ? $instance[ 'show_title' ] : false;
    $enable_more = isset( $instance[ 'enable_more' ] ) ? $instance[ 'enable_more' ] : false;
    $enable_view_all = isset( $instance[ 'enable_view_all' ] ) ? $instance[ 'enable_view_all' ] : false;
    $property_stats = isset( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array();

    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = __( 'Property Type', 'wpp' );
    }
    ?>
    <script type="text/javascript">
      //hide and show dropdown whith thumb settings
      jQuery( document ).ready( function ( $ ) {
        jQuery( 'input.check_me' ).change( function () {
          if ( $( this ).attr( 'checked' ) !== true ) {
            jQuery( 'p#choose_thumb' ).css( 'display', 'block' );
          } else {
            jQuery( 'p#choose_thumb' ).css( 'display', 'none' );
          }
        } );
      } );
    </script>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
        <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
               name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
               value="<?php echo ( !empty( $title ) ) ? $title : __( 'Latest Properties', 'wpp' ); ?>"/>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>">
        <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me"
               name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
               value="on" <?php if ( $hide_image == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Hide Images?', 'wpp' ); ?>
      </label>
    </p>
    <p id="choose_thumb" <?php echo( $hide_image !== 'on' ? 'style="display:block;"' : 'style="display:none;"' ); ?>>
      <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', 'wpp' ); ?>
        <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'per_page' ); ?>"><?php _e( 'Listings to display?', 'wpp' ); ?>
        <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
               name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
               value="<?php echo ( empty( $amount_items ) ) ? 5 : $amount_items; ?>"/>
      </label>
    </p>
    <p><?php _e( 'Select stats you want to display', 'wpp' ) ?></p>
    <p>
      <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
        <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
               name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
               value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Title', 'wpp' ); ?>
      </label>
      <?php foreach ( $property_stats as $stat => $label ): ?>
        <br/>
        <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>">
          <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
                 name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
          <?php if ( is_array( $instance_stats ) && in_array( $stat, $instance_stats ) ) echo " checked "; ?>">
          <?php echo $label; ?>
        </label>
      <?php endforeach; ?>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', 'wpp' ); ?>
        <textarea style="width: 100%" id="<?php echo $this->get_field_id( 'address_format' ); ?>"
                  name="<?php echo $this->get_field_name( 'address_format' ); ?>"><?php echo $address_format; ?></textarea>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_more' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_more' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_more' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_more == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "More" link?', 'wpp' ); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>">
        <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
        <?php _e( 'Show "View All" link?', 'wpp' ); ?>
      </label>
    </p>
  <?php
  }

}

/**
 * Property Search Widget
 */
class SearchPropertiesWidget extends WP_Widget {
  var $id = false;

  /** constructor */
  function SearchPropertiesWidget() {

    $property_label = strtolower( WPP_F::property_label() );

    parent::__construct(
      false,
      sprintf( __( '%1s Search', 'wpp' ), WPP_F::property_label() ),
      array(
        'classname' => 'wpp_property_attributes',
        'description' => sprintf( __( 'Display a highly customizable  %1s search form.', 'wpp' ), $property_label )
      ),
      array(
        'width' => 300
      )
    );

  }

  /** @see WP_Widget::widget */
  function widget( $args, $instance ) {

    global $wp_properties;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    $widget_id = '';
    extract( $args );
    $title = apply_filters( 'widget_title', $instance[ 'title' ] );

    $instance = apply_filters( 'SearchPropertiesWidget', $instance );
    $search_attributes = isset( $instance[ 'searchable_attributes' ] ) ? $instance[ 'searchable_attributes' ] : false;
    $sort_by = isset( $instance[ 'sort_by' ] ) ? $instance[ 'sort_by' ] : false;
    $sort_order = isset( $instance[ 'sort_order' ] ) ? $instance[ 'sort_order' ] : false;
    $searchable_property_types = isset( $instance[ 'searchable_property_types' ] ) ? $instance[ 'searchable_property_types' ] : array();
    $grouped_searchable_attributes = isset( $instance[ 'grouped_searchable_attributes' ] ) ? $instance[ 'grouped_searchable_attributes' ] : array();

    if ( !is_array( $search_attributes ) ) {
      return;
    }

    if ( !function_exists( 'draw_property_search_form' ) ) {
      return;
    }

    //** The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique */
    /*
          Removed since this will cause problems with jQuery Tabs in Denali.
          $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
        */

    echo $before_widget;

    echo '<div class="wpp_search_properties_widget">';

    if ( $title ) {
      echo $before_title . $title . $after_title;
    } else {
      echo '<span class="wpp_widget_no_title"></span>';
    }

    //** Load different attribute list depending on group selection */
    if ( isset( $instance[ 'group_attributes' ] ) && $instance[ 'group_attributes' ] == 'true' ) {
      $search_args[ 'group_attributes' ] = true;
      $search_args[ 'search_attributes' ] = $grouped_searchable_attributes;
    } else {
      $search_args[ 'search_attributes' ] = $search_attributes;
    }

    //* Clean searchable attributes: remove unavailable ones */
    $all_searchable_attributes = array_unique( $wp_properties[ 'searchable_attributes' ] );
    foreach ( $search_args[ 'search_attributes' ] as $k => $v ) {
      if ( !in_array( $v, $all_searchable_attributes ) ) {
        //* Don't remove hardcoded attributes (property_type,city) */
        if ( $v != 'property_type' && $v != 'city' ) {
          unset( $search_args[ 'search_attributes' ][ $k ] );
        }
      }
    }

    $search_args[ 'searchable_property_types' ] = $searchable_property_types;

    if ( isset( $instance[ 'use_pagi' ] ) && $instance[ 'use_pagi' ] == 'on' ) {

      if ( empty( $instance[ 'per_page' ] ) ) {
        $instance[ 'per_page' ] = 10;
      }

      $search_args[ 'per_page' ] = $instance[ 'per_page' ];
      $search_args[ 'use_pagination' ] = 'on';
    } else {
      $search_args[ 'use_pagination' ] = 'off';
      $search_args[ 'per_page' ] = isset( $instance[ 'per_page' ] ) ? $instance[ 'per_page' ] : false;
    }

    $search_args[ 'instance_id' ] = $widget_id;
    $search_args[ 'sort_by' ] = $sort_by;
    $search_args[ 'sort_order' ] = $sort_order;
    $search_args[ 'strict_search' ] = ( !empty( $instance[ 'strict_search' ] ) && $instance[ 'strict_search' ] == 'on' ? 'true' : 'false' ) ;

    draw_property_search_form( $search_args );

    echo "<div class='cboth'></div></div>";

    echo $after_widget;
  }

  /** @see WP_Widget::update */
  function update( $new_instance, $old_instance ) {
    //Recache searchable values for search widget form
    $searchable_attributes = $new_instance[ 'searchable_attributes' ];
    $grouped_searchable_attributes = $new_instance[ 'grouped_searchable_attributes' ];
    $searchable_property_types = $new_instance[ 'searchable_property_types' ];
    $group_attributes = $new_instance[ 'group_attributes' ];


    if ( $group_attributes == 'true' ) {

      WPP_F::get_search_values( $grouped_searchable_attributes, $searchable_property_types, false, $this->id );
    } else {
      WPP_F::get_search_values( $searchable_attributes, $searchable_property_types, false, $this->id );
    }

    return $new_instance;
  }

  /**
   *
   * Renders back-end property search widget tools.
   *
   * @complexity 8
   * @author potanin@UD
   *
   */
  function form( $instance ) {
    global $wp_properties;

    //** Get widget-specific data */
    $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : false;
    $searchable_attributes = isset( $instance[ 'searchable_attributes' ] ) ? $instance[ 'searchable_attributes' ] : false;
    $grouped_searchable_attributes = isset( $instance[ 'grouped_searchable_attributes' ] ) ? $instance[ 'grouped_searchable_attributes' ] : false;
    $use_pagi = isset( $instance[ 'use_pagi' ] ) ? $instance[ 'use_pagi' ] : false;
    $per_page = isset( $instance[ 'per_page' ] ) ? $instance[ 'per_page' ] : false;
    $strict_search = isset( $instance[ 'strict_search' ] ) ? $instance[ 'strict_search' ] : false;
    $sort_by = isset( $instance[ 'sort_by' ] ) ? $instance[ 'sort_by' ] : false;
    $sort_order = isset( $instance[ 'sort_order' ] ) ? $instance[ 'sort_order' ] : false;
    $group_attributes = isset( $instance[ 'group_attributes' ] ) ? $instance[ 'group_attributes' ] : false;
    $searchable_property_types = isset( $instance[ 'searchable_property_types' ] ) ? $instance[ 'searchable_property_types' ] : false;
    $property_stats = isset( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array();

    //** Get WPP data */
    $all_searchable_property_types = array_unique( $wp_properties[ 'searchable_property_types' ] );
    $all_searchable_attributes = array_unique( $wp_properties[ 'searchable_attributes' ] );
    $groups = isset( $wp_properties[ 'property_groups' ] ) ? $wp_properties[ 'property_groups' ] : false;
    $main_stats_group = isset( $wp_properties[ 'configuration' ][ 'main_stats_group' ] ) ? $wp_properties[ 'configuration' ][ 'main_stats_group' ] : false;

    $error = array();
    
    if ( !is_array( $all_searchable_property_types ) ) {
      $error[ 'no_searchable_types' ] = true;
    }

    if ( !is_array( $all_searchable_property_types ) ) {
      $error[ 'no_searchable_attributes' ] = true;
    }

    /** Set label for list below only */
    if ( !isset( $property_stats[ 'property_type' ] ) ) {
      $property_stats[ 'property_type' ] = __( 'Property Type', 'wpp' );
    }

    if ( is_array( $all_searchable_property_types ) && count( $all_searchable_property_types ) > 1 ) {

      //** Add property type to the beginning of the attribute list, even though it's not a typical attribute */
      array_unshift( $all_searchable_attributes, 'property_type' );
    }

    //** Find the difference between selected attributes and all attributes, i.e. unselected attributes */
    if ( is_array( $searchable_attributes ) && is_array( $all_searchable_attributes ) ) {
      $unselected_attributes = array_diff( $all_searchable_attributes, $searchable_attributes );

      //* Clean searchable attributes: remove unavailable ones */
      foreach ( $searchable_attributes as $k => $v ) {
        if ( !in_array( $v, $all_searchable_attributes ) ) {
          //* Don't remove hardcoded attributes (property_type,city) */
          if ( $v != 'property_type' && $v != 'city' ) {
            unset( $searchable_attributes[ $k ] );
          }
        }
      }

      // Build new array beginning with selected attributes, in order, follow by all other attributes
      $ungrouped_searchable_attributes = array_merge( $searchable_attributes, $unselected_attributes );

    } else {
      $ungrouped_searchable_attributes = $all_searchable_attributes;
    }

    $ungrouped_searchable_attributes = array_unique( $ungrouped_searchable_attributes );

    //* Perpare $all_searchable_attributes for using by sort function */
    $temp_attrs = array();

    foreach ( $all_searchable_attributes as $slug ) {
      $temp_attrs[ $slug ] = $slug;
    }

    //* Sort stats by groups */
    $stats_by_groups = sort_stats_by_groups( $temp_attrs );

    //** If the search widget cannot be created without some data, we bail */
    if ( !empty( $error ) ) {
      echo '<p>' . _e( 'No searchable property types were found.', 'wpp' ) . '</p>';
      return;
    }

    ?>

    <ul data-widget_number="<?php echo $this->number; ?>" data-widget="search_properties_widget"
        class="wpp_widget wpp_property_search_wrapper">

      <li class="<?php echo $this->get_field_id( 'title' ); ?>">
        <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
          <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                 name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
        </label>
      </li>

      <li class="wpp_property_types">
        <p><?php _e( 'Property types to search:', 'wpp' ); ?></p>
        <ul>
          <?php foreach ( $all_searchable_property_types as $property_type ) { ?>
            <li>
              <label
                for="<?php echo $this->get_field_id( 'searchable_property_types' ); ?>_<?php echo $property_type; ?>">
                <input class="wpp_property_types"
                       id="<?php echo $this->get_field_id( 'searchable_property_types' ); ?>_<?php echo $property_type; ?>"
                       name="<?php echo $this->get_field_name( 'searchable_property_types' ); ?>[]"
                       type="checkbox" <?php if ( empty( $searchable_property_types ) ) { echo 'checked="checked"'; } ?>
                       value="<?php echo $property_type; ?>" <?php if ( is_array( $searchable_property_types ) && in_array( $property_type, $searchable_property_types ) ) {
                  echo " checked ";
                } ?> />
                <?php echo( !empty( $wp_properties[ 'property_types' ][ $property_type ] ) ? $wp_properties[ 'property_types' ][ $property_type ] : ucwords( $property_type ) ); ?>
              </label>
            </li>
          <?php } ?>
        </ul>
      </li>

      <li class="wpp_attribute_selection">
        <p><?php _e( 'Select the attributes you want to search.', 'wpp' ); ?></p>

        <div class="wpp_search_widget_tab wpp_subtle_tabs ">

          <ul class="wpp_section_tabs  tabs">
            <li><a href="#all_atributes_<?php echo $this->id; ?>"><?php _e( 'All Attributes', 'wpp' ); ?></a></li>

            <?php if ( $stats_by_groups ) { ?>
              <li><a href="#grouped_attributes_<?php echo $this->id; ?>"><?php _e( 'Grouped Attributes', 'wpp' ); ?></a>
              </li>
            <?php } ?>
          </ul>

          <div id="all_atributes_<?php echo $this->id; ?>" class="wp-tab-panel wpp_all_attributes">
            <ul class="wpp_sortable_attributes">
              <?php foreach ( $ungrouped_searchable_attributes as $attribute ) { ?>

                <li class="wpp_attribute_wrapper <?php echo $attribute; ?>">
                  <input id="<?php echo $this->get_field_id( 'searchable_attributes' ); ?>_<?php echo $attribute; ?>"
                         name="<?php echo $this->get_field_name( 'searchable_attributes' ); ?>[]"
                         type="checkbox" <?php if ( empty( $searchable_attributes ) ) {
                    echo 'checked="checked"';
                  } ?>
                         value="<?php echo $attribute; ?>" <?php echo( ( is_array( $searchable_attributes ) && in_array( $attribute, $searchable_attributes ) ) ? " checked " : "" ); ?> />
                  <label
                    for="<?php echo $this->get_field_id( 'searchable_attributes' ); ?>_<?php echo $attribute; ?>"><?php echo( !empty( $property_stats[ $attribute ] ) ? $property_stats[ $attribute ] : ucwords( $attribute ) ); ?></label>
                </li>
              <?php } ?>
            </ul>
          </div><?php /* end all (ungrouped) attribute selection */ ?>

          <?php if ( $stats_by_groups ) { ?>
            <div id="grouped_attributes_<?php echo $this->id; ?>" class="wpp_grouped_attributes_container wp-tab-panel">

              <?php foreach ( $stats_by_groups as $gslug => $gstats ) { ?>
                <?php if ( $main_stats_group != $gslug || !key_exists( $gslug, $groups ) ) { ?>
                  <?php $group_name = ( key_exists( $gslug, $groups ) ? $groups[ $gslug ][ 'name' ] : "<span style=\"color:#8C8989\">" . __( 'Ungrouped', 'wpp' ) . "</span>" ); ?>
                  <h2 class="wpp_stats_group"><?php echo $group_name; ?></h2>
                <?php } ?>
                <ul>
                  <?php foreach ( $gstats as $attribute ) { ?>
                    <li>
                      <input
                        id="<?php echo $this->get_field_id( 'grouped_searchable_attributes' ); ?>_<?php echo $attribute; ?>"
                        name="<?php echo $this->get_field_name( 'grouped_searchable_attributes' ); ?>[]"
                        type="checkbox" <?php if ( empty( $grouped_searchable_attributes ) ) {
                        echo 'checked="checked"';
                      } ?>
                        value="<?php echo $attribute; ?>" <?php echo( ( is_array( $grouped_searchable_attributes ) && in_array( $attribute, $grouped_searchable_attributes ) ) ? " checked " : "" ); ?> />
                      <label
                        for="<?php echo $this->get_field_id( 'grouped_searchable_attributes' ); ?>_<?php echo $attribute; ?>"><?php echo( !empty( $property_stats[ $attribute ] ) ? $property_stats[ $attribute ] : ucwords( $attribute ) ); ?></label>
                    </li>
                  <?php } ?>
                </ul>
              <?php } /* End cycle through $stats_by_groups */ ?>
            </div>
          <?php } ?>

        </div>

      </li>

      <li>

        <?php if ($stats_by_groups) { ?>
        <div>
          <input id="<?php echo $this->get_field_id( 'group_attributes' ); ?>" class="wpp_toggle_attribute_grouping"
                 type="checkbox" value="true"
                 name="<?php echo $this->get_field_name( 'group_attributes' ); ?>" <?php checked( $group_attributes, 'true' ); ?> />
          <label
            for="<?php echo $this->get_field_id( 'group_attributes' ); ?>"><?php _e( 'Group attributes together.' ); ?></label>
        </div>
      </li>
      <?php } ?>

      <li>

        <div class="wpp_something_advanced_wrapper" style="margin-top: 10px;">
          <ul>

            <?php if ( !empty( $wp_properties[ 'sortable_attributes' ] ) && is_array( $wp_properties[ 'sortable_attributes' ] ) ) : ?>
              <li class="wpp_development_advanced_option">
                <div><label
                    for="<?php echo $this->get_field_id( 'sort_by' ); ?>"><?php _e( 'Default Sort Order', 'wpp' ); ?></label>
                </div>
                <select id="<?php echo $this->get_field_id( 'sort_by' ); ?>"
                        name="<?php echo $this->get_field_name( 'sort_by' ); ?>">
                  <option></option>
                  <?php foreach ( $wp_properties[ 'sortable_attributes' ] as $attribute ) { ?>
                    <option
                      value="<?php echo esc_attr( $attribute ); ?>"  <?php selected( $sort_by, $attribute ); ?> ><?php echo $property_stats[ $attribute ]; ?></option>
                  <?php } ?>
                </select>

                <select id="<?php echo $this->get_field_id( 'sort_order' ); ?>"
                        name="<?php echo $this->get_field_name( 'sort_order' ); ?>">
                  <option></option>
                  <option value="DESC"  <?php selected( $sort_order, 'DESC' ); ?> ><?php _e( 'Descending', 'wpp' ); ?></option>
                  <option value="ASC"  <?php selected( $sort_order, 'ASC' ); ?> ><?php _e( 'Ascending', 'wpp' ); ?></option>
                </select>

              </li>
            <?php endif; ?>
            
            <li class="wpp_development_advanced_option">
              <label for="<?php echo $this->get_field_id( 'use_pagi' ); ?>">
                <input id="<?php echo $this->get_field_id( 'use_pagi' ); ?>"
                       name="<?php echo $this->get_field_name( 'use_pagi' ); ?>" type="checkbox"
                       value="on" <?php if ( $use_pagi == 'on' ) echo " checked='checked';"; ?> />
                <?php _e( 'Use pagination', 'wpp' ); ?>
              </label>
            </li>

            <li class="wpp_development_advanced_option">
              <label for="<?php echo $this->get_field_id( 'strict_search' ); ?>">
                <input id="<?php echo $this->get_field_id( 'strict_search' ); ?>"
                       name="<?php echo $this->get_field_name( 'strict_search' ); ?>" type="checkbox"
                       value="on" <?php if ( $strict_search == 'on' ) echo " checked='checked';"; ?> />
                <?php _e( 'Strict Search', 'wpp' ); ?>
              </label>
            </li>
            
            <li class="wpp_development_advanced_option">
              <label for="<?php echo $this->get_field_id( 'per_page' ); ?>"><?php _e( 'Items per page', 'wpp' ); ?>
                <input style="width:30px" id="<?php echo $this->get_field_id( 'per_page' ); ?>"
                       name="<?php echo $this->get_field_name( 'per_page' ); ?>" type="text"
                       value="<?php echo $per_page; ?>"/>
              </label>
            </li>

            <li>
              <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Search Options', 'wpp' ); ?></span>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  <?php

  }

}

// Default function to use in template directly
function wpp_search_widget( $args = false, $custom = false ) {
  global $wp_properties;

  if ( !$args ) {
    $args = array(
      'before_title' => '<h3>',
      'after_title' => '</h3>',
      'before_widget' => '',
      'after_widget' => ''
    );
  }

  // $searchable_attributes = array('bedrooms','bathrooms','area','city', 'price');
  $searchable_attributes = $custom;
  $searchable_property_types = $wp_properties[ 'searchable_property_types' ];

  $default = array(
    'title' => __( 'Search Properies', 'wpp' ),
    'use_pagi' => 'on',
    'per_page' => 10,
    'searchable_attributes' => $searchable_attributes,
    'searchable_property_types' => $searchable_property_types
  );

  if ( $custom ) {
    $default = array_merge( $default, (array)$custom );
  }

  $count = strlen( implode( '-', $default[ 'searchable_attributes' ] ) ) . strlen( implode( '-', $default[ 'searchable_property_types' ] ) );

  WPP_F::get_search_values( $searchable_attributes, $searchable_property_types, false, 'searchpropertieswidget-' . $count );

  SearchPropertiesWidget::widget( $args, $default );
}

/**
Property Gallery Widget
 */
class GalleryPropertiesWidget extends WP_Widget {

  /** constructor */
  function GalleryPropertiesWidget() {
    parent::WP_Widget( false, $name = sprintf( __( '%1s Gallery', 'wpp' ), WPP_F::property_label() ), array( 'description' => __( 'List of all images attached to the current property', 'wpp' ) ) );
  }

  /** @see WP_Widget::widget */
  function widget( $args, $instance ) {
    global $wp_properties, $post, $property;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract( $args );

    $title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
    $image_type = !empty( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : 'thumbnail';
    $big_image_type = isset( $instance[ 'big_image_type' ] ) ? $instance[ 'big_image_type' ] : false;
    $gallery_count = isset( $instance[ 'gallery_count' ] ) ? $instance[ 'gallery_count' ] : false;
    $show_caption = isset( $instance[ 'show_caption' ] ) ? $instance[ 'show_caption' ] : false;
    $show_description = isset( $instance[ 'show_description' ] ) ? $instance[ 'show_description' ] : false;
    $gallery = isset( $post->gallery ) ? (array)$post->gallery : ( isset( $property[ 'gallery' ] ) ? (array)$property[ 'gallery' ] : array() );

    $slideshow_order = maybe_unserialize( ( $post->slideshow_images ) ? $post->slideshow_images : $property[ 'slideshow_images' ] );
    $gallery_order = maybe_unserialize( !empty( $post->gallery_images ) ? $post->gallery_images : ( !empty( $property[ 'gallery_images' ] ) ? $property[ 'gallery_images' ] : false ) );

    //** Calculate order of images */
    if ( is_array( $slideshow_order ) || is_array( $gallery_order ) ) {
      $order = array_unique( array_merge( (array) $slideshow_order, (array) $gallery_order ) );
      $prepared_gallery_images = array();
      //** Get images from the list of images by order */
      foreach ( $order as $order_id ) {
        foreach ( $gallery as $image_slug => $gallery_image_data ) {
          if ( $gallery_image_data[ 'attachment_id' ] == $order_id ) {
            $prepared_gallery_images[ $image_slug ] = $gallery_image_data;
            unset( $gallery[ $image_slug ] );
          }
        }
      }
      //** Be sure we show ALL property images in gallery */
      $gallery = array_merge( $prepared_gallery_images, $gallery );
    }

    if ( !is_array( $gallery ) ) {
      return NULL;
    }
    
    $thumbnail_dimensions = WPP_F::image_sizes( $image_type );

    //** The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique */
    /*
      Removed since this will cause problems with jQuery Tabs in Denali.
      $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
    */

    $html[ ] = $before_widget;
    $html[ ] = "<div class='wpp_gallery_widget'>";

    if ( $title ) {
      $html[ ] = $before_title . $title . $after_title;
    }

    ob_start();

    if ( is_array( $gallery ) ) {

      $real_count = 0;

      foreach ( $gallery as $image ) {

        $thumb_image = wpp_get_image_link( $image[ 'attachment_id' ], $image_type );
        ?>
        <div class="sidebar_gallery_item">
          <?php if ( !empty( $big_image_type ) ) : ?>
            <?php $big_image = wpp_get_image_link( $image[ 'attachment_id' ], $big_image_type ); ?>
            <a href="<?php echo $big_image; ?>" class="fancybox_image thumbnail" rel="property_gallery">
              <img src="<?php echo $thumb_image; ?>"
                   title="<?php echo esc_attr( $image[ 'post_excerpt' ] ? $image[ 'post_excerpt' ] : $image[ 'post_title' ] . ' - ' . $post->post_title ); ?>"
                   alt="<?php echo esc_attr( $image[ 'post_excerpt' ] ? $image[ 'post_excerpt' ] : $image[ 'post_title' ] ); ?>"
                   class="wpp_gallery_widget_image size-thumbnail "
                   width="<?php echo $thumbnail_dimensions[ 'width' ]; ?>"
                   height="<?php echo $thumbnail_dimensions[ 'height' ]; ?>"/>
            </a>
          <?php else : ?>
            <img src="<?php echo $thumb_image; ?>"
                 title="<?php echo esc_attr( $image[ 'post_excerpt' ] ? $image[ 'post_excerpt' ] : $image[ 'post_title' ] . ' - ' . $post->post_title ); ?>"
                 alt="<?php echo esc_attr( $image[ 'post_excerpt' ] ? $image[ 'post_excerpt' ] : $image[ 'post_title' ] ); ?>"
                 class="wpp_gallery_widget_image size-thumbnail "
                 width="<?php echo $thumbnail_dimensions[ 'width' ]; ?>"
                 height="<?php echo $thumbnail_dimensions[ 'height' ]; ?>"/>            <?php endif; ?>
          <?php if ( $show_caption == 'on' && !empty( $image[ 'post_excerpt' ] ) ) { ?>
            <div class="wpp_image_widget_caption"><?php echo $image[ 'post_excerpt' ]; ?></div>
          <?php } ?>

          <?php if ( $show_description == 'on' ) { ?>
            <div class="wpp_image_widget_description"><?php echo $image[ 'post_content' ]; ?></div>
          <?php } ?>

        </div>
        <?php
        $real_count++;

        if ( !empty( $gallery_count ) && $gallery_count == $real_count ) {
          break;
        }

      }
    }

    $html[ 'images' ] = ob_get_contents();
    ob_end_clean();

    $html[ ] = "</div>";
    $html[ ] = $after_widget;

    $html = apply_filters( 'wpp_widget_property_gallery', $html, array( 'args' => $args, 'instance' => $instance, 'post' => $post ) );

    if ( !empty( $html[ 'images' ] ) && is_array( $html ) ) {
      echo implode( '', $html );
    }

    return;

  }

  /** @see WP_Widget::update */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /** @see WP_Widget::form */
  function form( $instance ) {

    global $wp_properties;
    $title = isset( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : '';
    $image_type = isset( $instance[ 'image_type' ] ) ? $instance[ 'image_type' ] : false;
    $big_image_type = isset( $instance[ 'big_image_type' ] ) ? $instance[ 'big_image_type' ] : false;
    $show_caption = isset( $instance[ 'show_caption' ] ) ? $instance[ 'show_caption' ] : false;
    $show_description = isset( $instance[ 'show_description' ] ) ? $instance[ 'show_description' ] : false;
    $gallery_count = isset( $instance[ 'gallery_count' ] ) ? $instance[ 'gallery_count' ] : false; 
    
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
             name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Regular Size:' ); ?></label>
      <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
    </p>

    <p class="wpp_gallery_big_image_type">
      <label for="<?php echo $this->get_field_id( 'big_image_type' ); ?>"><?php _e( 'Large Image Size:' ); ?></label>
      <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'big_image_type' ) . "&selected=" . $big_image_type ); ?>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'gallery_count' ) ?>"></label>
      <?php $number_of_images = '<input size="3" type="text" id="' . $this->get_field_id( 'gallery_count' ) . '" name="' . $this->get_field_name( 'gallery_count' ) . '" value="' . $gallery_count . '" />'; ?>
      <?php echo sprintf( __( 'Show %s Images', 'wpp' ), $number_of_images ); ?>
    </p>

    <p>
      <input name="<?php echo $this->get_field_name( 'show_caption' ); ?>"
             id="<?php echo $this->get_field_id( 'show_caption' ) ?>"
             type="checkbox" <?php checked( 'on', $show_caption ); ?> value="on"/>
      <label
        for="<?php echo $this->get_field_id( 'show_caption' ) ?>"><?php _e( 'Show Image Captions', 'wpp' ); ?></label>
    </p>

    <p class="wpp_gallery_show_description">
      <input name="<?php echo $this->get_field_name( 'show_description' ); ?>"
             id="<?php echo $this->get_field_id( 'show_description' ) ?>"
             type="checkbox" <?php checked( 'on', $show_description ); ?> value="on"/>
      <label
        for="<?php echo $this->get_field_id( 'show_description' ) ?>"><?php _e( 'Show Image Descriptions.', 'wpp' ); ?></label>
    </p>

    <?php do_action( 'wpp_widget_slideshow_bottom', array( 'this_object' => $this, 'instance' => $instance ) ); ?>


  <?php

  }

}
