<?php
/**
 * Settings 'Developer' Tab
 * Section 'Types'
 */

global $wp_properties;

?>
<h3><?php printf( __( '%1s Types', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></h3>
<table id="wpp_inquiry_property_types" class="ud_ui_dynamic_table widefat" allow_random_slug="true">
  <thead>
  <tr>
    <th><?php _e( 'Type', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Default Image', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Settings', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Hidden Attributes', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Inherit from Parent', ud_get_wp_property()->domain ) ?></th>
  </tr>
  </thead>
  <tbody>
  <?php foreach( $wp_properties[ 'property_types' ] as $property_slug => $label ): ?>

    <tr class="wpp_dynamic_table_row" slug="<?php echo $property_slug; ?>"  data-property-slug="<?php echo $property_slug; ?>" new_row='false'>

      <td>
        <ul>
          <li><input class="slug_setter" type="text" name="wpp_settings[property_types][<?php echo $property_slug; ?>]" value="<?php echo $label; ?>"/></li>
          <li><input type="text" class="slug" readonly='readonly' value="<?php echo $property_slug; ?>"/></li>
          <li><span class="wpp_delete_row wpp_link">Delete</span></li>
        </ul>
      </td>

      <td>
        <div class="upload-image-section">
          <input type="hidden" name="wpp_settings[configuration][default_image][types][<?php echo $property_slug; ?>][url]" class="input-image-url" value="<?php echo isset( $wp_properties[ 'configuration' ][ 'default_image' ]['types'][$property_slug]['url'] ) ? $wp_properties[ 'configuration' ][ 'default_image' ]['types'][$property_slug]['url'] : ''; ?>">
          <input type="hidden" name="wpp_settings[configuration][default_image][types][<?php echo $property_slug; ?>][id]" class="input-image-id" value="<?php echo isset( $wp_properties[ 'configuration' ][ 'default_image' ]['types'][$property_slug]['id'] ) ? $wp_properties[ 'configuration' ][ 'default_image' ]['types'][$property_slug]['id'] : ''; ?>">
          <div class="image-actions">
            <input type="button" class="button-secondary button-setup-image" value="<?php _e( 'Setup Image', ud_get_wp_property('domain') ); ?>" title="<?php printf( __( 'If %1$s has no any image, the default one based on %1$s Type will be shown.', ud_get_wp_property('domain') ), \WPP_F::property_label() ); ?>">
          </div>
          <div class="image-wrapper"></div>
        </div>
      </td>

      <td>
        <ul>
          <li>
            <label for="<?php echo $property_slug; ?>_searchable_property_types">
              <input class="slug" id="<?php echo $property_slug; ?>_searchable_property_types" <?php if( is_array( $wp_properties[ 'searchable_property_types' ] ) && in_array( $property_slug, $wp_properties[ 'searchable_property_types' ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[searchable_property_types][]" value="<?php echo $property_slug; ?>"/>
              <?php _e( 'Searchable', ud_get_wp_property()->domain ) ?>
            </label>
          </li>

          <li>
            <label for="<?php echo $property_slug; ?>_location_matters">
              <input class="slug" id="<?php echo $property_slug; ?>_location_matters"  <?php if( in_array( $property_slug, $wp_properties[ 'location_matters' ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[location_matters][]" value="<?php echo $property_slug; ?>"/>
              <?php _e( 'Location Matters', ud_get_wp_property()->domain ) ?>
            </label>
          </li>
          
          <?php $property_type_settings = apply_filters( 'wpp_property_type_settings', array(), $property_slug ); ?>
          <?php foreach( (array) $property_type_settings as $property_type_setting ) : ?>
            <li>
              <?php echo $property_type_setting; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </td>

      <td>
        <ul class="wp-tab-panel wpp_hidden_property_attributes wpp_something_advanced_wrapper">

          <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', ud_get_wp_property()->domain ); ?></li>

          <?php foreach( $wp_properties[ 'property_stats' ] as $property_stat_slug => $property_stat_label ) : ?>
            <li class="wpp_development_advanced_option">
              <input id="<?php echo $property_slug . "_" . $property_stat_slug; ?>_hidden_attributes" <?php if( isset( $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) && in_array( $property_stat_slug, $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug; ?>][]" value="<?php echo $property_stat_slug; ?>"/>
              <label for="<?php echo $property_slug . "_" . $property_stat_slug; ?>_hidden_attributes">
                <?php echo $property_stat_label; ?>
              </label>
            </li>
          <?php endforeach; ?>

          <?php foreach( $wp_properties[ 'property_meta' ] as $property_meta_slug => $property_meta_label ) : ?>
            <li class="wpp_development_advanced_option">
              <input id="<?php echo $property_slug . "_" . $property_meta_slug; ?>_hidden_attributes" <?php if( isset( $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) && in_array( $property_meta_slug, $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug; ?>][]" value="<?php echo $property_meta_slug; ?>"/>
              <label for="<?php echo $property_slug . "_" . $property_meta_slug; ?>_hidden_attributes">
                <?php echo $property_meta_label; ?>
              </label>
            </li>
          <?php endforeach; ?>

          <?php if( empty( $wp_properties[ 'property_stats' ][ 'parent' ] ) ) : ?>
            <li class="wpp_development_advanced_option">
              <input id="<?php echo $property_slug; ?>parent_hidden_attributes" <?php if( isset( $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) && in_array( 'parent', $wp_properties[ 'hidden_attributes' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug; ?>][]" value="parent"/>
              <label for="<?php echo $property_slug; ?>parent_hidden_attributes"><?php _e( 'Parent Selection', ud_get_wp_property()->domain ); ?></label>
            </li>
          <?php endif; ?>
          <?php do_action( 'wpp::types::hidden_attributes', $property_slug ); ?>
        </ul>
      </td>

      <td>
        <ul class="wp-tab-panel wpp_inherited_property_attributes wpp_something_advanced_wrapper">
          <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', ud_get_wp_property()->domain ); ?></li>
          <?php foreach( $wp_properties[ 'property_stats' ] as $property_stat_slug => $property_stat_label ): ?>
            <li class="wpp_development_advanced_option">
              <input id="<?php echo $property_slug . "_" . $property_stat_slug; ?>_inheritance" <?php if( isset( $wp_properties[ 'property_inheritance' ][ $property_slug ] ) && in_array( $property_stat_slug, $wp_properties[ 'property_inheritance' ][ $property_slug ] ) ) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[property_inheritance][<?php echo $property_slug; ?>][]" value="<?php echo $property_stat_slug; ?>"/>
              <label for="<?php echo $property_slug . "_" . $property_stat_slug; ?>_inheritance">
                <?php echo $property_stat_label; ?>
              </label>
            </li>
          <?php endforeach; ?>
          <?php do_action( 'wpp::types::inherited_attributes', $property_slug ); ?>
        </ul>
      </td>

    </tr>

  <?php endforeach; ?>
  </tbody>

  <tfoot>
  <tr>
    <td colspan='5'>
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', ud_get_wp_property()->domain ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>