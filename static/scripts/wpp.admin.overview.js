/**
 * WP-Property Admin Overview Scripts
 *
 *
 * @class wpp.overview
 */
jQuery.extend( wpp = wpp || {}, { overview: {

  initialized: false,

  /**
   * Initialize DOM.
   *
   * @for wpp.overview
   * @method ready
   */
  ready: function ready() {

    // Toggling filter options
    jQuery( '.wpp_filter_section_title' ).click( function () {
      var parent = jQuery( this ).parents( '.wpp_overview_filters' );
      jQuery( '.wpp_checkbox_filter', parent ).slideToggle( 'fast', function () {
        if ( jQuery( this ).css( 'display' ) == 'none' ) {
          jQuery( '.wpp_filter_show', parent ).html( wpp.strings.show );
        } else {
          jQuery( '.wpp_filter_show', parent ).html( wpp.strings.hide );
        }
      });
    });

    jQuery(document).on('wplt-after-update', function(e, instance, type, r){
      var el = jQuery(instance);
      // Showing or hiding the pagination.
      if(typeof r.data.pagination._pagination_args !== 'undefined' && r.data.pagination._pagination_args.total_pages > 1 ){
        jQuery('.tablenav .tablenav-pages', el).show();
      }
      else{
        jQuery('.tablenav .tablenav-pages', el).hide();
      }
      wpp.overview.initialize();
    });
    wpp.overview.initialize();

  },

  /**
   * Toggle Feature.
   *
   * @for wpp.overview
   * @method toggle_featured
   */
  toggle_featured: function toggle_featured() {
    var post_id = jQuery( this ).attr( "id" ).replace( 'wpp_feature_', '' );

    jQuery.post( wpp.instance.ajax_url, {
        post_id: post_id,
        action: 'wpp_make_featured',
        _wpnonce: jQuery( this ).attr( "nonce" )
      }, function toggle_featured_response( data ) {

        var button = jQuery( "#wpp_feature_" + data.post_id );

        if ( data.status == 'featured' ) {
          jQuery( button ).val( wpp.strings.featured );
          jQuery( button ).addClass( 'wpp_is_featured' );
        }

        if ( data.status == 'not_featured' ) {
          jQuery( button ).val( wpp.strings.add_to_featured );
          jQuery( button ).removeClass( 'wpp_is_featured' );
        }

        jQuery(document).trigger( 'after-toggle-featured', [ data ] );

      }, 'json' );

  },

  /**
   * Initialize User Interface.
   *
   * This function may be ran multiple times.
   *
   * @for wpp.overview
   * @method initialize
   */
  initialize: function initialize() {
    /* Load fancybox if it exists */
    if ( jQuery.fn.fancybox && typeof jQuery.fn.fancybox == 'function' ) {
      jQuery( ".fancybox" ).fancybox( {
        'transitionIn': 'elastic',
        'transitionOut': 'elastic',
        'speedIn': 600,
        'speedOut': 200,
        'overlayShow': false
      });
    }
    
    // Toggle Featured Setting
    if ( jQuery.fn.live && typeof jQuery.fn.live == 'function' && !wpp.overview.initialized ) {
      jQuery( document ).on( 'click', ".wpp_featured_toggle", wpp.overview.toggle_featured );
    }
    
    wpp.overview.initialized = true;
  },

  filterEvent: function filterEvent( event ) {
    // console.log( 'filterEvent' );

    if( 'object' === typeof wp_list_table ) {
      event.preventDefault();
      wp_list_table.fnDraw();
    }

  }

}});

// Initialize Overview.
jQuery( document ).ready( wpp.overview.ready );