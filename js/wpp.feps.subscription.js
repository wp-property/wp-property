/**
 * FEPS Subscription plans step
 *
 * @author peshkov@UD
 */
 
if( typeof feps_subsc_form !== 'function' ) {
  var feps_subsc_form = function( el ) {

    el.each( function( i, e ) {
      if( jQuery( e ).hasClass( 'feps_subsc_form_activated' ) ) {
        return null;
      }

      jQuery( 'li.wpp_feps_row_wrapper', e ).click( function() {
        jQuery( 'input[name="subscription_plan"]', e ).val( jQuery( this ).data( 'subsc_plan' ) );
        jQuery( 'li.wpp_feps_row_wrapper', e ).removeClass( 'feps_plan_selected' );
        jQuery( this ).addClass( 'feps_plan_selected' );
      } );

      jQuery( e ).addClass( 'feps_subsc_form_activated' );
    } );
  };
}
feps_subsc_form( jQuery( '.wpp_feps_subscription_plan_form' ) );