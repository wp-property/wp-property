/**
 * WP-Property Widgets Page
 *
 * This file is included on widgets page.
 *
 * @auhor peshkov@UD
 */

wpp = jQuery.extend( true, typeof wpp === 'object' ? wpp : {}, {
  'widgets' : {}
} );


/**
 * Inits Search Properties Widget form's functionality
 * Should not be called directly: used by wpp.widgets.run()
 *
 * @author peshkov@UD
 */
wpp.widgets._search_properties_widget = function( e ) {

  /* */
  var set_group_or_ungroup = function() {
    if(jQuery( "input.wpp_toggle_attribute_grouping", e ).is(":checked")) {
      jQuery(".wpp_subtle_tabs", e ).tabs( 'option', 'active', 1 );
    } else {
      jQuery(".wpp_subtle_tabs", e ).tabs( 'option', 'active', 0 );
    }
  }

  /* */
  var adjust_property_type_option = function() {
    var count = jQuery( "input.wpp_property_types:checked", e ).length;
    if(count < 2) {
      jQuery( ".wpp_attribute_wrapper.property_type", e ).hide();
      jQuery( ".wpp_attribute_wrapper.property_type input", e ).attr("checked", false);
    } else {
      jQuery( ".wpp_attribute_wrapper.property_type", e ).show();
    }
  }

  /* Run on load to hide property type attribute if there is less than 2 property types */
  adjust_property_type_option();

  jQuery( ".wpp_all_attributes .wpp_sortable_attributes", e ).sortable();

  /* Setup tab the grouping/ungrouping tabs, and trigger checking the select box when tabs are switched */
  jQuery( ".wpp_subtle_tabs", e ).tabs({
    select: function( event, ui ) {
      jQuery( "input.wpp_toggle_attribute_grouping", e ).attr("checked", ( ui.index == 0 ? false : true ) );
    }
  });

  /* Select the correct tab */
  set_group_or_ungroup();

  /* Select grouped tab if grouping is enabled here */
  jQuery( "input.wpp_property_types" ).change(function() {
    adjust_property_type_option();
  });

  /* Select grouped tab if grouping is enabled here */
  jQuery( "input.wpp_toggle_attribute_grouping" ).change(function() {
    set_group_or_ungroup();
  });

}

/**
 * Inits Property Attributes Widget functionality
 * Should not be called directly: used by wpp.widgets.run()
 *
 * @author peshkov@UD
 */
wpp.widgets._property_attributes_widget = function( e ) {
  jQuery( ".wpp_sortable_attributes", e ).sortable();
}


/**
 * Goes through all wpp widgets and inits them
 *
 * @author peshkov@UD
 */
wpp.widgets.run = function() {

  jQuery( '.wpp_widget' ).each( function( i, e ) {
    e = jQuery(e);
    /* Determine if element has number, if not it's not registered and we ignore it */
    if( isNaN( parseInt( e.data( 'widget_number' ) ) ) ) return null;
    /* Ignore if we already called function for the current widget's element */
    if( e.hasClass( 'wpp_widget_loaded' ) ) return null;
    /* Be sure that we init it at once. */
    e.addClass( 'wpp_widget_loaded' );

    switch( e.data( 'widget' ) ) {
      case 'search_properties_widget':
        wpp.widgets._search_properties_widget( e );
        break;

      case 'property_attributes_widget':
        wpp.widgets._property_attributes_widget( e );
        break;
    }

  } );

}


/* Call widgets_run on specific events ( on DOM updates )  */
jQuery( document ).ready( function() {

  /* After every ajax call. Some callbacks on ajax call update DOM structure. */
  jQuery( document ).live( 'ajaxComplete', function(){ wpp.widgets.run() });

  /* */
  jQuery( 'div.widgets-sortables' ).on( 'sortstop', function( event, ui ) {
    setTimeout( function() { wpp.widgets.run() }, 100 );

  } );

  /* */
  wpp.widgets.run();

} );