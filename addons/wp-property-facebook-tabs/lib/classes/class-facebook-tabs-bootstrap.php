<?php
/**
 * Bootstrap
 *
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Facebook_Tabs_Bootstrap' ) ) {

    final class Facebook_Tabs_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPP\Facebook_Tabs_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {
        require_once( dirname( __DIR__ ) . '/class-wpp-facebook-tabs.php' );
        add_action( 'wpp_init', array( 'class_wpp_facebook_tabs', 'wpp_pre_init' ), 0 );
        add_action( 'wpp_init', array( 'class_wpp_facebook_tabs', 'wpp_init' ), 10 );
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
