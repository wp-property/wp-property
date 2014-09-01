<?php
/**
 * Bootstrap
 *
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Bootstrap' ) ) {

    class Bootstrap {
    
      /**
       * Core version.
       *
       * @static
       * @property $version
       * @type {Object}
       */
      public $version = false;

      /**
       * Textdomain String
       *
       * @public
       * @property domain
       * @var string
       */
      public $domain = false;

      /**
       * Singleton Instance Reference.
       *
       * @private
       * @static
       * @property $instance
       * @type \UsabilityDynamics\WPP\Bootstrap object
       */
      private static $instance = null;

      /**
       * Singleton Instance Reference.
       *
       * @private
       * @static
       * @property $settings
       * @type \UsabilityDynamics\Settings object
       */
      private $settings = null;
      
      /**
       * Instantaite class.
       *
       * @todo: get rid of includes, - move to autoload. peshkov@UD
       */
      private function __construct() {
      
        $plugin_file = dirname( __DIR__ ) . '/wp-property.php';
        $plugin_data = get_file_data( $plugin_file, array(
          'Version' => 'Version',
          'TextDomain' => 'Text Domain',
        ), 'plugin' );

        $this->version  = trim( $plugin_data[ 'Version' ] );
        $this->domain   = trim( $plugin_data[ 'TextDomain' ] );
        
        /** Loads built-in plugin metadata and allows for third-party modification to hook into the filters. Has to be included here to run after template functions.php */
        include_once WPP_Path . 'action_hooks.php';
        /** Defaults filters and hooks */
        include_once WPP_Path . 'default_api.php';
        /** Loads general functions used by WP-Property */
        include_once WPP_Path . 'core/class_functions.php';
        /** Loads Admin Tools feature */
        include_once WPP_Path . 'core/class_admin_tools.php';
        /** Loads export functionality */
        include_once WPP_Path . 'core/class_property_export.php';
        /** Loads all the metaboxes for the property page */
        include_once WPP_Path . 'core/ui/class_ui.php';
        /** Loads all the metaboxes for the property page */
        include_once WPP_Path . 'core/class_core.php';
        /** Bring in the RETS library */
        include_once WPP_Path . 'core/class_rets.php';
        /** Load set of static methods for mail notifications */
        include_once WPP_Path . 'core/class_mail.php';
        /** Load in hooks that deal with legacy and backwards-compat issues */
        include_once WPP_Path . 'core/class_legacy.php';

        //** Init Settings */
        $this->settings = new Settings( array(
          'key'  => 'wpp_settings',
          'store'  => 'options',
          'data' => array(
            'version' => $this->version,
            'domain' => $this->domain,
          )
        ));
        
        //** Register activation hook */
        register_activation_hook( $plugin_file, array( 'WPP_F', 'activation' ) );

        //** Register activation hook */
        register_deactivation_hook( $plugin_file, array( 'WPP_F', 'deactivation' ) );

        //** Initiate the plugin */
        add_action( "after_setup_theme", array( $this, 'load' ) );
        
      }
      
      /**
       * Loads Plugin's functionality
       *
       * @action after_setup_theme
       * @author peshkov@UD
       */
      private function load() {
        //** */
        new WPP_Core();
      }
      
      /**
       * Determine if instance already exists and Return Instance
       *
       */
      public static function get_instance( $args = array() ) {
        if( null === self::$instance ) {
          self::$instance = new self();
        }
        return self::$instance;
      }
      
      /**
       * @param string $key
       * @param mixed $value
       *
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = null, $value = null ) {
        return $this->settings->set( $key, $value );
      }

      /**
       * @param string $key
       * @param mixed $default
       *
       * @return \UsabilityDynamics\type
       */
      public function get( $key = null, $default = null ) {
        return $this->settings->get( $key, $default );
      }

    }

  }

}
