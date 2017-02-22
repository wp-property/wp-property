<div class="wpp_retsci_widget_subscriber" data-config="<?php echo esc_attr($data) ?>">

  <h3>RETS Credentials</h3>

  <form id="subscriber-form" action="" method="post">

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="RETS URL" type="url" name="url" value="<?php echo get_option('rets_credential_login_url') ? get_option('rets_credential_login_url') : '' ?>" <?php echo get_option('rets_credential_login_url') ? 'disabled' : '' ?> />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="User" type="text" name="user" value="<?php echo get_option('rets_credential_username') ? get_option('rets_credential_username') : '' ?>" <?php echo get_option('rets_credential_username') ? 'disabled' : '' ?> />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="Password" type="password" name="password" value="<?php echo get_option('rets_credential_password') ? get_option('rets_credential_password') : '' ?>" <?php echo get_option('rets_credential_password') ? 'disabled' : '' ?> />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="RETS Version" type="text" name="rets_version" value="<?php echo get_option('rets_credential_version') ? get_option('rets_credential_version') : '' ?>" <?php echo get_option('rets_credential_version') ? 'disabled' : '' ?> />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="User Agent" type="text" name="user_agent" value="<?php echo get_option('rets_credential_user_agent') ? get_option('rets_credential_user_agent') : '' ?>" <?php echo get_option('rets_credential_user_agent') ? 'disabled' : '' ?> />
    </div>
    <?php if(!get_option('rets_credential_login_url')){ ?>
      <input type="submit" class="button button-primary" value="Send" />
    <?php } else { ?>
      <input type="submit" class="button button-primary" value="Stop subscribe" />
    <?php }  ?>

  </form>

  <p class="message"></p>

</div>

<style type="text/css">
  .wpp_retsci_widget_schedule .messages_header {
    display: none;
  }
  .message {
    text-align: center;
    font-size: 15px;
    font-weight: bold;
  }
  .wpp_retsci_widget_stats .updates_number {
    font-weight: bold;
    font-size: 5em;
    color: #0f7b18;
  }
  .wpp_retsci_widget_subscriber h3 {
    text-align: center;
  }

  .wpp_retsci_widget_subscriber .input-text-wrapper {
    margin-bottom: 10px;
  }
  .wpp_retsci_widget_subscriber .button.button-primary {
    text-align: center;
  }

</style>