<?php
/**
 * Settings 'Developer' Tab
 * Section 'Attributes'
 */

global $wp_properties;

?>
<div>
  <h3 style="float:left;"><?php printf( __( '%1s Attributes', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></h3>
  <div class="wpp_property_stat_functions">
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="expand" value="<?php _e( 'Expand all', ud_get_wp_property()->domain ) ?>" />
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="collapse" value="<?php _e( 'Collapse all', ud_get_wp_property()->domain ) ?>" />
    <input type="button" class="sort_stats_by_groups button-secondary" value="<?php _e( 'Sort by Groups', ud_get_wp_property()->domain ) ?>"/>
  </div>
  <div class="clear"></div>
</div>

<table id="wpp_inquiry_attribute_fields" class="wpp_inquiry_attribute_fields ud_ui_dynamic_table widefat" allow_random_slug="true">
  <thead>
  <tr>
    <th class='wpp_draggable_handle'>&nbsp;</th>
    <th class='wpp_attribute_name_col'><?php _e( 'Attribute Name', ud_get_wp_property()->domain ) ?></th>
    <th class='wpp_attribute_group_col'><?php _e( 'Group', ud_get_wp_property()->domain ) ?></th>
    <th class='wpp_settings_input_col'><?php _e( 'Settings', ud_get_wp_property()->domain ) ?></th>
    <th class='wpp_search_input_col'><?php _e( 'Search Input', ud_get_wp_property()->domain ) ?></th>
    <th class='wpp_admin_input_col'><?php _e( 'Data Entry', ud_get_wp_property()->domain ) ?></th>
  </tr>
  </thead>
  <tbody>
  <?php foreach( $wp_properties[ 'property_stats' ] as $slug => $label ): ?>
    <?php $gslug = false; ?>
    <?php $group = false; ?>
    <?php if( !empty( $wp_properties[ 'property_stats_groups' ][ $slug ] ) ) : ?>
      <?php $gslug = $wp_properties[ 'property_stats_groups' ][ $slug ]; ?>
      <?php $group = $wp_properties[ 'property_groups' ][ $gslug ]; ?>
    <?php endif; ?>
    <tr class="wpp_dynamic_table_row" <?php echo( !empty( $gslug ) ? "wpp_attribute_group=\"" . $gslug . "\"" : "" ); ?> style="<?php echo( !empty( $group[ 'color' ] ) ? "background-color:" . $group[ 'color' ] : "" ); ?>" slug="<?php echo $slug; ?>" new_row='false'>

      <td class="wpp_draggable_handle">&nbsp;</td>

      <td class="wpp_attribute_name_col">
        <ul class="wpp_attribute_name">
          <li>
            <input class="slug_setter" type="text" name="wpp_settings[property_stats][<?php echo $slug; ?>]" value="<?php echo $label; ?>"/>
          </li>
          <li class="wpp_development_advanced_option">
            <input type="text" class="slug wpp_stats_slug_field" readonly='readonly' value="<?php echo $slug; ?>"/>
            <?php if( in_array( $slug, $wp_properties[ 'geo_type_attributes' ] ) ): ?>
              <div class="wpp_notice">
                <span><?php _e( 'Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automaticaly and can not be edited on Property Adding/Updating page.', ud_get_wp_property()->domain ); ?></span>
              </div>
            <?php endif; ?>
          </li>
          <?php do_action( 'wpp::property_attributes::attribute_name', $slug ); ?>
          <li>
            <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Settings', ud_get_wp_property()->domain ); ?></span>
          </li>
        </ul>
      </td>

      <td class="wpp_attribute_group_col">
        <input type="text" class="wpp_attribute_group wpp_group" value="<?php echo( !empty( $group[ 'name' ] ) ? $group[ 'name' ] : "" ); ?>"/>
        <input type="hidden" class="wpp_group_slug" name="wpp_settings[property_stats_groups][<?php echo $slug; ?>]" value="<?php echo( !empty( $gslug ) ? $gslug : "" ); ?>">
      </td>

      <td class="wpp_settings_input_col">
        <ul>
          <li>
            <label>
              <input <?php if( in_array( $slug, ( ( !empty( $wp_properties[ 'sortable_attributes' ] ) ? $wp_properties[ 'sortable_attributes' ] : array() ) ) ) ) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[sortable_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Sortable.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li>
            <label>
              <input <?php echo ( isset( $wp_properties[ 'searchable_attributes' ] ) && is_array( $wp_properties[ 'searchable_attributes' ] ) && in_array( $slug, $wp_properties[ 'searchable_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[searchable_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Searchable.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li class="wpp_development_advanced_option">
            <label>
              <input <?php echo ( isset( $wp_properties[ 'hidden_frontend_attributes' ] ) && is_array( $wp_properties[ 'hidden_frontend_attributes' ] ) && in_array( $slug, $wp_properties[ 'hidden_frontend_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[hidden_frontend_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Admin Only.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li class="wpp_development_advanced_option">
            <label>
              <input <?php echo ( isset( $wp_properties[ 'column_attributes' ] ) && is_array( $wp_properties[ 'column_attributes' ] ) && in_array( $slug, $wp_properties[ 'column_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[column_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Add Column on "All Properties" page.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <?php do_action( 'wpp::property_attributes::settings', $slug ); ?>
          <li class="wpp_development_advanced_option">
            <span class="wpp_delete_row wpp_link"><?php _e( 'Delete Attribute', ud_get_wp_property()->domain ) ?></span>
          </li>
        </ul>
      </td>

      <td class="wpp_search_input_col">
        <ul>
          <li>
            <select name="wpp_settings[searchable_attr_fields][<?php echo $slug; ?>]" class="wpp_pre_defined_value_setter wpp_searchable_attr_fields">
              <option value=""> - </option>
              <option value="input" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'input' ); ?>><?php _e( 'Free Text', ud_get_wp_property()->domain ) ?></option>
              <option value="range_input" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'range_input' ); ?>><?php _e( 'Text Input Range', ud_get_wp_property()->domain ) ?></option>
              <option value="range_dropdown" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'range_dropdown' ); ?>><?php _e( 'Range Dropdown', ud_get_wp_property()->domain ) ?></option>
              <option value="advanced_range_dropdown" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'advanced_range_dropdown' ); ?>><?php _e( 'Advanced Range Dropdown', ud_get_wp_property()->domain ) ?></option>
              <option value="dropdown" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'dropdown' ); ?>><?php _e( 'Dropdown Selection', ud_get_wp_property()->domain ) ?></option>
              <option value="checkbox" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'checkbox' ); ?>><?php _e( 'Single Checkbox', ud_get_wp_property()->domain ) ?></option>
              <option value="multi_checkbox" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'multi_checkbox' ); ?>><?php _e( 'Multi-Checkbox', ud_get_wp_property()->domain ) ?></option>
              <?php do_action( 'wpp::property_attributes::searchable_attr_field', $slug ); ?>
            </select>
          </li>
          <li>
            <textarea class="wpp_attribute_pre_defined_values" name="wpp_settings[predefined_search_values][<?php echo $slug; ?>]"><?php echo isset( $wp_properties[ 'predefined_search_values' ][ $slug ] ) ? $wp_properties[ 'predefined_search_values' ][ $slug ] : ''; ?></textarea>
          </li>
        </ul>
      </td>

      <td class="wpp_admin_input_col">
        <ul>
          <li>
            <select name="wpp_settings[admin_attr_fields][<?php echo $slug; ?>]" class="wpp_pre_defined_value_setter wpp_searchable_attr_fields">
              <?php $meta_box_fields = ud_get_wp_property('attributes.types', array()); ?>
              <?php if( !empty( $meta_box_fields ) ) foreach( $meta_box_fields as $key => $label ) :  ?>
                <option value="<?php echo $key; ?>" <?php if( isset( $wp_properties[ 'admin_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'admin_attr_fields' ][ $slug ], $key ); ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
              <?php do_action( 'wpp::property_attributes::admin_attr_field', $slug ); ?>
            </select>
          </li>
          <li>
            <textarea class="wpp_attribute_pre_defined_values" name="wpp_settings[predefined_values][<?php echo $slug; ?>]"><?php echo isset( $wp_properties[ 'predefined_values' ][ $slug ] ) ? $wp_properties[ 'predefined_values' ][ $slug ] : ''; ?></textarea>
          </li>
        </ul>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>

  <tfoot>
  <tr>
    <td colspan='6'>
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', ud_get_wp_property()->domain ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>