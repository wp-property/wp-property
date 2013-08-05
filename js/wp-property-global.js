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


function wpp_format_currency( selector ) {

  jQuery(selector).change(function() {
    this_value = jQuery(this).val();
    var val = jQuery().number_format( this_value.replace(/[^\d|\.]/g,'') );
    jQuery(this).val( val );
  });

}


function wpp_format_number( selector ) {

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

}


function wpp_add_commas( nStr ) {
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