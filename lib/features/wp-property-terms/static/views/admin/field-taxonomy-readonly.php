<?php
/**
 * Field Taxonomy Readonly
 */

global $post;

$terms = wp_get_object_terms( $post->ID, $taxonomy );

echo "<pre>";
print_r( $terms );
echo "</pre>";

foreach ( $terms as $term ) : ?>
  <div class="wpp-term-item">
    <div class="wpp-term-desc">
      <i class="dashicons-admin-home dashicons"></i>
    </div>
  </div>
<?php endforeach; ?>