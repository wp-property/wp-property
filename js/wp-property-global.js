/**
 * WP-Property Global Scripts
 *
 * This file is included on all front-end and specific back-end pages.
 *
*/

/* Determine if page is loaded in frame. */
if ( top === self ) {
  //not in a frame
} else {
  //in a frame
  if( typeof window.wpp === 'object' &&
      typeof window.wpp.instance === 'object' &&
      typeof window.wpp.instance.iframe_enabled !== 'undefined' &&
      window.wpp.instance.iframe_enabled === true ) {
    // ignore. Application allows to be used in iframe
  } else {
    top.location.href = document.location.href;
  }
}

jQuery.extend( wpp = wpp || {}, {

  // Global Settings
  settings: {
    debug: false
  },

  /**
   * Global Debug Logger
   *
   * Accepts any number of arguments and passes them to console.log, if available.
   * Returns the first argument.
   *
   * @usage
   *    wpp.debug( 'debug data', { key: 'some value' } );
   *
   * @return {data} Returns first argument.
   * @method debug
   * @for wpp
   */
  debug: function debug( data ) {

    // Ignore if debugging is not enabled.
    if( !wpp.settings.debug ) {
      return data;
    }

    if( 'function' === typeof console.log ) {
      console.log.apply( console, arguments );
    }

    return data;

  },

  /**
   * Formatting Methods. (todo)
   *
   */
  format: {

    /**
     * Format Currency
     *
     */
    currency: function currency( selector ) {

      jQuery(selector).change(function() {
        this_value = jQuery(this).val();
        var val = jQuery().number_format( this_value.replace(/[^\d|\.]/g,'') );
        jQuery(this).val( val );
      });

    },

    /**
     * Format Number
     *
     */
    number: function number( selector ) {

      jQuery(selector).change(function() {
        this_value = jQuery(this).val();
        var val = jQuery().number_format( this_value.replace(/[^\d|\.]/g,''), {
          numberOfDecimals:0,
          decimalSeparator: '.',
          thousandSeparator: ','
        } );

        if(val == 'NaN') {
          val = '';
        }

        jQuery(this).val( val );
      });

    },

    /**
     * Add Commas
     *
     * @method commas
     */
    commas: function commas( nStr ) {

      nStr += '';
      x = nStr.split('.');
      x1 = x[0];
      x2 = x.length > 1 ? '.' + x[1] : '';
      var rgx = /(\d+)(\d{3})/;
      while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
      }
      return x1 + x2;

    }

  }

});

/**
 * Legacy Support
 *
 * @param selector
 * @returns {*}
 */
function wpp_format_currency( selector ) {
  return wpp.format.currency( selector );
}

/**
 * Legacy Support
 *
 * @param selector
 * @returns {*}
 */
function wpp_format_number( selector ) {
  return wpp.format.number( selector );
}

/**
 * Legacy Support
 *
 * @param data
 * @returns {*}
 */
function wpp_add_commas( data ) {
  return wpp.format.commas( data );
}