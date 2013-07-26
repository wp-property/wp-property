jQuery(document).ready(function() {
    
  if(typeof jQuery.fn.fancybox == 'function') {
  
    jQuery("a.fancybox_image").fancybox({
      'transitionIn'	:	'elastic',
      'transitionOut'	:	'elastic',
      'speedIn'    :  600,
      'speedOut'    :  200,
      'overlayShow'  :  false
    });

  }

  jQuery("a.fancybox_image img").click(function(e) {
  
    /* Do nothing in FancyBox is set */
    if(typeof jQuery.fn.fancybox == 'function') {
      return;
    }
    
    /* Fancybox is not set as expected, do not open the image URL */
    e.preventDefault();
    
  });

  /* Scroll to top of pagination */
  jQuery(document).bind('wpp_pagination_change', function(e, data) {
    var overview_id = data.overview_id;    
    var position = jQuery("#wpp_shortcode_" + overview_id).offset();  
    jQuery.scrollTo(position.top - 40 + 'px', 1500);  

  });

});


function wpp_add_commas(nStr) {
  nStr += '';
  x = nStr.split('.');
  x1 = x[0];
  x2 = x.length > 1 ? '.' + x[1] : '';
  var rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1)) {
    x1 = x1.replace(rgx, '$1' + ',' + '$2');
  }
  return x1 + x2;
}