<?php
/**
 * Functions to Set Feature Flags
 *
 * @author den@UD
 * @package WP-Property
 */

/**
 * Filter for defining fetures flags
 *
 * @author den@UD
 * @return define = true\false
 */


add_filter('flag_wp_property_layouts', function ($enabled) {
  $settings = get_option('wpp_settings');
  if (isset($settings['configuration']['disable_layouts']) && ($settings['configuration']['disable_layouts'] == 'true')) {
    return false;
  }
  return $enabled;
});

if (!function_exists('parse_feature_flags')) {
  /**
   * Set Feature Flag constants by parsing composer.json
   *
   * @todo Make sure settings from DB can override these.
   *
   * @author potanin@UD
   * @return array|mixed|null|object
   */
  function parse_feature_flags()
  {
    try {
      $_raw = file_get_contents(plugin_dir_path(__FILE__) . 'composer.json');
      $_parsed = json_decode($_raw);
      // @todo Catch poorly formatted JSON.
      if (!is_object($_parsed)) {
        // throw new Error( "unable to parse."  );
      }
      foreach ((array)$_parsed->extra->featureFlags as $_feature) {
        if (!defined($_feature->constant . '_DEFAULT')) {
          define($_feature->constant . '_DEFAULT', $_feature->enabled);
        }
        if (!defined($_feature->constant)) {
          define($_feature->constant, apply_filters('flag_' . strtolower($_feature->constant), $_feature->enabled));
        }
      }
    } catch (Exception $e) {
      echo 'Caught exception: ', $e->getMessage(), "\n";
    }
    return isset($_parsed) ? $_parsed : null;
  }

  // Init feature flags
  parse_feature_flags();

}