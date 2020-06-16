jQuery(document).ready(function() {
    function wpp_feps_checkout_event(event, result, target, gateway) {
        jQuery(".wpi_checkout_payment_response", target).remove(), jQuery(".wpp_feps_message").hide().removeClass("error").html("");
        var message = "", error = !1;
        switch (event) {
          case "wpi_spc_validation_fail":
            message = wpp.strings.validation_error, error = !0;
            break;

          case "wpi_spc_processing_failure":
            message = result.message, error = !0;
            break;

          case "wpi_spc_success":
            jQuery(target).parents(".wpp_feps_checkout_wrapper").hide(), jQuery(".wpp_feps_change_subscription_plan").hide(), 
            message = result.message;
        }
        var property_id = null;
        "undefined" != typeof result.callback && (property_id = "undefined" != typeof result.callback.post_data["wpp::feps::property_id"][0] ? result.callback.post_data["wpp::feps::property_id"][0] : null), 
        wpp_feps_checkout_message(message, property_id, error);
    }
    function wpp_feps_checkout_message(message, property_id, error) {
        "undefined" != typeof error && error ? jQuery(".wpp_feps_message").addClass("error") : jQuery(".wpp_feps_message").removeClass("error"), 
        window.wpp_feps_checkout_message = message, jQuery(document).trigger("wpp_feps_checkout_message", [ property_id, error ]), 
        setTimeout(function() {
            window.wpp_feps_checkout_message && "" != window.wpp_feps_checkout_message && jQuery(".wpp_feps_message").html(window.wpp_feps_checkout_message).show();
        }, 50);
    }
    jQuery("form.wpp_feps_withdraw_credits").submit(function() {
        jQuery(".wpp_feps_message").html(""), jQuery(".wpp_feps_message").removeClass("error");
        var data = jQuery(this).serialize();
        return jQuery.post(wpp.instance.ajax_url, data, function(response) {
            var error = !1;
            response.success ? (jQuery(".feps_spc_details_wrapper .submit_action_wrapper").remove(), 
            jQuery(".wpp_feps_change_subscription_plan").remove()) : error = !0, wpp_feps_checkout_message(response.message, response.property_id, error);
        }, "JSON"), !1;
    }), jQuery(".add_credits input").click(function() {
        jQuery(".feps_spc_details_wrapper .submit_action_wrapper").remove(), jQuery(".wpp_feps_checkout_wrapper").toggle("slow");
    }), jQuery(".wpp_feps_checkout_wrapper form").bind("submit", function() {
        jQuery(".wpp_feps_message").removeClass("error");
    }), jQuery(document).bind("wpi_spc_validation_fail", function(event, result, target, gateway) {
        wpp_feps_checkout_event("wpi_spc_validation_fail", result, target, gateway);
    }), jQuery(document).bind("wpi_spc_success", function(event, result, target, gateway) {
        wpp_feps_checkout_event("wpi_spc_success", result, target, gateway);
    }), jQuery(document).bind("wpi_spc_processing_failure", function(event, result, target, gateway) {
        wpp_feps_checkout_event("wpi_spc_processing_failure", result, target, gateway);
    });
});