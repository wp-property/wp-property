<?php
/**
 * Bootstrap
 *
 * @since 3.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\PDF_Bootstrap' ) ) {

    final class PDF_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPP\PDF_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {
        require_once( dirname( __DIR__ ) . '/class-wpp-pdf-flyer.php' );
        add_action( 'wpp_init', array( 'class_wpp_pdf_flyer', 'pre_init' ), 0 );
        add_action( 'wpp_init', array( 'class_wpp_pdf_flyer', 'init' ), 10 );
        /* Any front-end Functions */
        add_action( 'template_redirect', array( 'class_wpp_pdf_flyer', 'template_redirect' ) );
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
