<div class="wpp_retsci_widget_schedule" data-config="<?php echo esc_attr($data) ?>">

  <h3>RETS Credentials</h3>
  <p>Please provide rets credentials to setup..</p>

  <form id="subscription-form" action="" method="post">

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="RETS URL" type="url" name="url" />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="User" type="text" name="user" />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="Password" type="password" name="password" />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="RETS Version" type="text" name="rets_version" />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="User Agent" type="text" name="user_agent" />
    </div>

    <input type="submit" class="button button-primary" value="Send" />

  </form>

  <p class="message"></p>

</div>

<style type="text/css">
  .wpp_retsci_widget_schedule .messages_header {
    display: none;
  }
  .wpp_retsci_widget_schedule {
    text-align: center;
  }
  .wpp_retsci_widget_stats .updates_number {
    font-weight: bold;
    font-size: 5em;
    color: #0f7b18;
  }
</style>