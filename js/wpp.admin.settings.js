/**
 * WP-Property Admin Settings page
 *
 */
jQuery.extend( wpp = wpp || {}, { ui: { settings: {

  /**
   * Initialize DOM.
   *
   * @for wpp.settings
   * @method ready
   */
  ready: function() {

    /**
     * @todo: move submit functionality here instead of reloading page.
     * 
     */

  }

}}});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.settings.ready );