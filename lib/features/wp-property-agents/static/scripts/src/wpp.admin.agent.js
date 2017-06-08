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

(function ($) {
  $.fn.agent_image_select = function (options) {

    var settings = $.extend({
      image: '.image_input'
    }, options );

    var file_frame;
    var that = this;

    this.on('click', function (event) {

      event.preventDefault();

      // If the media frame already exists, reopen it.
      if (file_frame) {
        file_frame.open();
        return;
      }

      // Create the media frame.
      file_frame = wp.media.frames.file_frame = wp.media({
        title: that.data('uploader_title'),
        button: {
          text: that.data('uploader_button_text')
        },
        multiple: false  // Set to true to allow multiple files to be selected
      });

      // When an image is selected, run a callback.
      file_frame.on('select', function () {
        var selected = file_frame.state().get('selection').first().toJSON();
        $.post(window.ajaxurl, {
          action: 'wpp_save_agent_image',
          agent_id: wpp.instance.get.user_id,
          attachment_id: selected.id
        }, function(response){
          if (response.success) {
            $(settings.image).attr('src', selected.url).parents('li').show();
            $('.wpp_no_agent_images').hide();
          }
        })
      });

      // Finally, open the modal
      file_frame.open();
    });
  };
}(jQuery));