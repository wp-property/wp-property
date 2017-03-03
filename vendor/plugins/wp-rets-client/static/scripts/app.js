jQuery(function() {

  /**
   *
   */
  if ( jQuery( '.wpp_retsci_widget_stats').length ) {
    var container = jQuery( '.wpp_retsci_widget_stats');
    var config = container.data('config');
    var url    = config.api_url + 'v2/site/' + config.site_id + '/status';

    jQuery.getJSON( url, { token: config.site_secret_token }, function( res ) {
      if ( res.ok ) {
        if ( res.status.recentUpdates ) {
          jQuery( '.updates_number', container ).html( res.status.recentUpdates );
        }
        if ( res.status.activePollers ) {
          jQuery( '.active_pollers', container ).html( res.status.activePollers );
        }
        if ( res.status.lastUpdate ) {
          jQuery( '.last_update_date', container ).html( res.status.lastUpdate );
        }
        if ( res.status.provider ) {
          jQuery( '.provider', container ).html( res.status.provider );
        }
        if ( res.messages && res.messages.length ) {
          jQuery( '.messages_header', container ).show();
          for(var i in res.messages) {
            jQuery( '.messages', container ).append( '<p>'+res.messages[i]+'</p>' );
          }
        }
      } else {
        jQuery('.stats', container).hide();
        jQuery('.message').html( res.message );
      }
    } );
  }

});