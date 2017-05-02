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
    wp_properties.property_stats = {'':''};
  }

  // Defining property of object wp_properties(if not defined) to avoid checking of typeof != 'undefined' in template
  var requiredProps = ['property_stats_groups', 'property_groups', 'sortable_attributes', 'prop_std_att', 'prop_std_att_mapsto', 'en_default_value', 'searchable_attr_fields', 'predefined_search_values', 'admin_attr_fields', 'predefined_values', 'default_values'];
  
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

  var _wppAttributes = new wppAttributes();
  // attributes needed in every rows
  var _requiredProps = 
  [
    'searchable_attr_fields',
    'predefined_search_values',
    'admin_attr_fields',
    'predefined_values',
    'default_values',
    'prop_std_att_mapsto'
  ];

  jQuery.each(wp_properties.property_stats, function(slug, value) {
    var gslug = '';
    var group = '';

    // Defining value of property in object wp_properties(if not defined) to avoid checking of typeof != 'undefined' in template
    jQuery.each(_requiredProps, function(index, props){
      if (typeof wp_properties[props][slug] == 'undefined') {
        wp_properties[props][slug] = '';
      }
    });

    if(typeof wp_properties.property_stats_groups[ slug ] != 'undefined'){
      gslug = wp_properties.property_stats_groups[ slug ];
      group = typeof wp_properties.property_groups[ gslug ] != 'undefined'  ? wp_properties[ 'property_groups' ][ gslug ] : '';
    }

    var attributes = {
      slug          : slug,
      gslug         : gslug,
      group         : group,
      wp_properties : wp_properties
    }

    jQuery.extend( attributes, wpp_property_attributes_variables );

    var row = new wppAttribute( attributes );
    _wppAttributes.add(row);
  });

  wppAttributesView = new WPPAttributesView({ collection: _wppAttributes });
  jQuery("#wpp_inquiry_attribute_fields tbody").empty().append(wppAttributesView.render().el);

  jQuery( ".wpp_admin_input_col .wpp_default_value_setter" ).each( function () {
    wpp.ui.settings.default_values_for_attribute( this );
  } );
});