/**
 * Script Ran on Customizer Side
 *
 * Handles editor and UI.
 *
 * We use jQuery.ui.tabs( options, element ) instead of jQuery().tabs( options ).
 *
 */
define( 'udx.ui.jquery.tabs', [ 'jquery.ui' ], function scriptEditor() {
  // module.log( 'Module loaded.' );
  // module.debug( 'module debug' );
  // module.error( 'module error' );

  return function domnReady() {
    // module.log( 'callbackOfEditor', 'Module initialized.' );

    // Ensure UI Tabs Available.
    if( !jQuery.fn.tabs ) {
      return console.error( 'jQuery.fn.tabs not defined' );
    }

    // this.getAttribute( 'data-size' );

    // Enanble Tabs.
    var _tabs = jQuery( this ).tabs({
      collapsible: true
    });

    // Make Visble.
    if( _tabs.hasClass( 'hidden' ) ) {
     _tabs.removeClass( 'hidden' )
    }

    return _tabs;

  };

});

