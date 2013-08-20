/**
 * Mocha Test for WordPress Property API
 *
 * To use, install Node.js and the Node.js Mocha module, Node.js Zombie module and Node.js WordPress module - and run via command line.
 *
 * Node.js - http://nodejs.org/
 * Mocha - https://github.com/visionmedia/mocha
 * Zombie - http://zombie.labnotes.org/
 * Node WordPress - https://github.com/scottgonzalez/node-wordpress/
 *
 * $ mocha test/api.js --reporter list --ui exports --watch
 *
 * @author potanin@UD
 * @date 8/17/13
 * @type {Object}
 */
module.exports = {

  /**
   * Prepare Environment
   *
   */
  before: function before ( done ) {

    try {

      this.zombie = require( 'zombie' );
      this.assert = require( 'assert' );
      this.wordpress = require( 'wordpress' );

      this.browser = new this.zombie;

    } catch ( error ) {
      done( new Error( 'Could not setup testing environment, make sure all required Node.js modules are installed. Message: ' + error.message ) )
    }

    // All modules exist.
    done();

  },

  'WP-Property': {

    /**
     * -
     *
     */
    'can access development environment.': function ( done ) {
      var browser = this.browser;

      this.timeout( 5000 );

      browser.visit( "http://wordpress.dev/wp-admin", function () {
        done();
      } );

    },

    'can login using test account.': function ( done ) {
      var browser = this.browser;
      var assert = this.assert;

      // Disabled for now.
      return done();

      // Fill out login form
      browser.fill( "log", "test" );
      browser.fill( "pwd", "test" ).pressButton( "Log In", function () {

        // Form submitted, new page loaded.
        assert.ok( browser.success );
        assert.equal( browser.text( "title" ), "Welcome To Brains Depot" );

        done();

      });


    }


  }

}