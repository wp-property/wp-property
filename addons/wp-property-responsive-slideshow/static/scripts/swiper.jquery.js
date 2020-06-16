(function () {
    'use strict';
    var $;;
/*===========================
Swiper
===========================*/
var Swiper = function (container, params) {
    if (!(this instanceof Swiper)) return new Swiper(container, params);;
var defaults = {
    direction: 'horizontal',
    touchEventsTarget: 'container',
    initialSlide: 0,
    speed: 300,
    // autoplay
    autoplay: false,
    autoplayDisableOnInteraction: true,
    // To support iOS's swipe-to-go-back gesture (when being used in-app, with UIWebView).
    iOSEdgeSwipeDetection: false,
    iOSEdgeSwipeThreshold: 20,
    // Free mode
    freeMode: false,
    freeModeMomentum: true,
    freeModeMomentumRatio: 1,
    freeModeMomentumBounce: true,
    freeModeMomentumBounceRatio: 1,
    freeModeSticky: false,
    freeModeMinimumVelocity: 0.02,
    // Autoheight
    autoHeight: false,
    // Set wrapper width
    setWrapperSize: false,
    // Virtual Translate
    virtualTranslate: false,
    // Effects
    effect: 'slide', // 'slide' or 'fade' or 'cube' or 'coverflow'
    coverflow: {
        rotate: 50,
        stretch: 0,
        depth: 100,
        modifier: 1,
        slideShadows : true
    },
    cube: {
        slideShadows: true,
        shadow: true,
        shadowOffset: 20,
        shadowScale: 0.94
    },
    fade: {
        crossFade: false
    },
    // Parallax
    parallax: false,
    // Scrollbar
    scrollbar: null,
    scrollbarHide: true,
    scrollbarDraggable: false,
    scrollbarSnapOnRelease: false,
    // Keyboard Mousewheel
    keyboardControl: false,
    mousewheelControl: false,
    mousewheelReleaseOnEdges: false,
    mousewheelInvert: false,
    mousewheelForceToAxis: false,
    mousewheelSensitivity: 1,
    // Hash Navigation
    hashnav: false,
    // Breakpoints
    breakpoints: undefined,
    // Slides grid
    spaceBetween: 0,
    slidesPerView: 1,
    slidesPerColumn: 1,
    slidesPerColumnFill: 'column',
    slidesPerGroup: 1,
    centeredSlides: false,
    slidesOffsetBefore: 0, // in px
    slidesOffsetAfter: 0, // in px
    // Round length
    roundLengths: false,
    // Touches
    touchRatio: 1,
    touchAngle: 45,
    simulateTouch: true,
    shortSwipes: true,
    longSwipes: true,
    longSwipesRatio: 0.5,
    longSwipesMs: 300,
    followFinger: true,
    onlyExternal: false,
    threshold: 0,
    touchMoveStopPropagation: true,
    // Pagination
    pagination: null,
    paginationElement: 'span',
    paginationClickable: false,
    paginationHide: false,
    paginationBulletRender: null,
    // Resistance
    resistance: true,
    resistanceRatio: 0.85,
    // Next/prev buttons
    nextButton: null,
    prevButton: null,
    // Progress
    watchSlidesProgress: false,
    watchSlidesVisibility: false,
    // Cursor
    grabCursor: false,
    // Clicks
    preventClicks: true,
    preventClicksPropagation: true,
    slideToClickedSlide: false,
    // Lazy Loading
    lazyLoading: false,
    lazyLoadingInPrevNext: false,
    lazyLoadingOnTransitionStart: false,
    // Images
    preloadImages: true,
    updateOnImagesReady: true,
    // loop
    loop: false,
    loopAdditionalSlides: 0,
    loopedSlides: null,
    // Control
    control: undefined,
    controlInverse: false,
    controlBy: 'slide', //or 'container'
    // Swiping/no swiping
    allowSwipeToPrev: true,
    allowSwipeToNext: true,
    swipeHandler: null, //'.swipe-handler',
    noSwiping: true,
    noSwipingClass: 'swiper-no-swiping',
    // NS
    slideClass: 'swiper-slide',
    slideActiveClass: 'swiper-slide-active',
    slideVisibleClass: 'swiper-slide-visible',
    slideDuplicateClass: 'swiper-slide-duplicate',
    slideNextClass: 'swiper-slide-next',
    slidePrevClass: 'swiper-slide-prev',
    wrapperClass: 'swiper-wrapper',
    bulletClass: 'swiper-pagination-bullet',
    bulletActiveClass: 'swiper-pagination-bullet-active',
    buttonDisabledClass: 'swiper-button-disabled',
    paginationHiddenClass: 'swiper-pagination-hidden',
    // Observer
    observer: false,
    observeParents: false,
    // Accessibility
    a11y: false,
    prevSlideMessage: 'Previous slide',
    nextSlideMessage: 'Next slide',
    firstSlideMessage: 'This is the first slide',
    lastSlideMessage: 'This is the last slide',
    paginationBulletMessage: 'Go to slide {{index}}',
    // Callbacks
    runCallbacksOnInit: true
    /*
    Callbacks:
    onInit: function (swiper)
    onDestroy: function (swiper)
    onClick: function (swiper, e)
    onTap: function (swiper, e)
    onDoubleTap: function (swiper, e)
    onSliderMove: function (swiper, e)
    onSlideChangeStart: function (swiper)
    onSlideChangeEnd: function (swiper)
    onTransitionStart: function (swiper)
    onTransitionEnd: function (swiper)
    onImagesReady: function (swiper)
    onProgress: function (swiper, progress)
    onTouchStart: function (swiper, e)
    onTouchMove: function (swiper, e)
    onTouchMoveOpposite: function (swiper, e)
    onTouchEnd: function (swiper, e)
    onReachBeginning: function (swiper)
    onReachEnd: function (swiper)
    onSetTransition: function (swiper, duration)
    onSetTranslate: function (swiper, translate)
    onAutoplayStart: function (swiper)
    onAutoplayStop: function (swiper),
    onLazyImageLoad: function (swiper, slide, image)
    onLazyImageReady: function (swiper, slide, image)
    */

};
var initialVirtualTranslate = params && params.virtualTranslate;

params = params || {};
var originalParams = {};
for (var param in params) {
    if (typeof params[param] === 'object' && !(params[param].nodeType || params[param] === window || params[param] === document || (typeof Dom7 !== 'undefined' && params[param] instanceof Dom7) || (typeof jQuery !== 'undefined' && params[param] instanceof jQuery))) {
        originalParams[param] = {};
        for (var deepParam in params[param]) {
            originalParams[param][deepParam] = params[param][deepParam];
        }
    }
    else {
        originalParams[param] = params[param];
    }
}
for (var def in defaults) {
    if (typeof params[def] === 'undefined') {
        params[def] = defaults[def];
    }
    else if (typeof params[def] === 'object') {
        for (var deepDef in defaults[def]) {
            if (typeof params[def][deepDef] === 'undefined') {
                params[def][deepDef] = defaults[def][deepDef];
            }
        }
    }
}

// Swiper
var s = this;

// Params
s.params = params;
s.originalParams = originalParams;

// Classname
s.classNames = [];
/*=========================
  Dom Library and plugins
  ===========================*/
if (typeof $ !== 'undefined' && typeof Dom7 !== 'undefined'){
    $ = Dom7;
}
if (typeof $ === 'undefined') {
    if (typeof Dom7 === 'undefined') {
        $ = window.Dom7 || window.Zepto || window.jQuery;
    }
    else {
        $ = Dom7;
    }
    if (!$) return;
}
// Export it to Swiper instance
s.$ = $;

/*=========================
  Breakpoints
  ===========================*/
s.currentBreakpoint = undefined;
s.getActiveBreakpoint = function () {
    //Get breakpoint for window width
    if (!s.params.breakpoints) return false;
    var breakpoint = false;
    var points = [], point;
    for ( point in s.params.breakpoints ) {
        if (s.params.breakpoints.hasOwnProperty(point)) {
            points.push(point);
        }
    }
    points.sort(function (a, b) {
        return parseInt(a, 10) > parseInt(b, 10);
    });
    for (var i = 0; i < points.length; i++) {
        point = points[i];
        if (point >= window.innerWidth && !breakpoint) {
            breakpoint = point;
        }
    }
    return breakpoint || 'max';
};
s.setBreakpoint = function () {
    //Set breakpoint for window width and update parameters
    var breakpoint = s.getActiveBreakpoint();
    if (breakpoint && s.currentBreakpoint !== breakpoint) {
        var breakPointsParams = breakpoint in s.params.breakpoints ? s.params.breakpoints[breakpoint] : s.originalParams;
        for ( var param in breakPointsParams ) {
            s.params[param] = breakPointsParams[param];
        }
        s.currentBreakpoint = breakpoint;
    }
};
// Set breakpoint on load
if (s.params.breakpoints) {
    s.setBreakpoint();
}

/*=========================
  Preparation - Define Container, Wrapper and Pagination
  ===========================*/
s.container = $(container);
if (s.container.length === 0) return;
if (s.container.length > 1) {
    s.container.each(function () {
        new Swiper(this, params);
    });
    return;
}

// Save instance in container HTML Element and in data
s.container[0].swiper = s;
s.container.data('swiper', s);

s.classNames.push('swiper-container-' + s.params.direction);

if (s.params.freeMode) {
    s.classNames.push('swiper-container-free-mode');
}
if (!s.support.flexbox) {
    s.classNames.push('swiper-container-no-flexbox');
    s.params.slidesPerColumn = 1;
}
if (s.params.autoHeight) {
    s.classNames.push('swiper-container-autoheight');
}
// Enable slides progress when required
if (s.params.parallax || s.params.watchSlidesVisibility) {
    s.params.watchSlidesProgress = true;
}
// Coverflow / 3D
if (['cube', 'coverflow'].indexOf(s.params.effect) >= 0) {
    if (s.support.transforms3d) {
        s.params.watchSlidesProgress = true;
        s.classNames.push('swiper-container-3d');
    }
    else {
        s.params.effect = 'slide';
    }
}
if (s.params.effect !== 'slide') {
    s.classNames.push('swiper-container-' + s.params.effect);
}
if (s.params.effect === 'cube') {
    s.params.resistanceRatio = 0;
    s.params.slidesPerView = 1;
    s.params.slidesPerColumn = 1;
    s.params.slidesPerGroup = 1;
    s.params.centeredSlides = false;
    s.params.spaceBetween = 0;
    s.params.virtualTranslate = true;
    s.params.setWrapperSize = false;
}
if (s.params.effect === 'fade') {
    s.params.slidesPerView = 1;
    s.params.slidesPerColumn = 1;
    s.params.slidesPerGroup = 1;
    s.params.watchSlidesProgress = true;
    s.params.spaceBetween = 0;
    if (typeof initialVirtualTranslate === 'undefined') {
        s.params.virtualTranslate = true;
    }
}

// Grab Cursor
if (s.params.grabCursor && s.support.touch) {
    s.params.grabCursor = false;
}

// Wrapper
s.wrapper = s.container.children('.' + s.params.wrapperClass);

// Pagination
if (s.params.pagination) {
    s.paginationContainer = $(s.params.pagination);
    if (s.params.paginationClickable) {
        s.paginationContainer.addClass('swiper-pagination-clickable');
    }
}

// Is Horizontal
function isH() {
    return s.params.direction === 'horizontal';
}

// RTL
s.rtl = isH() && (s.container[0].dir.toLowerCase() === 'rtl' || s.container.css('direction') === 'rtl');
if (s.rtl) {
    s.classNames.push('swiper-container-rtl');
}

// Wrong RTL support
if (s.rtl) {
    s.wrongRTL = s.wrapper.css('display') === '-webkit-box';
}

// Columns
if (s.params.slidesPerColumn > 1) {
    s.classNames.push('swiper-container-multirow');
}

// Check for Android
if (s.device.android) {
    s.classNames.push('swiper-container-android');
}

// Add classes
s.container.addClass(s.classNames.join(' '));

// Translate
s.translate = 0;

// Progress
s.progress = 0;

// Velocity
s.velocity = 0;

/*=========================
  Locks, unlocks
  ===========================*/
s.lockSwipeToNext = function () {
    s.params.allowSwipeToNext = false;
};
s.lockSwipeToPrev = function () {
    s.params.allowSwipeToPrev = false;
};
s.lockSwipes = function () {
    s.params.allowSwipeToNext = s.params.allowSwipeToPrev = false;
};
s.unlockSwipeToNext = function () {
    s.params.allowSwipeToNext = true;
};
s.unlockSwipeToPrev = function () {
    s.params.allowSwipeToPrev = true;
};
s.unlockSwipes = function () {
    s.params.allowSwipeToNext = s.params.allowSwipeToPrev = true;
};

/*=========================
  Round helper
  ===========================*/
function round(a) {
    return Math.floor(a);
}
/*=========================
  Set grab cursor
  ===========================*/
if (s.params.grabCursor) {
    s.container[0].style.cursor = 'move';
    s.container[0].style.cursor = '-webkit-grab';
    s.container[0].style.cursor = '-moz-grab';
    s.container[0].style.cursor = 'grab';
}
/*=========================
  Update on Images Ready
  ===========================*/
s.imagesToLoad = [];
s.imagesLoaded = 0;

s.loadImage = function (imgElement, src, srcset, checkForComplete, callback) {
    var image;
    function onReady () {
        if (callback) callback(image.width, image.height);
    }
    if (!imgElement.complete || !checkForComplete) {
        if (src) {
            image = new window.Image();
            image.onload = onReady;
            image.onerror = onReady;
            if (srcset) {
                image.srcset = srcset;
            }
            if (src) {
                image.src = src;
            }
        } else {
            onReady();
        }

    } else {//image already loaded...
        onReady();
    }
};
s.preloadImages = function () {
    s.imagesToLoad = s.container.find('img');
    function _onReady() {
        if (typeof s === 'undefined' || s === null) return;
        if (s.imagesLoaded !== undefined) s.imagesLoaded++;
        if (s.imagesLoaded === s.imagesToLoad.length) {
            if (s.params.updateOnImagesReady) s.update();
            s.emit('onImagesReady', s);
        }
    }
    for (var i = 0; i < s.imagesToLoad.length; i++) {
        s.loadImage(s.imagesToLoad[i], (s.imagesToLoad[i].currentSrc || s.imagesToLoad[i].getAttribute('src')), (s.imagesToLoad[i].srcset || s.imagesToLoad[i].getAttribute('srcset')), false, _onReady);
    }
};

/*=========================
  Autoplay
  ===========================*/
s.autoplayTimeoutId = undefined;
s.autoplaying = false;
s.autoplayPaused = false;
function autoplay() {
    s.autoplayTimeoutId = setTimeout(function () {
        if (s.params.loop) {
            s.fixLoop();
            s._slideNext();
        }
        else {
            if (!s.isEnd) {
                s._slideNext();
            }
            else {
                if (!params.autoplayStopOnLast) {
                    s._slideTo(0);
                }
                else {
                    s.stopAutoplay();
                }
            }
        }
    }, s.params.autoplay);
}
s.startAutoplay = function () {
    if (typeof s.autoplayTimeoutId !== 'undefined') return false;
    if (!s.params.autoplay) return false;
    if (s.autoplaying) return false;
    s.autoplaying = true;
    s.emit('onAutoplayStart', s);
    autoplay();
};
s.stopAutoplay = function (internal) {
    if (!s.autoplayTimeoutId) return;
    if (s.autoplayTimeoutId) clearTimeout(s.autoplayTimeoutId);
    s.autoplaying = false;
    s.autoplayTimeoutId = undefined;
    s.emit('onAutoplayStop', s);
};
s.pauseAutoplay = function (speed) {
    if (s.autoplayPaused) return;
    if (s.autoplayTimeoutId) clearTimeout(s.autoplayTimeoutId);
    s.autoplayPaused = true;
    if (speed === 0) {
        s.autoplayPaused = false;
        autoplay();
    }
    else {
        s.wrapper.transitionEnd(function () {
            if (!s) return;
            s.autoplayPaused = false;
            if (!s.autoplaying) {
                s.stopAutoplay();
            }
            else {
                autoplay();
            }
        });
    }
};
/*=========================
  Min/Max Translate
  ===========================*/
s.minTranslate = function () {
    return (-s.snapGrid[0]);
};
s.maxTranslate = function () {
    return (-s.snapGrid[s.snapGrid.length - 1]);
};
/*=========================
  Slider/slides sizes
  ===========================*/
s.updateAutoHeight = function () {
    // Update Height
    var containerWidth = s.container.width();
    var newWidth = s.slides.eq(s.activeIndex).find('img').attr('width');
    var newHeight = s.slides.eq(s.activeIndex).find('img').attr('height');
    var ratio = newWidth / newHeight;
    var maxHeight = containerWidth / ratio;
    if(newHeight > maxHeight)
        newHeight = maxHeight;
    if (newHeight){
        s.container.css('height', newHeight + 'px');
        s.slides.eq(s.activeIndex).find('img').css('height', newHeight + 'px');
    }

};
s.updateContainerSize = function () {
    var width, height;
    if (typeof s.params.width !== 'undefined') {
        width = s.params.width;
    }
    else {
        width = s.container[0].clientWidth;
    }
    if (typeof s.params.height !== 'undefined') {
        height = s.params.height;
    }
    else {
        height = s.container[0].clientHeight;
    }
    if (width === 0 && isH() || height === 0 && !isH()) {
        return;
    }

    //Subtract paddings
    width = width - parseInt(s.container.css('padding-left'), 10) - parseInt(s.container.css('padding-right'), 10);
    height = height - parseInt(s.container.css('padding-top'), 10) - parseInt(s.container.css('padding-bottom'), 10);

    // Store values
    s.width = width;
    s.height = height;
    s.size = isH() ? s.width : s.height;
};

s.updateSlidesSize = function () {
    s.slides = s.wrapper.children('.' + s.params.slideClass);
    s.snapGrid = [];
    s.slidesGrid = [];
    s.slidesSizesGrid = [];

    var spaceBetween = s.params.spaceBetween,
        slidePosition = {},
        i,
        prevSlideSize = 0,
        index = 0;
    for (var i = s.params.slidesPerColumn - 1; i >= 0; i--) {
        slidePosition[i] = -s.params.slidesOffsetBefore;
    }
    if (typeof spaceBetween === 'string' && spaceBetween.indexOf('%') >= 0) {
        spaceBetween = parseFloat(spaceBetween.replace('%', '')) / 100 * s.size;
    }

    s.virtualSize = -spaceBetween;
    // reset margins
    if (s.rtl) s.slides.css({marginLeft: '', marginTop: ''});
    else s.slides.css({marginRight: '', marginBottom: ''});
    if(s.isGrid())
        s.wrapper.css('display', 'block');
    else
        s.wrapper.css('display', '');

    var slidesNumberEvenToRows;
    var noOfSlide;
    if(s.isGrid())
        noOfSlide = s.slides.length + 1; // Because the first slide takes size of tow slide.
    if (s.params.slidesPerColumn > 1) {
        if (Math.floor(noOfSlide / s.params.slidesPerColumn) === noOfSlide / s.params.slidesPerColumn) {
            slidesNumberEvenToRows = noOfSlide;
        }
        else {
            slidesNumberEvenToRows = Math.ceil(noOfSlide / s.params.slidesPerColumn) * s.params.slidesPerColumn;
        }
        if (s.params.slidesPerView !== 'auto' && s.params.slidesPerColumnFill === 'row') {
            slidesNumberEvenToRows = Math.max(slidesNumberEvenToRows, s.params.slidesPerView * s.params.slidesPerColumn);
        }
    }

    // Calc slides
    var slideSize;
    var slidesPerColumn = s.params.slidesPerColumn;
    var slidesPerRow = slidesNumberEvenToRows / slidesPerColumn;
    var numFullColumns = slidesPerRow - (s.params.slidesPerColumn * slidesPerRow - noOfSlide);
    var ii = 0;
    var calculatedLeft = {0:0};
    for (i = 0; i < s.slides.length; i++) {
        var _slideSize, compareSlideSize;
        var slide = s.slides.eq(i);
        slideSize = 0;
        if (slide.css('display') === 'none') continue;
        if (s.params.slidesPerView === 'auto') {
            slideSize = isH() ? slide.outerWidth(true) : slide.outerHeight(true);
            if (s.params.roundLengths) slideSize = round(slideSize);
        }
        else {
            slideSize = (s.size - (s.params.slidesPerView - 1) * spaceBetween) / s.params.slidesPerView;
            
            if(s.is12mosaic()){
                slideSize = s.setSlideSize_12mosaic(s.slides[i], s);
            }
            else if(s.is12grid()){
                _slideSize = s.setSlideSize_12grid(s.slides[i], s);
                if(i==0){
                    compareSlideSize = 0;
                }
                else if(i % 2 == 1){
                    compareSlideSize = s.setSlideSize_12grid(s.slides[i+1], s);
                }
                else{
                    compareSlideSize = s.slides[i-1].swiperSlideSize;
                }
                slideSize = Math.max(_slideSize, compareSlideSize);
            }
            if (s.params.roundLengths) slideSize = round(slideSize);

            if (isH()) {
                s.slides[i].style.width = slideSize + 'px';
            }
            else {
                s.slides[i].style.height = slideSize + 'px';
            }
        }
        s.slides[i].swiperSlideSize = slideSize;
        s.slidesSizesGrid.push(slideSize);


        if (s.params.slidesPerColumn > 1) {
            // Set slides order
            var newSlideOrderIndex;
            var column, row;
            if(i>0 && s.isGrid())
                ii = i +1; // Increase by 1 because first slide takes two rows.
            if (s.params.slidesPerColumnFill === 'column') {
                column = Math.floor(ii / slidesPerColumn);
                row = ii - column * slidesPerColumn;
                if (column > numFullColumns || (column === numFullColumns && row === slidesPerColumn-1)) {
                    if (++row >= slidesPerColumn) {
                        row = 0;
                        column++;
                    }
                }
            }
            else {
                row = Math.floor(ii / slidesPerRow);
                column = ii - row * slidesPerRow;
            }
            // Searching for the nearest position in rows.
            jQuery.each(calculatedLeft, function(l, item){
                if(calculatedLeft[l] < calculatedLeft[row])
                    row = l;
            });
            var top = (row * (s.container.height() - s.params.spaceBetween) / s.params.slidesPerColumn);
            top += row * s.params.spaceBetween;
            if(!s.params.lightBox){
                slide.css({
                    'left':     calculatedLeft[row] + 'px',
                    'top':      top + 'px',
                    'display':  'block',
                    'position': 'absolute'
                })
                .attr('data-swiper-column', column)
                .attr('data-swiper-row', row);
            }
            else{
                slide.css({
                    'margin-top': (row !== 0 && s.params.spaceBetween) && (s.params.spaceBetween + 'px'),
                    'position':'relative',
                    'display':  '',
                });
            }

            if(i == 0){
                // Because first column will get space of tow row it's need to add it's width to calculatedLeft for each row.
                for (var j = s.params.slidesPerColumn - 1; j >= 0; j--) {
                    calculatedLeft[j] = slideSize + s.params.spaceBetween;
                }
            }
            else
                calculatedLeft[row] += slideSize + s.params.spaceBetween;

        }
        // Nedded to avoid error on single row.
        if(typeof row == 'undefined')
            row = 0;
        if (s.params.centeredSlides) {
            slidePosition[row] = slidePosition[row] + slideSize / 2 + prevSlideSize / 2 + spaceBetween;
            if (i === 0) slidePosition[row] = slidePosition[row] - s.size / 2 - spaceBetween;
            if (Math.abs(slidePosition[row]) < 1 / 1000) slidePosition[row] = 0;
            if ((index) % s.params.slidesPerGroup === 0) s.snapGrid.push(slidePosition[row]);
            s.slidesGrid.push(slidePosition[row]);
        }
        else {
            if ((index) % s.params.slidesPerGroup === 0) s.snapGrid.push(slidePosition[row]);
            s.slidesGrid.push(slidePosition[row]); 
            if(i == 0){
                for (var j = s.params.slidesPerColumn - 1; j >= 0; j--) {
                    slidePosition[j] = slideSize + s.params.spaceBetween;
                }
            }
            else{
                slidePosition[row] = slidePosition[row] + slideSize + spaceBetween;
            }
        }

        s.virtualSize += slideSize + ((row%2 == 0)?spaceBetween:0);

        prevSlideSize = slideSize;

        index ++;
    }
    s.virtualSize = Math.max(s.virtualSize, s.size) + s.params.slidesOffsetAfter;
    var newSlidesGrid;

    if (
        s.rtl && s.wrongRTL && (s.params.effect === 'slide' || s.params.effect === 'coverflow')) {
        s.wrapper.css({width: s.virtualSize + s.params.spaceBetween + 'px'});
    }
    if (!s.support.flexbox || s.params.setWrapperSize) {
        if (isH()) s.wrapper.css({width: s.virtualSize + s.params.spaceBetween + 'px'});
        else s.wrapper.css({height: s.virtualSize + s.params.spaceBetween + 'px'});
    }

    if (s.params.slidesPerColumn > 1) {
        s.virtualSize = Math.max(calculatedLeft[0], calculatedLeft[1]);
        s.wrapper.css({width: s.virtualSize + s.params.spaceBetween + 'px'});
        if (s.params.centeredSlides) {
            newSlidesGrid = [];
            for (i = 0; i < s.snapGrid.length; i++) {
                if (s.snapGrid[i] < s.virtualSize + s.snapGrid[0]) newSlidesGrid.push(s.snapGrid[i]);
            }
            s.snapGrid = newSlidesGrid;
        }
    }

    // Remove last grid elements depending on width
    if (!s.params.centeredSlides) {
        newSlidesGrid = [];
        for (i = 0; i < s.snapGrid.length; i++) {
            if (s.snapGrid[i] <= s.virtualSize - s.size) {
                newSlidesGrid.push(s.snapGrid[i]);
            }
        }
        s.snapGrid = newSlidesGrid;
        if (Math.floor(s.virtualSize - s.size) > Math.floor(s.snapGrid[s.snapGrid.length - 1])) {
            s.snapGrid.push(s.virtualSize - s.size);
        }
    }
    if (s.snapGrid.length === 0) s.snapGrid = [0];

    if (s.params.spaceBetween !== 0) {
        if (isH()) {
            if (s.rtl) s.slides.css({marginLeft: spaceBetween + 'px'});
            else s.slides.css({marginRight: spaceBetween + 'px'});
        }
        else s.slides.css({marginBottom: spaceBetween + 'px'});
    }
    if (s.params.watchSlidesProgress) {
        s.updateSlidesOffset();
    }
};
s.updateSlidesOffset = function () {
    for (var i = 0; i < s.slides.length; i++) {
        s.slides[i].swiperSlideOffset = isH() ? s.slides[i].offsetLeft : s.slides[i].offsetTop;
    }
};

/*=========================
  Slider/slides progress
  ===========================*/
s.updateSlidesProgress = function (translate) {
    if (typeof translate === 'undefined') {
        translate = s.translate || 0;
    }
    if (s.slides.length === 0) return;
    if (typeof s.slides[0].swiperSlideOffset === 'undefined') s.updateSlidesOffset();

    var offsetCenter = -translate;
    if (s.rtl) offsetCenter = translate;

    // Visible Slides
    s.slides.removeClass(s.params.slideVisibleClass);
    for (var i = 0; i < s.slides.length; i++) {
        var slide = s.slides[i];
        var slideProgress = (offsetCenter - slide.swiperSlideOffset) / (slide.swiperSlideSize + s.params.spaceBetween);
        if (s.params.watchSlidesVisibility) {
            var slideBefore = -(offsetCenter - slide.swiperSlideOffset);
            var slideAfter = slideBefore + s.slidesSizesGrid[i];
            var isVisible =
                (slideBefore >= 0 && slideBefore < s.size) ||
                (slideAfter > 0 && slideAfter <= s.size) ||
                (slideBefore <= 0 && slideAfter >= s.size);
            if (isVisible) {
                s.slides.eq(i).addClass(s.params.slideVisibleClass);
            }
        }
        slide.progress = s.rtl ? -slideProgress : slideProgress;
    }
};
s.updateProgress = function (translate) {
    if (typeof translate === 'undefined') {
        translate = s.translate || 0;
    }
    var translatesDiff = s.maxTranslate() - s.minTranslate();
    var wasBeginning = s.isBeginning;
    var wasEnd = s.isEnd;
    if (translatesDiff === 0) {
        s.progress = 0;
        s.isBeginning = s.isEnd = true;
    }
    else {
        s.progress = (translate - s.minTranslate()) / (translatesDiff);
        s.isBeginning = s.progress <= 0;
        s.isEnd = s.progress >= 1;
    }
    if (s.isBeginning && !wasBeginning) s.emit('onReachBeginning', s);
    if (s.isEnd && !wasEnd) s.emit('onReachEnd', s);

    if (s.params.watchSlidesProgress) s.updateSlidesProgress(translate);
    s.emit('onProgress', s, s.progress);
};
s.updateActiveIndex = function () {
    var translate = s.rtl ? s.translate : -s.translate;
    var newActiveIndex, i, snapIndex;
    for (i = 0; i < s.slidesGrid.length; i ++) {
        if (typeof s.slidesGrid[i + 1] !== 'undefined') {
            if (translate >= s.slidesGrid[i] && translate < s.slidesGrid[i + 1] - (s.slidesGrid[i + 1] - s.slidesGrid[i]) / 2) {
                newActiveIndex = i;
            }
            else if (translate >= s.slidesGrid[i] && translate < s.slidesGrid[i + 1]) {
                newActiveIndex = i + 1;
            }
        }
        else {
            if (translate >= s.slidesGrid[i]) {
                newActiveIndex = i;
            }
        }
    }
    // Normalize slideIndex
    if (newActiveIndex < 0 || typeof newActiveIndex === 'undefined') newActiveIndex = 0;
    // for (i = 0; i < s.slidesGrid.length; i++) {
        // if (- translate >= s.slidesGrid[i]) {
            // newActiveIndex = i;
        // }
    // }
    snapIndex = Math.floor(newActiveIndex / s.params.slidesPerGroup);
    if (snapIndex >= s.snapGrid.length) snapIndex = s.snapGrid.length - 1;

    if (newActiveIndex === s.activeIndex) {
        return;
    }
    s.snapIndex = snapIndex;
    s.previousIndex = s.activeIndex;
    s.activeIndex = newActiveIndex;
    s.updateClasses();
};

/*=========================
  Classes
  ===========================*/
s.updateClasses = function () {
    s.slides.removeClass(s.params.slideActiveClass + ' ' + s.params.slideNextClass + ' ' + s.params.slidePrevClass);
    var activeSlide = s.slides.eq(s.activeIndex);
    // Active classes
    activeSlide.addClass(s.params.slideActiveClass);
    activeSlide.next('.' + s.params.slideClass).addClass(s.params.slideNextClass);
    activeSlide.prev('.' + s.params.slideClass).addClass(s.params.slidePrevClass);

    // Pagination
    if (s.bullets && s.bullets.length > 0) {
        s.bullets.removeClass(s.params.bulletActiveClass);
        var bulletIndex;
        if (s.params.loop) {
            bulletIndex = Math.ceil(s.activeIndex - s.loopedSlides)/s.params.slidesPerGroup;
            if (bulletIndex > s.slides.length - 1 - s.loopedSlides * 2) {
                bulletIndex = bulletIndex - (s.slides.length - s.loopedSlides * 2);
            }
            if (bulletIndex > s.bullets.length - 1) bulletIndex = bulletIndex - s.bullets.length;
        }
        else {
            if (typeof s.snapIndex !== 'undefined') {
                bulletIndex = s.snapIndex;
            }
            else {
                bulletIndex = s.activeIndex || 0;
            }
        }
        if (s.paginationContainer.length > 1) {
            s.bullets.each(function () {
                if ($(this).index() === bulletIndex) $(this).addClass(s.params.bulletActiveClass);
            });
        }
        else {
            s.bullets.eq(bulletIndex).addClass(s.params.bulletActiveClass);
        }
    }

    // Next/active buttons
    if (!s.params.loop) {
        if (s.params.prevButton) {
            if (s.isBeginning) {
                $(s.params.prevButton).addClass(s.params.buttonDisabledClass);
                if (s.params.a11y && s.a11y) s.a11y.disable($(s.params.prevButton));
            }
            else {
                $(s.params.prevButton).removeClass(s.params.buttonDisabledClass);
                if (s.params.a11y && s.a11y) s.a11y.enable($(s.params.prevButton));
            }
        }
        if (s.params.nextButton) {
            if (s.isEnd) {
                $(s.params.nextButton).addClass(s.params.buttonDisabledClass);
                if (s.params.a11y && s.a11y) s.a11y.disable($(s.params.nextButton));
            }
            else {
                $(s.params.nextButton).removeClass(s.params.buttonDisabledClass);
                if (s.params.a11y && s.a11y) s.a11y.enable($(s.params.nextButton));
            }
        }
    }
};

/*=========================
  Pagination
  ===========================*/
s.updatePagination = function () {
    if (!s.params.pagination) return;
    if (s.paginationContainer && s.paginationContainer.length > 0) {
        var bulletsHTML = '';
        var numberOfBullets = s.params.loop ? Math.ceil((s.slides.length - s.loopedSlides * 2) / s.params.slidesPerGroup) : s.snapGrid.length;
        for (var i = 0; i < numberOfBullets; i++) {
            if (s.params.paginationBulletRender) {
                bulletsHTML += s.params.paginationBulletRender(i, s.params.bulletClass);
            }
            else {
                bulletsHTML += '<' + s.params.paginationElement+' class="' + s.params.bulletClass + '"></' + s.params.paginationElement + '>';
            }
        }
        s.paginationContainer.html(bulletsHTML);
        s.bullets = s.paginationContainer.find('.' + s.params.bulletClass);
        if (s.params.paginationClickable && s.params.a11y && s.a11y) {
            s.a11y.initPagination();
        }
    }
};
/*=========================
  Common update method
  ===========================*/
s.update = function (updateTranslate) {
    s.updateContainerSize();
    s.updateSlidesSize();
    s.updateProgress();
    s.updatePagination();
    s.updateClasses();
    if (s.params.scrollbar && s.scrollbar) {
        s.scrollbar.set();
    }
    function forceSetTranslate() {
        newTranslate = Math.min(Math.max(s.translate, s.maxTranslate()), s.minTranslate());
        s.setWrapperTranslate(newTranslate);
        s.updateActiveIndex();
        s.updateClasses();
    }
    if (updateTranslate) {
        var translated, newTranslate;
        if (s.controller && s.controller.spline) {
            s.controller.spline = undefined;
        }
        if (s.params.freeMode) {
            forceSetTranslate();
            if (s.params.autoHeight) {
                s.updateAutoHeight();
            }
        }
        else {
            if ((s.params.slidesPerView === 'auto' || s.params.slidesPerView > 1) && s.isEnd && !s.params.centeredSlides) {
                translated = s.slideTo(s.slides.length - 1, 0, false, true);
            }
            else {
                translated = s.slideTo(s.activeIndex, 0, false, true);
            }
            if (!translated) {
                forceSetTranslate();
            }
        }
    }
    else if (s.params.autoHeight) {
        s.updateAutoHeight();
    }
};

/*=========================
  Resize Handler
  ===========================*/
s.onResize = function (forceUpdatePagination) {
    s.emit('onResizeStart', s);
    //Breakpoints
    if (s.params.breakpoints) {
        s.setBreakpoint();
    }

    // Disable locks on resize
    var allowSwipeToPrev = s.params.allowSwipeToPrev;
    var allowSwipeToNext = s.params.allowSwipeToNext;
    s.params.allowSwipeToPrev = s.params.allowSwipeToNext = true;

    s.updateContainerSize();
    s.updateSlidesSize();
    if (s.params.slidesPerView === 'auto' || s.params.freeMode || forceUpdatePagination) s.updatePagination();
    if (s.params.scrollbar && s.scrollbar) {
        s.scrollbar.set();
    }
    if (s.controller && s.controller.spline) {
        s.controller.spline = undefined;
    }
    if (s.params.freeMode) {
        var newTranslate = Math.min(Math.max(s.translate, s.maxTranslate()), s.minTranslate());
        s.setWrapperTranslate(newTranslate);
        s.updateActiveIndex();
        s.updateClasses();

        if (s.params.autoHeight) {
            s.updateAutoHeight();
        }
    }
    else {
        s.updateClasses();
        if ((s.params.slidesPerView === 'auto' || s.params.slidesPerView > 1) && s.isEnd && !s.params.centeredSlides) {
            s.slideTo(s.slides.length - 1, 0, false, true);
        }
        else {
            s.slideTo(s.activeIndex, 0, false, true);
        }
    }
    // Return locks after resize
    s.params.allowSwipeToPrev = allowSwipeToPrev;
    s.params.allowSwipeToNext = allowSwipeToNext;
};

/*=========================
  Events
  ===========================*/

//Define Touch Events
var desktopEvents = ['mousedown', 'mousemove', 'mouseup'];
if (window.navigator.pointerEnabled) desktopEvents = ['pointerdown', 'pointermove', 'pointerup'];
else if (window.navigator.msPointerEnabled) desktopEvents = ['MSPointerDown', 'MSPointerMove', 'MSPointerUp'];
s.touchEvents = {
    start : s.support.touch || !s.params.simulateTouch  ? 'touchstart' : desktopEvents[0],
    move : s.support.touch || !s.params.simulateTouch ? 'touchmove' : desktopEvents[1],
    end : s.support.touch || !s.params.simulateTouch ? 'touchend' : desktopEvents[2]
};


// WP8 Touch Events Fix
if (window.navigator.pointerEnabled || window.navigator.msPointerEnabled) {
    (s.params.touchEventsTarget === 'container' ? s.container : s.wrapper).addClass('swiper-wp8-' + s.params.direction);
}

// Attach/detach events
s.initEvents = function (detach) {
    var actionDom = detach ? 'off' : 'on';
    var action = detach ? 'removeEventListener' : 'addEventListener';
    var touchEventsTarget = s.params.touchEventsTarget === 'container' ? s.container[0] : s.wrapper[0];
    var target = s.support.touch ? touchEventsTarget : document;

    var moveCapture = s.params.nested ? true : false;

    //Touch Events
    if (s.browser.ie) {
        touchEventsTarget[action](s.touchEvents.start, s.onTouchStart, false);
        target[action](s.touchEvents.move, s.onTouchMove, moveCapture);
        target[action](s.touchEvents.end, s.onTouchEnd, false);
    }
    else {
        if (s.support.touch) {
            touchEventsTarget[action](s.touchEvents.start, s.onTouchStart, false);
            touchEventsTarget[action](s.touchEvents.move, s.onTouchMove, moveCapture);
            touchEventsTarget[action](s.touchEvents.end, s.onTouchEnd, false);
        }
        if (params.simulateTouch && !s.device.ios && !s.device.android) {
            touchEventsTarget[action]('mousedown', s.onTouchStart, false);
            document[action]('mousemove', s.onTouchMove, moveCapture);
            document[action]('mouseup', s.onTouchEnd, false);
        }
    }
    window[action]('resize', s.onResize);

    // Next, Prev, Index
    if (s.params.nextButton) {
        $(s.params.nextButton)[actionDom]('click', s.onClickNext);
        if (s.params.a11y && s.a11y) $(s.params.nextButton)[actionDom]('keydown', s.a11y.onEnterKey);
    }
    if (s.params.prevButton) {
        $(s.params.prevButton)[actionDom]('click', s.onClickPrev);
        if (s.params.a11y && s.a11y) $(s.params.prevButton)[actionDom]('keydown', s.a11y.onEnterKey);
    }
    if (s.params.pagination && s.params.paginationClickable) {
        $(s.paginationContainer)[actionDom]('click', '.' + s.params.bulletClass, s.onClickIndex);
        if (s.params.a11y && s.a11y) $(s.paginationContainer)[actionDom]('keydown', '.' + s.params.bulletClass, s.a11y.onEnterKey);
    }

    // Prevent Links Clicks
    if (s.params.preventClicks || s.params.preventClicksPropagation) touchEventsTarget[action]('click', s.preventClicks, true);
};
s.attachEvents = function (detach) {
    s.initEvents();
};
s.detachEvents = function () {
    s.initEvents(true);
};

/*=========================
  Handle Clicks
  ===========================*/
// Prevent Clicks
s.allowClick = true;
s.preventClicks = function (e) {
    if (!s.allowClick) {
        if (s.params.preventClicks) e.preventDefault();
        if (s.params.preventClicksPropagation && s.animating) {
            e.stopPropagation();
            e.stopImmediatePropagation();
        }
    }
};
// Clicks
s.onClickNext = function (e) {
    e.preventDefault();
    if (s.isEnd && !s.params.loop) return;
    s.slideNext();
};
s.onClickPrev = function (e) {
    e.preventDefault();
    if (s.isBeginning && !s.params.loop) return;
    s.slidePrev();
};
s.onClickIndex = function (e) {
    e.preventDefault();
    var index = $(this).index() * s.params.slidesPerGroup;
    if (s.params.loop) index = index + s.loopedSlides;
    s.slideTo(index);
};

/*=========================
  Handle Touches
  ===========================*/
function findElementInEvent(e, selector) {
    var el = $(e.target);
    if (!el.is(selector)) {
        if (typeof selector === 'string') {
            el = el.parents(selector);
        }
        else if (selector.nodeType) {
            var found;
            el.parents().each(function (index, _el) {
                if (_el === selector) found = selector;
            });
            if (!found) return undefined;
            else return selector;
        }
    }
    if (el.length === 0) {
        return undefined;
    }
    return el[0];
}
s.updateClickedSlide = function (e) {
    var slide = findElementInEvent(e, '.' + s.params.slideClass);
    var slideFound = false;
    if (slide) {
        for (var i = 0; i < s.slides.length; i++) {
            if (s.slides[i] === slide) slideFound = true;
        }
    }

    if (slide && slideFound) {
        s.clickedSlide = slide;
        s.clickedIndex = $(slide).index();
    }
    else {
        s.clickedSlide = undefined;
        s.clickedIndex = undefined;
        return;
    }
    if (s.params.slideToClickedSlide && s.clickedIndex !== undefined && s.clickedIndex !== s.activeIndex) {
        var slideToIndex = s.clickedIndex,
            realIndex,
            duplicatedSlides;
        if (s.params.loop) {
            if (s.animating) return;
            realIndex = $(s.clickedSlide).attr('data-swiper-slide-index');
            if (s.params.centeredSlides) {
                if ((slideToIndex < s.loopedSlides - s.params.slidesPerView/2) || (slideToIndex > s.slides.length - s.loopedSlides + s.params.slidesPerView/2)) {
                    s.fixLoop();
                    slideToIndex = s.wrapper.children('.' + s.params.slideClass + '[data-swiper-slide-index="' + realIndex + '"]:not(.swiper-slide-duplicate)').eq(0).index();
                    setTimeout(function () {
                        s.slideTo(slideToIndex);
                    }, 0);
                }
                else {
                    s.slideTo(slideToIndex);
                }
            }
            else {
                if (slideToIndex > s.slides.length - s.params.slidesPerView) {
                    s.fixLoop();
                    slideToIndex = s.wrapper.children('.' + s.params.slideClass + '[data-swiper-slide-index="' + realIndex + '"]:not(.swiper-slide-duplicate)').eq(0).index();
                    setTimeout(function () {
                        s.slideTo(slideToIndex);
                    }, 0);
                }
                else {
                    s.slideTo(slideToIndex);
                }
            }
        }
        else {
            s.slideTo(slideToIndex);
        }
    }
};

var isTouched,
    isMoved,
    allowTouchCallbacks,
    touchStartTime,
    isScrolling,
    currentTranslate,
    startTranslate,
    allowThresholdMove,
    // Form elements to match
    formElements = 'input, select, textarea, button',
    // Last click time
    lastClickTime = Date.now(), clickTimeout,
    //Velocities
    velocities = [],
    allowMomentumBounce;

// Animating Flag
s.animating = false;

// Touches information
s.touches = {
    startX: 0,
    startY: 0,
    currentX: 0,
    currentY: 0,
    diff: 0
};

// Touch handlers
var isTouchEvent, startMoving;
s.onTouchStart = function (e) {
    if (e.originalEvent) e = e.originalEvent;
    isTouchEvent = e.type === 'touchstart';
    if (!isTouchEvent && 'which' in e && e.which === 3) return;
    if (s.params.noSwiping && findElementInEvent(e, '.' + s.params.noSwipingClass)) {
        s.allowClick = true;
        return;
    }
    if (s.params.swipeHandler) {
        if (!findElementInEvent(e, s.params.swipeHandler)) return;
    }

    var startX = s.touches.currentX = e.type === 'touchstart' ? e.targetTouches[0].pageX : e.pageX;
    var startY = s.touches.currentY = e.type === 'touchstart' ? e.targetTouches[0].pageY : e.pageY;

    // Do NOT start if iOS edge swipe is detected. Otherwise iOS app (UIWebView) cannot swipe-to-go-back anymore
    if(s.device.ios && s.params.iOSEdgeSwipeDetection && startX <= s.params.iOSEdgeSwipeThreshold) {
        return;
    }

    isTouched = true;
    isMoved = false;
    allowTouchCallbacks = true;
    isScrolling = undefined;
    startMoving = undefined;
    s.touches.startX = startX;
    s.touches.startY = startY;
    touchStartTime = Date.now();
    s.allowClick = true;
    s.updateContainerSize();
    s.swipeDirection = undefined;
    if (s.params.threshold > 0) allowThresholdMove = false;
    if (e.type !== 'touchstart') {
        var preventDefault = true;
        if ($(e.target).is(formElements)) preventDefault = false;
        if (document.activeElement && $(document.activeElement).is(formElements)) {
            document.activeElement.blur();
        }
        if (preventDefault) {
            e.preventDefault();
        }
    }
    s.emit('onTouchStart', s, e);
};

s.onTouchMove = function (e) {
    if (e.originalEvent) e = e.originalEvent;
    if (isTouchEvent && e.type === 'mousemove') return;
    if (e.preventedByNestedSwiper) return;
    if (s.params.onlyExternal) {
        // isMoved = true;
        s.allowClick = false;
        if (isTouched) {
            s.touches.startX = s.touches.currentX = e.type === 'touchmove' ? e.targetTouches[0].pageX : e.pageX;
            s.touches.startY = s.touches.currentY = e.type === 'touchmove' ? e.targetTouches[0].pageY : e.pageY;
            touchStartTime = Date.now();
        }
        return;
    }
    if (isTouchEvent && document.activeElement) {
        if (e.target === document.activeElement && $(e.target).is(formElements)) {
            isMoved = true;
            s.allowClick = false;
            return;
        }
    }
    if (allowTouchCallbacks) {
        s.emit('onTouchMove', s, e);
    }
    if (e.targetTouches && e.targetTouches.length > 1) return;

    s.touches.currentX = e.type === 'touchmove' ? e.targetTouches[0].pageX : e.pageX;
    s.touches.currentY = e.type === 'touchmove' ? e.targetTouches[0].pageY : e.pageY;

    if (typeof isScrolling === 'undefined') {
        var touchAngle = Math.atan2(Math.abs(s.touches.currentY - s.touches.startY), Math.abs(s.touches.currentX - s.touches.startX)) * 180 / Math.PI;
        isScrolling = isH() ? touchAngle > s.params.touchAngle : (90 - touchAngle > s.params.touchAngle);
    }
    if (isScrolling) {
        s.emit('onTouchMoveOpposite', s, e);
    }
    if (typeof startMoving === 'undefined' && s.browser.ieTouch) {
        if (s.touches.currentX !== s.touches.startX || s.touches.currentY !== s.touches.startY) {
            startMoving = true;
        }
    }
    if (!isTouched) return;
    if (isScrolling)  {
        isTouched = false;
        return;
    }
    if (!startMoving && s.browser.ieTouch) {
        return;
    }
    s.allowClick = false;
    s.emit('onSliderMove', s, e);
    e.preventDefault();
    if (s.params.touchMoveStopPropagation && !s.params.nested) {
        e.stopPropagation();
    }

    if (!isMoved) {
        if (params.loop) {
            s.fixLoop();
        }
        startTranslate = s.getWrapperTranslate();
        s.setWrapperTransition(0);
        if (s.animating) {
            s.wrapper.trigger('webkitTransitionEnd transitionend oTransitionEnd MSTransitionEnd msTransitionEnd');
        }
        if (s.params.autoplay && s.autoplaying) {
            if (s.params.autoplayDisableOnInteraction) {
                s.stopAutoplay();
            }
            else {
                s.pauseAutoplay();
            }
        }
        allowMomentumBounce = false;
        //Grab Cursor
        if (s.params.grabCursor) {
            s.container[0].style.cursor = 'move';
            s.container[0].style.cursor = '-webkit-grabbing';
            s.container[0].style.cursor = '-moz-grabbin';
            s.container[0].style.cursor = 'grabbing';
        }
    }
    isMoved = true;

    var diff = s.touches.diff = isH() ? s.touches.currentX - s.touches.startX : s.touches.currentY - s.touches.startY;

    diff = diff * s.params.touchRatio;
    if (s.rtl) diff = -diff;

    s.swipeDirection = diff > 0 ? 'prev' : 'next';
    currentTranslate = diff + startTranslate;

    var disableParentSwiper = true;
    if ((diff > 0 && currentTranslate > s.minTranslate())) {
        disableParentSwiper = false;
        if (s.params.resistance) currentTranslate = s.minTranslate() - 1 + Math.pow(-s.minTranslate() + startTranslate + diff, s.params.resistanceRatio);
    }
    else if (diff < 0 && currentTranslate < s.maxTranslate()) {
        disableParentSwiper = false;
        if (s.params.resistance) currentTranslate = s.maxTranslate() + 1 - Math.pow(s.maxTranslate() - startTranslate - diff, s.params.resistanceRatio);
    }

    if (disableParentSwiper) {
        e.preventedByNestedSwiper = true;
    }

    // Directions locks
    if (!s.params.allowSwipeToNext && s.swipeDirection === 'next' && currentTranslate < startTranslate) {
        currentTranslate = startTranslate;
    }
    if (!s.params.allowSwipeToPrev && s.swipeDirection === 'prev' && currentTranslate > startTranslate) {
        currentTranslate = startTranslate;
    }

    if (!s.params.followFinger) return;

    // Threshold
    if (s.params.threshold > 0) {
        if (Math.abs(diff) > s.params.threshold || allowThresholdMove) {
            if (!allowThresholdMove) {
                allowThresholdMove = true;
                s.touches.startX = s.touches.currentX;
                s.touches.startY = s.touches.currentY;
                currentTranslate = startTranslate;
                s.touches.diff = isH() ? s.touches.currentX - s.touches.startX : s.touches.currentY - s.touches.startY;
                return;
            }
        }
        else {
            currentTranslate = startTranslate;
            return;
        }
    }
    // Update active index in free mode
    if (s.params.freeMode || s.params.watchSlidesProgress) {
        s.updateActiveIndex();
    }
    if (s.params.freeMode) {
        //Velocity
        if (velocities.length === 0) {
            velocities.push({
                position: s.touches[isH() ? 'startX' : 'startY'],
                time: touchStartTime
            });
        }
        velocities.push({
            position: s.touches[isH() ? 'currentX' : 'currentY'],
            time: (new window.Date()).getTime()
        });
    }
    // Update progress
    s.updateProgress(currentTranslate);
    // Update translate
    s.setWrapperTranslate(currentTranslate);
};
s.onTouchEnd = function (e) {
    if (e.originalEvent) e = e.originalEvent;
    if (allowTouchCallbacks) {
        s.emit('onTouchEnd', s, e);
    }
    allowTouchCallbacks = false;
    if (!isTouched) return;
    //Return Grab Cursor
    if (s.params.grabCursor && isMoved && isTouched) {
        s.container[0].style.cursor = 'move';
        s.container[0].style.cursor = '-webkit-grab';
        s.container[0].style.cursor = '-moz-grab';
        s.container[0].style.cursor = 'grab';
    }

    // Time diff
    var touchEndTime = Date.now();
    var timeDiff = touchEndTime - touchStartTime;

    // Tap, doubleTap, Click
    if (s.allowClick) {
        s.updateClickedSlide(e);
        s.emit('onTap', s, e);
        if (timeDiff < 300 && (touchEndTime - lastClickTime) > 300) {
            if (clickTimeout) clearTimeout(clickTimeout);
            clickTimeout = setTimeout(function () {
                if (!s) return;
                if (s.params.paginationHide && s.paginationContainer.length > 0 && !$(e.target).hasClass(s.params.bulletClass)) {
                    s.paginationContainer.toggleClass(s.params.paginationHiddenClass);
                }
                s.emit('onClick', s, e);
            }, 300);

        }
        if (timeDiff < 300 && (touchEndTime - lastClickTime) < 300) {
            if (clickTimeout) clearTimeout(clickTimeout);
            s.emit('onDoubleTap', s, e);
        }
    }

    lastClickTime = Date.now();
    setTimeout(function () {
        if (s) s.allowClick = true;
    }, 0);

    if (!isTouched || !isMoved || !s.swipeDirection || s.touches.diff === 0 || currentTranslate === startTranslate) {
        isTouched = isMoved = false;
        return;
    }
    isTouched = isMoved = false;

    var currentPos;
    if (s.params.followFinger) {
        currentPos = s.rtl ? s.translate : -s.translate;
    }
    else {
        currentPos = -currentTranslate;
    }
    if (s.params.freeMode) {
        if (currentPos < -s.minTranslate()) {
            s.slideTo(s.activeIndex);
            return;
        }
        else if (currentPos > -s.maxTranslate()) {
            if (s.slides.length < s.snapGrid.length) {
                s.slideTo(s.snapGrid.length - 1);
            }
            else {
                s.slideTo(s.slides.length - 1);
            }
            return;
        }

        if (s.params.freeModeMomentum) {
            if (velocities.length > 1) {
                var lastMoveEvent = velocities.pop(), velocityEvent = velocities.pop();

                var distance = lastMoveEvent.position - velocityEvent.position;
                var time = lastMoveEvent.time - velocityEvent.time;
                s.velocity = distance / time;
                s.velocity = s.velocity / 2;
                if (Math.abs(s.velocity) < s.params.freeModeMinimumVelocity) {
                    s.velocity = 0;
                }
                // this implies that the user stopped moving a finger then released.
                // There would be no events with distance zero, so the last event is stale.
                if (time > 150 || (new window.Date().getTime() - lastMoveEvent.time) > 300) {
                    s.velocity = 0;
                }
            } else {
                s.velocity = 0;
            }

            velocities.length = 0;
            var momentumDuration = 1000 * s.params.freeModeMomentumRatio;
            var momentumDistance = s.velocity * momentumDuration;

            var newPosition = s.translate + momentumDistance;
            if (s.rtl) newPosition = - newPosition;
            var doBounce = false;
            var afterBouncePosition;
            var bounceAmount = Math.abs(s.velocity) * 20 * s.params.freeModeMomentumBounceRatio;
            if (newPosition < s.maxTranslate()) {
                if (s.params.freeModeMomentumBounce) {
                    if (newPosition + s.maxTranslate() < -bounceAmount) {
                        newPosition = s.maxTranslate() - bounceAmount;
                    }
                    afterBouncePosition = s.maxTranslate();
                    doBounce = true;
                    allowMomentumBounce = true;
                }
                else {
                    newPosition = s.maxTranslate();
                }
            }
            else if (newPosition > s.minTranslate()) {
                if (s.params.freeModeMomentumBounce) {
                    if (newPosition - s.minTranslate() > bounceAmount) {
                        newPosition = s.minTranslate() + bounceAmount;
                    }
                    afterBouncePosition = s.minTranslate();
                    doBounce = true;
                    allowMomentumBounce = true;
                }
                else {
                    newPosition = s.minTranslate();
                }
            }
            else if (s.params.freeModeSticky) {
                var j = 0,
                    nextSlide;
                for (j = 0; j < s.snapGrid.length; j += 1) {
                    if (s.snapGrid[j] > -newPosition) {
                        nextSlide = j;
                        break;
                    }

                }
                if (Math.abs(s.snapGrid[nextSlide] - newPosition) < Math.abs(s.snapGrid[nextSlide - 1] - newPosition) || s.swipeDirection === 'next') {
                    newPosition = s.snapGrid[nextSlide];
                } else {
                    newPosition = s.snapGrid[nextSlide - 1];
                }
                if (!s.rtl) newPosition = - newPosition;
            }
            //Fix duration
            if (s.velocity !== 0) {
                if (s.rtl) {
                    momentumDuration = Math.abs((-newPosition - s.translate) / s.velocity);
                }
                else {
                    momentumDuration = Math.abs((newPosition - s.translate) / s.velocity);
                }
            }
            else if (s.params.freeModeSticky) {
                s.slideReset();
                return;
            }

            if (s.params.freeModeMomentumBounce && doBounce) {
                s.updateProgress(afterBouncePosition);
                s.setWrapperTransition(momentumDuration);
                s.setWrapperTranslate(newPosition);
                s.onTransitionStart();
                s.animating = true;
                s.wrapper.transitionEnd(function () {
                    if (!s || !allowMomentumBounce) return;
                    s.emit('onMomentumBounce', s);

                    s.setWrapperTransition(s.params.speed);
                    s.setWrapperTranslate(afterBouncePosition);
                    s.wrapper.transitionEnd(function () {
                        if (!s) return;
                        s.onTransitionEnd();
                    });
                });
            } else if (s.velocity) {
                s.updateProgress(newPosition);
                s.setWrapperTransition(momentumDuration);
                s.setWrapperTranslate(newPosition);
                s.onTransitionStart();
                if (!s.animating) {
                    s.animating = true;
                    s.wrapper.transitionEnd(function () {
                        if (!s) return;
                        s.onTransitionEnd();
                    });
                }

            } else {
                s.updateProgress(newPosition);
            }

            s.updateActiveIndex();
        }
        if (!s.params.freeModeMomentum || timeDiff >= s.params.longSwipesMs) {
            s.updateProgress();
            s.updateActiveIndex();
        }
        return;
    }

    // Find current slide
    var i, stopIndex = 0, groupSize = s.slidesSizesGrid[0];
    for (i = 0; i < s.slidesGrid.length; i += s.params.slidesPerGroup) {
        if (typeof s.slidesGrid[i + s.params.slidesPerGroup] !== 'undefined') {
            if (currentPos >= s.slidesGrid[i] && currentPos < s.slidesGrid[i + s.params.slidesPerGroup]) {
                stopIndex = i;
                groupSize = s.slidesGrid[i + s.params.slidesPerGroup] - s.slidesGrid[i];
            }
        }
        else {
            if (currentPos >= s.slidesGrid[i]) {
                stopIndex = i;
                groupSize = s.slidesGrid[s.slidesGrid.length - 1] - s.slidesGrid[s.slidesGrid.length - 2];
            }
        }
    }

    // Find current slide size
    var ratio = (currentPos - s.slidesGrid[stopIndex]) / groupSize;

    if (timeDiff > s.params.longSwipesMs) {
        // Long touches
        if (!s.params.longSwipes) {
            s.slideTo(s.activeIndex);
            return;
        }
        if (s.swipeDirection === 'next') {
            if (ratio >= s.params.longSwipesRatio) s.slideTo(stopIndex + s.params.slidesPerGroup);
            else s.slideTo(stopIndex);

        }
        if (s.swipeDirection === 'prev') {
            if (ratio > (1 - s.params.longSwipesRatio)) s.slideTo(stopIndex + s.params.slidesPerGroup);
            else s.slideTo(stopIndex);
        }
    }
    else {
        // Short swipes
        if (!s.params.shortSwipes) {
            s.slideTo(s.activeIndex);
            return;
        }
        if (s.swipeDirection === 'next') {
            s.slideTo(stopIndex + s.params.slidesPerGroup);

        }
        if (s.swipeDirection === 'prev') {
            s.slideTo(stopIndex);
        }
    }
};
/*=========================
  Transitions
  ===========================*/
s._slideTo = function (slideIndex, speed) {
    return s.slideTo(slideIndex, speed, true, true);
};
s.slideTo = function (slideIndex, speed, runCallbacks, internal) {
    if (typeof runCallbacks === 'undefined') runCallbacks = true;
    if (typeof slideIndex === 'undefined') slideIndex = 0;
    if (slideIndex < 0) slideIndex = 0;
    s.snapIndex = Math.floor(slideIndex / s.params.slidesPerGroup);
    if (s.snapIndex >= s.snapGrid.length) slideIndex = s.snapIndex = s.snapGrid.length - 1;

    
    if(s.isGrid()){
        if(slideIndex>s.activeIndex){ // next
            if (s.activeIndex >= s.snapGrid.length) s.activeIndex = s.snapGrid.length - 1;
            var maxRight = s.snapGrid[s.activeIndex] + s.container.width() - s.params.spaceBetween;
            for (var i = s.snapIndex; i < s.snapGrid.length; i++) {
                slideIndex = s.snapIndex = i;
                if(s.snapGrid[i] + s.slidesSizesGrid[i] > maxRight)
                    break;
                    
            }
        }
        else if(slideIndex<s.activeIndex){ // prev
            if (s.activeIndex >= s.snapGrid.length) s.activeIndex = s.snapGrid.length - 1;
            var maxLeft = s.snapGrid[s.activeIndex] - (s.container.width() + s.params.spaceBetween);
            for (var i = s.snapIndex; i >= 0; i--) {
                if(s.snapGrid[i] - s.slidesSizesGrid[i] < maxLeft)
                    break;
                slideIndex = s.snapIndex = i;
            }
        }
    }

    var translate = - s.snapGrid[s.snapIndex];
    // Stop autoplay
    if (s.params.autoplay && s.autoplaying) {
        if (internal || !s.params.autoplayDisableOnInteraction) {
            s.pauseAutoplay(speed);
        }
        else {
            s.stopAutoplay();
        }
    }
    // Update progress
    s.updateProgress(translate);

    // Normalize slideIndex
    if(!s.isGrid() && !s.isLightbox())
    for (var i = 0; i < s.slidesGrid.length; i++) {
        if (- Math.floor(translate * 100) >= Math.floor(s.slidesGrid[i] * 100)) {
            slideIndex = i;
        }
    }

    // Directions locks
    if (!s.params.allowSwipeToNext && translate < s.translate && translate < s.minTranslate()) {
        return false;
    }
    if (!s.params.allowSwipeToPrev && translate > s.translate && translate > s.maxTranslate()) {
        if ((s.activeIndex || 0) !== slideIndex ) return false;
    }

    // Update Index
    if (typeof speed === 'undefined') speed = s.params.speed;
    s.previousIndex = s.activeIndex || 0;
    s.activeIndex = slideIndex;

    // Update Height
    if (s.params.autoHeight) {
        s.updateAutoHeight();
    }
    if ((s.rtl && -translate === s.translate) || (!s.rtl && translate === s.translate)) {
        s.updateClasses();
        if (s.params.effect !== 'slide') {
            s.setWrapperTranslate(translate);
        }
        return false;
    }
    s.updateClasses();
    s.onTransitionStart(runCallbacks);

    if (speed === 0) {
        s.setWrapperTranslate(translate);
        s.setWrapperTransition(0);
        s.onTransitionEnd(runCallbacks);
    }
    else {
        s.setWrapperTranslate(translate);
        s.setWrapperTransition(speed);
        if (!s.animating) {
            s.animating = true;
            s.wrapper.transitionEnd(function () {
                if (!s) return;
                s.onTransitionEnd(runCallbacks);
            });
        }

    }

    return true;
};

s.onTransitionStart = function (runCallbacks) {
    if (typeof runCallbacks === 'undefined') runCallbacks = true;
    if (s.params.autoHeight) {
        s.updateAutoHeight();
    }
    if (s.lazy) s.lazy.onTransitionStart();
    if (runCallbacks) {
        s.emit('onTransitionStart', s);
        if (s.activeIndex !== s.previousIndex) {
            s.emit('onSlideChangeStart', s);
            if (s.activeIndex > s.previousIndex) {
                s.emit('onSlideNextStart', s);
            }
            else {
                s.emit('onSlidePrevStart', s);
            }
        }

    }
};
s.onTransitionEnd = function (runCallbacks) {
    s.animating = false;
    s.setWrapperTransition(0);
    if (typeof runCallbacks === 'undefined') runCallbacks = true;
    if (s.lazy) s.lazy.onTransitionEnd();
    if (runCallbacks) {
        s.emit('onTransitionEnd', s);
        if (s.activeIndex !== s.previousIndex) {
            s.emit('onSlideChangeEnd', s);
            if (s.activeIndex > s.previousIndex) {
                s.emit('onSlideNextEnd', s);
            }
            else {
                s.emit('onSlidePrevEnd', s);
            }
        }
    }
    if (s.params.hashnav && s.hashnav) {
        s.hashnav.setHash();
    }

};
s.slideNext = function (runCallbacks, speed, internal) {
    if (s.params.loop) {
        if (s.animating) return false;
        s.fixLoop();
        var clientLeft = s.container[0].clientLeft;
        return s.slideTo(s.activeIndex + s.params.slidesPerGroup, speed, runCallbacks, internal);
    }
    else return s.slideTo(s.activeIndex + s.params.slidesPerGroup, speed, runCallbacks, internal);
};
s._slideNext = function (speed) {
    return s.slideNext(true, speed, true);
};
s.slidePrev = function (runCallbacks, speed, internal) {
    if (s.params.loop) {
        if (s.animating) return false;
        s.fixLoop();
        var clientLeft = s.container[0].clientLeft;
        return s.slideTo(s.activeIndex - 1, speed, runCallbacks, internal);
    }
    else return s.slideTo(s.activeIndex - 1, speed, runCallbacks, internal);
};
s._slidePrev = function (speed) {
    return s.slidePrev(true, speed, true);
};
s.slideReset = function (runCallbacks, speed, internal) {
    return s.slideTo(s.activeIndex, speed, runCallbacks);
};

/*=========================
  Translate/transition helpers
  ===========================*/
s.setWrapperTransition = function (duration, byController) {
    s.wrapper.transition(duration);
    if (s.params.effect !== 'slide' && s.effects[s.params.effect]) {
        s.effects[s.params.effect].setTransition(duration);
    }
    if (s.params.parallax && s.parallax) {
        s.parallax.setTransition(duration);
    }
    if (s.params.scrollbar && s.scrollbar) {
        s.scrollbar.setTransition(duration);
    }
    if (s.params.control && s.controller) {
        s.controller.setTransition(duration, byController);
    }
    s.emit('onSetTransition', s, duration);
};
// zm edited;
s.setWrapperTranslate = function (translate, updateActiveIndex, byController) {
    var x = 0, y = 0, z = 0;
    if (isH()) {
        if(!s.params.lightBox || s.container.hasClass('gallery-thumbs')){
            var width = s.virtualSize,
                container_width = s.container.width(); 
            if(translate>0)
                translate = 0.01; // Added fraction to avoid accidental lightbox open.
            else if(translate< -(width - container_width))
                translate = -(width - container_width) + 0.01; // Added fraction to avoid accidental lightbox open.
        }
        x = s.rtl ? -translate : translate;
    }
    else {
        y = translate;
    }

    if (s.params.roundLengths) {
        x = round(x);
        y = round(y);
    }

    if (!s.params.virtualTranslate) {
        if (s.support.transforms3d) s.wrapper.transform('translate3d(' + x + 'px, ' + y + 'px, ' + z + 'px)');
        else s.wrapper.transform('translate(' + x + 'px, ' + y + 'px)');
    }

    s.translate = isH() ? x : y;

    // Check if we need to update progress
    var progress;
    var translatesDiff = s.maxTranslate() - s.minTranslate();
    if (translatesDiff === 0) {
        progress = 0;
    }
    else {
        progress = (translate - s.minTranslate()) / (translatesDiff);
    }
    if (progress !== s.progress) {
        s.updateProgress(translate);
    }

    if (updateActiveIndex) s.updateActiveIndex();
    if (s.params.effect !== 'slide' && s.effects[s.params.effect]) {
        s.effects[s.params.effect].setTranslate(s.translate);
    }
    if (s.params.parallax && s.parallax) {
        s.parallax.setTranslate(s.translate);
    }
    if (s.params.scrollbar && s.scrollbar) {
        s.scrollbar.setTranslate(s.translate);
    }
    if (s.params.control && s.controller) {
        s.controller.setTranslate(s.translate, byController);
    }
    s.emit('onSetTranslate', s, s.translate);
};

s.getTranslate = function (el, axis) {
    var matrix, curTransform, curStyle, transformMatrix;

    // automatic axis detection
    if (typeof axis === 'undefined') {
        axis = 'x';
    }

    if (s.params.virtualTranslate) {
        return s.rtl ? -s.translate : s.translate;
    }

    curStyle = window.getComputedStyle(el, null);
    if (window.WebKitCSSMatrix) {
        curTransform = curStyle.transform || curStyle.webkitTransform;
        if (curTransform.split(',').length > 6) {
            curTransform = curTransform.split(', ').map(function(a){
                return a.replace(',','.');
            }).join(', ');
        }
        // Some old versions of Webkit choke when 'none' is passed; pass
        // empty string instead in this case
        transformMatrix = new window.WebKitCSSMatrix(curTransform === 'none' ? '' : curTransform);
    }
    else {
        transformMatrix = curStyle.MozTransform || curStyle.OTransform || curStyle.MsTransform || curStyle.msTransform  || curStyle.transform || curStyle.getPropertyValue('transform').replace('translate(', 'matrix(1, 0, 0, 1,');
        matrix = transformMatrix.toString().split(',');
    }

    if (axis === 'x') {
        //Latest Chrome and webkits Fix
        if (window.WebKitCSSMatrix)
            curTransform = transformMatrix.m41;
        //Crazy IE10 Matrix
        else if (matrix.length === 16)
            curTransform = parseFloat(matrix[12]);
        //Normal Browsers
        else
            curTransform = parseFloat(matrix[4]);
    }
    if (axis === 'y') {
        //Latest Chrome and webkits Fix
        if (window.WebKitCSSMatrix)
            curTransform = transformMatrix.m42;
        //Crazy IE10 Matrix
        else if (matrix.length === 16)
            curTransform = parseFloat(matrix[13]);
        //Normal Browsers
        else
            curTransform = parseFloat(matrix[5]);
    }
    if (s.rtl && curTransform) curTransform = -curTransform;
    return curTransform || 0;
};
s.getWrapperTranslate = function (axis) {
    if (typeof axis === 'undefined') {
        axis = isH() ? 'x' : 'y';
    }
    return s.getTranslate(s.wrapper[0], axis);
};

/*=========================
  Observer
  ===========================*/
s.observers = [];
function initObserver(target, options) {
    options = options || {};
    // create an observer instance
    var ObserverFunc = window.MutationObserver || window.WebkitMutationObserver;
    var observer = new ObserverFunc(function (mutations) {
        mutations.forEach(function (mutation) {
            s.onResize(true);
            s.emit('onObserverUpdate', s, mutation);
        });
    });

    observer.observe(target, {
        attributes: typeof options.attributes === 'undefined' ? true : options.attributes,
        childList: typeof options.childList === 'undefined' ? true : options.childList,
        characterData: typeof options.characterData === 'undefined' ? true : options.characterData
    });

    s.observers.push(observer);
}
s.initObservers = function () {
    if (s.params.observeParents) {
        var containerParents = s.container.parents();
        for (var i = 0; i < containerParents.length; i++) {
            initObserver(containerParents[i]);
        }
    }

    // Observe container
    initObserver(s.container[0], {childList: false});

    // Observe wrapper
    initObserver(s.wrapper[0], {attributes: false});
};
s.disconnectObservers = function () {
    for (var i = 0; i < s.observers.length; i++) {
        s.observers[i].disconnect();
    }
    s.observers = [];
};
/*=========================
  Loop
  ===========================*/
// Create looped slides
s.createLoop = function () {
    // Remove duplicated slides
    s.wrapper.children('.' + s.params.slideClass + '.' + s.params.slideDuplicateClass).remove();

    var slides = s.wrapper.children('.' + s.params.slideClass);

    if(s.params.slidesPerView === 'auto' && !s.params.loopedSlides) s.params.loopedSlides = slides.length;

    s.loopedSlides = parseInt(s.params.loopedSlides || s.params.slidesPerView, 10);
    s.loopedSlides = s.loopedSlides + s.params.loopAdditionalSlides;
    if (s.loopedSlides > slides.length) {
        s.loopedSlides = slides.length;
    }

    var prependSlides = [], appendSlides = [], i;
    slides.each(function (index, el) {
        var slide = $(this);
        if (index < s.loopedSlides) appendSlides.push(el);
        if (index < slides.length && index >= slides.length - s.loopedSlides) prependSlides.push(el);
        slide.attr('data-swiper-slide-index', index);
    });
    for (i = 0; i < appendSlides.length; i++) {
        s.wrapper.append($(appendSlides[i].cloneNode(true)).addClass(s.params.slideDuplicateClass));
    }
    for (i = prependSlides.length - 1; i >= 0; i--) {
        s.wrapper.prepend($(prependSlides[i].cloneNode(true)).addClass(s.params.slideDuplicateClass));
    }
};
s.destroyLoop = function () {
    s.wrapper.children('.' + s.params.slideClass + '.' + s.params.slideDuplicateClass).remove();
    s.slides.removeAttr('data-swiper-slide-index');
};
s.fixLoop = function () {
    var newIndex;
    //Fix For Negative Oversliding
    if (s.activeIndex < s.loopedSlides) {
        newIndex = s.slides.length - s.loopedSlides * 3 + s.activeIndex;
        newIndex = newIndex + s.loopedSlides;
        s.slideTo(newIndex, 0, false, true);
    }
    //Fix For Positive Oversliding
    else if ((s.params.slidesPerView === 'auto' && s.activeIndex >= s.loopedSlides * 2) || (s.activeIndex > s.slides.length - s.params.slidesPerView * 2)) {
        newIndex = -s.slides.length + s.activeIndex + s.loopedSlides;
        newIndex = newIndex + s.loopedSlides;
        s.slideTo(newIndex, 0, false, true);
    }
};
/*=========================
  Append/Prepend/Remove Slides
  ===========================*/
s.appendSlide = function (slides) {
    if (s.params.loop) {
        s.destroyLoop();
    }
    if (typeof slides === 'object' && slides.length) {
        for (var i = 0; i < slides.length; i++) {
            if (slides[i]) s.wrapper.append(slides[i]);
        }
    }
    else {
        s.wrapper.append(slides);
    }
    if (s.params.loop) {
        s.createLoop();
    }
    if (!(s.params.observer && s.support.observer)) {
        s.update(true);
    }
};
s.prependSlide = function (slides) {
    if (s.params.loop) {
        s.destroyLoop();
    }
    var newActiveIndex = s.activeIndex + 1;
    if (typeof slides === 'object' && slides.length) {
        for (var i = 0; i < slides.length; i++) {
            if (slides[i]) s.wrapper.prepend(slides[i]);
        }
        newActiveIndex = s.activeIndex + slides.length;
    }
    else {
        s.wrapper.prepend(slides);
    }
    if (s.params.loop) {
        s.createLoop();
    }
    if (!(s.params.observer && s.support.observer)) {
        s.update(true);
    }
    s.slideTo(newActiveIndex, 0, false);
};
s.removeSlide = function (slidesIndexes) {
    if (s.params.loop) {
        s.destroyLoop();
        s.slides = s.wrapper.children('.' + s.params.slideClass);
    }
    var newActiveIndex = s.activeIndex,
        indexToRemove;
    if (typeof slidesIndexes === 'object' && slidesIndexes.length) {
        for (var i = 0; i < slidesIndexes.length; i++) {
            indexToRemove = slidesIndexes[i];
            if (s.slides[indexToRemove]) s.slides.eq(indexToRemove).remove();
            if (indexToRemove < newActiveIndex) newActiveIndex--;
        }
        newActiveIndex = Math.max(newActiveIndex, 0);
    }
    else {
        indexToRemove = slidesIndexes;
        if (s.slides[indexToRemove]) s.slides.eq(indexToRemove).remove();
        if (indexToRemove < newActiveIndex) newActiveIndex--;
        newActiveIndex = Math.max(newActiveIndex, 0);
    }

    if (s.params.loop) {
        s.createLoop();
    }

    if (!(s.params.observer && s.support.observer)) {
        s.update(true);
    }
    if (s.params.loop) {
        s.slideTo(newActiveIndex + s.loopedSlides, 0, false);
    }
    else {
        s.slideTo(newActiveIndex, 0, false);
    }

};
s.removeAllSlides = function () {
    var slidesIndexes = [];
    for (var i = 0; i < s.slides.length; i++) {
        slidesIndexes.push(i);
    }
    s.removeSlide(slidesIndexes);
};
;
s.isGrid = function(){
    if(!s.params.lightBox && (s.params.sliderType == "12grid" || s.params.sliderType == "12mosaic"))
        return true;
}
s.is12grid = function(){
    if(!s.params.lightBox && s.params.sliderType == "12grid")
        return true;
}
s.is12mosaic = function(){
    if(!s.params.lightBox && s.params.sliderType == "12mosaic")
        return true;
}
s.isLightbox = function(){
    if(s.params.lightBox)
        return true;
}

s.setSlideSize_12mosaic = function(slide, s){
    if( typeof slide == 'undefined' ) {
        return 0;
    }
    
    var width, height;
    var slide = jQuery(slide);
    var img = slide.find('img');
    var maxHeight = s.container.height() / s.params.slidesPerColumn;

    if( typeof img[0] == 'undefined' ) {
        return 0;
    }

    var attrWidth  = parseInt(img.attr('width'));
    var attrHeight  = parseInt(img.attr('height'));
    var ratio   = attrWidth / attrHeight;

    if(slide.is(':first-child')){
        height = s.container.height();
    }
    else{
        height = maxHeight;
    }
    width   = height * ratio;
    img.width(width)
         .height(height);
    slide.width(width)
         .height(height);
    img[0].style.setProperty('width', width + "px", 'important');
    img[0].style.setProperty('height', height + "px", 'important');
    return width;
}

s.setSlideSize_12grid = function(slide, s){
    if( typeof slide == 'undefined' ) {
        return 0;
    }
    
    var width, height;
    var top = 0, left = 0;
    var slideWidth, slideHeight, slideRatio;
    var aspectWidth, aspectHeight;
    var slide = jQuery(slide);
    var img = slide.find('img');
    var maxHeight = (s.container.height() - s.params.spaceBetween) / s.params.slidesPerColumn;

    if( typeof img[0] == 'undefined' ) {
        return 0;
    }

    var attrWidth  = parseInt(img.attr('width'));
    var attrHeight  = parseInt(img.attr('height'));
    var imgRatio   = attrWidth / attrHeight;

    slideHeight = maxHeight;
    slideWidth   = slideHeight;

    width   = attrWidth;
    height  = attrHeight;

    if(slide.is(':first-child')){
        slideHeight = s.container.height();
        slideRatio = attrWidth / attrHeight;
        slideWidth = slideHeight * imgRatio;
        width = slideWidth + 'px';
        height = slideHeight + 'px';
    }
    else if(1 > imgRatio){ // Here 1 is slide ratio. Because slide width and height is same.
        width = "100%";
        height = "auto";
        aspectHeight = slideWidth / imgRatio;
        top = (slideHeight - aspectHeight) / 2;
    }
    else{
        width = "auto";
        height = "100%";
        aspectWidth = slideHeight * imgRatio;
        left = (slideWidth - aspectWidth) / 2;
    }
    img.css({
        width: width,
        height: height,
        position: 'relative',
        left: left + "px",
        top: top + "px"
    });
    img[0].style.setProperty('width', width , 'important');
    img[0].style.setProperty('height', height , 'important');

    slide.height(slideHeight);
    
    return slideWidth;
}
;
/*=========================
  Images Lazy Loading
  ===========================*/
s.lazy = {
    initialImageLoaded: false,
    loadImageInSlide: function (index, loadInDuplicate) {
        if (typeof index === 'undefined') return;
        if (typeof loadInDuplicate === 'undefined') loadInDuplicate = true;
        if (s.slides.length === 0) return;

        var slide = s.slides.eq(index);
        var img = slide.find('.swiper-lazy:not(.swiper-lazy-loaded):not(.swiper-lazy-loading)');
        if (slide.hasClass('swiper-lazy') && !slide.hasClass('swiper-lazy-loaded') && !slide.hasClass('swiper-lazy-loading')) {
            img = img.add(slide[0]);
        }
        if (img.length === 0) return;

        img.each(function () {
            var _img = $(this);
            _img.addClass('swiper-lazy-loading');
            var background = _img.attr('data-background');
            var src = _img.attr('data-src'),
                srcset = _img.attr('data-srcset');
            s.loadImage(_img[0], (src || background), srcset, false, function (width, height) {
                _img.attr('width', width)
                    .attr('height', height)
                    .attr('data-width', width)
                    .attr('data-height', height);
                if (background) {
                    _img.css('background-image', 'url(' + background + ')');
                    _img.removeAttr('data-background');
                }
                else {
                    if (srcset) {
                        _img.attr('srcset', srcset);
                        _img.removeAttr('data-srcset');
                    }
                    if (src) {
                        _img.attr('src', src);
                        _img.removeAttr('data-src');
                    }

                }

                _img.addClass('swiper-lazy-loaded').removeClass('swiper-lazy-loading');
                slide.find('.swiper-lazy-preloader, .preloader').remove();
                if (s.params.loop && loadInDuplicate) {
                    var slideOriginalIndex = slide.attr('data-swiper-slide-index');
                    if (slide.hasClass(s.params.slideDuplicateClass)) {
                        var originalSlide = s.wrapper.children('[data-swiper-slide-index="' + slideOriginalIndex + '"]:not(.' + s.params.slideDuplicateClass + ')');
                        s.lazy.loadImageInSlide(originalSlide.index(), false);
                    }
                    else {
                        var duplicatedSlide = s.wrapper.children('.' + s.params.slideDuplicateClass + '[data-swiper-slide-index="' + slideOriginalIndex + '"]');
                        s.lazy.loadImageInSlide(duplicatedSlide.index(), false);
                    }
                }
                s.emit('onLazyImageReady', s, slide[0], _img[0]);
            });

            s.emit('onLazyImageLoad', s, slide[0], _img[0]);
        });

    },
    load: function () {
        var i;
        if (s.params.watchSlidesVisibility) {
            s.wrapper.children('.' + s.params.slideVisibleClass).each(function () {
                s.lazy.loadImageInSlide($(this).index());
            });
        }
        else {
            if (s.params.slidesPerView > 1) {
                for (i = s.activeIndex; i < s.activeIndex + s.params.slidesPerView ; i++) {
                    if (s.slides[i]) s.lazy.loadImageInSlide(i);
                }
            }
            else {
                s.lazy.loadImageInSlide(s.activeIndex);
            }
        }
        if (s.params.lazyLoadingInPrevNext) {
            if (s.params.slidesPerView > 1) {
                // Next Slides
                for (i = s.activeIndex + s.params.slidesPerView; i < s.activeIndex + s.params.slidesPerView + s.params.slidesPerView; i++) {
                    if (s.slides[i]) s.lazy.loadImageInSlide(i);
                }
                // Prev Slides
                for (i = s.activeIndex - s.params.slidesPerView; i < s.activeIndex ; i++) {
                    if (s.slides[i]) s.lazy.loadImageInSlide(i);
                }
            }
            else {
                var nextSlide = s.wrapper.children('.' + s.params.slideNextClass);
                if (nextSlide.length > 0) s.lazy.loadImageInSlide(nextSlide.index());

                var prevSlide = s.wrapper.children('.' + s.params.slidePrevClass);
                if (prevSlide.length > 0) s.lazy.loadImageInSlide(prevSlide.index());
            }
        }
    },
    onTransitionStart: function () {
        if (s.params.lazyLoading) {
            if (s.params.lazyLoadingOnTransitionStart || (!s.params.lazyLoadingOnTransitionStart && !s.lazy.initialImageLoaded)) {
                s.lazy.load();
            }
        }
    },
    onTransitionEnd: function () {
        if (s.params.lazyLoading && !s.params.lazyLoadingOnTransitionStart) {
            s.lazy.load();
        }
    }
};
;
/*=========================
  Keyboard Control
  ===========================*/
function handleKeyboard(e) {
    if (e.originalEvent) e = e.originalEvent; //jquery fix
    var kc = e.keyCode || e.charCode;
    // Directions locks
    if (!s.params.allowSwipeToNext && (isH() && kc === 39 || !isH() && kc === 40)) {
        return false;
    }
    if (!s.params.allowSwipeToPrev && (isH() && kc === 37 || !isH() && kc === 38)) {
        return false;
    }
    if (e.shiftKey || e.altKey || e.ctrlKey || e.metaKey) {
        return;
    }
    if (document.activeElement && document.activeElement.nodeName && (document.activeElement.nodeName.toLowerCase() === 'input' || document.activeElement.nodeName.toLowerCase() === 'textarea')) {
        return;
    }
    if (kc === 37 || kc === 39 || kc === 38 || kc === 40) {
        var inView = false;
        //Check that swiper should be inside of visible area of window
        if (s.container.parents('.swiper-slide').length > 0 && s.container.parents('.swiper-slide-active').length === 0) {
            return;
        }
        var windowScroll = {
            left: window.pageXOffset,
            top: window.pageYOffset
        };
        var windowWidth = window.innerWidth;
        var windowHeight = window.innerHeight;
        var swiperOffset = s.container.offset();
        if (s.rtl) swiperOffset.left = swiperOffset.left - s.container[0].scrollLeft;
        var swiperCoord = [
            [swiperOffset.left, swiperOffset.top],
            [swiperOffset.left + s.width, swiperOffset.top],
            [swiperOffset.left, swiperOffset.top + s.height],
            [swiperOffset.left + s.width, swiperOffset.top + s.height]
        ];
        for (var i = 0; i < swiperCoord.length; i++) {
            var point = swiperCoord[i];
            if (
                point[0] >= windowScroll.left && point[0] <= windowScroll.left + windowWidth &&
                point[1] >= windowScroll.top && point[1] <= windowScroll.top + windowHeight
            ) {
                inView = true;
            }

        }
        if (!inView) return;
    }
    if (isH()) {
        if (kc === 37 || kc === 39) {
            if (e.preventDefault) e.preventDefault();
            else e.returnValue = false;
        }
        if ((kc === 39 && !s.rtl) || (kc === 37 && s.rtl)) s.slideNext();
        if ((kc === 37 && !s.rtl) || (kc === 39 && s.rtl)) s.slidePrev();
    }
    else {
        if (kc === 38 || kc === 40) {
            if (e.preventDefault) e.preventDefault();
            else e.returnValue = false;
        }
        if (kc === 40) s.slideNext();
        if (kc === 38) s.slidePrev();
    }
}
s.disableKeyboardControl = function () {
    s.params.keyboardControl = false;
    $(document).off('keydown', handleKeyboard);
};
s.enableKeyboardControl = function () {
    s.params.keyboardControl = true;
    $(document).on('keydown', handleKeyboard);
};
;
/*=========================
  Plugins API. Collect all and init all plugins
  ===========================*/
s._plugins = [];
for (var plugin in s.plugins) {
    var p = s.plugins[plugin](s, s.params[plugin]);
    if (p) s._plugins.push(p);
}
// Method to call all plugins event/method
s.callPlugins = function (eventName) {
    for (var i = 0; i < s._plugins.length; i++) {
        if (eventName in s._plugins[i]) {
            s._plugins[i][eventName](arguments[1], arguments[2], arguments[3], arguments[4], arguments[5]);
        }
    }
};;
/*=========================
  Events/Callbacks/Plugins Emitter
  ===========================*/
function normalizeEventName (eventName) {
    if (eventName.indexOf('on') !== 0) {
        if (eventName[0] !== eventName[0].toUpperCase()) {
            eventName = 'on' + eventName[0].toUpperCase() + eventName.substring(1);
        }
        else {
            eventName = 'on' + eventName;
        }
    }
    return eventName;
}
s.emitterEventListeners = {

};
s.emit = function (eventName) {
    // Trigger callbacks
    if (s.params[eventName]) {
        s.params[eventName](arguments[1], arguments[2], arguments[3], arguments[4], arguments[5]);
    }
    var i;
    // Trigger events
    if (s.emitterEventListeners[eventName]) {
        for (i = 0; i < s.emitterEventListeners[eventName].length; i++) {
            s.emitterEventListeners[eventName][i](arguments[1], arguments[2], arguments[3], arguments[4], arguments[5]);
        }
    }
    // Trigger plugins
    if (s.callPlugins) s.callPlugins(eventName, arguments[1], arguments[2], arguments[3], arguments[4], arguments[5]);
};
s.on = function (eventName, handler) {
    eventName = normalizeEventName(eventName);
    if (!s.emitterEventListeners[eventName]) s.emitterEventListeners[eventName] = [];
    s.emitterEventListeners[eventName].push(handler);
    return s;
};
s.off = function (eventName, handler) {
    var i;
    eventName = normalizeEventName(eventName);
    if (typeof handler === 'undefined') {
        // Remove all handlers for such event
        s.emitterEventListeners[eventName] = [];
        return s;
    }
    if (!s.emitterEventListeners[eventName] || s.emitterEventListeners[eventName].length === 0) return;
    for (i = 0; i < s.emitterEventListeners[eventName].length; i++) {
        if(s.emitterEventListeners[eventName][i] === handler) s.emitterEventListeners[eventName].splice(i, 1);
    }
    return s;
};
s.once = function (eventName, handler) {
    eventName = normalizeEventName(eventName);
    var _handler = function () {
        handler(arguments[0], arguments[1], arguments[2], arguments[3], arguments[4]);
        s.off(eventName, _handler);
    };
    s.on(eventName, _handler);
    return s;
};;
// Accessibility tools
s.a11y = {
    makeFocusable: function ($el) {
        $el.attr('tabIndex', '0');
        return $el;
    },
    addRole: function ($el, role) {
        $el.attr('role', role);
        return $el;
    },

    addLabel: function ($el, label) {
        $el.attr('aria-label', label);
        return $el;
    },

    disable: function ($el) {
        $el.attr('aria-disabled', true);
        return $el;
    },

    enable: function ($el) {
        $el.attr('aria-disabled', false);
        return $el;
    },

    onEnterKey: function (event) {
        if (event.keyCode !== 13) return;
        if ($(event.target).is(s.params.nextButton)) {
            s.onClickNext(event);
            if (s.isEnd) {
                s.a11y.notify(s.params.lastSlideMessage);
            }
            else {
                s.a11y.notify(s.params.nextSlideMessage);
            }
        }
        else if ($(event.target).is(s.params.prevButton)) {
            s.onClickPrev(event);
            if (s.isBeginning) {
                s.a11y.notify(s.params.firstSlideMessage);
            }
            else {
                s.a11y.notify(s.params.prevSlideMessage);
            }
        }
        if ($(event.target).is('.' + s.params.bulletClass)) {
            $(event.target)[0].click();
        }
    },

    liveRegion: $('<span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>'),

    notify: function (message) {
        var notification = s.a11y.liveRegion;
        if (notification.length === 0) return;
        notification.html('');
        notification.html(message);
    },
    init: function () {
        // Setup accessibility
        if (s.params.nextButton) {
            var nextButton = $(s.params.nextButton);
            s.a11y.makeFocusable(nextButton);
            s.a11y.addRole(nextButton, 'button');
            s.a11y.addLabel(nextButton, s.params.nextSlideMessage);
        }
        if (s.params.prevButton) {
            var prevButton = $(s.params.prevButton);
            s.a11y.makeFocusable(prevButton);
            s.a11y.addRole(prevButton, 'button');
            s.a11y.addLabel(prevButton, s.params.prevSlideMessage);
        }

        $(s.container).append(s.a11y.liveRegion);
    },
    initPagination: function () {
        if (s.params.pagination && s.params.paginationClickable && s.bullets && s.bullets.length) {
            s.bullets.each(function () {
                var bullet = $(this);
                s.a11y.makeFocusable(bullet);
                s.a11y.addRole(bullet, 'button');
                s.a11y.addLabel(bullet, s.params.paginationBulletMessage.replace(/{{index}}/, bullet.index() + 1));
            });
        }
    },
    destroy: function () {
        if (s.a11y.liveRegion && s.a11y.liveRegion.length > 0) s.a11y.liveRegion.remove();
    }
};
;
/*=========================
  Init/Destroy
  ===========================*/
s.init = function () {
    if (s.params.loop) s.createLoop();
    s.updateContainerSize();
    s.updateSlidesSize();
    s.updatePagination();
    if (s.params.scrollbar && s.scrollbar) {
        s.scrollbar.set();
        if (s.params.scrollbarDraggable) {
            s.scrollbar.enableDraggable();
        }
    }
    if (s.params.effect !== 'slide' && s.effects[s.params.effect]) {
        if (!s.params.loop) s.updateProgress();
        s.effects[s.params.effect].setTranslate();
    }
    if (s.params.loop) {
        s.slideTo(s.params.initialSlide + s.loopedSlides, 0, s.params.runCallbacksOnInit);
    }
    else {
        s.slideTo(s.params.initialSlide, 0, s.params.runCallbacksOnInit);
        if (s.params.initialSlide === 0) {
            if (s.parallax && s.params.parallax) s.parallax.setTranslate();
            if (s.lazy && s.params.lazyLoading) {
                s.lazy.load();
                s.lazy.initialImageLoaded = true;
            }
        }
    }
    s.attachEvents();
    if (s.params.observer && s.support.observer) {
        s.initObservers();
    }
    if (s.params.preloadImages && !s.params.lazyLoading) {
        s.preloadImages();
    }
    if (s.params.autoplay) {
        s.startAutoplay();
    }
    if (s.params.keyboardControl) {
        if (s.enableKeyboardControl) s.enableKeyboardControl();
    }
    if (s.params.mousewheelControl) {
        if (s.enableMousewheelControl) s.enableMousewheelControl();
    }
    if (s.params.hashnav) {
        if (s.hashnav) s.hashnav.init();
    }
    if (s.params.a11y && s.a11y) s.a11y.init();
    s.emit('onInit', s);
};

// Cleanup dynamic styles
s.cleanupStyles = function () {
    // Container
    s.container.removeClass(s.classNames.join(' ')).removeAttr('style');

    // Wrapper
    s.container.parent().removeAttr('style');
    s.wrapper.removeAttr('style');
    s.container.height('').width('');
    // Slides
    if (s.slides && s.slides.length) {
        s.slides
            .removeClass([
              s.params.slideVisibleClass,
              s.params.slideActiveClass,
              s.params.slideNextClass,
              s.params.slidePrevClass
            ].join(' '))
            .removeAttr('style')
            .removeAttr('data-swiper-column')
            .removeAttr('data-swiper-row');

        s.slides.width('').height('');
        s.slides.each(function(){
            $(this).find('img').removeAttr('style');
        })
    }

    // Pagination/Bullets
    if (s.paginationContainer && s.paginationContainer.length) {
        s.paginationContainer.removeClass(s.params.paginationHiddenClass);
    }
    if (s.bullets && s.bullets.length) {
        s.bullets.removeClass(s.params.bulletActiveClass);
    }

    // Buttons
    if (s.params.prevButton) $(s.params.prevButton).removeClass(s.params.buttonDisabledClass);
    if (s.params.nextButton) $(s.params.nextButton).removeClass(s.params.buttonDisabledClass);

    // Scrollbar
    if (s.params.scrollbar && s.scrollbar) {
        if (s.scrollbar.track && s.scrollbar.track.length) s.scrollbar.track.removeAttr('style');
        if (s.scrollbar.drag && s.scrollbar.drag.length) s.scrollbar.drag.removeAttr('style');
    }
};

// Destroy
s.destroy = function (deleteInstance, cleanupStyles) {
    // Detach evebts
    s.detachEvents();
    // Stop autoplay
    s.stopAutoplay();
    // Disable draggable
    if (s.params.scrollbar && s.scrollbar) {
        if (s.params.scrollbarDraggable) {
            s.scrollbar.disableDraggable();
        }
    }
    // Destroy loop
    if (s.params.loop) {
        s.destroyLoop();
    }
    // Cleanup styles
    if (cleanupStyles) {
        s.cleanupStyles();
    }
    // Disconnect observer
    s.disconnectObservers();
    // Disable keyboard/mousewheel
    if (s.params.keyboardControl) {
        if (s.disableKeyboardControl) s.disableKeyboardControl();
    }
    if (s.params.mousewheelControl) {
        if (s.disableMousewheelControl) s.disableMousewheelControl();
    }
    // Disable a11y
    if (s.params.a11y && s.a11y) s.a11y.destroy();
    // Destroy callback
    s.emit('onDestroy');
    // Delete instance
    if (deleteInstance !== false) s = null;
};

s.init();
;

    // Return swiper instance
    return s;
};
;
/*==================================================
    Prototype
====================================================*/
Swiper.prototype = {
    isSafari: (function () {
        var ua = navigator.userAgent.toLowerCase();
        return (ua.indexOf('safari') >= 0 && ua.indexOf('chrome') < 0 && ua.indexOf('android') < 0);
    })(),
    isUiWebView: /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(navigator.userAgent),
    isArray: function (arr) {
        return Object.prototype.toString.apply(arr) === '[object Array]';
    },
    /*==================================================
    Browser
    ====================================================*/
    browser: {
        ie: window.navigator.pointerEnabled || window.navigator.msPointerEnabled,
        ieTouch: (window.navigator.msPointerEnabled && window.navigator.msMaxTouchPoints > 1) || (window.navigator.pointerEnabled && window.navigator.maxTouchPoints > 1)
    },
    /*==================================================
    Devices
    ====================================================*/
    device: (function () {
        var ua = navigator.userAgent;
        var android = ua.match(/(Android);?[\s\/]+([\d.]+)?/);
        var ipad = ua.match(/(iPad).*OS\s([\d_]+)/);
        var ipod = ua.match(/(iPod)(.*OS\s([\d_]+))?/);
        var iphone = !ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/);
        return {
            ios: ipad || iphone || ipod,
            android: android
        };
    })(),
    /*==================================================
    Feature Detection
    ====================================================*/
    support: {
        touch : (window.Modernizr && Modernizr.touch === true) || (function () {
            return !!(('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch);
        })(),

        transforms3d : (window.Modernizr && Modernizr.csstransforms3d === true) || (function () {
            var div = document.createElement('div').style;
            return ('webkitPerspective' in div || 'MozPerspective' in div || 'OPerspective' in div || 'MsPerspective' in div || 'perspective' in div);
        })(),

        flexbox: (function () {
            var div = document.createElement('div').style;
            var styles = ('alignItems webkitAlignItems webkitBoxAlign msFlexAlign mozBoxAlign webkitFlexDirection msFlexDirection mozBoxDirection mozBoxOrient webkitBoxDirection webkitBoxOrient').split(' ');
            for (var i = 0; i < styles.length; i++) {
                if (styles[i] in div) return true;
            }
        })(),

        observer: (function () {
            return ('MutationObserver' in window || 'WebkitMutationObserver' in window);
        })()
    },
    /*==================================================
    Plugins
    ====================================================*/
    plugins: {}
};
;
/*===========================
 Get Dom libraries
 ===========================*/
var swiperDomPlugins = ['jQuery', 'Zepto', 'Dom7'];
for (var i = 0; i < swiperDomPlugins.length; i++) {
	if (window[swiperDomPlugins[i]]) {
		addLibraryPlugin(window[swiperDomPlugins[i]]);
	}
}
// Required DOM Plugins
var domLib;
if (typeof Dom7 === 'undefined') {
	domLib = window.Dom7 || window.Zepto || window.jQuery;
}
else {
	domLib = Dom7;
};
/*===========================
Add .swiper plugin from Dom libraries
===========================*/
function addLibraryPlugin(lib) {
    lib.fn.swiper = function (params) {
        var firstInstance;
        lib(this).each(function () {
            var s = new Swiper(this, params);
            if (!firstInstance) firstInstance = s;
        });
        return firstInstance;
    };
}

if (domLib) {
    if (!('transitionEnd' in domLib.fn)) {
        domLib.fn.transitionEnd = function (callback) {
            var events = ['webkitTransitionEnd', 'transitionend', 'oTransitionEnd', 'MSTransitionEnd', 'msTransitionEnd'],
                i, j, dom = this;
            function fireCallBack(e) {
                /*jshint validthis:true */
                if (e.target !== this) return;
                callback.call(this, e);
                for (i = 0; i < events.length; i++) {
                    dom.off(events[i], fireCallBack);
                }
            }
            if (callback) {
                for (i = 0; i < events.length; i++) {
                    dom.on(events[i], fireCallBack);
                }
            }
            return this;
        };
    }
    if (!('transform' in domLib.fn)) {
        domLib.fn.transform = function (transform) {
            for (var i = 0; i < this.length; i++) {
                var elStyle = this[i].style;
                elStyle.webkitTransform = elStyle.MsTransform = elStyle.msTransform = elStyle.MozTransform = elStyle.OTransform = elStyle.transform = transform;
            }
            return this;
        };
    }
    if (!('transition' in domLib.fn)) {
        domLib.fn.transition = function (duration) {
            if (typeof duration !== 'string') {
                duration = duration + 'ms';
            }
            for (var i = 0; i < this.length; i++) {
                var elStyle = this[i].style;
                elStyle.webkitTransitionDuration = elStyle.MsTransitionDuration = elStyle.msTransitionDuration = elStyle.MozTransitionDuration = elStyle.OTransitionDuration = elStyle.transitionDuration = duration;
            }
            return this;
        };
    }
};
    window.Swiper = Swiper;
})();