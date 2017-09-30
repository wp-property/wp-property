<?php

namespace UsabilityDynamics\WPP {

  use UsabilityDynamics\WPP\Layouts;

  if( !class_exists( 'UsabilityDynamics\WPP\Layouts_Settings' ) ) {

    /**
     * Class Layouts_Builder
     * @package UsabilityDynamics\WPP
     */
    class Layouts_Settings extends Scaffold {

      /**
       * @var
       */
      private $api_client;

      /**
       * Layouts_Settings constructor.
       */
      public function __construct() {
        parent::__construct();

        /**
         *
         */
        $this->api_client = new Layouts_API_Client(array(
          'url' => defined('UD_API_LAYOUTS_URL') ? UD_API_LAYOUTS_URL : 'https://api.usabilitydynamics.com/product/property/layouts/v1'
        ));

        /**
         *
         */
        add_filter( 'wpp::layouts::template_files', array( $this, 'filter_template_files' ) );
      }

      /**
       * @param $files
       * @return mixed
       */
      public function filter_template_files( $files ) {

        $unwanted = array(
          '404.php',
          'author.php',
          'sidebar.php',
          'comments.php',
          'footer.php',
          'functions.php',
          'header.php',
          'search.php',
          'searchform.php'
        );

        foreach( $unwanted as $file ) {
          unset( $files[$file] );
        }

        foreach( $files as $file => $path ) {
          if( preg_match( "/sidebar/", $file ) ) {
            unset( $files[$file] );
          }
        }

        return $files;
      }
    }
  }
}
