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
      var error = false;
      if ( response.success ) {
        jQuery( '.feps_spc_details_wrapper .submit_action_wrapper' ).remove();
        jQuery( '.wpp_feps_change_subscription_plan' ).remove();
      } else {
        error = true;
      }
      wpp_feps_checkout_message( response.message, response.property_id, error );
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
    var message = '', 
        error = false;
    switch ( event ) {
      case 'wpi_spc_validation_fail':
        message = wpp.strings.validation_error;
        error = true;
        break;
      case 'wpi_spc_processing_failure':
        message = result.message;
        error = true;
        break;
      case 'wpi_spc_success':
        jQuery( target ).parents( '.wpp_feps_checkout_wrapper' ).remove();
        jQuery( '.wpp_feps_change_subscription_plan' ).remove();
        message = result.message;
        break;
    }
    var property_id = typeof result.callback.post_data['wpp::feps::property_id'][0] !== 'undefined' ? result.callback.post_data['wpp::feps::property_id'][0] : null;
    wpp_feps_checkout_message( message, property_id, error );
  }
  
  /**
   * Shows result of checkout process
   * 
   * @param {type} message
   * @param {type} property_id
   * @param {type} error
   * @returns {undefined}
   */
  function wpp_feps_checkout_message( message, property_id, error ) {
    if( typeof error != 'undefined' && error ) {
      jQuery('.wpp_feps_message').addClass( 'error' );
    } else {
      jQuery('.wpp_feps_message').removeClass( 'error' );
    }
    window.wpp_feps_checkout_message = message;
    /* This event can be used for customizations */
    jQuery( document ).trigger( 'wpp_feps_checkout_message', [property_id, error] );
    setTimeout( function() {
      if( window.wpp_feps_checkout_message && window.wpp_feps_checkout_message != '' ) {
        jQuery('.wpp_feps_message').html( window.wpp_feps_checkout_message ).show();
      }
    }, 50 );
  }

});