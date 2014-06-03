<?php
/**
 * Name: Tools
 * Group: Settings
 *
 */
?>
<div class="wpp_settings_block">
  <label>
  <?php _e( 'If prompted for your domain name during a premium feature purchase, enter as appears here:', 'wpp' ); ?>
    <input type="text" readonly="true" value="<?php echo $site_domain; ?>" size="<?php echo strlen( $site_domain ) + 10; ?>"/>
  </label>
</div>

<div class="wpp_settings_block">
  <?php _e( "Restore Backup of WP-Property Configuration", 'wpp' ); ?>: <input name="wpp_settings[settings_from_backup]" id="wpp_backup_file" type="file"/>
  <a href="<?php echo wp_nonce_url( "edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup", 'download-wpp-backup' ); ?>"><?php _e( 'Download Backup of Current WP-Property Configuration.', 'wpp' ); ?></a>
</div>

<div class="wpp_settings_block">
  <?php $google_map_localizations = WPP_F::draw_localization_dropdown( 'return_array=true' ); ?>
  <?php _e( 'Revalidate all addresses using', 'wpp' ); ?>
  <b><?php echo $google_map_localizations[ $wp_properties[ 'configuration' ][ 'google_maps_localization' ] ]; ?></b> <?php _e( 'localization', 'wpp' ); ?>.
 <input type="button" value="<?php _e( 'Revalidate', 'wpp' ); ?>" id="wpp_ajax_revalidate_all_addresses">
</div>

<div class="wpp_settings_block"><?php printf( __( 'Enter in the ID of the %1s you want to look up, and the class will be displayed below.', 'wpp' ), WPP_F::property_label( 'singular' ) ) ?>
  <input type="text" id="wpp_property_class_id"/>
  <input type="button" value="<?php _e( 'Lookup', 'wpp' ) ?>" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden"><?php _e( 'Cancel', 'wpp' ) ?></span>
  <pre id="wpp_ajax_property_result" class="wpp_class_pre hidden"></pre>
</div>

<div class="wpp_settings_block"><?php printf( __( 'Get %1s image data.', 'wpp' ), WPP_F::property_label( 'singular' ) ) ?>
  <label for="wpp_image_id"><?php printf( __( '%1s ID:', 'wpp' ), WPP_F::property_label( 'singular' ) ) ?></label>
  <input type="text" id="wpp_image_id"/>
  <input type="button" value="<?php _e( 'Lookup', 'wpp' ) ?>" id="wpp_ajax_image_query"> <span id="wpp_ajax_image_query_cancel" class="wpp_link hidden"><?php _e( 'Cancel', 'wpp' ) ?></span>
  <pre id="wpp_ajax_image_result" class="wpp_class_pre hidden"></pre>
</div>

<div class="wpp_settings_block">
  <?php _e( 'Look up the <b>$wp_properties</b> global settings array.  This array stores all the default settings, which are overwritten by database settings, and custom filters.', 'wpp' ) ?>
  <input type="button" value="<?php _e( 'Show $wp_properties', 'wpp' ) ?>" id="wpp_show_settings_array"> <span id="wpp_show_settings_array_cancel" class="wpp_link hidden"><?php _e( 'Cancel', 'wpp' ) ?></span>
  <pre id="wpp_show_settings_array_result" class="wpp_class_pre hidden"><?php print_r( $wp_properties ); ?></pre>
</div>

<div class="wpp_settings_block">
  <?php _e( 'Clear WPP Cache. Some shortcodes and widgets use cache, so the good practice is clear it after widget, shortcode changes.', 'wpp' ) ?>
  <input type="button" value="<?php _e( 'Clear Cache', 'wpp' ) ?>" id="wpp_clear_cache">
</div>

<div class="wpp_settings_block"><?php printf( __( 'Set all %1s to same %2s type:', 'wpp' ), WPP_F::property_label( 'plural' ), WPP_F::property_label( 'singular' ) ) ?>
  <select id="wpp_ajax_max_set_property_type_type">
  <?php foreach( $wp_properties[ 'property_types' ] as $p_slug => $p_label ) { ?>
    <option value="<?php echo $p_slug; ?>"><?php echo $p_label; ?></option>
  <?php } ?>
  <input type="button" value="<?php _e( 'Set', 'wpp' ) ?>" id="wpp_ajax_max_set_property_type">
  <pre id="wpp_ajax_max_set_property_type_result" class="wpp_class_pre hidden"></pre>
</div>

<div class="wpp_settings_block">
  <?php if( function_exists( 'memory_get_usage' ) ): ?>
    <?php _e( 'Memory Usage:', 'wpp' ); ?> <?php echo round( ( memory_get_usage() / 1048576 ), 2 ); ?> megabytes.
  <?php endif; ?>
  <?php if( function_exists( 'memory_get_peak_usage' ) ): ?>
    <?php _e( 'Peak Memory Usage:', 'wpp' ); ?> <?php echo round( ( memory_get_peak_usage() / 1048576 ), 2 ); ?> megabytes.
  <?php endif; ?>
</div>

<?php do_action( 'wpp_settings_help_tab', $wp_properties ); ?>

<?php do_settings_fields( get_current_screen()->id, 'tools' ); ?>
