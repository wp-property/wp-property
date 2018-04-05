<?php

/**
 * Class OtherPropertiesWidget
 *
 * Deprecated Widget. Enable Legacy Features option on Settings page to activate it.
 */
class OtherPropertiesWidget extends WP_Widget {

  /**
   * Construct
   */
  function __construct() {

    $property_label = strtolower( WPP_F::property_label( 'plural' ) );

    parent::__construct(
        false,
        sprintf( __( 'Other %1s', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ) ),
        array(
            'description' => sprintf( __( 'Display a list of %1s that share a parent with the currently displayed property.', ud_get_wp_property()->domain ), $property_label )
        ),
        array(
            'width' => 300
        )
    );

  }

  /**
   * Widget body
   *
   * @param array $args
   * @param array $instance
   */
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
          'exclude' => $post->ID,
          'suppress_filters' => 0
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

    if (!WPP_LEGACY_WIDGETS) {
      $html[] = "<div class='wpp_other_properties_widget_wrapper'>";
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
                case ( !empty($wp_properties["predefined_values"][$stat]) ):
                  $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}",apply_filters( "wpp::attribute::value", $this_property->$stat, $stat ) ) );
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
                <span class="attribute"><?php echo apply_filters('wpp::attribute::label', $wp_properties[ 'property_stats' ][ $stat ], $stat); ?>:</span>
                <span class="value"><?php echo $content; ?></span>
              </li>
            <?php
            }
          } ?>
        </ul>

        <?php if ( $instance[ 'enable_more' ] == 'on' ) {
          echo '<p class="more"><a href="' . $this_property->permalink . '" class="btn btn-info">' . __( 'More', ud_get_wp_property()->domain ) . '</a></p>';
        } ?>

      </div>

      <?php
      unset( $this_property );
    }

    $html[ 'widget_content' ] = ob_get_contents();
    ob_end_clean();

    if ( $instance[ 'enable_view_all' ] == 'on' ) {
      $html[ ] = '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', ud_get_wp_property()->domain ) . '</a></p>';
    }

    $html[ ] = '</div>';

    if (!WPP_LEGACY_WIDGETS) {
      $html[] = '</div>';
    }

    $html[ ] = $after_widget;

    if ( !empty( $html[ 'widget_content' ] ) ) {
      echo implode( '', $html );
    }

  }

  /**
   * Update callback
   *
   * @param array $new_instance
   * @param array $old_instance
   * @return array
   */
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }

  /**
   * Form handler
   *
   * @param array $instance
   */
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
        jQuery( document ).on( 'change', 'input.check_me_other', function () {
          var parent = jQuery( this ).closest( '.widget-content' );
          if ( jQuery( this ).is( ':checked' ) ) {
            jQuery( 'p.choose_thumb_other', parent ).hide();
          } else {
            jQuery( 'p.choose_thumb_other', parent ).show();
          }
        } )
      } );

    </script>
    <p><?php _e( 'The widget will show other properties that share a parent with the currently displayed property.', ud_get_wp_property()->domain ); ?></p>

    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', ud_get_wp_property()->domain ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
             name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
    </p>

    <p>
      <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me_other"
             name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
             value="on" <?php if ( $hide_image == 'on' ) echo "checked='checked'"; ?> />
      <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>"><?php _e( 'Hide images.', ud_get_wp_property()->domain ); ?></label>
    </p>

    <p
        class="choose_thumb_other" <?php echo ( $hide_image !== 'on' ) ? 'style="display:block;"' : 'style="display:none;"'; ?>>
      <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', ud_get_wp_property()->domain ); ?></label>
      <?php WPP_F::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
    </p>

    <p>
      <label
          for="<?php echo $this->get_field_id( 'amount_items' ); ?>"><?php _e( 'Listings to display?', ud_get_wp_property()->domain ); ?></label>
      <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
             name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
             value="<?php echo $amount_items; ?>"/>
    </p>

    <p><?php _e( 'Select the attributes you want to display: ', ud_get_wp_property()->domain ); ?></p>

    <ul class="wp-tab-panel">
      <li>
        <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
               name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
               value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
        <label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Title', ud_get_wp_property()->domain ); ?></label>
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
      <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', ud_get_wp_property()->domain ); ?>
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
            for="<?php echo $this->get_field_id( 'enable_more' ); ?>"><?php _e( 'Show "More" link?', ud_get_wp_property()->domain ); ?></label>
      </li>
      <li>
        <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
               name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
               value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
        <label
            for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"><?php _e( 'Show "View All" link?', ud_get_wp_property()->domain ); ?></label>
      </li>
      <li>
        <input id="<?php echo $this->get_field_id( 'show_properties_of_type' ); ?>"
               name="<?php echo $this->get_field_name( 'show_properties_of_type' ); ?>" type="checkbox"
               value="on" <?php checked( 'on', $show_properties_of_type ); ?> />
        <label
            for="<?php echo $this->get_field_id( 'show_properties_of_type' ); ?>"><?php _e( 'If property has no parent, show other properties of same type.', ud_get_wp_property()->domain ); ?></label>
      </li>
      <li>
        <input id="<?php echo $this->get_field_id( 'shuffle_order' ); ?>"
               name="<?php echo $this->get_field_name( 'shuffle_order' ); ?>" type="checkbox"
               value="on" <?php checked( 'on', $shuffle_order ); ?> />
        <label
            for="<?php echo $this->get_field_id( 'shuffle_order' ); ?>"><?php _e( 'Randomize order of displayed properties.', ud_get_wp_property()->domain ); ?></label>
      </li>
    </ul>

  <?php
  }

}

/**
 * Register widget
 */
add_action( 'widgets_init', function() {
  if( ud_get_wp_property( 'configuration.enable_legacy_features' ) == 'true' ) {
    register_widget("OtherPropertiesWidget");
  }
});