jQuery.extend(wpp = wpp || {}, {
    ui: {
        fbtabs: {
            auto_complete_timer: null,
            ready: function() {
                var params = {
                    create: function() {
                        jQuery(".wpp_add_tab").click(wpp.ui.fbtabs.add_canvas), jQuery("input.slug_setter").live("change", wpp.ui.fbtabs.set_slug), 
                        jQuery(".wpp_fb_page_type").live("change", wpp.ui.fbtabs.update_fields_by_type).trigger("change"), 
                        jQuery("input.wpp_fb_property_input").live("keyup", wpp.ui.fbtabs.property_input_keyup).live("focus", wpp.ui.fbtabs.property_input_focus).live("blur", wpp.ui.fbtabs.property_input_blur).live("keydown", function(event) {
                            return 13 == event.keyCode ? (event.preventDefault(), !1) : void 0;
                        }), jQuery(".wpp_fb_app_id").live("change", wpp.ui.fbtabs.set_add_to_fb_link).trigger("change"), 
                        jQuery("a.wpp_fb_tabs_property_link").live("click", wpp.ui.fbtabs.property_link_click), 
                        jQuery(".current_slug").live("change", wpp.ui.fbtabs.set_urls).each(function(i, e) {
                            jQuery(e).trigger("change");
                        }), jQuery("#save_form").show(), wpp.ui.fbtabs.init_close_btn();
                    }
                };
                wpp.version_compare(jQuery.ui.version, "1.10", ">=") || (params.add = wpp.ui.fbtabs.canvas_added), 
                jQuery(".wpp_fb_tabs").tabs(params);
            },
            canvas_added: function(event, ui) {
                jQuery(".wpp_fb_tab table:first").clone().appendTo(ui.panel), wpp.ui.fbtabs.set_default_values(ui.panel), 
                wpp.ui.fbtabs.init_close_btn();
            },
            add_canvas: function() {
                var new_tab_href_id = parseInt(1e6 * Math.random());
                if (wpp.version_compare(jQuery.ui.version, "1.10", ">=")) {
                    var tabs = jQuery(".wpp_fb_tabs"), ul = tabs.find(">ul"), index = tabs.find(">ul >li").size(), panel = jQuery('<div id="fb_form_' + new_tab_href_id + '"></div>');
                    jQuery("<li><a href='#fb_form_" + new_tab_href_id + "'></a></li>").appendTo(ul), 
                    jQuery(".wpp_fb_tabs table:first").clone().appendTo(panel), panel.appendTo(tabs), 
                    tabs.tabs("refresh"), wpp.ui.fbtabs.set_default_values(panel), wpp.ui.fbtabs.init_close_btn(), 
                    tabs.tabs("option", "active", index);
                } else jQuery(".wpp_fb_tabs").tabs("add", "#fb_canvas_" + new_tab_href_id, ""), 
                jQuery(".wpp_fb_tabs").tabs("select", jQuery(".wpp_fb_tabs").tabs("length") - 1);
            },
            set_slug: function(event) {
                var value = jQuery(event.currentTarget).val(), panel = jQuery(jQuery(event.currentTarget).parents("div.ui-tabs-panel").get(0)), old_slug = jQuery("input.current_slug", panel).val(), new_slug = wpp_create_slug(value);
                jQuery('a[href="#' + panel.attr("id") + '"]').html(value).closest("li").attr("fb_canvas_id", new_slug), 
                jQuery("input.current_slug", panel).val(new_slug).trigger("change"), jQuery("input,select, textarea", panel).each(function(i, e) {
                    var old_name = jQuery(e).attr("name");
                    if ("undefined" != typeof old_name) {
                        var new_name = old_name.replace("[" + old_slug + "]", "[" + new_slug + "]");
                        jQuery(e).attr("name", new_name);
                    }
                    var old_id = jQuery(e).attr("id");
                    if ("undefined" != typeof old_id) {
                        var new_id = old_id.replace(old_slug, new_slug);
                        jQuery(e).attr("id", new_id);
                    }
                    jQuery("label", panel).each(function(i, e) {
                        if ("undefined" != typeof jQuery(e).attr("for")) {
                            var old_for = jQuery(e).attr("for"), new_for = old_for.replace(old_slug, new_slug);
                            jQuery(e).attr("for", new_for);
                        }
                    });
                });
            },
            set_urls: function() {
                var secure_url, debug_url, slug = jQuery(this).val(), panel = jQuery(jQuery(this).parents("div.ui-tabs-panel").get(0)), url = wpp.instance.ajax_url;
                wpp.instance.is_permalink ? (url += "/" + wpp.instance.fbtabs.query_var + "/" + slug + "/", 
                debug_url = url + "?signed_request=" + md5("debug::" + slug)) : (url += "?" + wpp.instance.fbtabs.query_var + "=" + slug, 
                debug_url = url + "&signed_request=" + md5("debug::" + slug)), secure_url = url.replace("http://", "https://"), 
                jQuery("input.default_canvas_url", panel).val(url), jQuery("input.secure_canvas_url", panel).val(secure_url), 
                jQuery("input.debug_canvas_url", panel).val(debug_url);
            },
            set_default_values: function(ui) {
                jQuery('input.wpp_default_empty[type="text"]', ui).val(""), jQuery('input.wpp_default_empty[type="checkbox"]', ui).attr("checked", !1), 
                jQuery(".wpp_fb_page_type", ui).val("page").trigger("change"), jQuery("input.slug_setter", ui).val(wpp.strings.fbtabs.unnamed_canvas).trigger("change");
            },
            set_add_to_fb_link: function(event) {
                var panel = jQuery(jQuery(event.currentTarget).parents("div.ui-tabs-panel").get(0)), value = jQuery(event.currentTarget).val(), button = jQuery("a.wpp_fb_tabs_add_to_page", panel);
                button.attr("href", "https://www.facebook.com/dialog/pagetab?app_id=" + value + "&redirect_uri=http%3A%2F%2Fwww.facebook.com"), 
                "" == value ? button.hide() : button.show();
            },
            update_fields_by_type: function(event) {
                var panel = jQuery(jQuery(event.currentTarget).parents("div.ui-tabs-panel").get(0)), value = jQuery(event.currentTarget).val();
                switch (value) {
                  case "page":
                    jQuery(".wpp_fb_type_property", panel).hide().attr("disabled", "disabled"), jQuery(".wpp_fb_type_page", panel).show().removeAttr("disabled");
                    break;

                  case "property":
                    jQuery(".wpp_fb_type_page", panel).hide().attr("disabled", "disabled"), jQuery(".wpp_fb_type_property", panel).show().removeAttr("disabled");
                }
            },
            init_close_btn: function() {
                jQuery("ul.tabs li.ui-state-default:not(:first):not(:has(a.remove-tab))").append('<a href="javascript:void(0);" class="remove-tab">x</a>').mouseenter(function() {
                    jQuery("a.remove-tab", this).show();
                }).mouseleave(function() {
                    jQuery("a.remove-tab", this).hide();
                }), jQuery("ul.tabs li a.remove-tab").unbind("click"), jQuery("ul.tabs li a.remove-tab").click(function(e) {
                    if (wpp.version_compare(jQuery.ui.version, "1.10", ">=")) {
                        var index = jQuery(this).parent().index();
                        jQuery(".wpp_fb_tabs").tabs("option", "active") == index && jQuery(".wpp_fb_tabs").tabs("option", "active", index - 1);
                        var tab = jQuery(".wpp_fb_tabs").find(".ui-tabs-nav li:eq(" + index + ")").remove(), panelId = tab.attr("aria-controls");
                        jQuery("#" + panelId).remove(), jQuery(".wpp_feps_tabs").tabs("refresh");
                    } else jQuery(".wpp_fb_tabs").tabs("remove", jQuery(e).parent().index());
                });
            },
            property_input_keyup: function() {
                var typing_timeout = 600, input = jQuery(this), panel = input.parents("div.ui-tabs-panel").get(0);
                jQuery(".wpp_fb_tabs_found_properies", panel).hide().empty(), window.clearTimeout(wpp.ui.fbtabs.auto_complete_timer), 
                wpp.ui.fbtabs.auto_complete_timer = window.setTimeout(function() {
                    jQuery(".wpp_fb_tabs_loader_image", panel).show(), jQuery.post(wpp.instance.ajax_url, {
                        action: "wpp_fb_tabs_get_properties",
                        s: input.val()
                    }, function(response) {
                        jQuery(".wpp_fb_tabs_loader_image", panel).hide(), response && "object" == typeof response && jQuery.each(response, function() {
                            jQuery(".wpp_fb_tabs_found_properies", panel).width(input.outerWidth()).append('<li><a class="wpp_fb_tabs_property_link" href="' + this.id + '">' + this.title + "</a></li>").show();
                        });
                    }, "json");
                }, typing_timeout);
            },
            property_input_focus: function() {
                var panel = jQuery(this).parents("div.ui-tabs-panel").get(0);
                jQuery(".wpp_fb_tabs_found_properies", panel).hide().empty();
            },
            property_input_blur: function() {
                var panel = jQuery(this).parents("div.ui-tabs-panel").get(0);
                jQuery(".wpp_fb_tabs_found_properies", panel).delay(300).queue(function() {
                    jQuery(this).hide().empty();
                });
            },
            property_link_click: function() {
                var a = jQuery(this), panel = a.parents("div.ui-tabs-panel").get(0);
                return jQuery(".wpp_fb_property_input", panel).val(a.text()), jQuery(".wpp_fb_property_input_hidden", panel).val(a.attr("href")), 
                jQuery(".wpp_fb_tabs_found_properies", panel).hide().empty(), !1;
            }
        }
    }
}), jQuery(document).ready(wpp.ui.fbtabs.ready);