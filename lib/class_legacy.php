<?php
/**
 * Legacy Support
 *
 * This file deals with upgrading and backwards compatability issues.
 *
 * @package WP-Property
 */
//** Support for legacy UD Classes - extend WPP_F, which in turn extends UD_API */
if ( !class_exists( 'WPP_UD_F' ) && class_exists( 'WPP_F' ) ) {
  class WPP_UD_F extends WPP_F {
  }
}
if ( !class_exists( 'WPP_UD_UI' ) ) {
  class WPP_UD_UI extends WPP_F {
  }
}
if ( !class_exists( 'UD_UI' ) ) {
  class UD_UI extends WPP_F {
  }
}
if ( !class_exists( 'UD_F' ) ) {
  class UD_F extends WPP_F {
  }
}