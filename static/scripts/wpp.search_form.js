/**
 * Search form scripts
 *
 */
jQuery(document).ready(function () {
  jQuery('.wpp_search_elements > li.wpp_search_group:first-child').addClass('active');

  jQuery(document).on('click', '.wpp_search_elements > li.wpp_search_group > span.wpp_search_group_title', function() {
    jQuery(this).parent('li.wpp_search_group').toggleClass('active');
  })
});