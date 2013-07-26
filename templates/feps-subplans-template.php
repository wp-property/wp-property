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

global $wpi_settings;
 ?>

<form method="POST" action="<?php echo ( !empty($post->ID) ? get_page_link($post->ID) : WPP_F::base_url( FEPS_EDIT_PAGE.'/?feps='.$_REQUEST['wpp_post_id']) ); ?>" class="<?php wpp_css("feps-subpaln-template::form","wpp_feps_subscription_plan_form"); ?>">
  <input type="hidden" name="wpp_front_end_action" value="wpp_purchase_feps" />
  <input type="hidden" name="wpp_feps_form" value="<?php echo $_REQUEST['wpp_feps_form'] ?>" />
  <input type="hidden" name="wpp_post_id" value="<?php echo $_REQUEST['wpp_post_id'] ?>" />
  <?php if ( !empty( $_REQUEST['wpp_user_email'] ) ) : ?>
    <input type="hidden" name="wpp_user_email" value="<?php echo $_REQUEST['wpp_user_email'] ?>" />
  <?php endif; ?>
  <?php wp_nonce_field( 'feps_select_subscription_plan', '_wpnonce' ); ?>
  <?php $checked = false; ?>
  <ul class="<?php wpp_css("feps-subpaln-template::fields-wrapper","wpp_feps_subscription_plan_list"); ?>">
    <?php foreach( (array)$subscription_plans as $plan_key => $plan_data ): ?>
      <li class="<?php wpp_css("feps-subpaln-template::row-wrapper","wpp_feps_subscription_plan"); ?>">
        <label for="feps_subscription_plan_<?php echo $plan_key; ?>">
          <table>
            <tr>
              <td class="<?php wpp_css("feps-subpaln-template::input-wrapper","wpp_feps_subscription_plan_radio_holder"); ?>">
                <input id="feps_subscription_plan_<?php echo $plan_key; ?>" class="<?php wpp_css("feps-subpaln-template::styled","styled"); ?>" <?php echo !$checked?'checked="checked"':''; ?> type="radio" name="feps_subscription_plan" value="<?php echo $plan_key; ?>" />
              </td>
              <td>
                <h3 class="<?php wpp_css("feps-subpaln-template::title","wpp_feps_subscription_plan_title"); ?>"><?php echo $plan_data['name']; ?></h3>
                <p class="<?php wpp_css("feps-subpaln-template::description","wpp_feps_subscription_plan_description"); ?>"><?php echo $plan_data['description']; ?></p>
                <ul class="<?php wpp_css("feps-subpaln-template::info","wpp_feps_subscription_plan_info"); ?>">
                  <li class="<?php wpp_css("feps-subpaln-template::info-element","price"); ?>">
                    <label><?php _e('Price:', 'wpp'); ?></label>
                    <span><?php echo sprintf("%01.2f", $plan_data['price']); ?> <?php echo $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']]; ?></span>
                  </li>
                  <li class="<?php wpp_css("feps-subpaln-template::info-element","duration"); ?>">
                    <label><?php _e('Duration:', 'wpp'); ?></label>
                    <span><?php echo sprintf(__("%d %s", 'wpp'),$plan_data['duration']['value'],_n($plan_data['duration']['interval'],$plan_data['duration']['interval'].'s',$plan_data['duration']['value'], 'wpp', 'wpp')); ?></span>
                  </li>
                  <li class="<?php wpp_css("feps-subpaln-template::info-element","images_limit"); ?>">
                    <label><?php _e('Images:', 'wpp'); ?></label>
                    <span><?php echo $plan_data['images_limit']; ?></span>
                  </li>
                </ul>
              </td>
            </tr>
          </table>
        </label>
      </li>
    <?php $checked=true; endforeach; ?>
  </ul>
  <input type="submit" value="<?php _e('Next', 'wpp'); ?>" class="<?php wpp_css("feps-subpaln-template::submit","wpp_feps_submit_form btn"); ?>">
</form>
