function wpp_validate_email(address) {
    var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
    return 0 == reg.test(address) ? !1 : !0;
}

function toggle_advanced_options() {
    jQuery(".wpp_show_advanced").live("click", function() {
        var advanced_option_class = !1, show_type = !1, show_type_element_attribute = !1, wrapper = jQuery(this).attr("wrapper") ? jQuery(this).closest("." + jQuery(this).attr("wrapper")) : jQuery(this).parents("tr.wpp_dynamic_table_row");
        if (void 0 !== jQuery(this).attr("advanced_option_class")) var advanced_option_class = "." + jQuery(this).attr("advanced_option_class");
        if (void 0 !== jQuery(this).attr("show_type_element_attribute")) var show_type_element_attribute = jQuery(this).attr("show_type_element_attribute");
        if (advanced_option_class || (advanced_option_class = "li.wpp_development_advanced_option"), 
        0 == wrapper.length) var wrapper = jQuery(this).parents(".wpp_something_advanced_wrapper");
        if (show_type_source = jQuery(this).attr("show_type_source")) {
            var source_element = jQuery("#" + show_type_source);
            source_element && jQuery(source_element).is("select") && (show_type = jQuery("option:selected", source_element).val());
        }
        if (show_type || (element_path = jQuery(advanced_option_class, wrapper)), show_type && (element_path = jQuery(advanced_option_class + "[" + show_type_element_attribute + "='" + show_type + "']", wrapper)), 
        jQuery(this).is("input[type=checkbox]")) {
            var toggle_logic = jQuery(this).attr("toggle_logic");
            return void (jQuery(this).is(":checked") ? "reverse" == toggle_logic ? jQuery(element_path).hide() : jQuery(element_path).show() : "reverse" == toggle_logic ? jQuery(element_path).show() : jQuery(element_path).hide());
        }
        jQuery(element_path).toggle();
    });
}

function wpp_create_slug(slug) {
    return slug = slug.replace(/[^a-zA-Z0-9_\s]/g, ""), slug = slug.toLowerCase(), slug = slug.replace(/\s/g, "_");
}

function wpp_add_row(element) {
    {
        var auto_increment = !1, table = jQuery(element).parents(".ud_ui_dynamic_table");
        jQuery(table).attr("id");
    }
    if ("true" == jQuery(table).attr("auto_increment")) var auto_increment = !0; else if ("true" == jQuery(table).attr("use_random_row_id")) var use_random_row_id = !0; else if ("true" == jQuery(table).attr("allow_random_slug")) var allow_random_slug = !0;
    var cloned = jQuery(".wpp_dynamic_table_row:last", table).clone(), unique = Math.floor(1e3 * Math.random());
    if (wpp_set_unique_ids(cloned, unique), auto_increment) jQuery("input,select,textarea", cloned).each(function() {
        var old_name = jQuery(this).attr("name"), matches = old_name.match(/\[(\d{1,2})\]/);
        matches && (old_count = parseInt(matches[1]), new_count = old_count + 1);
        var new_name = old_name.replace("[" + old_count + "]", "[" + new_count + "]");
        jQuery(this).attr("name", new_name);
    }); else if (use_random_row_id) {
        var random_row_id = jQuery(cloned).attr("random_row_id"), new_random_row_id = Math.floor(1e3 * Math.random());
        jQuery("input,select,textarea", cloned).each(function() {
            var old_name = jQuery(this).attr("name"), new_name = old_name.replace("[" + random_row_id + "]", "[" + new_random_row_id + "]");
            jQuery(this).attr("name", new_name);
        }), jQuery(cloned).attr("random_row_id", new_random_row_id);
    } else if (allow_random_slug) {
        var slug_setter = jQuery("input.slug_setter", cloned);
        jQuery(slug_setter).attr("value", ""), slug_setter.length > 0 && updateRowNames(slug_setter.get(0), !0);
    }
    jQuery(cloned).appendTo(table);
    var added_row = jQuery(".wpp_dynamic_table_row:last", table);
    return bindColorPicker(added_row), jQuery(added_row).show(), jQuery("textarea", added_row).val(""), 
    jQuery("select", added_row).val(""), jQuery("input[type=text]", added_row).val(""), 
    jQuery("input[type=checkbox]", added_row).attr("checked", !1), jQuery(added_row).attr("new_row", "true"), 
    jQuery("input.slug_setter", added_row).focus(), added_row.trigger("added"), (callback_function = jQuery(element).attr("callback_function")) && wpp_call_function(callback_function, window, added_row), 
    added_row;
}

function wpp_toggle_contextual_help(element) {
    var el = jQuery(element), screen_meta = jQuery("#screen-meta"), panel = jQuery("#contextual-help-wrap"), help_link = jQuery("#contextual-help-link"), scroll_to = el.attr("wpp_scroll_to") && jQuery(el.attr("wpp_scroll_to")).length ? jQuery(el.attr("wpp_scroll_to")) : !1;
    return help_link.hasClass("screen-meta-active") ? (help_link.removeClass("screen-meta-active"), 
    panel.slideUp("fast", function() {
        panel.hide(), screen_meta.hide(), jQuery(".screen-meta-toggle").css("visibility", "");
    }), void (scroll_to && scroll_to.removeClass("wpp_contextual_highlight"))) : help_link.hasClass("screen-meta-active") ? void 0 : (help_link.addClass("screen-meta-active"), 
    scroll_to && scroll_to.addClass("wpp_contextual_highlight"), void panel.slideDown("fast", function() {
        panel.show(), screen_meta.show(), scroll_to && jQuery("html, body").animate({
            scrollTop: scroll_to.offset().top
        }, 1e3);
    }));
}

function wpp_call_function(functionName, context, args) {
    for (var args = Array.prototype.slice.call(arguments).splice(2), namespaces = functionName.split("."), func = namespaces.pop(), i = 0; i < namespaces.length; i++) context = context[namespaces[i]];
    return context[func].apply(this, args);
}

function wpp_set_unique_ids(el, unique) {
    "undefined" != typeof el && 0 !== el.size() && el.each(function() {
        var child = jQuery(this);
        child.children().size() > 0 && wpp_set_unique_ids(child.children(), unique);
        var id = child.attr("id");
        "undefined" != typeof id && child.attr("id", id + "_" + unique);
        var efor = child.attr("for");
        "undefined" != typeof efor && child.attr("for", efor + "_" + unique);
    });
}

jQuery.fn.wppGroups = function(opt) {
    var instance = jQuery(this), defaults = {
        groupsBox: "#wpp_attribute_groups",
        groupWrapper: "#wpp_dialog_wrapper_for_groups",
        closeButton: ".wpp_close_dialog",
        assignButton: ".wpp_assign_to_group",
        unassignButton: ".wpp_unassign_from_group",
        removeButton: ".wpp_delete_row",
        sortButton: "#sort_stats_by_groups"
    };
    opt = jQuery.extend({}, defaults, opt), !jQuery(opt.groupWrapper).length > 0 && jQuery("body").append('<div id="wpp_dialog_wrapper_for_groups"></div>');
    var groupsBlock = jQuery(opt.groupsBox), sortButton = jQuery(opt.sortButton), statsRow = instance.parent().parent(), statsTable = instance.parents("#wpp_inquiry_attribute_fields"), close = jQuery(opt.closeButton, groupsBlock), assign = jQuery(opt.assignButton), unassign = jQuery(opt.unassignButton), wrapper = jQuery(opt.groupWrapper), colorpicker = jQuery("input.wpp_input_colorpicker", groupsBlock), groupname = jQuery("input.slug_setter", groupsBlock), remove = jQuery(opt.removeButton, groupsBlock), sortButton = jQuery(opt.sortButton), showGroupBox = function() {
        groupsBlock.show(300), wrapper.css("display", "block");
    }, closeGroupBox = function() {
        groupsBlock.hide(300), wrapper.css("display", "none"), statsRow.each(function(i, e) {
            jQuery(e).removeClass("groups_active");
        });
    };
    if (instance.live("click", function() {
        showGroupBox(), jQuery(this).parent().parent().addClass("groups_active");
    }), instance.live("focus", function() {
        jQuery(this).trigger("blur");
    }), close.live("click", function() {
        closeGroupBox();
    }), assign.live("click", function() {
        var row = jQuery(this).parent().parent();
        statsRow.each(function(i, e) {
            if (jQuery(e).hasClass("groups_active")) {
                jQuery(e).css("background-color", jQuery("input.wpp_input_colorpicker", row).val()), 
                "undefined" != typeof jQuery.browser.msie && 7 == parseInt(jQuery.browser.version) && jQuery(e).find("td").css("background-color", jQuery("input.wpp_input_colorpicker", row).val()), 
                jQuery(e).attr("wpp_attribute_group", row.attr("slug")), jQuery("input.wpp_group_slug", e).val(row.attr("slug"));
                var groupName = jQuery("input.slug_setter", row).val();
                "" == groupName && (groupName = "NO NAME"), jQuery("input.wpp_attribute_group", e).val(groupName);
            }
        }), closeGroupBox();
    }), unassign.live("click", function() {
        statsRow.each(function(i, e) {
            jQuery(e).hasClass("groups_active") && (jQuery(e).css("background-color", ""), "undefined" != typeof jQuery.browser.msie && 7 == parseInt(jQuery.browser.version) && jQuery(e).find("td").css("background-color", ""), 
            jQuery(e).removeAttr("wpp_attribute_group"), jQuery("input.wpp_group_slug", e).val(""), 
            jQuery("input.wpp_attribute_group", e).val(""));
        }), closeGroupBox();
    }), colorpicker.live("change", function() {
        var cp = jQuery(this), s = cp.parent().parent().attr("slug");
        instance.each(function(i, e) {
            s == jQuery(e).next().val() && (jQuery(e).parent().parent().css("background-color", cp.val()), 
            "undefined" != typeof jQuery.browser.msie && 7 == parseInt(jQuery.browser.version) && jQuery(e).parent().parent().find("td").css("background-color", cp.val()));
        });
    }), groupname.live("change", function() {
        var gn = "" != jQuery(this).val() ? jQuery(this).val() : "NO NAME", s = jQuery(this).parent().parent().attr("slug");
        instance.each(function(i, e) {
            s == jQuery(e).next().val() && jQuery(e).val(gn);
        });
    }), remove.live("click", function() {
        var s = jQuery(this).parent().parent().attr("slug");
        instance.each(function(i, e) {
            s == jQuery(e).next().val() && (jQuery(e).parent().parent().css("background-color", ""), 
            "undefined" != typeof jQuery.browser.msie && 7 == parseInt(jQuery.browser.version) && jQuery(e).parent().parent().find("td").css("background-color", ""), 
            jQuery(e).val(""), jQuery(e).next().val(""));
        });
    }), wrapper.live("click", function() {
        closeGroupBox();
    }), sortButton.live("click", function() {
        jQuery("tbody tr", groupsBlock).each(function(gi, ge) {
            statsRow.each(function(si, se) {
                "undefined" != typeof jQuery(se).attr("wpp_attribute_group") ? jQuery(se).attr("wpp_attribute_group") == jQuery(ge).attr("slug") && jQuery(se).attr("sortpos", gi + 1) : jQuery(se).attr("sortpos", "9999");
            });
        });
        var sortlist = jQuery("tbody", statsTable), listitems = sortlist.children("tr").get();
        listitems.sort(function(a, b) {
            var compA = parseFloat(jQuery(a).attr("sortpos")), compB = parseFloat(jQuery(b).attr("sortpos"));
            return compB > compA ? -1 : compA > compB ? 1 : 0;
        }), jQuery.each(listitems, function(idx, itm) {
            sortlist.append(itm);
        });
    }), "undefined" != typeof jQuery.browser.msie && 7 == parseInt(jQuery.browser.version)) {
        var sortlist = jQuery("tbody", statsTable), listitems = sortlist.children("tr").get();
        jQuery.each(listitems, function(i, e) {
            jQuery(e).find("td").css("background-color", jQuery(e).css("background-color"));
        });
    }
};

var bindColorPicker = function(instance) {
    "function" == typeof window.jQuery.prototype.ColorPicker && (instance || (instance = jQuery("body")), 
    jQuery(".wpp_input_colorpicker", instance).ColorPicker({
        onSubmit: function(hsb, hex, rgb, el) {
            jQuery(el).val("#" + hex), jQuery(el).ColorPickerHide(), jQuery(el).trigger("change");
        },
        onBeforeShow: function() {
            jQuery(this).ColorPickerSetColor(this.value);
        }
    }).bind("keyup", function() {
        jQuery(this).ColorPickerSetColor(this.value);
    }));
}, updateRowNames = function(instance, allowRandomSlug) {
    if ("undefined" == typeof instance) return !1;
    if ("undefined" == typeof allowRandomSlug) var allowRandomSlug = !1;
    var this_row = jQuery(instance).parents("tr.wpp_dynamic_table_row"), old_slug = jQuery(this_row).attr("slug"), new_slug = jQuery(instance).val();
    if (new_slug = wpp_create_slug(new_slug), "" == new_slug) {
        if (!allowRandomSlug || jQuery(instance).hasClass("wpp_slug_can_be_empty")) return;
        new_slug = "random_" + Math.floor(1e3 * Math.random());
    }
    if (old_slug != new_slug) {
        jQuery(instance).addClass("wpp_current_slug_is_being_checked");
        var slugs = jQuery(this_row).parents("table").find("input.slug");
        slugs.each(function(k, v) {
            return jQuery(v).val() != new_slug || jQuery(v).hasClass("wpp_current_slug_is_being_checked") ? void 0 : (new_slug = "random_" + Math.floor(1e3 * Math.random()), 
            !1);
        }), jQuery(instance).removeClass("wpp_current_slug_is_being_checked"), jQuery(".slug", this_row).val(new_slug), 
        jQuery(this_row).attr("slug", new_slug), jQuery("input,select,textarea", this_row).each(function(i, e) {
            var old_name = jQuery(e).attr("name");
            if ("undefined" != typeof old_name && !jQuery(e).hasClass("wpp_no_change_name")) {
                var new_name = old_name.replace("[" + old_slug + "]", "[" + new_slug + "]");
                jQuery(e).attr("name", new_name);
            }
            var old_id = jQuery(e).attr("id");
            if ("undefined" != typeof old_id) {
                var new_id = old_id.replace(old_slug, new_slug);
                jQuery(e).attr("id", new_id);
            }
        }), jQuery("label", this_row).each(function(i, e) {
            if ("undefined" != typeof jQuery(e).attr("for")) {
                var old_for = jQuery(e).attr("for"), new_for = old_for.replace(old_slug, new_slug);
                jQuery(e).attr("for", new_for);
            }
        }), jQuery(".slug", this_row).trigger("change");
    }
};

jQuery(document).ready(function() {
    jQuery("#contextual-help-link").click(function() {
        jQuery("#contextual-help-wrap h3").removeClass("wpp_contextual_highlight");
    }), toggle_advanced_options(), jQuery(".wpp_toggle_contextual_help").live("click", function(event) {
        wpp_toggle_contextual_help(this, event);
    }), jQuery("#wpp_wpp_settings_configuration_automatically_insert_overview_").change(function() {
        jQuery(this).is(":checked") ? jQuery("li.wpp_wpp_settings_configuration_do_not_override_search_result_page_row").hide() : jQuery("li.wpp_wpp_settings_configuration_do_not_override_search_result_page_row").show();
    }), bindColorPicker(), jQuery(".wpp_add_row").live("click", function() {
        wpp_add_row(this);
    }), jQuery(".wpp_dynamic_table_row[new_row=true] input.slug_setter").live("keyup", function() {
        updateRowNames(this, !0);
    }), jQuery(".wpp_dynamic_table_row[new_row=true] select.slug_setter").live("change", function() {
        updateRowNames(this, !0);
    }), jQuery(".wpp_delete_row").live("click", function() {
        var parent = jQuery(this).parents("tr.wpp_dynamic_table_row"), table = jQuery(jQuery(this).parents("table").get(0)), row_count = table.find(".wpp_delete_row").length;
        return "true" != jQuery(this).attr("verify_action") || confirm("Are you sure?") ? (jQuery("input[type=text]", parent).val(""), 
        jQuery("input[type=checkbox]", parent).attr("checked", !1), row_count > 1 ? (jQuery(parent).hide(), 
        jQuery(parent).remove()) : jQuery(parent).attr("new_row", "true"), void table.trigger("row_removed", [ parent ])) : !1;
    }), jQuery(".wpp_attach_to_agent").live("click", function() {
        var agent_image_id = jQuery(this).attr("id");
        "" != agent_image_id && jQuery("#library-form").append('<input name="wpp_agent_post_id" type="text" value="' + agent_image_id + '" />').submit();
    }), "function" == typeof jQuery.fn.sortable && (jQuery("table.wpp_sortable tbody").sortable(), 
    jQuery("table.wpp_sortable tbody tr").live("mouseover mouseout", function(event) {
        "mouseover" == event.type ? jQuery(this).addClass("wpp_draggable_handle_show") : jQuery(this).removeClass("wpp_draggable_handle_show");
    }));
});