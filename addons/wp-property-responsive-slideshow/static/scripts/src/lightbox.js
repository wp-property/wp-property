/**
 * Script need to be improve
 * Author: Md. Alimuzzaman Alim
 * 
 */

(function($){

  // Defining our jQuery plugin

  $.fn.wpp_rs_lb = function(prop){
    var lb = this;
    // Default parameters

    var options = $.extend({
      galleryTop:[],
      galleryThumbs:[],
      sliderType:''
    },prop);
    var originalParams = jQuery.extend(true, {}, options.galleryTop.params);
    var slideActiveClass;
    //Click event on element
    if(options.galleryTop.isGrid())
      slideActiveClass = '.gallery-top .swiper-slide img';
    else
      slideActiveClass = '.gallery-top .swiper-slide.swiper-slide-active img';

    lb.on('click', slideActiveClass, function(e){
      if(!lb.hasClass('lightbox')){
        showLightbox(this);
      }
      return false;
    });
    //Click event on element
    lb.on('click', '.modal-header .close', function(e){
      hideLightbox(e);
    });

    setViewOriginalHref(options.galleryTop);

    //
    options.galleryTop.on('slideChangeStart', function(s){
      // Setting view originals link for current slide.
      setViewOriginalHref(s); 

    });

    //Swipe down to close
    function closeLB(s, e){
      var touches = s.touches;
      var diff = touches.currentY - touches.startY;
      if(diff>100){
        e.preventDefault();
        setTimeout(function() {
          hideLightbox(e);
        },50);
        return false;
      }
    }

    function handleMoveImage(s, e){
      e.stopPropagation();
      e.stopImmediatePropagation();
      return false;
    }

    function setViewOriginalHref(s){
      var activeIndex = s.activeIndex;
      var href = $(s.slides[activeIndex]).data('src');
      lb.find('.viewOriginal').attr('href', href);
    }

    function showLightbox(img){
      var activeIndex = jQuery(img).parent().index();
      originalParams = jQuery.extend(true, {}, options.galleryTop.params);
      options.galleryTop.params.slidesPerView = 1;
      options.galleryTop.params.slidesPerColumn = 1;
      options.galleryTop.params.lightBox = true;
      options.galleryTop.params.noSwiping = true;
      options.galleryTop.params.initialSlide = activeIndex;
      options.galleryTop.params.autoHeight = false;
      options.galleryTop.params.slider_width = false;
      options.galleryTop.params.slider_height = false;
      options.galleryTop.params.slideshow_layout = false;
      options.galleryTop.translate = 0;
      options.galleryThumbs.activeIndex = activeIndex;

      loadFullImageLazy();
      lb.addClass('lightbox');
      $('#wpadminbar').hide();

      options.galleryTop.destroy(false, true);
      options.galleryTop.init();
      options.galleryTop.lazy.load();
      if(options.galleryThumbs.onResize)
        options.galleryThumbs.onResize();

      options.galleryTop.enableKeyboardControl();
      $(document).on('keydown', lbHandleKeyboard);
      options.galleryTop.on('touchEnd', closeLB);
      $('body').css({'overflow':'hidden'});
    }

    function loadFullImageLazy(index){
      $.each(options.galleryTop.slides, function(index, item){
        var slide = $(item);
        var src = slide.data('src');
        var img = slide.find('img').addClass('swiper-lazy')
                       .attr('data-src', src)
                       .attr('srcset', "")
                       .attr('sizes', "")
      });
    }

    function hideLightbox(e){
      var activeIndex = options.galleryTop.activeIndex;

      options.galleryTop.params = jQuery.extend(true, {}, originalParams); // Restoring original params object.
      
      options.galleryTop.params.initialSlide = activeIndex;
      options.galleryTop.params.lightBox = false;
      options.galleryTop.translate = 0;

      $(options.galleryTop.slides).removeClass('swiper-lazy');
      lb.removeClass('lightbox');
      $('#wpadminbar').show();

      options.galleryTop.destroy(false, true);
      options.galleryTop.init();
      options.galleryTop.enableKeyboardControl();

      if(options.galleryThumbs.onResize)
        options.galleryThumbs.onResize();

      $(document).off('keydown', lbHandleKeyboard);
      options.galleryTop.off('touchEnd', closeLB);
      $('body').css({'overflow':''});
    }
    /**
     * Add styles to the html markup
     */
     function lbHandleKeyboard(e){
      switch(e.keyCode){
        case 27:
          hideLightbox(e);
          if (e.preventDefault) e.preventDefault();
          break;
      }
    }

    return this;
  };

})(jQuery);
