<?php
/**
Other Properties Widget
 */
class OtherPropertiesWidget extends WP_Widget {

  function OtherPropertiesWidget() {

    $property_label = strtolower( \UsabilityDynamics\WPP\Utility::property_label( 'plural' ) );

    parent::__construct(
      false,
      sprintf( __( 'Other %1s', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'plural' ) ),
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

    $title = apply_filters( 'widget_title', $instance[ 'title' ] );
    $instance = apply_filters( 'OtherPropertiesWidget', $instance );
    $show_title = $instance[ 'show_title' ];
    $image_type = $instance[ 'image_type' ];
    $hide_image = $instance[ 'hide_image' ];
    $stats = $instance[ 'stats' ];
    $address_format = $instance[ 'address_format' ];
    $amount_items = $instance[ 'amount_items' ];
    $show_properties_of_type = $instance[ 'show_properties_of_type' ];
    $shuffle_order = $instance[ 'shuffle_order' ];

    if ( !isset( $post->ID ) || ( $post->post_parent == 0 && $show_properties_of_type != 'on' ) ) {
      return;
    }


    if ( is_object( $property ) ) {
      $this_property = (array)$property;
    } else {
      $this_property = $property;
    }

    if ( $post->post_parent ) {
      $properties = get_posts( array(
        'numberposts' => !empty( $amount_items ) ? $amount_items : 0,
        'post_type' => 'property',
        'post_status' => 'publish',
        'post_parent' => $post->post_parent,
        'exclude' => $post->ID
      ) );
    } else {
      $properties = \UsabilityDynamics\WPP\Utility::get_properties( "property_type={$this_property['property_type']}&pagi=0--$amount_items" );
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

      $this_property = \UsabilityDynamics\WPP\Utility::get_property( $property_id, 'return_object=true' );

      $this_property = prepare_property_for_display( $this_property );

      $image = wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) );

      ?>
      <div class="property_widget_block apartment_entry clearfix"
        style="<?php echo( $image[ 'width' ] ? 'width: ' . ( $image[ 'width' ] + 5 ) . 'px;' : '' ); ?>">

        <?php if ( $hide_image !== 'on' && !empty( $image ) ) { ?>
          <a class="sidebar_property_thumbnail thumbnail" href="<?php echo $this_property->permalink; ?>">
            <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>"
              src="<?php echo $image[ 'link' ]; ?>"
              alt="<?php echo sprintf( __( '%s at %s for %s', 'wpp' ), $this_property->post_title, $this_property->location, $this_property->price ); ?>"/>
          </a>
        <?php } ?>

        <?php if ( $show_title == 'on' ) { ?>
          <p class="title"><a
              href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
        <?php } ?>

        <ul class="wpp_widget_attribute_list">
          <?php

          if ( is_array( $stats ) ) {
            foreach ( $stats as $stat ) {

              $content = $this_property->$stat;

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
  function update( $new_instance ) {
    return $new_instance;
  }

  /** @see WP_Widget::form */
  function form( $instance ) {

    global $wp_properties;
    $title = esc_attr( $instance[ 'title' ] );
    $show_title = $instance[ 'show_title' ];
    $amount_items = esc_attr( $instance[ 'amount_items' ] );
    $address_format = esc_attr( $instance[ 'address_format' ] );
    $image_type = esc_attr( $instance[ 'image_type' ] );
    $property_stats = $instance[ 'stats' ];
    $hide_image = $instance[ 'hide_image' ];
    $enable_more = $instance[ 'enable_more' ];
    $enable_view_all = $instance[ 'enable_view_all' ];
    $show_properties_of_type = $instance[ 'show_properties_of_type' ];
    $shuffle_order = $instance[ 'shuffle_order' ];
    $address_format = !empty( $address_format ) ? $address_format : '[street_number] [street_name], [city], [state]';

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
      <?php \UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
    </p>

    <p>
      <label
        for="<?php echo $this->get_field_id( 'amount_items' ); ?>"><?php _e( 'Listings to display?', 'wpp' ); ?></label>
      <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
        name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
        value="<?php echo ( empty( $amount_items ) ) ? 5 : $amount_items; ?>"/>
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