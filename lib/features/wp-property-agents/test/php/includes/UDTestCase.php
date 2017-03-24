<?php
/**
 * 
 * @class UD_Plugin_WP_UnitTestCase
 */
class UD_Plugin_WP_UnitTestCase extends WP_UnitTestCase {

  protected $root_dir;
  
  protected $instance;

  /**
   * WP Test Framework Constructor
   */
  function setUp() {
	  parent::setUp();
    $this->root_dir = dirname( dirname( dirname( __DIR__ ) ) );
    if( file_exists( $this->root_dir . '/wp-property-agents.php' ) ) {
      include_once( $this->root_dir . '/wp-property-agents.php' );
    }
    if( !class_exists( '\UsabilityDynamics\WPP\Agents_Bootstrap' ) ) {
      $this->fail( 'Plugin is not available.' );
    }
    $this->instance = \UsabilityDynamics\WPP\Agents_Bootstrap::get_instance();
  }
  
  /**
   * WP Test Framework Destructor
   */
  function tearDown() {
	  parent::tearDown();
    $this->root_dir = NULL;
    $this->instance = NULL;
  }
  
}
