<?php
/**
 *
 * Inherited means "delegated" from a parent listing down to a child listing.
 *
 */
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Inherited_Address_Field' ) && class_exists( 'RWMB_Text_Field' ) ) {
  class RWMB_Wpp_Inherited_Address_Field extends RWMB_Text_Field {

    static $map = array(
      'latitude',
      'longitude',
      'address_is_formatted',
      'exclude_from_supermap',
      'manual_coordinates',
    );

    /*
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field ) {

      $html = sprintf(
        '<input type="text" data-field-type="wpp-inheriteed-address" readonly="readonly" class="rwmb-text" name="%s" id="%s" value="%s" placeholder="%s" size="%s" %s>%s',
        $field[ 'field_name' ],
        $field[ 'id' ],
        $meta[ 'value' ],
        $field[ 'placeholder' ],
        $field[ 'size' ],
        $field[ 'datalist' ] ? "list='{$field['datalist']['id']}'" : '',
        self::datalist_html( $field )
      );

      if( !empty( $meta[ 'options' ] ) ) {
        foreach( $meta[ 'options' ] as $k => $v ) {
          $html .= sprintf( '<input type="hidden" name="%s" value="%s" />', $k, $v );
        }
      }

      return $html;
    }

    /**
     * Save meta value
     *
     * Ignore it for now.
     * WP-Property takes care about saving address data itself
     * @see WPP_Core::save_property
     *
     * @param $new
     * @param $old
     * @param $post_id
     * @param $field
     */
    static function save( $new, $old, $post_id, $field ) {

      update_post_meta( $post_id, $field[ 'id' ], $new, $old );

      $attributes = array_keys( ud_get_wp_property( 'property_stats', array() ) );

      foreach( self::$map as $meta ) {
        // Ignore meta if property attribute with the same name exists
        if( isset( $_REQUEST[ $meta ] ) && !in_array( $meta, $attributes ) ) {
          update_post_meta( $post_id, $meta, $_REQUEST[ $meta ] );
        }
      }
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
      $meta = array(
        'value' => false,
        'options' => array()
      );

      if( empty( $field[ 'id' ] ) || ud_get_wp_property( 'configuration.address_attribute' ) !== $field[ 'id' ] )
        return array();

      /**
       * Maybe set value from parent
       */
      $post = get_post( $post_id );
      if( $post && $post->post_parent > 0 ) {
        $property_inheritance = ud_get_wp_property( 'property_inheritance', array() );
        $type = get_post_meta( $post_id, 'property_type', true );
        if( !isset( $property_inheritance[ $type ] ) || !in_array( $field[ 'id' ], $property_inheritance[ $type ] ) ) {
          return array();
        }
        $meta[ 'value' ] = get_post_meta( $post->post_parent, $field[ 'id' ], true );

        $attributes = array_keys( ud_get_wp_property( 'property_stats', array() ) );

        foreach( self::$map as $k ) {
          // Ignore meta if property attribute with the same name exists
          if( in_array( $k, $attributes ) ) {
            continue;
          }
          $meta[ 'options' ][ $k ] = get_post_meta( $post->post_parent, $k, true );
        }
      }

      return $meta;
    }
    
    /**
     * Create datalist, if any
     *
     * @param array $field
     *
     * @return array
     */
    static public function datalist_html( $field ) {
      if( !$field->datalist ) {
        return '';
      }
      $datalist = $field->datalist;
      $html = sprintf( '<datalist id="%s">', $datalist[ 'id' ] );

      foreach( $datalist[ 'options' ] as $option ) {
        $html.= sprintf( '<option value="%s"></option>', $option );
      }

      $html .= '</datalist>';

      return $html;
    }
  }
}
