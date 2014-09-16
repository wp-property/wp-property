/**
 * WP-Property FEPS settings Page ( Property Forms )
 *
 * @author peshkov@UD
 */
jQuery.extend( wpp = wpp || {}, { ui: { feps: {

  /**
   * Initialize DOM.
   *
   * @for wpp.ui.feps
   * @method ready
   */
  ready: function() {
    var new_tab_href_id = 0;
    var index = jQuery('#save_form').attr('action').indexOf("#");
    var url   = jQuery('#save_form').attr('action').substring(0, index);

    jQuery(".wpp_feps_tabs").bind( "tabsselect", function(event, ui) {
      index = jQuery('#save_form').attr('action').indexOf("#");
      url   = jQuery('#save_form').attr('action').substring(0, index);
      jQuery('#save_form').attr( 'action', url+'#feps_form_'+jQuery( ui.panel ).attr('feps_form_id') );
    });

    jQuery(".wpp_feps_tabs").bind( "tabscreate", function(event, ui) {
      jQuery('#save_form').attr( 'action', url+window.location.hash );
    });

    if( wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
      jQuery(".wpp_feps_tabs").tabs();
    } else {
      jQuery(".wpp_feps_tabs").tabs({
        add: function(event, ui) {
          jQuery(ui.panel).addClass('wpp_feps_form').attr('feps_form_id', new_tab_href_id);
          jQuery(ui.tab).parent().attr('feps_form_id', new_tab_href_id);
          jQuery('.wpp_feps_form table:first').clone().appendTo( ui.panel );
          wpp.ui.feps.init_close_btn();
          wpp.ui.feps.set_default_field_values( ui.panel );
          jQuery( 'input[name*="wpp_feps[forms]"], select[name*="wpp_feps[forms]"], textarea[name*="wpp_feps[forms]"]', ui.panel ).each(function(key, value){
            jQuery( value ).attr( 'name', String(jQuery( value ).attr('name')).replace(/wpp_feps\[forms\]\[\d.+?\]/, 'wpp_feps[forms]['+new_tab_href_id+']') );
          });
          wpp.ui.feps.update_dom();
        }
      });
    }

    wpp.ui.feps.init_close_btn();

    jQuery(".wpp_add_tab").click(function() {
      new_tab_href_id = parseInt(Math.random()*1000000);

      if( wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
        var tabs = jQuery(".wpp_feps_tabs"),
          ul = tabs.find( ">ul" ),
          index = tabs.find( '>ul >li').size(),
          panel = jQuery( '<div id="feps_form_' + new_tab_href_id + '" class="wpp_feps_form" feps_form_id="' + new_tab_href_id + '"></div>' );

        jQuery( "<li><a href='#feps_form_" + new_tab_href_id + "'><span>" + wpp.strings.feps.unnamed_form + "</span></a></li>" ).appendTo( ul );
        jQuery('.wpp_feps_form table:first').clone().appendTo( panel );
        panel.appendTo( tabs );
        tabs.tabs( "refresh" );

        var tab = jQuery( '>li:last', ul );

        tab.attr('feps_form_id', new_tab_href_id );
        jQuery( 'input[name*="wpp_feps[forms]"], select[name*="wpp_feps[forms]"], textarea[name*="wpp_feps[forms]"]', panel ).each( function(key, value){
          jQuery( value ).attr( 'name', String(jQuery( value ).attr('name')).replace(/wpp_feps\[forms\]\[\d.+?\]/, 'wpp_feps[forms]['+new_tab_href_id+']') );
        });
        wpp.ui.feps.init_close_btn();
        wpp.ui.feps.set_default_field_values( panel );
        wpp.ui.feps.update_dom();
        tabs.tabs( "option", "active", index );
      } else {
        jQuery(".wpp_feps_tabs").tabs( "add", "#feps_form_"+new_tab_href_id, wpp.strings.feps.unnamed_form );
        jQuery(".wpp_feps_tabs").tabs( "active", jQuery(".wpp_feps_tabs").tabs( 'length' )-1 );
      }
    });

    jQuery(".wpp_dynamic_table_row").each(function() {
      /* A bit of  hack, but we want users to be able to change rows around as much as they need */
      jQuery(this).attr("new_row", "true");
      /* Maybe hide 'Required' option */
      wpp.ui.feps.is_active_required_option( jQuery(this) );
    });

    jQuery(".wpp_feps_new_attribute").live("change", function() {
      var parent = jQuery(this).parents(".wpp_dynamic_table_row");
      var title = jQuery("option:selected", this).text();
      jQuery("input.title", parent).val(title);
      /* Maybe hide 'Required' option */
      wpp.ui.feps.is_active_required_option( parent );
    });

    /* On form name change */
    jQuery(".wpp_feps_form .form_title").live("change", function() {
      var title = jQuery(this).val();

      if(title == "") {
        return;
      }

      var slug = wpp_create_slug(title);
      var this_form = jQuery(this).parents(".wpp_feps_form");
      var form_id = jQuery(this_form).attr("feps_form_id");

      /* Update tab title */
      jQuery(".wpp_feps_tabs .tabs li[feps_form_id="+form_id+"] a span").text(title);
      /* Update shortcode */
      jQuery("input.shortcode", this_form).val("[wpp_feps_form form=" + slug + "]");
      /* Update Slug */
      jQuery("input.slug", this_form).val(slug);

    });

    jQuery("a.wpp_forms_remove_attribute").live('click', function(){
      var row_to_be_removed = jQuery(this).attr("row");
      var context           = jQuery(this).parents("div.wpp_feps_form .ud_ui_dynamic_table");
      var rows              = jQuery("tr.wpp_dynamic_table_row", context);
      if ( rows.length > 2 ) {
        rows.each(function(k, v){
          if ( jQuery(v).attr("random_row_id") == row_to_be_removed ) {
            jQuery(v).remove();
          }
        });
      }
      wpp.ui.feps.update_dom();
    });

    jQuery("select.wpp_feps_new_attribute").live('change', function(){
      wpp.ui.feps.update_dom();
    });

    jQuery("input.imageslimit").live('change', function(){
      if ( jQuery(this).val() < 1 ) jQuery(this).val(1);
    });

    wpp.ui.feps.update_dom();
  },
  
  
  /**
   * Enable/disable 'Required' option for some specific attributes
   */
  is_active_required_option: function( e ) {
    var attribute = e.find( '.wpp_feps_new_attribute' ).val();
    var req_option_wrap = e.find( '.is_required' );
    if( jQuery.inArray( attribute, [ "image_upload" ] ) >= 0 ) {
      req_option_wrap.css( 'visibility', 'hidden' ).find( 'input' ).prop( 'disabled', true );
    } else {
      req_option_wrap.css( 'visibility', 'visible' ).find( 'input' ).prop( 'disabled', false );
    }
  },


  /**
   * Render Close button on tabs
   *
   * @for wpp.ui.feps
   * @method init_close_btn
   */
  init_close_btn: function(){
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
    jQuery('ul.tabs li a.remove-tab').click(function(e){
      var feps_form_id = jQuery(e.target).closest('li').attr('feps_form_id');
      if ( feps_form_id ) {
        //* Check if form can be removed */
        jQuery.ajax({
          url: wpp.instance.ajax_url,
          async: false,
          data: {
            action: 'wpp_feps_can_remove_form',
            feps_form_id: feps_form_id
          },
          success: function(response){
            var data = eval('(' + response + ')');
            if( data.success ) {
              if( wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
                // Remove the tab
                var tab = jQuery( ".wpp_feps_tabs" ).find( ".ui-tabs-nav li[feps_form_id='" + feps_form_id + "']" ).remove();
                // Find the id of the associated panel
                var panelId = tab.attr( "aria-controls" );
                // Remove the panel
                jQuery( "#" + panelId ).remove();
                // Refresh the tabs widget
                jQuery( ".wpp_feps_tabs" ).tabs( "refresh" );
              } else {
                jQuery(".wpp_feps_tabs").tabs('remove', jQuery(this).parent().index());
              }
            } else {
              alert( data.message );
            }
          },
          error: function() {
            alert( wpp.strings.feps.form_could_not_be_removed_1 );
          }
        });
      } else {
        alert( wpp.strings.feps.form_could_not_be_removed_2 );
      }
    });
  },


  /**
   * Set default field values after adding new tab
   *
   * @for wpp.ui.feps
   * @method set_default_field_values
   */
  set_default_field_values: function( context ) {
    jQuery("input.form_title", context).val('Unnamed Form').trigger('change');
    jQuery("input.shortcode", context).val('[wpp_feps_form form='+wpp_create_slug('Unnamed Form '+jQuery(context).attr('feps_form_id'))+']');
    jQuery("input.slug", context).val(wpp_create_slug('Unnamed Form '+jQuery(context).attr('feps_form_id')));
    jQuery(".ud_ui_dynamic_table", context).each(function(){
      jQuery("tr.wpp_dynamic_table_row", jQuery(this)).find("textarea.description").val('');
      jQuery("tr.wpp_dynamic_table_row:not(.required):not(:first)", jQuery(this)).remove();
    });
  },


  /**
   * Update dom after changes
   *
   * @for wpp.ui.feps
   * @method update_dom
   */
  update_dom: function () {
    jQuery(".wpp_feps_sortable tbody").sortable( { items: 'tr.wpp_dynamic_table_row:not(.required)' } );

    jQuery(".wpp_feps_sortable tr.wpp_dynamic_table_row").live("mouseover", function() {
      jQuery(this).addClass("wpp_draggable_handle_show");
    });

    jQuery(".wpp_feps_sortable tr.wpp_dynamic_table_row").live("mouseout", function() {
      jQuery(this).removeClass("wpp_draggable_handle_show");
    });

    jQuery(".wpp_feps_sortable tr.wpp_dynamic_table_row").each(function(k, v){
      var random_row_id = jQuery(v).attr("random_row_id");
      jQuery(v).find("a.wpp_forms_remove_attribute").attr("row", random_row_id);
    });

    //* Determine if form allows to upload images and show/hide images settings */
    jQuery(".ui-tabs-panel").each(function(k,v){
      var image_upload = false,
          plan_images_col = jQuery('.wpp_plan_images_limit_col', v),
          feps_credits = jQuery('input.feps_credits', v);
      jQuery("select.wpp_feps_new_attribute option:selected", v).each(function(i, e){
        if ( jQuery(e).val() == 'image_upload' ) {
          image_upload = true;
          return false;
        }
      });
      if( image_upload ) {
        if ( !feps_credits.length > 0 || ( feps_credits.length > 0 && !feps_credits.is(':checked') ) ) {
          jQuery('input.imageslimit', v).parent().show();
        }
        if( plan_images_col.length > 0 ) {
          plan_images_col.show();
        }
      } else {
        jQuery('input.imageslimit', v).parent().hide();
        if( plan_images_col.length > 0 ) {
          plan_images_col.hide();
        }
      }
    });
  },


  /**
   * Ran after a row is added
   *
   * @for wpp.ui.feps
   * @method on_added_row
   */
  on_added_row: function(added_row) {
    /* Set the title */
    wpp.ui.feps.update_dom();
  }

}}});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.feps.ready );