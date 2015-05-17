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

    // metabox.hide();
    //$('#content-resize-handle' ).show();
    // thisView.trigger('hide_builder');
  },

  ourButton: jQuery( '<a id="wpp-editor-toggler" class="hide-if-no-js wp-switch-editor wpp-editor-toggler">Toggle Editor</a>' ),

  buttonClickHandler: function buttonClickHandler(e) {
    console.log( 'buttonClickHandler' );

    // Switch to the Page Builder interface
    e.preventDefault();

    // Hide/show the standard content editor
    jQuery( 'body.post-type-property' ).toggleClass( 'wpp-content-editor-hidden' );

  }

});

// Initialize Overview.
jQuery( document ).ready( wpp.ui.editor.ready );