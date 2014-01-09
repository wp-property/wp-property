<?php
/**
 * Backwards Compatibility
 *
 * Support for legacy UD Classes - extend WPP_F, which in turn extends UD_API
 *
 */

add_action( 'wpp:init:post', function( $context ) {
  do_action( 'wpp_post_init', $context );
});

add_action( 'wpp:init:pre', function( $context ) {
  do_action( 'wpp_pre_init', $context );
});

add_action( 'wpp:init', function( $context ) {
  do_action( 'wpp_init', $context );
});

add_action( 'wpp:metaboxes', function( $context ) {
  do_action( 'wpp_metaboxes', $context  );
});

add_action( 'wpp:save_property', function( $id, $context ) {
  do_action( 'save_property',  $id, $context  );
});

if( !class_exists( 'WPP_Core' ) ) {
  final class WPP_Core extends \UsabilityDynamics\WPP\Bootstrap {}
}

if( !class_exists( 'WPP_F' ) ) {
  class WPP_F extends \UsabilityDynamics\WPP\Utility {}
}

if( !class_exists( 'WPP_Legacy' ) ) {
  class WPP_Legacy extends \UsabilityDynamics\WPP\Utility {}
}

if( !class_exists( 'WPP_UD_F' ) ) {
  class WPP_UD_F extends \UsabilityDynamics\WPP\Utility {}
}

if( !class_exists( 'WPP_UD_UI' ) ) {
  class WPP_UD_UI extends \UsabilityDynamics\WPP\Utility {}
}

if( !class_exists( 'UD_UI' ) ) {
  class UD_UI extends \UsabilityDynamics\WPP\Utility {}
}

if( !class_exists( 'UD_F' ) ) {
  class UD_F extends \UsabilityDynamics\WPP\Utility {}
}

