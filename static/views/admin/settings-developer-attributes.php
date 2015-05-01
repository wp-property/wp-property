<?php
/**
 * Settings 'Developer' Tab
 * Section 'Attributes'
 */

global $wp_properties;

?>
<div>
  <h3 style="float:left;"><?php printf( __( '%1s Attributes', 'wpp' ), WPP_F::property_label() ); ?></h3>
  <div class="wpp_property_stat_functions">
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="expand" value="<?php _e( 'Expand all', 'wpp' ) ?>" />
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="collapse" value="<?php _e( 'Collapse all', 'wpp' ) ?>" />
    <input type="button" id="sort_stats_by_groups" class="button-secondary" value="<?php _e( 'Sort Stats by Groups', 'wpp' ) ?>"/>
  </div>
  <div class="clear"></div>
</div>

<div id="wpp_dialog_wrapper_for_groups"></div>
<div id="wpp_attribute_groups">
  <table cellpadding="0" cellspacing="0" allow_random_slug="true" class="ud_ui_dynamic_table widefat wpp_sortable">
    <thead>
    <tr>
      <th class="wpp_group_assign_col">&nbsp;</th>
      <th class='wpp_draggable_handle'>&nbsp;</th>
      <th class="wpp_group_name_col"><?php _e( 'Group Name', 'wpp' ) ?></th>
      <th class="wpp_group_slug_col"><?php _e( 'Slug', 'wpp' ) ?></th>
      <th class='wpp_group_main_col'><?php _e( 'Main', 'wpp' ) ?></th>
      <th class="wpp_group_color_col"><?php _e( 'Group Color', 'wpp' ) ?></th>
      <th class="wpp_group_action_col">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if( empty( $wp_properties[ 'property_groups' ] ) ) {
      //* If there is no any group, we set default */
      $wp_properties[ 'property_groups' ] = array(
        'main' => array(
          'name'  => 'Main',
          'color' => '#bdd6ff'
        )
      );
    }
    ?>
    <?php foreach( $wp_properties[ 'property_groups' ] as $slug => $group ): ?>
      <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='false'>
        <td class="wpp_group_assign_col">
          <input type="button" class="wpp_assign_to_group button-secondary" value="<?php _e( 'Assign', 'wpp' ) ?>"/>
        </td>
        <td class="wpp_draggable_handle">&nbsp;</td>
        <td class="wpp_group_name_col">
          <input class="slug_setter" type="text" name="wpp_settings[property_groups][<?php echo $slug; ?>][name]" value="<?php echo $group[ 'name' ]; ?>"/>
        </td>
        <td class="wpp_group_slug_col">
          <input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>"/>
        </td>
        <td class="wpp_group_main_col">
          <input type="radio" class="wpp_no_change_name" name="wpp_settings[configuration][main_stats_group]" <?php echo( isset( $wp_properties[ 'configuration' ][ 'main_stats_group' ] ) && $wp_properties[ 'configuration' ][ 'main_stats_group' ] == $slug ? "checked=\"checked\"" : "" ); ?> value="<?php echo $slug; ?>"/>
        </td>
        <td class="wpp_group_color_col">
          <input type="text" class="wpp_input_colorpicker" name="wpp_settings[property_groups][<?php echo $slug; ?>][color]" value="<?php echo $group[ 'color' ]; ?>"/>
        </td>
        <td class="wpp_group_action_col">
          <span class="wpp_delete_row wpp_link"><?php _e( 'Delete', 'wpp' ) ?></span>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
      <td colspan='7'>
        <div style="float:left;text-align:left;">
          <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Group', 'wpp' ) ?>"/>
          <input type="button" class="wpp_unassign_from_group button-secondary" value="<?php _e( 'Unassign from Group', 'wpp' ) ?>"/>
        </div>
        <div style="float:right;">
          <input type="button" class="wpp_close_dialog button-secondary" value="<?php _e( 'Apply', 'wpp' ) ?>"/>
        </div>
        <div class="clear"></div>
      </td>
    </tr>
    </tfoot>
  </table>
</div>

<table id="wpp_inquiry_attribute_fields" class="ud_ui_dynamic_table widefat" allow_random_slug="true">
  <thead>
  <tr>
    <th class='wpp_draggable_handle'>&nbsp;</th>
    <th class='wpp_attribute_name_col'><?php _e( 'Attribute Name', 'wpp' ) ?></th>
    <th class='wpp_attribute_group_col'><?php _e( 'Group', 'wpp' ) ?></th>
    <th class='wpp_settings_input_col'><?php _e( 'Settings', 'wpp' ) ?></th>
    <th class='wpp_search_input_col'><?php _e( 'Search Input', 'wpp' ) ?></th>
    <th class='wpp_admin_input_col'><?php _e( 'Data Entry', 'wpp' ) ?></th>
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
                <span><?php _e( 'Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automaticaly and can not be edited on Property Adding/Updating page.', 'wpp' ); ?></span>
              </div>
            <?php endif; ?>
          </li>
          <?php do_action( 'wpp::property_attributes::attribute_name', $slug ); ?>
          <li>
            <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Settings', 'wpp' ); ?></span>
          </li>
        </ul>
      </td>

      <td class="wpp_attribute_group_col">
        <input type="text" class="wpp_attribute_group" value="<?php echo( !empty( $group[ 'name' ] ) ? $group[ 'name' ] : "" ); ?>"/>
        <input type="hidden" class="wpp_group_slug" name="wpp_settings[property_stats_groups][<?php echo $slug; ?>]" value="<?php echo( !empty( $gslug ) ? $gslug : "" ); ?>">
      </td>

      <td class="wpp_settings_input_col">
        <ul>
          <li>
            <label>
              <input <?php if( in_array( $slug, ( ( !empty( $wp_properties[ 'sortable_attributes' ] ) ? $wp_properties[ 'sortable_attributes' ] : array() ) ) ) ) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[sortable_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Sortable.', 'wpp' ); ?>
            </label>
          </li>
          <li>
            <label>
              <input <?php echo ( isset( $wp_properties[ 'searchable_attributes' ] ) && is_array( $wp_properties[ 'searchable_attributes' ] ) && in_array( $slug, $wp_properties[ 'searchable_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[searchable_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Searchable.', 'wpp' ); ?>
            </label>
          </li>
          <li class="wpp_development_advanced_option">
            <label>
              <input <?php echo ( isset( $wp_properties[ 'hidden_frontend_attributes' ] ) && is_array( $wp_properties[ 'hidden_frontend_attributes' ] ) && in_array( $slug, $wp_properties[ 'hidden_frontend_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[hidden_frontend_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Admin Only.', 'wpp' ); ?>
            </label>
          </li>
          <li class="wpp_development_advanced_option">
            <label>
              <input <?php echo ( isset( $wp_properties[ 'column_attributes' ] ) && is_array( $wp_properties[ 'column_attributes' ] ) && in_array( $slug, $wp_properties[ 'column_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[column_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Add Column on "All Properties" page.', 'wpp' ); ?>
            </label>
          </li>
          <?php do_action( 'wpp::property_attributes::settings', $slug ); ?>
          <li class="wpp_development_advanced_option">
            <span class="wpp_delete_row wpp_link"><?php _e( 'Delete Attribute', 'wpp' ) ?></span>
          </li>
        </ul>
      </td>

      <td class="wpp_search_input_col">
        <ul>
          <li>
            <select name="wpp_settings[searchable_attr_fields][<?php echo $slug; ?>]" class="wpp_pre_defined_value_setter wpp_searchable_attr_fields">
              <option value=""> - </option>
              <option value="input" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'input' ); ?>><?php _e( 'Free Text', 'wpp' ) ?></option>
              <option value="range_input" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'range_input' ); ?>><?php _e( 'Text Input Range', 'wpp' ) ?></option>
              <option value="range_dropdown" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'range_dropdown' ); ?>><?php _e( 'Range Dropdown', 'wpp' ) ?></option>
              <option value="dropdown" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'dropdown' ); ?>><?php _e( 'Dropdown Selection', 'wpp' ) ?></option>
              <option value="checkbox" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'checkbox' ); ?>><?php _e( 'Single Checkbox', 'wpp' ) ?></option>
              <option value="multi_checkbox" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'multi_checkbox' ); ?>><?php _e( 'Multi-Checkbox', 'wpp' ) ?></option>
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
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>