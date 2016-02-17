<div class="wpp_settings_block">
  <?php printf( __( 'Export All %s to CSV file', ud_get_wp_property()->domain ), \WPP_F::property_label('plural') ); ?>
  <a href="<?php echo home_url() . '?action=wpp_export_to_scv&nonce=' . wp_create_nonce( 'export_properties_to_scv' ); ?>" class="button" id="wpp_export_to_scv"><?php _e( 'Export', ud_get_wp_property()->domain ); ?></a>
</div>
<div class="wpp_settings_block">
  <label for="wpp_export_url"><?php _e( 'Feed URL:', ud_get_wp_property()->domain ); ?></label>
  <input id="wpp_export_url" type="text" style="width: 70%" readonly="true" value="<?php echo esc_attr( $export_url ); ?>"/>
  <a target="_blank" class="button" href="<?php echo $export_url; ?>"><?php _e( 'Open', ud_get_wp_property()->domain ); ?></a>
  <br/><br/>
  <?php _e( 'You may append the export URL with the following arguments:', ud_get_wp_property()->domain ); ?>
  <ul style="margin: 15px 0 0 10px">
    <li><b>limit</b> - number</li>
    <li><b>per_page</b> - number</li>
    <li><b>starting_row</b> - number</li>
    <li><b>sort_order</b> - number</li>
    <li><b>sort_by</b> - number</li>
    <li><b>property_type</b> - string - <?php printf( __( 'Slug for the %s type.', ud_get_wp_property()->domain ), \WPP_F::property_label() ); ?></li>
    <li><b>format</b> - string - "xml" <?php _e( 'or', ud_get_wp_property()->domain ); ?> "json"</li>
  </ul>
  </li>
  </ul>
</div>