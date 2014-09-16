/**
 * FEPS Subscription plans step
 *
 * @author peshkov@UD
 */

(function($){

  var feps_subsc_form = function( el ) {
    el.each( function( i, e ) {
      if( $( e ).hasClass( 'feps_subsc_form_activated' ) ) {
        return null;
      }
      $( 'li.wpp_feps_row_wrapper', e ).click( function() {
        $( 'input[name="subscription_plan"]', e ).val( $( this ).data( 'subsc_plan' ) );
        $( 'li.wpp_feps_row_wrapper', e ).removeClass( 'feps_plan_selected' );
        $( this ).addClass( 'feps_plan_selected' );
      } );
      $( e ).addClass( 'feps_subsc_form_activated' );
    } );
  };
  
  $('#renew_plan_' + wpp.instance.get.feps).click( function(){
    jQuery.post( wpp.instance.ajax_url, {
      'action' : 'wpp_feps_renew_plan',
      'post_id' : wpp.instance.get.feps,
      'value' : $(this).is(':checked')
    });
  } );

  feps_subsc_form( $( '.wpp_feps_subscription_plan_form' ) );
  
})(jQuery);




