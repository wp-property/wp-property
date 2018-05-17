<?php

/**
 * Class GalleryPropertiesWidget
 */
class GalleryPropertiesWidget extends WP_Widget
{

  /**
   * Construct
   */
  function __construct()
  {
    parent::__construct(false, $name = sprintf(__('%1s Gallery', ud_get_wp_property()->domain), WPP_F::property_label()), array('description' => __('List of all images attached to the current property', ud_get_wp_property()->domain)));
  }

  /**
   * Widget body
   * @param array $args
   * @param array $instance
   */
  function widget($args, $instance)
  {
    global $wp_properties, $post, $property;
    $before_widget = '';
    $before_title = '';
    $after_title = '';
    $after_widget = '';
    extract($args);

    $title = isset($instance['title']) ? $instance['title'] : '';
    $image_type = !empty($instance['image_type']) ? $instance['image_type'] : 'thumbnail';
    $big_image_type = isset($instance['big_image_type']) ? $instance['big_image_type'] : false;
    $gallery_count = isset($instance['gallery_count']) ? $instance['gallery_count'] : false;
    $show_caption = isset($instance['show_caption']) ? $instance['show_caption'] : false;
    $show_description = isset($instance['show_description']) ? $instance['show_description'] : false;
    $gallery = !empty($post->gallery) ? (array)$post->gallery : (!empty($property['gallery']) ? (array)$property['gallery'] : array());

    $slideshow_images = !empty($post->slideshow_images) ? $post->slideshow_images : (!empty($property['slideshow_images']) ? $property['slideshow_images'] : false);
    $slideshow_order = maybe_unserialize($slideshow_images);
    $gallery_order = maybe_unserialize(!empty($post->gallery_images) ? $post->gallery_images : (!empty($property['gallery_images']) ? $property['gallery_images'] : false));

    //** Calculate order of images */
    $_meta_attached = get_post_meta($post->ID, 'wpp_media');
    $prepared_gallery_images = array();
    if (!empty($_meta_attached) && is_array($_meta_attached)) {
      //** Get images from the list of images by order */
      foreach ($_meta_attached as $_meta_attached_id) {
        foreach ($gallery as $image_slug => $gallery_image_data) {
          if ($gallery_image_data['attachment_id'] == (int) $_meta_attached_id) {
            $prepared_gallery_images[$image_slug] = $gallery_image_data;
            unset($gallery[$image_slug]);
          }
        }
      }
      $gallery = $prepared_gallery_images;
    } else if (is_array($slideshow_order) || is_array($gallery_order)) {
      $order = array_unique(array_merge((array)$slideshow_order, (array)$gallery_order));
      //** Get images from the list of images by order */
      foreach ($order as $order_id) {
        foreach ($gallery as $image_slug => $gallery_image_data) {
          if ($gallery_image_data['attachment_id'] == $order_id) {
            $prepared_gallery_images[$image_slug] = $gallery_image_data;
            unset($gallery[$image_slug]);
          }
        }
      }
      //** Be sure we show ALL property images in gallery */
      $gallery = array_merge($prepared_gallery_images, $gallery);
    }

    if (!is_array($gallery)) {
      return NULL;
    }

    $thumbnail_dimensions = WPP_F::image_sizes($image_type);

    //** The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique */
    /*
      Removed since this will cause problems with jQuery Tabs in Denali.
      $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
    */

    $html[] = $before_widget;

    if (WPP_LEGACY_WIDGETS) {
      $html[] = "<div class='wpp_gallery_widget'>";
    } else {
      $html[] = "<div class='wpp_gallery_widget_v2'>";
    }

    if ($title) {
      $html[] = $before_title . $title . $after_title;
    }

    ob_start();

    if (is_array($gallery)) {
      echo WPP_LEGACY_WIDGETS ? '' : '<div class="wpp_gallery_widget_v2_container">';
      $real_count = 0;
      foreach ($gallery as $image) {
        $thumb_image = wpp_get_image_link($image['attachment_id'], $image_type);
        $thumb_image_title = !empty($image['post_title']) ? trim(strip_tags($image['post_title'])) : trim(strip_tags($post->post_title));
        $alt = get_post_meta($image['attachment_id'], '_wp_attachment_image_alt', true);
        $thumb_image_alt = !empty($alt) ? trim(strip_tags($alt)) : $thumb_image_title;
        ?>
        <div class="sidebar_gallery_item">
          <?php if (!empty($big_image_type)) : ?>
            <?php $big_image = wpp_get_image_link($image['attachment_id'], $big_image_type); ?>
            <a href="<?php echo $big_image; ?>" class="fancybox_image thumbnail" rel="property_gallery">
              <img src="<?php echo $thumb_image; ?>"
                   title="<?php echo $thumb_image_title; ?>"
                   alt="<?php echo $thumb_image_alt; ?>"
                   class="wpp_gallery_widget_image size-thumbnail "
                   width="<?php echo $thumbnail_dimensions['width']; ?>"
                   height="<?php echo $thumbnail_dimensions['height']; ?>"/>
            </a>
          <?php else : ?>
            <img src="<?php echo $thumb_image; ?>"
                 title="<?php echo $thumb_image_title; ?>"
                 alt="<?php echo $thumb_image_alt; ?>"
                 class="wpp_gallery_widget_image size-thumbnail "
                 width="<?php echo $thumbnail_dimensions['width']; ?>"
                 height="<?php echo $thumbnail_dimensions['height']; ?>"/>
          <?php endif; ?>

          <?php if ($show_caption == 'on' && !empty($image['post_excerpt'])) { ?>
            <div class="wpp_image_widget_caption"><?php echo $image['post_excerpt']; ?></div>
          <?php } ?>

          <?php if ($show_description == 'on' && !empty($image['post_content'])) { ?>
            <div class="wpp_image_widget_description"><?php echo $image['post_content']; ?></div>
          <?php } ?>

        </div>
        <?php
        $real_count++;
        if (!empty($gallery_count) && $gallery_count == $real_count) {
          break;
        }
      }
      echo WPP_LEGACY_WIDGETS ? '' : '</div>';
    }

    $html['images'] = ob_get_contents();
    ob_end_clean();

    $html[] = "</div>";
    $html[] = $after_widget;

    $html = apply_filters('wpp_widget_property_gallery', $html, array('args' => $args, 'instance' => $instance, 'post' => $post));

    if (!empty($html['images']) && is_array($html)) {
      echo implode('', $html);
    }

    return;

  }

  /** @see WP_Widget::update */
  function update($new_instance, $old_instance)
  {
    return $new_instance;
  }

  /** @see WP_Widget::form */
  function form($instance)
  {

    global $wp_properties;
    $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
    $image_type = isset($instance['image_type']) ? $instance['image_type'] : false;
    $big_image_type = isset($instance['big_image_type']) ? $instance['big_image_type'] : false;
    $show_caption = isset($instance['show_caption']) ? $instance['show_caption'] : false;
    $show_description = isset($instance['show_description']) ? $instance['show_description'] : false;
    $gallery_count = isset($instance['gallery_count']) ? $instance['gallery_count'] : false;

    ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
             name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>"/>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Regular Size:'); ?></label>
      <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
    </p>

    <p class="wpp_gallery_big_image_type">
      <label for="<?php echo $this->get_field_id('big_image_type'); ?>"><?php _e('Large Image Size:'); ?></label>
      <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('big_image_type') . "&selected=" . $big_image_type); ?>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id('gallery_count') ?>"></label>
      <?php $number_of_images = '<input size="3" type="text" id="' . $this->get_field_id('gallery_count') . '" name="' . $this->get_field_name('gallery_count') . '" value="' . $gallery_count . '" />'; ?>
      <?php echo sprintf(__('Show %s Images', ud_get_wp_property()->domain), $number_of_images); ?>
    </p>

    <p>
      <input name="<?php echo $this->get_field_name('show_caption'); ?>"
             id="<?php echo $this->get_field_id('show_caption') ?>"
             type="checkbox" <?php checked('on', $show_caption); ?> value="on"/>
      <label
        for="<?php echo $this->get_field_id('show_caption') ?>"><?php _e('Show Image Captions', ud_get_wp_property()->domain); ?></label>
    </p>

    <p class="wpp_gallery_show_description">
      <input name="<?php echo $this->get_field_name('show_description'); ?>"
             id="<?php echo $this->get_field_id('show_description') ?>"
             type="checkbox" <?php checked('on', $show_description); ?> value="on"/>
      <label
        for="<?php echo $this->get_field_id('show_description') ?>"><?php _e('Show Image Descriptions.', ud_get_wp_property()->domain); ?></label>
    </p>

    <?php do_action('wpp_widget_slideshow_bottom', array('this_object' => $this, 'instance' => $instance)); ?>


    <?php

  }

}

/**
 * Register widget
 */
add_action('widgets_init', function () {
  register_widget("GalleryPropertiesWidget");
});