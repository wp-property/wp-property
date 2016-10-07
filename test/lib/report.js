var path = require("path");
var fs = require("fs");
var intercept = require("intercept-stdout");

if( process.env.CIRCLE_ARTIFACTS ) {

  var file = path.normalize( process.env.CIRCLE_ARTIFACTS + '/report.txt');

  // console.log( file );

  var writer = fs.createWriteStream(file); // meow meow etc.

  intercept(function eachLog(txt) {

    writer.write( txt );

  });


}


//writer.pipe(process.stdout)

