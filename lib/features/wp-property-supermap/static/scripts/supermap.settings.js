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

jQuery(document).ready(function(){
  /* Change marker's image preview on marker changing */
  jQuery('select.wpp_setting_property_type_marker').live('change', function(){
    var e = jQuery(this).parents('.wpp_property_type_supermap_settings');
    var filename = jQuery(this).val();
    var rand = Math.random();
    var HTML = '';
    if(filename != '') {
      HTML = '<img src="' + filename + '?' + rand + '" alt="" />';
    } else {
      HTML = '<img src="' + wpp_supermap_default_marker + '" alt="" />';
    }
    e.find('.wpp_supermap_marker_image').html(HTML);
  });

  /* Fire marker's image changing Event after Row is added */
  if(jQuery('#wpp_inquiry_property_types').length > 0) {
    jQuery('#wpp_inquiry_property_types tr').live('added', function(){
      jQuery('select.wpp_setting_property_type_marker', this).trigger('change');
    });
  }
});