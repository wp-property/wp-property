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

      $alias_values = array();
      $list = false;
      $targets = ud_get_wp_property()->alias->get_alias_map( $field[ 'id' ] );
      $_targets = explode( ',', $targets );
      foreach( $_targets as $target ) {
        $target = trim( $target );
        $_alias_value = ud_get_wp_property()->alias->get_alias_value( $target, $post->ID );
        if( is_array( $_alias_value ) ) {
          $list = true;
        }
        // Alias value found.
        if( $_alias_value ) {
          if( $list ) {
            $alias_values = array_merge( $alias_values, $_alias_value );
          } else {
            $alias_values[] = $_alias_value;
          }
        }
      }

      $html = '<ul style="margin:0;">';
      if( !empty( $alias_values ) ) {
        foreach( $alias_values as $value ) {
          if( strlen( $value ) > 100 ) {
            $html .= ( sprintf(
              '<li><textarea data-field-type="wpp-readonly" readonly="readonly" class="rwmb-text" cols="%s" rows="5" >%s</textarea></li>',
              $field[ 'size' ],
              $value
            ) );
          } else {
            $html .= ( sprintf(
              '<li><input type="text" data-field-type="wpp-readonly" readonly="readonly" class="rwmb-text" value="%s" size="%s"></li>',
              $value,
              $field[ 'size' ]
            ) );
          }
        }
        $html .= '<li class="howto">' . sprintf( __( "Shown values for Alias [%s]", ud_get_wp_property()->domain ), $targets ) . '</li>';
      } else {
        $html .= '<li class="howto">' . sprintf( __( "Values for Alias [%s] not found", ud_get_wp_property()->domain ), $targets ) . '</li>';
      }
      $html .= '<ul>';

      return $html;

    }

  }
}
