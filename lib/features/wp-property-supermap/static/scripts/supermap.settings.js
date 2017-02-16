(function ($) {
  $.fn.map_marker_select = function (options) {

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
        var image_url = typeof file_frame.state().get('selection').first().toJSON().sizes.supermap_marker != 'undefined'
                        ? file_frame.state().get('selection').first().toJSON().sizes.supermap_marker.url
                        : file_frame.state().get('selection').first().toJSON().url;
        $(settings.image, $(event.currentTarget)).attr('src', image_url );
        $('.wpp_supermap_marker_file', $(event.currentTarget)).val( image_url );
      });

      // Finally, open the modal
      file_frame.open();
    });
  };
}(jQuery));