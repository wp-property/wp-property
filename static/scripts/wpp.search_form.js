/**
 * Search form scripts
 *
 */
jQuery(document).ready(function () {
  jQuery('.wpp_search_elements_v2 > .wpp_search_group:first-child').addClass('active');

  jQuery(document).on('click', '.wpp_search_elements_v2 > .wpp_search_group > span.wpp_search_group_title', function () {
    jQuery(this).parent('div.wpp_search_group').toggleClass('active');
  });
});

jQuery(window).load(function () {
  jQuery('body .wpp_shortcode_search_form').each(function () {
    if (jQuery(this).width() < 800) {
      jQuery(this).addClass('wpp_search_one_column');
    }
  });
});