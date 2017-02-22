/**
 * Swiper
 *
 * @todo Handle show-on-ready better.
 * @todo Right now the height must be set in CSS, otherwise will be arbitrary.
 *
 * @author potanin@UD
 *
 */
define( 'udx.ui.swiper', [ 'jquery', 'swiper' ], function( jQuery ) {
  console.debug( 'udx.ui.swiper', 'loaded' );

  require.loadStyle( '//cdn.udx.io/vendor/swiper.css' );

  /**
   *
   */
  return function domnReady() {
    console.debug( 'udx.ui.swiper', 'ready' );

    if( 'function' !== typeof Swiper ) {
      console.error( 'udx.ui.swiper', 'Swiper object not available.' );
    }

    this.options = Object.extend( this.options, {
      initialSlide: 0,
      calculateHeight: true,
      mode: 'horizontal',
      slideActiveClass: 'swiper-slide-active',
      wrapperClass: 'swiper-wrapper',
      slideClass: 'swiper-slide'
    });

    this.swiper = new Swiper( this, {
      mode: this.options.mode,
      loop: this.options.loop,
      wrapperClass: this.options.wrapperClass,
      slideClass: this.options.slideClass,
      slideActiveClass: this.options.slideActiveClass,
      initialSlide: this.options.initialSlide,
      cssWidthAndHeight: this.options.cssWidthAndHeight,
      calculateHeight: this.options.calculateHeight,
      autoplay: this.options.autoplay,
      speed: this.options.speed,
      onInit : function onInit() {
        // console.debug( 'udx.ui.swiper', 'swiper initialied' );
        // _wrapper.css( 'opacity', 1 );

      }.bind( this ),
      onTouchStart : function onTouchStart() {
        //console.debug( 'udx.ui.swiper', 'swiper touch detected' );
      }.bind( this )
    });

    return this;

  };

});

