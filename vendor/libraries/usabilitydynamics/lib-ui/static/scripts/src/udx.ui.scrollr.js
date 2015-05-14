/**
 * Scrollr
 *
 * @todo Handle show-on-ready better.
 * @todo Right now the height must be set in CSS, otherwise will be arbitrary.
 *
 * @author peshkov@UD
 *
 */
define( 'udx.ui.scrollr', [ 'scrollr' ], function( jQuery ) {
  console.debug( 'udx.ui.scrollr', 'loaded' );

  /**
   *
   */
  return function domnReady() {
    console.debug( 'udx.ui.scrollr', 'ready' );

    if( 'undefined' == typeof skrollr ) {
      console.error( 'udx.ui.swiper', 'Scrollr is not available.' );
    }
    
    this.options = Object.extend( this.options, {
      forceHeight:false
    });

    this.skrollr = skrollr.init({
      forceHeight:this.options.forceHeight
    });

    return this;

  };

});

