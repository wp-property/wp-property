!function($) {
    $.fn.loadingdots = function(options) {
        var i = 0, settings = $.extend({}, {
            duration: 250
        }, options), bucle = function() {
            var cycle, $el = $(this), timing = i * settings.duration, first = !0;
            i++, (cycle = function() {
                first ? first = !1 : timing = 0, $el.delay(timing).fadeTo(1e3, .4).fadeTo(1e3, 0, cycle);
            })(first);
        };
        return this.each(function() {
            $(this).html('<span class="dot"></span><span class="dot"></span><span class="dot"></span>').find(".dot").each(bucle);
        });
    };
}(jQuery);