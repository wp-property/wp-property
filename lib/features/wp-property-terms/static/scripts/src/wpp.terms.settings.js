(function($){

  var wppt = {

    /**
     *
     */
    init: function() {

      $(document).ready(function(){

        $( '.wpp-terms-type-selector' ).each( function(i,e){
          wppt.update_type_desc( $(e) );
        } );

        $( '.wpp-terms-type-selector').on( 'change', function(){
          wppt.update_type_desc( $(this) );
        } );

      });

    },

    /**
     *
     */
    update_type_desc: function( e ) {
      var container = e.parent(),
          desc = container.find( '.wpp-terms-type-desc');
      if( !desc.length > 0 ) {
        container.append( '<div class="wpp-terms-type-desc"></div>' );
        desc = container.find( '.wpp-terms-type-desc');
      }
      desc.html(e.find('option:selected').data('desc'));
    }

  }

  wppt.init();

})(jQuery);