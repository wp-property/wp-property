<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Child_Properties_Field' ) && class_exists( 'RWMB_Field' ) ) {
  class RWMB_Wpp_Child_Properties_Field extends RWMB_Field {

    /**
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field ) {
      ob_start();

      $list_table = new UsabilityDynamics\WPP\Children_List_Table( array(
        'name' => 'wpp_edit_property_page',
        'per_page' => 10,
      ) );

      $list_table->prepare_items();
      $list_table->display();

      return ob_get_clean();
    }

  }
}
