<?php
/**
 * Wrapper for Widget class
 *
 * @author potanin@UD
 * @author peshkov@UD
 * @author korotkov@UD
 *
 * @version 0.1.0
 * @package UsabilityDynamics
 * @subpackage WPP
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Widget' ) ) {

    class Widget extends \WP_Widget {
    
      public function __construct( $id_base, $name, $widget_options = array(), $control_options = array() ) {
        parent::__construct( $id_base, $name, $widget_options, $control_options );
      }

    }

  }

}