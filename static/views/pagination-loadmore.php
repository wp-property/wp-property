<?php
/**
 * Property Overview Pagination
 * Pagination Type: 'Load more'
 *
 * To modify template, copy the file to root of your theme.
 */
global $wpp_query;
?>
<div class="wpp_pagination_buttons_wrapper pagination-loadmore">
  <button class="wpp_loadmore_button" data-button="<?php echo base64_encode(json_encode($wpp_query)); ?>"><?php _e('Load more', ud_get_wp_property()->domain); ?></button>
</div>