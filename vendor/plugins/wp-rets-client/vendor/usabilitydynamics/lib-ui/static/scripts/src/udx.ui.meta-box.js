/**
 * Steram
 *
 */
define( 'udx.ui.meta-box', [ 'require', 'exports', 'module', 'udx.utility' ], function MetaBox( require, exports, module ) {
  // console.debug( 'udx.ui.meta-box', 'loaded' );

  return function domnReady() {
    // console.debug( 'udx.ui.meta-box', 'ready', this );

    if( this.getAttribute( 'data-type' ) === 'google-map' ) {
      jQuery( 'button.rwmb-map-goto-address-button', this ).hide();

      var mapController   = jQuery( '.rwmb-map-field', this ).data( 'mapController' );
      var canvas          = jQuery( mapController.canvas );

      canvas.on( 'updateCoordinates', function updatedCoordinates( event, data ) {
        console.log( 'updatedCoordinates', data  );

      });

      // 137 Holly Tree Lane, Hampstead, NC 28443, USA
      canvas.on( 'geolocationComplete', function geolocationComplete( event, data ) {

        var address = data.address || {};

        var terms = {
          city:     ( address.locality || {} ).long_name,
          country:  ( address.country || {} ).long_name,
          state:    ( address.administrative_area_level_1 || {} ).long_name
        }


        if( 'function' === typeof jQuery.fn.select2 ) {

          jQuery( 'div[data-field-type=taxonomy]' ).each( function eachField() {

            return;

            var id = jQuery( this ).attr( 'data-field-id' );
            var field =jQuery( '.select2-container', this );
            var data = field.select2( 'data' );

            if( id === 'term-city' && terms.city ) {
              // field.select2( 'val', terms.city );
            }

            if( id === 'term-state' && terms.state ) {
              // select2( 'val', terms.state );
            }

            if( id === 'term-country' && terms.country ) {
              // select2( 'val', terms.country );
            }


          })


        }

        console.dir( terms );

      });

    }

    return this;

  };

});
