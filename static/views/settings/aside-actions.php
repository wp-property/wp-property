<?php
/**
 * Name: Utility - Actions
 * Group: Settings
 *
 */
?>
<ul data-panel="settings-utility-actions">

  <?php wp_nonce_field( 'wpp_setting_save' ); ?>

  <?php submit_button( __( 'Save Changes', 'wpp' ), 'primary', 'submit', true, array( 'data-action' => 'save' )); ?>

  <div class="wpp_fb_like">
    <div class="fb-like" data-href="https://www.facebook.com/wpproperty" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false"></div>
  </div>

  <p id="wpp_plugins_ajax_response" class="hidden"></p>

  <p>
    <input type="button" value="<?php _e( 'Check Updates', 'wpp' ); ?>" id="wpp_ajax_check_plugin_updates"/><?php _e( 'to download, or update, all premium features purchased for this domain.', 'wpp' ); ?>
  </p>


</ul>