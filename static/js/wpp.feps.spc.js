/**
 * FEPS SPC page
 *
 * @author peshkov@UD
 */

jQuery(document).ready(function(){

  jQuery( document ).bind( 'wpi_spc_validation_fail', function( event, result, target, gateway ) {
    wpp_feps_spc_event( 'wpi_spc_validation_fail', result, target, gateway );
  } );
  jQuery( document ).bind( 'wpi_spc_success', function( event, result, target, gateway ) {
    wpp_feps_spc_event( 'wpi_spc_success', result, target, gateway );
  } );
  jQuery( document ).bind( 'wpi_spc_processing_failure', function( event, result, target, gateway ) {
    wpp_feps_spc_event( 'wpi_spc_processing_failure', result, target, gateway );
  } );

  function wpp_feps_spc_event( event, result, target, gateway ) {
    jQuery( '.wpi_checkout_payment_response', target ).remove();
    var scp_wrapper = jQuery( target ).parents( '.wpi_checkout' );
    if( !jQuery('.wpp_feps_message').length ) {
      scp_wrapper.before( '<div class="wpp_feps_message"></div>' );
    }
    jQuery('.wpp_feps_message').hide().removeClass( 'error' ).html( '' );
    var message = '';
    switch ( event ) {
      case 'wpi_spc_validation_fail':
        message = wpp.strings.validation_error;
        jQuery('.wpp_feps_message').addClass( 'error' );
        break;
      case 'wpi_spc_processing_failure':
        message = result.message;
        jQuery('.wpp_feps_message').addClass( 'error' );
        break;
      case 'wpi_spc_success':
        scp_wrapper.hide();
        message = result.message;
        break;
    }
    jQuery('.wpp_feps_message').html( message ).show();
  }

});