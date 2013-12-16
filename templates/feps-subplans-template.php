<?php
/**
 * Default FEPS Subscription Plans template. Used for SPC integration.
 *
 * Copy this file into your theme directory to customize it.
 *
 * @version 0.1
 * @author korotkov@UD
 * @package WP-Property
 *
 */

global $wpi_settings, $wpp_feps_subsc_plan;

$post_id = $_REQUEST['feps'];
$renew_plan = get_post_meta( $post_id, FEPS_RENEW_PLAN, true );
$renew_plan = !empty( $renew_plan ) && $renew_plan == 'true' ? 'checked' : '';

?><form method="POST" action="<?php echo class_wpp_feps::get_edit_feps_permalink( $_REQUEST['feps'], 'checkout' ); ?>" class="<?php wpp_css("feps-subpaln-template::form","wpp_feps_form"); ?> wpp_feps_subscription_plan_form">
    <?php wp_nonce_field( 'feps_select_subscription_plan', '_wpnonce' ); ?>
    <?php $checked = false; ?>
    <h3><?php _e( 'Choose Subscription Plan', 'wpp' ); ?>:</h3>
    <ul class="<?php wpp_css("feps-subpaln-template::fields-wrapper","wpp_feps_subscription_plan_list feps_property_input_fields"); ?>">
    <?php foreach( (array)$subscription_plans as $plan_key => $plan_data ): ?>
      <?php if( empty( $wpp_feps_subsc_plan ) ) $wpp_feps_subsc_plan = $plan_key; ?>
      <?php $is_selected = $wpp_feps_subsc_plan == $plan_key ? 'feps_plan_selected' : ''; ?>
      <li class="<?php wpp_css("feps-subpaln-template::row-wrapper","wpp_feps_subscription_plan"); ?> wpp_feps_row_wrapper <?php echo $is_selected; ?>" data-subsc_plan="<?php echo $plan_key; ?>" >
        <div class="<?php wpp_css("feps-subpaln-template::label-wrapper","wpp_feps_label_wrapper"); ?>">
          <span class="<?php wpp_css("feps-subpaln-template::the_title","the_title"); ?>"><?php echo $plan_data['name']; ?></span>
        </div>
        <div class="<?php wpp_css("feps-subpaln-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-subpaln-template::input-content","wpp_feps_input_content"); ?>">
            <p class="<?php wpp_css("feps-subpaln-template::description","wpp_feps_subscription_plan_description"); ?>"><?php echo $plan_data['description']; ?></p>
            <ul class="<?php wpp_css("feps-subpaln-template::info","wpp_feps_subscription_plan_info"); ?>">
              <li class="<?php wpp_css("feps-subpaln-template::info-element","price"); ?>">
                <label><?php _e('Price:', 'wpp'); ?></label>
                <span><?php echo $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']]; ?><?php echo sprintf("%01.2f", $plan_data['price']); ?></span>
              </li>
              <li class="<?php wpp_css("feps-subpaln-template::info-element","duration"); ?>">
                <label><?php _e('Duration:', 'wpp'); ?></label>
                <span><?php printf( "%d %s", $plan_data['duration']['value'], _n( $plan_data['duration']['interval'], $plan_data['duration']['interval'].'s', $plan_data['duration']['value'], 'wpp' ) ); ?></span>
              </li>
              <li class="<?php wpp_css("feps-subpaln-template::info-element","is_featured"); ?>">
                <label><?php _e( 'Featured:', 'wpp' ); ?></label>
                <span><?php ( isset( $plan_data[ 'is_featured' ] ) && $plan_data[ 'is_featured' ] == 'true' ) ? _e( 'Yes', 'wpp' ) : _e( 'No', 'wpp' ); ?></span>
              </li>
              <li class="<?php wpp_css("feps-subpaln-template::info-element","images_limit" . ($form_has_images ? '' : " no_image_field") ); ?>">
                <label><?php _e('Images:', 'wpp'); ?></label>
                <span><?php echo (!empty($plan_data['images_limit']) ? $plan_data['images_limit'] : 1 ); ?></span>
                <span class="description"><?php _e( 'The available amount of images which can be uploaded for the property', 'wpp'); ?></span>
              </li>
            </ul>
          </div>
        </div>
        <div class="<?php wpp_css("feps-subpaln-template::clear","clear"); ?>"></div>
      </li>
    <?php $checked=true; endforeach; ?>
  </ul><br/>
  <h3><?php _e( 'Subscription Plan Options', 'wpp' ); ?>:</h3>
  <ul class="<?php wpp_css("feps-default-template::fields-wrapper","wpp_feps_plan_options"); ?>">
    <li>
      <div class="<?php wpp_css("feps-default-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
        <div class="<?php wpp_css("feps-default-template::input-content","wpp_feps_input_content"); ?>">
          <label>
            <input id="renew_plan_<?php echo $post_id; ?>" type="checkbox" value="" <?php echo $renew_plan; ?> /> 
            <?php printf( __( 'Enable automatic renewal of Subscription plan for the current %1$s when subscription time is expired.', 'wpp' ), WPP_F::property_label( 'singular' ) ); ?>
            <span class="<?php wpp_css("feps-default-template::attribute_description_text","attribute_description_text"); ?>"><?php printf( __( 'Credits will be automatically withdrawn from your balance. Notice, automatic renewal will be done only if you have enough credits on your balance.', 'wpp' ), WPP_F::property_label( 'singular' ) ); ?></span>
          </label>
        </div>
      </div>
    </li>
  </ul>
  <ul class="<?php wpp_css("feps-subpaln-template::fields-wrapper","feps_actions feps_user_input_fields"); ?>">
    <?php if( !empty( $form_data[ 'can_manage_feps' ] ) && $form_data[ 'can_manage_feps' ] == 'true' ) : ?>
    <li class="<?php wpp_css("feps-subpaln-template::back-action-wrapper","wpp_feps_input_wrapper back_action"); ?>">
      <input type="button" onclick="window.location.href = '<?php echo class_wpp_feps::get_edit_feps_permalink( $post_id, 'edit' ); ?>';" value="<?php _e('Back', 'wpp'); ?>" class="<?php wpp_css("feps-subpaln-template::back-action","btn feps_action_btn"); ?>" />
    </li>
    <?php endif; ?>
    <li class="<?php wpp_css("feps-subpaln-template::submit-action-wrapper","wpp_feps_input_wrapper submit_action"); ?> ">
      <input type="submit" value="<?php _e('Next', 'wpp'); ?>" class="<?php wpp_css("feps-subpaln-template::submit-action","wpp_feps_submit_form btn feps_action_btn"); ?>">
    </li>
  </ul>
  <input type="hidden" name="subscription_plan" value="<?php echo $wpp_feps_subsc_plan; ?>" >
</form>
