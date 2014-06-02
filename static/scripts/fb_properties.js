/**
 * Facebook Tabs script
 * Must be enqueued on all facebook pages.
 */

if( typeof wpp_fb_tabs != 'undefined' ) {

  //** Try to convert strings to JSON objects */
  for( var i in wpp_fb_tabs ) {
    if( typeof wpp_fb_tabs[i] !== 'function' ) {
      try {
        var json = jQuery.parseJSON( wpp_fb_tabs[i] );
        wpp_fb_tabs[i] = json;
      } catch (e) {
        // Looks like it's not JSON object, so just ignore it.
      }
    }
  }

  /**
   * Adds specific header to AJAX requests
   */
  jQuery(document).ajaxSend(function(event, xhr, settings) {
    xhr.setRequestHeader( "X-FB-CANVAS", wpp_fb_tabs.canvas );
  });

  //** Open links in new window */
  if( typeof wpp_fb_tabs.data.settings.open_links_in_new_window !== 'undefined' ) {
    if ( wpp_fb_tabs.data.settings.open_links_in_new_window == 'true' ) {
      jQuery( "a" ).live( "click", function() {
        if( !/^\#/.test( jQuery( this ).attr( 'href' ) ) && !/^javascript/.test( jQuery( this ).attr( 'href' ) ) ) {
          window.open( jQuery( this ).attr( 'href' ) );
          return false;
        }
      } );
    }
  }

  //** Open forms in new window */
  if( typeof wpp_fb_tabs.data.settings.open_forms_in_new_window !== 'undefined' ) {
    if ( wpp_fb_tabs.data.settings.open_forms_in_new_window == 'true' ) {
      jQuery('form').attr('target', '_blank');
    }
  }

}
