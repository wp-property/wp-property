<?php
/**
 * Backwards Compatibility
 *
 * Support for legacy UD Classes - extend WPP_F, which in turn extends UD_API
 *
 */

if( !class_exists( 'WPP_Core' ) ) {
  class WPP_Core extends \UsabilityDynamics\WPP\Bootstrap {}
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

