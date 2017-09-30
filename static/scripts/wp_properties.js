jQuery(document).ready(function () {


  /* Scroll to top of pagination */
  jQuery(document).bind('wpp_pagination_change', function (e, data) {
    var overview_id = data.overview_id;
    var position = jQuery("#wpp_shortcode_" + overview_id).offset();
    if (typeof jQuery.scrollTo !== 'undefined') {
      jQuery.scrollTo(position.top - 40 + 'px', 1500);
    }
  });

  jQuery(".ui-tabs").bind("tabsshow", function (event, ui) {
    var panel = ui.panel;
    jQuery(document).trigger("wpp::ui-tabs::tabsshow", panel);
  });

  jQuery(".ui-tabs").bind("tabsselect", function (event, ui) {
    var panel = ui.panel;
    jQuery(document).trigger("wpp::ui-tabs::tabsselect", panel);
  });

  // .wpp_features_box
  jQuery('.wpp_features_box').each(function (k, v) {
    if (jQuery(v).width() < 600) {
      jQuery(v).addClass('wpp_features_box_full_width');
    }
  });

});
