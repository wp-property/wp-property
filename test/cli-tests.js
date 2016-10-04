/**
 *
 *
 */
module.exports = {

  // curl -H 'host:localhost' http://localhost:3000/ -I
  'WordPress is discoverable.': function(  ) {


    // /home/ubuntu/www/wp-config.php
    //WP.discover({});

  }

};

var exec = require( 'child_process' ).exec;
var request = require( 'requestretry' );
