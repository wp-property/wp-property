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

    module.downloadUrl = process.env.CIRCLE_REPOSITORY_URL + '/archive/' + process.env.CIRCLE_SHA1 + '.zip';

    done();

  },

  // curl -H 'host:localhost' http://localhost:3000/ -I
  'WordPress is reachable.': function( done ) {
    // console.log( 'test one', 'http://' + module.host + ':3000/' );

    request.get( {
      followRedirect: false,
      timeout: 2000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: 'http://' + module.host + ':3000/'
    } , function checkResponse( error, resp, body ) {

      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      // console.log( 'resp.statusCode', resp.statusCode );

      if( resp.statusCode === 301 ) {
        console.log( "Most likely first time tests are being ran and site is trying to redirect to its default siteurl." );
      } else if( resp.statusCode === 200 ) {
        console.log( "No redirection, our custom siteurl/home have already been set." );
      } else {
        console.log( "Unexpected status code!", resp.statusCode );
        return done( new Error( 'Unexpected status code.' ) );
      }

      done();

    });

    //done();

  },

  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 user create andy@udx.io andy@udx.io --role=administrator --user_pass=jgnqaobleiubnmcx
  'can create user via wp-cli': function( done ) {

    exec( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 user create andy@udx.io andy@udx.io --role=administrator --user_pass=jgnqaobleiubnmcx', function( error, stdout, stderr ) {

      if( error && error.message ) {
        // this is okay when tests are ran multiple times...
        if( error.message.indexOf( 'username is already registered' ) > 0 ) {
          return done();
        }
        console.log( 'error', error );
      }

      if( stderr ) {
        console.log( 'stderr', stderr );
      }

      console.log( stdout );

      done();
    });

  },

  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update home "http://${CIRCLE_SHA1}-${CIRCLE_BUILD_NUM}.ngrok.io"
  'can update home url': function( done ) {

    exec( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update home http://' + process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io', function( error, stdout, stderr ) {

      if( error ) {
        console.log( 'error', error );
      }

      if( stderr ) {
        console.log( 'stderr', stderr );
      }

      console.log( stdout );

      done();
    });
  },

  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update siteurl "http://${CIRCLE_SHA1}-${CIRCLE_BUILD_NUM}.ngrok.io"
  'can update site url': function( done ) {

    exec( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update siteurl http://' + process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io', function( error, stdout, stderr ) {

      if( error ) {
        console.log( 'error', error );
      }

      if( stderr ) {
        console.log( 'stderr', stderr );
      }

      console.log( stdout );

      done();
    });

  },

  // sudo -u www-data /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install wp-property
  // sudo -u www-data /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install wp-property
  'can download and activate wp-property using the sha version ': function( done ) {

    //console.log( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install ' + module.downloadUrl + ' --activate --quiet' );
    exec( 'sudo -u www-data /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install ' + module.downloadUrl + ' --activate --quiet', function( error, stdout, stderr ) {

      if( error ) {
        console.log( 'error', error );
        return done( error );
      }

      if( stderr ) {
        console.log( 'stderr', stderr );
        return done( new Error( stderr ) );
      }

      console.log( stdout );

      done();
    });

  },

  // curl -H 'host:localhost' http://localhost:3000/?ci-test=one
  'site operational after wp-proeprty activation': function( done ) {

    // I realize this isn't setting the bar very high, but its a start.

    request.get( {
      followRedirect: false,
      timeout: 2000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: 'http://' + module.host + ':3000/'
    } , function checkResponse( error, resp, body ) {

      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      if( resp.statusCode !== 200 ) {
        return new Error( 'Unexpected response code post-wp-property activation.' );
      }

      done();

    });

  },


};

var exec = require( 'child_process' ).exec;
var request = require( 'requestretry' );
