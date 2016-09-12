jQuery(document).ready(function () {
  $ = jQuery.noConflict();//allow shorthand without conflict

  //handle form submits
  $('#wpp-setup-assistant').submit(postBackForm);

  function postBackForm() {
    
    var data = jQuery(this).serialize();
    
    // no need to do anything for first screen
    if($isScreen==1)
      return false;
    
    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'wpp_save_setup_settings',
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

  //handle each screen individually
  function propAssistScreens() {
    $isScreen = $(".owl-page.active").index() + 1;
    // maybe add some screen specific
    switch ($isScreen) {
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
