<?php
/**
 * [property_attributes] template
 *
 * To modify it, copy it to your theme's root.
 */

?>

<?php if ( $data[ 'display' ] == 'list' && $data[ 'sort_by_groups' ] != 'true' ) : ?>
  <ul id="property_stats" class="<?php wpp_css('property::property_stats', "property_stats overview_stats list"); ?>">
    <?php @draw_stats($data); ?>
  </ul>
<?php else: ?>
  <?php @draw_stats($data); ?>
<?php endif; ?>