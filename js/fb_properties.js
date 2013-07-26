wpp_fb_frontend_actions = {
  init : function () {
    if ( typeof wpp_fb_tabs_settings == 'object' ) {
      wpp_fb_frontend_actions.settings.open_links_in_new_window( wpp_fb_tabs_settings.open_links_in_new_window );
      wpp_fb_frontend_actions.settings.open_forms_in_new_window( wpp_fb_tabs_settings.open_forms_in_new_window );
    }
  },
  settings : {
    open_links_in_new_window : function ( state ) {
      if ( state == 'true' ) {
        jQuery("a").live("click", function() { window.open( jQuery(this).attr('href') );
  return false; })
      }
    },
    open_forms_in_new_window : function ( state ) {
      if ( state == 'true' ) {
        jQuery('form').attr('target', '_blank');
      }
    }
  }
};
jQuery(document).ready( wpp_fb_frontend_actions.init );
jQuery(document).ajaxComplete( wpp_fb_frontend_actions.init );

