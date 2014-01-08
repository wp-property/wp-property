<?php
/**
 * WP-Property Mail Notifications
 * Contains set of static methods for notifications
 *
 * @version 1.0
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 1.38
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Meta_Box' ) ) {

    if( !defined( 'RWMB_URL' ) ) {
      define( 'RWMB_URL', WPP_URL . 'vendor/wordpress/meta-box/' );
      define( 'RWMB_DIR', WPP_PATH . 'vendor/wordpress/meta-box/' );
      define( 'RWMB_VER', '4.3.4' );
      define( 'RWMB_JS_URL', trailingslashit( RWMB_URL . 'js' ) );
      define( 'RWMB_CSS_URL', trailingslashit( RWMB_URL . 'css' ) );
      define( 'RWMB_INC_DIR', trailingslashit( RWMB_DIR . 'inc' ) );
      define( 'RWMB_FIELDS_DIR', trailingslashit( RWMB_INC_DIR . 'fields' ) );
    }

    class Meta_Box extends \RW_Meta_Box {}

  }

}



