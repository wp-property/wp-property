/**
 * WPP Address
 */
jQuery( document ).ready( function () {
  jQuery( 'input#wpp_manual_coordinates' ).change( function () {
    var use_manual_coordinates;
    if ( jQuery( this ).is( ":checked" ) ) {
      use_manual_coordinates = true;
      jQuery( '#wpp_coordinates' ).show();
    } else {
      use_manual_coordinates = false;
      jQuery( '#wpp_coordinates' ).hide();
    }
  } );
} );