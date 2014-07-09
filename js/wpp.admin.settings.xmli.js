/**
 * WP-Property Admin Settings page. - XMLI Settings
 *
 */
 
wpp = wpp || {};
wpp.ui = wpp.ui || {};

jQuery.extend( wpp.ui, { xmli: {

  /**
   * Initialize DOM.
   *
   * @for wpp.ui.settings
   * @method ready
   */
  ready: function() {

    jQuery( ".wppi_delete_all_orphan_attachments" ).click( function ( e ) {

      var notice_container = jQuery( '.wppi_delete_all_orphan_attachments_result' ).show();

      jQuery( notice_container ).html( "Deleting all unattached images. You can close your browser, the operation will continue until completion." );

      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_property_import_handler',
        wpp_action: 'delete_all_orphan_attachments'
      }, function ( result ) {
        if ( result && result.success ) {
          jQuery( notice_container ).html( result.ui );
        } else {
          jQuery( notice_container ).html( 'An error occured.' );
        }

      }, 'json' );

    });
    
    jQuery( "#wpp_ajax_show_xml_imort_history" ).click( function () {

      jQuery( ".wpp_ajax_show_xml_imort_history_result" ).html( "" );

      jQuery.post( wpp.instance.ajax_url, {
        action: 'wpp_ajax_show_xml_imort_history'
      }, function ( data ) {
        jQuery( ".wpp_ajax_show_xml_imort_history_result" ).show();
        jQuery( ".wpp_ajax_show_xml_imort_history_result" ).html( data );

      });
    });

  }

}});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.xmli.ready );