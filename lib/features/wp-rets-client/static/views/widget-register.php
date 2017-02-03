<div class="wpp_retsci_widget_register" data-config="<?php echo esc_attr($data) ?>">

  <h3>RETS Sign In</h3>
  <p>This site is not registered, please proceed below.</p>

  <form id="signin-form" action="" method="post">

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="URL" type="url" name="url" />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="User" type="text" name="user" />
    </div>

    <div class="input-text-wrapper">
      <input required class="widefat" placeholder="Password" type="password" name="password" />
    </div>

    <input type="submit" class="button button-primary" value="Sign In" />

  </form>

  <p class="message"></p>

</div>

<style type="text/css">

  .wpp_retsci_widget_register h3 {
    text-align: center;
  }

  .wpp_retsci_widget_register .input-text-wrapper {
    margin-bottom: 10px;
  }

</style>