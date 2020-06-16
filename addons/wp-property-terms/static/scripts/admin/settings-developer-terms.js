jQuery(document).on('wpp.ui.settings.ready', function() {
  var table = jQuery('#wpp_inquiry_property_terms');
  var wp_properties = wpp.instance.settings;
  var wpp_property_types_variables = {};

  try{
    wpp_property_types_variables = JSON.parse(jQuery('#wpp-terms-variables').html());
  }
  catch(error){
    console.log('JSON error: ', error);
    return;
  }

  var config = wpp_property_types_variables.config;
  
  jQuery.each(wpp_property_types_variables.globals, function(index, val){
    window[index] = val;
  });
  delete wpp_property_types_variables.globals;
  
  var wppTerms = Backbone.Model.extend({
  });

  var wppTermsCollection = Backbone.Collection.extend({
    model: wppTerms,
  });

  var wppTermsView = Backbone.View.extend({
    tagName: 'tr',
    className: 'wpp_dynamic_table_row',
    template: _.template(jQuery('#settings-developer-terms-template').html(), {
      evaluate:    /{{([\s\S]+?)}}/g,
      interpolate: /{{=([\s\S]+?)}}/g,
      escape:      /{{-([\s\S]+?)}}/g
    }),
    attributes: function(){
      return {
        slug: this.model.get('slug'),
        'data-property-slug': this.model.get('slug'),
        new_row: this.model.get('slug') == '' ? true : false,
        wpp_attribute_group: this.model.get('gslug'),
        style: typeof this.model.get('group').color != 'undefined' ? 'background-color:' + this.model.get('group').color : '',
      };
    },
    render: function() {
      this.el.innerHTML = this.template(this.model.toJSON());
      return this;
    }
  });



  var wppTermsWrapperView = Backbone.View.extend({
    el: '#wpp_inquiry_property_terms tbody',
    render: function() {
      this.collection.each(this.addAttribute.bind(this));
      return this;
    },
    addAttribute: function (model) {
      var row = new wppTermsView({ model: model });
      jQuery(this.el).append(row.render().el);
    },

  });

  var attributes = {
      slug                              : '',
      data                              : '',
      gslug                             : '',
      group                             : '',
      rewriteSlug                       : '',
      wp_properties                     : wp_properties,
      current_search_input              : '',
  }
  jQuery.extend( attributes, wpp_property_types_variables );
  var row      = new wppTerms( attributes );
  var rowView  = new wppTermsView({ model: row });
  table.data('newRow', rowView.render().$el);

  var _wppTerms = new wppTermsCollection();

  jQuery.each(config.taxonomies, function(slug, data) {

    var gslug       = __.get(config, ['groups', slug ], '');
    var group       = __.get(wp_properties, ['property_groups', gslug ], '');;
    var rewriteSlug = __.get(data, ['rewrite', slug ], slug);

    var attributes = {
      slug                              : slug,
      data                              : data,
      gslug                             : gslug,
      group                             : group,
      rewriteSlug                       : rewriteSlug,
      wp_properties                     : wp_properties,
      current_search_input              : __.get(wp_properties, ['searchable_attr_fields', slug ], ''),
    };

    jQuery.extend(attributes, wpp_property_types_variables);

    var row = new wppTerms(attributes);
    _wppTerms.add(row);

  });
  
  var wrapper = new wppTermsWrapperView({ collection: _wppTerms });
  table.find("tbody").empty().append(wrapper.render().el);


  if(!__.get(_wppTerms, 'length', 0)){
    // Adding empty row if there no row.
    wpp_add_row(table.find('.wpp_add_row'));
  }
});
