/**
 * WP-Property Admin Settings
 *
 * jquery.ui has a shim which adds it to jQuery.ui
 * require( ['wpp.admin.settings'] )
 *
 */
define( 'wpp.admin.settings', [ 'wpp.model', 'wpp.locale', 'jquery', 'knockout', 'knockout.mapping' ], function(  ) {
  // console.log( 'wpp.admin.settings:ko', ko );
  // console.log( 'wpp.admin.settings:jQuery', jQuery );

  // Add Mapping to Knockout Module.
  require( 'knockout' ).mapping = require( 'knockout.mapping' );

  /**
   * Trigger on Document Ready
   *
   */
  return function domReady() {

    var jQuery  = require( 'jquery' );
    var ko      = require( 'knockout' );
    var model   = require( 'wpp.model' );
    var locale  = require( 'wpp.locale' );

    console.log( 'wpp.admin.settings:model', model );
    console.log( 'wpp.admin.settings:locale', locale );
    // console.log( 'wpp.admin.settings:ko', ko );
    // console.log( 'wpp.admin.settings:ko.mapping', ko.mapping );
    // console.log( 'wpp.admin.settings:jQuery', jQuery );

    var _api_url = '/manage/wp-ajax.php';
    var _strings = {};

    //console.log( "JQUERY DEBUG", typeof require( 'jquery' ).fn.tabs );
    //console.log( "JQUERY ui", typeof jquery.fn.tabs );
    //console.log( "JQUERY ui", ui );

    return;

    /**
     * Handles form saving
     * Do any validation/data work before the settings page form is submitted
     * @author odokienko@UD
     */
    jQuery(".wpp_settings_page form").submit(function( form ) {
      var error_field = {object:false,tab_index:false};

      /* The next block make validation for required fields    */
      jQuery("form #wpp_settings_tabs :input[validation_required=true],form #wpp_settings_tabs .wpp_required_field :input,form #wpp_settings_tabs :input[required],form #wpp_settings_tabs :input.slug_setter").each(function(){

        /* we allow empty value if dynamic_table has only one row */
        var dynamic_table_row_count = jQuery(this).closest('.wpp_dynamic_table_row').parent().children('tr.wpp_dynamic_table_row').length;

        if (!jQuery(this).val() && dynamic_table_row_count!=1){
          error_field.object = this;
          error_field.tab_index = jQuery('#wpp_settings_tabs a[href="#' + jQuery( error_field.object ).closest( ".ui-tabs-panel" ).attr('id') + '"]').parent().index();
          return false;
        }
      });

      /* if error_field object is not empty then we've error found */
      if (error_field.object != false ) {
        /* do focus on tab with error field */
        if(typeof error_field.tab_index !='undefined') {
          jQuery('#wpp_settings_tabs').tabs('option', 'active', error_field.tab_index);
        }
        /* mark error field and remove mark on keyup */
        jQuery(error_field.object).addClass('ui-state-error').one('keyup',function(){jQuery(this).removeClass('ui-state-error');});
        jQuery(error_field.object).focus();
        return false;
      }
    });

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
        jQuery.ajax( {
          type: 'POST',
          url: _api_url,
          data: {
            action: 'wpp_save_settings',
            data: data
          },
          success: function( response ) {
            var data = jQuery.parseJSON( response );
            if( data.success ) {
              window.location.href = data.redirect;
            } else {
              alert( data.message );
              btn.prop( 'disabled', false );
            }
          },
          error: function() {
            alert( _strings.undefined_error );
            btn.prop( 'disabled', false );
          }
        } );
        return false;
      }
    } );

    /* Tabs for various UI elements */
    //jQuery( '.wpp_subtle_tabs' ).tabs();

    jQuery( "#wpp_settings_base_slug" ).change( function() {
      //wpp.ui.settings.setup_default_property_page();
    } );

    if( document.location.hash != '' && jQuery( document.location.hash ).length > 0 ) {
      //jQuery( "#wpp_settings_tabs" ).tabs();
    } else {
//      jQuery( "#wpp_settings_tabs" ).tabs( { cookie: {  name: 'wpp_settings_tabs', expires: 30 } } );
    }

    /* Show settings array */
    jQuery( "#wpp_show_settings_array" ).click( function() {
      jQuery( "#wpp_show_settings_array_cancel" ).show();
      jQuery( "#wpp_show_settings_array_result" ).show();
    } );

    /* Hide settings array */
    jQuery( "#wpp_show_settings_array_cancel" ).click( function() {
      jQuery( "#wpp_show_settings_array_result" ).hide();
      jQuery( this ).hide();
    } );

    /* Hide property query */
    jQuery( "#wpp_ajax_property_query_cancel" ).click( function() {
      jQuery( "#wpp_ajax_property_result" ).hide();
      jQuery( this ).hide();
    } );

    /* Hide image query */
    jQuery( "#wpp_ajax_image_query_cancel" ).click( function() {
      jQuery( "#wpp_ajax_image_result" ).hide();
      jQuery( this ).hide();
    } );

    /* Check plugin updates */
    jQuery( "#wpp_ajax_check_plugin_updates" ).click( function() {
      jQuery( '.plugin_status' ).remove();
      jQuery.post( _api_url, {
        action: 'wpp_ajax_check_plugin_updates'
      }, function( data ) {
        message = "<div class='plugin_status updated fade'><p>" + data + "</p></div>";
        jQuery( message ).insertAfter( "h2" );
      } );
    } );

    /* Clear Cache */
    jQuery( "#wpp_clear_cache" ).click( function() {
      jQuery( '.clear_cache_status' ).remove();
      jQuery.post( _api_url, {
        action: 'wpp_ajax_clear_cache'
      }, function( data ) {
        message = "<div class='clear_cache_status updated fade'><p>" + data + "</p></div>";
        jQuery( message ).insertAfter( "h2" );
      } );
    } );

    /* Revalidate all addresses */
    jQuery( "#wpp_ajax_revalidate_all_addresses" ).click( function() {
      jQuery( this ).val( _strings.processing );
      jQuery( this ).attr( 'disabled', true );
      jQuery( '.address_revalidation_status' ).remove();

      jQuery.post( _api_url, {
        action: 'wpp_ajax_revalidate_all_addresses'
      }, function( data ) {
        jQuery( "#wpp_ajax_revalidate_all_addresses" ).val( 'Revalidate again' );
        jQuery( "#wpp_ajax_revalidate_all_addresses" ).attr( 'disabled', false );
        var message = '';
        if( data.success == 'true' ) {
          message = "<div class='address_revalidation_status updated fade'><p>" + data.message + "</p></div>";
        } else {
          message = "<div class='address_revalidation_status error fade'><p>" + data.message + "</p></div>";
        }
        jQuery( message ).insertAfter( "h2" );
      }, 'json' );
    } );

    /* Show property query */
    jQuery( "#wpp_ajax_property_query" ).click( function() {
      var property_id = jQuery( "#wpp_property_class_id" ).val();
      jQuery( "#wpp_ajax_property_result" ).html( "" );

      jQuery.post( _api_url, {
        action: 'wpp_ajax_property_query',
        property_id: property_id
      }, function( data ) {
        jQuery( "#wpp_ajax_property_result" ).show();
        jQuery( "#wpp_ajax_property_result" ).html( data );
        jQuery( "#wpp_ajax_property_query_cancel" ).show();
      } );
    } );

    //** Mass set property type */
    jQuery( "#wpp_ajax_max_set_property_type" ).click( function() {
      if( !confirm( _strings.set_property_type_confirmation ) ) {
        return;
      }
      jQuery.post( _api_url, {
        action: 'wpp_ajax_max_set_property_type',
        property_type: jQuery( "#wpp_ajax_max_set_property_type_type" ).val()
      }, function( data ) {
        jQuery( "#wpp_ajax_max_set_property_type_result" ).show();
        jQuery( "#wpp_ajax_max_set_property_type_result" ).html( data );
      } );
    } );

    /* Show image data */
    jQuery( "#wpp_ajax_image_query" ).click( function() {
      var image_id = jQuery( "#wpp_image_id" ).val();
      jQuery( "#wpp_ajax_image_result" ).html( "" );

      jQuery.post( _api_url, {
        action: 'wpp_ajax_image_query',
        image_id: image_id
      }, function( data ) {
        jQuery( "#wpp_ajax_image_result" ).show();
        jQuery( "#wpp_ajax_image_result" ).html( data );
        jQuery( "#wpp_ajax_image_query_cancel" ).show();
      } );
    } );

    /** Show property query */
    jQuery( "#wpp_check_premium_updates" ).click( function() {
      jQuery( "#wpp_plugins_ajax_response" ).hide();
      jQuery.post( _api_url, {
        action: 'wpp_ajax_check_plugin_updates'
      }, function( data ) {
        jQuery( "#wpp_plugins_ajax_response" ).show();

        jQuery( "#wpp_plugins_ajax_response" ).html( data );
      } );
    } );

    var selection = jQuery( "#wpp_settings_base_slug" ).val();
    /* Default Property Page is dynamic. */
    if( selection == "property" ) {
      jQuery( ".wpp_non_property_page_settings" ).hide();
      jQuery( ".wpp_non_property_page_settings input[type=checkbox]" ).attr( "checked", false );
      jQuery( ".wpp_non_property_page_settings input[type=checkbox]" ).attr( "disabled", true );
    } else {
      jQuery( ".wpp_non_property_page_settings" ).show();
      jQuery( ".wpp_non_property_page_settings input[type=checkbox]" ).attr( "disabled", false );
    }

  }

});
