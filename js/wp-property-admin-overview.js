/**
 * WP-Property Admin Overview Scripts
 *
*/

jQuery(document).ready(function() {  

  admin_overview_init();
  
  // Toggling filter options
  jQuery('.wpp_filter_section_title').click(function(){
    var parent = jQuery(this).parents('.wpp_overview_filters');
    jQuery('.wpp_checkbox_filter', parent).slideToggle('fast', function(){
      if(jQuery(this).css('display') == 'none') {
        jQuery('.wpp_filter_show', parent).html('Show');
      } else {
        jQuery('.wpp_filter_show', parent).html('Hide');
      } 
    });
  });
  
  // DataTable check all checkbox
  jQuery("input.check-all", "#wp-list-table").click(function(e){
    if ( e.target.checked ) {
      jQuery("#the-list td.cb input:checkbox").attr('checked', 'checked');
    } else {
      jQuery("#the-list td.cb input:checkbox").removeAttr('checked');
    }
  });
  
});

function admin_overview_init() {
  
  /* Load fancybox if it exists */
  if(typeof jQuery.fn.fancybox == 'function') { 
    jQuery(".fancybox").fancybox({
      'transitionIn'  :  'elastic',
      'transitionOut'  :  'elastic',
      'speedIn'    :  600, 
      'speedOut'    :  200, 
      'overlayShow'  :  false
    });  
  }


  // Toggle Featured Setting
  
  jQuery(".wpp_featured_toggle").click(function(){
    
    var button_id   = jQuery(this).attr("id");
    var post_id   = button_id.replace('wpp_feature_', '');
    var _wpnonce = jQuery(this).attr("nonce");
    
    jQuery.post(
      ajaxurl,
      {
        post_id: post_id,
        action: 'wpp_make_featured',
        _wpnonce: _wpnonce        
      },
      function(data) {
      
        var button = jQuery("#wpp_feature_" + data.post_id);
         if(data.status == 'featured') {
          jQuery(button).val("Featured");
          jQuery(button).addClass('wpp_is_featured');
         }
        if(data.status == 'not_featured') {
          jQuery(button).val("Feature");
          jQuery(button).removeClass('wpp_is_featured');
         }
        
      },
      'json'
      );
      
    
  });
  
}
