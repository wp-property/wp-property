<?php
/**
 * [property_terms] template
 *
 * To modify it, copy it to your theme's root.
 */

echo '<ul class="' . $taxonomy .'_list">';
echo get_the_term_list( $property_id, $taxonomy, '<li>', '</li><li>', '</li>' );
echo '</ul>';

/*  if(!empty($wp_properties['taxonomies'])) foreach($wp_properties['taxonomies'] as $tax_slug => $tax_data): $data['type'] = $tax_slug; ?>
  <?php if(get_features("type={$tax_slug}&format=count")):  ?>
    <div class="<?php echo $tax_slug; ?>_list">
      <h2><?php echo $tax_data['label']; ?></h2>
      <ul class="clearfix">
        <?php get_features($data); ?>
      </ul>
    </div>
  <?php endif; ?>
<?php endforeach; */ ?>