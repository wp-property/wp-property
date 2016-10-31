module.exports = {

  'tunnel': function( done ) {

    this.timeout = 10000;

    var options = {
      proto: 'http',
      addr: 3000,
      subdomain: [ (process.env.CIRCLE_SHA1 || 'wp-property').substring( 0, 8 ), process.env.CIRCLE_BUILD_NUM || '1' ].join( '-' ),
      authtoken: process.env.NGROK_TOKEN
    };

    ngrok.connect(options, function (error, url) {

      if( error ) {
        return done( error );
      }

      console.log( "Up on [%s].", url );

      setTimeout(done, 3000 );

    });


  },

  after:function() {
    ngrok.disconnect();
  }
};

var ngrok = require( 'ngrok' );