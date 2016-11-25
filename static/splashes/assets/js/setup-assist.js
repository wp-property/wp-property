jQuery(document).ready(function () {
  $ = jQuery.noConflict();//allow shorthand without conflict

  //handle form submits
  $('#wpp-setup-assistant').submit(postBackForm);

  function postBackForm() {

    var data = jQuery(this).serialize();

    // no need to do anything for first screen
    if ($isScreen == 1)
      return false;

    if ($('#soflow').val() == "create-new" && $(".wpp-base-slug-new").val() == "") {
      alert(wpp_property_assistant.properties_page_error);
      return false;
    }

    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: {
        action: 'wpp_save_setup_settings',
        data: data
      },
      beforeSend: function () {
        $showLoader();
      },
      success: function (response) {
        $hideLoader();
//        var data = jQuery.parseJSON(response);
        
      },
      error: function () {
        $hideLoader();
        alert(wpp.strings.undefined_error);
      }
    });
    return false;
  }
  $showLoader = function () {
    $(".loader-div").fadeTo("fast", 1);
    $(".owl-item").fadeTo("fast", 0);
  }

  $hideLoader = function () {
    $(".loader-div").fadeTo("fast", 0);
    $(".owl-item").fadeTo("fast", 1);
  }
  //handle each screen individually
  function propAssistScreens() {
    
    $indexOfLastScreen = $(".owl-page").length;
    $isScreen = $(".owl-page.active").index() + 1 + 1;
    // maybe add some screen specific
    switch ($isScreen) {
      case $indexOfLastScreen:
        jQuery('#wpp-setup-assistant').submit();
        break;
      default:
//        console.log("reached default screen");
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
    rewindNav: false,
    touchDrag: false,
    mouseDrag: false,
    navigationText: [
      "<i class='icon-chevron-left icon-white'></i>",
      "<i class='icon-chevron-right icon-white'></i>"
    ],
    beforeMove: propAssistScreens,
    afterAction: function () {
//      console.log("afterAction")
    },
    afterMove: function () {
//      console.log("afterMove")
    },
    singleItem: true
  });
  var wpp_owl = $("#wpp-splash-screen-owl").data('owlCarousel');
  // letsgo button on screen one should mimic next event
  $(".btn_letsgo").click(function () {
    wpp_owl.next();
  });

  //on change of "Choose default properties pages"
  $('#soflow').on('change', function () {
    if (this.value == "create-new") {
      $('.wpp-base-slug-new').fadeIn("fast");
    }
    else {
      $('.wpp-base-slug-new').fadeOut("fast");
    }
  });
  
  //make checkboxes container clickable
  $('li.wpp_asst_label').click(function (e) {
    if (e.target != this)
      return;
    el = $(this).find('input');
    el.click();
  });
  $(".wpp_asst_screen .foot-note a").click(function(){$('.wpp_asst_screen .foot-note .wpp_toggl_desctiption').toggle("slow")})
});