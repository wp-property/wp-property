jQuery(document).ready(function () {
  $ = jQuery.noConflict();//allow shorthand without conflict

  //handle form submits
  $('#wpp-setup-assistant').submit(postBackForm);

  function postBackForm() {

    var data = jQuery(this).serialize();

    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'wpp_save_settings',
        data: data
      },
      success: function (response) {
        var data = jQuery.parseJSON(response);
      },
      error: function () {
        alert(wpp.strings.undefined_error);
      }
    });
    return false;
  }

  // handle screen 2: property types and attributes
  function handle_prop_types() {
    // remove previously set props in case user is moving back and forth
    if ($('.wpp_settings_property_stats'))
      $('.wpp_settings_property_stats').remove();

    var propAttrSet = {};
    $('input:checkbox.asst_prop_types').each(function () {
      var sThisVal = (this.checked ? $(this).attr('name') : "");

      if (sThisVal != "") {

        switch (sThisVal) {
          case 'land':
            $.extend(propAttrSet, wpp_property_assistant.property_assistant.land);
            break;
          case 'commercial'  :
            $.extend(propAttrSet, wpp_property_assistant.property_assistant.commercial);
            break;
          default :
            $.extend(propAttrSet, wpp_property_assistant.property_assistant.residential);
            break;
        }

        // add property type
        $('.wpp-asst_hidden-attr')
                .append('<input type="hidden" class="wpp_settings_property_stats" name="wpp_settings[property_types][' +
                        $(this).attr('name') + ']"  value="' + $(this).val() + '" />');

        // make property Searchable
        $('.wpp-asst_hidden-attr')
                .append('<input type="hidden" class="wpp_settings_property_stats" name="wpp_settings[searchable_property_types][]" value="' +
                        $(this).attr('name') + '" />');
        //handle location matters
        $('.wpp-asst_hidden-attr')
                .append('<input type="hidden" class="wpp_settings_property_stats" name="wpp_settings[location_matters][]"  value="' +
                        $(this).attr('name') + '" />');
      }
    });

    // add property attributes
    $.each(propAttrSet, function (index, value) {
      $('.wpp-asst_hidden-attr').append('<input type="hidden" class="wpp_settings_property_stats" name="wpp_settings[property_stats][' +
              index + ']"  value="' + value + '" />');
    });
  }

  //handle each screen individually
  function propAssistScreens() {
    var isScreen = $(".owl-page.active").index() + 1;
    switch (isScreen) {
      case 2:
        handle_prop_types();
        break;
      default:
        console.log("reached default screen");
        break;
    }
  }

  //init owl carousel
  var wpp_owl = $("#wpp-splash-screen-owl");
  wpp_owl.owlCarousel({
    navigation: true,
    slideSpeed: 400,
    paginationSpeed: 400,
    autoPlay: false,
    pagination: true,
    rewindNav: true,
    touchDrag: false,
    mouseDrag: false,
    navigationText: [
      "<i class='icon-chevron-left icon-white'></i>",
      "<i class='icon-chevron-right icon-white'></i>"
    ],
    beforeMove: propAssistScreens,
    afterMove: function () {
      jQuery('#wpp-setup-assistant').submit();
    },
    singleItem: true
  });
  var wpp_owl = $("#wpp-splash-screen-owl").data('owlCarousel');
// letsgo button on screen one should mimic next event
  $(".btn_letsgo").click(function () {
    wpp_owl.next();
  });
});
