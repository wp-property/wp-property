jQuery(document).on('wpp.ui.settings.ready', function() {
  var table = jQuery('#wpp_inquiry_property_types');
  var wp_properties = wpp.instance.settings;
  var configuration = wp_properties.configuration;
  var supermap_configuration = {};
  var supermap_configuration = __.get(configuration, 'feature_settings.supermap', {});

  try{
    wpp_property_types_variables = JSON.parse(jQuery('#wpp-property-types-variables').html());
  }
  catch(error){
    console.log('JSON error: ', error);
    return;
  }

  var filtered_property_types = wpp_property_types_variables.filtered_property_types || [];


  jQuery.each(wpp_property_types_variables.globals, function(index, val){
    window[index] = val;
  });
  delete wpp_property_types_variables.globals;
  
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
      };
    },
    template: _.template(jQuery('#settings-developer-types-template').html(), {
      evaluate:    /{{([\s\S]+?)}}/g,
      interpolate: /{{=([\s\S]+?)}}/g,
      escape:      /{{-([\s\S]+?)}}/g
    }),
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

  // Saving a new row to table for add row button
  var attributes = {
    label                             : '',
    slug                              : '',
    property_slug                     : '',
    wp_properties                     : wp_properties,
    supermap_configuration            : supermap_configuration,
  }
  jQuery.extend( attributes, wpp_property_types_variables );
  var row      = new wppTypes( attributes );
  var rowView  = new wppTypesView({ model: row });
  table.data('newRow', rowView.render().$el);

  var _wppTypes = new wppTypesCollection();

  jQuery.each(filtered_property_types, function(property_slug, label) {

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
  table.find("body").empty().append(wrapper.render().el);

  if(!__.get(_wppTypes, 'length', 0)){
    // Adding empty row if there no row.
    wpp_add_row(table.find('.wpp_add_row'));
  }

});