<?php
/**
 * Class Module
 *
 */
namespace UsabilityDynamics\WPP {

  include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

  if( !class_exists( 'UsabilityDynamics\WPP\Upgrader_Skin' ) ) {

    /**
     * Silent SKin
     *
     * @package WordPress
     * @subpackage Upgrader_Skin
     */
    class Upgrader_Skin extends \WP_Upgrader_Skin {

      var $options = array();

      function __construct( $args = array() ) {

        parent::__construct( array(
          'title' => __( 'Update Module' ),
        ));

      }

      function request_filesystem_credentials( $error = null ) {
        include_once( ABSPATH . 'wp-admin/includes/file.php' );
        return parent::request_filesystem_credentials( $error );
      }

      function header() {}

      function footer() {}

      function error( $error ) {}

      function feedback( $string ) {}

      function before() {}

      function after() {}

    }

  }

}