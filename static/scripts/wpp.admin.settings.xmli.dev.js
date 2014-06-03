wpp = wpp || {}, wpp.ui = wpp.ui || {}, jQuery.extend(wpp.ui, {
    xmli: {
        ready: function() {
            jQuery(".wppi_delete_all_orphan_attachments").click(function() {
                var notice_container = jQuery(".wppi_delete_all_orphan_attachments_result").show();
                jQuery(notice_container).html("Deleting all unattached images. You can close your browser, the operation will continue until completion."), 
                jQuery.post(wpp.instance.ajax_url, {
                    action: "wpp_property_import_handler",
                    wpp_action: "delete_all_orphan_attachments"
                }, function(result) {
                    jQuery(notice_container).html(result && result.success ? result.ui : "An error occured.");
                }, "json");
            }), jQuery("#wpp_ajax_show_xml_imort_history").click(function() {
                jQuery(".wpp_ajax_show_xml_imort_history_result").html(""), jQuery.post(wpp.instance.ajax_url, {
                    action: "wpp_ajax_show_xml_imort_history"
                }, function(data) {
                    jQuery(".wpp_ajax_show_xml_imort_history_result").show(), jQuery(".wpp_ajax_show_xml_imort_history_result").html(data);
                });
            });
        }
    }
}), jQuery(document).ready(wpp.ui.xmli.ready);