<?php
/**
 * Name: Modules
 * Group: Settings
 *
 */
?>
<table id="wpp_premium_feature_table">
  <?php foreach( (array) $wp_properties[ 'available_features' ] as $plugin_slug => $plugin_data ): ?>
    <?php if( $plugin_slug == 'class_admin_tools' ) continue; ?>

    <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][title]" value="<?php echo $plugin_data[ 'title' ]; ?>"/>
    <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][tagline]" value="<?php echo $plugin_data[ 'tagline' ]; ?>"/>
    <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][image]" value="<?php echo $plugin_data[ 'image' ]; ?>"/>
    <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $plugin_data[ 'description' ]; ?>"/>

    <?php $installed = WPP_F::check_premium( $plugin_slug ); ?>
    <?php $active = ( @$wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] != 'false' ? true : false ); ?>

    <?php if( $installed ): ?>
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
            <a href="https://usabilitydynamics.com/products/wp-property/premium/?wp_checkout_payment_domain=<?php echo $site_domain; ?>"><?php _e( '[purchase feature]', 'wpp' ) ?></a>
          </p>
        </div>
        <div class="wpp_box_content">
          <p><?php echo $plugin_data[ 'description' ]; ?></p>

        </div>

        <div class="wpp_box_footer clearfix">
          <?php if( $installed ) { ?>

            <div class="alignleft">
            <?php

            if( $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_upgrade' ] == 'true' ) {
              printf( __( 'This feature is disabled because it requires WP-Property %1$s or higher.' ), $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum_wpp_version' ] );
            } else {
              echo WPP_F::checkbox( "name=wpp_settings[installed_features][$plugin_slug][disabled]&label=" . __( 'Disable plugin.', 'wpp' ), $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] );

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