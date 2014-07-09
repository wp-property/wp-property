<?php
/**
 * Legacy Support
 *
 * This file deals with upgrading and backwards compatability issues.
 *
 * @package WP-Property
 */
class WPP_Legacy {

  /**
   * Adds compatibility with legacy functionality on WP-Property upgrade
   *
   */
  static function upgrade() {
    global $wpdb;

    $installed_ver = get_option( "wpp_version", 0 );
    $wpp_version = WPP_Version;

    if ( @version_compare( $installed_ver, WPP_Version ) == '-1' ) {

      switch ( $installed_ver ) {

        /**
         * Upgrade:
         * - WPP postmeta data were saved to database with '&ndash;' instead of '-' in value. Function encode_sql_input was modified and it doesn't change '-' to '&ndash' anymore
         * So to prevent search result issues we need to update database data.
         * peshkov@UD
         */
        case ( version_compare( $installed_ver, '1.37.4' ) == '-1' ):

          $wpdb->query( "UPDATE {$wpdb->prefix}postmeta SET meta_value = REPLACE( meta_value, '&ndash;', '-')" );

          break;

      }

    }

  }

}

//** Support for legacy UD Classes - extend WPP_F, which in turn extends UD_API */
if ( !class_exists( 'WPP_UD_F' ) ) {
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
