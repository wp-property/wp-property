<?php
/**
 * Default FEPS form template
 *
 * Copy this file into your theme directory to customize it.
 *
 * @version 0.1
 * @author korotkov@UD
 * @package WP-Property
 *
 */

global $wpp_asset;

ob_start();

?>

<script type="text/javascript">

  user_logged_in = <?php echo $user_logged_in ? 'true' : 'false'; ?>;

  jQuery(document).ready(function() {

    if( typeof wpp === 'object' && typeof wpp.format_currency === 'function' ) {
      wpp.format_currency( '.wpp_feps_input_wrapper input.wpp_currency' );
    }

    if( typeof wpp === 'object' && typeof wpp.format_number === 'function' ) {
      wpp.format_number( '.wpp_feps_input_wrapper input.wpp_numeric' );
    }

  });
</script>
<?php $output_js = ob_get_contents();
ob_end_clean();
echo $output_js; ?>
<style type="text/css">#wpp_feps_form_<?php echo $form_id; ?> .wpp_feps_preview_thumb,#wpp_feps_form_<?php echo $form_id; ?> .wpp_feps_existing_thumb {max-width: <?php echo $thumbnail_size['width']; ?>px !important;cursor:pointer; }</style>

<?php if ($args['show_error_message']) { ?>
  <div class="<?php wpp_css("feps-default-template::error_message","wpp_feps_message error"); ?>"><?php echo $args['show_error_message']; ?></div>
<?php } ?>

<form action="" method="post" enctype="multipart/form-data" id="wpp_feps_form_<?php echo $form_id; ?>" class="<?php wpp_css("feps-default-template::form","wpp_feps_form"); ?>">
  <input type="hidden" name="wpp_front_end_action" value="wpp_submit_feps" />
  <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
  <input type="hidden" name="wpp_feps_data[form_id]" value="<?php echo esc_attr(md5($args['form_id'])); ?>" />
  <input type="hidden" name="wpp_feps_data[this_session]" value="<?php echo esc_attr($this_session); ?>" />

  <?php if ($parent_id) { ?>
    <input type="hidden" name="wpp_feps_data[parent_id]" value="<?php echo esc_attr($args['parent_id']); ?>" />
  <?php } ?>
  <?php if (!empty($property['ID'])) { ?>
    <input type="hidden" name="wpp_feps_data[post]" value="<?php echo esc_attr($property['ID']); ?>" />
  <?php } ?>

  <ul class="<?php wpp_css("feps-default-template::fields-wrapper","feps_property_input_fields"); ?>">
    <?php
    $tabindex = 100;

    foreach ( (array)$form_fields as $row_id => $att_data ) {
      $tabindex++;

      $att_data['tabindex'] = $tabindex;
      ?>
      <li class="<?php wpp_css("feps-default-template::row-wrapper", array("wpp_feps_row_wrapper",$att_data['ui_class'],($att_data['required'] == 'on' ? 'required' : ''))); ?>">
        <div class="<?php wpp_css("feps-default-template::label-wrapper","wpp_feps_label_wrapper"); ?>">
          <label for="wpp_<?php echo $row_id; ?>_input"><span class="<?php wpp_css("feps-default-template::the_title","the_title"); ?>"><?php echo $att_data['title']; ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
            <?php
            echo apply_filters('wpp_feps_input', array(
                'this_session' => $this_session,
                'form_dom_id' => 'wpp_feps_form_' . $form_id,
                'att_data' => $att_data,
                'row_id' => $row_id,
                'args' => $args,
                'property' => $property,
                'images_limit' => abs((int) $images_limit))
            );
            ?></div>
        </div>
        <div class="<?php wpp_css("feps-default-template::description_wrapper","wpp_feps_description_wrapper"); ?>">
          <?php if (!empty($att_data['description'])) { ?>
            <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
            <span class="<?php wpp_css("feps-default-template::attribute_description_text","attribute_description_text"); ?>"><?php echo nl2br(WPP_F::cleanup_extra_whitespace($att_data['description'])); ?></span>
          <?php } ?>
        </div>
        <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
      </li>
    <?php } ?>
  </ul>

  <ul class="<?php wpp_css("feps-default-template::fields-wrapper","feps_user_input_fields"); ?>">

    <?php if (empty($current_user->data)) { ?>
      <li class="<?php wpp_css("feps-default-template::row-wrapper", array("wpp_feps_row_wrapper")); ?>">
        <div class="<?php wpp_css("feps-default-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
          <label for="<?php echo $form_id; ?>_user_email"><span class="<?php wpp_css("feps-default-template::the_title","the_title"); ?>"><?php _e('Your e-mail:', 'wpp'); ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
            <input tabindex="<?php echo $tabindex; ?>" type="text" id="<?php echo $form_id; ?>_user_email" name="wpp_feps_data[user_email]" class="<?php wpp_css("feps-default-template::input-class","wpp_feps_user_email"); ?>" value="<?php echo $current_user->data->user_email; ?>" />
          </div>
        </div>
        <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
      </li>
      <li class="<?php wpp_css("feps-default-template::row-wrapper", array("wpp_feps_row_wrapper",'user_password')); ?>" style="display:none;">
        <div class="<?php wpp_css("feps-default-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
          <label for="<?php echo $form_id; ?>_user_password"><span class="<?php wpp_css("feps-default-template::the_title","the_title"); ?>"><?php _e('Your Password:', 'wpp'); ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
            <input tabindex="<?php echo $tabindex; ?>" type="password" id="<?php echo $form_id; ?>_user_password" name="wpp_feps_data[user_password]"  class="<?php wpp_css("feps-default-template::input-class","wpp_feps_user_password"); ?>" />
          </div>
        </div>
        <div class="<?php wpp_css("feps-default-template::clear","clear"); ?>"></div>
      </li>
    <?php } ?>
    <li class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper submit_action"); ?> ">
      <?php

      switch (1){
        case
          /** If new FEPS with feps_credits */
          ( class_exists('wpi_spc') && (
            (empty($property) && !empty( $form['feps_credits'] ) && $form['feps_credits'] == 'true' ) ||
          /** Case edit of pending property and form with credits */
            ( !empty($property) && $property['post_status'] == 'pending' && !empty($property['wpp::feps::form_id']) && ($wp_properties['configuration']['feature_settings']['feps']['forms'][$property['wpp::feps::form_id']]["feps_credits"]=='true')))
          ):
          $btn_label = __('Next', 'wpp');
          break;

        case (!empty($property) && $property['post_status'] == 'publish'):
          $btn_label = __('Update', 'wpp');
          break;

        default:
          $btn_label = ($user_can_publish_properties=='true')?__('Publish', 'wpp'):__('Submit', 'wpp');
      }

      ?>
      <input tabindex="<?php echo $tabindex; ?>" type="submit" class="<?php wpp_css("feps-default-template::submit","wpp_feps_submit_form btn"); ?>" value="<?php echo apply_filters('feps-default-template::submit',$btn_label,$property,$form); ?>" />
      <span class="<?php wpp_css("feps-default-template::ajax-message","wpp_feps_ajax_message"); ?>"></span>
    </li>
  </ul>

</form>

<?php ob_start(); ?>
<script type="text/javascript">
  jQuery(document).ready(function() {

    var this_form = jQuery("#wpp_feps_form_<?php echo $form_id; ?>");

    jQuery(this_form).submit(function(event) {
      if(jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled") == "true") {
        event.preventDefault();
        return false;
      }
    });

    if(typeof jQuery.fn.validate == 'function') {
      jQuery(this_form).validate({
        submitHandler: function(form){
          wpp_feps_lookup_email(form);
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
    }

    function wpp_feps_lookup_email(this_form) {

      var user_email = jQuery(".wpp_feps_user_email", this_form).val();
      var user_password = jQuery(".wpp_feps_user_password", this_form).val();

      if ( typeof this_form.valid == 'function' && !this_form.valid() ) return;

      if ( user_logged_in ) {
        this_form.submit();
        jQuery('input.wpp_feps_submit_form').hide();
        return;
      }

      if(user_email == "") {
        jQuery(".wpp_feps_ajax_message", this_form).text("<?php _e('Please type in your e-mail address.', 'wpp'); ?>");
        jQuery(".wpp_feps_user_email", this_form).focus();
        return;
      }

      /* Disable submit button while checking e-mail */
      jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled", true);
      jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_processing", true);

      if(user_password == "") {
        jQuery(".wpp_feps_ajax_message", this_form).text("<?php _e('Checking if account exists.', 'wpp'); ?>");
        jQuery(".wpp_feps_row_wrapper.user_password", this_form).hide();
      } else {
        jQuery(".wpp_feps_ajax_message", this_form).text("<?php _e('Checking your credentials.', 'wpp'); ?>");
      }

      jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>", {
        action: "wpp_feps_email_lookup",
        user_email: user_email,
        user_password: user_password
      }, function(response) {

        jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_processing", false);

        if(response.email_exists == 'true') {

          if(response.credentials_verified == "true") {
            /* Email exists AND user credentials were verified */
            jQuery(".wpp_feps_ajax_message", this_form).text("<?php _e('Your credentials have been verified.', 'wpp'); ?>");
            jQuery(".wpp_feps_row_wrapper.user_password", this_form).show(); /* In case it was hidden but prefilled by auto-complete in browser */
            jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled", false);
            this_form.submit();
            jQuery('input.wpp_feps_submit_form').hide();
          } else if(response.invalid_credentials == "true") {
            /* Login failed. */
            jQuery(".wpp_feps_ajax_message", this_form).text("<?php _e('Your login credentials are not correct.', 'wpp'); ?>");

          } else {
            /* Email Exists, still need to check password. */
            jQuery(".wpp_feps_row_wrapper.user_password", this_form).show();
            jQuery(".wpp_feps_ajax_message", this_form).text("<?php _e('Account found, please type in your password.', 'wpp'); ?>");
          }

        } else {
          /* New Account */
          jQuery(".wpp_feps_row_wrapper.user_password", this_form).hide();
          jQuery(".wpp_feps_ajax_message", this_form).text("<?php _e('Your account has been created. Check your e-mail to activate account.', 'wpp'); ?>");
          jQuery(".wpp_feps_submit_form", this_form).attr("wpp_feps_disabled", false);
          this_form.submit();
          jQuery('input.wpp_feps_submit_form').hide();
        }

      }, "json");
    }

  });

</script>
<?php
$output_js = ob_get_contents();
ob_end_clean();
echo $output_js;
