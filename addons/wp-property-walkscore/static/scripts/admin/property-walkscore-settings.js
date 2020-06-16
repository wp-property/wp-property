/**
 * Conferences Shortcode
 *
 */

!function( $, s ) {

  var logs, btn, close_btn, spinner;

  function output_log( value ) {
    $( 'ul', logs).append( '<li>' + value + '</li>' );
  }

  /**
   * Finish process
   */
  function end_process() {
    btn.prop( 'disabled', false);
    spinner.hide();
    close_btn.show();
  }

  /**
   * Runs process for getting all missed Walk Scores
   */
  function run_process() {
    logs.html( '<ul></ul>').toggle();
    spinner.show();
    $.ajax({
      type: "POST",
      url: s.admin_ajax,
      data: {
        'action': 'wpp_ws_get_properties_ids'
      },
      dataType: "json",
      cache: !1,
      complete: function(r) {
        var d = r.responseJSON;
        if( typeof d !== 'object' ) {
          output_log( s.error_occurred );
          end_process();
        } else if( !d.success ) {
          output_log( d.data );
          end_process();
        } else if( typeof d.data.ids !== 'object' ) {
          output_log( s.error_occurred );
          end_process();
        } else {
          output_log( s.got_ids );
          for( var i in d.data.ids ) {
            if( typeof d.data.ids[i] == 'object' ) {
              get_walkscore( d.data.ids[i] );
            }
          }
          output_log( s.done );
          end_process();
        }
      }
    });
  }

  /**
   *
   */
  function get_walkscore( data ) {
    data.action = 'wpp_ws_update_walkscore';
    $.ajax({
      type: "POST",
      url: s.admin_ajax,
      data: data,
      dataType: "json",
      cache: !1,
      async: !1,
      complete: function(r) {
        var d = r.responseJSON;
        if( typeof d !== 'object' ) {
          output_log( s.error_occurred );
          end_process();
        } else {
          output_log( d.data );
        }
      }
    });
  }

  $(document).ready( function() {

    /**
     * Init DOM elements
     */
    logs = $('#ws-response-logs');
    if( !logs.length > 0 ) {
      return;
    }
    btn = $( '#ws-bulk-request-button');
    if( !btn.length > 0 ) {
      return;
    }
    close_btn = $( '#ws-close-log-button');
    if( !close_btn.length > 0 ) {
      return;
    }
    spinner = $( '#ws-ajax-spinner');
    if( !spinner.length > 0 ) {
      return;
    }

    /**
     * Run Process Button
     */
    btn.click( function(event){
      event.preventDefault();
      $( this ).prop( 'disabled', true);
      run_process();
    } );

    /**
     * Run Process Button
     */
    close_btn.click( function(event){
      event.preventDefault();
      $( this).hide();
      logs.hide();
    } );

  } );



}( jQuery, _walkscore_settings );