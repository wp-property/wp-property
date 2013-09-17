/**
 * FEPS Checkout step
 *
 * @author peshkov@UD
 */

jQuery(document).ready(function(){

  /* Withdraw credits */
  jQuery( 'form.wpp_feps_withdraw_credits' ).submit( function() {
    jQuery('.wpp_feps_message').html( '' );
    jQuery('.wpp_feps_message').removeClass( 'error' );
    var data = jQuery( this ).serialize();
    jQuery.post( wpp.instance.ajax_url, data, function(response) {
      if ( response.success ) {
        jQuery( '.feps_spc_details_wrapper .submit_action_wrapper' ).remove();
        jQuery( '.wpp_feps_change_subscription_plan' ).remove();
      } else {
        jQuery('.wpp_feps_message').addClass( 'error' );
      }
      jQuery('.wpp_feps_message').html( response.message ).show();
    }, 'JSON');
    return false;
  } );

  jQuery( '.add_credits input' ).click( function() {
    jQuery( '.feps_spc_details_wrapper .submit_action_wrapper' ).remove();
    jQuery( '.wpp_feps_checkout_wrapper' ).toggle( 'slow' );
  } );

  jQuery( '.wpp_feps_checkout_wrapper form' ).bind( 'submit', function() {
    jQuery('.wpp_feps_message').removeClass( 'error' );
  } );

  jQuery( document ).bind( 'wpi_spc_validation_fail', function( event, result, target, gateway ) {
    wpp_feps_checkout_event( 'wpi_spc_validation_fail', result, target, gateway );
  } );
  jQuery( document ).bind( 'wpi_spc_success', function( event, result, target, gateway ) {
    wpp_feps_checkout_event( 'wpi_spc_success', result, target, gateway );
  } );
  jQuery( document ).bind( 'wpi_spc_processing_failure', function( event, result, target, gateway ) {
    wpp_feps_checkout_event( 'wpi_spc_processing_failure', result, target, gateway );
  } );

  function wpp_feps_checkout_event( event, result, target, gateway ) {
    jQuery( '.wpi_checkout_payment_response', target ).remove();
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
        jQuery( target ).parents( '.wpp_feps_checkout_wrapper' ).remove();
        jQuery( '.wpp_feps_change_subscription_plan' ).remove();
        message = result.message;
        break;
    }
    jQuery('.wpp_feps_message').html( message ).show();
  }

});