<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Alias_Field' ) && class_exists( 'RWMB_Text_Field' ) ) {
  class RWMB_Wpp_Alias_Field extends RWMB_Text_Field {

    /**
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field ) {
      global $post;

      $targets = ud_get_wp_property()->alias->get_alias_map( $field[ 'id' ] );
      $targets = explode( ',', $targets );
      foreach( $targets as $target ) {
        $target = trim( $target );
        $value = ud_get_wp_property()->alias->get_alias_value( $target );

      }


      ob_start();
      echo "<pre>";
      print_r($targets);
      echo "</pre>";
      $html = ob_get_clean();

      // fix "array" situation by only showing the first value
      if( is_array( $meta ) ) {
        $meta = array_shift(array_values($meta));
      }

      return $html . ( sprintf(
        '<input type="text" data-field-type="wpp-readonly" readonly="readonly" class="rwmb-text" id="%s" value="%s" placeholder="%s" size="%s" %s>',
        // $field['field_name'],
        $field[ 'id' ],
        $meta,
        $field[ 'placeholder' ],
        $field[ 'size' ],
        $field[ 'datalist' ] ? "list='{$field['datalist']['id']}'" : ''
      ) );
    }

  }
}
