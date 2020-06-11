<?php
/**
 * Settings 'Developer' Tab
 * Section 'Terms'
 */

wp_enqueue_script( 'wpp-terms-settings', ud_get_wpp_terms()->path( '/static/scripts/wpp.terms.settings.js', 'url' ), array( 'wp-property-admin-settings' ) );
wp_enqueue_style( 'wpp-terms-settings', ud_get_wpp_terms()->path( '/static/styles/wpp.terms.settings.css', 'url' ) );

$_term_config = (array) ud_get_wpp_terms( 'config', array( 'taxonomies' => array() ) );
$_term_types = (array) ud_get_wpp_terms( 'types', array() );

foreach( $_term_config['taxonomies'] as $slug => $data ){
  $_term_config['taxonomies'][$slug] = ud_get_wpp_terms()->prepare_taxonomy( $data, $slug );
}

$search_input = apply_filters( 'wpp::terms::search_input_fields', 
  array( 
    'dropdown'          => __( 'Dropdown Selection', ud_get_wpp_terms()->domain ),
    'multi_checkbox'    => __( 'Multi-Checkbox', ud_get_wpp_terms()->domain ) 
) );

$wpp_property_types_variables = apply_filters( 'wpp::settings::developer::terms', array(
    'globals'               => array(),
    'config'                => $_term_config,
    'types'                 => $_term_types,
    'search_input'          => $search_input,
) );

?>

<script type="text/template" id="wpp-terms-variables">
  <?php echo json_encode($wpp_property_types_variables);?>
</script>

<div>
  <h3 style="float:left;"><?php printf( __( '%1s Taxonomies', ud_get_wpp_terms()->domain ), \WPP_F::property_label() ); ?></h3>
  <div class="wpp_property_stat_functions">
    <input type="button" class="wpp_all_advanced_settings button-secondary" data-action="expand" value="<?php _e( 'Expand all', 'wpp' ) ?>" />
    <input type="button" class="wpp_all_advanced_settings button-secondary" data-action="collapse" value="<?php _e( 'Collapse all', 'wpp' ) ?>" />
    <input type="button" class="sort_stats_by_groups button-secondary" value="<?php _e( 'Sort by Groups', 'wpp' ) ?>"/>
  </div>
  <div class="clear"></div>
</div>

<p style="margin-top: 0;"><?php printf( __( 'Manage your %s Taxonomies here. Note, you can not remove all taxonomies, in this case default WP-Property taxonomies will be returned back.', ud_get_wpp_terms()->domain ), WPP_F::property_label() ); ?></p>

<table id="wpp_inquiry_property_terms" class="wpp_sortable wpp_inquiry_attribute_fields ud_ui_dynamic_table widefat last_delete_row">
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
  </tbody>

  <tfoot>
  <tr>
    <td colspan="6">
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>

<script type="text/template" id="settings-developer-terms-template">
    <tr>
      <th class='wpp_draggable_handle'>&nbsp;</th>
      <td>
        <ul>
          <li>
            <input class="slug_setter" type="text" name="wpp_terms[taxonomies][{{= slug }}][label]" value="{{= __.get(data, 'label', '') }}" maxlength='32'/>
          </li>
          <li class="wpp_development_advanced_option">
            <input type="text" class="slug" readonly='readonly' value="{{= slug }}"/>

            <?php do_action( "wpp::settings::developer::terms::item_advanced_options" ); ?>

          </li>
          <li class="hide-on-new-row">
            <a target="_blank" href="<?php echo admin_url( "edit-tags.php?taxonomy={{= slug }}&post_type=property" ); ?>"><?php _e( 'Manage Terms', ud_get_wpp_terms()->domain ); ?></a>
          </li>
          <li>
            <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Settings', ud_get_wpp_terms()->domain ); ?></span>
          </li>
        </ul>
      </td>

      <td>
        {{ if(__.get(data, 'readonly', false)){ }}
          <input type="hidden" class="wpp_group_slug" name="wpp_terms[types][{{= slug }}]" value="{{= __.get(config, ['types', slug], '') }}">
        {{ } }}
        <select class="wpp-terms-type-selector" name="wpp_terms[types][{{= slug }}]" {{ if(__.get(data, 'readonly', false)) print('disabled = "disabled"')}}>
          {{ jQuery.each(types, function(key, type ){ }}
            <option value="{{= key }}" {{= _.wppSelected(key, __.get(config, ['types', slug], '')) }} data-desc="{{= __.get(type, 'desc', '') }}" >{{= __.get(type, 'label', '') }}</option>
          {{ }); }}
        </select>
      </td>

      <td class="wpp_attribute_group_col">
        <input type="text" class="wpp_attribute_group wpp_taxonomy_group wpp_group" value="{{= __.get(group, 'name', '') }}"/>
        <input type="hidden" class="wpp_group_slug" name="wpp_terms[groups][{{= slug }}]" value="{{= gslug }}">
      </td>

      <td>
        <ul>
          <li class="wpp_development_advanced_option">
            <label><?php _e( 'Rewrite Slug', ud_get_wpp_terms()->domain ); ?> <input type="text" name="wpp_terms[taxonomies][{{= slug }}][rewrite][slug]" value="{{= rewriteSlug }}" maxlength='32'/></label>
          </li>
          {{ if( search_input) { }}
          <li class="wpp_development_advanced_option">
            <label><?php _e( 'Search Input', ud_get_wpp_terms()->domain ); ?></label>
              <select name="wpp_settings[searchable_attr_fields][{{= slug }}]">
                {{ jQuery.each(search_input, function(k, v ){ }}
                  <option value="{{= k }}" {{= _.wppSelected(k, current_search_input) }}>{{= v }}</option>
                {{ }); }}
              </select>
          </li>
          {{ } }}

          <li class="">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][public]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'public', false) }} value="true"/> <?php _e( 'Public & Searchable', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][hierarchical]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'hierarchical', false) }} value="true"/> <?php _e( 'Hierarchical', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][show_in_nav_menus]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'show_in_nav_menus', false) }} value="true"/> <?php _e( 'Show in Nav Menus', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][show_tagcloud]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'show_tagcloud', false) }} value="true"/> <?php _e( 'Show in Tag Cloud', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][show_in_menu]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'show_in_menu', false) }} value="true"/> <?php _e( 'Show in Admin Menu', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][add_native_mtbox]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'add_native_mtbox', false) }} value="true"/> <?php _e( 'Add native Meta Box', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][rich_taxonomy]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'rich_taxonomy', false) }} value="true"/> <?php _e( 'Add Term Post', ud_get_wpp_terms()->domain ); ?></label>
          </li>

          <li class="wpp_development_advanced_option">
            <label><input type="checkbox" name="wpp_terms[taxonomies][{{= slug }}][admin_searchable]" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}} {{= _.wppChecked(data, 'admin_searchable', false) }} value="true" class="wpp-terms-option-admin_searchable"/> <?php _e( 'Admin Searchable', ud_get_wpp_terms()->domain ); ?></label>
          </li>

        </ul>
      </td>

      <td>
        <button class="wpp_delete_row button {{ if(__.get(data, 'system', false)) print('disabled'); }}" {{ if(__.get(data, 'system', false)) print('disabled = "disabled"');}}><?php _e( 'Delete', ud_get_wpp_terms()->domain ); ?></button>
        <input type="hidden" name="wpp_terms[taxonomies][{{= slug }}][default]" value="{{= __.get(data, 'default', '') }}">
        <input type="hidden" name="wpp_terms[taxonomies][{{= slug }}][readonly]" value="{{= __.get(data, 'readonly', '') }}">
        <input type="hidden" name="wpp_terms[taxonomies][{{= slug }}][hidden]" value="{{= __.get(data, 'hidden', '') }}">
        <input type="hidden" name="wpp_terms[taxonomies][{{= slug }}][system]" value="{{= __.get(data, 'system', false) }}">
        <input type="hidden" name="wpp_terms[taxonomies][{{= slug }}][meta]" value="{{= __.get(data, 'meta', '') }}">
      </td>
    </tr>

</script>