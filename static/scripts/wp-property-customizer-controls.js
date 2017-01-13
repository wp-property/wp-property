/**
 * Customizer scripts
 */
jQuery(document).ready(function () {

  var propertyOverviewUrl = wpp.instance.settings.configuration.base_property_url;
  var propertySingleUrl = wpp.instance.settings.configuration.base_property_single_url;

  jQuery('#accordion-section-layouts_property_overview_settings h3').on('click', function () {
    wp.customize.previewer.previewUrl(propertyOverviewUrl);
  });
  jQuery('#accordion-section-layouts_property_single_settings h3').on('click', function () {
    wp.customize.previewer.previewUrl(propertySingleUrl);
  });

});
