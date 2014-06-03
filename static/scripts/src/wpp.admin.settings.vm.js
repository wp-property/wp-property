define( [ 'require', 'module', 'exports' ], function( require, module, exports ) {
  console.info( 'Loaded %s module.', module.id );

  // Facebook Like Box.
  (function( d, s, id ) {
    var js, fjs = d.getElementsByTagName( s )[0];
    if( d.getElementById( id ) ) return;
    js = d.createElement( s );
    js.id = id;
    js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=373515126019844";
    fjs.parentNode.insertBefore( js, fjs );
  }( document, 'script', 'facebook-jssdk' ));

});