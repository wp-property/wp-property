jQuery(document).ready(function($) {
    var wraper = $(".rwmb-taxonomy-wrapper");
    wraper.each(function() {
        var $this = $(this), tagchecklist = $this.find(".tagchecklist"), datataxcounter = $this.attr("data-tax-counter"), slug = $this.attr("data-slug"), btnAdd = $this.find(".tagadd"), input = $this.find(".newtag"), template = $("#wpp-feps-taxnomy-template").html();
        input.autocomplete({
            source: window["availableTags_" + datataxcounter]
        });
        var autoComplete = input.autocomplete("instance");
        $(autoComplete.menu.activeMenu).on("click", ".ui-menu-item", function() {
            input.val($(this).html()), autoComplete.close();
        }), console.log(autoComplete.menu.activeMenu), btnAdd.on("click", function(e) {
            var tag = input.val(), taglistChild = tagchecklist.children();
            "" != tag && (tag = tag.split(","), taglistChild.each(function(index, item) {
                var val = $(item).find("input").val(), index = tag.indexOf(val);
                index >= 0 && tag.splice(index, 1);
            }), $.each(tag, function(index, item) {
                item = item.trim();
                var tmpl = _.template(template);
                tmpl = tmpl({
                    i: taglistChild.length,
                    val: item,
                    slug: slug
                }), tagchecklist.append(tmpl), input.val("");
            }), input.trigger("tax-added"));
        }), input.keypress(function(e) {
            return 13 == e.which ? (btnAdd.trigger("click"), e.preventDefault(), !1) : void 0;
        }), tagchecklist.on("click", ".ntdelbutton", function() {
            $(this).parent().parent().remove(), input.trigger("tax-removed");
        });
    });
});