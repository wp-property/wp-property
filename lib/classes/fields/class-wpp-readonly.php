<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Readonly_Field' ) )
{
  class RWMB_Wpp_Readonly_Field extends RWMB_Text_Field
  {

    /**
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field )
    {
      return sprintf(
        '<input type="text" readonly="readonly" class="rwmb-text" id="%s" value="%s" placeholder="%s" size="%s" %s>%s',
        // $field['field_name'],
        $field['id'],
        $meta,
        $field['placeholder'],
        $field['size'],
        $field['datalist'] ? "list='{$field['datalist']['id']}'" : '',
        self::datalist_html( $field )
      );
    }

  }
}
