jQuery(function($) {
    "use strict";
    var frame, template = $("#tmpl-rwmb-image-advanced").html();
    $("body").on("click", ".rwmb-image-advanced-upload", function(e) {
        e.preventDefault();
        var $uploadButton = $(this), $imageList = $uploadButton.siblings(".rwmb-images"), maxFileUploads = $imageList.data("max_file_uploads"), msg = maxFileUploads > 1 ? rwmbFile.maxFileUploadsPlural : rwmbFile.maxFileUploadsSingle;
        msg = msg.replace("%d", maxFileUploads), frame || (frame = wp.media({
            className: "media-frame rwmb-media-frame",
            multiple: !0,
            title: rwmbImageAdvanced.frameTitle,
            library: {
                type: "image"
            }
        })), frame.open(), frame.off("select"), frame.on("select", function() {
            var ids, selection = frame.state().get("selection").toJSON(), uploaded = $imageList.children().length;
            maxFileUploads > 0 && uploaded + selection.length > maxFileUploads && (maxFileUploads > uploaded && (selection = selection.slice(0, maxFileUploads - uploaded)), 
            alert(msg)), selection = _.filter(selection, function(attachment) {
                return 0 === $imageList.children("li#item_" + attachment.id).length;
            }), ids = _.pluck(selection, "id"), ids.length > 0 && $(selection).each(function(index, slec) {
                var input = $("<input />", {
                    name: $imageList.data("field_id") + "[]",
                    value: ids[index],
                    type: "hidden"
                }), tmpl = _.template(template, {
                    evaluate: /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape: /\{\{([^\}]+?)\}\}(?!\})/g
                });
                tmpl = tmpl({
                    attachments: [ slec ]
                }), tmpl = $(tmpl).append(input), $imageList.append(tmpl).trigger("update.rwmbFile");
            });
        });
    });
});