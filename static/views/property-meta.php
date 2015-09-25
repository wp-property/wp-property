<?php
/**
 * [property_meta] template
 *
 * To modify it, copy it to your theme's root.
 */

global $post, $wp_properties;
?>

<?php if(is_array($wp_properties['property_meta'])): ?>
  <?php foreach($wp_properties['property_meta'] as $meta_slug => $meta_title):
    if(empty($post->$meta_slug) || $meta_slug == 'tagline')
      continue;
    ?>
    <h2><?php echo $meta_title; ?></h2>
    <p><?php echo do_shortcode(html_entity_decode($post->$meta_slug)); ?></p>
  <?php endforeach; ?>
<?php endif; ?>