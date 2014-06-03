jQuery(document).ready(function() {
    jQuery(".wpp_settings_page form").submit(function() {
        var error_field = {
            object: !1,
            tab_index: !1
        };
        return jQuery("form #wpp_settings_tabs :input[validation_required=true],form #wpp_settings_tabs .wpp_required_field :input,form #wpp_settings_tabs :input[required],form #wpp_settings_tabs :input.slug_setter").each(function() {
            var dynamic_table_row_count = jQuery(this).closest(".wpp_dynamic_table_row").parent().children("tr.wpp_dynamic_table_row").length;
            return jQuery(this).val() || 1 == dynamic_table_row_count ? void 0 : (error_field.object = this, 
            error_field.tab_index = jQuery('#wpp_settings_tabs a[href="#' + jQuery(error_field.object).closest(".ui-tabs-panel").attr("id") + '"]').parent().index(), 
            !1);
        }), 0 != error_field.object ? ("undefined" != typeof error_field.tab_index && jQuery("#wpp_settings_tabs").tabs("option", "active", error_field.tab_index), 
        jQuery(error_field.object).addClass("ui-state-error").one("keyup", function() {
            jQuery(this).removeClass("ui-state-error");
        }), jQuery(error_field.object).focus(), !1) : void 0;
    });
});