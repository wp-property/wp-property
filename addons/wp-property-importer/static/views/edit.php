<?php
/**
 * Edit Schedule template
 *
 */

global $wp_properties, $wpp_property_import;

//echo "<pre>"; print_r( $settings ); echo "</pre>";

$settings = wp_parse_args( $settings, array(
  'name' => '',
  'hash' => '',
  'url' => '',
  'alt_cron_enabled' => 'false',
  'alt_cron_run' => '86400',
  'alt_cron_run_custom' => '',
  'alt_cron_sys_command' => 'false',
  'source_type' => 'xml',
  'postauth' => '',
  'google_username' => '',
  'google_password' => '',
  'google_extra_query' => '',
  'rets_username' => '',
  'rets_password' => '',
  'rets_resource' => '',
  'rets_class' => '',
  'rets_pk' => '',
  'rets_photo' => '',
  'rets_agent' => '',
  'rets_agent_password' => '',
  'rets_query' => '',
  'root_element' => '',
  'worksheet_id' => '',
  'worksheet_query_url' => '',
  'spreadsheet_key' => '',
  'property_type' => '',
  'limit_scanned_properties' => '',
  'limit_properties' => '',
  'num_worker_threads' => '',
  'minimum_images' => '',
  'limit_images' => '',
  'min_image_width' => '',
  'min_image_height' => '',
  'reimport_delay' => '',
  'automatically_feature_image_enabled' => '',
  'automatically_feature_image' => '',
  'remove_non_existant' => '',
  'remove_images' => '',
  'skip_images' => '',
  'send_email_updates' => '',
  'remove_all_from_this_source' => '',
  'remove_all_before_import' => '',
  'fix_caps' => '',
  'force_remove_formatting' => '',
  'automatically_load_slideshow_images' => '',
  'revalidate_addreses_on_completion' => '',
  'log_detail' => '',
  'show_sql_queries' => '',
  'run_system_command_cron' => '',
  'map' => array(),
) );

$alt_cron_timers = array(
  ( 12 * HOUR_IN_SECONDS ) => __( 'twice per day', ud_get_wpp_importer()->domain ),
  ( 24 * HOUR_IN_SECONDS ) => __( 'daily', ud_get_wpp_importer()->domain ),
  ( 24 * 7 * HOUR_IN_SECONDS ) => __( 'weekly', ud_get_wpp_importer()->domain ),
);


$_taxonomies = $wp_properties[ 'taxonomies' ];
$_attributes = WPP_F::get_total_attribute_array();

// sort attributes by value
// asort( $_attributes );

// Sort taxonomies by label
//usort($_taxonomies, function( $a, $b ) {
//  if ($a['label'] == $b['label']) return 0;
//  return ($a['label'] < $b['label']) ? -1 : 1;
//});

?>
<style type="text/css">
  <?php if( $settings[ 'source_type' ] == "gs" ): ?>
  div#wpp_property_import_ajax div.wpp_property_import_setup .wpp_property_import_gs_options.wpp_i_advanced_source_settings

  {
    display: block
  ;
  }
  <?php elseif( $settings[ 'source_type' ] == "rets" ): ?>
  div#wpp_property_import_ajax div.wpp_property_import_setup .wpp_property_import_rets_options.wpp_i_advanced_source_settings

  {
    display: block
  ;
  }
  div#wpp_property_import_ajax div.wpp_property_import_setup .wpp_property_import_rets_options.wpp_i_advanced_source_settings

  {
    display: none
  ;
  }
  <?php endif; ?>
</style>
<div class="wpp_property_import_setup" import_type="<?php echo( $settings[ 'source_type' ] ? $settings[ 'source_type' ] : 'xml' ); ?>" >

  <form id="wpp_property_import_setup" action="#" autocomplete="off">

    <table class="form-table">
      <tbody>
      <tr>
        <th>
          <label for="wpp_property_import_name"><?php _e( 'Import name', ud_get_wpp_importer()->domain ); ?></label>
        </th>
        <td>
          <input class="regular-text wpp_property_import_name"  id="wpp_property_import_name" name="wpp_property_import[name]" type="text" value="<?php echo $settings[ 'name' ]; ?>"/>
        </td>
      </tr>

      <tr class="step_one">
        <th>
          <label for="wpp_property_import_remote_url"><?php _e( 'Source', ud_get_wpp_importer()->domain ); ?></label>
        </th>
        <td>
          <ul class="wppi_source_option_preview_wrapper">
            <li>
              <label for="wpp_property_import_remote_url"><?php _e( 'URL', ud_get_wpp_importer()->domain ); ?></label>
              <input class="regular-text wpp_property_import_remote_url" name="wpp_property_import[url]"  type="text" id="wpp_property_import_remote_url" value="<?php echo esc_attr( $settings[ 'url' ] ); ?>" />

              <label for="wpp_property_import_source_type"><?php _e( 'Type:', ud_get_wpp_importer()->domain ); ?></label>
              <select id="wpp_property_import_source_type" name="wpp_property_import[source_type]">
                <option <?php selected( $settings[ 'source_type' ], 'xml' ); ?> value="xml"><?php _e( "XML / JSON", ud_get_wpp_importer()->domain ); ?></option>
                <option <?php selected( $settings[ 'source_type' ], 'csv' ); ?> value="csv"><?php _e( "CSV", ud_get_wpp_importer()->domain ); ?></option>
                <option <?php selected( $settings[ 'source_type' ], 'gs' ); ?>   value="gs"><?php _e( "Google Spreadsheet", ud_get_wpp_importer()->domain ); ?></option>
                <option <?php selected( $settings[ 'source_type' ], ud_get_wpp_importer()->domain ); ?>   value="wpp"><?php _e( "WP-Property Feed", ud_get_wpp_importer()->domain ); ?></option>
                <option <?php selected( $settings[ 'source_type' ], 'rets' ); ?>   value="rets"><?php _e( "RETS", ud_get_wpp_importer()->domain ); ?></option>
              </select>
              <span id="wpp_property_import_source_status" class="button"></span>
            </li>
            <li class="wpp_i_source_feedback"></div>
</ul>

<ul class="wpp_something_advanced_wrapper wppi_source_option_preview_wrapper">

  <li class="wpp_i_source_specific wpp_i_advanced_source_settings" wpp_i_source_type="xml">
    <input type="checkbox" id="wpp_property_import_use_postauth_checkbox" name="wpp_property_import[postauth]" <?php echo checked( 'on', $settings[ 'postauth' ] ); ?>/>
    <label class="description" for="wpp_property_import_use_postauth_checkbox"><?php echo __( 'Send GET variables as POST data.', ud_get_wpp_importer()->domain ); ?></label>
  </li>
  <?php /*
        <li class="wpp_i_source_specific wpp_i_advanced_source_settings" wpp_i_source_type="csv">
          <input type="checkbox" id="wpp_i_csv_no_headers" name="wpp_property_import[csv][no_headers]" <?php echo checked( 'on', $settings['csv']['no_headers'] ); ?>/>
          <label class="description" for="wpp_i_csv_no_headers"><?php echo __( 'First line <b>does not</b> contain headers.',ud_get_wpp_importer()->domain ); ?></label>
        </li>
      */ ?>
  <li class="wpp_i_source_specific wpp_property_import_gs_options wpp_i_advanced_source_settings" wpp_i_source_type="gs">
    <input type="text" class="regular-text"  name="wpp_property_import[google_username]" id='wpp_property_import_username'  value="<?php echo $settings[ 'google_username' ] ?>" />
    <label for="wpp_property_import_username"><?php _e( 'Google Username', ud_get_wpp_importer()->domain ); ?></label>
  </li>

  <li class="wpp_i_source_specific wpp_property_import_gs_options wpp_i_advanced_source_settings" wpp_i_source_type="gs">
    <input type="password" class="regular-text"  name="wpp_property_import[google_password]" id='wpp_property_import_password' value="<?php echo $settings[ 'google_password' ] ?>" />
    <label for="wpp_property_import_password"><?php _e( 'Google Password', ud_get_wpp_importer()->domain ); ?></label>
  </li>

  <li class="wpp_i_source_specific wpp_property_import_gs_options wpp_i_advanced_source_settings" wpp_i_source_type="gs">
    <input type="text" class="regular-text"  name="wpp_property_import[spreadsheet_key]" value="<?php echo $settings[ 'spreadsheet_key' ] ?>" />
    <label><?php _e( 'Spreadsheet Key', ud_get_wpp_importer()->domain ); ?></label>
  </li>

  <li class="wpp_i_source_specific wpp_property_import_gs_options wpp_i_advanced_source_settings" wpp_i_source_type="gs">
    <input type="text" class="regular-text"  name="wpp_property_import[worksheet_id]" id='wpp_property_import_username'  value="<?php echo $settings[ 'worksheet_id' ] ?>" />
    <label for="wpp_property_import_username"><?php _e( 'Worksheet ID', ud_get_wpp_importer()->domain ); ?></label>
  </li>

  <li class="wpp_i_source_specific wpp_property_import_gs_options wpp_i_advanced_source_settings" wpp_i_source_type="gs">
    <input type="text" class="regular-text"  name="wpp_property_import[worksheet_query_url]" value="<?php echo $settings[ 'worksheet_query_url' ] ?>" />
    <label><?php _e( 'Query URL', ud_get_wpp_importer()->domain ); ?></label>
  </li>

  <li class="wpp_i_source_specific wpp_property_import_gs_options wpp_i_advanced_source_settings"  wpp_i_source_type="gs">
    <input type="text" class="regular-text"  name="wpp_property_import[google_extra_query]" id='wpp_property_import_extra_query'  value="<?php echo $settings[ 'google_extra_query' ] ?>" />
    <label for="wpp_property_import_extra_query"><?php _e( 'Google Extra Query Vars', ud_get_wpp_importer()->domain ); ?></label><br />
    <span class="description"><?php _e( 'See the <a href="http://code.google.com/apis/spreadsheets/data/3.0/reference.html#ListParameters" target="_blank">Google Spreadsheet API docs</a> for the format of this field ( should be name value pairs, without the beginning "?" )', ud_get_wpp_importer()->domain ); ?></span>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options"  wpp_i_source_type="rets">
    <input type="text" class="regular-text wpp_required" name="wpp_property_import[rets_username]" id='wpp_property_import_rets_username' autocomplete='off' value="<?php echo $settings[ 'rets_username' ] ?>" />
    <label for="wpp_property_import_rets_username"><?php _e( 'RETS Username.', ud_get_wpp_importer()->domain ); ?></label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options"  wpp_i_source_type="rets">
    <input type="password" class="regular-text wpp_required"  name="wpp_property_import[rets_password]" id='wpp_property_import_rets_password' autocomplete='off' value="<?php echo $settings[ 'rets_password' ] ?>" />
    <label for="wpp_property_import_rets_password"><?php _e( 'RETS Password.', ud_get_wpp_importer()->domain ); ?></label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options wpp_i_advanced_source_settings"  wpp_i_source_type="rets">
    <input type="text" class="regular-text" placeholder="Property" name="wpp_property_import[rets_resource]" id='wpp_property_import_rets_resource'  value="<?php echo $settings[ 'rets_resource' ] ?>" />
    <label for="wpp_property_import_rets_class"><?php _e( 'Property Resource.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'Default is "Property"', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options"  wpp_i_source_type="rets">
    <input type="text" class="regular-text wpp_required"  name="wpp_property_import[rets_class]" id='wpp_property_import_rets_class'  value="<?php echo $settings[ 'rets_class' ] ?>" />
    <label for="wpp_property_import_rets_class"><?php _e( 'Property Resource Class.', ud_get_wpp_importer()->domain ); ?> </label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options" wpp_i_source_type="rets">
    <input type="text" class="regular-text wpp_required"  placeholder="ListingKey" name="wpp_property_import[rets_pk]" id='wpp_property_import_rets_pk'  value="<?php echo $settings[ 'rets_pk' ] ?>" />
    <label for="wpp_property_import_rets_pk"><?php _e( 'Primary Key for Resource.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'Also referred to as "Key Field". Default is "ListingKey"', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options wpp_i_advanced_source_settings" wpp_i_source_type="rets">
    <input type="text" class="regular-text"  placeholder="Photo" name="wpp_property_import[rets_photo]" id='wpp_property_import_rets_photo'  value="<?php echo $settings[ 'rets_photo' ] ?>" />
    <label for="wpp_property_import_rets_photo"><?php _e( 'Photo Object.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'Default is "Photo"', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options wpp_i_advanced_source_settings" wpp_i_source_type="rets">
    <input type="text" class="regular-text" placeholder="WP-Property/1.0" name="wpp_property_import[rets_agent]" id='wpp_property_import_rets_agent'  value="<?php echo $settings[ 'rets_agent' ] ?>" />
    <label for="wpp_property_import_rets_agent"><?php _e( 'User-Agent String.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'May be required by your RETS', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options wpp_i_advanced_source_settings" wpp_i_source_type="rets">
    <input type="text" class="regular-text"  name="wpp_property_import[rets_agent_password]" id='wpp_property_import_rets_agent_password'  value="<?php echo $settings[ 'rets_agent_password' ] ?>" />
    <label for="wpp_property_import_rets_agent_password"><?php _e( 'User-Agent Password.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'May be required by your RETS', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>
  <li class="wpp_i_source_specific wpp_property_import_rets_options wpp_i_advanced_source_settings" wpp_i_source_type="rets">
    <?php /** @todo Remove inline styling - adding for now as quick fix -- williams@UD */ ?>
    <select style="width:25em;" name="wpp_property_import[rets_version]" id='wpp_property_import_rets_version'>
      <?php foreach( array( 'RETS/1.0' => '1.0', 'RETS/1.5' => '1.5', 'RETS/1.7' => '1.7', 'RETS/1.7.2' => '1.7.2', ) as $key => $option ) { ?>
        <option value='<?php echo $key; ?>' <?php echo( $settings[ 'rets_version' ] == $key ? 'selected="selected"' : '' ); ?>><?php echo $option; ?></option>
      <?php } ?>
    </select>
    <label for="wpp_property_import_rets_version"><?php _e( 'RETS Version.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'Version is set by your RETS provider.', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>
  <li class="wpp_i_source_specific wpp_i_advanced_source_settings wpp_property_import_rets_options"  wpp_i_source_type="rets">
    <input type="checkbox" id="wpp_rets_use_post_method" name="wpp_property_import[rets_use_post_method]" value="true" <?php echo checked( 'true', $settings[ 'rets_use_post_method' ] ); ?>/>
    <label for="wpp_rets_use_post_method"><?php _e( 'Use POST method for RETS provider.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'Certain providers require the POST method be used when making requests.', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>

  <li class="wpp_i_source_specific wpp_property_import_rets_options"  wpp_i_source_type="rets">
    <input type="text" class="regular-text" placeholder="(ListingStatus=|Active)" style="width: 35em;" name="wpp_property_import[rets_query]" id='wpp_property_import_rets_query'  value="<?php echo $settings[ 'rets_query' ] ?>" />
    <label for="wpp_property_import_rets_query"><?php _e( 'Property Query.', ud_get_wpp_importer()->domain ); ?> <span class="description"><?php _e( 'Accepts <a href="https://www.flexmls.com/support/rets/tutorials/dmql/" target="_blank">DMQL</a> - Default is "(ListingStatus=|Active)"', ud_get_wpp_importer()->domain ); ?></span></label>
  </li>

  <li class="wpp_show_advanced_wrapper">
    <span class="wpp_show_advanced" advanced_option_class="wpp_i_advanced_source_settings" show_type_source="wpp_property_import_source_type" show_type_element_attribute="wpp_i_source_type"><?php _e( 'Toggle Advanced Source Options', ud_get_wpp_importer()->domain ); ?></span>
  </li>
</ul>

<ul class="wppi_source_option_preview_wrapper">
  <li>
    <label for="wpp_property_import_choose_root_element" class="description"><?php echo __( 'Root XPath Query:', ud_get_wpp_importer()->domain ); ?></label>
    <input type='text' id="wpp_property_import_choose_root_element" name="wpp_property_import[root_element]" value="<?php echo esc_attr( $settings[ 'root_element' ] ); ?>" class="wpp_property_import_choose_root_element"/>
    <span class="wpp_link wpp_toggle_contextual_help" wpp_scroll_to="#tab-link-xpath-query-to-property-elements"><?php _e( 'What is this?', ud_get_wpp_importer()->domain ); ?></span>
  </li>
</ul>

<ul class="wppi_source_option_preview_wrapper">
  <li>
    <ul>
      <li>
        <input type="button" id="wpp_i_preview_raw_data" value="<?php _e( 'Preview Raw Data', ud_get_wpp_importer()->domain ); ?>" class="button-secondary" >
        <span class="wpp_i_preview_raw_data_result"></span>
        <span class="wpp_i_close_preview hidden wpp_link"><?php _e( 'Close Preview', ud_get_wpp_importer()->domain ); ?></span>
      </li>
      <li>
        <div class="wppi_raw_preview_result"></div>
      </li>
    </ul>
  </li>
</ul>


</td>
</tr>

<tr data-feature="wp-property-importer-alternative-cron">
  <th>
    <label for="wpp_property_import_alternative_cron"><?php _e( 'Alternative Cron', ud_get_wpp_importer()->domain ); ?></label>
  </th>
  <td>
    <input type="checkbox" id="wpp_property_import_alt_cron_enabled" name="wpp_property_import[alt_cron_enabled]" value="true" <?php echo checked( 'true', $settings[ 'alt_cron_enabled' ] ); ?>/>
    <label for="wpp_property_import_alt_cron_enabled"><?php _e( 'Enabled.', ud_get_wpp_importer()->domain ); ?></label>
    <ul class="wpp_property_import_alt_cron_options">
      <li>
        <select  name="wpp_property_import[alt_cron_run]" id="wpp_property_import_alt_cron_run">
          <?php foreach( $alt_cron_timers as $k => $v ): ?>
            <option value="<?php echo $k; ?>" <?php selected( $k, $settings[ 'alt_cron_run' ] ); ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
        <label for="wpp_property_import_alt_cron_run"><?php _e( 'How often import should be run.', ud_get_wpp_importer()->domain ); ?></label>

        <?php if( isset( $settings['schedule'] ) && isset( $settings['schedule']['uuid'] ) ) { ?>
          <input type="hidden" name="wpp_property_import[schedule][uuid]" value="<?php echo $settings['schedule']['uuid']; ?>" />
        Job schedule created. View detail <a target="_blank" href="https://usabilitydynamics-node-product-api-staging.c.rabbit.ci/property/importer/v1/scheduler/job/<?php echo $settings['schedule']['uuid']; ?>">API status.</a>

        <?php } ?>
      </li>
      <li>
        <input type="text" id="wpp_property_import_alt_cron_run_custom" name="wpp_property_import[alt_cron_run_custom]" value="<?php echo( empty( $settings[ 'alt_cron_run_custom' ] ) ? '' : $settings[ 'alt_cron_run_custom' ] ); ?>"/>
        <label class="description" for="wpp_property_import_alt_cron_run"><?php _e( 'Custom time for Cron running. Must be set in seconds ( e.g.: one day equal 86400 sec ). If empty, default timer above will be used.', ud_get_wpp_importer()->domain ); ?></label>
      </li>
      <?php if( !defined( 'XMLI_SYSTEM_COMMAND_CRON' ) ) : ?>
        <li class="hidden">
          <input type="checkbox" id="wpp_property_import_alt_cron_sys_command" name="wpp_property_import[alt_cron_sys_command]" value="true" <?php echo checked( 'true', $settings[ 'alt_cron_sys_command' ] ); ?> />
          <label for="wpp_property_import_alt_cron_sys_command">
            <?php echo __( 'Run system command cron instead of HTTP request.', ud_get_wpp_importer()->domain ); ?>
            <span class="description"><?php echo __( 'Attention, enable the option only if Cron is not being run.', ud_get_wpp_importer()->domain ); ?></span>
          </label>
        </li>
      <?php endif; ?>
    </ul>

  </td>
</tr>

<tr>
  <th>
    <label for="wpp_property_import_property_type"><?php _e( 'Default Property Type', ud_get_wpp_importer()->domain ); ?></label>
  </th>
  <td>
    <select  name="wpp_property_import[property_type]" id="wpp_property_import_property_type">
      <?php foreach( $wp_properties[ 'property_types' ] as $property_slug => $property_title ): ?>
        <option value="<?php echo $property_slug; ?>" <?php selected( $property_slug, $settings[ 'property_type' ] ); ?>><?php echo $property_title; ?></option>
      <?php endforeach; ?>
    </select>
    <span class="description"><?php _e( 'Will be defaulted to if no xPath rule exists for the "Property Type".', ud_get_wpp_importer()->domain ); ?></span>
  </td>
</tr>

<tr>

  <th>
    <label for="wpp_property_import_settings"><?php _e( 'Advanced Options', ud_get_wpp_importer()->domain ); ?></label>
  </th>
  <td>

    <input type="hidden" name="wpp_property_import[is_scheduled]" value="on" />

    <ul class="wpp_property_import_settings hidden">

      <li class="wpp_xi_advanced_setting">
        <label class="description" for="wpp_property_limit_scanned_properties"><?php echo __( '<b>Pre-QC Limit:</b> Limit import to the first', ud_get_wpp_importer()->domain ); ?>
          <input type="text"  class="wpp_xmli_enforce_integer"  id="wpp_property_limit_scanned_properties" name="wpp_property_import[limit_scanned_properties]" value="<?php echo( empty( $settings[ 'limit_scanned_properties' ] ) ? '' : $settings[ 'limit_scanned_properties' ] ); ?>"/>
          <?php echo __( 'properties in the feed.', ud_get_wpp_importer()->domain ); ?>
          <span wpp_scroll_to="h3.limit_import" class="wpp_link wpp_toggle_contextual_help"><?php _e( 'More about limits.', ud_get_wpp_importer()->domain ); ?></span>
        </label>
      </li>
      <li class="wpp_xi_advanced_setting">
        <label class="description"><?php _e( '<b>Post-QC Limit:</b> Limit import to the first', ud_get_wpp_importer()->domain ); ?>
          <input type="text"   class="wpp_xmli_enforce_integer"  id="wpp_property_limit_properties" name="wpp_property_import[limit_properties]" value="<?php echo( empty( $settings[ 'limit_properties' ] ) ? '' : $settings[ 'limit_properties' ] ); ?>"/>
          <?php echo __( 'created properties that have passed quality standards.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <label class="description"><?php _e( 'Number of image importing threads spawned', ud_get_wpp_importer()->domain ); ?>
          <input type="text"   class="wpp_xmli_enforce_integer"  id="wpp_property_limit_properties" name="wpp_property_import[num_worker_threads]" value="<?php echo( empty( $settings[ 'num_worker_threads' ] ) ? '' : $settings[ 'num_worker_threads' ] ); ?>"/>
          <span class="description"><?php _e( 'Default is 10. Not recommended to increase the number.', ud_get_wpp_importer()->domain ); ?></span>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <?php printf(
          __( 'Only import images that are over %1spx in width, and %2spx in height.', ud_get_wpp_importer()->domain ),
          '<input type="text" value="' . $settings[ "min_image_width" ] . '" name="wpp_property_import[min_image_width]" />',
          '<input type="text" value="' . $settings[ "min_image_height" ] . '"  name="wpp_property_import[min_image_height]" />'
        );
        ?>
        <span class="description"><?php _e( 'Minimum sizes are ignored if blank.', ud_get_wpp_importer()->domain ); ?></span>
      </li>

      <li class="wpp_xi_advanced_setting" data-exclude_type="rets">
        <label class="description"><?php _e( 'Imported properties must have at least ', ud_get_wpp_importer()->domain ); ?>
          <input type="text" id="wpp_i_minimum_images" class="wpp_xmli_enforce_integer" name="wpp_property_import[minimum_images]" value="<?php echo( empty( $settings[ 'minimum_images' ] ) ? '' : $settings[ 'minimum_images' ] ); ?>"/><?php _e( ' valid image(s).', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <label class="description"><?php _e( 'Imported properties must have no more than ', ud_get_wpp_importer()->domain ); ?>
          <input type="text" id="wpp_i_limit_images" name="wpp_property_import[limit_images]" value="<?php echo( empty( $settings[ 'limit_images' ] ) ? '' : $settings[ 'limit_images' ] ); ?>"/>
          <?php _e( ' valid image(s).', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <label class="description" for="wpp_property_reimport_delay"><?php echo __( 'Do not update properties that have been imported less than ', ud_get_wpp_importer()->domain ); ?>
          <input type="text" id="wpp_property_reimport_delay" name="wpp_property_import[reimport_delay]" value="<?php echo( empty( $settings[ 'reimport_delay' ] ) ? 0 : $settings[ 'reimport_delay' ] ); ?>"/>
          <?php echo __( 'hour(s) ago.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <label class="description">
          <?php
          $drop_down = '
          <select name="wpp_property_import[automatically_feature_image]">
            <option value="first" ' . selected( 'first', $settings[ 'automatically_feature_image' ] , false) . '>First</option>
            <option value="last" ' . selected( 'last', $settings[ 'automatically_feature_image' ] , false) . '>Last</option>
          </select>
          ';
          ?>
          <input type="checkbox" name="wpp_property_import[automatically_feature_image_enabled]" value="on" <?php checked( 'on', $settings[ 'automatically_feature_image_enabled' ] ); ?> />
          <?php printf( __( "Automatically set the %s image as the thumbnail.", ud_get_wpp_importer()->domain ), $drop_down ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_property_import_remove_non_existant_properties" name="wpp_property_import[remove_non_existant]" value="on"<?php echo checked( 'on', $settings[ 'remove_non_existant' ] ); ?> />
        <label class="description" for="wpp_property_import_remove_non_existant_properties">
          <?php echo __( 'Remove properties that are no longer in source XML from this site\'s database. This can now be done if the the import configuration does not have a Pre-QC or Post-QC Limit.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_property_remove_images" name="wpp_property_import[remove_images]" value="on" <?php echo checked( 'on', $settings[ 'remove_images' ] ); ?>/>
        <label class="description" for="wpp_property_remove_images"><?php echo __( 'When updating an existing property, remove all old images before downloading new ones.', ud_get_wpp_importer()->domain ); ?></label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_property_skip_images" name="wpp_property_import[skip_images]" value="on" <?php echo checked( 'on', $settings[ 'skip_images' ] ); ?>/>
        <label class="description" for="wpp_property_skip_images"><?php echo __( 'Skip images if images already downloaded.', ud_get_wpp_importer()->domain ); ?></label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_send_email_updates" name="wpp_property_import[send_email_updates]" value="on" <?php echo checked( 'on', $settings[ 'send_email_updates' ] ); ?>/>
        <label class="description" for="wpp_send_email_updates">
          <?php printf( __( 'Send email updates to the site admin e-mail address ( %1s ) when import schedules are executed and completed.', ud_get_wpp_importer()->domain ), get_option( 'admin_email' ) ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_property_import_remove_all_from_this_source" name="wpp_property_import[remove_all_from_this_source]" value="on"<?php echo checked( 'on', $settings[ 'remove_all_from_this_source' ] ); ?> />
        <label class="description" for="wpp_property_import_remove_all_from_this_source">
          <?php echo __( 'Remove all properties that were originally imported from this feed on import.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_property_import_remove_all_before_import" name="wpp_property_import[remove_all_before_import]" value="on"<?php echo checked( 'on', $settings[ 'remove_all_before_import' ] ); ?> />
        <label class="description" for="wpp_property_import_remove_all_before_import">
          <?php echo __( 'Completely remove <b>all</b> existing properties prior to import.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_property_fix_caps" name="wpp_property_import[fix_caps]" value="on"<?php echo checked( 'on', $settings[ 'fix_caps' ] ); ?> />
        <label class="description" for="wpp_property_fix_caps">
          <?php echo __( 'Fix strings that are in all caps.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_property_force_remove_formatting" name="wpp_property_import[force_remove_formatting]" value="on"<?php echo checked( 'on', $settings[ 'force_remove_formatting' ] ); ?> />
        <label class="description" for="wpp_property_force_remove_formatting">
          <?php echo __( 'Scan for any formatting tags and strip them out.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <?php if( class_exists( 'class_wpp_slideshow' ) ) { ?>
        <li class="wpp_xi_advanced_setting">
          <input type="checkbox" id="wpp_property_automatically_load_slideshow_images" name="wpp_property_import[automatically_load_slideshow_images]" value="on"<?php echo checked( 'on', $settings[ 'automatically_load_slideshow_images' ] ); ?> />
          <label class="description" for="wpp_property_automatically_load_slideshow_images">
            <?php echo __( 'Automatically load imported images into property slideshow.', ud_get_wpp_importer()->domain ); ?>
          </label>
        </li>
      <?php } ?>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_import_revalidate_addreses_on_completion" name="wpp_property_import[revalidate_addreses_on_completion]" value="on"<?php echo checked( 'on', $settings[ 'revalidate_addreses_on_completion' ] ); ?> />
        <label class="description" for="wpp_import_revalidate_addreses_on_completion">
          <?php echo __( 'Geolocate imported listings.', ud_get_wpp_importer()->domain ); ?>
        </label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <input type="checkbox" id="wpp_import_log_detail" name="wpp_property_import[log_detail]" value="on" <?php echo checked( 'on', $settings[ 'log_detail' ] ); ?> />
        <label class="description" for="wpp_import_log_detail"><?php echo __( 'Enable detailed logging to assist with troubleshooting.', ud_get_wpp_importer()->domain ); ?></label>
      </li>

      <li class="wpp_xi_advanced_setting">
        <label class="description">
          <input type="checkbox" name="wpp_property_import[show_sql_queries]" value="on" <?php echo checked( 'on', $settings[ 'show_sql_queries' ] ); ?> />
          <?php echo __( 'Show SQL Queries and errors.', ud_get_wpp_importer()->domain ); ?></label>
      </li>

      <?php  if( !defined( 'XMLI_SYSTEM_COMMAND_CRON' ) ) : ?>
        <li class="wpp_xi_advanced_setting hidden">
          <input type="checkbox" id="wpp_disable_http_blocking" name="wpp_property_import[run_system_command_cron]" value="on"<?php echo checked( 'on', $settings[ 'run_system_command_cron' ] ); ?> />
          <label class="description" for="run_system_command_cron">
            <?php echo __( 'Run system command cron instead of PHP cron.', ud_get_wpp_importer()->domain ); ?>
            <span class="description"><?php echo __( 'Attention, enable the option only if listings are not being published but added to <b>Draft status</b>.', ud_get_wpp_importer()->domain ); ?></span>
          </label>
        </li>
      <?php endif; ?>

      <?php do_action( 'wpp_import_advanced_options', $settings ); ?>

    </ul>

    <span class="wpp_property_toggle_import_settings wpp_link"><?php _e( 'Toggle Advanced Options', ud_get_wpp_importer()->domain ); ?></span> <span class="wpp_property_toggle_import_settings wpp_xi_advanced_option_counter"></span>

  </td>
</tr>

<tr>
  <th><?php _e( 'Automatic Matching', ud_get_wpp_importer()->domain ); ?></th>
  <td>
    <input type="button" value="<?php _e( 'Automatically Match', ud_get_wpp_importer()->domain ); ?>" class='button' id="wpp_import_auto_match" />
    <span><?php _e( 'This will work for WP-Property exports and imports, but will have mixed results with uniquely formatted feeds.', ud_get_wpp_importer()->domain ); ?></span>
  </td>
</tr>

<tr>
  <th><?php _e( 'Attribute Map', ud_get_wpp_importer()->domain ); ?></th>
  <td>
    <p>
      <?php _e( 'Use XPath rules to setup the paths to the individual XML attributes to match them up with WP-Property attributes.', ud_get_wpp_importer()->domain ); ?>
      <span class="wpp_xi_sort_rules wpp_link"><?php _e( 'Sort Attribute Rules', ud_get_wpp_importer()->domain ); ?></span>.
    </p>
    <table id="wpp_property_import_attribute_mapper" auto_increment="true" class="ud_ui_dynamic_table widefat">
      <thead>
      <tr>
        <th style="width: 5%;"><input type="checkbox" id="check_all"></th>
        <th style="width: 150px;"><?php echo __( 'WP-Property Attribute', ud_get_wpp_importer()->domain ); ?></th>
        <th style="width: 300px;"><?php echo __( 'XPath Rule', ud_get_wpp_importer()->domain ); ?></th>
        <th style="width: auto;"><?php echo __( 'Matches', ud_get_wpp_importer()->domain ); ?></th>
      </tr>
      </thead>
      <tbody>
      <?php $index = 0; ?>
      <?php foreach( $settings[ 'map' ] as $attr ) : ?>
        <?php $index++; ?>
        <?php $attr = wp_parse_args( $attr, array(
          'wpp_attribute' => 'post_title',
          'xpath_rule' => '',
        ) );
        ?>
        <tr class="wpp_dynamic_table_row">
          <td>
            <input type="checkbox" name="wpp_property_import[map][<?php echo( $index ); ?>][check]">
          </td>
          <td>
            <select name="wpp_property_import[map][<?php echo( $index ); ?>][wpp_attribute]" class='wpp_import_attribute_dropdown'>
              <option></option>
              <optgroup label="<?php _e( 'WordPress Attributes', ud_get_wpp_importer()->domain ); ?>">
                <?php foreach( $wpp_property_import[ 'post_table_columns' ] as $column_name => $column_label ) { ?>
                  <option value="<?php echo $column_name; ?>" <?php selected( $attr[ 'wpp_attribute' ], $column_name ); ?> ><?php echo $column_label; ?></option>
                <?php } ?>
                <option value="images" <?php echo ( $attr[ 'wpp_attribute' ] == 'images' ) ? 'selected="selected"' : ''; ?> >Images ( allows multiple )</option>
                <option value="featured-image" <?php echo ( $attr[ 'wpp_attribute' ] == 'featured-image' ) ? 'selected="selected"' : ''; ?> >Featured Image</option>
              </optgroup>
              <optgroup label="<?php _e( 'Taxonomies', ud_get_wpp_importer()->domain ); ?>">
                <?php foreach( $_taxonomies as $tax_slug => $tax ) { ?>
                  <option value="<?php echo $tax_slug; ?>" <?php echo ( $attr[ 'wpp_attribute' ] == $tax_slug ) ? 'selected="selected"' : ''; ?> ><?php echo $tax[ 'label' ]; ?> ( allows multiple )</option>
                <?php } ?>
              </optgroup>
              <optgroup label="<?php _e( 'Attributes', ud_get_wpp_importer()->domain ); ?>">
                <?php foreach( $_attributes as $property_stat_slug => $property_stat_label ): ?>
                  <option value="<?php echo $property_stat_slug; ?>" <?php echo ( $attr[ 'wpp_attribute' ] == $property_stat_slug ) ? 'selected="selected"' : ''; ?> ><?php echo $property_stat_label; ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="<?php _e( 'Address', ud_get_wpp_importer()->domain ); ?>">
                <option value='street_number' <?php selected( $attr[ 'wpp_attribute' ], 'street_number' ); ?>><?php _e( 'Street Number', ud_get_wpp_importer()->domain ); ?></option>
                <option value='route' <?php selected( $attr[ 'wpp_attribute' ], 'route' ); ?>><?php _e( 'Street', ud_get_wpp_importer()->domain ); ?></option>
                <option value='city' <?php selected( $attr[ 'wpp_attribute' ], 'city' ); ?>><?php _e( 'City', ud_get_wpp_importer()->domain ); ?></option>
                <option value='county' <?php selected( $attr[ 'wpp_attribute' ], 'county' ); ?>><?php _e( 'County', ud_get_wpp_importer()->domain ); ?></option>
                <option value='state' <?php selected( $attr[ 'wpp_attribute' ], 'state' ); ?>><?php _e( 'State', ud_get_wpp_importer()->domain ); ?></option>
                <option value='country' <?php selected( $attr[ 'wpp_attribute' ], 'country' ); ?>><?php _e( 'Country', ud_get_wpp_importer()->domain ); ?></option>
                <option value='postal_code' <?php selected( $attr[ 'wpp_attribute' ], 'postal_code' ); ?>><?php _e( 'Postal Code', ud_get_wpp_importer()->domain ); ?></option>
                <option value='latitude' <?php selected( $attr[ 'wpp_attribute' ], 'latitude' ); ?>><?php _e( 'Latitude', ud_get_wpp_importer()->domain ); ?></option>
                <option value='longitude' <?php selected( $attr[ 'wpp_attribute' ], 'longitude' ); ?>><?php _e( 'Longitude', ud_get_wpp_importer()->domain ); ?></option>
              </optgroup>

              <optgroup label="<?php _e( 'WP-Property Attributes', ud_get_wpp_importer()->domain ); ?>">
                <option value='property_type' <?php selected( $attr[ 'wpp_attribute' ], 'property_type' ); ?>><?php _e( 'Property Type', ud_get_wpp_importer()->domain ); ?></option>
                <?php if( class_exists( 'class_agents' ) ) { ?>
                  <option value='wpp_agents' <?php selected( $attr[ 'wpp_attribute' ], 'wpp_agents' ); ?>><?php _e( 'Property Agent', ud_get_wpp_importer()->domain ); ?></option>
                <?php } ?>
                <option value='wpp_gpid' <?php selected( $attr[ 'wpp_attribute' ], 'wpp_gpid' ); ?>><?php _e( 'Global Property ID', ud_get_wpp_importer()->domain ); ?></option>
                <option value='display_address' <?php selected( $attr[ 'wpp_attribute' ], 'display_address' ); ?>><?php _e( 'Display Address', ud_get_wpp_importer()->domain ); ?></option>
              </optgroup>
            </select>
          </td>
          <td><input style="width: 100%;" name="wpp_property_import[map][<?php echo( $index ) ?>][xpath_rule]" type="text" class='xpath_rule' value="<?php echo esc_attr( $attr[ 'xpath_rule' ] ); ?>"/></td>
          <td class="matches-section">
            <div>
              <a class="toggle_advanced_settings" href="javascript:;"><?php _e( 'Show Matches', ud_get_wpp_importer()->domain ); ?></a>
              <i class="defined-matches">(<span class="counter"><?php echo ( !empty( $attr['matches'] ) && is_array( $attr[ 'matches' ] ) ? count( $attr[ 'matches' ] ) : '0' ); ?></span>)</i>
              <ul class="advanced-options">
                <li class="clearfix">
                  <ul class="matches">
                    <li class=""><label><?php printf( __( 'Replace found matches with your custom value (%smore details%s)', ud_get_wpp_importer()->domain ), '<a target="_blank" href="https://wp-property.github.io/addons/importer/attribute-matches-in-schedule-settings-for-wp-property-importer.html">', '</a>' ); ?>:</label></li>
                    <li>
                      <ul class="list">
                        <?php $mindex = 0; if( !empty( $attr['matches'] ) && is_array( $attr[ 'matches' ] ) ) foreach( $attr['matches'] as $match ) : $mindex++; ?>
                          <li class="clearfix">
                            <span>
                              <input type="text" value="<?php echo $match[ 'match' ] ?>" name="wpp_property_import[map][<?php echo( $index ) ?>][matches][<?php echo( $mindex ) ?>][match]" placeholder="<?php _e( 'Matches via comma', ud_get_wpp_importer()->domain ); ?>">
                            </span>
                            <span>
                              <input type="text" value="<?php echo $match[ 'value' ] ?>" name="wpp_property_import[map][<?php echo( $index ) ?>][matches][<?php echo( $mindex ) ?>][value]" placeholder="<?php _e( 'Value', ud_get_wpp_importer()->domain ); ?>">
                            </span>
                            <a class="button remove_match_row" href="javascript:;"><?php _e( 'Remove', ud_get_wpp_importer()->domain ); ?></a></li>
                        <?php endforeach; ?>
                      </ul>
                    </li>
                    <li class=""><a class="add_match_row btn button" href="javascript:;"><?php _e( 'Add Match', ud_get_wpp_importer()->domain ); ?></a></li>
                  </ul>
                </li>
              </ul>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
      <tr>
        <td colspan="4">
          <div class="alignleft">
            <input type="button" class="wpp_import_delete_row button-secondary" value="<?php _e( 'Delete Selected', ud_get_wpp_importer()->domain ) ?>" />
            <input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', ud_get_wpp_importer()->domain ) ?>" callback_function="wpp.xmli.row_added" />
                <span class="wpp_i_unique_id_wrapper">
                  <select id="wpp_property_import_unique_id" name="wpp_property_import[unique_id]">
                    <?php $total_attribute_array = self::get_total_attribute_array(); ?>
                    <?php foreach( $settings[ 'map' ] as $attr ) { ?>
                      <?php if( !isset( $attr[ 'wpp_attribute' ] ) || !isset( $total_attribute_array[ $attr[ 'wpp_attribute' ] ] ) ) continue; ?>
                      <option value="<?php echo $attr[ 'wpp_attribute' ]; ?>" <?php selected( $attr[ 'wpp_attribute' ], $settings[ 'unique_id' ] ); ?>><?php echo $total_attribute_array[ $attr[ 'wpp_attribute' ] ]; ?>
                        ( <?php echo $attr[ 'wpp_attribute' ]; ?> )</option>
                    <?php } ?>
                  </select>
                  <span class="description"></span>
                </span>

          </div>

          <div class="alignright">
            <?php $save_button_id = ( $new_schedule ? 'id="wpp_property_import_save"' : 'id="wpp_property_import_update" schedule_id="' . $_REQUEST[ 'schedule_id' ] . '"' ); ?>
            <input type="button" <?php echo $save_button_id ?> class="button-primary" value="<?php _e( 'Save Configuration', ud_get_wpp_importer()->domain ) ?>" />

          </div>
        </td>
      </tr>

      </tfoot>

    </table>
  </td>
</tr>
<tr class="wpp_i_import_actions">
  <th></th>
  <td>
    <div class="wpp_i_import_actions_bar">
      <input type="hidden" id="import_hash" value="<?php echo $settings[ 'hash' ]; ?>" />
      <input type="button" id="wpp_i_preview_action" value="<?php _e( 'Preview Import', ud_get_wpp_importer()->domain ); ?>" class="button-secondary" />
      <input type="button" id="wpp_i_do_full_import" value="<?php _e( 'Process Full Import', ud_get_wpp_importer()->domain ); ?>" class="button-secondary" />
      <div class="wpp_i_ajax_message"></div>
    </div>
    <div class="wpp_i_import_preview">
      <div id="wpp_import_object_preview" class="hidden"><div class="wp-tab-panel"></div></div>
    </div>
  </td>
</tr>

</tbody>
</table>
</form>
</div>