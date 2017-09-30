/**
 * WP-Property Global Admin Scripts
 *
 * This file is included on all back-end pages, so extra care needs be taken to avoid conflicts
 *
 */

if( !wpp ) {
}

var __ = {};
if(_.name == 'lodash'){
  __ = _.noConflict();
}

_.wppSelected = function(selected, current) {
  var result = '';
  current = current || true;

  if ( selected === current )
    result = " selected='selected' ";

  return result;
}

// Only usable in settings page  
_.wppChecked = function(obj, property, val) {
  var result = '';
  var items = __.get(obj, property, []);

  if ( (val === false && items) || _.contains(items, val)){
    result = " CHECKED ";
  }

  return result;
}

// Only usable in settings page  
_.wppMarkerUrl = function(marker_slug, supermap_configuration, wpp_supermap_default_marker, default_google_map_marker) {
  var regex = /(http|https):\/\//;
  var marker_url = '';

  console.log(marker_slug);
  if(!marker_slug || marker_slug == 'undefined'){
    marker_slug = __.get(supermap_configuration, 'default_marker', '');
  }

  if(marker_slug == ''){
    return wpp_supermap_default_marker;
  }
  else if(marker_slug == 'default_google_map_marker'){
    return default_google_map_marker;
  }
  else if(marker_slug && regex.exec(marker_slug)){
    return marker_slug;
  }
  
  marker_url = __.get(supermap_configuration, ['markers', marker_slug, 'file'], '');
  console.log(marker_slug, marker_url);



  return marker_url;
}

/**
 * Assign Property Stat to Group Functionality
 *
 * @param object opt Params
 * @author Maxim Peshkov
 */
jQuery.fn.wppGroups = function(opt) {
  var
  instance = jQuery(this),
  //* Default params */
  defaults = {
    elementClass: '.wpp_attribute_group',
    groupsBox: '#wpp_attribute_groups',
    groupWrapper: '#wpp_dialog_wrapper_for_groups',
    closeButton: '.wpp_close_dialog',
    assignButton: '.wpp_assign_to_group',
    unassignButton: '.wpp_unassign_from_group',
    removeButton: '.wpp_delete_row',
    sortButton: ".sort_stats_by_groups"
  };

  if(instance.length == 0)
    return;
  
  opt = jQuery.extend({}, defaults, opt);

  // get element class
  var elementClass = opt.elementClass;
  if (instance.length > 0) {
    elementClass = instance.attr('class');
    elementClass = elementClass.replace(/\s/ig, '.');
    elementClass = elementClass.split('.')[0];
    elementClass = '.' + elementClass;
  }

  //* Determine if dialog Wrapper exist */
  if(!jQuery(opt.groupWrapper).length > 0) {
    jQuery('body').append('<div id="wpp_dialog_wrapper_for_groups"></div>');
  }

  var
  groupsBlock = jQuery(opt.groupsBox),
  close = jQuery(opt.closeButton, groupsBlock),
  assign = jQuery(opt.assignButton),
  unassign = jQuery(opt.unassignButton),
  wrapper = jQuery(opt.groupWrapper),
  colorpicker = jQuery('input.wpp_input_colorpicker', groupsBlock),
  groupname = jQuery('input.slug_setter', groupsBlock),
  remove = jQuery(opt.removeButton, groupsBlock),
  sortButton = jQuery(opt.sortButton);

  //* Open Groups Block */
  showGroupBox = function() {
    groupsBlock.show(300);
    wrapper.css('display','block');
  };

  //* Close Groups Block */
  closeGroupBox = function () {
    groupsBlock.hide(300);
    wrapper.css('display','none');
    var row = jQuery('.wpp_inquiry_attribute_fields .wpp_dynamic_table_row');
    row.each(function(i, e){
      jQuery(e).removeClass('groups_active');
    });

    jQuery.each(colorpicker, function(i, e) {
      var _this = jQuery(e);
      var gslug = _this.parent().parent().find('.slug').val();
      console.log(gslug);
      console.log(_this.val());
      console.log(row.filter('tr[wpp_attribute_group=' + gslug + ']'));
      row.filter('tr[wpp_attribute_group=' + gslug + ']').css('background-color', _this.val());
    });
  };

  //* EVENTS */
  jQuery(document).on('click', elementClass, function(){
    showGroupBox();
    jQuery(this).parent().parent().addClass('groups_active');
  });

  jQuery(document).on('focus', elementClass, function(){
    jQuery(this).trigger('blur');
  });

  //* Close Group Box */
  jQuery(groupsBlock).on('click', opt.closeButton, function(){
    closeGroupBox();
  });

  //* Assign attribute to Group */
  jQuery(document).on('click', opt.assignButton, function(){
    var row = jQuery(this).parent().parent();

    var active_groups = jQuery('.wpp_inquiry_attribute_fields .wpp_dynamic_table_row.groups_active');
    active_groups.css('background-color', jQuery('input.wpp_input_colorpicker' , row).val());

    //* HACK FOR IE7 */
    if(typeof jQuery.browser.msie != 'undefined' && (parseInt(jQuery.browser.version) == 7)) {
      active_groups.find('td').css('background-color', jQuery('input.wpp_input_colorpicker' , row).val());
    }

    active_groups.attr('wpp_attribute_group' , row.attr('slug'));
    jQuery('input.wpp_group_slug' , active_groups).val(row.attr('slug'));

    var groupName = jQuery('input.slug_setter' , row).val();
    if(groupName == '') {
      groupName = 'NO NAME';
    }

    jQuery('input.wpp_attribute_group' , active_groups).val(groupName);
    closeGroupBox();
  });

  //* Unassign attribute from Group */
  jQuery(document).on('click', opt.unassignButton, function(){
    var active_groups = jQuery('.wpp_inquiry_attribute_fields .wpp_dynamic_table_row.groups_active');
    jQuery(active_groups).css('background-color', '');
    //* HACK FOR IE7 */
    if(typeof jQuery.browser.msie != 'undefined' && (parseInt(jQuery.browser.version) == 7)) {
      jQuery(active_groups).find('td').css('background-color', '');
    }

    jQuery(active_groups).removeAttr('wpp_attribute_group');
    jQuery('input.wpp_group_slug' , active_groups).val('');
    jQuery('input.wpp_attribute_group' , active_groups).val('');
    closeGroupBox();
  });

  //* Refresh background of all attributes on color change */
  jQuery(groupsBlock).on('change', 'input.wpp_input_colorpicker', function(){
    var cp = jQuery(this);
    var s = cp.parent().parent().attr('slug');
    instance.each(function(i,e){
      if(s == jQuery(e).next().val()) {
        jQuery(e).parent().parent().css('background-color', cp.val());
        //* HACK FOR IE7 */
        if(typeof jQuery.browser.msie != 'undefined' && (parseInt(jQuery.browser.version) == 7)) {
          jQuery(e).parent().parent().find('td').css('background-color', cp.val());
        }
      }
    });
  });

  //* Refresh Group Name field of all assigned attributes on group name change */
  jQuery(groupsBlock).on('change', 'input.slug_setter', function(){
    var gn = ( jQuery(this).val() != '' ) ? jQuery(this).val() : 'NO NAME';
    var s = jQuery(this).parent().parent().attr('slug');
    instance.each(function(i,e){
      if(s == jQuery(e).next().val()) {
        jQuery(e).val(gn);
      }
    });
  });

  //* Remove group from the list */
  jQuery(groupsBlock).on('click', opt.removeButton, function(){
    var s = jQuery(this).parent().parent().attr('slug');
    instance.each(function(i,e){
      if(s == jQuery(e).next().val()) {
        jQuery(e).parent().parent().css('background-color', '');
        //* HACK FOR IE7 */
        if(typeof jQuery.browser.msie != 'undefined' && (parseInt(jQuery.browser.version) == 7)) {
          jQuery(e).parent().parent().find('td').css('background-color', '');
        }
        jQuery(e).val('');
        jQuery(e).next().val('');
      }
    });
  });

  //* Close Groups Box on wrapper click */
  jQuery(document).on('click', opt.groupWrapper, function(){
    closeGroupBox();
  });

  //* Sorts all attributes by Groups */
  jQuery(document).on('click', opt.sortButton, function(){
    jQuery('tbody tr' , groupsBlock).each(function(gi,ge){
      var statsRow = jQuery('.wpp_inquiry_attribute_fields .wpp_dynamic_table_row');
      statsRow.each(function(si,se){
        if(typeof jQuery(se).attr('wpp_attribute_group') != 'undefined') {
          if(jQuery(se).attr('wpp_attribute_group') == jQuery(ge).attr('slug')) {
            jQuery(se).attr('sortpos', (gi + 1));
          }
        } else {
          jQuery(se).attr('sortpos', '9999');
        }
      });
    });

    jQuery( '.wpp_inquiry_attribute_fields tbody' ).each( function( i, e ){
      var currentlist = jQuery(e);
      var listitems = currentlist.children('tr').get();
      listitems.sort(function(a, b) {
        var compA = parseFloat(jQuery(a).attr('sortpos'));
        var compB = parseFloat(jQuery(b).attr('sortpos'));
        if( isNaN( compA ) ) compA = 9999;
        if( isNaN( compB ) ) compB = 9999;
        return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
      });
      jQuery.each(listitems, function(idx, itm) {
        currentlist.append(itm);
      });

    } );


  });

}

/**
 * Basic e-mail validation
 *
 * @param address
 * @return boolean. Returns true if email address is successfully validated.
 */
function wpp_validate_email(address) {
  var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,40})$/;

  if(reg.test(address) == false) {
    return false;
  } else {
    return true;
  }
}

/**
 * Bind ColorPicker with input fields '.wpp_input_colorpicker'
 *
 * @param object instance. jQuery object
 */
var bindColorPicker = function(instance){
  if(typeof window.jQuery.prototype.ColorPicker == 'function') {
    if(!instance) {
      instance = jQuery('body');
    }
    jQuery('.wpp_input_colorpicker', instance).ColorPicker({
      onSubmit: function(hsb, hex, rgb, el) {
        jQuery(el).val('#' + hex);
        jQuery(el).ColorPickerHide();
        jQuery(el).trigger('change');
      },
      onBeforeShow: function () {
        jQuery(this).ColorPickerSetColor(this.value);
      }
    })
    .bind('keyup', function(){
      jQuery(this).ColorPickerSetColor(this.value);
    });
  }
}

/**
 * Updates Row field names
 *
 * @param object instance. DOM element
 * @param boolean allowRandomSlug. Determine if Row can contains random slugs.
 */
var updateRowNames = function(instance, allowRandomSlug) {
  if(typeof instance == 'undefined') {
    return false;
  }
  if(typeof allowRandomSlug == 'undefined') {
    var allowRandomSlug = false;
  }

  var this_row = jQuery(instance).parents('tr.wpp_dynamic_table_row');
  // Slug of row in question
  var old_slug = jQuery(this_row).attr('data-slug') || jQuery(this_row).attr('slug');
  // Get data from input.slug_setter
  var new_slug = jQuery(instance).val();

  // Convert into slug
  new_slug = wpp_create_slug(new_slug);

  // Don't allow to blank out slugs
  if(new_slug == "") {
    if(allowRandomSlug && !jQuery(instance).hasClass( 'wpp_slug_can_be_empty' ) ) {
      new_slug = 'random_' + Math.floor(Math.random()*1000);
    } else {
      return;
    }
  }

  // There is no sense to continue if slugs are the same /
  if ( old_slug == new_slug ) {
    //* new_slug = 'random_' + Math.floor(Math.random()*1000); */
    return;
  }

  // Get all slugs of the table
  jQuery(instance).addClass( 'wpp_current_slug_is_being_checked' );
  var slugs = jQuery(this_row).parents('table').find('input.slug');
  slugs.each(function(k, v){
    if ( jQuery(v).val() == new_slug && !jQuery(v).hasClass( 'wpp_current_slug_is_being_checked' ) ) {
      new_slug = 'random_' + Math.floor(Math.random()*1000);
      return false;
    }
  });
  jQuery(instance).removeClass( 'wpp_current_slug_is_being_checked' );

  // If slug input.slug exists in row, we modify it
  jQuery(".slug" , this_row).val(new_slug);
  // Update row slug
  jQuery(this_row).attr('slug', new_slug);

  // Cycle through all child elements and fix names
  jQuery('input,select,textarea', this_row).each(function(i,e) {
    var old_name = jQuery(e).attr('name');
    if (typeof old_name != 'undefined' && !jQuery(e).hasClass('wpp_no_change_name')) {
      var new_name =  old_name.replace('['+old_slug+']','['+new_slug+']');
      // Update to new name
      jQuery(e).attr('name', new_name);
    }
    var old_id = jQuery(e).attr('id');
    if( typeof old_id != 'undefined' ) {
      var new_id =  old_id.replace( old_slug, new_slug );
      jQuery(e).attr('id', new_id);
    }
  });

  // Cycle through labels too
  jQuery('label', this_row).each(function(i,e) {
    if( typeof jQuery(e).attr('for') != 'undefined' ) {
      var old_for = jQuery(e).attr('for');
      var new_for =  old_for.replace(old_slug,new_slug);
      // Update to new name
      jQuery(e).attr('for', new_for);
    }
  });

  jQuery(".slug" , this_row).trigger('change');
}

/**
 * Toggle advanced options that are somehow related to the clicked trigger
 *
 * If trigger element has an attr of 'show_type_source', then function attempt to find that element and get its value
 * if value is found, that value is used as an additional requirement when finding which elements to toggle
 *
 * Example: <span class="wpp_show_advanced" show_type_source="id_of_input_with_a_string" advanced_option_class="class_of_elements_to_trigger" show_type_element_attribute="attribute_name_to_match">Show Advanced</span>
 * The above, when clicked, will toggle all elements within the same parent tree of cicked element, with class of "advanced_option_class" and with attribute of "show_type_element_attribute" the equals value of "#id_of_input_with_a_string"
 *
 * Clicking the trigger in example when get the value of:
 * <input id="value_from_source_element" value="some_sort_of_identifier" />
 *
 * And then toggle all elements like below:
 * <li class="class_of_elements_to_trigger" attribute_name_to_match="some_sort_of_identifier">Data that will be toggled.</li>
 *
 * Copyright 2011 Usability Dynamics, Inc. <info@usabilitydynamics.com>
 */
function toggle_advanced_options() {
  jQuery(document).on("click", ".wpp_show_advanced", function() {
    var advanced_option_class = false;
    var show_type = false;
    var show_type_element_attribute = false;

    //* Try getting arguments automatically */
    var wrapper = (jQuery(this).attr('wrapper') ? jQuery(this).closest('.' + jQuery(this).attr('wrapper'))  : jQuery(this).parents('tr.wpp_dynamic_table_row'));

    if(jQuery(this).attr("advanced_option_class") !== undefined) {
      var advanced_option_class = "." + jQuery(this).attr("advanced_option_class");
    }

    if(jQuery(this).attr("show_type_element_attribute") !== undefined) {
      var show_type_element_attribute = jQuery(this).attr("show_type_element_attribute");
    }

    //* If no advanced_option_class is found in attribute, we default to 'wpp_development_advanced_option' */
    if(!advanced_option_class) {
      advanced_option_class = "li.wpp_development_advanced_option";
    }

    //* If element does not have a table row wrapper, we look for the closts .wpp_something_advanced_wrapper wrapper */
    if(wrapper.length == 0) {
      var wrapper = jQuery(this).parents('.wpp_something_advanced_wrapper');
    }

    //* get_show_type_value forces the a look up a value of a passed element, ID of which is passed, which is then used as another conditional argument */
    if(show_type_source = jQuery(this).attr("show_type_source")) {
      var source_element = jQuery("#" + show_type_source);

      if(source_element) {
        //* Element found, determine type and get current value */
        if(jQuery(source_element).is("select")) {
          show_type = jQuery("option:selected", source_element).val();
        }
      }
    }


    if(!show_type) {
      element_path = jQuery(advanced_option_class, wrapper);
    }

    //** Look for advanced options with show type */
    if(show_type) {
      element_path = jQuery(advanced_option_class + "[" + show_type_element_attribute + "='"+show_type+"']", wrapper);
    }

    /* Check if this element is a checkbox, we assume that we always show things when it is checked, and hiding when unchecked */
    if(jQuery(this).is("input[type=checkbox]")) {

      var toggle_logic = jQuery(this).attr("toggle_logic");


      if(jQuery(this).is(":checked")) {
        if(toggle_logic == 'reverse') {
          jQuery(element_path).hide();
        } else {
          jQuery(element_path).show();
        }
      } else {
        if(toggle_logic == 'reverse') {
          jQuery(element_path).show();
        } else {
          jQuery(element_path).hide();
        }
      }

      return;

    }


    jQuery(element_path).toggle();

  });
}

/**
 *
 * @param slug
 * @return
 */
function wpp_create_slug(slug) {
  slug = slug.replace(/[^a-zA-Z0-9_\s]/g,"");
  slug = slug.toLowerCase();
  slug = slug.replace(/\s/g,'_');
  return slug;
}

/**
 * Adds new Row to the table
 *
 * @param element
 * @return
 */
function wpp_add_row(element,hides) {
  var auto_increment = false;
  var table = jQuery(element).parents('.ud_ui_dynamic_table');
  var table_id = jQuery(table).attr("id");

  //* Determine if table rows are numeric */
  if(jQuery(table).attr('auto_increment') == 'true') {
    var auto_increment = true;
  } else if (jQuery(table).attr('use_random_row_id') == 'true') {
    var use_random_row_id = true;
  } else if (jQuery(table).attr('allow_random_slug') == 'true') {
    var allow_random_slug = true;
  }

  //* Clone last row */
  var cloned;
  if(table.data('newRow')){
    cloned = table.data('newRow').clone();
  }
  else{
    cloned = jQuery(".wpp_dynamic_table_row:last", table).clone();
  }

  //return;
  //* Set unique 'id's and 'for's for elements of the new row */
  var unique = Math.floor(Math.random()*1000);
  wpp_set_unique_ids(cloned, unique);


  //* Increment name value automatically */
  if(auto_increment) {
    //* Cycle through all child elements and fix names */
    jQuery('input,select,textarea', cloned).each(function(element) {
      var old_name = jQuery(this).attr('name');
      var matches = old_name.match(/\[(\d{1,4})\]/);
      if (matches) {
        old_count = parseInt(matches[1]);
        new_count = (old_count + 1);
      }
      var new_name =  old_name.replace('[' + old_count + ']','[' + new_count + ']');
      //* Update to new name */
      jQuery(this).attr('name', new_name);
    });

  } else if (use_random_row_id) {
    //* Get the current random id of row */
    var random_row_id = jQuery(cloned).attr('random_row_id');
    var new_random_row_id = Math.floor(Math.random()*1000)
    //* Cycle through all child elements and fix names */
    jQuery('input,select,textarea', cloned).each(function(element) {
      var old_name = jQuery(this).attr('name');
      var new_name =  old_name.replace('[' + random_row_id + ']','[' + new_random_row_id + ']');
      //* Update to new name */
      jQuery(this).attr('name', new_name);
    });
    jQuery(cloned).attr('random_row_id', new_random_row_id);

  } else if (allow_random_slug) {
    //* Update Row names */
    var slug_setter = jQuery("input.slug_setter", cloned);
    jQuery(slug_setter).attr('value', '');
    if(slug_setter.length > 0) {
      updateRowNames(slug_setter.get(0), true);
    }
  }

  //* Blank out all values */
  jQuery("textarea", cloned).val('');
  jQuery("select", cloned).val('');
  jQuery("input[type=text]", cloned).val('');
  jQuery("input[type=checkbox]", cloned).attr('checked', false);
  jQuery("input[type=radio]", cloned).val('');
  jQuery("input[type=radio]", cloned).attr('checked', false);

  //* Insert new row after last one */
  jQuery(cloned).appendTo(table);

  //* Get Last row to update names to match slug */
  var added_row = jQuery(".wpp_dynamic_table_row:last", table);

  //* Bind (Set) ColorPicker with new fields '.wpp_input_colorpicker' */
  bindColorPicker(added_row);
  // Display row just in case
  if(hides== true){
   jQuery(added_row).hide();
  } else{
   jQuery(added_row).show();
  }

  //* Remove hidden cass from delete button if the button of last row was hidden. */
  jQuery(".wpp_delete_row", added_row).removeClass('hidden');

  //* Unset 'new_row' attribute */
  jQuery(added_row).attr('new_row', 'true');

  //* Focus on new element */
  jQuery('input.slug_setter', added_row).focus();

  //* Fire Event after Row added to the Table */
  added_row.trigger('added');

  if (callback_function = jQuery(element).attr("callback_function")) {
    wpp_call_function(callback_function, window, added_row);
  }

  return added_row;
}
/**
 * Slides down WP contextual help,
 * and if 'wpp_scroll_to' attribute exists, scroll to it.
 *
 * @param element
 */
function wpp_toggle_contextual_help( element, event ) {
  var el = jQuery( element );
  var screen_meta = jQuery("#screen-meta");
  var panel = jQuery("#contextual-help-wrap");
  var help_link = jQuery("#contextual-help-link");
  var scroll_to = el.attr('wpp_scroll_to') && jQuery( el.attr('wpp_scroll_to') ).length ? jQuery( el.attr('wpp_scroll_to') ) : false;

  /* If Already Open - we close Help */
  if ( help_link.hasClass( 'screen-meta-active' ) ) {

    help_link.removeClass( 'screen-meta-active' );

    panel.slideUp( 'fast', function() {
      panel.hide();
      screen_meta.hide();
      jQuery('.screen-meta-toggle').css('visibility', '');
    });

    if( scroll_to ) {
      scroll_to.removeClass( 'wpp_contextual_highlight');
    }

    return;

  }

  /* If not open - we open help and maybe scroll to something */
  if ( !help_link.hasClass( 'screen-meta-active' ) ) {

    help_link.addClass('screen-meta-active');

    if( scroll_to ) {
      scroll_to.addClass( 'wpp_contextual_highlight' );
    }

    panel.slideDown( 'fast', function() {
      panel.show();
      screen_meta.show();

      if( scroll_to ) {

        jQuery('html, body').animate({
          scrollTop: scroll_to.offset().top
        }, 1000);

      }

    });

    return;

  }

}

/**
 *
 * @param functionName
 * @param context
 * @param args
 * @return
 */
function wpp_call_function(functionName, context, args) {
  var args = Array.prototype.slice.call(arguments).splice(2);
  var namespaces = functionName.split(".");
  var func = namespaces.pop();
  for(var i = 0; i < namespaces.length; i++) {
    context = context[namespaces[i]];
  }
  return context[func].apply(this, args);
}

/**
 * Set unique IDs and FORs of DOM elements recursivly
 *
 * @param object el. jQuery DOM object
 * @param integer unique. Unique suffix which will be added to all IDs and FORs
 * @author Maxim Peshkov
 */
function wpp_set_unique_ids(el, unique) {
  if (typeof el == "undefined" || el.size() === 0) {
    return;
  }

  el.each(function(){
    var child = jQuery(this);

    if (child.children().size() > 0) {
      wpp_set_unique_ids(child.children(), unique);
    }

    var id = child.attr('id');
    if(typeof id != 'undefined') {
      child.attr('id', id + '_' + unique);
    }

    var efor = child.attr('for');
    if(typeof efor != 'undefined') {
      child.attr('for', efor + '_' + unique);
    }
  });
}

/**
 * DOCUMENT READY EVENTS AND ACTIONS
 */
jQuery(document).ready(function() {

  setTimeout(function(){
    jQuery('#posts_list').removeClass('closed').children('h2').off();
  }, 400);
  /* Remove any highlight classes */
  jQuery("#contextual-help-link").click(function() {
    jQuery("#contextual-help-wrap h3").removeClass("wpp_contextual_highlight");
  });

  toggle_advanced_options();

  //* Easy way of displaying the contextual help dropdown */
  jQuery(document).on("click", ".wpp_toggle_contextual_help", function( event ) {
    wpp_toggle_contextual_help(this , event );
  });

  // Toggle wpp_wpp_settings_configuration_do_not_override_search_result_page_
  jQuery("#wpp_wpp_settings_configuration_automatically_insert_overview_").change(function() {
    if(jQuery(this).is(":checked")) {
      jQuery("li.wpp_wpp_settings_configuration_do_not_override_search_result_page_row").hide();
    } else {
      jQuery("li.wpp_wpp_settings_configuration_do_not_override_search_result_page_row").show();
    }
  });

  // Bind (Set) ColorPicker
  bindColorPicker();

  // Add row to UD UI Dynamic Table
  jQuery(document).on("click", ".wpp_add_row", function() {
    wpp_add_row(this);
  });

  // When the .slug_setter input field is modified, we update names of other elements in row
  jQuery(document).on("keyup", ".wpp_dynamic_table_row[new_row=true] input.slug_setter", function() {
    updateRowNames(this, true);
  });
  jQuery(document).on("change", ".wpp_dynamic_table_row[new_row=true] select.slug_setter", function() {
    updateRowNames(this, true);
  });

  // Delete dynamic row
  jQuery(document).on("click", ".wpp_delete_row", function(event) {
    event.preventDefault();
    if(jQuery(this).hasClass('disabled'))
      return false;

    var parent = jQuery(this).parents('tr.wpp_dynamic_table_row');
    var table = jQuery(jQuery(this).parents('table').get(0));
    var row_count = table.find(".wpp_delete_row").length;
    if(jQuery(this).attr('verify_action') == 'true') {
      if(!confirm('Are you sure?'))
        return false;
    }
    // Blank out all values
    jQuery("input[type=text]", parent).val('');
    jQuery("input[type=checkbox]", parent).attr('checked', false);
    jQuery("input[type=radio]", parent).val('');
    jQuery("input[type=radio]", parent).attr('checked', false);
    // Don't hide last row
    if(row_count > 1) {
      jQuery(parent).hide();
      jQuery(parent).remove();
    } else {
	   if(jQuery(table).hasClass('last_delete_row')){
	       wpp_add_row(this);
         jQuery(parent).remove();
	   } else{
         jQuery(parent).attr( 'new_row', 'true' );
	   }
    }

    table.trigger('row_removed', [parent]);
  });

  jQuery(document).on('click', '.wpp_attach_to_agent', function(){
    var agent_image_id = jQuery(this).attr('id');
    if (agent_image_id != '')
      jQuery('#library-form').append('<input name="wpp_agent_post_id" type="text" value="' + agent_image_id + '" />').submit();
  });

  //* Add Sort functionality to Table */
  if(typeof jQuery.fn.sortable == 'function') {
    jQuery('table.wpp_sortable tbody').sortable();
    jQuery(document).on("mouseover mouseout", 'table.wpp_sortable tbody tr', function(event) {
      if ( event.type == "mouseover" ) {
        jQuery(this).addClass("wpp_draggable_handle_show");
      } else {
        jQuery(this).removeClass("wpp_draggable_handle_show");
      }
    });
  }
  // for developer-settings-attributes
  //toggle std attr
  jQuery(document).on("click", ".wpp-toggle-std-attr", function() {
      jQuery(this).closest('li').find(".std-attr-mapper").fadeToggle();
  });

  // apply notices on developer tab
  applyNotices = function(notice,notice_cont){

    if(typeof(notice)!="undefined" && notice.trim().length >0){
      notice_cont.text(notice).fadeIn();
    }
    else{
      notice_cont.text('').fadeOut();
    }
  }
  // for developer-settings-attributes
  // load icon on page load
  jQuery("#wpp_inquiry_attribute_fields tr").each(function(){

    var iconClass =  jQuery(this).find(".std-attr-mapper .wpp_settings-prop_std_att_mapsto").val();
    $x = jQuery(this).find(".wpp_std_attr_view i").addClass(iconClass);

    //if there are notices then display them
    var notice = jQuery(this).find('.wpp_settings-prop_std_att_mapsto').find(':selected').data('notice');
    var notice_cont = jQuery(this).find("i.std_att_notices");
    applyNotices(notice,notice_cont);
  });

  // for developer-settings-attributes
  //change icon on select change
  jQuery(".std-attr-mapper .wpp_settings-prop_std_att_mapsto").change(function(){

    var iconClass =  jQuery(this).val();
    $x = jQuery(this).closest("tr").find(".wpp_std_attr_view i");
    $x.removeClass().addClass(iconClass);

    //if there are notices then display them
    var notice = jQuery(this).find(':selected').data('notice');
    var notice_cont = jQuery(this).parent().find("i.std_att_notices");
    applyNotices(notice,notice_cont);
    toggleAttributesDropdown();
  });
  if(jQuery("#wpp_settings_base_slug").length>0)
    jQuery("#wpp_settings_base_slug").select2();
    //hide already choosen standard attributes
  (toggleAttributesDropdown = function(){
    var items = jQuery(".wpp_settings-prop_std_att_mapsto option:selected");
    jQuery(".wpp_settings-prop_std_att_mapsto").each(function (ind,val) {
        // Get the selected value
        jQuery("option", jQuery(this)).each(function(){
            jQuery(this).removeAttr("disabled");
        });

      jQuery.each(items, function() {
        if (jQuery.trim(jQuery(this).val()) == ''){
          jQuery("option[value='" + jQuery(this).val() + "']", val).attr("disabled", true);
        }
      });
    });
  })();

    //notice popups to explain matched fields in Standard attributes
  jQuery(".wpp-notice-for-match").click(function(e){
    e.preventDefault();
    jQuery(".wpp-notice-dialog").dialog('open');
  });
});
