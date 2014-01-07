<div class="wrap" id="wpp-settings">

  <div id="wpp-header"<?php if( $is_connected ) : ?> class="small"<?php endif; ?>>
    <div id="wpp-clouds">
      <?php if( $is_connected ) : ?>
        <div id="wpp-disconnectors">
        <?php if( current_user_can( 'wpp_disconnect' ) ) : ?>
          <div id="wpp-disconnect" class="wpp-disconnect">
          <a href="<?php echo wp_nonce_url( Jetpack::admin_url( 'action=disconnect' ), 'wpp-disconnect' ); ?>"><div class="deftext"><?php _e( 'Connected to WordPress.com', 'wpp' ); ?></div><div class="hovertext"><?php _e( 'Disconnect from WordPress.com', 'wpp' ) ?></div></a>
        </div>
        <?php endif; ?>
          <?php if( $is_user_connected && !$is_master_user ) : ?>
            <div id="wpp-unlink" class="wpp-disconnect">
          <a href="<?php echo wp_nonce_url( Jetpack::admin_url( 'action=unlink' ), 'wpp-unlink' ); ?>"><div class="deftext"><?php _e( 'User linked to WordPress.com', 'wpp' ); ?></div><div class="hovertext"><?php _e( 'Unlink user from WordPress.com', 'wpp' ) ?></div></a>
        </div>
          <?php endif; ?>
      </div>
      <?php endif; ?>
      <h3><?php _e( 'Jetpack by WordPress.com', 'wpp' ) ?></h3>
      <?php if( !$is_connected ) : ?>
        <div id="wpp-notice">
        <p><?php _e( 'Jetpack supercharges your self-hosted WordPress site with the awesome cloud power of WordPress.com.', 'wpp' ); ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div id="message" class="updated wpp-message wpp-connect" style="display:block !important;">
    <div id="wpp-dismiss" class="wpp-close-button-container">
      <a class="wpp-close-button" href="?page=wpp&wpp-notice=dismiss" title="<?php _e( 'Dismiss this notice.', 'wpp' ); ?>"><?php _e( 'Dismiss this notice.', 'wpp' ); ?></a>
    </div>
    <div class="wpp-wrap-container">
      <div class="wpp-text-container">
        <h4>
          <p><?php _e( 'To enable all of the Jetpack features you&#8217;ll need to connect your website to WordPress.com using the button to the right. Once you&#8217;ve made the connection you&#8217;ll activate all the delightful features below.', 'wpp' ) ?></p>
        </h4>
      </div>
      <div class="wpp-install-container">
        <p class="submit"><a href="" class="button-connector" id="wpcom-connect"><?php _e( 'Connect to WordPress.com', 'wpp' ); ?></a></p>
      </div>
    </div>
  </div>

</div>