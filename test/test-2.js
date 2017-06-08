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

  'Site operational after wp-property activation, can open wpp_export_properties.': function( done ) {

    // I realize this isn't setting the bar very high, but its a start.

    var _params = {
      followRedirect: false,
      json: true,
      timeout: 2000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: 'http://' + module.host + ':3000/wp-admin/admin-ajax.php?action=wpp_export_properties&api=test'
    };
    request.get( _params , function checkResponse( error, resp, body ) {

      // @note just returns "null"

      //console.log( require( 'util' ).inspect( _params.url, { showHidden: false, depth: 2, colors: true } ) );
      //console.log( require( 'util' ).inspect( body, { showHidden: false, depth: 2, colors: true } ) );
      //console.log( require( 'util' ).inspect( resp.headers, { showHidden: false, depth: 2, colors: true } ) );

      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      if( !resp || ( resp.statusCode !== 200 && resp.statusCode !== 302 ) ) {
        return done( new Error( 'Unexpected response code post-wp-property activation.' ) );
      }

      done();

    });

  }


};

var exec = require( 'child_process' ).exec;
var request = require( 'requestretry' );
