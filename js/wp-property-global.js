/**
 * WP-Property Global Scripts
 *
 * This file is included on all back-end and front-end pages.
 *
*/


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