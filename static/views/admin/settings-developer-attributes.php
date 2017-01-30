<?php
/**
 * Settings 'Developer' Tab
 * Section 'Attributes'
 */

global $wp_properties;
$attributes_default = ud_get_wp_property()->get('attributes.default');
$attributes_multiple = ud_get_wp_property()->get('attributes.multiple');
$predefined_values = isset( $wp_properties[ 'predefined_values' ] ) ? $wp_properties[ 'predefined_values' ]: ''; 
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
  <?php 
  
  if(empty($wp_properties[ 'property_stats' ])){
	  $wp_properties[ 'property_stats' ] = array("" => "");
  }
  foreach( $wp_properties[ 'property_stats' ] as $slug => $label ): ?>
    <?php $gslug = false; ?>
    <?php $group = false; ?>
    <?php if( !empty( $wp_properties[ 'property_stats_groups' ][ $slug ] ) ) : ?>
      <?php $gslug = $wp_properties[ 'property_stats_groups' ][ $slug ]; ?>
      <?php $group = $wp_properties[ 'property_groups' ][ $gslug ]; ?>
    <?php endif; 
		
	?>
    <tr class="wpp_dynamic_table_row" <?php echo( !empty( $gslug ) ? "wpp_attribute_group=\"" . $gslug . "\"" : "" ); ?> style="<?php echo( !empty( $group[ 'color' ] ) ? "background-color:" . $group[ 'color' ] : "" ); echo( empty( $slug ) ? "display:none;" : ""); ?>" slug="<?php echo $slug; ?>" new_row='false'>

      <td class="wpp_draggable_handle">&nbsp;</td>

      <td class="wpp_attribute_name_col">
        <ul class="wpp_attribute_name">
          <li>
            <input class="slug_setter" type="text" name="wpp_settings[property_stats][<?php echo $slug; ?>]" value="<?php echo $label; ?>"/>
          </li>
          <li class="wpp_development_advanced_option">

            <label class="wpp-mmeta-slug-entry">
              <input type="text" class="slug wpp_stats_slug_field" readonly='readonly' value="<?php echo $slug; ?>"/>
            </label>

            <?php if( defined( 'WP_PROPERTY_FIELD_ALIAS' ) && WP_PROPERTY_FIELD_ALIAS ) { ?>
            <label class="wpp-meta-alias-entry">
              <input type="text" class="slug wpp_field_alias" name="wpp_settings[field_alias][<?php echo $slug; ?>]" placeholder="<?php echo $slug; ?>" value="<?php echo WPP_F::get_alias_map( $slug ) ; ?>" />
            </label>
            <?php } ?>

            <?php if( in_array( $slug, $wp_properties[ 'geo_type_attributes' ] ) ): ?>
              <div class="wpp_notice">
                <span><?php _e( 'Attention! This attribute (slug) is used by Google Validator and Address Display functionality. It is set automaticaly and can not be edited on Property Adding/Updating page.', ud_get_wp_property()->domain ); ?></span>
              </div>
            <?php endif; ?>
            <?php if($slug=="ID"): // for ID field: show a notice to the user about the field being non-editable @raj (22/07/2016) ?>
              <div class="wpp_notice">
                <span><?php _e( 'Note! This attribute (slug) is predefined and used by WP-Property. You can not remove it or change it.', ud_get_wp_property()->domain ); ?></span>
              </div>
            <?php endif; ?>
          <?php
          // BEGIN : code for standard attributes
          if(isset($wp_properties[ 'configuration' ][ 'show_advanced_options' ]) && $wp_properties[ 'configuration' ][ 'show_advanced_options' ]=="true"){
            // merge std_attr  if need to use all attributes as a single array
//            $merged_std_attributes = array_unique(call_user_func_array('array_merge', $wp_properties[ 'prop_std_att' ]),SORT_REGULAR);
            ?>
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
              <select  name='wpp_settings[prop_std_att_mapsto][<?php echo $slug;?>]' id="wpp_prop_std_att_mapsto_<?php echo $slug;?>" class=' wpp_settings-prop_std_att_mapsto'><option value=''> - </option>
             <?php
              foreach ($wp_properties[ 'prop_std_att' ] as $std_attr_type){
                foreach ($std_attr_type as $std_key => $std_val){
                ?>    
                  <option value="<?php echo $std_key; ?>" 
                    data-notice='<?php  if(isset($std_val['notice']) && !empty($std_val['notice'])) echo $std_val['notice'];?> '
                    <?php
                    // check if the attribute type is "address" from legacy system  @raj
                    if ( $slug == $wp_properties[ 'configuration' ]['address_attribute'] ){
                       selected(  $std_key,'address');
                    }
                     // if the user has updated to new standard attributes then this is the one we select
                     if(isset( $wp_properties[ 'prop_std_att_mapsto' ][ $slug ]) )
                       selected( $wp_properties[ 'prop_std_att_mapsto' ][ $slug ], $std_key ); ?>	
                   > 
                    <?php _e( $std_val['label'], ud_get_wp_property()->domain ) ?>
                  </option>
                  <?php  
                } //end attributes foreach
              } //end attributes-category foreach
              ?>
              </select>
              <i class='std_att_notices'></i>
              </div>
            <?php
            }// end $wp_properties[ 'prop_std_att' ]
          }
          // END : code for standard attributes
          ?>
                  
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
              <?php _e( 'Admin only.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li class="wpp-setting wpp_development_advanced_option wpp-setting-attribute-admin-sortable">
            <label>
              <input <?php echo ( isset( $wp_properties[ 'column_attributes' ] ) && is_array( $wp_properties[ 'column_attributes' ] ) && in_array( $slug, $wp_properties[ 'column_attributes' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug" name="wpp_settings[column_attributes][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Admin sortable.', ud_get_wp_property()->domain ); ?>
            </label>
          </li>
          <li class="wpp_development_advanced_option en_default_value_container">
            <label>
              <input <?php echo ( isset( $wp_properties[ 'en_default_value' ] ) && is_array( $wp_properties[ 'en_default_value' ] ) && in_array( $slug, $wp_properties[ 'en_default_value' ] ) ) ? "CHECKED" : ""; ?> type="checkbox" class="slug en_default_value" name="wpp_settings[en_default_value][]" value="<?php echo $slug; ?>"/>
              <?php _e( 'Set default value.', ud_get_wp_property()->domain ); ?>
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
              <option value="range_date" <?php if( isset( $wp_properties[ 'searchable_attr_fields' ][ $slug ] ) ) selected( $wp_properties[ 'searchable_attr_fields' ][ $slug ], 'range_date' ); ?>><?php _e( 'Date Input Range', ud_get_wp_property()->domain ) ?></option>
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
          <?php $class = (isset( $wp_properties[ 'en_default_value' ] ) && in_array( $slug, $wp_properties[ 'en_default_value' ] ) )? "show":"hidden";?>
          <li class="wpp_attribute_default_values <?php echo $class;?>">
            <?php
            $input_type = isset( $wp_properties[ 'admin_attr_fields' ][ $slug ] ) ? $wp_properties[ 'admin_attr_fields' ][ $slug ] : null;
            $value = (isset( $wp_properties[ 'default_values' ][ $slug ]))? $wp_properties[ 'default_values' ][ $slug ]: "";
            $field_name = "wpp_settings[default_values][$slug]";
            echo __("<label>Default Value</label>", ud_get_wp_property()->domain);
            echo "<br />";
            echo "<div class='default_value_container' data-name='$field_name' data-value='$value' ></div>";
            ?>
            <a class="button apply-to-all" data-attribute="<?php echo $slug;?>" href="#" title="<?php _e("Apply to listings that have no value for this field.", ud_get_wp_property()->domain);?>" ><?php _e("Apply to all", ud_get_wp_property()->domain);?></a> <br/>
          </li>
        </ul>
      </td>
    </tr>
  <?php
	endforeach;
  ?>
  </tbody>

  <tfoot>
  <tr>
    <td colspan='6'>
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', ud_get_wp_property()->domain ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>
