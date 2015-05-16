<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Wpp_Address_Field' ) )
{
  class RWMB_Wpp_Address_Field extends RWMB_Text_Field
  {

    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts() {
      wp_enqueue_style( 'rwmb-text', RWMB_CSS_URL . 'text.css', array(), RWMB_VER );
      wp_enqueue_script( 'rwmb-wpp-address', ud_get_wp_property()->path( 'static/scripts/wpp.admin.fields.js' ), array( 'jquery' ) );
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

      $property = get_property( $post->ID, array(
        'get_children'          => 'false',
        'return_object'         => 'true',
        'load_gallery'          => 'false',
        'load_thumbnail'        => 'false',
        'load_parent'           => 'false',
      ) );

      ob_start();
      ?>
      <div class="wpp_attribute_row_address">
        <?php
        printf(
          '<input type="text" class="rwmb-text" name="wpp_data[meta][%s]" id="%s" value="%s" placeholder="%s" size="%s" %s>',
          $field['field_name'],
          $field['id'],
          $meta,
          $field['placeholder'],
          $field['size'],
          $field['datalist'] ? "list='{$field['datalist']['id']}'" : ''
        );
        echo $field['desc'] ? "<p id='{$field['id']}_description' class='description'>{$field['desc']}</p>" : '';
        ?>
        <?php if( !empty( $property ) && current_user_can( 'manage_wpp_settings' ) ) : ?>
          <div class="wpp_attribute_row_address_options hidden">
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
      return ob_get_clean();
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
    static function save( $new, $old, $post_id, $field ) {}

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
