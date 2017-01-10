/**
 * Customizer scripts
 */
jQuery(document).ready(function () {

  jQuery('#accordion-section-layouts_property_overview_settings h3').on('click', function () {
    wp.customize.previewer.previewUrl('http://avalon.loc/properties/');
  });
  jQuery('#accordion-section-layouts_property_single_settings h3').on('click', function () {
    wp.customize.previewer.previewUrl('http://avalon.loc/property/460-w-huron-st/');
  });

});
