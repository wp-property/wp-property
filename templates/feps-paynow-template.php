<?php
/**
 * PayNow FEPS form template
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
<style type="text/css">
  .wpp_feps_pay_now_result {
    display: none;
  }
</style>
<script type="text/javascript">
  jQuery(document).ready(function(){
    jQuery('.wpp_feps_pay_now_button').click(function(){
      jQuery(this).attr('disabled', true).html('<?php _e('Loading...', 'wpp'); ?>');
      jQuery.post( wpp.instance.ajax_url, jQuery('.wpp_feps_pay_now_form').serialize(), function(response) {
        if ( response.success ) {
          jQuery('.wpp_feps_pay_now').remove();
          jQuery('.wpp_feps_pay_now_result').html(response.message+' <a href="'+response.redirect+'">View Properties.</a>').show();
          jQuery(document).trigger('wpp_feps_pay_now_success');
        } else {
          jQuery('.wpp_feps_pay_now_result').html(response.message).show();
          jQuery(document).trigger('wpp_feps_pay_now_error');
        }
      }, 'JSON');
    });
  });
</script>
<div class="wpp_feps_pay_now">
  <table>
    <tr>
      <td class="<?php wpp_css("feps-paynow-template::info","wpp_feps_pay_now information"); ?>">
        <?php echo sprintf( __('Current submission requires <strong>%s</strong> credits. You can use your balance for purchasing. Also, you are able to increase your balance using form below.', 'wpp'), $price ); ?>
      </td>
      <td class="<?php wpp_css("feps-paynow-template::payment-wrapper","wpp_feps_pay_now payment_form"); ?>">
        <div class="<?php wpp_css("feps-paynow-template::balance-inner","wpp_feps_balance_inner"); ?>">
          <div class="<?php wpp_css("feps-paynow-template::balance","wpp_feps_balance"); ?>">
            <?php echo sprintf( __('<label>Balance</label><span class="devider">:</span> <span class="value">%s</span> <span class="currency">%s</span>', 'wpp'), sprintf("%01.2f", $credits), $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']] ); ?>
          </div>
          <div class="<?php wpp_css("feps-paynow-template::price","wpp_feps_price"); ?>">
            <?php echo sprintf( __('<label>Price</label><span class="devider">:</span> <span class="value">%s</span> <span class="currency">%s</span>', 'wpp', 'wpp'), sprintf("%01.2f", $price), $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']] ); ?>
          </div>
          <div>
            <form class="<?php wpp_css("feps-paynow-template::form","wpp_feps_pay_now_form"); ?>">
              <input type="hidden" value="wpp_feps_pay_now" name="action" />
              <input type="hidden" value="<?php echo $price; ?>" name="amount" />
              <input type="hidden" value="<?php echo $property_id; ?>" name="property_id" />
              <input type="hidden" value="<?php echo $subscription_plan_slug; ?>" name="subscription_plan" />
              <?php wp_nonce_field('wpp_feps_pay_now'); ?>
            </form>
            <button class="<?php wpp_css("feps-paynow-template::submit","wpp_feps_pay_now_button"); ?>"><?php echo sprintf( __( '<label>Pay Now</label> <span class="value">%s</span> <span class="currency">%s</span>', 'wpp'), sprintf("%01.2f", $price), $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']] ); ?></button>
          </div>
        </div>
      </td>
    </tr>
  </table>
</div>
<div class="wpp_feps_pay_now_result"></div>