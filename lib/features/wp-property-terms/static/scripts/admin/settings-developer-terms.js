jQuery(document).on('wpp.ui.settings.ready', function() {
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

  window.selected = function(selected, current) {
    var result = '';
    current = current || true;

    if ( selected === current )
      result = " selected='selected' ";
 
    return result;
  }
  
  var wppTerms = Backbone.Model.extend({
  });

  var wppTermsCollection = Backbone.Collection.extend({
    model: wppTerms,
  });

  var wppTermsView = Backbone.View.extend({
    tagName: 'tr',
    className: 'wpp_dynamic_table_row',
    template: _.template(jQuery('#settings-developer-terms-template').html()),
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


  var _wppTerms = new wppTermsCollection();

  jQuery.each(config.taxonomies, function(slug, data) {

    var gslug       = '';
    var group       = '';
    var rewriteSlug = '';

    if(typeof config.groups != 'undefined' && typeof config.groups[ slug ] != 'undefined'){
      gslug = config.groups[ slug ];
      group = typeof wp_properties.property_groups[ gslug ] != 'undefined'  ? wp_properties[ 'property_groups' ][ gslug ] : {};
    }

    if(typeof wp_properties.searchable_attr_fields[slug] == 'undefined'){
      wp_properties.searchable_attr_fields[slug] = '';
    }

    if(typeof data.rewrite == 'undefined' || typeof data.rewrite.slug == 'undefined'){
      rewriteSlug = slug;
    }
    else{
      rewriteSlug = data.rewrite.slug;
    }

    var attributes = {
      slug                              : slug,
      data                              : data,
      gslug                             : gslug,
      group                             : group,
      rewriteSlug                       : rewriteSlug,
      wp_properties                     : wp_properties,
      current_search_input              : wp_properties.searchable_attr_fields[slug],
    };

    jQuery.extend(attributes, wpp_property_types_variables);

    var row = new wppTerms(attributes);
    _wppTerms.add(row);


  });

  var wrapper = new wppTermsWrapperView({ collection: _wppTerms });
  jQuery("#wpp_inquiry_property_terms tbody").empty().append(wrapper.render().el);

});
