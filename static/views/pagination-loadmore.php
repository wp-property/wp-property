<?php
/**
 * Property Overview Pagination
 * Pagination Type: 'Load more'
 *
 * To modify template, copy the file to root of your theme.
 */
$pages = ceil($wpp_query['properties']['total'] / $wpp_query['per_page']);
?>
<div class="wpp_pagination_buttons_wrapper pagination-loadmore">
  <button class="wpp_loadmore_button" data-page="2" data-pages="<?php echo $pages; ?>"><?php _e('Load more', ud_get_wp_property()->domain); ?></button>
  <span class="wpp_to_top"></span>
</div>