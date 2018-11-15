/**
 * WordPress Posts
 *
 */
define( 'udx.ui.wp.posts', [ 'udx.utility' ], function wpPosts() {
  console.debug( 'udx.ui.wp.posts', 'loaded' );

  return function domnReady() {
    console.debug( 'udx.ui.wp.posts', 'ready' );

    return this;

  };

});

