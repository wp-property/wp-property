"function" == typeof wpp.format_currency && wpp.format_currency(".wpp_feps_input_wrapper input.wpp_currency"), 
"function" == typeof wpp.format_number && wpp.format_number(".wpp_feps_input_wrapper input.wpp_numeric"), 
"function" != typeof wpp.init_feps_form && (wpp.init_feps_form = function(this_form) {
    function submit_form() {
        if ("true" == jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled")) return submit_button.removeAttr("disabled"), 
        !1;
        this_form.prev().hide().removeClass("error");
        var data = this_form.serialize();
        jQuery.post(wpp.instance.ajax_url, {
            action: "wpp_feps_save_property",
            data: data
        }, function(response) {
            response.success ? response.credentials_verified && response.callback && response.callback ? window.location.href = response.callback : (this_form.prev().html(response.message).show(), 
            this_form.remove()) : (this_form.prev().addClass("error"), this_form.prev().html(response.message).show());
        }, "json");
    }
    function wpp_feps_lookup_email() {
        var user_email = jQuery(".wpp_feps_user_email", this_form).val(), user_password = jQuery(".wpp_feps_user_password", this_form).val();
        return "function" != typeof this_form.valid || this_form.valid() ? "true" === wpp.instance.user_logged_in ? (submit_form(0), 
        !1) : "" == user_email ? (jQuery(".wpp_feps_ajax_message", this_form).text(wpp.strings.type_email), 
        jQuery(".wpp_feps_user_email", this_form).focus(), submit_button.removeAttr("disabled"), 
        !1) : (jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled", !0), 
        jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_processing", !0), "" == user_password ? (jQuery(".wpp_feps_ajax_message", this_form).text(wpp.strings.checking_account), 
        jQuery(".wpp_feps_row_wrapper.user_password", this_form).hide()) : jQuery(".wpp_feps_ajax_message", this_form).text(wpp.strings.checking_credentials), 
        void jQuery.post(wpp.instance.ajax_url, {
            action: "wpp_feps_email_lookup",
            user_email: user_email,
            user_password: user_password
        }, function(response) {
            jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_processing", !1), "true" == response.email_exists ? "true" == response.credentials_verified ? (jQuery(".wpp_feps_ajax_message", this_form).text(wpp.strings.credentials_verified), 
            jQuery(".wpp_feps_row_wrapper.user_password", this_form).show(), jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled", !1), 
            submit_form()) : "true" == response.invalid_credentials ? (jQuery(".wpp_feps_ajax_message", this_form).text(wpp.strings.credentials_incorrect), 
            submit_button.removeAttr("disabled")) : (jQuery(".wpp_feps_row_wrapper.user_password", this_form).show(), 
            jQuery(".wpp_feps_ajax_message", this_form).text(wpp.strings.account_found_type_password), 
            submit_button.removeAttr("disabled")) : (jQuery(".wpp_feps_row_wrapper.user_password", this_form).hide(), 
            jQuery(".wpp_feps_ajax_message", this_form).text(wpp.strings.account_created_check_email), 
            jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled", !1), submit_form());
        }, "json")) : (submit_button.removeAttr("disabled"), !1);
    }
    if ("object" != typeof this_form && (this_form = jQuery(this_form)), !this_form.length) return !1;
    var submit_button = jQuery('input[type="submit"]', this_form);
    this_form.validate({
        submitHandler: function(form) {
            submit_button.attr("disabled", "disabled"), wpp_feps_lookup_email(form);
        },
        errorPlacement: function(error, element) {
            return;
        },
        errorElement: !1,
        errorClass: "wpp_feps_input_error",
        rules: {
            "wpp_feps_data[user_email]": {
                required: !0,
                email: !0
            }
        }
    });
});