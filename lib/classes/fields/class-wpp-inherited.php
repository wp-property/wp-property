<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Inherited_Field' ) && class_exists( 'RWMB_Text_Field' ) ) {

  class RWMB_Wpp_Inherited_Field extends RWMB_Text_Field {

    /**
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field ) {
      return sprintf(
        '<input type="text" data-field-type="wpp-inheriteed" readonly="readonly" name="%s" class="rwmb-text" id="%s" value="%s" placeholder="%s" size="%s" %s>%s',
        $field[ 'field_name' ],
        $field[ 'id' ],
        $meta,
        $field[ 'placeholder' ],
        $field[ 'size' ],
        $field[ 'datalist' ] ? "list='{$field['datalist']['id']}'" : '',
        self::datalist_html( $field )
      );
    }

    /**
     * Get meta value
     *
     * @param int $post_id
     * @param bool $saved
     * @param array $field
     *
     * @return mixed
     */
    static function meta( $post_id, $saved, $field ) {

      /**
       * For special fields like 'divider', 'heading' which don't have ID, just return empty string
       * to prevent notice error when displayin fields
       */
      if( empty( $field[ 'id' ] ) )
        return '';

      /**
       * Maybe set value from parent
       */
      $post = get_post( $post_id );
      if( $post && $post->post_parent > 0 ) {
        $property_inheritance = ud_get_wp_property( 'property_inheritance', array() );
        $type = get_post_meta( $post_id, 'property_type', true );
        if( isset( $property_inheritance[ $type ] ) && in_array( $field[ 'id' ], $property_inheritance[ $type ] ) ) {
          $meta = get_post_meta( $post->post_parent, $field[ 'id' ], !$field[ 'multiple' ] );
        }
      }

      if( !$meta ) {
        $meta = get_post_meta( $post_id, $field[ 'id' ], !$field[ 'multiple' ] );
      }

      // Use $field['std'] only when the meta box hasn't been saved (i.e. the first time we run)
      $meta = ( !$saved && '' === $meta || array() === $meta ) ? $field[ 'std' ] : $meta;

      // Escape attributes
      $meta = call_user_func( array( RW_Meta_Box::get_class_name( $field ), 'esc_meta' ), $meta );

      return $meta;
    }

  }

}
