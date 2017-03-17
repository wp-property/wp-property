<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Readonly_Field' ) && class_exists( 'RWMB_Text_Field' ) ) {
  class RWMB_Wpp_Readonly_Field extends RWMB_Text_Field {

    /**
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field ) {

      // fix "array" situation by only showing the first value
      if( is_array( $meta ) ) {
        $meta = array_shift(array_values($meta));
      }

      return sprintf(
        '<input type="text" data-field-type="wpp-readonly" readonly="readonly" class="rwmb-text" id="%s" value="%s" placeholder="%s" size="%s" %s>',
        // $field['field_name'],
        $field[ 'id' ],
        $meta,
        $field[ 'placeholder' ],
        $field[ 'size' ],
        $field[ 'datalist' ] ? "list='{$field['datalist']['id']}'" : ''
      );
    }

  }
}
