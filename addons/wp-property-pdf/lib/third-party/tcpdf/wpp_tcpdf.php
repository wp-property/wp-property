<?php

@include_once 'tcpdf.php';

/**
 * Class for extending of TCPDF
 *
 * @author odokienko@UD
 */
class WPP_TCPDF extends TCPDF {

  /**
   * Draws HTML list with fonts abaliable
   * @param type $args
   * @author odokienko@UD
   * @since 1.37.6
   */
  public function getHTMLFontList($args){
    $fontpath = $this->_getfontpath();
    $fontsdir = opendir($fontpath);
    while (($file = readdir($fontsdir)) !== false) {
      $file_name = strtolower(basename($file, '.php'));
      if (substr($file, -4) == '.ttf' && !file_exists($fontsdir . $file_name)) {
        TCPDF_FONTS::addTTFfont($fontpath . $file);
      }
    }
    closedir($fontsdir);
   $this->getFontsList();

   $defaults = array(
      'name' => 'setfont',
      'selected' => 'none',
      'fontlist' => $this->fontlist,
      'fontpath' => $this->_getfontpath(),
      'blank_selection_label' => ' - '
      );

    extract( $args = wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $fontlist = array_unique($fontlist);
    natsort($fontlist);

    if(empty($id) && !empty($name)) {
      $id = $name;
    }

    ?>
      <select id="<?php echo $id ?>" name="<?php echo $name ?>" >
        <option value=""><?php echo $blank_selection_label; ?></option>
          <?php
            foreach($fontlist as $fontname) {
              //in any case $name will be owerwritten by include();
              unset($name);unset($type);
              if( $fontname === 'index' ) continue;
          ?>
            <option value='<?php echo $fontname; ?>' <?php if($selected == $fontname) echo 'selected="selected"'; ?>>
              <?php
              $font_file  = $fontpath . $fontname . '.php' ;
              if ( file_exists( $font_file ) ) {
                include( $font_file );
                echo $name . ((false !== strpos(strtolower($type),'unicode')) ? ' (Unicode)' : '');
              }else{
               echo $fontname;
              }
              ?>
            </option>
          <?php } ?>
      </select>

    <?php

  }

  /**
   * @param $file
   * @param string $x
   * @param string $y
   * @param int $w
   * @param int $h
   * @param string $type
   * @param string $link
   * @param string $align
   * @param bool $resize
   * @param int $dpi
   * @param string $palign
   * @param bool $ismask
   * @param bool $imgmask
   * @param int $border
   * @param bool $fitbox
   * @param bool $hidden
   * @param bool $fitonpage
   * @return int
   *
   * Overridden function that replaces URL with absolute paths to images. Fixed allow_url_fopen and allow_url_include issues.
   */
  public function Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false) {

    $file = preg_replace( '/http[s]?:\/\/'.$_SERVER['HTTP_HOST'].'/i', $_SERVER['DOCUMENT_ROOT'], $file );

    if ($x === '') {
      $x = $this->x;
    }
    if ($y === '') {
      $y = $this->y;
    }
    // check page for no-write regions and adapt page margins if necessary
    $this->checkPageRegions($h, $x, $y);
    $cached_file = false; // true when the file is cached
    // check if we are passing an image as file or string
    if ($file{0} === '@') { // image from string
      $imgdata = substr($file, 1);
      $file = tempnam(K_PATH_CACHE, 'img_');
      $fp = fopen($file, 'w');
      fwrite($fp, $imgdata);
      fclose($fp);
      unset($imgdata);
      $cached_file = true;
      $imsize = @getimagesize($file);
      if ($imsize === FALSE) {
        unlink($file);
        $cached_file = false;
      }
    } else { // image file
      // check if is local file
      if (!@file_exists($file)) {
        // encode spaces on filename (file is probably an URL)
        $file = str_replace(' ', '%20', $file);
      }
      // get image dimensions
      $imsize = @getimagesize($file);
      if ($imsize === FALSE) {
        if (function_exists('curl_init')) {
          // try to get remote file data using cURL
          $cs = curl_init(); // curl session
          curl_setopt($cs, CURLOPT_URL, $file);
          curl_setopt($cs, CURLOPT_BINARYTRANSFER, true);
          curl_setopt($cs, CURLOPT_FAILONERROR, true);
          curl_setopt($cs, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($cs, CURLOPT_CONNECTTIMEOUT, 5);
          curl_setopt($cs, CURLOPT_TIMEOUT, 30);
          $imgdata = curl_exec($cs);
          curl_close($cs);
          if($imgdata !== FALSE) {
            // copy image to cache
            $file = tempnam(K_PATH_CACHE, 'img_');
            $fp = fopen($file, 'w');
            fwrite($fp, $imgdata);
            fclose($fp);
            unset($imgdata);
            $cached_file = true;
            $imsize = @getimagesize($file);
            if ($imsize === FALSE) {
              unlink($file);
              $cached_file = false;
            }
          }
        } elseif (($w > 0) AND ($h > 0)) {
          // get measures from specified data
          $pw = $this->getHTMLUnitToUnits($w, 0, $this->pdfunit, true) * $this->imgscale * $this->k;
          $ph = $this->getHTMLUnitToUnits($h, 0, $this->pdfunit, true) * $this->imgscale * $this->k;
          $imsize = array($pw, $ph);
        }
      }
    }
    if ($imsize === FALSE) {
      $this->Error('[Image] Unable to get image: '.$file);
    }
    // get original image width and height in pixels
    list($pixw, $pixh) = $imsize;
    // calculate image width and height on document
    if (($w <= 0) AND ($h <= 0)) {
      // convert image size to document unit
      $w = $this->pixelsToUnits($pixw);
      $h = $this->pixelsToUnits($pixh);
    } elseif ($w <= 0) {
      $w = $h * $pixw / $pixh;
    } elseif ($h <= 0) {
      $h = $w * $pixh / $pixw;
    } elseif (($fitbox !== false) AND ($w > 0) AND ($h > 0)) {
      if (strlen($fitbox) !== 2) {
        // set default alignment
        $fitbox = '--';
      }
      // scale image dimensions proportionally to fit within the ($w, $h) box
      if ((($w * $pixh) / ($h * $pixw)) < 1) {
        // store current height
        $oldh = $h;
        // calculate new height
        $h = $w * $pixh / $pixw;
        // height difference
        $hdiff = ($oldh - $h);
        // vertical alignment
        switch (strtoupper($fitbox{1})) {
          case 'T': {
            break;
          }
          case 'M': {
            $y += ($hdiff / 2);
            break;
          }
          case 'B': {
            $y += $hdiff;
            break;
          }
        }
      } else {
        // store current width
        $oldw = $w;
        // calculate new width
        $w = $h * $pixw / $pixh;
        // width difference
        $wdiff = ($oldw - $w);
        // horizontal alignment
        switch (strtoupper($fitbox{0})) {
          case 'L': {
            if ($this->rtl) {
              $x -= $wdiff;
            }
            break;
          }
          case 'C': {
            if ($this->rtl) {
              $x -= ($wdiff / 2);
            } else {
              $x += ($wdiff / 2);
            }
            break;
          }
          case 'R': {
            if (!$this->rtl) {
              $x += $wdiff;
            }
            break;
          }
        }
      }
    }
    // fit the image on available space
    $this->fitBlock($w, $h, $x, $y, $fitonpage);
    // calculate new minimum dimensions in pixels
    $neww = round($w * $this->k * $dpi / $this->dpi);
    $newh = round($h * $this->k * $dpi / $this->dpi);
    // check if resize is necessary (resize is used only to reduce the image)
    $newsize = ($neww * $newh);
    $pixsize = ($pixw * $pixh);
    if (intval($resize) == 2) {
      $resize = true;
    } elseif ($newsize >= $pixsize) {
      $resize = false;
    }
    // check if image has been already added on document
    $newimage = true;
    if (in_array($file, $this->imagekeys)) {
      $newimage = false;
      // get existing image data
      $info = $this->getImageBuffer($file);
      // check if the newer image is larger
      $oldsize = ($info['w'] * $info['h']);
      if ((($oldsize < $newsize) AND ($resize)) OR (($oldsize < $pixsize) AND (!$resize))) {
        $newimage = true;
      }
    }
    if ($newimage) {
      //First use of image, get info
      $type = strtolower($type);
      if ($type == '') {
        $type = $this->getImageFileType($file, $imsize);
      } elseif ($type == 'jpg') {
        $type = 'jpeg';
      }
      $mqr = $this->get_mqr();
      $this->set_mqr(false);
      // Specific image handlers
      $mtd = '_parse'.$type;
      // GD image handler function
      $gdfunction = 'imagecreatefrom'.$type;
      $info = false;
      if ((method_exists($this, $mtd)) AND (!($resize AND function_exists($gdfunction)))) {
        // TCPDF image functions
        $info = $this->$mtd($file);
        if ($info == 'pngalpha') {
          return $this->ImagePngAlpha($file, $x, $y, $pixw, $pixh, $w, $h, 'PNG', $link, $align, $resize, $dpi, $palign);
        }
      }
      if (!$info) {
        if (function_exists($gdfunction)) {
          // GD library
          $img = $gdfunction($file);
          if ($resize) {
            $imgr = imagecreatetruecolor($neww, $newh);
            if (($type == 'gif') OR ($type == 'png')) {
              $imgr = $this->_setGDImageTransparency($imgr, $img);
            }
            imagecopyresampled($imgr, $img, 0, 0, 0, 0, $neww, $newh, $pixw, $pixh);
            if (($type == 'gif') OR ($type == 'png')) {
              $info = $this->_toPNG($imgr);
            } else {
              $info = $this->_toJPEG($imgr);
            }
          } else {
            if (($type == 'gif') OR ($type == 'png')) {
              $info = $this->_toPNG($img);
            } else {
              $info = $this->_toJPEG($img);
            }
          }
        } elseif (extension_loaded('imagick')) {
          // ImageMagick library
          $img = new Imagick();
          if ($type == 'SVG') {
            // get SVG file content
            $svgimg = file_get_contents($file);
            // get width and height
            $regs = array();
            if (preg_match('/<svg([^\>]*)>/si', $svgimg, $regs)) {
              $svgtag = $regs[1];
              $tmp = array();
              if (preg_match('/[\s]+width[\s]*=[\s]*"([^"]*)"/si', $svgtag, $tmp)) {
                $ow = $this->getHTMLUnitToUnits($tmp[1], 1, $this->svgunit, false);
                $owu = sprintf('%.3F', ($ow * $dpi / 72)).$this->pdfunit;
                $svgtag = preg_replace('/[\s]+width[\s]*=[\s]*"[^"]*"/si', ' width="'.$owu.'"', $svgtag, 1);
              } else {
                $ow = $w;
              }
              $tmp = array();
              if (preg_match('/[\s]+height[\s]*=[\s]*"([^"]*)"/si', $svgtag, $tmp)) {
                $oh = $this->getHTMLUnitToUnits($tmp[1], 1, $this->svgunit, false);
                $ohu = sprintf('%.3F', ($oh * $dpi / 72)).$this->pdfunit;
                $svgtag = preg_replace('/[\s]+height[\s]*=[\s]*"[^"]*"/si', ' height="'.$ohu.'"', $svgtag, 1);
              } else {
                $oh = $h;
              }
              $tmp = array();
              if (!preg_match('/[\s]+viewBox[\s]*=[\s]*"[\s]*([0-9\.]+)[\s]+([0-9\.]+)[\s]+([0-9\.]+)[\s]+([0-9\.]+)[\s]*"/si', $svgtag, $tmp)) {
                $vbw = ($ow * $this->imgscale * $this->k);
                $vbh = ($oh * $this->imgscale * $this->k);
                $vbox = sprintf(' viewBox="0 0 %.3F %.3F" ', $vbw, $vbh);
                $svgtag = $vbox.$svgtag;
              }
              $svgimg = preg_replace('/<svg([^\>]*)>/si', '<svg'.$svgtag.'>', $svgimg, 1);
            }
            $img->readImageBlob($svgimg);
          } else {
            $img->readImage($file);
          }
          if ($resize) {
            $img->resizeImage($neww, $newh, 10, 1, false);
          }
          $img->setCompressionQuality($this->jpeg_quality);
          $img->setImageFormat('jpeg');
          $tempname = tempnam(K_PATH_CACHE, 'jpg_');
          $img->writeImage($tempname);
          $info = $this->_parsejpeg($tempname);
          unlink($tempname);
          $img->destroy();
        } else {
          return;
        }
      }
      if ($info === false) {
        //If false, we cannot process image
        return;
      }
      $this->set_mqr($mqr);
      if ($ismask) {
        // force grayscale
        $info['cs'] = 'DeviceGray';
      }
      $info['i'] = $this->numimages;
      if (!in_array($file, $this->imagekeys)) {
        ++$info['i'];
      }
      if ($imgmask !== false) {
        $info['masked'] = $imgmask;
      }
      // add image to document
      $this->setImageBuffer($file, $info);
    }
    if ($cached_file) {
      // remove cached file
      unlink($file);
    }
    // set alignment
    $this->img_rb_y = $y + $h;
    // set alignment
    if ($this->rtl) {
      if ($palign == 'L') {
        $ximg = $this->lMargin;
      } elseif ($palign == 'C') {
        $ximg = ($this->w + $this->lMargin - $this->rMargin - $w) / 2;
      } elseif ($palign == 'R') {
        $ximg = $this->w - $this->rMargin - $w;
      } else {
        $ximg = $x - $w;
      }
      $this->img_rb_x = $ximg;
    } else {
      if ($palign == 'L') {
        $ximg = $this->lMargin;
      } elseif ($palign == 'C') {
        $ximg = ($this->w + $this->lMargin - $this->rMargin - $w) / 2;
      } elseif ($palign == 'R') {
        $ximg = $this->w - $this->rMargin - $w;
      } else {
        $ximg = $x;
      }
      $this->img_rb_x = $ximg + $w;
    }
    if ($ismask OR $hidden) {
      // image is not displayed
      return $info['i'];
    }
    $xkimg = $ximg * $this->k;
    $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%u Do Q', ($w * $this->k), ($h * $this->k), $xkimg, (($this->h - ($y + $h)) * $this->k), $info['i']));
    if (!empty($border)) {
      $bx = $this->x;
      $by = $this->y;
      $this->x = $ximg;
      if ($this->rtl) {
        $this->x += $w;
      }
      $this->y = $y;
      $this->Cell($w, $h, '', $border, 0, '', 0, '', 0, true);
      $this->x = $bx;
      $this->y = $by;
    }
    if ($link) {
      $this->Link($ximg, $y, $w, $h, $link, 0);
    }
    // set pointer to align the next text/objects
    switch($align) {
      case 'T': {
        $this->y = $y;
        $this->x = $this->img_rb_x;
        break;
      }
      case 'M': {
        $this->y = $y + round($h/2);
        $this->x = $this->img_rb_x;
        break;
      }
      case 'B': {
        $this->y = $this->img_rb_y;
        $this->x = $this->img_rb_x;
        break;
      }
      case 'N': {
        $this->SetY($this->img_rb_y);
        break;
      }
      default:{
        break;
      }
    }
    $this->endlinex = $this->img_rb_x;
    if ($this->inxobj) {
      // we are inside an XObject template
      $this->xobjects[$this->xobjid]['images'][] = $info['i'];
    }
    return $info['i'];
  }

}


/**
 * Renders the list of available TCPDF fonts
 *
 * @author peshkov@UD
 * @since 1.37.6
 */
function wpp_tcpdf_get_HTML_font_list( $args='' ) {
  static $pdf;
  if( !is_object( $pdf ) ) {
    $pdf = new WPP_TCPDF();
  }
  $pdf->getHTMLFontList( $args );
}
?>
