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

  /**
   *
   */
  if ( jQuery( '.wpp_retsci_widget_register').length ) {
    var container = jQuery( '.wpp_retsci_widget_register' );
    var config = container.data('config');
    var signin_form = jQuery( '#signin-form', container );

    signin_form.on( 'submit', function() {

      var data = jQuery(this).serializeArray().reduce(function(obj, item) {
        obj[item.name] = item.value;
        return obj;
      }, {});

      jQuery.post( ajaxurl, {
        action: 'wpp_retsci_signin',
        security: config.security,
        payload: {
          ud_site_id: config.ud_site_id,
          ud_site_secret_token: config.ud_site_secret_token,
          retsci_site_secret_token: config.retsci_site_secret_token,
          api_url: config.api_url,
          credentials: data
        }
      }, function( res ){

        if ( res.ok ) {
          signin_form.hide();
        }

        if ( res.message ) {
          jQuery( '.message', container).html( res.message );
        }

      });

      return false;
    });
  }

  if ( jQuery( '.wpp_retsci_widget_subscription').length ) {
    var container = jQuery( '.wpp_retsci_widget_subscription' );
    var config = container.data('config');
    var signin_form = jQuery( '#subscription-form', container );

    signin_form.on( 'submit', function() {

      var data = jQuery(this).serializeArray().reduce(function(obj, item) {
        obj[item.name] = item.value;
        return obj;
      }, {});

      jQuery.post( ajaxurl, {
        action: 'wpp_retsci_subscription',
        security: config.security,
        payload: {
          retsci_site_id: config.retsci_site_id,
          retsci_site_secret_token: config.retsci_site_secret_token,
          user_data: config.user_data,
          blog_id: config.blog_id,
          credentials: data,
          api_url: config.api_url,
        }
      }, function( res ){

        if ( res.ok ) {
          alert('Data added successfully.');
        }

        if ( res.message ) {
          jQuery( '.message', container).html( res.message );
        }

      });

      return false;
    });
  }

});