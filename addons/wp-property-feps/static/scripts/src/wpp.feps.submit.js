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

    var submit_button = jQuery( 'button[type="submit"]', this_form );
    
    submit_button.find('.wpp-feps-loadingdots').loadingdots({ dots : 5 });

    /**
     * Flush email and password fields in case browser filled default values itself.
     */
    if( jQuery( "input.wpp_feps_user_email", this_form ).length > 0 ) {
      jQuery( "input.wpp_feps_user_email", this_form ).val('');
    }
    if( jQuery( "input.wpp_feps_user_password", this_form ).length > 0 ) {
      jQuery( "input.wpp_feps_user_password", this_form ).val('');
    }

    /* */
    this_form.validate({
      submitHandler: function( form ){
        submit_button.attr( 'disabled', 'disabled' ).addClass('disabled');
        wpp_feps_lookup_email( form );
      },
      errorPlacement: function(error, element) {
        var wrapper = element.parents(".wpp_feps_row_wrapper");
        var description_wrapper = jQuery(".wpp_feps_description_wrapper", wrapper);
        if(jQuery('.wpp_feps_input_error', description_wrapper).length == 0)
          description_wrapper.prepend(error);
      },
      highlight: function(element, errorClass, validClass) {
        element = jQuery(element);
        var wrapper = element.parents(".wpp_feps_row_wrapper");
        var description_wrapper = jQuery(".wpp_feps_description_wrapper", wrapper);
        jQuery(description_wrapper).find(".wpp_feps_input_error")
              .removeClass('success').addClass('error').html(element.attr('data-msg'))
              //.show();
      },
      unhighlight: function(element, errorClass, validClass) {
        element = jQuery(element);
        var wrapper = element.parents(".wpp_feps_row_wrapper");
        var description_wrapper = jQuery(".wpp_feps_description_wrapper", wrapper);
        jQuery(description_wrapper).find(".wpp_feps_input_error")
              .removeClass('error').addClass('success').html("Valid")
              //.hide(3000);
      },
      //errorElement: false,
      errorClass: "wpp_feps_input_error error",
      rules: {
        'wpp_feps_data[user_email]':{
          required: true,
          email: true,
          messages: {
            required: "Email is required.",
          }
        }
      },
    });
    jQuery.each(fepsRequiredItems, function(i, item){
      var _this = jQuery('[name="' + item + '"]');
      var msg = "Required input.";
      if(_this.length == 0) return true;
      _this.attr('data-msg', msg);
      _this.rules( "add", {
        required: true,
        messages: {
          required: msg,
        }
      });
    });
    
    jQuery.validator.addMethod("requiredTaxonomy", function(value, element) {
      element = jQuery(element);
      var wrapper = element.parents(".wpp_feps_input_wrapper");
      if(wrapper.find('.tagchecklist').children().length>0)
        return true;
      return false;
    });
    
    jQuery.each(fepsRequiredTaxonomy, function(i, taxSlug){
      var _this = jQuery("#wpp-feps-tax-" + taxSlug);
      
      if(_this.length == 0) return true;

      var msg = "Atleast one tag is needed.";
      var newtag = _this.parents(".wpp_feps_row_wrapper").find('.newtag');

      _this.attr('data-msg', msg);
      newtag.attr('data-msg', msg);

      newtag.on('tax-added tax-removed', jQuery.proxy(_this.valid, _this));

      _this.rules( "add", {
        requiredTaxonomy: true,
        messages: {
          requiredTaxonomy: msg,
        }
      });
    });


    /* */
    function submit_form() {
      if( jQuery( ".wpp_feps_submit_form", this_form ).attr( "wpp_feps_disabled" ) == "true" ) {
        submit_button.removeAttr( 'disabled' ).removeClass( 'disabled' );
        return false;
      }

      this_form.prev().hide().removeClass( 'error' );
      if(typeof tinyMCE != "undefined" && typeof tinyMCE.triggerSave != "undefined")
        tinyMCE.triggerSave(); // unless text editor value will not get saved.
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
        submit_button.removeAttr( 'disabled' ).removeClass( 'disabled' );
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
        submit_button.removeAttr( 'disabled' ).removeClass( 'disabled' );
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
            submit_button.removeAttr( 'disabled' ).removeClass( 'disabled' );
          } else {
            /* Email Exists, still need to check password. */
            jQuery(".wpp_feps_row_wrapper.user_password", this_form).show();
            jQuery(".wpp_feps_ajax_message", this_form ).text( wpp.strings.account_found_type_password );
            submit_button.removeAttr( 'disabled' ).removeClass( 'disabled' );
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