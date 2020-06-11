(function($)
{
  $.fn.loadingdots = function( options )
  {
    var i = 0, settings = $.extend( {}, { duration : 250 }, options ),

    bucle = function() {
      var $el = $( this ), cycle, timing = i * settings.duration, first = true;
      i++;

      cycle = function()
      {
        // if it's not the first time the cycle is called for a dot then the timing fired is 0
        if ( !first )
          timing = 0;
        else
          first = false;
        // delay the animation the timing needed, and then make the animation to fadeIn and Out the dot to make the effect
        $el.delay( timing )
          .fadeTo( 1000, 0.4 )
          .fadeTo( 1000, 0, cycle );
      };

      cycle( first );
    };
    // for every element where the plugin was called we create the loading dots html and start the animations
    return this.each( function()
    {
      $( this )
        .html( '<span class="dot"></span><span class="dot"></span><span class="dot"></span>' )
        .find( '.dot' )
        .each( bucle );
    });

  };
})(jQuery);