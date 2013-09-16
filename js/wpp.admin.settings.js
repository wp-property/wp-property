/**
 * WP-Property Admin Settings page
 *
 */
jQuery.extend( wpp = wpp || {}, { ui: { settings: {

  /**
   * Initialize DOM.
   *
   * @for wpp.ui.settings
   * @method ready
   */
  ready: function() {

    /**
     * Handles data saving.
     * Only if we don't upload backup file!
     *
     * @author peshkov@UD
     */
    jQuery( '#wpp_settings_form' ).submit( function() {
      if( !jQuery( '#wpp_backup_file' ).val() ) {
        var btn = jQuery( "input[type='submit']" );
        btn.prop( 'disabled', true );
        var data = jQuery( this ).serialize();
        jQuery.ajax({
          type: 'POST',
          url: wpp.instance.ajax_url,
          data: {
            action: 'wpp_save_settings',
            data: data
          },
          success: function( response ){
            var data = jQuery.parseJSON( response );
            if( data.success ) {
              window.location.href = data.redirect;
            } else {
              alert( data.message );
              btn.prop( 'disabled', false );
            }
          },
          error: function() {
            alert( wpp.strings.undefined_error );
            btn.prop( 'disabled', false );
          }
        });
        return false;
      }
    } );

    /* Tabs for various UI elements */
    jQuery( '.wpp_subtle_tabs' ).tabs();

    wpp.ui.settings.setup_default_property_page();

    jQuery( "#wpp_settings_base_slug" ).change( function () {
      wpp.ui.settings.setup_default_property_page();
    } );

    if ( document.location.hash != '' && jQuery( document.location.hash ).length > 0 ) {
      jQuery( "#wpp_settings_tabs" ).tabs();
    } else {
      jQuery( "#wpp_settings_tabs" ).tabs( { cookie: {  name: 'wpp_settings_tabs', expires: 30 } } );
    }

    /* Show settings array */
    jQuery( "#wpp_show_settings_array" ).click( function () {
      jQuery( "#wpp_show_settings_array_cancel" ).show();
      jQuery( "#wpp_show_settings_array_result" ).show();
    } );

    /* Hide settings array */
    jQuery( "#wpp_show_settings_array_cancel" ).click( function () {
      jQuery( "#wpp_show_settings_array_result" ).hide();
      jQuery( this ).hide();
    } );

    /* Hide property query */
    jQuery( "#wpp_ajax_property_query_cancel" ).click( function () {
      jQuery( "#wpp_ajax_property_result" ).hide();
      jQuery( this ).hide();
    } );

    /* Hide image query */
    jQuery( "#wpp_ajax_image_query_cancel" ).click( function () {
      jQuery( "#wpp_ajax_image_result" ).hide();
      jQuery( this ).hide();
    } );

    /* Check plugin updates */
    jQuery( "#wpp_ajax_check_plugin_updates" ).click( function () {
      jQuery( '.plugin_status' ).remove();
      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_check_plugin_updates'
      }, function ( data ) {
        message = "<div class='plugin_status updated fade'><p>" + data + "</p></div>";
        jQuery( message ).insertAfter( "h2" );
      } );
    } );

    /* Clear Cache */
    jQuery( "#wpp_clear_cache" ).click( function () {
      jQuery( '.clear_cache_status' ).remove();
      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_clear_cache'
      }, function ( data ) {
        message = "<div class='clear_cache_status updated fade'><p>" + data + "</p></div>";
        jQuery( message ).insertAfter( "h2" );
      } );
    } );

    /* Revalidate all addresses */
    jQuery( "#wpp_ajax_revalidate_all_addresses" ).click( function () {
      jQuery( this ).val( wpp.strings.processing );
      jQuery( this ).attr( 'disabled', true );
      jQuery( '.address_revalidation_status' ).remove();

      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_revalidate_all_addresses'
      }, function ( data ) {
        jQuery( "#wpp_ajax_revalidate_all_addresses" ).val( 'Revalidate again' );
        jQuery( "#wpp_ajax_revalidate_all_addresses" ).attr( 'disabled', false );
        var message = '';
        if ( data.success == 'true' ) {
          message = "<div class='address_revalidation_status updated fade'><p>" + data.message + "</p></div>";
        } else {
          message = "<div class='address_revalidation_status error fade'><p>" + data.message + "</p></div>";
        }
        jQuery( message ).insertAfter( "h2" );
      }, 'json' );
    } );

    /* Show property query */
    jQuery( "#wpp_ajax_property_query" ).click( function () {
      var property_id = jQuery( "#wpp_property_class_id" ).val();
      jQuery( "#wpp_ajax_property_result" ).html( "" );

      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_property_query',
        property_id: property_id
      }, function ( data ) {
        jQuery( "#wpp_ajax_property_result" ).show();
        jQuery( "#wpp_ajax_property_result" ).html( data );
        jQuery( "#wpp_ajax_property_query_cancel" ).show();
      } );
    } );

    //** Mass set property type */
    jQuery( "#wpp_ajax_max_set_property_type" ).click( function () {
      if ( !confirm( wpp.strings.set_property_type_confirmation ) ) {
        return;
      }
      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_max_set_property_type',
        property_type: jQuery( "#wpp_ajax_max_set_property_type_type" ).val()
      }, function ( data ) {
        jQuery( "#wpp_ajax_max_set_property_type_result" ).show();
        jQuery( "#wpp_ajax_max_set_property_type_result" ).html( data );
      } );
    } );

    /* Show image data */
    jQuery( "#wpp_ajax_image_query" ).click( function () {
      var image_id = jQuery( "#wpp_image_id" ).val();
      jQuery( "#wpp_ajax_image_result" ).html( "" );

      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_image_query',
        image_id: image_id
      }, function ( data ) {
        jQuery( "#wpp_ajax_image_result" ).show();
        jQuery( "#wpp_ajax_image_result" ).html( data );
        jQuery( "#wpp_ajax_image_query_cancel" ).show();
      } );
    } );

    /** Show property query */
    jQuery( "#wpp_check_premium_updates" ).click( function () {
      jQuery( "#wpp_plugins_ajax_response" ).hide();
      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_check_plugin_updates'
      }, function ( data ) {
        jQuery( "#wpp_plugins_ajax_response" ).show();
        jQuery( "#wpp_plugins_ajax_response" ).html( data );
      } );
    } );

  },

  /**
   * Modifies UI to reflect Default Property Page selection
   *
   * @for wpp.ui.settings
   * @method setup_default_property_page
   */
  setup_default_property_page: function() {
    var selection = jQuery( "#wpp_settings_base_slug" ).val();
    /* Default Property Page is dynamic. */
    if ( selection == "property" ) {
      jQuery( ".wpp_non_property_page_settings" ).hide();
      jQuery( ".wpp_non_property_page_settings input[type=checkbox]" ).attr( "checked", false );
      jQuery( ".wpp_non_property_page_settings input[type=checkbox]" ).attr( "disabled", true );
    } else {
      jQuery( ".wpp_non_property_page_settings" ).show();
      jQuery( ".wpp_non_property_page_settings input[type=checkbox]" ).attr( "disabled", false );
    }
  }

}}});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.settings.ready );