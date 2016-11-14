jQuery(document).ready(function () {
  $ = jQuery.noConflict();//allow shorthand without conflict

  //handle form submits
  $('#wpp-setup-assistant').submit(postBackForm);

  function postBackForm() {
    
    var data = jQuery(this).serialize();
    
    // no need to do anything for first screen
    if($isScreen==1)
      return false;
    
    if( $('#soflow').val()=="create-new" && $(".wpp-base-slug-new").val()==""){
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
      beforeSend : function(){
        showLoader();
      },
      success: function (response) {
        hideLoader();
        var data = jQuery.parseJSON(response);
        if(data.props_over!='false' && data.props_over!=false)
          $(".btn_single_page.oviews").attr("href",data.props_over);
        if(data.props_single!='false' && data.props_single!=false)
          $(".btn_single_page.props").attr("href",data.props_single);
      },
      error: function () {
        hideLoader();
        alert(wpp.strings.undefined_error);
      }
    });
    return false;
  }
  function showLoader(){
    $( ".loader-div" ).fadeTo( "fast" , 1);
    $( ".owl-item" ).fadeTo( "fast" , 0);
  }

  function hideLoader(){
    $( ".loader-div" ).fadeTo( "fast" , 0);
    $( ".owl-item" ).fadeTo( "fast" , 1);
  }
  //handle each screen individually
  function propAssistScreens() {
    $isScreen = $(".owl-page.active").index() + 1;
    // maybe add some screen specific
    switch ($isScreen) {
      case 6 :
        jQuery('#wpp-setup-assistant').submit();
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
    rewindNav: false,
    touchDrag: false,
    mouseDrag: false,
    navigationText: [
      "<i class='icon-chevron-left icon-white'></i>",
      "<i class='icon-chevron-right icon-white'></i>"
    ],
    beforeMove: propAssistScreens,
    afterAction :function(){
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
  $('#soflow').on('change', function() {
    if(this.value=="create-new"){
      $('.wpp-base-slug-new').fadeIn("fast");
    }
    else{
      $('.wpp-base-slug-new').fadeOut("fast");
    }
  });
  
  //on click of last screen option buttons
  $(".btn_single_page").click(function (e) {
    e.stopPropagation();
    e.preventDefault();
    
    if(this.href.indexOf("javascript:;")>-1 || this.href=="" || this.href=="false"){
      alert(wpp_property_assistant.no_link_available);
    }
    else{
      var win = window.open(this.href, '_blank');
      win.focus();
    }
    return false;
  });
});