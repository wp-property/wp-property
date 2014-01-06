/**
 * WP-Property Edit Agent Page
 *
 * This file is included on Edit Agent page.
 *
 * @author peshkov@UD
 */
jQuery.extend( wpp = wpp || {}, { ui: { agents: {

  /**
   * Initialize DOM.
   *
   * @for wpp.ui.agents
   * @method ready
   */
  ready: function() {

    //** Show 'Options' section if it's not empty */
    jQuery( 'tr.wpp_agent_options' ).find( '.wpp_agents_agent_options' ).each( function( i,e ) {
      if( !jQuery(e).is(':empty') ) {
        jQuery( 'tr.wpp_agent_options' ).show();
        return null;
      }
    } );

  }

}}});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.agents.ready );