<?php
// Prevent loading this file directly
/*
This file will use for any inheried fields.
This find original field type from $wp_properties array.
Then pass field to modified show function of wp-meta-box/inc/field.php

*/
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Inherited_Field' ) && class_exists( 'RWMB_Field' ) ) {

  class RWMB_Wpp_Inherited_Field extends RWMB_Field {

    static function admin_enqueue_scripts(){
      wp_enqueue_style( 'wpp-inherited-style', ud_get_wp_property()->path( 'static/styles/fields/wpp-inherited.css' ), array( 'wp-admin' ), ud_get_wp_property( 'version' ) );
    }

    static function show( $field, $saved ){

      global $post;
      $id   = $field['id'];

      $type = $field['original_type'];
      if($type == 'image_advanced' || $type == 'file_advanced') {
          $type = "wpp_Inherited_" . $type;
      } 

      $field['readonly']    = true;
      $field['type']        = $type;

      if($type != 'oembed'){
        $field['class']       = "readonly";
      }
      
      $field_class = self::get_class_name( $field );
      $field = call_user_func(array($field_class , 'normalize'), $field);
      call_user_func(array($field_class, 'admin_enqueue_scripts'));
      add_filter( "rwmb_{$type}_html", array(__CLASS__, 'make_readonly'), 10, 3);
      self::_show( $field, $saved );
      remove_filter( "rwmb_{$type}_html", array(__CLASS__, 'make_readonly'), 10, 3);
    }

    /**
     * Show field HTML
     * Filters are put inside this method, not inside methods such as "meta", "html", "begin_html", etc.
     * That ensures the returned value are always been applied filters
     * This method is not meant to be overwritten in specific fields
     * Copied & modified from wp-meta-box/inc/field.php
     * @param array $field
     * @param bool  $saved
     *
     * @return string
     */
    static function _show( $field, $saved ){

      global $post;

      $field_class = self::get_class_name( $field );
      $meta        = self::meta($post->ID, $saved, $field ); // Modification made here to get meta() function from this class.

      // Apply filter to field meta value
      // 1st filter applies to all fields
      // 2nd filter applies to all fields with the same type
      // 3rd filter applies to current field only
      $meta = apply_filters( 'rwmb_field_meta', $meta, $field, $saved );
      $meta = apply_filters( "rwmb_{$field['type']}_meta", $meta, $field, $saved );
      $meta = apply_filters( "rwmb_{$field['id']}_meta", $meta, $field, $saved );

      $type = $field['type'];
      $id   = $field['id'];

      $begin = call_user_func( array( $field_class, 'begin_html' ), $meta, $field );

      // Apply filter to field begin HTML
      // 1st filter applies to all fields
      // 2nd filter applies to all fields with the same type
      // 3rd filter applies to current field only
      $begin = apply_filters( 'rwmb_begin_html', $begin, $field, $meta );
      $begin = apply_filters( "rwmb_{$type}_begin_html", $begin, $field, $meta );
      $begin = apply_filters( "rwmb_{$id}_begin_html", $begin, $field, $meta );

      // Separate code for cloneable and non-cloneable fields to make easy to maintain

      // Cloneable fields
      if ( $field['clone'] ){
        $field_html = '';

        /**
         * Note: $meta must contain value so that the foreach loop runs!
         * @see self::meta()
         */
        foreach ( $meta as $index => $sub_meta ){
          $sub_field               = $field;
          $sub_field['field_name'] = $field['field_name'] . "[{$index}]";
          if ( $index > 0 )
          {
            if ( isset( $sub_field['address_field'] ) )
              $sub_field['address_field'] = $field['address_field'] . "_{$index}";
            $sub_field['id'] = $field['id'] . "_{$index}";
          }
          if ( $field['multiple'] )
            $sub_field['field_name'] .= '[]';

          // Wrap field HTML in a div with class="rwmb-clone" if needed
          $input_html = '<div class="rwmb-clone">';

          // Call separated methods for displaying each type of field
          $input_html .= call_user_func( array( $field_class, 'html' ), $sub_meta, $sub_field );

          // Apply filter to field HTML
          // 1st filter applies to all fields with the same type
          // 2nd filter applies to current field only
          $input_html = apply_filters( "rwmb_{$type}_html", $input_html, $field, $sub_meta );
          $input_html = apply_filters( "rwmb_{$id}_html", $input_html, $field, $sub_meta );

          // Remove clone button
          $input_html .= call_user_func( array( $field_class, 'remove_clone_button' ), $sub_field );

          $input_html .= '</div>';

          $field_html .= $input_html;
        }
      }
      // Non-cloneable fields
      else{
        // Call separated methods for displaying each type of field
        $field_html = call_user_func( array( $field_class, 'html' ), $meta, $field );

        // Apply filter to field HTML
        // 1st filter applies to all fields with the same type
        // 2nd filter applies to current field only
        $field_html = apply_filters( "rwmb_{$type}_html", $field_html, $field, $meta );
        $field_html = apply_filters( "rwmb_{$id}_html", $field_html, $field, $meta );
      }

      $end = call_user_func( array( $field_class, 'end_html' ), $meta, $field );

      // Apply filter to field end HTML
      // 1st filter applies to all fields
      // 2nd filter applies to all fields with the same type
      // 3rd filter applies to current field only
      $end = apply_filters( 'rwmb_end_html', $end, $field, $meta );
      $end = apply_filters( "rwmb_{$type}_end_html", $end, $field, $meta );
      $end = apply_filters( "rwmb_{$id}_end_html", $end, $field, $meta );

      // Apply filter to field wrapper
      // This allow users to change whole HTML markup of the field wrapper (i.e. table row)
      // 1st filter applies to all fields
      // 1st filter applies to all fields with the same type
      // 2nd filter applies to current field only
      $html = apply_filters( 'rwmb_wrapper_html', "{$begin}{$field_html}{$end}", $field, $meta );
      $html = apply_filters( "rwmb_{$type}_wrapper_html", $html, $field, $meta );
      $html = apply_filters( "rwmb_{$id}_wrapper_html", $html, $field, $meta );

      // Display label and input in DIV and allow user-defined classes to be appended
      $classes = array( 'rwmb-field', "rwmb-{$type}-wrapper" );
      if ( 'hidden' === $field['type'] )
        $classes[] = 'hidden';
      if ( ! empty( $field['required'] ) )
        $classes[] = 'required';
      if ( ! empty( $field['class'] ) )
        $classes[] = $field['class'];

      $outer_html = sprintf(
        $field['before'] . '<div class="%s">%s</div>' . $field['after'],
        implode( ' ', $classes ),
        $html
      );

      // Allow to change output of outer div
      // 1st filter applies to all fields
      // 1st filter applies to all fields with the same type
      // 2nd filter applies to current field only
      $outer_html = apply_filters( 'rwmb_outer_html', $outer_html, $field, $meta );
      $outer_html = apply_filters( "rwmb_{$type}_outer_html", $outer_html, $field, $meta );
      $outer_html = apply_filters( "rwmb_{$id}_outer_html", $outer_html, $field, $meta );

      echo $outer_html;

    }

    /**
     * Get meta value frm parent post
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
      $meta = call_user_func( array( self::get_class_name( $field ), 'esc_meta' ), $meta );

      return $meta;
    }

    static function make_readonly($field_html, $field = array(), $meta = ""){
      if(isset($field['readonly']) and $field['readonly']):
        $field_html = preg_replace("/(name=(\"|').*?(\"|'))/", " ", $field_html);
        return preg_replace('/(<(input|select|a).*?)>/', '$1 disabled="disabled" >', $field_html);
      endif;
      return $field_html;
    }

  }

}
