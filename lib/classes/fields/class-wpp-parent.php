<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Parent_Field' ) && class_exists( 'RWMB_Field' ) ) {
  class RWMB_Wpp_Parent_Field extends RWMB_Field {

    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts() {
      wp_enqueue_style( 'field-wpp-parent', ud_get_wp_property()->path( 'static/styles/fields/wpp-parent.css' ), array( 'wp-admin' ), ud_get_wp_property( 'version' ) );
      wp_enqueue_script( 'field-wpp-parent', ud_get_wp_property()->path( 'static/scripts/fields/wpp-parent.js' ), array( 'jquery-ui-autocomplete', 'wpp-localization' ), ud_get_wp_property( 'version' ), true );
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

      if( !is_array( $meta ) )
        $meta = array( $meta );

      if( is_string( $field[ 'options' ] ) ) {
        $options = $field[ 'options' ];
      } else {
        $options = array();
        foreach( $field[ 'options' ] as $value => $label ) {
          $options[ ] = array(
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
        $field[ 'id' ],
        $field[ 'field_name' ],
        esc_attr( $options ),
        ( isset( $field[ 'size' ] ) ? $field[ 'size' ] : 20 )
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
        $label = self::prepare_parent_label( $post->post_parent );
        $html .= sprintf(
          $tpl,
          $label,
          __( 'Delete', 'meta-box' ),
          $field[ 'field_name' ],
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
    static function save( $new, $old, $post_id, $field ) {
      self::update_parent_id( $new, $post_id );
    }

    /**
     * Updates parent ID.
     * Determines if parent exists and it doesn't have own parent.
     *
     * @param integer $parent_id
     * @param integer $post_id
     *
     * @return int
     * @author peshkov@UD
     * @since 1.37.5
     */
    static public function update_parent_id( $parent_id, $post_id ) {
      global $wpdb, $wp_properties;

      $parent_id = !empty( $parent_id ) ? $parent_id : 0;

      $post = get_post( $parent_id );

      if( !$post ) {
        $parent_id = 0;
      } else {
        if( $post->post_parent > 0 ) {
          if( empty( $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] ) || $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] != 'true' ) {
            $parent_id = 0;
          }
        }
      }

      if( $parent_id == 0 ) {
        $wpdb->query( "UPDATE {$wpdb->posts} SET post_parent=0 WHERE ID={$post_id}" );
      }

      update_post_meta( $post_id, 'parent_gpid', WPP_F::maybe_set_gpid( $parent_id ) );

      return $parent_id;
    }

    /**
     * Normalize parameters for field
     *
     * @param array $field
     *
     * @return array
     */
    static function normalize_field( $field ) {
      $field = wp_parse_args( $field, array(
        'size' => 30,
      ) );
      return $field;
    }

    /**
     * Maybe prepare parent label for showing it on Meta Box.
     */
    static public function prepare_parent_label( $label ) {
      if( is_numeric( $label ) ) {
        $post = get_post( $label );
        if( !empty( $post ) ) {
          $label = '<span class="wpp-parent-label-wprapper"><i class="dashicons-admin-home dashicons"></i>';
          $label .= '<a class="wpp-parent-label" href="' . admin_url( "post.php?post={$post->ID}&action=edit" ) . '">' . $post->post_title . '</a>';
          $label .= '</span>';
        }
      }
      return $label;
    }

  }
}
