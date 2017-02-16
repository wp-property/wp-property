jQuery(document).ready(function($) {
    var wraper = $(".wpp-taxonomy-select-combobox");
    wraper.each(function() {
        var $this = $(this), taxonomy = $this.attr("data-taxonomy"), input_terms = $this.find(".wpp-terms-input"), btntoggle = $this.find(".select-combobox-toggle"), taxList = {}, query = {
            action: "term_autocomplete",
            taxonomy: taxonomy
        }, url = ajaxurl + "?" + jQuery.param(query);
        input_terms.each(function() {
            var input = $(this);
            input.one("focus", function() {
                input.hasClass("ui-autocomplete-loading") || input.hasClass("ui-autocomplete-input") || (input_terms.addClass("ui-autocomplete-loading"), 
                $.ajax(url).done(function(data) {
                    taxList = data, input_terms.removeClass("ui-autocomplete-loading"), input_terms.autocomplete({
                        minLength: 0,
                        source: data,
                        focus: function(event, ui) {
                            var input = $(this);
                            return input.val(ui.item.label), onInputChange.apply(input), !1;
                        },
                        select: function(event, ui) {
                            var input = $(this);
                            return input.val(ui.item.label), onInputChange.apply(input), !1;
                        }
                    }), input_terms.each(function() {
                        var input = $(this);
                        input.autocomplete("instance")._renderItem = function(ul, item) {
                            var exist = item.label == input.val(), selected = exist ? "ui-state-selected" : "";
                            return $("<li>").append("<a class='" + selected + "'>" + item.label + "</a>").appendTo(ul);
                        }, input.autocomplete("instance")._resizeMenu = function() {
                            var ul = this.menu.element;
                            ul.outerWidth(input.outerWidth() + btntoggle.outerWidth());
                        }, input.autocomplete("widget").addClass("wpp-autocomplete");
                    }), input_terms.on("focus", function() {
                        var input = $(this);
                        wasOpen = input.autocomplete("widget").is(":visible"), wasOpen || input.autocomplete("search", input.val());
                    }), input.is(":focus") && input.autocomplete("search", "");
                }));
            });
        }), input_terms.on("keyup change input", function() {
            onInputChange.call(this);
        });
        var wasOpen, onInputChange = function(e) {
            $input = $(this);
            var value = $input.val();
            $term_input = $input.siblings(".wpp-terms-id-input"), $.each(taxList, function(i, tax) {
                return tax.label == $input.val() ? (value = "tID_" + tax.value, !1) : void 0;
            }), $term_input.val(value);
        };
        btntoggle.on("click", function(e) {
            var input = $(this).siblings("input.wpp-terms-input");
            return input.hasClass("ui-autocomplete-input") ? wasOpen ? void btntoggle.blur() : (input.focus(), 
            void input.autocomplete("search", "")) : input.focus();
        }).mousedown(function() {
            var input = $(this).siblings("input.wpp-terms-input");
            input.hasClass("ui-autocomplete-input") && (wasOpen = input.autocomplete("widget").is(":visible"));
        }), $this.find(".term-parent").hide();
    }), $(document).on("click", ".assign-parent", function() {
        var parent = $(this).siblings(".term-parent").toggle();
        parent.is(":hidden") && parent.val("");
    });
});