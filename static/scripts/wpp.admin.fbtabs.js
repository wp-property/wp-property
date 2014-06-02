/**
 * WP-Property Facebook Tabs settings Page
 *
 * @author peshkov@UD
 */
jQuery.extend( wpp = wpp || {}, { ui: { fbtabs: {

  auto_complete_timer : null,

  /**
   * Init basic actions 
   */
  ready : function() {
    var params = {
      create: function( event, ui ) {
        jQuery( '.wpp_add_tab' ).click( wpp.ui.fbtabs.add_canvas );
        jQuery( 'input.slug_setter' ).live( 'change', wpp.ui.fbtabs.set_slug );
        jQuery( '.wpp_fb_page_type' ).live( 'change', wpp.ui.fbtabs.update_fields_by_type ).trigger( 'change' );
        jQuery( 'input.wpp_fb_property_input' )
          .live( 'keyup', wpp.ui.fbtabs.property_input_keyup )
          .live( 'focus', wpp.ui.fbtabs.property_input_focus )
          .live( 'blur', wpp.ui.fbtabs.property_input_blur )
          .live( 'keydown', function(event){
            if(event.keyCode == 13) {
              event.preventDefault();
              return false;
            }
          });
        jQuery( '.wpp_fb_app_id' ).live( 'change', wpp.ui.fbtabs.set_add_to_fb_link ).trigger( 'change' );
        jQuery( 'a.wpp_fb_tabs_property_link' ).live( 'click', wpp.ui.fbtabs.property_link_click );
        jQuery('.current_slug').live('change', wpp.ui.fbtabs.set_urls ).each( function( i, e ) {
          jQuery( e ).trigger( 'change' );
        } );
        jQuery( '#save_form' ).show();
        wpp.ui.fbtabs.init_close_btn();
      }
    }
    if( !wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
      params.add = wpp.ui.fbtabs.canvas_added;
    }
    jQuery(".wpp_fb_tabs").tabs( params );
  },

  /**
   * 
   */
  canvas_added : function( event, ui ) {
    jQuery( '.wpp_fb_tab table:first' ).clone().appendTo( ui.panel );
    wpp.ui.fbtabs.set_default_values( ui.panel );
    wpp.ui.fbtabs.init_close_btn();
  },

  /**
   * 
   */
  add_canvas : function() {
    var new_tab_href_id = parseInt( Math.random()*1000000 );
    if( wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
      var tabs = jQuery(".wpp_fb_tabs"),
      ul = tabs.find( ">ul" ),
      index = tabs.find( '>ul >li').size(),
      panel = jQuery( '<div id="fb_form_' + new_tab_href_id + '"></div>' );

      jQuery( "<li><a href='#fb_form_" + new_tab_href_id + "'></a></li>" ).appendTo( ul );
      jQuery('.wpp_fb_tabs table:first').clone().appendTo( panel );
      panel.appendTo( tabs );
      tabs.tabs( "refresh" );

      wpp.ui.fbtabs.set_default_values( panel );
      wpp.ui.fbtabs.init_close_btn();

      tabs.tabs( "option", "active", index );
    } else {
      jQuery( '.wpp_fb_tabs' ).tabs( "add", "#fb_canvas_" + new_tab_href_id, '' );
      jQuery( '.wpp_fb_tabs' ).tabs( "select", jQuery(".wpp_fb_tabs").tabs( 'length' )-1 );
    }
  },

  /**
   * 
   */
  set_slug : function( event ) {
    var value = jQuery( event.currentTarget ).val(),
        panel = jQuery( jQuery( event.currentTarget ).parents( 'div.ui-tabs-panel' ).get(0) ),
        old_slug = jQuery( 'input.current_slug', panel ).val(),
        new_slug = wpp_create_slug( value );

    jQuery( 'a[href="#'+ panel.attr('id') +'"]' ).html( value ).closest('li').attr( 'fb_canvas_id', new_slug );
    jQuery( 'input.current_slug', panel ).val( new_slug ).trigger( 'change' );

    // Cycle through all child elements and fix names
    jQuery( 'input,select, textarea', panel ).each( function(i,e) {
      var old_name = jQuery(e).attr('name');
      if ( typeof old_name != 'undefined' ) {
        var new_name =  old_name.replace('['+old_slug+']','['+new_slug+']');
        // Update to new name
        jQuery(e).attr('name', new_name);
      }
      var old_id = jQuery(e).attr('id');
      if( typeof old_id != 'undefined' ) {
        var new_id =  old_id.replace( old_slug, new_slug );
        jQuery(e).attr('id', new_id);
      }
      // Cycle through labels too
      jQuery( 'label', panel ).each(function(i,e) {
        if( typeof jQuery(e).attr('for') != 'undefined' ) {
          var old_for = jQuery(e).attr('for');
          var new_for =  old_for.replace(old_slug,new_slug);
          // Update to new name
          jQuery(e).attr('for', new_for);
        }
      });
    });
  },

  /** 
   * Set URLs for canvases 
   */
  set_urls : function() {
    var slug = jQuery( this ).val(),
        panel = jQuery( jQuery( this ).parents( 'div.ui-tabs-panel' ).get(0) ),
        url = wpp.instance.ajax_url,
        secure_url,
        debug_url;
    if( wpp.instance.is_permalink ) {
      url += '/' + wpp.instance.fbtabs.query_var + '/' + slug + '/';
      debug_url = url + '?signed_request=' + md5( 'debug::' + slug );
    } else {
      url += '?' + wpp.instance.fbtabs.query_var + '=' + slug;
      debug_url = url + '&signed_request=' + md5( 'debug::' + slug );
    }
    secure_url = url.replace( 'http://', 'https://' );

    jQuery( 'input.default_canvas_url', panel ).val( url );
    jQuery( 'input.secure_canvas_url', panel ).val( secure_url );
    jQuery( 'input.debug_canvas_url', panel ).val( debug_url );
  },

  /**
   * 
   */
  set_default_values : function( ui ) {
    jQuery( 'input.wpp_default_empty[type="text"]', ui ).val( '' );
    jQuery( 'input.wpp_default_empty[type="checkbox"]', ui ).attr( 'checked', false );
    jQuery( '.wpp_fb_page_type', ui ).val( 'page' ).trigger( 'change' );
    jQuery( 'input.slug_setter', ui ).val( wpp.strings.fbtabs.unnamed_canvas ).trigger( 'change' );
  },

  /**
   * 
   */
  set_add_to_fb_link : function( event ) {
    var panel = jQuery( jQuery( event.currentTarget ).parents( 'div.ui-tabs-panel' ).get(0) );
    var value = jQuery( event.currentTarget ).val();
    var button = jQuery( 'a.wpp_fb_tabs_add_to_page', panel );
    button.attr( 'href', 'https://www.facebook.com/dialog/pagetab?app_id=' + value + '&redirect_uri=http%3A%2F%2Fwww.facebook.com' );
    if( value == '' ) button.hide();
    else button.show();
  },

  /**
   * 
   */
  update_fields_by_type : function( event ) {
    var panel = jQuery( jQuery( event.currentTarget ).parents( 'div.ui-tabs-panel' ).get(0) );
    var value = jQuery( event.currentTarget ).val();
    switch( value ) {
      case 'page':
        jQuery( '.wpp_fb_type_property', panel ).hide().attr( 'disabled', 'disabled' );
        jQuery( '.wpp_fb_type_page', panel ).show().removeAttr( 'disabled' );
        break;
      case 'property':
        jQuery( '.wpp_fb_type_page', panel ).hide().attr( 'disabled', 'disabled' );
        jQuery( '.wpp_fb_type_property', panel ).show().removeAttr( 'disabled' );
        break;
    }
  },

  /**
   * 
   */
  init_close_btn : function() {
    // Add remove button for tabs
    jQuery('ul.tabs li.ui-state-default:not(:first):not(:has(a.remove-tab))')
      .append('<a href="javascript:void(0);" class="remove-tab">x</a>')
      .mouseenter(function(){
        jQuery('a.remove-tab', this).show();
      })
      .mouseleave(function(){
        jQuery('a.remove-tab', this).hide();
      });

    // On remove tab button click
    jQuery('ul.tabs li a.remove-tab').unbind('click');
    jQuery('ul.tabs li a.remove-tab').click(function( e ){
      if( wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
        var index = jQuery(this).parent().index();
        if( jQuery( '.wpp_fb_tabs' ).tabs( 'option', 'active' ) == index ) {
          jQuery( '.wpp_fb_tabs' ).tabs( "option", "active", index-1 );
        }
        // Remove the tab
        var tab = jQuery( ".wpp_fb_tabs" ).find( ".ui-tabs-nav li:eq(" + index + ")" ).remove();
        // Find the id of the associated panel
        var panelId = tab.attr( "aria-controls" );
        // Remove the panel
        jQuery( "#" + panelId ).remove();
        // Refresh the tabs widget
        jQuery( ".wpp_feps_tabs" ).tabs( "refresh" );
      } else {
        jQuery(".wpp_fb_tabs").tabs( 'remove', jQuery( e ).parent().index() );
      }
    });
  },

  /** 
   * Process keyup 
   */
  property_input_keyup : function() {
    var typing_timeout = 600;
    var input = jQuery(this);
    var panel = input.parents( 'div.ui-tabs-panel' ).get(0);
    jQuery( '.wpp_fb_tabs_found_properies', panel ).hide().empty();
    window.clearTimeout( wpp.ui.fbtabs.auto_complete_timer );
    wpp.ui.fbtabs.auto_complete_timer = window.setTimeout( function(){
      jQuery( '.wpp_fb_tabs_loader_image', panel ).show();
      jQuery.post(
        wpp.instance.ajax_url,
        {
          action: 'wpp_fb_tabs_get_properties',
          s: input.val()
        },
        function( response ) {
          jQuery( '.wpp_fb_tabs_loader_image', panel ).hide();
          if ( response && typeof response == 'object' ) {
            jQuery.each(response, function(){
              jQuery( '.wpp_fb_tabs_found_properies', panel )
                .width(input.outerWidth())
                .append( '<li><a class="wpp_fb_tabs_property_link" href="'+this.id+'">'+this.title+'</a></li>' )
                .show();
            });
          }
        }, 'json'
      );
    }, typing_timeout);
  },

  /** 
   * Process focus 
   */
  property_input_focus : function() {
    var panel = jQuery(this).parents( 'div.ui-tabs-panel' ).get(0);
    jQuery( '.wpp_fb_tabs_found_properies', panel ).hide().empty();
  },

  /** 
   * Process blur 
   */
  property_input_blur : function() {
    var panel = jQuery(this).parents( 'div.ui-tabs-panel' ).get(0);
    jQuery( '.wpp_fb_tabs_found_properies', panel ).delay(300).queue(function(){
      jQuery(this).hide().empty();
    });
  },

  /** 
   * Process click 
   */
  property_link_click : function() {
    var a = jQuery(this);
    var panel = a.parents( 'div.ui-tabs-panel' ).get(0);
    jQuery( '.wpp_fb_property_input', panel ).val(a.text());
    jQuery( '.wpp_fb_property_input_hidden', panel ).val(a.attr('href'));
    jQuery( '.wpp_fb_tabs_found_properies', panel ).hide().empty();
    return false;
  }

}}});

//** Initialize Overview. */
jQuery( document ).ready( wpp.ui.fbtabs.ready );