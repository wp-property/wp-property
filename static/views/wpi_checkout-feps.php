<?php /** If there are available gateways in WP-Invoice */ ?>
<?php if ( is_array( $available_gateways ) && !empty( $available_gateways ) ): ?>
  <div class="wpp_feps_form wpi_checkout_wrapper">
    <?php /** If there are more then 1 gateway - show select */ ?>
    <?php if ( count( $available_gateways ) > 1 ): ?>
      <ul class="<?php wpp_css("feps-checkout-template::fields-wrapper","feps_property_input_fields wpi_checkout"); ?>">
        <li class="<?php wpp_css("feps-checkout-template::row-wrapper", array("wpp_feps_row_wrapper")); ?>">
          <div class="<?php wpp_css("feps-checkout-template::label-wrapper","wpp_feps_label_wrapper"); ?>">
            <label><span class="<?php wpp_css("feps-checkout-template::the_title","the_title"); ?>"><?php _e('Payment Method', 'wpp'); ?></span></label>
          </div>
          <div class="<?php wpp_css("feps-checkout-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
            <div class="<?php wpp_css("feps-checkout-template::input-content","wpp_feps_input_content wpi_checkout_input_content"); ?>">
              <div class="control-group">
                <label class="control-label"><?php _e( 'Method', 'wpp' ); ?></label>
                <div class="controls">
                  <select class="wpi_checkout_select_payment_method_dropdown">
                  <?php foreach( $available_gateways as $key => $val ): ?>
                      <option <?php echo $val['default_option'] == 'true' ? 'selected="selected"' : ''; ?> value="<?php echo esc_attr( $key ); ?>"><?php _e( $val['name'], 'wpp' ); ?></option>
                  <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="<?php wpp_css("feps-checkout-template::clear","clear"); ?>"></div>
        </li>
      </ul>
    <?php endif; ?>
    <?php
    /** For each available gateway - draw payment form for current gateway */
    foreach( $available_gateways as $gateway_key => $gateway ) {

      /**
       * If there is one gateway, display it, otherwise display default_option
       */
      $display = true;
      if ( count($available_gateways) > 1 ) {
       if ( $gateway['default_option'] == 'true' ) {
         $display = true;
       } else {
         $display = false;
       }
      } else {
       $display = true;
      }

      $template_found = UD_API::get_template_part( array(
        "{$gateway_key}-checkout-{$template}",
        "{$gateway_key}-checkout-{$template}.tpl",
        "{$gateway_key}-checkout",
        "{$gateway_key}-checkout.tpl",
      ), array( WPP_Templates , WPI_Gateways_Path . '/templates' ) );

      if( $template_found ) {
        include $template_found;
      }

    }
    ?>
  </div>
<?php else: ?>
  <p class="wpi_checkout_gateways_error"><?php _e( 'Specified gateways are not available', 'wpp' ); ?></p>
<?php endif; ?>