/**
 * WP-Property Edit Agent Page
 *
 * This file is included on Edit Agent page.
 *
 * @auhor peshkov@UD
 */

jQuery( document ).ready( function() {

  //** Show 'Options' section if it's not empty */
  jQuery( 'tr.wpp_agent_options' ).find( '.wpp_agents_agent_options' ).each( function( i,e ) {
    if( !jQuery(e).is(':empty') ) {
      jQuery( 'tr.wpp_agent_options' ).show();
      return null;
    }
  } );

} );