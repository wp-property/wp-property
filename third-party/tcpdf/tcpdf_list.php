<?php

include_once 'wpp_tcpdf.php';

/*
 * Class for PDF List generation
 *
 */
class TCPDF_List extends WPP_TCPDF {

  private $wpp_background;

  private $wpp_text_color;

  private $wpp_default_text_color;

  private $wpp_title;

  private $wpp_tagline;

  private $wpp_contact_info;

  public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $atts = array()) {
    // Set options for Header and Footer
    $this->wpp_background = (!empty($atts['background']) ? $atts['background'] : array(115, 119, 136) );
    $this->wpp_text_color = (!empty($atts['text_color']) ? $atts['text_color'] : array(255, 255, 255) );
    $this->wpp_default_text_color = (!empty($atts['default_text_color']) ? $atts['default_text_color'] : array(0, 0, 0) );
    $this->wpp_title = (!empty($atts['title']) ? $atts['title'] : '' );
    $this->wpp_tagline = (!empty($atts['tagline']) ? $atts['tagline'] : '' );
    $this->wpp_contact_info = (!empty($atts['contact_info']) ? $atts['contact_info'] : '' );
    $this->wpp_setfont = (!empty($atts['setfont']) ? $atts['setfont'] : 'helvetica' );

    // If it's not array, looks like it has HEX format.
    // This way, try to Convert HEX to RGB
    if(!is_array($this->wpp_background)){
      $this->wpp_background = $this->HexToRGB($this->wpp_background);
      // Check again. If it's not HEX, we set default value
      if(!is_array($this->wpp_background)){
        $this->wpp_background = array(115, 119, 136);
      }
    }

    // If it's not array, looks like it has HEX format.
    // This way, try to Convert HEX to RGB
    if(!is_array($this->wpp_text_color)){
      $this->wpp_text_color = $this->HexToRGB($this->wpp_text_color);
      // Check again. If it's not HEX, we set default value
      if(!is_array($this->wpp_text_color)){
        $this->wpp_text_color = array(255, 255, 255);
      }
    }

    // If it's not array, looks like it has HEX format.
    // This way, try to Convert HEX to RGB
    if(!is_array($this->wpp_default_text_color)){
      $this->wpp_default_text_color = $this->HexToRGB($this->wpp_default_text_color);
      // Check again. If it's not HEX, we set default value
      if(!is_array($this->wpp_default_text_color)){
        $this->wpp_default_text_color = array(0, 0, 0);
      }
    }

    parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);

    // Set default text color
    $this->SetTextColor($this->wpp_default_text_color[0], $this->wpp_default_text_color[1], $this->wpp_default_text_color[2]);
  }

/*
 * Override to avoid fatal errors when errors occur.
 *
 * @author potanin@UD
 */
  public function Error($msg) {
    $this->wpp_error_log[] = $msg;
  }


  /*
   * Page header
   *
   */
  public function Header() {
    // Set font
    $this->SetFont($this->wpp_setfont, 'B', 14);
    $this->SetTextColor($this->wpp_text_color[0], $this->wpp_text_color[1], $this->wpp_text_color[2]);
    $this->SetFillColor($this->wpp_background[0], $this->wpp_background[1], $this->wpp_background[2]);
    $this->SetCellPaddings( 5, 0, 5, 0 );
    // Title
    $this->MultiCell( $w=0,
      $h=10,
      $txt= $this->wpp_title,
      $border=0,
      $align='L',
      $fill=TRUE,
      $ln=0,
      $x=0,
      $y=0,
      $reseth=FALSE,
      $stretch=FALSE,
      $ishtml=FALSE,
      $autopadding=FALSE,
      $maxh=10,
      $valign='M',
      $fitcell=TRUE
    );

    $this->SetFontSize( 12 );
    $this->MultiCell( $w=0,
      $h=10,
      $txt= $this->wpp_tagline,
      $border=0,
      $align='R',
      $fill=TRUE,
      $ln=0,
      $x=0,
      $y=0,
      $reseth=FALSE,
      $stretch=FALSE,
      $ishtml=FALSE,
      $autopadding=FALSE,
      $maxh=10,
      $valign='M',
      $fitcell=TRUE
    );
  }

  /*
   * Page footer
   *
   */
  public function Footer() {
    $this->SetY(-7);
    // Set font
    $this->SetFont($this->wpp_setfont, 'N', 11);
    $this->SetTextColor($this->wpp_text_color[0], $this->wpp_text_color[1], $this->wpp_text_color[2]);
    $this->SetFillColor($this->wpp_background[0], $this->wpp_background[1], $this->wpp_background[2]);
    $this->SetCellPaddings( 3, 0, 0, 0 );

    $this->MultiCell( $w=0,
      $h=8,
      $txt= $this->wpp_contact_info,
      $border=0,
      $align='L',
      $fill=TRUE,
      $ln=0,
      $x=0,
      $y=$this->getPageHeight()-7,
      $reseth=FALSE,
      $stretch=FALSE,
      $ishtml=FALSE,
      $autopadding=FALSE,
      $maxh=7,
      $valign='M',
      $fitcell=TRUE
    );

    $this->MultiCell( $w=0,
      $h=8,
      $txt='Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(),
      $border=0,
      $align='R',
      $fill=TRUE,
      $ln=0,
      $x=$this->getPageWidth()-30,
      $y=$this->getPageHeight()-7,
      $reseth=FALSE,
      $stretch=FALSE,
      $ishtml=FALSE,
      $autopadding=TRUE,
      $maxh=7,
      $valign='M',
      $fitcell=TRUE
    );
  }

  /*
   * Convert HEX to RGB
   *
   */
  public function HexToRGB($hex) {
    $hex = str_replace("#", "", $hex);
    $color = array();

    if(strlen($hex) == 3) {
      $color[0] = hexdec(substr($hex, 0, 1) . $r);
      $color[1] = hexdec(substr($hex, 1, 1) . $g);
      $color[2] = hexdec(substr($hex, 2, 1) . $b);
    }

    else if(strlen($hex) == 6) {
      $color[0] = hexdec(substr($hex, 0, 2));
      $color[1] = hexdec(substr($hex, 2, 2));
      $color[2] = hexdec(substr($hex, 4, 2));
    }

    return $color;
  }

}
?>
