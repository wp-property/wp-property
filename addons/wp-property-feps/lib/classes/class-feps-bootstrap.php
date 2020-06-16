<?php
/**
 * Bootstrap
 *
 * @since 3.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\FEPS_Bootstrap' ) ) {

    final class FEPS_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type \UsabilityDynamics\WPP\FEPS_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {
        require_once( dirname( __DIR__ ) . '/class-wpp-feps.php' );
        add_action( 'wpp_pre_init', array( 'class_wpp_feps', 'wpp_pre_init' ) );
        add_action( 'wpp_post_init', array( 'class_wpp_feps', 'wpp_post_init' ) );
        add_action( 'widgets_init', function() { register_widget("MyFepsWidget"); } );
        add_action( 'widgets_init', function() { register_widget("MyFepsInfoWidget"); } );
      }

      /**
       * Plugin Activation
       *
       */
      public function activate() {
        //** flush Object Cache */
        wp_cache_flush();
        //** set transient to flush WP-Property cache */
        set_transient( 'wpp_cache_flush', time() );
      }

      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {
        //** flush Object Cache */
        wp_cache_flush();
      }

      /**
       * Determine if Utility class contains missed method
       * in other case, just return NULL to prevent ERRORS
       *
       * @author peshkov@UD
       */
      public function __call( $name, $arguments ) {
        if ( method_exists( '\UsabilityDynamics\WPP\FEPS_Utility', $name )) {
          return call_user_func_array(array('\UsabilityDynamics\WPP\FEPS_Utility', $name), $arguments);
        } else {
          return NULL;
        }
      }

    }

  }

}
