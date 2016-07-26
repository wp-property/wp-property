<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if( !class_exists( 'RWMB_Wpp_Address_Field' ) && class_exists( 'RWMB_Text_Field' ) ) {
  class RWMB_Wpp_Address_Field extends RWMB_Text_Field {

    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts() {
      // wp_enqueue_style( 'rwmb-text', RWMB_CSS_URL . 'text.css', array(), RWMB_VER );
      wp_enqueue_script( 'rwmb-wpp-address', ud_get_wp_property()->path( 'static/scripts/wpp.admin.fields.js' ), array( 'jquery' ) );

      wp_register_script( 'google-maps', 'https://maps.google.com/maps/api/js?sensor=false&key='.ud_get_wp_property( 'configuration.google_maps_api' ), array(), '', true );
      wp_enqueue_style( 'rwmb-map', RWMB_CSS_URL . 'map.css' );
      wp_enqueue_script( 'rwmb-map', ud_get_wp_property()->path( 'static/scripts/fields/wpp-map-address.js' ), array( 'jquery-ui-autocomplete', 'google-maps' ), RWMB_VER, true );
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
      global $post, $wp_properties;

      $property = get_property( $post->ID, array(
        'get_children' => 'false',
        'return_object' => 'true',
        'load_gallery' => 'false',
        'load_thumbnail' => 'false',
        'load_parent' => 'false',
      ) );

      $html = '<div class="rwmb-map-field">';

      $html .= sprintf(
          '<button class="button rwmb-map-goto-address-button" value="%s">%s</button>',
          $field[ 'field_name' ],
          __( 'Show Address on Map', 'meta-box' )
      );

      $html .= sprintf(
          '<div class="rwmb-map-canvas" data-default-loc="%s"></div>
				<input type="hidden" name="wpp_data[meta][location_map_coordinates]" class="rwmb-map-coordinate" value="%s">',
          esc_attr( implode( ',', $wp_properties['default_coords'] ) ),
          esc_attr( !empty($property->latitude)?($property->latitude.','.$property->longitude):implode( ',', $wp_properties['default_coords'] ) )
      );

      $html .= '</div>';

      ob_start();
      ?>
      <div class="wpp_attribute_row_address">
        <?php
        printf(
          '<input type="text" data-field-type="wpp-address" class="rwmb-text" name="wpp_data[meta][%s]" id="%s" value="%s" placeholder="%s" size="%s" %s>',
          $field[ 'field_name' ],
          $field[ 'id' ],
          $meta,
          $field[ 'placeholder' ],
          $field[ 'size' ],
          $field[ 'datalist' ] ? "list='{$field['datalist']['id']}'" : ''
        );
        echo $field[ 'desc' ] ? "<p id='{$field['id']}_description' class='description'>{$field['desc']}</p>" : '';
        ?>
        <?php if( !empty( $property ) && current_user_can( 'manage_wpp_settings' ) ) : ?>
          <div class="wpp_attribute_row_address_options">
            <input type="hidden" name="wpp_data[meta][manual_coordinates]" value="false"/>
            <input type="checkbox" id="wpp_manual_coordinates" name="wpp_data[meta][manual_coordinates]" value="true" <?php echo isset( $property->manual_coordinates ) && in_array( $property->manual_coordinates, array( 'true', '1' ) ) ? 'checked="checked"' : ''; ?> />
            <label for="wpp_manual_coordinates"><?php _e( 'Set Coordinates Manually.', ud_get_wp_property()->domain ); ?></label>
            <div id="wpp_coordinates" style="<?php echo !empty( $property->manual_coordinates ) && in_array( $property->manual_coordinates, array( 'true', '1' ) ) ? '' : 'display:none;'; ?>">
              <ul>
                <li>
                  <input type="text" id="wpp_meta_latitude" name="wpp_data[meta][latitude]" value="<?php echo isset( $property->latitude ) ? $property->latitude : ''; ?>"/>
                  <label><?php _e( 'Latitude', ud_get_wp_property()->domain ); ?></label>
                  <div class="wpp_clear"></div>
                </li>
                <li>
                  <input type="text" id="wpp_meta_longitude" name="wpp_data[meta][longitude]" value="<?php echo isset( $property->longitude ) ? $property->longitude : ''; ?>"/>
                  <label><?php _e( 'Longitude', ud_get_wp_property()->domain ); ?></label>
                  <div class="wpp_clear"></div>
                </li>
              </ul>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <?php
      return ob_get_clean() . $html;
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
    }

    /**
     * Show end HTML markup for fields
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function end_html( $meta, $field ) {
      // Closes the container
      $html = "</div>";
      return $html;
    }

  }
}
