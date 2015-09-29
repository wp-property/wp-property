<?php

/**
 * Class ChildPropertiesWidget
 */
class ChildPropertiesWidget extends WP_Widget {

  /**
   * Constructor
   */
  function __construct() {
    parent::__construct( false, $name = sprintf( __( 'Child %1s', 'wpp' ), WPP_F::property_label( 'plural' ) ), array( 'description' => __( 'Show child properties (if any) for currently displayed property', 'wpp' ) ) );
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
 * Register widget
 */
add_action( 'widgets_init', function() {
  if( class_exists( 'ChildPropertiesWidget' ) ) {
    register_widget( "ChildPropertiesWidget" );
  }
});