jQuery.extend(!0, wpp = wpp || {}, {
    ui: {
        feps: {
            ready: function() {
                var new_tab_href_id = 0, index = jQuery("#save_form").attr("action").indexOf("#"), url = jQuery("#save_form").attr("action").substring(0, index);
                jQuery(".wpp_feps_tabs").bind("tabsselect", function(event, ui) {
                    index = jQuery("#save_form").attr("action").indexOf("#"), url = jQuery("#save_form").attr("action").substring(0, index), 
                    jQuery("#save_form").attr("action", url + "#feps_form_" + jQuery(ui.panel).attr("feps_form_id"));
                }), jQuery(".wpp_feps_tabs").bind("tabscreate", function(event, ui) {
                    jQuery("#save_form").attr("action", url + window.location.hash);
                }), wpp.version_compare(jQuery.ui.version, "1.10", ">=") ? jQuery(".wpp_feps_tabs").tabs() : jQuery(".wpp_feps_tabs").tabs({
                    add: function(event, ui) {
                        jQuery(ui.panel).addClass("wpp_feps_form").attr("feps_form_id", new_tab_href_id), 
                        jQuery(ui.tab).parent().attr("feps_form_id", new_tab_href_id), jQuery(".wpp_feps_form table:first").clone().appendTo(ui.panel), 
                        wpp.ui.feps.init_close_btn(), wpp.ui.feps.set_default_field_values(ui.panel), jQuery('input[name*="wpp_feps[forms]"], select[name*="wpp_feps[forms]"], textarea[name*="wpp_feps[forms]"]', ui.panel).each(function(key, value) {
                            jQuery(value).attr("name", String(jQuery(value).attr("name")).replace(/wpp_feps\[forms\]\[\d.+?\]/, "wpp_feps[forms][" + new_tab_href_id + "]"));
                        }), wpp.ui.feps.update_dom();
                    }
                }), wpp.ui.feps.init_close_btn(), jQuery(".wpp_add_tab").click(function() {
                    if (new_tab_href_id = parseInt(1e6 * Math.random()), wpp.version_compare(jQuery.ui.version, "1.10", ">=")) {
                        var tabs = jQuery(".wpp_feps_tabs"), ul = tabs.find(">ul"), index = tabs.find(">ul >li").size(), panel = jQuery('<div id="feps_form_' + new_tab_href_id + '" class="wpp_feps_form" feps_form_id="' + new_tab_href_id + '"></div>');
                        jQuery("<li><a href='#feps_form_" + new_tab_href_id + "'><span>" + wpp.strings.feps.unnamed_form + "</span></a></li>").appendTo(ul), 
                        jQuery(".wpp_feps_form table:first").clone().appendTo(panel), panel.appendTo(tabs), 
                        tabs.tabs("refresh");
                        var tab = jQuery(">li:last", ul);
                        tab.attr("feps_form_id", new_tab_href_id), jQuery('input[name*="wpp_feps[forms]"], select[name*="wpp_feps[forms]"], textarea[name*="wpp_feps[forms]"]', panel).each(function(key, value) {
                            jQuery(value).attr("name", String(jQuery(value).attr("name")).replace(/wpp_feps\[forms\]\[\d.+?\]/, "wpp_feps[forms][" + new_tab_href_id + "]"));
                        }), wpp.ui.feps.init_close_btn(), wpp.ui.feps.set_default_field_values(panel), wpp.ui.feps.update_dom(), 
                        tabs.tabs("option", "active", index);
                    } else jQuery(".wpp_feps_tabs").tabs("add", "#feps_form_" + new_tab_href_id, wpp.strings.feps.unnamed_form), 
                    jQuery(".wpp_feps_tabs").tabs("active", jQuery(".wpp_feps_tabs").tabs("length") - 1);
                }), jQuery(".wpp_dynamic_table_row").each(function() {
                    jQuery(this).attr("new_row", "true"), wpp.ui.feps.is_active_required_option(jQuery(this));
                }), jQuery(document).on("change", ".wpp_feps_new_attribute", function() {
                    var parent = jQuery(this).parents(".wpp_dynamic_table_row"), title = jQuery("option:selected", this).text();
                    jQuery("input.title", parent).val(title), wpp.ui.feps.is_active_required_option(parent);
                }), jQuery(document).on("change", ".wpp_feps_form .form_title", function() {
                    var title = jQuery(this).val();
                    if ("" != title) {
                        var slug = wpp_create_slug(title), this_form = jQuery(this).parents(".wpp_feps_form"), form_id = jQuery(this_form).attr("feps_form_id");
                        jQuery(".wpp_feps_tabs .tabs li[feps_form_id=" + form_id + "] a span").text(title), 
                        jQuery("input.shortcode", this_form).val("[wpp_feps_form form=" + slug + "]"), jQuery("input.slug", this_form).val(slug);
                    }
                }), jQuery(document).on("click", "a.wpp_forms_remove_attribute", function() {
                    var row_to_be_removed = jQuery(this).attr("row"), context = jQuery(this).parents("div.wpp_feps_form .ud_ui_dynamic_table"), rows = jQuery("tr.wpp_dynamic_table_row", context);
                    rows.length > 2 && rows.each(function(k, v) {
                        jQuery(v).attr("random_row_id") == row_to_be_removed && jQuery(v).remove();
                    }), wpp.ui.feps.update_dom();
                }), jQuery(document).on("change", "select.wpp_feps_new_attribute", function() {
                    wpp.ui.feps.update_dom();
                }), jQuery(document).on("change", "input.imageslimit", function() {
                    jQuery(this).val() < 1 && jQuery(this).val(1);
                }), wpp.ui.feps.update_dom();
            },
            is_active_required_option: function(e) {
                var attribute = e.find(".wpp_feps_new_attribute").val(), req_option_wrap = e.find(".is_required");
                jQuery.inArray(attribute, [ "image_upload" ]) >= 0 ? req_option_wrap.css("visibility", "hidden").find("input").prop("disabled", !0) : req_option_wrap.css("visibility", "visible").find("input").prop("disabled", !1);
            },
            init_close_btn: function() {
                jQuery("ul.tabs li.ui-state-default:not(:first):not(:has(a.remove-tab))").append('<a href="javascript:void(0);" class="remove-tab">x</a>').mouseenter(function() {
                    jQuery("a.remove-tab", this).show();
                }).mouseleave(function() {
                    jQuery("a.remove-tab", this).hide();
                }), jQuery("ul.tabs li a.remove-tab").unbind("click"), jQuery("ul.tabs li a.remove-tab").click(function(e) {
                    var feps_form_id = jQuery(e.target).closest("li").attr("feps_form_id");
                    feps_form_id ? jQuery.ajax({
                        url: wpp.instance.ajax_url,
                        async: !1,
                        data: {
                            action: "wpp_feps_can_remove_form",
                            feps_form_id: feps_form_id
                        },
                        success: function(response) {
                            var data = eval("(" + response + ")");
                            if (data.success) if (wpp.version_compare(jQuery.ui.version, "1.10", ">=")) {
                                var tab = jQuery(".wpp_feps_tabs").find(".ui-tabs-nav li[feps_form_id='" + feps_form_id + "']").remove(), panelId = tab.attr("aria-controls");
                                jQuery("#" + panelId).remove(), jQuery(".wpp_feps_tabs").tabs("refresh");
                            } else jQuery(".wpp_feps_tabs").tabs("remove", jQuery(this).parent().index()); else alert(data.message);
                        },
                        error: function() {
                            alert(wpp.strings.feps.form_could_not_be_removed_1);
                        }
                    }) : alert(wpp.strings.feps.form_could_not_be_removed_2);
                });
            },
            set_default_field_values: function(context) {
                jQuery("input.form_title", context).val("Unnamed Form").trigger("change"), jQuery("input.shortcode", context).val("[wpp_feps_form form=" + wpp_create_slug("Unnamed Form " + jQuery(context).attr("feps_form_id")) + "]"), 
                jQuery("input.slug", context).val(wpp_create_slug("Unnamed Form " + jQuery(context).attr("feps_form_id"))), 
                jQuery(".ud_ui_dynamic_table", context).each(function() {
                    jQuery("tr.wpp_dynamic_table_row", jQuery(this)).find("textarea.description").val(""), 
                    jQuery("tr.wpp_dynamic_table_row:not(.required):not(:first)", jQuery(this)).remove();
                });
            },
            update_dom: function() {
                jQuery(".wpp_feps_sortable tbody").sortable({
                    items: "tr.wpp_dynamic_table_row:not(.required)"
                }), jQuery(document).on("mouseover", ".wpp_feps_sortable tr.wpp_dynamic_table_row", function() {
                    jQuery(this).addClass("wpp_draggable_handle_show");
                }), jQuery(document).on("mouseout", ".wpp_feps_sortable tr.wpp_dynamic_table_row", function() {
                    jQuery(this).removeClass("wpp_draggable_handle_show");
                }), jQuery(".wpp_feps_sortable tr.wpp_dynamic_table_row").each(function(k, v) {
                    var random_row_id = jQuery(v).attr("random_row_id");
                    jQuery(v).find("a.wpp_forms_remove_attribute").attr("row", random_row_id);
                }), jQuery(".ui-tabs-panel").each(function(k, v) {
                    var image_upload = !1, plan_images_col = jQuery(".wpp_plan_images_limit_col", v), feps_credits = jQuery("input.feps_credits", v);
                    jQuery("select.wpp_feps_new_attribute option:selected", v).each(function(i, e) {
                        return "image_upload" == jQuery(e).val() ? (image_upload = !0, !1) : void 0;
                    }), image_upload ? ((!feps_credits.length > 0 || feps_credits.length > 0 && !feps_credits.is(":checked")) && jQuery("input.imageslimit", v).parent().show(), 
                    plan_images_col.length > 0 && plan_images_col.show()) : (jQuery("input.imageslimit", v).parent().hide(), 
                    plan_images_col.length > 0 && plan_images_col.hide());
                });
            },
            on_added_row: function(added_row) {
                wpp.ui.feps.update_dom();
            }
        }
    }
}), jQuery(document).ready(wpp.ui.feps.ready);