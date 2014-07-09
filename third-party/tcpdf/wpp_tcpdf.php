<?php

@include_once 'tcpdf.php';

/**
 * Class for extending of TCPDF
 *
 * @author odokienko@UD
 */
class WPP_TCPDF extends TCPDF {

  var $cdn_url = 'http://ud-cdn.com/';
  var $font_extentions = array('.z', '.ctg.z');

  public function __construct( $orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false ) {

    $uploads_fontsdir_name = self::_getfontpath();
    if ( !is_dir( $uploads_fontsdir_name ) ){
      @mkdir( $uploads_fontsdir_name, 0777, true );
      file_put_contents( $uploads_fontsdir_name . '/index.php', '', FILE_APPEND );
      if ( is_dir( K_PATH_MAIN . 'fonts/' ) ) {
        $base_fontsdir = opendir(K_PATH_MAIN.'fonts/');
        while ( ( $file = readdir( $base_fontsdir ) ) !== false ) {
          if ( $file == '.' || $file == '..' || file_exists( $uploads_fontsdir_name . '/' . $file ) ) continue;
          copy( K_PATH_MAIN.'fonts/' . $file, $uploads_fontsdir_name . '/' . $file);
        }
        closedir( $base_fontsdir );
      }
    }

    parent::__construct( $orientation, $unit, $format, $unicode, $encoding, $diskcache );
  }


  /**
   * Draws HTML list with fonts abaliable
   * @param type $args
   * @author odokienko@UD
   * @since 1.37.6
   */
  public function getHTMLFontList($args){

   $defaults = array(
      'name' => 'setfont',
      'selected' => 'none',
      'fontlist' => $this->getFontsList(),
      'fontpath' => $this->_getfontpath(),
      'blank_selection_label' => ' - '
      );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    array_unique($fontlist);
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
   * Partially TCPDF fonts data are stored on CDN
   * This function gets this data and puts it to font folder
   * @param type $font
   * @author odokienko@UD
   * @since 1.37.6
   * @return type
   */
  protected function retrieve_cdn_font($font) {

    $cdn_fonts_url = $this->cdn_url . 'assets/tcpdf/5.9.047/fonts/';

    if (file_exists( $this->_getfontpath() . $font . '.ctg.z') ) return;

    foreach ($this->font_extentions as $ext){
      $cdn_fonts_file = $cdn_fonts_url . $font . $ext;

      $font_request = wp_remote_get( preg_replace('~\s~','%20', $cdn_fonts_file), array( 'timeout' => apply_filters('wpp_pf_wp_remote_timeout', 10 ) ) );

      if( is_wp_error( $font_request ) || empty( $font_request['body'] ) || $font_request['response']['code']=='404' ) {
        continue;
      }

      //** Save the font to disk */
      file_put_contents( $this->_getfontpath() . $font . $ext, $font_request['body'] );
      array_push($this->fontlist, strtolower($font));

    }
  }


  /**
	 * Overloaded TCPDF function to give us ability inject function retrieve_cdn_font();
	 * @see TCPDF::getFontBuffer()
	 * @author odokienko@UD
   * @since 1.37.6
	 */
	protected function getFontBuffer($font) {

    if ($this->diskcache AND isset($this->fonts[$font])) {
			return unserialize($this->readDiskCache($this->fonts[$font]));
		} elseif (isset($this->fonts[$font])) {
			return $this->fonts[$font];
		}else{
      $this->retrieve_cdn_font($font);
    }

		return false;
	}

  /**
   * Scans K_PATH_MAIN.'fonts/' folder and moves all files to $this->_getfontpath() folder
   * Added ablity to use TTF fonts (functionality taken from newer version of TCPDF)
   * @uses TCPDF_FONTS::addTTFfont()
   * @uses wp_cache_get()
   * @uses wp_cache_set()
   * @return type
   * @author odokienko@UD
   * @since 1.37.6
   */
  protected function getFontsList() {

    $fontlist = wp_cache_get( 'fontlist', 'wpp_pdf_data' );

    if (!empty($fontlist)){
      $this->fontlist = $fontlist;
      return $fontlist;
    }

    $uploads_fontsdir = opendir( $this->_getfontpath() );

    while (($file = readdir($uploads_fontsdir)) !== false) {
      if (strtolower(substr($file, -4)) == '.ttf'){
        include_once('tcpdf_fonts.php');
        include_once('tcpdf_static.php');
        $fontname = TCPDF_FONTS::addTTFfont( $uploads_fontsdir_name . '/' . $file, '', '', 96, $uploads_fontsdir_name );
        if ($fontname){
          array_push($this->fontlist, $fontname);
          unlink($uploads_fontsdir_name . '/' . $file);
        }
      }
      if (substr($file, -4) == '.php') {
        array_push($this->fontlist, strtolower(basename($file, '.php')));
      }
    }

    wp_cache_add( 'fontlist', $this->fontlist, 'wpp_pdf_data' );

    return $this->fontlist;
  }

  /*
   * Override to avoid fatal errors when errors occur.
   *
   * @author potanin@UD
   */
  public function Error($msg) {
    $this->wpp_error_log[] = $msg;
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
