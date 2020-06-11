jQuery(document).ready(function() {
    jQuery(".wpp_feps_login_box #login").on("submit", function(e) {
        jQuery(".wpp_feps_login_box .status_login").show().text(feps_login_object.loadingmessage), 
        jQuery.ajax({
            type: "POST",
            dataType: "json",
            url: feps_login_object.ajaxurl,
            data: {
                action: "feps_user_login",
                username: jQuery(".wpp_feps_login_box #username").val(),
                password: jQuery(".wpp_feps_login_box #password").val(),
                "feps-login": jQuery(".wpp_feps_login_box #feps-login").val()
            },
            success: function(data) {
                jQuery(".wpp_feps_login_box .status_login").text(data.message), 1 == data.loggedin && (document.location.href = feps_login_object.redirecturl);
            }
        }), e.preventDefault();
    });
});