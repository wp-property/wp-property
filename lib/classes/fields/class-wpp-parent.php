<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Parent_Field' ) ) {
  class RWMB_Wpp_Parent_Field extends RWMB_Autocomplete_Field {

    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts() {
      parent::admin_enqueue_scripts();
      wp_enqueue_script( 'field-wpp-parent', ud_get_wp_property()->path( 'static/scripts/fields/wpp-parent.js' ), array( 'jquery-ui-autocomplete' ), ud_get_wp_property('version'), true );
    }

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

      if ( ! is_array( $meta ) )
        $meta = array( $meta );

      if ( is_string( $field['options'] ) )  {
        $options = $field['options'];
      } else {
        $options = array();
        foreach ( $field['options'] as $value => $label ) {
          $options[] = array(
            'value' => $value,
            'label' => $label,
          );
        }
        $options = json_encode( $options );
      }

      // Input field that triggers autocomplete.
      // This field doesn't store field values, so it doesn't have "name" attribute.
      // The value(s) of the field is store in hidden input(s). See below.
      $html = sprintf(
        '<input type="text" class="rwmb-autocomplete" id="%s" data-name="%s" data-options="%s" size="%s">',
        $field['id'],
        $field['field_name'],
        esc_attr( $options ),
        $field['size']
      );

      $html .= '<div class="rwmb-autocomplete-results">';

      // Each value is displayed with label and 'Delete' option
      // The hidden input has to have ".rwmb-*" class to make clone work
      $tpl = '
				<div class="rwmb-autocomplete-result">
					<div class="label">%s</div>
					<div class="actions">%s</div>
					<input type="hidden" class="rwmb-autocomplete-value" name="%s" value="%s">
				</div>
			';

      if( $post->post_parent > 0 ) {
        $label = apply_filters( 'rwmb_autocomplete_result_label', $post->post_parent, $field );
        $html .= sprintf(
          $tpl,
          $label,
          __( 'Delete', 'meta-box' ),
          $field['field_name'],
          $post->post_parent
        );
      }

      $html .= '</div>'; // .rwmb-autocomplete-results

      return $html;
    }

    /**
     * Save meta value
     *
     * Ignore it for now.
     * WP-Property takes care about saving parent data itself
     * @see WPP_Core::save_property
     *
     * @param $new
     * @param $old
     * @param $post_id
     * @param $field
     */
    static function save( $new, $old, $post_id, $field ) {}

  }
}
