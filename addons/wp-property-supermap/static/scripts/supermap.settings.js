(function ($) {
  $.fn.map_marker_select = function (options) {

    var settings = $.extend({
      image: '.image_input'
    }, options );

    var file_frame;
    var that = this;
    var currentTarget;

    this.on('click', function (event) {
      currentTarget = this;

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

        var wpp_marker_image = $(settings.image, currentTarget);
        if(!wpp_marker_image.length){
          wpp_marker_image = jQuery("<img class='wpp_marker_image'/>")
          $('.wpp_supermap_ajax_uploader', currentTarget).append(wpp_marker_image);
        }
        wpp_marker_image.attr('src', image_url );
        $('.wpp_supermap_marker_file', $(currentTarget)).val( image_url );
        jQuery(currentTarget).trigger('change', image_url);

      });

      // Finally, open the modal
      file_frame.open();
    });
    return that;
  };
}(jQuery));


jQuery(document).ready(function(){
  /* Change marker's image preview on marker changing */
  jQuery(document).on('change', 'select.wpp_setting_property_type_marker', function(){
    //var e = jQuery(this).parents('.wpp_property_type_supermap_settings');
    //var marker_slug = jQuery(this).val();
    //var marker_url = _.wppMarkerUrl(marker_slug, supermap_configuration, wpp_supermap_default_marker, default_google_map_marker );
    //var rand = Math.random();
    //var HTML = '<img src="' + marker_url + '" alt="" />';
    //e.find('.wpp_supermap_marker_image').html(HTML);
  });

  /* Fire marker's image changing Event after Row is added */
  if(jQuery('#wpp_inquiry_property_types').length > 0) {
    jQuery(document).on('added', '#wpp_inquiry_property_types tr', function(){
      jQuery('select.wpp_setting_property_type_marker', this).trigger('change');
    });
  }
});