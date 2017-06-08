<?php
/**
 * Be sure plugin's core is loaded.
 *
 * @class CoreTest
 */
class CoreTest extends UD_Plugin_WP_UnitTestCase {

  /**
   * 
   * @group core
   */
  function testGetInstance() {
    $this->assertTrue( function_exists( 'ud_get_wpp_resp_slideshow' ) );
    $data = ud_get_wpp_resp_slideshow();
    $this->assertTrue( is_object( $data ) && get_class( $data ) == 'UsabilityDynamics\WPP\Resp_Slideshow_Bootstrap' );
  }
  
  /**
   *
   * @group core
   */
  function testInstance() {
    $this->assertTrue( is_object( $this->instance ) );
  }
  
}
