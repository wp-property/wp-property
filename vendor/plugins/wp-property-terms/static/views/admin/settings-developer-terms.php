<?php
/**
 * Settings 'Developer' Tab
 * Section 'Terms'
 */

wp_enqueue_script( 'wpp-terms-settings', ud_get_wpp_terms()->path( '/static/scripts/wpp.terms.settings.js', 'url' ), array( 'wp-property-admin-settings' ) );
wp_enqueue_style( 'wpp-terms-settings', ud_get_wpp_terms()->path( '/static/styles/wpp.terms.settings.css', 'url' ) );

?>
<div>
  <h3 style="float:left;"><?php printf( __( '%1s Terms', ud_get_wpp_terms()->domain ), \WPP_F::property_label() ); ?></h3>
  <div class="wpp_property_stat_functions">
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="expand" value="<?php _e( 'Expand all', 'wpp' ) ?>" />
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="collapse" value="<?php _e( 'Collapse all', 'wpp' ) ?>" />
    <input type="button" class="sort_stats_by_groups button-secondary" value="<?php _e( 'Sort by Groups', 'wpp' ) ?>"/>
  </div>
  <div class="clear"></div>
</div>

<p style="margin-top: 0;"><?php printf( __( 'Manage your %s Taxonomies here. Note, you can not remove all taxonomies, in this case default WP-Property taxonomies will be returned back.', ud_get_wpp_terms()->domain ), WPP_F::property_label() ); ?></p>

<p><?php printf(__( 'Note, taxonomies length limit is 32 characters.', ud_get_wpp_terms()->domain ));?></p>
<table id="" class="wpp_sortable wpp_inquiry_attribute_fields ud_ui_dynamic_table widefat">
  <thead>
  <tr>
    <th class='wpp_draggable_handle'>&nbsp;</th>
    <th class='wpp_attribute_name_col'><?php _e( 'Label', ud_get_wpp_terms()->domain ) ?></th>
    <th class='wpp_attribute_name_col'><?php _e( 'Type', ud_get_wpp_terms()->domain ) ?></th>
    <th class='wpp_attribute_group_col'><?php _e( 'Group', ud_get_wpp_terms()->domain ) ?></th>
    <th class='wpp_settings_col'><?php _e( 'Settings', ud_get_wpp_terms()->domain ) ?></th>
    <th class='wpp_delete_col'>&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?php $search_input = apply_filters( 'wpp::terms::search_input_fields', array( 'dropdown' => __( 'Dropdown Selection', ud_get_wpp_terms()->domain ), 'multi_checkbox' => __( 'Multi-Checkbox', ud_get_wpp_terms()->domain ) ) ); ?>
  <?php foreach( (array) ud_get_wpp_terms( 'config.taxonomies', array() ) as $slug => $data ): ?>
    <?php

    $data = ud_get_wpp_terms()->prepare_taxonomy( $data, $slug );
    $gslug = ud_get_wpp_terms( "config.groups.{$slug}" );
    $group = ud_get_wp_property( "property_groups.{$gslug}" );
    $current_search_input = ud_get_wp_property( "searchable_attr_fields.{$slug}", false );

    ?>
    <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" <?php echo( !empty( $gslug ) ? "wpp_attribute_group=\"" . $gslug . "\"" : "" ); ?> style="<?php echo( !empty( $group[ 'color' ] ) ? "background-color:" . $group[ 'color' ] : "" ); ?>" slug="<?php echo $slug; ?>" new_row='false'>
      <th class='wpp_draggable_handle'>&nbsp;</th>
      <td>
        <ul>
          <li>
            <input class="slug_setter" type="text" name="wpp_terms[taxonomies][<?php echo $slug; ?>][label]" value="<?php echo $data['label']; ?>" maxlength='32'/>
          </li>
          <li class="wpp_development_advanced_option">
            <input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>"/>
          </li>
          <li class="hide-on-new-row">
            <a href="<?php echo admin_url( "edit-tags.php?taxonomy={$slug}&post_type=property" ); ?>"><?php _e( 'Manage Terms', ud_get_wpp_terms()->domain ); ?></a>
          </li>
          <li>
            <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Settings', ud_get_wpp_terms()->domain ); ?></span>
          </li>
        </ul>
      </td>

      <td>
        <select class="wpp-terms-type-selector" name="wpp_terms[types][<?php echo $slug; ?>]">
          <?php foreach( ud_get_wpp_terms( 'types', array() ) as $k => $type ) : ?>
            <option value="<?php echo $k ?>" <?php echo selected( $k, ud_get_wpp_terms( "config.types.{$slug}" ) ) ?> data-desc="<?php echo $type[ 'desc' ]; ?>" ><?php echo $type[ 'label' ]; ?></option>
          <?php endforeach; ?>
        </select>
      </td>

      <td class="wpp_attribute_group_col">
        <input type="text" class="wpp_attribute_group wpp_taxonomy_group wpp_group" value="<?php echo( !empty( $group[ 'name' ] ) ? $group[ 'name' ] : "" ); ?>"/>
        <input type="hidden" class="wpp_group_slug" name="wpp_terms[groups][<?php echo $slug; ?>]" value="<?php echo( !empty( $gslug ) ? $gslug : "" ); ?>">
      </td>

      <td>
        <ul>
          <li class="wpp_development_advanced_option">
            <label><?php _e( 'Rewrite Slug', ud_get_wpp_terms()->domain ); ?> <input type="text" name="wpp_terms[taxonomies][<?php echo $slug; ?>][rewrite][slug]" value="<?php echo !empty( $data['rewrite']['slug'] ) ? $data['rewrite']['slug'] : $slug; ?>" maxlength='32'/></label>
          </li>
          <?php if( !empty( $search_input ) && is_array( $search_input ) ) : ?>
          <li class="wpp_development_advanced_option">
            <label><?php _e( 'Search Input', ud_get_wpp_terms()->domain ); ?></label>
              <select name="wpp_settings[searchable_attr_fields][<?php echo $slug; ?>]">
                <?php foreach( $search_input as $k => $v ) : ?>
                  <option value="<?php echo $k ?>" <?php echo ( isset( $current_search_input ) && $current_search_input == $k ? 'selected="selected"' : '' ); ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
          </li>
          <?php endif; ?>

          <li class="">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][public]" <?php checked( $data['public'], true ); ?> value="true"/> <?php _e( 'Public & Searchable', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][hierarchical]" <?php checked( $data['hierarchical'], true ); ?> value="true"/> <?php _e( 'Hierarchical', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][show_in_nav_menus]" <?php checked( $data['show_in_nav_menus'], true ); ?> value="true"/> <?php _e( 'Show in Nav Menus', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][show_tagcloud]" <?php checked( $data['show_tagcloud'], true ); ?> value="true"/> <?php _e( 'Show in Tag Cloud', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][show_in_menu]" <?php checked( $data['show_in_menu'], true ); ?> value="true"/> <?php _e( 'Show in Admin Menu', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][add_native_mtbox]" <?php checked( $data['add_native_mtbox'], true ); ?> value="true"/> <?php _e( 'Add native Meta Box', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][rich_taxonomy]" <?php checked( $data['rich_taxonomy'], true ); ?> value="true"/> <?php _e( 'Add Term Post', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][<?php echo $slug; ?>][admin_searchable]" <?php checked( $data['admin_searchable'], true ); ?> value="true" class="wpp-terms-option-admin_searchable"/> <?php _e( 'Admin Searchable', ud_get_wpp_terms()->domain ); ?></label>
          </li>

        </ul>
      </td>

      <td>
        <span class="wpp_delete_row wpp_link <?php echo (isset($data['readonly']) && $data['readonly'])?"hidden":"";?>"><?php _e( 'Delete', ud_get_wpp_terms()->domain ); ?></span>
      </td>
    </tr>

    <input type="hidden" name="wpp_terms[taxonomies][<?php echo $slug; ?>][default]" value="<?php echo isset($data['default'])?$data['default']:false;?>">
    <input type="hidden" name="wpp_terms[taxonomies][<?php echo $slug; ?>][readonly]" value="<?php echo isset($data['readonly'])?$data['readonly']:false;?>">
    <input type="hidden" name="wpp_terms[taxonomies][<?php echo $slug; ?>][hidden]" value="<?php echo isset($data['hidden'])?$data['hidden']:false;?>">
  <?php endforeach; ?>
  </tbody>

  <tfoot>
  <tr>
    <td colspan="6">
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>
