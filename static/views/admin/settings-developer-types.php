<?php
/**
 * Settings 'Developer' Tab
 * Section 'Types'
 */
global $wp_properties;
$filtered_property_types = apply_filters( 'wpp::property_types', $wp_properties[ 'property_types' ] );

$property_type_settings = array();
$hidden_attributes_do_action    = array();
$inherited_attributes_do_action = array();
$property_type_settings_do_action = array();

foreach ($filtered_property_types as $slug => $label) {
  ob_start();
  WPP_F::do_action_deprecated( 'wpp::types::hidden_attributes', array($slug), '3.0.0', 'wpp::settings::developer::types::hidden_attributes', "New action expect underscore template and data should be passed by 'wpp::settings::developer::types' filter." );
  $hidden_attributes_do_action[$slug] = ob_get_clean();

  ob_start();
  WPP_F::do_action_deprecated( 'wpp::types::inherited_attributes', array($slug), '3.0.0', 'wpp::settings::developer::types::inherited_attributes', "New action expect underscore template and data should be passed by 'wpp::settings::developer::types' filter." );
  $inherited_attributes_do_action[$slug] = ob_get_clean();

  $property_type_settings_do_action[$slug] = WPP_F::apply_filters_deprecated( 'wpp_property_type_settings', array(array(), $slug), '3.0.0', "New action expect underscore template and data should be passed by 'wpp::settings::developer::types' filter." );
}

$hidden_attributes_do_action    = array_filter($hidden_attributes_do_action);
$inherited_attributes_do_action = array_filter($inherited_attributes_do_action);
$property_type_settings_do_action = array_filter($property_type_settings_do_action);

$wpp_property_types_variables = apply_filters( 'wpp::settings::developer::types', array(
    'globals'                           => array(),
    'filtered_property_types'           => $filtered_property_types,
    'hidden_attributes_do_action'       => $hidden_attributes_do_action,
    'inherited_attributes_do_action'    => $inherited_attributes_do_action,
    'property_type_settings_do_action'  => $property_type_settings_do_action,
  ) );

?>

<script type="text/template" id="wpp-property-types-variables">
  <?php echo json_encode($wpp_property_types_variables);?>
</script>
<h3><?php printf( __( '%1s Types', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></h3>
<table id="wpp_inquiry_property_types" class="<?php echo apply_filters( 'wpp::css::wpp_inquiry_property_types::classes', 'ud_ui_dynamic_table widefat last_delete_row' ); ?>" allow_random_slug="true">
  <thead>
  <tr>
    <th><?php _e( 'Type', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Default Image', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Settings', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Hidden Attributes', ud_get_wp_property()->domain ) ?></th>
    <th><?php _e( 'Inherit from Parent', ud_get_wp_property()->domain ) ?></th>
  </tr>
  </thead>
  <tbody></tbody>
  <tfoot>
  <tr>
    <td colspan='5'>
      <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', ud_get_wp_property()->domain ) ?>"/>
    </td>
  </tr>
  </tfoot>

</table>


<script type="text/template" id="settings-developer-types-template">
  
    <tr >

      <td>
        <ul>
          <li><input class="slug_setter" type="text" name="wpp_settings[property_types][{{= property_slug }}]" value="{{= label }}"/></li>
          <li><input type="text" class="slug" readonly='readonly' value="{{= property_slug }}"/></li>
          <li><span class="wpp_delete_row wpp_link">Delete</span></li>
        </ul>
      </td>

      <td>
        <div class="upload-image-section">
          <input type="hidden" name="wpp_settings[configuration][default_image][types][{{= property_slug }}][url]" class="input-image-url" value="{{= __.get(wp_properties, ['configuration', 'default_image', 'types', property_slug, 'url'], '') }}">
          <input type="hidden" name="wpp_settings[configuration][default_image][types][{{= property_slug }}][id]" class="input-image-id" value="{{= __.get(wp_properties, ['configuration', 'default_image', 'types', property_slug, 'id'], '') }}">
          <div class="image-actions">
            <input type="button" class="button-secondary button-setup-image" value="<?php _e( 'Setup Image', ud_get_wp_property('domain') ); ?>" title="<?php printf( __( 'If %1$s has no any image, the default one based on %1$s Type will be shown.', ud_get_wp_property('domain') ), \WPP_F::property_label() ); ?>">
          </div>
          <div class="image-wrapper">
            {{ if( __.get(wp_properties, ['configuration', 'default_image', 'types', property_slug, 'url'], '')) { }}
              <img src="{{= __.get(wp_properties, ['configuration', 'default_image', 'types', property_slug, 'url'], '') }}" alt="" />
            {{ } }}
          </div>
        </div>
      </td>

      <td>
        <ul>
          <li>
            <label for="{{= property_slug }}_searchable_property_types">
              <input class="slug wpp_no_change_name" id="{{= property_slug }}_searchable_property_types" {{= _.wppChecked(wp_properties, 'searchable_property_types', property_slug) }} type="checkbox" name="wpp_settings[searchable_property_types][]" value="{{= property_slug }}"/>
              <?php _e( 'Searchable', ud_get_wp_property()->domain ) ?>
            </label>
          </li>

          <li>
            <label for="{{= property_slug }}_location_matters">
              <input class="slug wpp_no_change_name" id="{{= property_slug }}_location_matters"  {{= _.wppChecked(wp_properties, 'location_matters', property_slug) }} type="checkbox" name="wpp_settings[location_matters][]" value="{{= property_slug }}"/>
              <?php _e( 'Location Matters', ud_get_wp_property()->domain ) ?>
            </label>
          </li>

          <li>
            <label>
              <input class="slug wpp_no_change_name" {{= _.wppChecked(wp_properties, 'type_supports_hierarchy', property_slug) }} type="checkbox" name="wpp_settings[type_supports_hierarchy][]" value="{{= property_slug }}"/>
              <?php _e( 'Supports Hiearchy', ud_get_wp_property()->domain ) ?>
            </label>
          </li>

          <?php $property_type_settings = apply_filters( 'wpp::settings::developer::types::settings', array()); ?>
          <?php foreach( (array) $property_type_settings as $property_type_setting ) : ?>
            <li>
              <?php echo $property_type_setting; ?>
            </li>
          <?php endforeach; ?>

          {{ 
            jQuery.each(__.get(property_type_settings_do_action, property_slug, []), function(index, property_type_setting){
              print('<li>');
                print(property_type_setting);
              print('</li>');
            });
          }}
        </ul>
      </td>

      <td>
        <ul class="wp-tab-panel wpp_hidden_property_attributes wpp_something_advanced_wrapper">

          <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', ud_get_wp_property()->domain ); ?></li>

          {{ jQuery.each( __.get(wp_properties, 'property_stats', []), function(property_stat_slug, property_stat_label ){ }}
            <li class="wpp_development_advanced_option">
              <input id="{{ print( property_slug + "_" + property_stat_slug) }}_hidden_attributes" {{= _.wppChecked(wp_properties, ['hidden_attributes', property_slug], property_stat_slug) }} type="checkbox" name="wpp_settings[hidden_attributes][{{= property_slug }}][]" value="{{= property_stat_slug }}"/>
              <label for="{{ print( property_slug + "_" + property_stat_slug) }}_hidden_attributes">
                {{= property_stat_label }}
              </label>
            </li>
          {{ }); }}

          {{ jQuery.each( __.get(wp_properties, 'property_meta', []), function(property_meta_slug, property_meta_label ){ }}
            <li class="wpp_development_advanced_option">
              <input id="{{ print( property_slug + "_" + property_meta_slug) }}_hidden_attributes" {{= _.wppChecked(wp_properties, ['hidden_attributes', property_slug], property_meta_slug) }} type="checkbox" name="wpp_settings[hidden_attributes][{{= property_slug }}][]" value="{{= property_meta_slug }}"/>
              <label for="{{ print( property_slug + "_" + property_meta_slug) }}_hidden_attributes">
                {{= property_meta_label }}
              </label>
            </li>
          {{ }); }}

          {{ if( !__.get(wp_properties, 'property_stats.parent', false) ){ }}
            <li class="wpp_development_advanced_option">
              <input id="{{= property_slug }}parent_hidden_attributes" {{= _.wppChecked(wp_properties, ['hidden_attributes', property_slug], 'parent') }}type="checkbox" name="wpp_settings[hidden_attributes][{{= property_slug }}][]" value="parent"/>
              <label for="{{= property_slug }}parent_hidden_attributes"><?php _e( 'Parent Selection', ud_get_wp_property()->domain ); ?></label>
            </li>
          {{ } }}
          {{= __.get(hidden_attributes_do_action, property_slug, '') }}
          <?php do_action( 'wpp::settings::developer::types::hidden_attributes'); /* The action should output underscore template. Template should pass variable to filter "wpp::settings::developer::types" to use in template or global wp_properties is available.*/ ?>
        </ul>
      </td>

      <td>
        <ul class="wp-tab-panel wpp_inherited_property_attributes wpp_something_advanced_wrapper">
          <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', ud_get_wp_property()->domain ); ?></li>

          {{ jQuery.each( __.get(wp_properties, 'property_stats', []), function(property_stat_slug, property_stat_label ){ }}
            <li class="wpp_development_advanced_option">
              <input id="{{ print( property_slug + "_" + property_stat_slug) }}_inheritance" {{= _.wppChecked(wp_properties, ['property_inheritance', property_slug], property_stat_slug) }} type="checkbox" name="wpp_settings[property_inheritance][{{= property_slug }}][]" value="{{= property_stat_slug }}"/>
              <label for="{{ print( property_slug + "_" + property_stat_slug) }}_inheritance">
                {{= property_stat_label }}
              </label>
            </li>
          {{ }); }}
          {{= __.get(inherited_attributes_do_action, property_slug, '') }}
        <?php do_action( 'wpp::settings::developer::types::inherited_attributes'); /* The action should output underscore template. Template should pass variable to filter "wpp::settings::developer::types" to use in template or global wp_properties is available.*/ ?>
        </ul>
      </td>

    </tr>
</script>