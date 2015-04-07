<?php
/**
 * Settings 'Developer' Tab
 * Section 'Advanced'
 */

?>
<h3><?php _e( 'Advanced Options', 'wpp' ); ?></h3>
<ul>
  <li>
    <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][show_ud_log]&label=" . __( 'Show Log.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'show_ud_log' ] ) ? $wp_properties[ 'configuration' ][ 'show_ud_log' ] : false ) ); ?>
    <br/>
    <span class="description"><?php _e( 'The log is always active, but the UI is hidden.  If enabled, it will be visible in the admin sidebar.', 'wpp' ); ?></span>
  </li>
  <li>
    <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][allow_parent_deep_depth]&label=" . __( 'Enable \'Falls Under\' deep depth.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] ) ? $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] : false ) ); ?>
    <br/>
    <span class="description"><?php printf( __( 'Allows to set child %1s as parent.', 'wpp' ), WPP_F::property_label( 'singular' ) )  ?></span>
  </li>
  <li>
    <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][disable_automatic_feature_update]&label=" . __( 'Disable automatic feature updates.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'disable_automatic_feature_update' ] ) ? $wp_properties[ 'configuration' ][ 'disable_automatic_feature_update' ] : false ) ); ?>
    <br/>
    <span class="description"><?php _e( 'If disabled, feature updates will not be downloaded automatically.', 'wpp' ); ?></span>
  </li>
  <li>
    <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][disable_wordpress_postmeta_cache]&label=" . __( 'Disable WordPress update_post_caches() function.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] ) ? $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] : false ) ); ?>
    <br/>
    <span class="description"><?php printf( __('This may solve Out of Memory issues if you have a lot of %1s.','wpp'), WPP_F::property_label( 'plural' )); ?></span>
  </li>
  <li>
    <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][developer_mode]&label=" . __( 'Enable developer mode - some extra information displayed via Firebug console.', 'wpp' ), ( isset( $wp_properties[ 'configuration' ][ 'developer_mode' ] ) ? $wp_properties[ 'configuration' ][ 'developer_mode' ] : false ) ); ?>
    <br/>
  </li>

</ul>