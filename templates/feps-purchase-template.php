<?php
/**
 * Default FEPS Purchase template. Used for SPC integration.
 *
 * Copy this file into your theme directory to customize it.
 *
 * @version 0.1
 * @author korotkov@UD
 * @package WP-Property
 *
 */
 ?>
<?php $fixed_email = !empty( $feps_user_email ) ? "user_email='{$feps_user_email}'" : false; ?>
<?php do_action('wpp_feps_before_checkout_form', array('subscription_plan' => $plan, 'subscription_plan_slug' => $plan_slug, 'property_id' => $property_id)); ?>
<?php echo do_shortcode("[wpi_checkout template='feps' custom_amount='true' amount='{$plan['price']}' hidden_attributes='wpp::feps::subscription_plan={$plan_slug},wpp::feps::property_id={$property_id}' {$fixed_email} callback_function='wpi_add_feps_credits' title='FEPS Credits']"); ?>