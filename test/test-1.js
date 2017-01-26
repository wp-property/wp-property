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

    // I realize this isn't setting the bar very high, but its a start.

    request.get( {
      followRedirect: false,
      timeout: 2000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: 'http://' + module.host + ':3000/wp-admin/admin-ajax.php'
    } , function checkResponse( error, resp, body ) {

      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      if( !resp || resp.statusCode !== 200 ) {
        console.log( require( 'util' ).inspect( error, { showHidden: false, depth: 2, colors: true } ) );
        return new Error( 'Unexpected response code post-wp-property activation.' );
      }

      done();

    });

  },

  'Site operational after wp-property activation': function( done ) {

    // curl http://localhost:3000 -H "host:32a8fc16ef10d620577367157c214f4ae1b0275e-1057.ngrok.io"
    // curl http://0.0.0.0:3000 -H "host:32a8fc16ef10d620577367157c214f4ae1b0275e-1057.ngrok.io"

    var requestOptions = {
      followRedirect: false,
      timeout: 2000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: 'http://' + module.host + ':3000'
    };

    request.get( requestOptions, function checkResponse( error, resp, body ) {

      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      if( !resp || resp.statusCode !== 200 ) {
        console.log( require( 'util' ).inspect( error, { showHidden: false, depth: 2, colors: true } ) );
        return new Error( 'Unexpected response code post-wp-property activation.' );
      }

      done();

    });

  }

};

var exec = require( 'child_process' ).exec;
var request = require( 'requestretry' );
