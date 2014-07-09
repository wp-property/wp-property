jQuery( document ).ready( function () {

  if ( typeof jQuery.fn.fancybox === 'function' ) {
    jQuery( 'a.fancybox_image' ).live( 'click', function () {
      if ( !jQuery( this ).hasClass( 'activated' ) ) {
        jQuery( this ).fancybox( {
          'transitionIn': 'elastic',
          'transitionOut': 'elastic',
          'speedIn': 600,
          'speedOut': 200,
          'overlayShow': false
        } );
        jQuery( this ).addClass( 'activated' );
        jQuery( this ).trigger( 'click' );
      }
      return false;
    } );
  }

  jQuery( "a.fancybox_image img" ).click( function ( e ) {
    /* Do nothing in FancyBox is set */
    if ( typeof jQuery.fn.fancybox === 'function' ) {
      return null;
    }
    /* Fancybox is not set as expected, do not open the image URL */
    e.preventDefault();
  } );

  /* Scroll to top of pagination */
  jQuery( document ).bind( 'wpp_pagination_change', function ( e, data ) {
    var overview_id = data.overview_id;
    var position = jQuery( "#wpp_shortcode_" + overview_id ).offset();
    if( typeof jQuery.scrollTo !== 'undefined' ) {
      jQuery.scrollTo( position.top - 40 + 'px', 1500 );
    }
  } );

  jQuery( ".ui-tabs" ).bind( "tabsshow", function ( event, ui ) {
    var panel = ui.panel;
    jQuery( document ).trigger( "wpp::ui-tabs::tabsshow", panel );
  } );

  jQuery( ".ui-tabs" ).bind( "tabsselect", function ( event, ui ) {
    var panel = ui.panel;
    jQuery( document ).trigger( "wpp::ui-tabs::tabsselect", panel );
  } );

} );
