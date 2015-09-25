<?php
/**
 * [property_taxonomies] template
 *
 * To modify it, copy it to your theme's root.
 */

global $wp_properties;
?>

<?php if(!empty($wp_properties['taxonomies'])) foreach($wp_properties['taxonomies'] as $tax_slug => $tax_data): $data['type'] = $tax_slug; ?>
  <?php if(get_features("type={$tax_slug}&format=count")):  ?>
    <div class="<?php echo $tax_slug; ?>_list">
      <h2><?php echo $tax_data['label']; ?></h2>
      <ul class="clearfix">
        <?php get_features($data); ?>
      </ul>
    </div>
  <?php endif; ?>
<?php endforeach; ?>