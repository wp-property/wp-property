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

foreach ($filtered_property_types as $slug => $label) {
  ob_start();
  WPP_F::do_action_deprecated( 'wpp::types::hidden_attributes', array($slug), '3.0.0', 'wpp::settings::developer::types::hidden_attributes', "New action expect underscore template and data should be passed by 'wpp::settings::developer::types' filter." );
  $hidden_attributes_do_action[$slug] = ob_get_clean();

  ob_start();
  do_action( 'wpp::types::inherited_attributes', $slug );
  WPP_F::do_action_deprecated( 'wpp::types::inherited_attributes', array($slug), '3.0.0', 'wpp::settings::developer::types::inherited_attributes', "New action expect underscore template and data should be passed by 'wpp::settings::developer::types' filter." );
  $inherited_attributes_do_action[$slug] = ob_get_clean();

  $property_type_settings_do_action[$slug] = WPP_F::apply_filters_deprecated( 'wpp_property_type_settings', array(array(), $slug), '3.0.0', "New action expect underscore template and data should be passed by 'wpp::settings::developer::types' filter." );
}

$hidden_attributes_do_action    = array_filter($hidden_attributes_do_action);
$inherited_attributes_do_action = array_filter($inherited_attributes_do_action);
$property_type_settings_do_action = array_filter($property_type_settings_do_action);


$wpp_property_types_variables = apply_filters( 'wpp::settings::developer::types', array(
    'globals'                           => array(),
    'hidden_attributes_do_action'       => $hidden_attributes_do_action,
    'inherited_attributes_do_action'    => $inherited_attributes_do_action,
    'property_type_settings_do_action'  => $property_type_settings_do_action,
  ) );

?>

<script type="text/javascript">
  
jQuery(document).ready(function($) {
  var wp_properties = wpp.instance.settings;
  var configuration = wp_properties.configuration;
  var supermap_configuration = {};

  if( typeof configuration.feature_settings != 'undefined' && typeof configuration.feature_settings.supermap != 'undefined' ) {
    supermap_configuration = configuration.feature_settings.supermap;
  }

  var filtered_property_types           = <?php echo json_encode($filtered_property_types);?>;
  var hidden_attributes_do_action       = <?php echo json_encode($hidden_attributes_do_action);?>;
  var inherited_attributes_do_action    = <?php echo json_encode($inherited_attributes_do_action);?>;
  var property_type_settings_do_action  = <?php echo json_encode($property_type_settings_do_action);?>;


  var wpp_property_types_variables  = <?php echo json_encode($wpp_property_types_variables);?>;

  jQuery.each(wpp_property_types_variables.globals, function(index, val){
    window[index] = val;
  });
  delete wpp_property_types_variables.globals;

  // Defining property of object wp_properties(if not defined) to avoid checking of typeof != 'undefined' in template
  var requiredProps = [
    'searchable_property_types', 
    'location_matters',
    'hidden_attributes', 
    'property_stats',
    'property_meta', 
  ];
  
  jQuery.each(requiredProps, function(index, item) {
    if(typeof  wp_properties[item] == 'undefined' ) {
      wp_properties[item] = {};
    }
  });

  if(typeof  configuration.default_image == 'undefined' ) {
    configuration.default_image = {};
  }
  if(typeof  configuration.default_image.types == 'undefined' ) {
    configuration.default_image.types = {};
  }

  if(typeof  wp_properties.property_types == 'undefined' ) {
    wp_properties.property_types = {'': ''};
  }

  window.selected = function(selected, current) {
    var result = '';
    current = current || true;

    if ( selected === current )
      result = " selected='selected' ";
 
    return result;
  }
  
  var wppTypes = Backbone.Model.extend({
  });

  var wppTypesCollection = Backbone.Collection.extend({
    model: wppTypes,
  });

  var wppTypesView = Backbone.View.extend({
    tagName: 'tr',
    className: 'wpp_dynamic_table_row',
    attributes: function(){
      return {
        slug: this.model.get('property_slug'),
        'data-property-slug': this.model.get('property_slug'),
        new_row: this.model.get('property_slug') == '' ? true : false,
        style: this.model.get('property_slug') == '' ? "display:none;" : "",
      };
    },
    template: _.template($('#settings-developer-types-template').html()),
    render: function() {
      this.el.innerHTML = this.template(this.model.toJSON());
      return this;
    }
  });



  var wppTypesWrapperView = Backbone.View.extend({
    el: '#wpp_inquiry_property_types tbody',
    render: function() {
      this.collection.each(this.addAttribute.bind(this));
      return this;
    },
    addAttribute: function (model) {
      var row = new wppTypesView({ model: model });
      jQuery(this.el).append(row.render().el);
    },

  });


  var _wppTypes = new wppTypesCollection();

  jQuery.each(filtered_property_types, function(property_slug, label) {

    if(typeof configuration.default_image.types[property_slug] == 'undefined'){
      configuration.default_image.types[property_slug] = {url: '', id: ''}
    }

    var attributes = {
      label                             : label,
      slug                              : property_slug,
      property_slug                     : property_slug,
      wp_properties                     : wp_properties,
      supermap_configuration            : supermap_configuration,
      hidden_attributes_do_action       : hidden_attributes_do_action,
      inherited_attributes_do_action    : inherited_attributes_do_action,
      property_type_settings_do_action  : property_type_settings_do_action,
    };
    jQuery.extend(attributes, wpp_property_types_variables);
    var row = new wppTypes(attributes);
    _wppTypes.add(row);


  });

  var wrapper = new wppTypesWrapperView({ collection: _wppTypes });
  jQuery("#wpp_inquiry_property_types tbody").empty().append(wrapper.render().el);

});


</script>

<h3><?php printf( __( '%1s Types', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></h3>
<table id="wpp_inquiry_property_types" class="ud_ui_dynamic_table widefat last_delete_row" allow_random_slug="true">
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
          <li><input class="slug_setter" type="text" name="wpp_settings[property_types][<%= property_slug %>]" value="<%= label %>"/></li>
          <li><input type="text" class="slug" readonly='readonly' value="<%= property_slug %>"/></li>
          <li><span class="wpp_delete_row wpp_link">Delete</span></li>
        </ul>
      </td>

      <td>
        <div class="upload-image-section">
          <input type="hidden" name="wpp_settings[configuration][default_image][types][<%= property_slug %>][url]" class="input-image-url" value="<%= wp_properties.configuration.default_image.types[property_slug].url %>">
          <input type="hidden" name="wpp_settings[configuration][default_image][types][<%= property_slug %>][id]" class="input-image-id" value="<%= wp_properties.configuration.default_image.types[property_slug].id %>">
          <div class="image-actions">
            <input type="button" class="button-secondary button-setup-image" value="<?php _e( 'Setup Image', ud_get_wp_property('domain') ); ?>" title="<?php printf( __( 'If %1$s has no any image, the default one based on %1$s Type will be shown.', ud_get_wp_property('domain') ), \WPP_F::property_label() ); ?>">
          </div>
          <div class="image-wrapper"></div>
        </div>
      </td>

      <td>
        <ul>
          <li>
            <label for="<%= property_slug %>_searchable_property_types">
              <input class="slug" id="<%= property_slug %>_searchable_property_types" <% if( jQuery.inArray(property_slug, wp_properties.searchable_property_types) != -1){ print( 'CHECKED'); } %> type="checkbox" name="wpp_settings[searchable_property_types][]" value="<%= property_slug %>"/>
              <?php _e( 'Searchable', ud_get_wp_property()->domain ) ?>
            </label>
          </li>

          <li>
            <label for="<%= property_slug %>_location_matters">
              <input class="slug" id="<%= property_slug %>_location_matters"  <% if( jQuery.inArray(property_slug, wp_properties.location_matters) != -1){ print( 'CHECKED'); } %> type="checkbox" name="wpp_settings[location_matters][]" value="<%= property_slug %>"/>
              <?php _e( 'Location Matters', ud_get_wp_property()->domain ) ?>
            </label>
          </li>

          <li>
            <label>
              <input class="slug" <% if( jQuery.inArray(property_slug, wp_properties.type_supports_hierarchy) != -1){ print( 'CHECKED'); } %> type="checkbox" name="wpp_settings[type_supports_hierarchy][]" value="<%= property_slug %>"/>
              <?php _e( 'Supports Hiearchy', ud_get_wp_property()->domain ) ?>
            </label>
          </li>

          <?php do_action( 'wpp::settings::developer::types::settings'); /* The action should output underscore template. Template should pass variable to filter "wpp::settings::developer::types" to use in template or global wp_properties is available.*/ ?>

          <% if( typeof property_type_settings_do_action[property_slug] != 'undefined'){

            jQuery.each(property_type_settings_do_action[ property_slug ], function(index, property_type_setting){
              print('<li>');
                print(property_type_setting);
              print('</li>');
            });

          } %>
        </ul>
      </td>

      <td>
        <ul class="wp-tab-panel wpp_hidden_property_attributes wpp_something_advanced_wrapper">

          <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', ud_get_wp_property()->domain ); ?></li>

          <% jQuery.each( wp_properties.property_stats, function(property_stat_slug, property_stat_label ){ %>
            <li class="wpp_development_advanced_option">
              <input id="<% print( property_slug + "_" + property_stat_slug) %>_hidden_attributes" <% if( typeof wp_properties.hidden_attributes[ property_slug ] != 'undefined' && jQuery.inArray(property_stat_slug, wp_properties.hidden_attributes[ property_slug ]) != -1){ print( 'CHECKED'); } %> type="checkbox" name="wpp_settings[hidden_attributes][<%= property_slug %>][]" value="<%= property_stat_slug %>"/>
              <label for="<% print( property_slug + "_" + property_stat_slug) %>_hidden_attributes">
                <%= property_stat_label %>
              </label>
            </li>
          <% }); %>

          <% jQuery.each( wp_properties.property_meta, function(property_meta_slug, property_meta_label ){ %>
            <li class="wpp_development_advanced_option">
              <input id="<% print( property_slug + "_" + property_meta_slug) %>_hidden_attributes" <% if( typeof wp_properties.hidden_attributes[ property_slug ] != 'undefined' && jQuery.inArray(property_meta_slug, wp_properties.hidden_attributes[ property_slug ]) != -1){ print( 'CHECKED'); } %> type="checkbox" name="wpp_settings[hidden_attributes][<%= property_slug %>][]" value="<%= property_meta_slug %>"/>
              <label for="<% print( property_slug + "_" + property_meta_slug) %>_hidden_attributes">
                <%= property_meta_label %>
              </label>
            </li>
          <% }); %>

          <% if( typeof wp_properties.property_stats[ 'parent' ] == 'undefined'){ %>
            <li class="wpp_development_advanced_option">
              <input id="<%= property_slug %>parent_hidden_attributes" <% if( typeof wp_properties.hidden_attributes[ property_slug ] != 'undefined' && jQuery.inArray('parent', wp_properties.hidden_attributes[ property_slug ]) != -1){ print( 'CHECKED'); } %>type="checkbox" name="wpp_settings[hidden_attributes][<%= property_slug %>][]" value="parent"/>
              <label for="<%= property_slug %>parent_hidden_attributes"><?php _e( 'Parent Selection', ud_get_wp_property()->domain ); ?></label>
            </li>
          <% } %>
          <% typeof hidden_attributes_do_action[property_slug] != 'undefined'? print(hidden_attributes_do_action[ property_slug ]):''; %>
          <?php do_action( 'wpp::settings::developer::types::hidden_attributes'); /* The action should output underscore template. Template should pass variable to filter "wpp::settings::developer::types" to use in template or global wp_properties is available.*/ ?>
        </ul>
      </td>

      <td>
        <ul class="wp-tab-panel wpp_inherited_property_attributes wpp_something_advanced_wrapper">
          <li class="wpp_show_advanced" wrapper="wpp_something_advanced_wrapper"><?php _e( 'Toggle Attributes Selection', ud_get_wp_property()->domain ); ?></li>

          <% jQuery.each( wp_properties.property_stats, function(property_stat_slug, property_stat_label ){ %>
            <li class="wpp_development_advanced_option">
              <input id="<% print( property_slug + "_" + property_stat_slug) %>_inheritance" <% if( typeof wp_properties.property_inheritance[ property_slug ] != 'undefined' && jQuery.inArray(property_stat_slug, wp_properties.property_inheritance[ property_slug ]) != -1){ print( 'CHECKED'); } %> type="checkbox" name="wpp_settings[property_inheritance][<%= property_slug %>][]" value="<%= property_stat_slug %>"/>
              <label for="<% print( property_slug + "_" + property_stat_slug) %>_inheritance">
                <%= property_stat_label %>
              </label>
            </li>
          <% }); %>
          <% typeof inherited_attributes_do_action[property_slug] != 'undefined'? print(inherited_attributes_do_action[ property_slug ]):''; %>
        <?php do_action( 'wpp::settings::developer::types::inherited_attributes'); /* The action should output underscore template. Template should pass variable to filter "wpp::settings::developer::types" to use in template or global wp_properties is available.*/ ?>
        </ul>
      </td>

    </tr>
</script>