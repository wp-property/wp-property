<!-- AUTHORIZE.NET -->
<form method="POST" class="<?php echo apply_filters('wpi_spc::form_class', "wpi_checkout_feps wpi_checkout {$gateway_key}"); ?>" action="#" <?php echo $display ? '' : 'style="display:none;"'; ?>>
  <?php do_action( 'wpi::spc::payment_form_top', $atts); ?>
  <input type="hidden" name="wpi_checkout[payment_method]" value="wpi_authorize" />
  <input type="hidden" name="wpi_checkout[currency_code]" value="<?php echo $wpi_settings['currency']['default_currency_code']; ?>" />
  <?php if ( !empty( $atts['items'] ) ) : ?>
    <?php foreach( $atts['items'] as $item ): ?>
      <input type="checkbox" <?php echo ($atts['uncheck_items'] != 'true' ? 'checked="true"' : ''); ?>  style="display:none;" class="wpi_checkout_products" item_price="<?php echo esc_attr( number_format( (float)($item['price']*$item['quantity'] + ($item['price']*$item['quantity']/100*$item['tax'])), 2, '.', '') ); ?>"  item_name="<?php echo esc_attr($item['name']); ?>" name="wpi_checkout[items][<?php echo esc_attr($item['name']); ?>]" value="true" />
    <?php endforeach; ?>
  <?php endif; ?>
  <input type="hidden" name="wpi_checkout[default_price]" id="default_price" value="<?php echo number_format((float)$total, 2, '.', ''); ?>" />
  <input type="hidden" class="wpi_checkout_security_hash" name="wpi_checkout[security_hash]" value="<?php echo self::generate_security_hash( !empty( $atts['fee'] )?$atts['fee']:'', number_format( (float)$total, 2, '.', '') ); ?>" />
  <?php if(!empty($atts['callback_function'])) : ?>
    <input type="hidden" name="wpi_checkout[callback_action]" value="<?php echo $atts['callback_function']; ?>" />
  <?php endif; ?>
  <?php if(!empty($atts['fee'])) : ?>
    <input type="hidden" class="wpi_checkout_fee wpi_authorize" name="wpi_checkout[fee]" value="<?php echo (int)$atts['fee']; ?>" />
  <?php endif; ?>
  <?php if( !empty($atts['title'] ) ) : ?>
    <input type="hidden" name="wpi_checkout[spc_title]" value="<?php echo $atts['title']; ?>" />
  <?php endif; ?>
  <ul class="<?php wpp_css("feps-checkout-template::fields-wrapper","feps_property_input_fields"); ?>">
    <?php foreach($wpi_checkout['info_block'] as $info_block => $block_data): ?>
      <li class="<?php wpp_css("feps-checkout-template::row-wrapper", array("wpp_feps_row_wrapper")); ?> wpi_checkout_block wpi_checkout_<?php echo $info_block; ?>">
        <div class="<?php wpp_css("feps-checkout-template::label-wrapper","wpp_feps_label_wrapper"); ?>">
          <label><span class="<?php wpp_css("feps-checkout-template::the_title","the_title"); ?>"><?php echo ucwords(str_replace("_", " ", $info_block)); ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-checkout-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div class="<?php wpp_css("feps-checkout-template::input-content","wpp_feps_input_content wpi_checkout_input_content"); ?>">
            <ul>
              <?php foreach($wpi_checkout['info_block'][$info_block] as $slug => $data): ?>
                <li class="wpi_checkout_row_<?php echo $slug; ?> wpi_checkout_row">
                <?php
                $group_control_attrs = apply_filters('wpi_spc::group_control_attrs', array(), $slug, $data);
                $attrs = '';

                if ( !empty( $group_control_attrs ) && is_array( $group_control_attrs ) ) {
                  foreach ($group_control_attrs as $attr_name => $attr_value) {
                    $attrs .= $attr_name.'="'.$attr_value.'" ';
                  }
                }
                ?>
                <div <?php echo $attrs; ?> class="<?php echo apply_filters('wpi_spc::group_coltrol_class', 'control-group'); ?>">
                  <?php $input_classes = implode(' ', apply_filters('wpi_spc::form_input_classes', array("input-large", "text-input", "wpi_checkout_payment_{$slug}_input"), $slug, $data, 'wpi_authorize') ); ?>
                  <?php $value = !empty($current_user->$slug) ? $current_user->$slug : '' ; ?>
                  <?php if ( $slug == 'amount' ) { $value = $atts['amount']; } ?>
                  <?php	echo apply_filters("wpi_checkout_input_{$slug}", "<label class='control-label' for='wpi_checkout_payment_{$slug}'>{$data['label']}</label><div class='controls'><input ".implode(' ', apply_filters('wpi_spc::input_attributes', array("type='text'"),$slug))." name='wpi_checkout[billing][{$slug}]' value='".apply_filters('wpi_spc::input_value', esc_attr($value), $slug, $atts)."' id='wpi_checkout_payment_{$slug}_{$gateway_key}' class='{$input_classes}' /><span class='help-inline validation'></span></div>", $slug, $data); ?>
                </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <div class="<?php wpp_css("feps-checkout-template::clear","clear"); ?>"></div>
      </li>
    <?php endforeach; ?>
    <?php if($atts['terms']) : ?>
      <li class="<?php wpp_css("feps-checkout-template::row-wrapper", array("wpp_feps_row_wrapper")); ?>">
        <div class="<?php wpp_css("feps-checkout-template::label-wrapper","wpp_feps_label_wrapper"); ?>">
          <label><span class="<?php wpp_css("feps-checkout-template::the_title","the_title"); ?>"><?php echo apply_filters('wpi_spc::terms_label', __('Agreement')); ?></span></label>
        </div>
        <div class="<?php wpp_css("feps-checkout-template::input-wrapper","wpp_feps_input_wrapper"); ?>">
          <div  class="<?php wpp_css("feps-checkout-template::input-content","wpp_feps_input_content wpi_checkout_input_content"); ?> wpi_checkout_row_terms wpi_checkout_row">
            <div validation_type="checked" class="<?php echo apply_filters('wpi_spc::group_coltrol_class', 'control-group'); ?>">
              <div class="controls">
                <input type="hidden"  name="wpi_checkout[terms]" value="false" />
                <label class="checkbox" for="wpi_checkout_payment_terms_input">
                  <input type="checkbox" class="wpi_checkout_payment_terms_input" id="wpi_checkout_payment_terms_input_<?php echo $gateway_key; ?>" name="wpi_checkout[terms]" value="true" />
                  <?php echo $atts['terms']; ?>
                </label>
                <span class="help-inline validation"></span>
              </div>
            </div>
          </div>
        </div>
        <div class="<?php wpp_css("feps-checkout-template::clear","clear"); ?>"></div>
      </li>
    <?php endif; ?>
  </ul>
  <ul class="<?php echo apply_filters('wpi_spc::form_actions_class', 'form-actions feps_user_input_fields', 'wpi_authorize'); ?>">
    <li class="<?php wpp_css("feps-checkout-template::input-wrapper","wpp_feps_input_wrapper submit_action"); ?> ">
      <input class="wpp_feps_submit_form btn wpi_checkout_submit_btn" type="submit" value="<?php esc_attr( _e('Process Payment', 'wpp' )); ?>">
      <span class="total_price"><?php _e( 'of', 'wpp' ); ?>
        <span class="wpi_checkout_final_price"><?php echo $wpi_settings['currency']['symbol'][$wpi_settings['currency']['default_currency_code']]; ?><span class="wpi_price"><?php echo wp_invoice_currency_format( (float)$total ); ?></span>
          <span class="wpi_fee_amount"></span>
        </span>
      </span>
    </li>
  </ul>
  <div class="<?php echo apply_filters('wpi_spc::response_box_class', 'wpi_checkout_payment_response hidden'); ?>"></div>
</form>