<?php
/**
 * Plugin Name: WP-Property
 * Plugin URI: https://www.usabilitydynamics.com/product/wp-property/
 * Description: Property and Real Estate Management Plugin for WordPress.  Create a directory of real estate / rental properties and integrate them into you WordPress CMS.
 * Author: Usability Dynamics, Inc.
 * Version: 2.3.8
 * Requires at least: 4.0
 * Tested up to: 4.9.8
 * Text Domain: wpp
 * Domain Path: /static/languages/
 * Author URI: https://www.usabilitydynamics.com
 * GitHub Plugin URI: wp-property/wp-property
 * GitHub Branch: v2.3
 * Support: https://wordpress.org/support/plugin/wp-property
 * UserVoice: http://feedback.usabilitydynamics.com/forums/95259-wp-property
 * 
 * Copyright 2012 - 2018 Usability Dynamics, Inc.  ( email : info@usabilitydynamics.com )
 *
 */

/** Get Directory - not always wp-property */
if (!defined('WPP_Directory')) {
  define('WPP_Directory', dirname(plugin_basename(__FILE__)));
}

/** Path for Includes */
if (!defined('WPP_Path')) {
  define('WPP_Path', plugin_dir_path(__FILE__));
}

/** Path for front-end links */
if (!defined('WPP_URL')) {
  define('WPP_URL', plugin_dir_url(__FILE__) . 'static/');
}

/** Directory path for includes of template files  */
if (!defined('WPP_Templates')) {
  define('WPP_Templates', WPP_Path . 'static/views');
}

if (!function_exists('ud_get_wp_property')) {

  /**
   * Returns  Instance
   *
   * @author Usability Dynamics, Inc.
   * @since 2.0.0
   * @param bool $key
   * @param null $default
   * @return
   */
  function ud_get_wp_property($key = false, $default = null)
  {
    $instance = \UsabilityDynamics\WPP\Bootstrap::get_instance();
    return $key ? $instance->get($key, $default) : $instance;
  }

}

if (!function_exists('ud_check_wp_property')) {
  /**
   * Determines if plugin can be initialized.
   *
   * @author Usability Dynamics, Inc.
   * @since 2.0.0
   */
  function ud_check_wp_property()
  {
    global $_ud_wp_property_error;
    try {
      //** Be sure composer.json exists */
      $file = dirname(__FILE__) . '/composer.json';
      if (!file_exists($file)) {
        throw new Exception(__('Distributive is broken. composer.json is missed. Try to remove and upload plugin again.', ud_get_wp_property()->domain));
      }
      $data = json_decode(file_get_contents($file), true);
      //** Be sure PHP version is correct. */
      if (!empty($data['require']['php'])) {
        preg_match('/^([><=]*)([0-9\.]*)$/', $data['require']['php'], $matches);
        if (!empty($matches[1]) && !empty($matches[2])) {
          if (!version_compare(PHP_VERSION, $matches[2], $matches[1])) {
            throw new Exception(sprintf(__('Plugin requires PHP %s or higher. Your current PHP version is %s', ud_get_wp_property()->domain), $matches[2], PHP_VERSION));
          }
        }
      }
      //** Be sure vendor autoloader exists */
      if (file_exists(dirname(__FILE__) . '/vendor/libraries/autoload.php')) {
        require_once(dirname(__FILE__) . '/vendor/libraries/autoload.php');
      } else {
        throw new Exception(sprintf(__('Distributive is broken. %s file is missed. Try to remove and upload plugin again.', ud_get_wp_property()->domain), dirname(__FILE__) . '/vendor/libraries/autoload.php'));
      }
      //** Be sure our Bootstrap class exists */
      if (!class_exists('\UsabilityDynamics\WPP\Bootstrap')) {
        throw new Exception(__('Distributive is broken. Plugin loader is not available. Try to remove and upload plugin again.', ud_get_wp_property()->domain));
      }
    } catch (Exception $e) {
      $_ud_wp_property_error = $e->getMessage();
      return false;
    }
    return true;
  }

}

if (!function_exists('ud_my_wp_plugin_message')) {
  /**
   * Renders admin notes in case there are errors on plugin init
   *
   * @author Usability Dynamics, Inc.
   * @since 1.0.0
   */
  function ud_wp_property_message()
  {
    global $_ud_wp_property_error;
    if (!empty($_ud_wp_property_error)) {
      $message = sprintf(__('<p><b>%s</b> can not be initialized. %s</p>', ud_get_wp_property()->domain), 'WP-Property', $_ud_wp_property_error);
      echo '<div class="error fade" style="padding:11px;">' . $message . '</div>';
    }
  }

  add_action('admin_notices', 'ud_wp_property_message');
}

// An alias for "ud_get_wp_property"
if (!function_exists('wpp')) {
  function wpp($key = false, $default = null) {
    return ud_get_wp_property($key, $default);
  }
}

//** Initialize. */
if (ud_check_wp_property()) {
  ud_get_wp_property();
}

/**
 * WP CLI Commands
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
  require_once( 'bin/wp-cli.php' );
}
