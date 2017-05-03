jQuery(document).on('wpp.ui.settings.ready', function() {
  var wp_properties = wpp.instance.settings;
  var wpp_property_attributes_variables = {};

  try{
    wpp_property_attributes_variables = JSON.parse(jQuery('#wpp-attributes-variables').html());
  }
  catch(error){
    console.log('JSON error: ', error);
    return;
  }

  if(typeof wp_properties.property_stats == 'undefined'){
    wp_properties.property_stats = {'': ''};
  }

  window.selected = function(selected, current) {
    var result = '';
    current = current || true;

    if ( selected === current )
      result = " selected='selected' ";
 
    return result;
  }

  _.wppChecked = function(obj, property, val) {
    var result = '';
    var items = _.get(obj, property, []);
    if ( jQuery.inArray(val, items) != -1)
      result = " CHECKED ";
 
    return result;
  }


  var wppAttribute = Backbone.Model.extend({
  });
  var wppAttributeView = Backbone.View.extend({
    tagName: 'tr',
    className: 'wpp_dynamic_table_row',
    attributes: function(){
      return {
        slug: this.model.get('slug'),
        wpp_attribute_group: this.model.get('gslug'),
        new_row: this.model.get('slug') == '' ? true : false,
        style: typeof this.model.get('group').color != 'undefined' ? 'background-color:' + this.model.get('group').color : '',
      };
    },
    template: _.template(jQuery('#settings-developer-attributes-template').html()),
    render: function() {
      this.el.innerHTML = this.template(this.model.toJSON());
      return this;
    }
  });


  var wppAttributes = Backbone.Collection.extend({
    model: wppAttribute,
  });

  var WPPAttributesView = Backbone.View.extend({
    el: '#wpp_inquiry_attribute_fields tbody',
    children: {},
    render: function() {
      this.collection.each(this.addAttribute.bind(this));
      return this;
    },
    addAttribute: function (model) {
      this.children[model.cid] = new wppAttributeView({ model: model });
      jQuery(this.el).append(this.children[model.cid].render().el);
    },

  });

  var attributes = {
    slug          : '',
    gslug         : '',
    group         : '',
    wp_properties : wp_properties
  }

  jQuery.extend( attributes, wpp_property_attributes_variables );

  var row      = new wppAttribute( attributes );
  var rowView  = new wppAttributeView({ model: row });
  jQuery('#wpp_inquiry_attribute_fields').data('newRow', rowView.render().$el);

  var _wppAttributes = new wppAttributes();

  jQuery.each(wp_properties.property_stats, function(slug, value) {
    var gslug = _.get(wp_properties, ['property_stats_groups', slug], '');
    var group = _.get(wp_properties, ['property_groups', gslug], '');

    var attributes = {
      slug          : slug,
      gslug         : gslug,
      group         : group,
      wp_properties : wp_properties
    }

    jQuery.extend( attributes, wpp_property_attributes_variables );

    var row = new wppAttribute( attributes );
    console.log(row)
    _wppAttributes.add(row);
  });

  wppAttributesView = new WPPAttributesView({ collection: _wppAttributes });
  jQuery("#wpp_inquiry_attribute_fields tbody").empty().append(wppAttributesView.render().el);

  jQuery( ".wpp_admin_input_col .wpp_default_value_setter" ).each( function () {
    wpp.ui.settings.default_values_for_attribute( this );
  } );
});