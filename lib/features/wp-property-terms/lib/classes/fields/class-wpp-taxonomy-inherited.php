<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Taxonomy_Inherited_Field' ) )  {

  class RWMB_Wpp_Taxonomy_Inherited_Field extends RWMB_Taxonomy_Field {

    /**
     * Loading Scripts and Styles for current Field
     *
     * @return void
     */
    static function admin_enqueue_scripts() {
      wp_enqueue_style( 'field-wpp-taxonomy-inherited', ud_get_wpp_terms()->path( 'static/styles/fields/wpp-taxonomy-inherited.css' ), array('wp-admin'), ud_get_wpp_terms('version') );
    }

    /**
     * Get field HTML
     *
     * @param $field
     * @param $meta
     *
     * @return string
     */
    static function html( $meta, $field ) {

      if( !empty( $meta ) ) {
        $options = $field['options'];
        $options['args'][ 'include' ] = (array) $meta;
        $terms   = get_terms( $options['taxonomy'], $options['args'] );
        $field['options'] = self::get_options( $terms );
        $field['display_type'] = $options['type'];
        $html = array();

        $tpl  = '<span class="term">%s<input type="hidden" class="rwmb-checkbox-list" name="%s" value="%s"></span>';

        foreach ( $field['options'] as $value => $label )  {
          $html[] = sprintf(
            $tpl,
            $label,
            $field['field_name'],
            $value
          );
        }

        $html = implode( '', $html );

      } else {
        $html = "&nbsp;";
      }

      return '<div class="readonly">' . $html . '</div>';
    }

    /**
     * Get meta value
     *
     * @param int   $post_id
     * @param bool  $saved
     * @param array $field
     *
     * @return mixed
     */
    static function meta( $post_id, $saved, $field ) {

      $parent_id = wp_get_post_parent_id($post_id);
      if( $parent_id ) {
        $post_id = $parent_id;
      }

      $options = $field['options'];

      $meta = wp_get_post_terms( $post_id, $options['taxonomy'] );
      $meta = is_array( $meta ) ? $meta : (array) $meta;
      $meta = wp_list_pluck( $meta, 'term_id' );

      return $meta;
    }

  }

}
