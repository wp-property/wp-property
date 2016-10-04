module.exports = {

  // curl -H 'host:localhost' http://localhost:3000/ -I
  'WordPress is up.': function() {

    
  },

  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 user create andy@udx.io andy@udx.io --role=administrator --user_pass=jgnqaobleiubnmcx
  'can create user via wp-cli': function() {


  },
  
  
  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update home "http://${CIRCLE_SHA1}-${CIRCLE_BUILD_NUM}.ngrok.io"
  'can update home url': function() {


  },
  
  
  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 option update siteurl "http://${CIRCLE_SHA1}-${CIRCLE_BUILD_NUM}.ngrok.io"
  'can update site url': function() {


  },
  
  
  // /home/ubuntu/wp-property-tests/bin/wp --path=/home/ubuntu/www --url=localhost:3000 plugin activate wp-property
  'can activate wpp': function() {


  },
  
  // curl -H 'host:localhost' http://localhost:3000/?ci-test=one
  'test mu was added': function() {


  },
  

};

