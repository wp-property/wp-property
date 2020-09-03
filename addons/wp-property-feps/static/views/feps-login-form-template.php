<?php
/**
 * FEPS Login Widget template
 *
 * @author kavaribes@UD
 */
global $user_ID, $user_identity, $user_level; 

wp_register_script( 'wpp-feps-login', ud_get_wpp_feps()->path( 'static/scripts/src/wpp.login.js' ), array( 'jquery', 'wp-property-global' ) );

wp_localize_script( 'wpp-feps-login', 'feps_login_object', array(
  'ajaxurl' => admin_url( 'admin-ajax.php' ),
  'redirecturl' => $redirect,
  'loadingmessage' => __('Please, wait...')
));

wp_enqueue_script('wpp-feps-login');

?>
<div class="wpp_feps_login_box" data-template="feps-login-form-template">

  <form id="login" action="login" method="post">

    <p class="status_login"></p>

    <div class="line login_form_description">
      <?php echo $login_form_description; ?>
    </div>

    <div class="line">
      <i class="icon-user"></i>
      <input id="username" class="input_text" type="text" placeholder="User" name="username" />
    </div>

    <div class="line">
      <i class="icon-key"></i>
      <input id="password" class="input_text" type="password" placeholder="Password" name="password" />
    </div>

    <div class="line" style="display: none;">
      <input name="rememberme" type="checkbox" id="my-rememberme" checked="checked" value="forever" />
    </div>

    <?php wp_nonce_field( 'ajax-login-nonce', 'feps-login' ); ?>

    <div class="line cf">
      <input class="submit_button" type="submit" value="Log in" name="submit">
      <div class="login_link">
        <?php if($show_reg_link == 'true') : ?>
        <a class="reg_link" href="<?php bloginfo('url'); ?>/wp-login.php?action=register"><?php echo apply_filters( 'wpp::feps::register_text', __('Register' ) ); ?></a>
        <?php endif; ?>
        <?php if($show_remember_link == 'true') : ?>
        <a class="lost_pass_link" href="<?php bloginfo('url'); ?>/wp-login.php?action=lostpassword"><?php echo apply_filters( 'wpp::feps::password_reset_text', __('Password Reset' ) ); ?></a>
        <?php endif; ?>
      </div>
    </div>

  </form>

</div>
