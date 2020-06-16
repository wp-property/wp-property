jQuery(function($) {
    "use strict";
    var template = $("#tmpl-rwmb-file-advanced").html();
    $("body").on("click", ".rwmb-file-advanced-upload", function(e) {
        e.preventDefault();
        var frame, $uploadButton = $(this), $fileList = $uploadButton.siblings(".rwmb-uploaded"), maxFileUploads = $fileList.data("max_file_uploads"), mimeType = $fileList.data("mime_type"), msg = maxFileUploads > 1 ? rwmbFile.maxFileUploadsPlural : rwmbFile.maxFileUploadsSingle, frameOptions = {
            className: "media-frame rwmb-file-frame",
            multiple: !0,
            title: rwmbFileAdvanced.frameTitle
        };
        msg = msg.replace("%d", maxFileUploads), mimeType && (frameOptions.library = {
            type: mimeType
        }), frame = wp.media(frameOptions), frame.open(), frame.off("select"), frame.on("select", function() {
            var ids, selection = frame.state().get("selection").toJSON(), uploaded = $fileList.children().length;
            maxFileUploads > 0 && uploaded + selection.length > maxFileUploads && (maxFileUploads > uploaded && (selection = selection.slice(0, maxFileUploads - uploaded)), 
            alert(msg)), console.log("selection::"), console.log(selection), selection = _.filter(selection, function(attachment) {
                return 0 === $fileList.children("li#item_" + attachment.id).length;
            }), ids = _.pluck(selection, "id"), ids.length > 0 && $(selection).each(function(index, slec) {
                var input = $("<input />", {
                    name: $fileList.data("field_id") + "[]",
                    value: ids[index],
                    type: "hidden"
                }), tmpl = _.template(template, {
                    evaluate: /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape: /\{\{([^\}]+?)\}\}(?!\})/g
                });
                tmpl = tmpl({
                    attachments: [ slec ]
                }), tmpl = $(tmpl).append(input), $fileList.append(tmpl).trigger("update.rwmbFile");
            });
        });
    });
});