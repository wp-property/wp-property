<?php
/**
 * [property_meta] template
 *
 * To modify it, copy it to your theme's root.
 */

?>
<?php if(is_array( $meta )): ?>
  <?php foreach( $meta as $meta_slug => $meta_title ):
    $meta_value = do_shortcode(html_entity_decode(get_post_meta( $post_id, $meta_slug, true )));
    if(trim($meta_value) != ""):
    ?>
      <h2><?php echo $meta_title; ?></h2>
      <p><?php echo trim($meta_value);?></p>
    <?php 
    endif;
  endforeach; ?>
<?php endif; ?>