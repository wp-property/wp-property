<?php

@include_once 'wpp_tcpdf.php';

/*
 * Class for PDF Flyer Generation
 *
 * @author potanin@UD
 */
class WPP_PDF_Flyer extends WPP_TCPDF {

  /*
   * Override to avoid fatal errors when errors occur.
   *
   * @author potanin@UD
   */
  public function Error($msg) {
    $this->wpp_error_log[] = $msg;
  }

}
?>
