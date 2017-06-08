/**
 * Gallery
 *
 * @version 1.0.1
 * @author potanin@UD
 */
define( 'udx.ui.gallery', [ 'udx.utility.imagesloaded', 'jquery.isotope', 'jquery.fancybox' ], function Gallery() {
  // console.debug( 'udx.ui.gallery', 'loaded' );

  /**
   * Bind Fancybox.
   *
   */
  function bindFancybox( element, options ) {
    // console.debug( 'udx.ui.gallery', 'bindFancybox', options );

    jQuery( 'a', element ).fancybox( options );

  }

  /**
   * Bind Isotpe.
   *
   */
  function bindIsotope( element, options ) {
    // console.debug( 'udx.ui.gallery', 'bindIsotope', options );

    var isotope = require( 'jquery.isotope' );

    if( !require( 'jquery.isotope' ) ) {
      console.error( 'udx.ui.gallery', 'isotope not available as expected' );
      return;
    }

    jQuery( element ).each( function eachElement() {

      new isotope( this, options );

    });

  }

  /**
   * Execute on DOM Ready.
   *
   * @todo Remove ghetto timeouts and make binding triggered when images are ready.
   */
  return function domnReady() {
    // console.debug( 'udx.ui.gallery', 'ready' );

    var self = this;
    var imagesLoaded = require( 'udx.utility.imagesloaded' )( this );

    imagesLoaded.on( 'done', function onDone() {
      // console.log( 'done' );

      if( self.options.isotope ) {
        bindIsotope( jQuery( self ), self.options.isotope );
      }

      if( self.options.fancybox ) {
        bindFancybox( jQuery( self ), self.options.fancybox );
      }

    });

    imagesLoaded.on( 'fail', function onFail( error ) {
      console.error( 'udx.ui.gallery', error );
    });

    // Set default optiosn.
    this.options = jQuery.extend( this.options, {
      isotope: {
        cellsByColumn: {
          columnWidth: 240,
          rowHeight: 360
        }
      },
      fancybox: {
        speedIn: 600,
        speedOut: 200,
        helpers:  {
          title : {
            type : 'inside'
          },
          overlay : {
            showEarly : false
          }
        }
      }
    });

    return this;

  }

});

