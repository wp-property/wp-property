<?php
/**
 * Bootstrap
 *
 * @since 4.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Supermap_Bootstrap' ) ) {

    final class Supermap_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPP\Supermap_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {
        require_once( dirname( __DIR__ ) . '/class-wpp-supermap.php' );
        add_action( 'wpp_init', array( 'class_wpp_supermap', 'pre_init' ), 0 );
        add_action( 'wpp_init', array( 'class_wpp_supermap', 'init' ), 10 );

        // WP-Property Setup Assistant
        add_action( 'wpp::setup_assistant::view', function() {
          include $this->path( 'static/views/admin/setup.php', 'dir' );
        } );

      }
      
      /**
       * Plugin Activation
       *
       */
      public function activate() {}
      
      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {}

      /**
       * Run Upgrade Process.
       *
       */
      public function run_upgrade_process() {
        Supermap_Upgrade::run($this->old_version, $this->args['version']);
      }

      /**
       * Determine if Utility class contains missed function
       * in other case, just return NULL to prevent ERRORS
       *
       * @author peshkov@UD
       * @param $name
       * @param $arguments
       * @return mixed|null
       */
      public function __call($name, $arguments) {
        if (is_callable(array("\\UsabilityDynamics\\WPP\\Supermap_Utility", $name))) {
          return call_user_func_array(array("\\UsabilityDynamics\\WPP\\Supermap_Utility", $name), $arguments);
        } else {
          return NULL;
        }
      }

    }

  }

}
