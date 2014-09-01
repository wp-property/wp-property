<?php
/**
 * Bootstrap
 *
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Bootstrap' ) ) {

    class Bootstrap extends \UsabilityDynamics\WP\Bootstrap {
    
      /**
       * Core object
       *
       * @private
       * @static
       * @property $settings
       * @type WPP_Core object
       */
      private $core = null;
    
      /**
       * Instantaite class.
       *
       * @todo: get rid of includes, - move to autoload. peshkov@UD
       */
      public function init() {
        global $wp_properties;
      
        $plugin_file = dirname( __DIR__ ) . '/wp-property.php';
        $plugin_data = get_file_data( $plugin_file, array(
          'Version' => 'Version',
          'TextDomain' => 'Text Domain',
        ), 'plugin' );

        $this->version  = trim( $plugin_data[ 'Version' ] );
        $this->domain   = trim( $plugin_data[ 'TextDomain' ] );
        
        //** Init Settings */
        $this->settings = new Settings( array(
          'key'  => 'wpp_settings',
          'store'  => 'options',
          'data' => array(
            'version' => $this->version,
            'domain' => $this->domain,
          )
        ));
        
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

        //** Register activation hook */
        register_activation_hook( $plugin_file, array( $this, 'activate' ) );

        //** Register activation hook */
        register_deactivation_hook( $plugin_file, array( $this, 'deactivate' ) );

        //** Initiate the plugin */
        add_action( "after_setup_theme", array( $this, 'after_setup_theme' ) );
        
      }
      
      /**
       * Loads Plugin's functionality
       *
       * @action after_setup_theme
       * @author peshkov@UD
       */
      public function after_setup_theme() {
        //** */
        $this->core = new \WPP_Core();
      }
      
      /**
       * Plugin Activation
       *
       */
      public function activate() {
        global $wp_rewrite;
        //** Do close to nothing because only ran on activation, not updates, as of 3.1 */
        //** Handled by WPP_F::manual_activation(). */
        $wp_rewrite->flush_rules();
      }
      
      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
      }

    }

  }

}
