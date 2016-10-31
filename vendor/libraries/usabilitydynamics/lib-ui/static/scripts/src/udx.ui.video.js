/**
 * Video Player
 *
 * ### Attributes
 *
 * * data-src
 * * data-debug
 * * data-loading
 *
 */
define( 'udx.ui.video', [ 'udx.utility.device' ], function video() {
  console.debug( 'udx.ui.video', 'loaded' );

  return function domnReady() {
    console.debug( 'udx.ui.video', 'ready' );

    // Device Detection.
    var Device = require( 'udx.utility.device' );

    var options = {
      src: this.getAttribute( 'data-tablet' ),
      tablet: this.getAttribute( 'data-tablet' ),
      desktop: this.getAttribute( 'data-desktop' ),
      mobile: this.getAttribute( 'data-mobile' ),
      placeholder: this.getAttribute( 'data-placeholder' ),
      loading: this.getAttribute( 'data-loading' )
    };

    var _player;

    console.debug( 'udx.ui.video', options );

    // MP4 Video
    if( options.src.indexOf( '.mp4' ) > 0 ) {
      console.debug( 'mp4 html5 video' );

      _player = document.createElement( 'video' );
      var _source = document.createElement( 'source' );

      // none - does not buffer the file
      // auto - buffers the media file
      // metadata - buffers only the metadata for the file
      _player.setAttribute( 'preload', 'metadata' );
      _player.setAttribute( 'style', 'max-width: 100%; height: auto' );
      // _player.setAttribute( 'controls' );

      // _player.setAttribute( 'autoplay', 'false' );
      // _player.setAttribute( 'autobuffer', 'true' );
      // _player.setAttribute( 'poster', 'true' );

      _source.setAttribute( 'src', options.src );
      _source.setAttribute( 'type', 'video/mp4' );

      _player.appendChild( _source );
      this.appendChild( _player );

      _player.addEventListener( 'playing', function() {
        console.log( 'playing', arguments );
      });

      _player.addEventListener( 'ended', function() {
        console.log( 'ended', arguments );
      });

      _player.addEventListener( 'progress', function() {
        console.log( 'progress', _player.currentTime );
      }, false );

      // _player.play()
      // _player.pause()

    }

    // YouTube Video
    if( options.src.indexOf( 'youtube.com' ) > 0 ) {
      console.debug( 'youtube video' );

      var _player = document.createElement( 'iframe' );
      _player.src = options.src;

      _player.setAttribute( 'frameborder', '0' );
      _player.setAttribute( 'allowfullscreen', 'true' );

      this.appendChild( _player );

    }

    return this;

  };

});

