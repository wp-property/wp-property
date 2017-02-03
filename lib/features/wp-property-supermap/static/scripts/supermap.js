(function($){

  /**
   * Supermap
   *
   * @param options
   */
  $.fn.wpp_supermap = function(options) {

    var instance = this;

    /** Our Main container */
    var el = $(this);

    /** Making variables public */
    var vars = $.extend({
      // Configuration!
    }, options);

    /** Events Handler */
    var supermap = instance.supermap = {

      init: function() {

      }

    }

    instance.init();

    return instance;
  };

})(jQuery);
