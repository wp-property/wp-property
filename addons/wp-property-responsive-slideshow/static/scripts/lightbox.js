!function($) {
    $.fn.wpp_rs_lb = function(prop) {
        function closeLB(s, e) {
            var touches = s.touches, diff = touches.currentY - touches.startY;
            return diff > 100 ? (e.preventDefault(), setTimeout(function() {
                hideLightbox(e);
            }, 50), !1) : void 0;
        }
        function setViewOriginalHref(s) {
            var activeIndex = s.activeIndex, href = $(s.slides[activeIndex]).data("src");
            lb.find(".viewOriginal").attr("href", href);
        }
        function showLightbox(img) {
            var activeIndex = jQuery(img).parent().index();
            originalParams = jQuery.extend(!0, {}, options.galleryTop.params), options.galleryTop.params.slidesPerView = 1, 
            options.galleryTop.params.slidesPerColumn = 1, options.galleryTop.params.lightBox = !0, 
            options.galleryTop.params.noSwiping = !0, options.galleryTop.params.initialSlide = activeIndex, 
            options.galleryTop.params.autoHeight = !1, options.galleryTop.params.slider_width = !1, 
            options.galleryTop.params.slider_height = !1, options.galleryTop.params.slideshow_layout = !1, 
            options.galleryTop.translate = 0, options.galleryThumbs.activeIndex = activeIndex, 
            loadFullImageLazy(), lb.addClass("lightbox"), $("#wpadminbar").hide(), options.galleryTop.destroy(!1, !0), 
            options.galleryTop.init(), options.galleryTop.lazy.load(), options.galleryThumbs.onResize && options.galleryThumbs.onResize(), 
            options.galleryTop.enableKeyboardControl(), $(document).on("keydown", lbHandleKeyboard), 
            options.galleryTop.on("touchEnd", closeLB), $("body").css({
                overflow: "hidden"
            });
        }
        function loadFullImageLazy(index) {
            $.each(options.galleryTop.slides, function(index, item) {
                var slide = $(item), src = slide.data("src");
                slide.find("img").addClass("swiper-lazy").attr("data-src", src).attr("srcset", "").attr("sizes", "");
            });
        }
        function hideLightbox(e) {
            var activeIndex = options.galleryTop.activeIndex;
            options.galleryTop.params = jQuery.extend(!0, {}, originalParams), options.galleryTop.params.initialSlide = activeIndex, 
            options.galleryTop.params.lightBox = !1, options.galleryTop.translate = 0, $(options.galleryTop.slides).removeClass("swiper-lazy"), 
            lb.removeClass("lightbox"), $("#wpadminbar").show(), options.galleryTop.destroy(!1, !0), 
            options.galleryTop.init(), options.galleryTop.enableKeyboardControl(), options.galleryThumbs.onResize && options.galleryThumbs.onResize(), 
            $(document).off("keydown", lbHandleKeyboard), options.galleryTop.off("touchEnd", closeLB), 
            $("body").css({
                overflow: ""
            });
        }
        function lbHandleKeyboard(e) {
            switch (e.keyCode) {
              case 27:
                hideLightbox(e), e.preventDefault && e.preventDefault();
            }
        }
        var slideActiveClass, lb = this, options = $.extend({
            galleryTop: [],
            galleryThumbs: [],
            sliderType: ""
        }, prop), originalParams = jQuery.extend(!0, {}, options.galleryTop.params);
        return slideActiveClass = options.galleryTop.isGrid() ? ".gallery-top .swiper-slide img" : ".gallery-top .swiper-slide.swiper-slide-active img", 
        lb.on("click", slideActiveClass, function(e) {
            return lb.hasClass("lightbox") || showLightbox(this), !1;
        }), lb.on("click", ".modal-header .close", function(e) {
            hideLightbox(e);
        }), setViewOriginalHref(options.galleryTop), options.galleryTop.on("slideChangeStart", function(s) {
            setViewOriginalHref(s);
        }), this;
    };
}(jQuery);