jQuery(document).on('wpp.ui.settings.ready', function() {
  var wp_properties = wpp.instance.settings;
  var configuration = wp_properties.configuration;
  var supermap_configuration = {};
  var wpp_property_types_variables = {};

  try{
    wpp_property_types_variables = JSON.parse(jQuery('#wpp-property-types-variables').html());
  }
  catch(error){
    console.log('JSON error: ', error);
    return;
  }

  var filtered_property_types = wpp_property_types_variables.filtered_property_types || {'': ''};

  if( typeof configuration.feature_settings != 'undefined' && typeof configuration.feature_settings.supermap != 'undefined' ) {
    supermap_configuration = configuration.feature_settings.supermap;
  }

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
    'configuration.default_image', 
    'configuration.default_image.types', 
  ];
  
  jQuery.each(requiredProps, function(index, item) {
    if(typeof  wp_properties[item] == 'undefined' ) {
      wp_properties[item] = {};
    }
  });

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
    template: _.template(jQuery('#settings-developer-types-template').html()),
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
    };
    jQuery.extend(attributes, wpp_property_types_variables);
    var row = new wppTypes(attributes);
    _wppTypes.add(row);


  });

  var wrapper = new wppTypesWrapperView({ collection: _wppTypes });
  jQuery("#wpp_inquiry_property_types tbody").empty().append(wrapper.render().el);

});