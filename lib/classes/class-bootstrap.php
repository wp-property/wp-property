<?php
/**
 * Bootstrap
 *
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  use WPP_Core;
  use WPP_F;
  use UsabilityDynamics\SAAS_UTIL\Register;

  if (!class_exists('UsabilityDynamics\WPP\Bootstrap')) {

    final class Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin
    {

      public $core;

      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type \UsabilityDynamics\WPP\Bootstrap object
       */
      protected static $instance = null;

      public $layouts_settings = null;

      public $property_customizer = null;

      public $layouts = null;

      /**
       * Handle some stuff very early
       *
       * @author peshkov@UD
       */
      public function boot()
      {

        //** Initiate Meta Box Handler */
        new Meta_Box();

        // Parse feature falgs, set constants.
        $this->parse_feature_flags();

        // Take care about our features before plugins loaded!
        $this->load_features();

      }

      /**
       * May be load WP-Property Features
       *
       * @author peshkov@UD
       */
      public function load_features()
      {
        // Autoload all our features
        require_once(dirname(__DIR__) . '/autoload/autoload.php');

        // May be load Features

        // Start Taxonomies

        // Enables [wpp_location] Taxonomy
        if ( WPP_FEATURE_FLAG_WPP_LISTING_LOCATION ) {
          new Taxonomy_WPP_Location();
        }

        // Enables [wpp_listing_status] Taxonomy
        if ( WPP_FEATURE_FLAG_WPP_LISTING_STATUS ) {
          new Taxonomy_WPP_Listing_Status();
        }

        // Enables [wpp_listing_type] Taxonomy
        if( defined( 'WPP_FEATURE_FLAG_WPP_LISTING_TYPE' ) && WPP_FEATURE_FLAG_WPP_LISTING_TYPE ) {
          new Taxonomy_WPP_Listing_Type();
        }

        // Enables [wpp_schools] Taxonomy
        if ( defined( 'WPP_FEATURE_FLAG_WPP_SCHOOLS' ) && WPP_FEATURE_FLAG_WPP_SCHOOLS ) {
          new Taxonomy_WPP_Schools();
        }

        // Enables [wpp_listing_policy] taxonomy.
        if ( WPP_FEATURE_FLAG_WPP_LISTING_POLICY ) {
          new Taxonomy_WPP_Listing_Policy();
        }

        // Enables [wpp_listing_label] taxonomy.
        if ( WP_PROPERTY_FLAG_WPP_LISTING_LABEL ) {
          new Taxonomy_WPP_Listing_Label();
        }

        // Enables [wpp_categorical] taxonomy for multiple terms.
        if ( WPP_FEATURE_FLAG_WPP_CATEGORICAL ) {
          new Taxonomy_WPP_Categorical();
        }

        // Enables [wpp_listing_category] taxonomy.
        if ( WPP_FEATURE_FLAG_WPP_LISTING_CATEGORY ) {
          new Taxonomy_WPP_Listing_Category();
        }

        // End Taxonomies

        if( WP_PROPERTY_LEGACY_META_ATTRIBUTES ) {
          new Legacy_Meta_Attributes();
        }

        // Apply alises.
        if( WP_PROPERTY_FIELD_ALIAS ) {
          $this->alias = new Field_Alias();
        }

        // Maybe load our built-in Add-ons

        // Enable RETS Client
        if( RETSCI_FEATURE_FLAG && !defined( 'WP_RETS_CLIENT_VENDOR_LOAD' )) {
          define('WP_RETS_CLIENT_VENDOR_LOAD', true);
        }

      }

      /**
       * Instantaite class.
       */
      public function init()
      {
        global $wp_properties;

        add_action('admin_head', function () {
          global $wp_properties, $_wp_admin_css_colors;
          $wp_properties['admin_colors'] = $_wp_admin_css_colors[get_user_option('admin_color')]->colors;
        });


        /**
         * Duplicates UsabilityDynamics\WP\Bootstrap_Plugin::load_textdomain();
         *
         * There is a bug with localisation in lib-wp-bootstrap 1.1.3 and lower.
         * So we load textdomain here again, in case old version lib-wp-bootstrap is being loaded
         * by another plugin.
         *
         * @since 2.0.2
         */
        load_plugin_textdomain($this->domain, false, dirname(plugin_basename($this->boot_file)) . '/static/languages/');

        /** This Version  */
        if (!defined('WPP_Version')) {
          define('WPP_Version', $this->args['version']);
        }

        /** Loads general functions used by WP-Property */
        include_once $this->path('lib/class_functions.php', 'dir');
        /** Loads Admin Tools feature */
        include_once $this->path('lib/class_admin_tools.php', 'dir');
        /** Loads all the metaboxes for the property page */
        include_once $this->path('lib/class_core.php', 'dir');
        /** Load set of static methods for mail notifications */
        include_once $this->path('lib/class_mail.php', 'dir');
        /** Load in hooks that deal with legacy and backwards-compat issues */
        include_once $this->path('lib/class_legacy.php', 'dir');

        $upload_dir = wp_upload_dir();

        //** Init Settings */
        $this->settings = new Settings(array(
          'key' => 'wpp_settings',
          'store' => 'options',
          'data' => array(
            'name' => $this->name,
            'version' => $this->args['version'],
            'domain' => $this->domain,
          )
        ));

        // Register Product with SaaS Services.
        if( class_exists( 'UsabilityDynamics\SAAS_UTIL\Register' ) && $this->get_schema( "extra.saasProduct", false ) ) {
          Register::product( $this->get_schema( "extra.saasProduct" ), array(
            "name" => $this->name,
            "slug" => $this->slug,
            "version" => $this->args[ "version" ],
            "type" => "plugin",
            // Commented for now since it may contain too big data
            //"wpp_settings" => $this->settings->get()
          ) );
        }

        //** Initiate Attributes Handler */
        new Attributes();

        //** Initiate AJAX Handler */
        new Ajax();

        //** Handles Export (XML/JSON/CSV) functionality */
        new Export();

        /** Initiate WPML class if WPML plugin activated. **/
        if (function_exists('icl_object_id')) {
          new \UsabilityDynamics\WPP\WPML();
        }

        //** Initiate Admin UI */
        if (is_admin()) {
          //** Initiate Admin Handler */
          new Admin();



          //** Setup Gallery Meta Box ( wp-gallery-metabox ) */
          add_action('be_gallery_metabox_post_types', function ($post_types = array()) {
            return array('property');
          });

          add_filter('be_gallery_metabox_remove', '__return_false');
        }

        /**
         * Load WP List Table library.
         */
        new \UsabilityDynamics\WPLT\Bootstrap();

        /**
         * May be load Shortcodes
         */
        add_action('init', function () {
          ud_get_wp_property()->load_files(ud_get_wp_property()->path('lib/shortcodes', 'dir'));
        }, 999);

        /**
         * May be load Widgets
         */
        add_action('widgets_init', function () {
          ud_get_wp_property()->load_files(ud_get_wp_property()->path('lib/widgets', 'dir'));
        }, 1);

        /** Legacy filters and hooks */
        include_once $this->path('lib/default_api.php', 'dir');

        /**
         * Initiate the plugin
         */
        $this->core = new WPP_Core();

        /**
         * Flush WP-Property cache
         */
        if (get_transient('wpp_cache_flush')) {
          delete_transient('wpp_cache_flush');
        }

        // Handle forced pre-release update checks.
        add_filter('site_transient_update_plugins', array('UsabilityDynamics\WPP\Bootstrap', 'update_check_handler'), 10, 2);
        add_filter('upgrader_process_complete', array('UsabilityDynamics\WPP\Bootstrap', 'upgrader_process_complete'), 10, 2);

        // New layout feature. Feature flag must be enabled.
        if ( WP_PROPERTY_LAYOUTS ) {
          $this->layouts_settings = new Layouts_Settings();
          $this->layouts = new Layouts();

          //** WP Property Customizer */
          $this->property_customizer = new WP_Property_Customizer();
        }

      }

      /**
       * Includes all PHP files from specific folder
       *
       * @param string $dir Directory's path
       * @author peshkov@UD
       */
      public function load_files($dir = '')
      {
        $dir = trailingslashit($dir);
        if (!empty($dir) && is_dir($dir)) {
          if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
              if (!in_array($file, array('.', '..')) && is_file($dir . $file) && 'php' == pathinfo($dir . $file, PATHINFO_EXTENSION)) {
                include_once($dir . $file);
              }
            }
            closedir($dh);
          }
        }
      }

      /**
       * Return localization's list.
       *
       * @author peshkov@UD
       * @return array
       */
      public function get_localization()
      {
        return apply_filters('wpp::get_localization', array(
          'licenses_menu_title' => __('Add-ons', $this->domain),
          'licenses_page_title' => __('WP-Property Add-ons Manager', $this->domain),
        ));
      }

      /**
       * Plugin Activation
       *
       */
      public function activate()
      {
        //** flush Object Cache */
        wp_cache_flush();
        //** set transient to flush WP-Property cache */
        set_transient('wpp_cache_flush', time());
        /* Deactivate wp-property-terms */
        deactivate_plugins('wp-property-terms/wp-property-terms.php', true);
        // To run activation task after plugin fully activated.
        add_option('wpp_activated', true);
      }

      /**
       * Plugin Deactivation
       *
       */
      public function deactivate()
      {
        //** flush Object Cache */
        wp_cache_flush();
      }

      /**
       * Run Install Process.
       *
       * @param string $old_version Old version.
       * @author peshkov@UD
       */
      public function run_install_process()
      {
        /* Compatibility with WP-Property 1.42.4 and less versions */
        $old_version = get_option('wpp_version');
        if ($old_version) {
          $this->run_upgrade_process();
        }
      }

      /**
       * Run Upgrade Process:
       * - do WP-Property settings backup.
       *
       * @author peshkov@UD
       */
      public function run_upgrade_process()
      {
        Upgrade::run($this->old_version, $this->args['version']);
      }

      /**
       * Check API for pre-release updates.
       *
       * @author potanin@UD
       * @return array|mixed
       */
      static public function get_update_check_result()
      {

        if (get_site_transient('wpp_product_updates') && !isset($_GET['force-check'])) {

          $_transient = get_site_transient('wpp_product_updates');

          if (is_array($_transient)) {
            return $_transient;
          }

        }

        $_products = array('wp-property' => 'wp-property/wp-property.php');

        foreach ($_products as $_product_name => $_product_path) {

          try {

            // Must be able to parse composer.json from plugin file, hopefully to detect the "_build.sha" field.
            $_composer = json_decode(@file_get_contents(trailingslashit(WPP_Path) . '/composer.json'));

            if (is_object($_composer) && $_composer->extra && isset($_composer->extra->_build) && !isset($_composer->extra->_build->sha)) {

              continue;
            }

            if (is_object($_composer) && $_composer->extra && isset($_composer->extra->_build) && isset($_composer->extra->_build->sha)) {
              $_version = $_composer->extra->_build->sha;
            } else {
              $_version = null;
            }

            $_detail[$_product_name] = array(
              'request_url' => 'https://api.usabilitydynamics.com/v1/product/updates/' . $_product_name . '/latest/?version=' . (isset($_version) ? $_version : ''),
              'product_path' => $_product_path,
              'response' => null,
              'have_update' => isset($_composer->extra->_build->sha) ? null : false
            );


            $_response = wp_remote_get($_detail[$_product_name]['request_url']);

            if (wp_remote_retrieve_response_code($_response) === 200) {
              $_body = wp_remote_retrieve_body($_response);
              $_body = json_decode($_body);

              if (isset($_body->data)) {
                $_detail[$_product_name]['response'] = $_body->data;

                if (!$_body->data->changesSince || $_body->data->changesSince > 0) {
                  $_detail[$_product_name]['have_update'] = true;
                }

              } else {
                $_detail[$_product_name]['response'] = null;
                $_detail[$_product_name]['have_update'] = false;
              }
            }

          } catch (\Exception $e) {
          }

        }

        if (isset($_detail)) {
          $_transient_result = set_site_transient('wpp_product_updates', array('data' => $_detail, 'cached' => true), 3600);
        }

        return array('data' => isset($_detail) ? $_detail : null, 'cached' => false, 'transient_result' => isset($_transient_result) ? $_transient_result : 0);

      }

      /**
       * @param $response
       * @param null $old_value
       */
      static public function upgrader_process_complete($response, $old_value = null)
      {
        delete_site_transient('wpp_product_updates');
      }

      /**
       * Check pre-release updates.
       *
       * @todo Refine the "when-to-check" logic. Right now multple requests may be made per request.
       *
       * @author potanin@UD
       *
       * @param $response
       * @param $old_value
       *
       * @return mixed
       */
      static public function update_check_handler($response, $old_value = null)
      {
        global $wp_properties;

        if (!$response || !isset($response->response) || !is_array($response->response) || !isset($wp_properties) || !isset($wp_properties['configuration']['pre_release_update'])) {
          return $response;
        }

        // If pre-release update checks are disabled, do nothing.
        if ($wp_properties['configuration']['pre_release_update'] !== 'true') {
          return $response;
        }

        $_ud_get_product_updates = self::get_update_check_result();

        foreach ((array)$_ud_get_product_updates['data'] as $_product_short_name => $_product_detail) {
          if ($_product_detail['have_update']) {
            $response->response[$_product_detail['product_path']] = $_product_detail['response'];
          }

        }
//        die( '<pre>' . print_r( $response, true ) . '</pre>' );

        return $response;

      }

      /**
       * Set Feature Flag constants by parsing composer.json
       *
       * @todo Make sure settings from DB can override these.
       *
       * @author potanin@UD
       * @return array|mixed|null|object
       */
      public function parse_feature_flags()
      {
        try {
          $_raw = file_get_contents(wp_normalize_path($this->root_path) . 'composer.json');
          $_parsed = json_decode($_raw);
          $legacy = get_option('wpp_legacy_2_2_0_2', false);
          // @todo Catch poorly formatted JSON.
          if (!is_object($_parsed)) {
            // throw new Error( "unable to parse."  );
          }

          if (isset($_parsed) && isset($_parsed->extra) && isset($_parsed->extra->featureFlags)) {
            foreach ((array)$_parsed->extra->featureFlags as $_feature) {
              if (!defined($_feature->constant)) {
                if($legacy){
                  $_feature->enabled = isset($_feature->enable_on_old_install)? $_feature->enable_on_old_install: false;
                }
                define($_feature->constant, $_feature->enabled);
              }
            }
          }

        } catch (Exception $e) {
          echo 'Caught exception: ', $e->getMessage(), "\n";
        }
        return isset($_parsed) ? $_parsed : null;
      }

      /**
       * Gets declared Feature Flags from composer.json
       * 
       * @return array
       */
      public function get_feature_flags()
      {
        try {
          $legacy = get_option('wpp_legacy_2_2_0_2', false);
          $_raw = file_get_contents(wp_normalize_path($this->root_path) . 'composer.json');
          $_parsed = json_decode($_raw);

          if (!is_object($_parsed)) {
            return array();
          }

          if (isset($_parsed) && isset($_parsed->extra) && isset($_parsed->extra->featureFlags)) {
            if($legacy){
              foreach ((array)$_parsed->extra->featureFlags as $key => $_feature) {
                $_parsed->extra->featureFlags[$key]->enabled = isset($_feature->enable_on_old_install)? $_feature->enable_on_old_install: false;
              }
            }
            return (array)$_parsed->extra->featureFlags;
          }

        } catch (Exception $e) {
          return array();
        }

        return array();

      }

    }

  }

}
