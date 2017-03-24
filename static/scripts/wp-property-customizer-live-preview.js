(function ($) {

  wp.customize('layouts_property_overview_select', function (value) {
    value.bind(function (newval) {
      $('.hb__contact-form .hb__title').html(newval);
    });
  });

})(jQuery);
