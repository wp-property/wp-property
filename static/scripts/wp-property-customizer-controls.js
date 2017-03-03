/**
 * Customizer scripts
 *
 * @todo Dont use [wpp.instance.settings.configuration] to hold the preview URLs.
 *
 */
jQuery(document).ready(function () {

  var propertyOverviewUrl = wpp.instance._customizer.base_property_url;
  var propertySingleUrl = wpp.instance._customizer.base_property_single_url;
  var base_property_term_url = wpp.instance._customizer.base_property_term_url;

  jQuery('#accordion-section-layouts_property_overview_settings h3').on('click', function () {
    wp.customize.previewer.previewUrl(propertyOverviewUrl);
  });

  jQuery('#accordion-section-layouts_property_single_settings h3').on('click', function () {
    wp.customize.previewer.previewUrl(propertySingleUrl);
  });

  jQuery('#accordion-section-layouts_property_term_settings h3').on('click', function () {
    wp.customize.previewer.previewUrl(base_property_term_url);
  });

});
