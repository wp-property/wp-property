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

  // curl -H 'host:localhost' http://localhost:3000/ -I
  'WordPress readme.html is reachable.': function( done ) {
    //console.log( 'test one', 'http://' + module.host + ':3000/' );

    request.get( {
      followRedirect: false,
      timeout: 1000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: module.base_url + '/readme.html'
    } , function checkResponse( error, resp, body ) {

      if( error ) {
        done( new Error( 'Can not reach WordPress at [' + module.base_url + '/readme.html].' ) );
      }
      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      // console.log( 'resp.statusCode', resp.statusCode );

      if( resp.statusCode === 301 ) {
        console.log( "Most likely first time tests are being ran and site is trying to redirect to its default siteurl." );
      } else if( resp.statusCode === 200 ) {
        // console.log( "No redirection, our custom siteurl/home have already been set." );
      } else {
        console.log( "Unexpected status code!", resp.statusCode );
        return done( new Error( 'Unexpected status code.' ) );
      }

      done();

    });

  },

  'WordPress wp-admin/admin-ajax.php is reachable.': function( done ) {
    //console.log( 'test one', 'http://' + module.host + ':3000/' );

    request.get( {
      followRedirect: false,
      timeout: 1000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: module.base_url + '/wp-admin/admin-ajax.php'
    } , function checkResponse( error, resp, body ) {

      if( error ) {
        done( new Error( 'Can not reach WordPress at [' + module.base_url + '/wp-admin/admin-ajax.php].' ) );
      }
      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      // console.log( 'resp.statusCode', resp.statusCode );

      if( resp.statusCode === 301 ) {
        console.log( "Most likely first time tests are being ran and site is trying to redirect to its default siteurl." );
      } else if( resp.statusCode === 200 ) {
        // console.log( "No redirection, our custom siteurl/home have already been set." );
      } else {
        console.log( "Unexpected status code!", resp.statusCode );
        return done( new Error( 'Unexpected status code.' ) );
      }

      done();

    });

  },

  // alias wp="/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000"
  //
  // curl -H 'host:de25021ab429e0f95909a482778c89768cc5b65b-1071.ngrok.io' localhost:3000/
  //
  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 user create andy@udx.io andy@udx.io --role=administrator --user_pass=jgnqaobleiubnmcx
  'Can create user via wp-cli': function( done ) {

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

      if( stdout.indexOf( "Success:" ) === 0 ) {
        return done();
      }

      console.log( stdout );

      done();
    });

  },

  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update home "http://${CIRCLE_SHA1}-${CIRCLE_BUILD_NUM}.ngrok.io"
  'Can update home url': function( done ) {

    exec( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update home http://' + process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io', function( error, stdout, stderr ) {

      if( error ) {
        console.log( 'error', error );
      }

      if( stderr ) {
        console.log( 'stderr', stderr );
      }

      if( stdout.indexOf( "Success:" ) === 0 ) {
        return done();
      }

      console.log( stdout );

      done();
    });
  },

  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update siteurl "http://${CIRCLE_SHA1}-${CIRCLE_BUILD_NUM}.ngrok.io"
  'Can update site url': function( done ) {

    exec( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update siteurl http://' + process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io', function( error, stdout, stderr ) {

      if( error ) {
        console.log( 'error', error );
      }

      if( stderr ) {
        console.log( 'stderr', stderr );
      }

      if( stdout.indexOf( "Success:" ) === 0 ) {
        return done();
      }

      console.log( stdout );

      done();
    });

  },

  'Can activate twentyfifteen theme': function( done ) {

    exec( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 theme activate twentyfifteen', function( error, stdout, stderr ) {

      if( error ) {
        console.log( 'error', error );
      }

      if( stderr ) {

        if( stderr.indexOf( "theme is already active" ) > 0 ) {
          return done();
        }

        console.log( 'stderr', stderr );
      }

      if( stdout.indexOf( "Success:" ) === 0 ) {
        return done();
      }

      console.log( stdout );

      done();

    });

  },

  // sudo -u www-data /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install wp-property
  // sudo -u www-data /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install wp-property
  'Can download and activate wp-property using the sha version ': function( done ) {

    //console.log( '/home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install ' + module.downloadUrl + ' --activate --quiet' );
    exec( 'sudo -u www-data /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin install ' + module.downloadUrl + ' --force --activate', function( error, stdout, stderr ) {

      if( error ) {
        console.log( 'error', error );
        return done( error );
      }

      if( stderr ) {

        if( stderr.indexOf( "is already active." ) > 0 ) {
          return done();
        }

        console.log( 'stderr', stderr );

        return done( new Error( stderr ) );
      }

      if( stdout ) {

        if( stdout.indexOf( "Success: Plugin" ) > 0 ) {
          return done();
        }

        console.log( 'stdout', stdout );
      }

      done();
    });

  },

  'HTTP request can get [wp-property/composer.json].': function( done ) {
    //console.log( 'test one', 'http://' + module.host + ':3000/' );

    request.get( {
      followRedirect: false,
      timeout: 1000,
      headers: {
        host: process.env.CIRCLE_SHA1 + '-' + process.env.CIRCLE_BUILD_NUM + '.ngrok.io'
      },
      url: module.base_url + '/wp-content/plugins/wp-property-' + process.env.CIRCLE_SHA1 + '/composer.json'
    } , function checkResponse( error, resp, body ) {

      if( error ) {
        done( new Error( 'Can not reach WordPress at [' + module.base_url + '/wp-admin/admin-ajax.php].' ) );
      }
      // console.log( require( 'util' ).inspect( resp.headers, {showHidden: false, depth: 2, colors: true} ) );
      // console.log( 'resp.statusCode', resp.statusCode );

      if( resp.statusCode === 301 ) {
        console.log( "Most likely first time tests are being ran and site is trying to redirect to its default siteurl." );
      } else if( resp.statusCode === 200 ) {
        // console.log( "No redirection, our custom siteurl/home have already been set." );
      } else {
        console.log( "Unexpected status code!", resp.statusCode );
        return done( new Error( 'Unexpected status code.' ) );
      }

      done();

    });

    //done();

  }

};

var exec = require( 'child_process' ).exec;
var request = require( 'requestretry' );
