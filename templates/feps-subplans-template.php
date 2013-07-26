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

?><form method="POST" action="<?php echo ( !empty($post->ID) ? get_page_link($post->ID) : WPP_F::base_url( FEPS_EDIT_PAGE.'/?feps='.$_REQUEST['wpp_post_id']) ); ?>" class="<?php wpp_css("feps-subpaln-template::form","wpp_feps_form"); ?> wpp_feps_subscription_plan_form">
  <input type="hidden" name="wpp_front_end_action" value="wpp_purchase_feps" />
  <input type="hidden" name="wpp_feps_form" value="<?php echo $_REQUEST['wpp_feps_form'] ?>" />
  <input type="hidden" name="wpp_post_id" value="<?php echo $_REQUEST['wpp_post_id'] ?>" />
  <?php if ( !empty( $_REQUEST['wpp_user_email'] ) ) : ?>
    <input type="hidden" name="wpp_user_email" value="<?php echo $_REQUEST['wpp_user_email'] ?>" />
  <?php endif; ?>
  <?php wp_nonce_field( 'feps_select_subscription_plan', '_wpnonce' ); ?>
  <?php $checked = false; ?>
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
                <span><?php echo sprintf("%01.2f", $plan_data['price']); ?> <?php echo $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']]; ?></span>
              </li>
              <li class="<?php wpp_css("feps-subpaln-template::info-element","duration"); ?>">
                <label><?php _e('Duration:', 'wpp'); ?></label>
                <span><?php echo sprintf(__("%d %s", 'wpp'),$plan_data['duration']['value'],_n($plan_data['duration']['interval'],$plan_data['duration']['interval'].'s',$plan_data['duration']['value'], 'wpp', 'wpp')); ?></span>
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
  </ul>
  <ul class="<?php wpp_css("feps-subpaln-template::fields-wrapper","feps_user_input_fields"); ?>">
    <li class="<?php wpp_css("feps-subpaln-template::input-wrapper","wpp_feps_input_wrapper submit_action"); ?> ">
      <input type="submit" value="<?php _e('Next', 'wpp'); ?>" class="<?php wpp_css("feps-subpaln-template::submit","wpp_feps_submit_form btn"); ?>">
    </li>
  </ul>
  <input type="hidden" name="feps_subscription_plan" value="<?php echo $wpp_feps_subsc_plan; ?>" >
</form>
<script type="text/javascript">
  if( typeof feps_subsc_form !== 'function' ) {
    var feps_subsc_form = function( el ) {

      el.each( function( i, e ) {
        if( jQuery( e ).hasClass( 'feps_subsc_form_activated' ) ) {
          return null;
        }

        jQuery( 'li.wpp_feps_row_wrapper', e ).click( function() {
          jQuery( 'input[name="feps_subscription_plan"]', e ).val( jQuery( this ).data( 'subsc_plan' ) );
          jQuery( 'li.wpp_feps_row_wrapper', e ).removeClass( 'feps_plan_selected' );
          jQuery( this ).addClass( 'feps_plan_selected' );
        } );

        jQuery( e ).addClass( 'feps_subsc_form_activated' );
      } );
    };
  }
  feps_subsc_form( jQuery( '.wpp_feps_subscription_plan_form' ) );
</script>
