jQuery(document).ready(function($){
    var wpprs = $('.property-resp-slideshow');
    wpprs.each(function(){
        var $this = $(this);
        var id = $this.attr('id');
        var isMobile = $this.hasClass('mobile');
        var sliderType = $this.attr('data-slider-type');
        var slidesPerView;
        var slidesPerColumn;
        var autoHeight = false;
        var pagination;
        var centeredSlides = true;
        var slidesPerColumnFill = 'column';
        var galleryTop;
        var _galleryTop = $this.find('.gallery-top');
        var galleryThumbs;
        var _galleryThumbs = $this.find('.gallery-thumbs');
        var slideshow_layout = _galleryTop.data('slideshow_layout');
        var slider_width = _galleryTop.data('slider_width');
        var slider_height = isMobile ? '' : _galleryTop.data('slider_height');
        var slider_auto_height = _galleryTop.data('slider_auto_height').toString();
            slider_auto_height = slider_auto_height == 'true' ? true : false;
        var slider_min_height = _galleryTop.data('slider_min_height');
        var slider_max_height = _galleryTop.data('slider_max_height');
        var slider_init = true;

        // Settings specific on slider types.
        switch(sliderType){
            case 'standard':
                slidesPerView   = 1;
                slidesPerColumn = 1;
                pagination = "swiper-pagination";
                autoHeight = slider_auto_height;
            break;
            case 'carousel':
                slidesPerView   = 'auto';
                slidesPerColumn = 1;
                autoHeight = slider_auto_height;
            break;
            case '12grid':
                slidesPerView   = 3;
                //slidesPerColumnFill   = 'row';
                slidesPerColumn = 2;
                centeredSlides = false;
            break;
            case '12mosaic':
                slidesPerView   = 3;
                //slidesPerColumnFill   = 'row';
                slidesPerColumn = 2;
                centeredSlides = false;
            break;
        }
        if(!autoHeight && !slider_height && slideshow_layout != 'strict')
            _galleryTop.addClass('ratio-16-9');

        galleryTop = new Swiper(_galleryTop, {
                nextButton: $this.find('.swiper-button-next'),
                prevButton: $this.find('.swiper-button-prev'),
                centeredSlides: centeredSlides,
                slidesPerView: slidesPerView,
                slidesPerColumn: slidesPerColumn,
                slidesPerColumnFill: slidesPerColumnFill,
                pagination: pagination,
                autoHeight: autoHeight,
                spaceBetween: 2.5,
                keyboardControl:true,
                lazyLoading: true,
                lazyLoadingInPrevNext: true,
                lazyLoadingOnTransitionStart: true,

                //extra parameter
                sliderType: sliderType,
                slideshow_layout: slideshow_layout,
                slider_width: slider_width,
                slider_height: slider_height,
                slider_min_height: slider_min_height,
                slider_max_height: slider_max_height,
                onInit: function(s){
                                    setTimeout(function() {
                                        s.onResize();
                                        setControlSize(); // setting the next prev control size;
                                        if( slider_init ) { 
                                            s.slideTo(0); 
                                            slider_init = false;
                                        }
                                    },00);
                                },
                //slideToClickedSlide:true
            });

        _galleryTop.find('img').each(function(){
            setRealWidthHeight($(this), galleryTop);
        });

        if(_galleryThumbs.length){
            galleryThumbs = new Swiper(_galleryThumbs, {
                spaceBetween: 2.5,
                centeredSlides: true,
                slidesPerView: 'auto',
                touchRatio: 1,
                longSwipesRatio: 1,
                //simulateTouch: false,
                //slideToClickedSlide:true

            });

            galleryThumbs.container.on('click', '.swiper-slide', function(e){
                goToClickedSlide.call(this, e);
                galleryThumbs.slideTo( $(this).index());
            });

            galleryTop.on('onSlideChangeStart', function(s){
                if(galleryThumbs.activeIndex != s.activeIndex)
                    galleryThumbs.slideTo( s.activeIndex );
            });
        }

        galleryTop.on('onSlideChangeStart', function(s){
            var active      = s.activeIndex+1;
            //var total       = s.slides.length;
            var progress    = s.container.find('.count-progress');
            progress.find('.current').html(active);
            //progress.find('.total').html(total);
            enDisKeyCtrl(); // Enable keyboard control only on current swiper.
        });
        
        jQuery(window).on('orientationchange', galleryTop.onResize);
        jQuery(document).on('wpp_denali_tabbed_widget_render', galleryTop.onResize);
        if(!galleryTop.isGrid())
            galleryTop.container.on('click', '.swiper-slide', goToClickedSlide);

        galleryTop.on('onResizeStart', function(s){
            setControlSize();// setting the next prev control size;

            if(s.params.slider_min_height){
                s.container.css('min-height', s.params.slider_min_height);
            }
            if(s.params.slider_max_height){
                s.container.css('max-height', s.params.slider_max_height);
            }

            if(s.params.slider_height && !s.params.slider_auto_height){
                s.container.height(s.params.slider_height);
            }
            
            if(s.params.slideshow_layout == 'strict' && s.params.slider_width){
                s.container.parent().width(s.params.slider_width);
                if(!s.params.slider_height)
                    s.container.height(s.container.width() / (16 / 9));
                s.container.parent().css('margin', 'auto');
            }
            else if(s.params.slideshow_layout == 'fullwidth'){
                forceSlideshowFullWidth();
                if(jQuery('#wprs-fullwidth-spacer-' + id).length == 0)
                    jQuery("<div />").attr('id', 'wprs-fullwidth-spacer-' + id).width('100%').height($this.height()).insertAfter($this);
            }

            if(s.params.slideshow_layout == 'auto' && s.params.slider_width && s.params.slider_height && !s.params.slider_auto_height){
                var containerRatio = s.params.slider_width / s.params.slider_height;
                s.container.height(s.container.width() / containerRatio);
            }

            if (s.isGrid()) {
                return;
            }

            var containerWidth = s.container.width();
            var containerHeight = s.container.height();
            var $styler = jQuery('#' + id + '-img-max-width')
            var maxWidth = (containerWidth / s.params.slidesPerView) - (s.params.spaceBetween * s.params.slidesPerView);
            var maxHeight = (containerHeight / s.params.slidesPerColumn) - s.params.spaceBetween;
            if($styler.length==0)
                $styler = jQuery('<style id="' + id + '-img-max-width"></style>').appendTo('body');
            $styler.html('#' + id + '.swiper-container.gallery-top .swiper-slide img{max-width:'+ containerWidth + 'px!important;max-height:' + containerHeight +'px!important;}');

            s.slides.each(function(){
                var $this   = jQuery(this).find('img');
                    width   = parseInt($this.attr('width')),
                    height  = parseInt($this.attr('height')),
                    ratio   = width/height;
                
                if(s.isLightbox()){
                    width   = parseInt($this.data('width'));
                    height  = parseInt($this.data('height'));
                    ratio   = width/height;
                }
                else if(s.params.autoHeight && !s.params.slider_height){
                    maxHeight = containerWidth / ratio;
                    if(height > maxHeight)
                        height = maxHeight;
                    width = height * ratio;
                    $this.css('height', height);
                    $this.css('width', width);
                    $this.css('max-width', maxWidth);
                    jQuery(this).css('height', '100%');
                    return;
                }
                if((width > maxWidth) && (height > maxHeight)){
                    if(maxHeight * ratio <= maxWidth){
                        height  = maxHeight;
                        width   = maxHeight * ratio;
                    }
                    else{
                        width   = maxWidth;
                        height  = maxWidth / ratio;
                    }
                }
                else if(width > maxWidth){
                    width   = maxWidth;
                    height  = maxWidth / ratio;
                }
                else if(height > maxHeight){
                    height  = maxHeight;
                    width   = maxHeight * ratio;
                }

                $this.width(width);
                $this.height(height);
                $this[0].style.setProperty('width', width + "px", 'important');
                $this[0].style.setProperty('height', height + "px", 'important');

            });

            if(isMobile && !s.isLightbox()){
                updateContainerHeight(s);
            }
        });

        
        galleryTop.on('onLazyImageReady', function(s, slide, _img){
            s.onResize();
        });

        // Lightbox
        $this.wpp_rs_lb({galleryTop:galleryTop, galleryThumbs:galleryThumbs, sliderType:sliderType});

        // set font based on cointer width
        function setControlSize(){
            var cWidth = galleryTop.container.width();
            var control = $(galleryTop.container).find('.swiper-button-prev, .swiper-button-next');
            if(cWidth>900){
                width = 36;
            }
            else if(cWidth<400){
                width = 20;
            }
            else{
                width = (cWidth /100) * 6
            }
            control.css('font-size', width);
        }
        //galleryTop.params.control = galleryThumbs;
        //galleryThumbs.params.control = galleryTop;
        var goToClickedSlide = function(e){
                var clickedIndex = $(this).index();
                if(galleryTop.activeIndex != clickedIndex){
                    galleryTop.slideTo(clickedIndex);
                    e.preventDefault();
                    e.stopImmediatePropagation()
                    return false;
                }
                enDisKeyCtrl(); // Enable keyboard control only on current swiper.
            };
        var enDisKeyCtrl = function(){
            wpprs.find('.gallery-top').each(function(){
                this.swiper.disableKeyboardControl();
            });
            galleryTop.enableKeyboardControl();
        }
        $(window).scroll(function(){
            if(galleryTop.params.slideshow_layout == 'fullwidth')
                forceSlideshowFullWidth();
        });
        var forceSlideshowFullWidth = function(){
            var ofsetTop = $this.parent().offset().top - $(window).scrollTop();
            $this.css({
                position: 'fixed',
                left: 0,
                right: 0,
                top: ofsetTop,
                width: jQuery(window).width(),
                margin:'0 auto'
            });
        }

        var updateContainerHeight = function(s, ratio){
            var landscape = 0, lcCount = 0, portrait = 0, ptCount = 0;
            s.slides.each(function(){
                var $this   = jQuery(this).find('img');
                    width   = parseInt($this.attr('width')),
                    height  = parseInt($this.attr('height')),
                    ratio   = width/height;
                var tmpWidth = Math.min(s.container.width() / ratio, width / ratio);
                if(ratio>=1){
                    landscape = Math.max(landscape, tmpWidth);
                    lcCount++;
                }
                else{
                    portrait = Math.max(portrait, tmpWidth);
                    ptCount++;
                }
            });
            s.params.slider_height = Math.min($( window ).height(), (landscape || portrait));
            jQuery('#wprs-fullwidth-spacer-' + id).height(s.params.slider_height);
            s.container.height(s.params.slider_height);
        }

        function setRealWidthHeight($img, s){
            if(typeof s.noOfimgageLoaded == 'undefined')
                s.noOfimgageLoaded = 0;
            var isAllImgLoaded = function(){
                s.noOfimgageLoaded += 1;
                if(s.noOfimgageLoaded == s.slides.length){
                    s.destroy(false, true);
                    s.init();
                    s.onResize();
                    $this.removeClass('wpp-responsive-slideshow-loading');
                    $this.addClass('wpp-responsive-slideshow-ready');
                }
            }
            
            if($img.attr('sizeLoaded') == 'true'){
                isAllImgLoaded();
                return $img;
            }

            $('<img />').load(function(){
                var width = this.width;
                var height = this.height;
                $img.attr('width', width)
                    .attr('height', height);
                $img.attr('sizeLoaded', 'true');
                isAllImgLoaded();
            }).error(function(){ // When image not exist or not loaded
                isAllImgLoaded();
            }).attr('src', $img.attr('src') || $img.data('src'));
            return $img;
        }
    });

});

