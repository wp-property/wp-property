<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Aggregated_Field' ) && class_exists( 'RWMB_Wpp_Readonly_Field' ) ) {

  class RWMB_Wpp_Aggregated_Field extends RWMB_Wpp_Readonly_Field {

  }

}
