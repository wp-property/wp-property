<?php
/**
 * Name: Main
 * Group: Settings
 * Description: Main settings.
 * Enqueue Scripts: udx-requires
 *
 */
?>
<table class="form-table" data-panel="main">

  <tr>
    <th><?php _e( 'Options', 'wpp' ); ?></th>
    <td>
      <ul>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][enable_comments]&label=" . __( 'Enable comments.', 'wpp' ), $wp_properties[ 'configuration' ][ 'enable_comments' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][exclude_from_regular_search_results]&label=" . sprintf( __( 'Exclude %1s from regular search results.', 'wpp' ), $object_label[ 'plural' ] ), $wp_properties[ 'configuration' ][ 'exclude_from_regular_search_results' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][do_not_automatically_regenerate_thumbnails]&label=" . __( 'Disable "on-the-fly" image regeneration.', 'wpp' ), $wp_properties[ 'configuration' ][ 'do_not_automatically_regenerate_thumbnails' ] ); ?></li>
        <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][auto_delete_attachments]&label=" . sprintf( __( 'Automatically delete all %1s images and attachments when a %2s is deleted.', 'wpp' ), $object_label[ 'singular' ], $object_label[ 'singular' ] ), $wp_properties[ 'configuration' ][ 'auto_delete_attachments' ] ); ?></li>
      </ul>
    </td>
  </tr>

  <tr>
    <th><?php printf( __( 'Default %1s Page', 'wpp' ), $wp_properties[ 'labels' ][ 'name' ] ); ?></th>
    <td>

      <div class="must_have_permalinks">
        <select name="wpp_settings[configuration][base_slug]" id="wpp_settings_base_slug">
          <option <?php selected( $wp_properties[ 'configuration' ][ 'base_slug' ], 'property' ); ?> value="property"><?php _e( 'Property (Default)', 'wpp' ); ?></option>
          <?php foreach( get_pages() as $page ): ?>
            <option <?php selected( $wp_properties[ 'configuration' ][ 'base_slug' ], $page->post_name ); ?> value="<?php echo $page->post_name; ?>"><?php echo $page->post_title; ?></option>
          <?php endforeach; ?>
        </select>
        <span data-scroll-to="h3.default_property_page" class="wpp_link wpp_toggle_contextual_help"><?php _e( 'What is this?', 'wpp' ); ?></span>
      </div>

      <div class="must_not_have_permalinks">
        <p class="description"><?php printf( __( 'You must have permalinks enabled to change the Default %1s page.', 'wpp' ), $wp_properties[ 'labels' ][ 'name' ] ); ?></p>
      </div>

    </td>
  </tr>

  <tr class="wpp_non_property_page_settings hidden">
    <th>&nbsp;</th>
    <td>
      <ul>
        <li>
          <?php echo WPP_F::checkbox( 'name=wpp_settings[configuration][automatically_insert_overview]&label=' . __( 'Automatically overwrite this page\'s content with [property_overview].', 'wpp' ), $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] ); ?>
        </li>
        <li class="wpp_wpp_settings_configuration_do_not_override_search_result_page_row <?php if( $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) echo " hidden "; ?>">
          <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][do_not_override_search_result_page]&label=" . __( 'When showing property search results, don\'t override the page content with [property_overview].', 'wpp' ), $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] ); ?>
          <div class="description"><?php _e( 'If checked, be sure to include [property_overview] somewhere in the content, or no properties will be displayed.', 'wpp' ); ?></div>
        </li>
    </ul>
    </td>
  </tr>

  <tr>
    <th><?php printf( __( 'Automatic Geolocation', 'wpp' ), WPP_F::property_label() ); ?></th>
    <td>
      <ul>
        <li><?php _e( 'Attribute to use for physical addresses:', 'wpp' ); ?><?php echo WPP_F::draw_attribute_dropdown( "name=wpp_settings[configuration][address_attribute]&selected={$wp_properties[ 'configuration' ]['address_attribute']}" ); ?></li>
        <li><?php _e( 'Localize addresses in:', 'wpp' ); ?> <?php echo WPP_F::draw_localization_dropdown( "name=wpp_settings[configuration][google_maps_localization]&selected={$wp_properties[ 'configuration' ]['google_maps_localization']}" ); ?></li>
      </ul>
    </td>
  </tr>

  <?php if( $custom_styles ) { ?>
  <tr>
    <th><?php _e( 'Styles', 'wpp' ); ?></th>
    <td>
      <ul>
        <li>
          <?php echo $this->get( 'custom_css' ) ? WPP_F::checkbox( "name=wpp_settings[configuration][autoload_css]&label=" . __( 'Load default CSS. If unchecked, the wp-properties.css in your theme folder will not be loaded.', 'wpp' ), $wp_properties[ 'configuration' ][ 'autoload_css' ] ) : WPP_F::checkbox( "name=wpp_settings[configuration][autoload_css]&label=" . __( 'Load default CSS.', 'wpp' ), $wp_properties[ 'configuration' ][ 'autoload_css' ] ); ?></li>
          <?php if( WPP_F::has_theme_specific_stylesheet() ) : ?>
          <li>
            <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][do_not_load_theme_specific_css]&label=" . __( 'Do not load theme-specific stylesheet.', 'wpp' ), $wp_properties[ 'configuration' ][ 'do_not_load_theme_specific_css' ] ); ?>
            <div class="description"><?php _e( 'This version of WP-Property has a stylesheet made specifically for the theme you are using.', 'wpp' ); ?></div>
          </li>
        <?php endif; ?>
      </ul>
    </td>
  </tr>
  <?php }; ?>

  <tr>
    <th><?php _e( 'Default Phone Number', 'wpp' ); ?></th>
    <td><?php echo WPP_F::input( "name=phone_number&label=" . sprintf( __( 'Phone number to use when a %1s-specific phone number is not specified.', 'wpp' ), WPP_F::property_label( 'singular' ) ) . "&group=wpp_settings[configuration]&style=width: 200px;", $wp_properties[ 'configuration' ][ 'phone_number' ] ); ?></td>
  </tr>

  <?php do_action( 'wpp_settings_main_tab_bottom', $wp_properties ); ?>

  <?php do_settings_fields( get_current_screen()->id, 'main' ); ?>

</table>