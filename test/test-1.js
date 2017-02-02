/**
 *
 *
 */
module.exports = {

  before: function ( done ) {

    if( !process.env.CIRCLE_SHA1 ) {
      return done( new Error( "These tests are designed for CircleCI. Sorry. ") );
    }

    module.host = 'localhost';
    module.base_url = 'http://' + module.host + ':3000';

    module.downloadUrl = process.env.CIRCLE_REPOSITORY_URL + '/archive/' + process.env.CIRCLE_SHA1 + '.zip';

    done();

  },

  // curl -H 'host:localhost' http://localhost:3000/?ci-test=one
  'Site admin-ajax.php operational after wp-property activation.': function( done ) {

    var requestOptions = {
      followRedirect: false,
      timeout: 5000,
      headers: {
        "host": process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io',
        'Accept':"text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
        'Accept-Language':"en-US,en;q=0.8",
        'Cache-Control':"max-age=0",
        "User-Agent":"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36"
      },
      url: 'http://' + module.host + ':3000/wp-admin/admin-ajax.php'
    };

    request.get( requestOptions , function checkResponse( error, resp, body ) {


      if( error && error.code && error.code === 'ESOCKETTIMEDOUT' ) {
        return done( new Error( 'Socker timeout to ' + requestOptions.url ) );
      }


      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      if( !resp || resp.statusCode !== 200 ) {
        console.log( require( 'util' ).inspect( error, { showHidden: false, depth: 2, colors: true } ) );
        return done( new Error( 'Unexpected response code post-wp-property activation.' ) );
      }

      done();

    });

  },

  'Site operational after wp-property activation, can open homepage.': function( done ) {

    // curl http://localhost:3000 -H "host:32a8fc16ef10d620577367157c214f4ae1b0275e-1057.ngrok.io"
    // curl http://0.0.0.0:3000 -H "host:32a8fc16ef10d620577367157c214f4ae1b0275e-1057.ngrok.io"

    var requestOptions = {
      followRedirect: true,
      timeout: 5000,
      headers: {
        "host": process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io',
        'Accept':"text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
        'Accept-Language':"en-US,en;q=0.8",
        'Cache-Control':"max-age=0",
        "User-Agent":"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36"
      },
      url: 'http://' + module.host + ':3000'
    };

    request.get( requestOptions, function checkResponse( error, resp, body ) {

      if( error && error.code && error.code === 'ESOCKETTIMEDOUT' ) {
        return done( new Error( 'Socker timeout to ' + requestOptions.url ) );
      }

      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      if( !resp || resp.statusCode !== 200 ) {
        console.log( require( 'util' ).inspect( error, { showHidden: false, depth: 2, colors: true } ) );
        return done( new Error( 'Unexpected response code post-wp-property activation.' ) );
      }

      done();

    });

  }

};

var exec = require( 'child_process' ).exec;
var request = require( 'request' );
