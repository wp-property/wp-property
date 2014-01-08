/**
 * FEPS Submit/Edit step
 *
 * @author peshkov@UD
 */

if( typeof wpp.format_currency === 'function' ) {
  wpp.format_currency( '.wpp_feps_input_wrapper input.wpp_currency' );
}

if( typeof wpp.format_number === 'function' ) {
  wpp.format_number( '.wpp_feps_input_wrapper input.wpp_numeric' );
}

if( typeof wpp.init_feps_form !== 'function' ) {

  wpp.init_feps_form = function( this_form ) {

    if( typeof this_form !== 'object' ) {
      this_form = jQuery( this_form );
    }

    if( !this_form.length ) {
      return false;
    }

    var submit_button = jQuery( 'input[type="submit"]', this_form );

    /* */
    this_form.validate({
      submitHandler: function( form ){
        submit_button.attr( 'disabled', 'disabled' );
        wpp_feps_lookup_email( form );
      },
      errorPlacement: function(error, element) {
        return;
        var wrapper = element.parents(".wpp_feps_row_wrapper");
        var description_wrapper = jQuery(".wpp_feps_description_wrapper", wrapper);
        description_wrapper.prepend(error);
      },
      errorElement: false,
      errorClass: "wpp_feps_input_error",
      rules: {
        'wpp_feps_data[user_email]':{
          required: true,
          email: true
        }
      }
    });

    /* */
    function submit_form() {
      if( jQuery( ".wpp_feps_submit_form", this_form ).attr( "wpp_feps_disabled" ) == "true" ) {
        submit_button.removeAttr( 'disabled' );
        return false;
      }

      this_form.prev().hide().removeClass( 'error' );

      var data = this_form.serialize();
      jQuery.post( wpp.instance.ajax_url, {
        action: "wpp_feps_save_property",
        data: data
      }, function( response ) {
        if( response.success ) {
          if( !response.credentials_verified || !response.callback ) {
            this_form.prev().html( response.message ).show();
            this_form.remove();
          } else {
            if( response.callback ) {
              window.location.href = response.callback;
            } else {
              this_form.prev().html( response.message ).show();
              this_form.remove();
            }
          }
        } else {
          this_form.prev().addClass( 'error' );
          this_form.prev().html( response.message ).show();
        }
      }, "json");

    }

    /* */
    function wpp_feps_lookup_email() {

      var user_email = jQuery( ".wpp_feps_user_email", this_form ).val();
      var user_password = jQuery( ".wpp_feps_user_password", this_form ).val();

      if ( typeof this_form.valid == 'function' && !this_form.valid() ) {
        submit_button.removeAttr( 'disabled' );
        return false;
      }

      if ( wpp.instance.user_logged_in === 'true' ) {
        submit_form( 0 );
        //jQuery( 'input.wpp_feps_submit_form', this_form ).hide();
        return false;
      }

      if( user_email == "" ) {
        jQuery( ".wpp_feps_ajax_message", this_form ).text( wpp.strings.type_email );
        jQuery( ".wpp_feps_user_email", this_form ).focus();
        submit_button.removeAttr( 'disabled' );
        return false;
      }

      /* Disable submit button while checking e-mail */
      jQuery( ".wpp_feps_submit_form", this_form ).attr( "wpp_feps_disabled", true );
      jQuery( ".wpp_feps_submit_form", this_form ).attr( "wpp_feps_processing", true );

      if( user_password == "" ) {
        jQuery(".wpp_feps_ajax_message", this_form).text( wpp.strings.checking_account );
        jQuery(".wpp_feps_row_wrapper.user_password", this_form).hide();
      } else {
        jQuery(".wpp_feps_ajax_message", this_form).text( wpp.strings.checking_credentials );
      }

      jQuery.post( wpp.instance.ajax_url, {
        action: "wpp_feps_email_lookup",
        user_email: user_email,
        user_password: user_password
      }, function( response ) {

        jQuery( ".wpp_feps_submit_form", this_form ).attr( "wpp_feps_processing", false );

        if(response.email_exists == 'true') {
          if(response.credentials_verified == "true") {
            /* Email exists AND user credentials were verified */
            jQuery(".wpp_feps_ajax_message", this_form).text( wpp.strings.credentials_verified );
            jQuery(".wpp_feps_row_wrapper.user_password", this_form).show(); /* In case it was hidden but prefilled by auto-complete in browser */
            jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled", false);
            submit_form();
            //jQuery('input.wpp_feps_submit_form', this_form ).hide();
          } else if( response.invalid_credentials == "true" ) {
            /* Login failed. */
            jQuery(".wpp_feps_ajax_message", this_form ).text( wpp.strings.credentials_incorrect );
            submit_button.removeAttr( 'disabled' );
          } else {
            /* Email Exists, still need to check password. */
            jQuery(".wpp_feps_row_wrapper.user_password", this_form).show();
            jQuery(".wpp_feps_ajax_message", this_form ).text( wpp.strings.account_found_type_password );
            submit_button.removeAttr( 'disabled' );
          }
        } else {
          /* New Account */
          jQuery(".wpp_feps_row_wrapper.user_password", this_form ).hide();
          jQuery(".wpp_feps_ajax_message", this_form ).text( wpp.strings.account_created_check_email );
          jQuery(".wpp_feps_submit_form", this_form ).attr( "wpp_feps_disabled", false );
          submit_form();
        }

      }, "json");
    }

  }

}