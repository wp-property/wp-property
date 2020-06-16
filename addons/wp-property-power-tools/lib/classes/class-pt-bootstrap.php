<?php
/**
 * Bootstrap
 *
 * @since 1.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\PT_Bootstrap' ) ) {

    final class PT_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPP\PT_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {
        require_once( dirname( __DIR__ ) . '/class-wpp-power-tools.php' );
        add_action( 'wpp_init', array( 'class_wpp_power_tools', 'pre_init' ), 0 );
        add_action( 'wpp_init', array( 'class_wpp_power_tools', 'init' ), 10 );
        add_action( 'admin_menu', array( 'class_wpp_power_tools', 'admin_menu' ) );
        add_filter( 'wpp_taxonomies', array( 'class_wpp_power_tools', 'wpp_taxonomies' ) );
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

    }

  }

}
