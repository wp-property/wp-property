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

    <p id="wpp_plugins_ajax_response" class="hidden"></p>

  <div class="wpp_settings_block">
      <input type="button" value="<?php _e( 'Check Updates', 'wpp' ); ?>" id="wpp_ajax_check_plugin_updates"/>
      <?php _e( 'to download, or update, all premium features purchased for this domain.', 'wpp' ); ?>
    </div>

    <?php /* if( get_option('ud_api_key') ) { ?>
    <div class="wpp_settings_block">
      <label><?php _e('If a feature or service requires an API Key, you may change it here:','wpp');?>
      <input size="70" type="text" readonly="true" value="<?php echo get_option('ud_api_key'); ?>" />
      </label>
    </div>
    <?php } */
    ?>

  <?php if ( count( $wp_properties[ 'available_features' ] ) > 0 ): ?>
    <div id="tab_plugins">

      <table id="wpp_premium_feature_table wpp-table">
      <?php foreach ( $wp_properties[ 'available_features' ] as $plugin_slug => $plugin_data ): ?>
        <?php if( $plugin_slug == 'class_admin_tools' ) continue; ?>

        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][title]" value="<?php echo $plugin_data[ 'title' ]; ?>"/>
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][tagline]" value="<?php echo $plugin_data[ 'tagline' ]; ?>"/>
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][image]" value="<?php echo $plugin_data[ 'image' ]; ?>"/>
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $plugin_data[ 'description' ]; ?>"/>

        <?php $installed = UsabilityDynamics\WPP\Utility::check_premium( $plugin_slug ); ?>
        <?php $active = ( @$wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] != 'false' ? true : false ); ?>

        <?php if ( $installed ): ?>
          <?php /* Do this to preserve settings after page save. */ ?>
          <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][disabled]" value="<?php echo $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ]; ?>"/>
          <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][name]" value="<?php echo $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'name' ]; ?>"/>
          <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][version]" value="<?php echo $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'version' ]; ?>"/>
          <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'description' ]; ?>"/>
        <?php endif; ?>


        <tr class="wpp_premium_feature_block">

          <td valign="top" class="wpp_premium_feature_image">
            <a href="http://usabilitydynamics.com/products/wp-property/"><img src="<?php echo $plugin_data[ 'image' ]; ?>"/></a>
          </td>

          <td valign="top">
            <div class="wpp_box">
            <div class="wpp_box_header">
              <strong><?php echo $plugin_data[ 'title' ]; ?></strong>
              <p><?php echo $plugin_data[ 'tagline' ]; ?>
                <a href="https://usabilitydynamics.com/products/wp-property/premium/?wp_checkout_payment_domain=<?php echo $this_domain; ?>"><?php _e( '[purchase feature]', 'wpp' ) ?></a>
              </p>
            </div>
            <div class="wpp_box_content">
              <p><?php echo $plugin_data[ 'description' ]; ?></p>

            </div>

            <div class="wpp_box_footer clearfix">
              <?php if ( $installed ) { ?>

                <div class="alignleft">
                <?php

                if ( $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_higher_wpp_version' ] == 'true' ) {
                  printf( __( 'This feature is disabled because it requires WP-Property %1$s or higher.', 'wpp' ), $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum_wpp_version' ] );
                } else {
                  echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[installed_features][$plugin_slug][disabled]&label=" . __( 'Disable plugin.', 'wpp' ), $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] );

                  ?>
                  </div>
                  <div class="alignright"><?php _e( 'Feature installed, using version', 'wpp' ) ?> <?php echo $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'version' ]; ?>
                    .</div>
                <?php
                }
              } else {
                $pr_link = 'https://usabilitydynamics.com/products/wp-property/premium/';
                echo sprintf( __( 'Please visit <a href="%s">UsabilityDynamics.com</a> to purchase this feature.', 'wpp' ), $pr_link );
              } ?>
            </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </table>

  </div>
  <?php endif; ?>


</div>