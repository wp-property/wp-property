<?php
/**
 * Settings 'Developer' Tab
 * Section 'Attributes'
 */

global $wp_properties;
$attributes_default = ud_get_wp_property()->get('attributes.default');
$attributes_multiple = ud_get_wp_property()->get('attributes.multiple');
$predefined_values = isset( $wp_properties[ 'predefined_values' ] ) ? $wp_properties[ 'predefined_values' ]: ''; 

$attributes = ud_get_wp_property()->get('property_stats');

$searchable_attr_fields_options = array(
        ''                        => __( ' - ', ud_get_wp_property()->domain ),
        'input'                   => __( 'Free Text', ud_get_wp_property()->domain ),
        'range_input'             => __( 'Text Input Range', ud_get_wp_property()->domain ),
        'range_dropdown'          => __( 'Range Dropdown', ud_get_wp_property()->domain ),
        'advanced_range_dropdown' => __( 'Advanced Range Dropdown', ud_get_wp_property()->domain ),
        'dropdown'                => __( 'Dropdown Selection', ud_get_wp_property()->domain ),
        'checkbox'                => __( 'Single Checkbox', ud_get_wp_property()->domain ),
        'multi_checkbox'          => __( 'Multi-Checkbox', ud_get_wp_property()->domain ),
        'range_date'              => __( 'Date Input Range', ud_get_wp_property()->domain ),
      );
$searchable_attr_field_do_action = array();
$admin_attr_field_do_action = array();
$attribute_name_do_action = array();
$settings_do_action = array();
$filtered_field_alias = array();
foreach ($attributes as $slug => $label) {
  ob_start();
  do_action( 'wpp::property_attributes::searchable_attr_field', $slug );
  $searchable_attr_field_do_action[$slug] = ob_get_clean();

  ob_start();
  do_action( 'wpp::property_attributes::admin_attr_field', $slug );
  $admin_attr_field_do_action[$slug] = ob_get_clean();

  ob_start();
  do_action( 'wpp::property_attributes::attribute_name', $slug );
  $attribute_name_do_action[$slug] = ob_get_clean();

  ob_start();
  do_action( 'wpp::property_attributes::settings', $slug );
  $settings_do_action[$slug] = ob_get_clean();
}

$searchable_attr_field_do_action  = array_filter($searchable_attr_field_do_action);
$admin_attr_field_do_action       = array_filter($admin_attr_field_do_action);
$attribute_name_do_action         = array_filter($attribute_name_do_action);
$settings_do_action               = array_filter($settings_do_action);

$meta_box_fields = ud_get_wp_property('attributes.types', array());

$wpp_property_attributes_variables = apply_filters( 'wpp::settings::developer::attributes', array(
  'searchable_attr_fields_options'  => $searchable_attr_fields_options,
  'searchable_attr_field_do_action' => $searchable_attr_field_do_action,
  'admin_attr_field_do_action'      => $admin_attr_field_do_action,
  'attribute_name_do_action'        => $attribute_name_do_action,
  'settings_do_action'              => $settings_do_action,
  'meta_box_fields'                 => $meta_box_fields,
  'settings_do_action'              => $filtered_field_alias
) );


?>
<script type="text/template" id="wpp-attributes-variables">
  <?php echo json_encode($wpp_property_attributes_variables);?>
</script>

<script type="text/template" id="settings-developer-attributes-template">

    <tr>

      <td class="wpp_draggable_handle">&nbsp;</td>

      <td class="wpp_attribute_name_col">
        <ul class="wpp_attribute_name">
          <li>
            <input class="slug_setter" type="text" name="wpp_settings[property_stats][{{= slug }}]" value="{{= __.get(wp_properties, ['property_stats', slug], '') }}"/>
          </li>
          <li class="wpp_development_advanced_option">

            <label class="wpp-mmeta-slug-entry">
              <input type="text" class="slug wpp_stats_slug_field" readonly='readonly' value="{{= slug }}"/>
            </label>

            <?php do_action( "wpp::settings::developer::attributes::item_advanced_options" ); ?>

            {{ if( jQuery.inArray(slug, __.get(wp_properties, 'geo_type_attributes', [])) != -1){ }}
              <div class="wpp_notice">
                <span><?php _e( 'Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automaticaly and can not be edited on Property Adding/Updating page.', ud_get_wp_property()->domain ); ?></span>
              </div>
            {{ } }}
            {{ if(slug == "ID"){ }} <?php // for ID field: show a notice to the user about the field being non-editable @raj (22/07/2016) ?>
              <div class="wpp_notice">
                <span><?php _e( 'Note! This attribute (slug) is predefined and used by WP-Property. You can not remove it or change it.', ud_get_wp_property()->domain ); ?></span>
              </div>
            {{ } }}
          <?php
          // BEGIN : code for standard attributes
          if( defined( 'WP_PROPERTY_FLAG_ENABLE_STANDARD_ATTRIBUTES_MATCHING' ) && WP_PROPERTY_FLAG_ENABLE_STANDARD_ATTRIBUTES_MATCHING && isset($wp_properties[ 'configuration' ][ 'show_advanced_options' ]) && $wp_properties[ 'configuration' ][ 'show_advanced_options' ] === "true" ) { ?>
            <p class="wpp-std-att-cont">
              <label>
                  <a class="wpp-toggle-std-attr">  <?php _e( 'Match standard attribute', ud_get_wp_property()->domain ); ?></a>
              </label>
            </p>
            <?php
            if(count($wp_properties[ 'prop_std_att' ]) || 
                (isset( $wp_properties[ 'configuration' ]['address_attribute']) && !empty($wp_properties[ 'configuration' ]['address_attribute']))){
            ?>
              
             <div  class='std-attr-mapper'>
              <select  name='wpp_settings[prop_std_att_mapsto][{{= slug }}]' id="wpp_prop_std_att_mapsto_{{= slug }}" class=' wpp_settings-prop_std_att_mapsto'><option value=''> - </option>

              {{ _.each( __.get(wp_properties, 'prop_std_att', []), function(std_attr_type){}}
                {{ _.each(std_attr_type, function(std_val, std_key){}}
                  <option value="{{= std_key }}" 
                    data-notice="{{= __.get(std_val, 'notice', '') }}"
                    <?php
                    // check if the attribute type is "address" from legacy system  @raj
                    ?>
                    {{ if ( slug == __.get(wp_properties, 'configuration.address_attribute', '') ){
                       print(_.wppSelected(  std_key,'address'));
                    }
                    }} 
                    <?php
                     // if the user has updated to new standard attributes then this is the one we select
                    ?>
                    {{= _.wppSelected( __.get(wp_properties, ['prop_std_att_mapsto', slug], ''), std_key ) }} 
                   > 
                    {{= __.get(std_val, 'label', '') }}
                  </option>
                {{ }); }}
              {{ }); }}
              </select>
              <i class='std_att_notices'></i>
              </div>
            <?php
            }// end $wp_properties[ 'prop_std_att' ]
          }
          // END : code for standard attributes
          ?>
                  
          </li>
          {{= __.get(attribute_name_do_action, slug, '') }}
          <li>
            <span class="wpp_show_advanced"><?php _e( 'Toggle Advanced Settings', ud_get_wp_property()->domain ); ?></span>
          </li>
        </ul>
      </td>

      <td class="wpp_attribute_group_col">
        <input type="text" class="wpp_attribute_group wpp_group" value="{{= __.get(group, 'name', '') }}"/>
        <input type="hidden" class="wpp_group_slug" name="wpp_settings[property_stats_groups][{{= slug }}]" value="{{= gslug }}">
      </td>

      <td class="wpp_settings_input_col">
        <ul>
          <li>
            <label>
              <input {{= _.wppChecked(wp_properties, 'sortable_attributes', slug) }} type="checkbox" class="slug wpp_no_change_name" name="wpp_settings[sortable_attributes][]" value="{{= slug }}"/>
              <?php _e( 'Sortable.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li>
            <label>
              <input {{= _.wppChecked(wp_properties, 'searchable_attributes', slug) }} type="checkbox" class="slug wpp_no_change_name" name="wpp_settings[searchable_attributes][]" value="{{= slug }}"/>
              <?php _e( 'Searchable.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li class="wpp_development_advanced_option">
            <label>
              <input {{= _.wppChecked(wp_properties, 'hidden_frontend_attributes', slug) }}  type="checkbox" class="slug wpp_no_change_name" name="wpp_settings[hidden_frontend_attributes][]" value="{{= slug }}"/>
              <?php _e( 'Admin only.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li class="wpp-setting wpp_development_advanced_option wpp-setting-attribute-admin-sortable">
            <label>
              <input {{= _.wppChecked(wp_properties, 'column_attributes', slug) }} type="checkbox" class="slug wpp_no_change_name" name="wpp_settings[column_attributes][]" value="{{= slug }}"/>
              <?php _e( 'Admin sortable.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li class="wpp_development_advanced_option en_default_value_container">
            <label>
              <input {{= _.wppChecked(wp_properties, 'en_default_value', slug) }} type="checkbox" class="slug wpp_no_change_name en_default_value" name="wpp_settings[en_default_value][]" value="{{= slug }}"/>
              <?php _e( 'Set default value.', ud_get_wp_property()->domain ); ?>
            </label>
          
          </li>
          {{= __.get(settings_do_action, slug, '') }}
          <li class="wpp_development_advanced_option">
            <span class="wpp_delete_row wpp_link"><?php _e( 'Delete Attribute', ud_get_wp_property()->domain ) ?></span>
          </li>
        </ul>
      </td>

      <td class="wpp_search_input_col">
        <ul>
          <li>
            <select name="wpp_settings[searchable_attr_fields][{{= slug }}]" class="wpp_pre_defined_value_setter wpp_searchable_attr_fields">
              {{ _.each(searchable_attr_fields_options, function(label, key){ }}
                <option value="{{= key }}" {{= _.wppSelected( __.get(wp_properties, ['searchable_attr_fields', slug], ''), key ) }}>{{= label }}</option>
              {{ }); }}
              {{= __.get(searchable_attr_field_do_action, slug, '') }}
            </select>
          </li>
          <li>
            <textarea class="wpp_attribute_pre_defined_values" name="wpp_settings[predefined_search_values][{{= slug }}]">{{= __.get(wp_properties, ['predefined_search_values', slug], '') }}</textarea>
          </li>
        </ul>
      </td>

      <td class="wpp_admin_input_col">
        <ul>
          <li>
            <select name="wpp_settings[admin_attr_fields][{{= slug }}]" class="wpp_pre_defined_value_setter wpp_default_value_setter wpp_searchable_attr_fields">
              {{ _.each( meta_box_fields, function(label, key){ }}
                <option value="{{= key }}" {{= _.wppSelected( __.get(wp_properties, ['admin_attr_fields', slug], ''), key) }}>{{= label }}</option>
              {{ }); }}
              {{= __.get(admin_attr_field_do_action, slug, '') }}
            </select>
          </li>
          <li>
            <textarea class="wpp_attribute_pre_defined_values" name="wpp_settings[predefined_values][{{= slug }}]">{{= __.get(wp_properties, ['predefined_values', slug], '') }}</textarea>
          </li>
          <li class="wpp_attribute_default_values {{= jQuery.inArray(slug, __.get(wp_properties, 'en_default_value', [])) != -1? 'show':'hidden' }}">
            <?php
            echo __("<label>Default Value</label>", ud_get_wp_property()->domain);
            echo "<br />";
            echo "<div class='default_value_container' data-name='wpp_settings[default_values][{{= slug }}]' data-value='{{= __.get(wp_properties, ['default_values', slug], '') }}' ></div>";
            ?>
            <a class="button apply-to-all" data-attribute="{{= slug }}" href="#" title="<?php _e("Apply to listings that have no value for this field.", ud_get_wp_property()->domain);?>" ><?php _e("Apply to all", ud_get_wp_property()->domain);?></a> <br/>
          </li>
        </ul>
      </td>
    </tr>
  
</script>
<div>
  <h3 style="float:left;"><?php printf( __( '%1s Attributes', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></h3>
  <div class="wpp_property_stat_functions">
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="expand" value="<?php _e( 'Expand all', ud_get_wp_property()->domain ) ?>" />
    <input type="button" class="wpp_all_advanced_settings button-secondary" action="collapse" value="<?php _e( 'Collapse all', ud_get_wp_property()->domain ) ?>" />
    <input type="button" class="sort_stats_by_groups button-secondary" value="<?php _e( 'Sort by Groups', ud_get_wp_property()->domain ) ?>"/>
  </div>
  <div class="clear"></div>
</div>

<table id="wpp_inquiry_attribute_fields" class="wpp_inquiry_attribute_fields ud_ui_dynamic_table widefat last_delete_row" allow_random_slug="true">
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
  </tbody>

  <tfoot>
  <tr>
    <td colspan='6'>
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', ud_get_wp_property()->domain ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>
