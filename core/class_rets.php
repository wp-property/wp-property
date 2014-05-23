<?php

if( !class_exists( 'phRETS' ) ) {
  include_once( dirname( dirname( __FILE__ ) ) . '/third-party/phrets.php' );
}

if( !class_exists( 'WPP_RETS' ) ) {
  /**
   * Just a wrapper for phRETS
   * can be used for data modifying.
   */
  class WPP_RETS extends phRETS {

  }
}
