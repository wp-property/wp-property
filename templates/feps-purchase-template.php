<?php
/**
 * Default FEPS Purchase template. Used for SPC integration.
 *
 * Copy this file into your theme directory to customize it.
 *
 * @version 0.1
 * @author korotkov@UD
 * @author peshkov@UD
 * @package WP-Property
 *
 */

?>
<div class="<?php wpp_css("feps-purchase-template::message", ""); ?> wpp_feps_message"></div>
<div class="wpp_feps_form feps_spc_details_wrapper">
  <ul class="feps_spc_details">
    <li class="<?php wpp_css("feps-purchase-template::row-wrapper", array("wpp_feps_row_wrapper")); ?>">
      <div class="<?php wpp_css("feps-purchase-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
        <label><?php echo WPP_F::property_label(); ?></label>
      </div>
      <div class="<?php wpp_css("feps-purchase-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
        <div class="<?php wpp_css("feps-purchase-template::input-content","wpp_feps_input_content"); ?>">
          <a target="_blank" href="<?php echo $property[ 'permalink' ]; ?>"><?php echo $property[ 'post_title' ]; ?></a>
          <?php if( !empty( $form_data[ 'can_manage_feps' ] ) && $form_data[ 'can_manage_feps' ] == 'true' ) : ?>
          <span class="wpp_feps_edit_property">( <a href="<?php echo class_wpp_feps::get_edit_feps_permalink( $_REQUEST['feps'], 'edit' ); ?>" ><?php _e( 'Edit', 'wpp' ); ?></a> )</span>
          <div class="description"><?php printf( __( 'You can modify the current %s anytime.', 'wpp' ), WPP_F::property_label() ); ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="<?php wpp_css("feps-purchase-template::clear","clear"); ?>"></div>
    </li>
    <li class="<?php wpp_css("feps-purchase-template::row-wrapper", array("wpp_feps_row_wrapper")); ?>">
      <div class="<?php wpp_css("feps-purchase-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
        <label><?php _e( 'Subscription Plan', 'wpp' ); ?></label>
      </div>
      <div class="<?php wpp_css("feps-purchase-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
        <div class="<?php wpp_css("feps-purchase-template::input-content","wpp_feps_input_content"); ?>">
          <strong><?php echo $plan[ 'name' ]; ?></strong> <span class="wpp_feps_change_subscription_plan">( <a href="<?php echo class_wpp_feps::get_edit_feps_permalink( $_REQUEST['feps'], 'subscription_plan' ); ?>" ><?php _e( 'Change', 'wpp' ); ?></a> )</span>
          <ul class="<?php wpp_css("feps-purchase-template::info","wpp_feps_subscription_plan_info"); ?>">
            <li class="<?php wpp_css("feps-purchase-template::info-element","price"); ?>">
              <label><?php _e('Price:', 'wpp'); ?></label>
              <span><strong><?php echo $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']]; ?><?php echo sprintf("%01.2f", $plan['price']); ?></strong></span> ( <?php printf( __( '%s%01.2f = %01.2f credit', 'wpp' ), $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']], 1, 1 ); ?> )
            </li>
            <li class="<?php wpp_css("feps-purchase-template::info-element","duration"); ?>">
              <label><?php _e('Duration:', 'wpp'); ?></label>
              <span><?php printf( "%d %s", $plan['duration']['value'], _n( $plan['duration']['interval'], $plan['duration']['interval'].'s', $plan['duration']['value'], 'wpp' ) ); ?></span>
            </li>
            <li class="<?php wpp_css("feps-purchase-template::info-element","is_featured"); ?>">
              <label><?php _e( 'Featured:', 'wpp' ); ?></label>
              <span><?php ( isset( $plan[ 'is_featured' ] ) && $plan[ 'is_featured' ] == 'true' ) ? _e( 'Yes', 'wpp' ) : _e( 'No', 'wpp' ); ?></span>
            </li>
            <?php if ( $form_has_images ) : ?>
            <li class="<?php wpp_css( "feps-purchase-template::info-element", "images_limit " ); ?>">
              <label><?php _e('Images:', 'wpp'); ?></label>
              <span><?php echo (!empty($plan['images_limit']) ? $plan['images_limit'] : 1 ); ?></span>
            </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
      <div class="<?php wpp_css("feps-purchase-template::clear","clear"); ?>"></div>
    </li>
    <?php if ( is_user_logged_in() ) : ?>
      <li class="<?php wpp_css("feps-purchase-template::row-wrapper", array("wpp_feps_row_wrapper")); ?>">
        <div class="<?php wpp_css("feps-purchase-template::label-wrapper", array("wpp_feps_label_wrapper")); ?>">
          <label><?php _e( 'Current Balance', 'wpp' ); ?></label>
        </div>
        <div class="<?php wpp_css("feps-purchase-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-purchase-template::input-content","wpp_feps_input_content"); ?>">
            <?php printf("%01.2f", $credits); ?> <?php _e( 'credits', 'wpp' ); ?>
          </div>
        </div>
        <div class="<?php wpp_css("feps-purchase-template::clear","clear"); ?>"></div>
      </li>
    <?php endif; ?>
  </ul>
  <ul class="<?php wpp_css("feps-purchase-template::fields-wrapper","feps_user_input_fields"); ?> submit_action_wrapper">
    <li class="<?php wpp_css("feps-purchase-template::input-wrapper","wpp_feps_input_wrapper submit_action"); ?> ">
      <?php if( is_user_logged_in() && $credits >= $plan['price'] ) : ?>
      <form class="<?php wpp_css("feps-purchase-template::form",""); ?> wpp_feps_withdraw_credits">
        <input type="hidden" value="wpp_feps_pay_now" name="action" />
        <input type="hidden" value="<?php echo $plan['price']; ?>" name="amount" />
        <input type="hidden" value="<?php echo $property[ 'ID' ]; ?>" name="property_id" />
        <input type="hidden" value="<?php echo $plan_slug; ?>" name="subscription_plan" />
        <?php wp_nonce_field('wpp_feps_pay_now'); ?>
        <div class="pay_now">
          <div class="<?php wpp_css("feps-purchase-template::action_button","wpp_feps_action_button"); ?>"><input type="submit" value="<?php printf( __( 'Publish the current %s', 'wpp'), WPP_F::property_label() ); ?>"></div>
          <div class="<?php wpp_css("feps-purchase-template::action_notification","wpp_feps_action_notification"); ?>">
            <div class="<?php wpp_css("feps-purchase-template::action_notification_content","wpp_feps_action_content"); ?>">
              <?php printf( __( '<strong>%01.2f</strong> credits will be withdrawn from your balance.', 'wpp' ), $plan['price'] ); ?>
            </div>
          </div>
          <div class="<?php wpp_css("feps-purchase-template::clear","clear"); ?>"></div>
        </div>
      </form>
      <?php else : ?>
        <div class="add_credits">
          <div class="<?php wpp_css("feps-purchase-template::action_button","wpp_feps_action_button"); ?>"><input type="submit" value="<?php _e( 'Add Credits', 'wpp'); ?>"></div>
          <div class="<?php wpp_css("feps-purchase-template::action_notification","wpp_feps_action_notification"); ?>">
            <div class="<?php wpp_css("feps-purchase-template::action_notification_content","wpp_feps_action_content"); ?>">
              <?php echo sprintf( __('You don\'t have enough credits on your balance. Current submission requires <strong>%01.2f</strong> credits. After credits will be added, the current %s will be automatically published.', 'wpp'), $plan['price'], WPP_F::property_label() ); ?>
            </div>
          </div>
          <div class="<?php wpp_css("feps-purchase-template::clear","clear"); ?>"></div>
        </div>
      <?php endif; ?>
    </li>
  </ul>
</div>
<div class="wpp_feps_checkout_wrapper">
  <?php if( !is_user_logged_in() || $credits < $plan['price'] ) : ?>
    <?php $fixed_email = !empty( $feps_user_email ) ? "user_email='{$feps_user_email}'" : ''; ?>
    <?php echo do_shortcode("[wpi_checkout template='feps' custom_amount='true' amount='{$plan['price']}' hidden_attributes='" . FEPS_META_PLAN . "={$plan_slug},wpp::feps::property_id={$property_id}' {$fixed_email} callback_function='wpp_add_feps_credits' title='FEPS Credits']"); ?>
  <?php endif; ?>
</div>
