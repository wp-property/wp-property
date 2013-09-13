/**
 * WP-Property Admin Settings page
 *
 */
jQuery.extend( wpp = wpp || {}, { ui: { settings: {

  /**
   * Initialize DOM.
   *
   * @for wpp.settings
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
  }

}}});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.settings.ready );