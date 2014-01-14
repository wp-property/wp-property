<?php
/**
 * Name: Gallery Properties
 * ID: Gallery_Properties_Widget
 * Type: widget
 * Group: WP-Property
 * Class: \UsabilityDynamics\WPP\Gallery_Properties_Widget
 * Version: 2.0.0
 * Description: List of all images attached to the current property
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Gallery_Properties_Widget' ) ) {
    /**
    Property Gallery Widget
     */
    class Gallery_Properties_Widget extends Widget {

      /** constructor */
      function __construct() {

        parent::__construct( 
          false, 
          $name = sprintf( __( '%1s Gallery', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label() ), 
          array(
            'description' => sprintf(__( 'List of all images attached to the current %1s', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) )
          )
        );

      }

      /** @see WP_Widget::widget */
      function widget( $args, $instance ) {
        global $wp_properties, $post, $property;
        $before_widget = '';
        $before_title = '';
        $after_title = '';
        $after_widget = '';
        extract( $args );

        $title = apply_filters( 'widget_title', $instance[ 'title' ] );
        $image_type = esc_attr( $instance[ 'image_type' ] );
        $big_image_type = esc_attr( $instance[ 'big_image_type' ] );
        $gallery_count = esc_attr( $instance[ 'gallery_count' ] );
        $show_caption = esc_attr( $instance[ 'show_caption' ] );
        $show_description = esc_attr( $instance[ 'show_description' ] );
        $gallery = (array)( ( $post->gallery ) ? $post->gallery : $property[ 'gallery' ] );

        $slideshow_order = maybe_unserialize( ( $post->slideshow_images ) ? $post->slideshow_images : $property[ 'slideshow_images' ] );
        $gallery_order = maybe_unserialize( ( $post->gallery_images ) ? $post->gallery_images : $property[ 'gallery_images' ] );

        //** Calculate order of images */
        if ( is_array( $slideshow_order ) && is_array( $gallery_order ) ) {
          $order = array_merge( $slideshow_order, $gallery_order );
          $prepared_gallery_images = array();

          //** Get images from the list of images by order */
          foreach ( $order as $order_id ) {
            foreach ( $gallery as $image_slug => $gallery_image_data ) {
              if ( $gallery_image_data[ 'attachment_id' ] == $order_id ) {
                $prepared_gallery_images[ $image_slug ] = $gallery_image_data;
              }
            }
          }

          //** Be sure we show ALL property images in gallery */
          $gallery = array_merge( $prepared_gallery_images, $gallery );
        }

        if ( empty( $image_type ) ) {
          $image_type = 'thumbnail';
        }

        if ( !is_array( $gallery ) ) {
          return;
        }

        $thumbnail_dimensions = \UsabilityDynamics\WPP\Utility::image_sizes( $image_type );

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
      function update( $new_instance ) {
        return $new_instance;
      }

      /** @see WP_Widget::form */
      function form( $instance ) {

        global $wp_properties;
        $title = esc_attr( $instance[ 'title' ] );
        $image_type = $instance[ 'image_type' ];
        $big_image_type = $instance[ 'big_image_type' ];
        $show_caption = $instance[ 'show_caption' ];
        $show_description = $instance[ 'show_description' ];
        $gallery_count = $instance[ 'gallery_count' ]; ?>

        <p>
          <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?></label>
          <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
            name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
        </p>

        <p>
          <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Regular Size:', 'wpp' ); ?></label>
          <?php \UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
        </p>

        <p class="wpp_gallery_big_image_type">
          <label for="<?php echo $this->get_field_id( 'big_image_type' ); ?>"><?php _e( 'Large Image Size:', 'wpp' ); ?></label>
          <?php \UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=" . $this->get_field_name( 'big_image_type' ) . "&selected=" . $big_image_type ); ?>
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
    
  }
  
}