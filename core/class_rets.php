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
    
    /**
     * Constructor
     */
    public function __construct() {
      parent::phRETS();
      /** Probably remove old cookie files. Runs once per 7 days. */
      $this->maybe_flush_tempdir();
    }
    
    /**
     * Maybe remove old phrets cookie files from temp dir
     *
     * @author peshkov@UD
     */
    public function maybe_flush_tempdir() {
      $time = get_option( 'phrets_flush_tempdir' );
      if( !$time || time() - $time >= 604800 ) {
        $tempdir = defined( 'WPP_XMLI_COOKIE_DIR' ) ? untrailingslashit( WPP_XMLI_COOKIE_DIR ) : sys_get_temp_dir();
        if ( !empty( $tempdir ) && is_dir( $tempdir ) ) {
          if ( $dh = opendir( $tempdir ) ) {
            while ( ( $file = readdir( $dh ) ) !== false ) {
              /** Be sure that removing file is phrets cookie file and has been created more then 2 days ago. */
              if( !in_array( $file, array( '.', '..' ) ) && 
                  is_file( $tempdir . '/' . $file ) && 
                  strpos( $file, 'phr' ) === 0 && 
                  'tmp' == pathinfo( $tempdir . '/' . $file, PATHINFO_EXTENSION ) &&
                  time() - filemtime( $tempdir . '/' . $file ) > 172800
                ) {
                @unlink( $tempdir . '/' . $file );
              }
            }
            closedir( $dh );
          }
        }
      }
      update_option( 'phrets_flush_tempdir', time() );
    }
    
  }
}
