( function( jQuery ){

  /**
   *
   * @param options
   */
  $.fn.wpp_pagination_slider = function(options) {

    var instance = this;

    /** Our Main container */
    var el = $(this);

    /** Making variables public */
    var vars = $.extend({
      'unique_id': false,
      'use_pagination': true,
      'pages': null
    }, options);

    if( !vars.unique_id ) {
      return;
    }



  };

} )( jQuery );