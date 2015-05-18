/**
 * WP-Property Admin Settings page
 *
 */

jQuery.extend( wpp.ui.editor, {

  /**
   * Initialize DOM.
   *
   * @for wpp.ui.settings
   * @method ready
   */
  ready: function editorReady() {
    console.log( 'wpp.ui.editor', 'ready' );

    if( 'object' === typeof localStorage && localStorage.getItem('wpp.editor.hidden') ) {
      jQuery( 'body.post-type-property' ).addClass( 'wpp-content-editor-hidden' );
    }

    wpp.ui.editor.ourButton.click( wpp.ui.editor.buttonClickHandler );

    jQuery( '#wp-content-wrap .wp-editor-tabs' )
      .find( '.wp-switch-editor' )
      .click( wpp.ui.editor.clickHandler )
      .end()
      .append( wpp.ui.editor.ourButton );

  },

  clickHandler: function clickHandler(e) {
    console.log( 'wpp.ui.editor', 'clickHandler' );

    e.preventDefault();

    jQuery( '#wp-content-editor-container, #post-status-info' ).show();

  },

  ourButton: jQuery( '<a id="wpp-editor-toggler" class="hide-if-no-js wp-switch-editor wpp-editor-toggler" data-option="wpp.editor.hide">Toggle Editor</a>' ),

  buttonClickHandler: function buttonClickHandler(e) {
    console.log( 'wpp.ui.editor', 'buttonClickHandler' );

    // Switch to the Page Builder interface
    e.preventDefault();

    // Hide/show the standard content editor
    jQuery( 'body.post-type-property' ).toggleClass( 'wpp-content-editor-hidden' );

    if( jQuery( 'body.post-type-property' ).hasClass( 'wpp-content-editor-hidden' ) ) {
      localStorage.setItem('wpp.editor.hidden', true );
    } else {
      localStorage.removeItem('wpp.editor.hidden' );
    }

  }

});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.editor.ready );