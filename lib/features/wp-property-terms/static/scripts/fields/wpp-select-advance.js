jQuery(document).ready(function($) {
    var wraper = $(".rwmb-wpp-taxonomy-wrapper");
    wraper.each(function() {
        var $this = $(this), tagchecklist = $this.find(".tagchecklist"), attrName = ($this.attr("data-tax-counter"), 
        $this.attr("data-name")), taxonomy = $this.attr("data-taxonomy"), btnAdd = $this.find(".taxadd"), input_terms = $this.find(".wpp-terms-input"), template = $("#wpp-terms-taxnomy-template").html(), taxList = {}, query = {
            action: "term_autocomplete",
            taxonomy: taxonomy
        }, url = ajaxurl + "?" + jQuery.param(query);
        input_terms.each(function() {
            var input = $(this);
            input.one("focus", function() {
                input.hasClass("ui-autocomplete-loading") || input.hasClass("ui-autocomplete-input") || (input_terms.addClass("ui-autocomplete-loading"), 
                $.ajax(url).done(function(data) {
                    input_terms.removeClass("ui-autocomplete-loading"), taxList = data, input_terms.autocomplete({
                        minLength: 0,
                        source: data,
                        focus: function(event, ui) {
                            var input = $(this);
                            return input.val(ui.item.label), !1;
                        },
                        select: function(event, ui) {
                            var input = $(this);
                            return input.val(ui.item.label), !1;
                        }
                    }), input_terms.each(function() {
                        var input = $(this);
                        input.autocomplete("instance")._renderItem = function(ul, item) {
                            var exist = is_already_added(item.value, tagchecklist.children()), selected = exist ? "ui-state-selected" : "";
                            return $("<li>").append("<a class='" + selected + "'>" + item.label + "</a>").appendTo(ul);
                        }, input.autocomplete("widget").addClass("wpp-autocomplete");
                    }), input_terms.on("focus", function() {
                        var input = $(this);
                        wasOpen = input.autocomplete("widget").is(":visible"), wasOpen || input.autocomplete("search", "");
                    }), input.is(":focus") && input.autocomplete("search", "");
                }));
            });
        }), btnAdd.on("click", function(e) {
            var input_term = $(this).siblings(".wpp-terms-term"), input_parent = $(this).siblings(".wpp-terms-parent"), parent = input_parent.val(), tag = input_term.val(), taglistChild = tagchecklist.children();
            return "" == tag ? void input_term.focus() : (tag = tag.split(","), $.each(taxList, function(i, tax) {
                return parent == tax.label ? (parent = "tID_" + tax.value, !1) : void 0;
            }), $.each(tag, function(index, item) {
                var item = item.trim(), label = item, exist = !1;
                if ($.each(taxList, function(i, tax) {
                    return item == tax.label ? (item = "tID_" + tax.value, label = tax.label, !1) : void 0;
                }), input_name = attrName + "[" + tagchecklist.children().length + "]", exist = is_already_added(item, taglistChild), 
                1 != exist) {
                    var tmpl = _.template(template), rendered = tmpl({
                        label: label,
                        term: item,
                        name: input_name,
                        parent: parent
                    });
                    tagchecklist.append(rendered);
                }
            }), void input_terms.val(""));
        }), input_terms.keypress(function(e) {
            return 13 == e.which ? (btnAdd.trigger("click"), e.preventDefault(), !1) : void 0;
        });
    }), $(document).on("click", ".assign-parent", function() {
        var parent = $(this).siblings(".wpp-terms-parent").toggle();
        parent.is(":hidden") && parent.val("");
    }), $(document).on("click", ".rwmb-wpp-taxonomy-wrapper .tagchecklist .ntdelbutton", function() {
        $(this).parent().remove();
    });
    var is_already_added = function(value, tagList) {
        return exist = !1, $.each(tagList, function(i, tag) {
            var val = $(tag).find("input").val();
            return value == val ? (exist = !0, !1) : void 0;
        }), exist;
    };
});