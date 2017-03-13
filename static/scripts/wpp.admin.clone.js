/**
 * Adds Post Clone Button.
 */

(function($, l10n){

  var clone = {

    disabled: false,

    /**
     * Constructor
     */
    init: function() {

      var html = '<a href="javascript:;" class="add-new-h2" id="wpp_clone_property_btn">' + l10n.clone_property + '</a>';
      $( '.wrap > h2').append( html );

      $('#wpp_clone_property_btn').click( function( e ){
        e.preventDefault();

        if( clone.disabled ) {
          return;
        }

        //clone.disabled = true;

        $.ajax({
          // /wp-admin/admin-ajax.php
          url: ajaxurl,
          type: 'POST',
          // Add action and nonce to our collected data
          data: {
            action: 'wpp_clone_property',
            post_id: $('#post_ID').val()
          },
          // Handle the successful result
          success: function( r ) {
            if( r.success ) {
              if( typeof r.data.redirect !== 'undefined' && r.data.redirect.length > 0 ) {
                window.location.replace( r.data.redirect );
              } else {
                alert( 'Post has been cloned.' );
              }
            } else {
              if( typeof r.data !== 'undefined' && r.data.lenght > 0 ) {
                alert(r.data );
              } else {
                alert( 'Something wrong happened. Please, try again later.' );
              }
            }
            clone.disdabled = false;
          }
        });

      } );

    }

  }

  $(document).ready(function(){
    clone.init();
  });

})(jQuery, wpp.strings);