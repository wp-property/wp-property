<?php
/**
 * Welcome (Upgrade) WP-Property page
 */

//** flush Rewrite Rules */
flush_rewrite_rules();
//** flush Object Cache */
wp_cache_flush();

?><style>
  .ud-badge {
    background: url( "<?php echo ud_get_wp_property()->path( '/static/images/icon.png', 'url' ); ?>" ) no-repeat center !important;
    background-size: 150px 150px !important;
    box-shadow: none !important;
  }
  .changelog .col-1 {
    padding-right: 2%;
  }
  .changelog .col-1,
  .changelog .col-2 {
    float: left;
    width: 48%;
  }
  .changelog ul {
    list-style-position: outside;
    list-style-type: disc;
    margin-left: 20px;
  }
</style>
<div class="changelog">

  <div class="overview">

    <h2><?php printf( __( 'WP-Property %s has been installed', ud_get_wp_property()->domain ), ud_get_wp_property( 'version' ) ); ?></h2>

    <p><?php _e( 'Congratulations! You have just installed brand new version of WP-Property plugin. There are some important things that you need to be aware of in order to use our products successfully and with pleasure.', ud_get_wp_property()->domain ) ?></p>

    <p><?php _e( 'Please read the following instructions carefully. Explore links below to get more information on our site.', ud_get_wp_property()->domain ); ?></p>

    <hr />

    <p><i><?php _e( 'WP-Property is the leading WordPress plugin for creating and managing highly customizable real estate, property management, and completely custom listing showcase websites. Packed full of features, it gives you possibilities that no other real estate system for WordPress does.', ud_get_wp_property()->domain ); ?></i></p>

    <p><i><?php printf( __( 'In addition to the default functions WP-Property can be extended with a <a href="%s">set of add-ons</a>. They may help you manage your business more effectively.', ud_get_wp_property()->domain ), 'https://www.usabilitydynamics.com/products' ); ?></i></p>

    <?php $s = ud_get_wp_property(); if( isset( $s->uservoice_url ) ) : ?>
      <hr />
      <p><?php printf( __( 'Do you want to help us to improve %s? Or do you have any idea? We are waiting <a href="%s" target="_blank">feedback</a> from you!', ud_get_wp_property()->domain ), ud_get_wp_property()->name, $s->uservoice_url  ); ?></p>
    <?php endif; ?>

    <hr />

    <h3><?php printf( __( 'WP-Property %s important changes', ud_get_wp_property()->domain ), ud_get_wp_property( 'version' ) ); ?>:</h3>

    <ul>
      <?php $changes = wpp_get_update_changes();
      foreach ($changes as $change) : ?>
        <li><?php echo $change; ?></li>
      <?php endforeach; ?>
    </ul>

  </div>

  <hr />

  <div class="feature-section col two-col">

    <div class="col-1">

      <h3><?php _e( 'User license management', ud_get_wp_property()->domain ); ?></h3>

      <h4><?php _e( 'Installation', ud_get_wp_property()->domain ); ?></h4>

      <p><?php _e( 'In new version of WP-Property all your premium features are separate plugins. If you are upgrading from old WP-Property ( < 2.0.0 ) you will need to install and activate them one more time.', ud_get_wp_property()->domain ); ?></p>

      <p><?php printf( __( 'After you get the product, visit your <a href="%s">UD Account</a>. You will find license keys and download links for all your add-ons. Download plugins to your computer and Upload as new plugin on your site.', ud_get_wp_property()->domain ), 'https://www.usabilitydynamics.com/account' ); ?></p>

    </div>

    <div class="col-2 last-feature">

      <h4><?php _e( 'Adding License Keys', ud_get_wp_property()->domain ); ?></h4>

      <p><?php _e( 'Click to activate plugin, you will see link to the Add-ons admin screen. On the Add-ons admin screen, you should see all your installed activated products, with an option to add your license key.', ud_get_wp_property()->domain ) ?></p>

      <p><?php _e( 'To add a license key:', ud_get_wp_property()->domain ); ?></p>

      <ul>
        <li><?php _e( 'copy the license key from your UD Account page or receipt email;', ud_get_wp_property()->domain ); ?></li>
        <li><?php _e( 'paste the license key into input field for your product;', ud_get_wp_property()->domain ); ?></li>
        <li><?php _e( 'save changes;', ud_get_wp_property()->domain ); ?></li>
      </ul>

    </div>

  </div>

</div>