<?php
/**
 * Page handles all the settings configuration for WP-Property. Premium features can hook into this page.
 *
 * Actions:
 * - wpp_settings_page_property_page
 * - wpp_settings_help_tab
 * - wpp_settings_content_$slug
 *
 * Filters:
 *  - wpp_settings_nav
 *
 * @version 1.12
 * @package   WP-Property
 * @author     team@UD
 * @copyright  2012 Usability Dyanmics, Inc.
 */

$wpp_plugin_settings_nav = apply_filters( 'wpp_settings_nav', array() );

//** Check if premium folder is writable */
// $wp_messages = UsabilityDynamics\WPP\Utility::check_premium_folder_permissions();

$object_label = array(
  'singular' => UsabilityDynamics\WPP\Utility::property_label( 'singular' ),
  'plural'   => UsabilityDynamics\WPP\Utility::property_label( 'plural' )
);

$wrapper_classes = array( 'wpp_settings_page' );

if( isset( $_REQUEST[ 'message' ] ) ) {

  switch( $_REQUEST[ 'message' ] ) {

    case 'updated':
      $wp_messages[ 'notice' ][ ] = __( "Settings updated.", 'wpp' );
      break;

  }
}

//** We have to update Rewrite rules here. peshkov@UD */ ... no we don't, should be done after settings updated only. -potanin@UD
// flush_rewrite_rules();

$parseUrl = parse_url( trim( get_bloginfo( 'url' ) ) );
$this_domain = trim( $parseUrl[ 'host' ] ? $parseUrl[ 'host' ] : array_shift( explode( '/', $parseUrl[ 'path' ], 2 ) ) );

/** Check if custom css exists */
if( file_exists( STYLESHEETPATH . '/wp_properties.css' ) || file_exists( TEMPLATEPATH . '/wp_properties.css' ) ) {
  $using_custom_css = true;
}

if( get_option( 'permalink_structure' ) == '' ) {
  $wrapper_classes[ ] = 'no_permalinks';
} else {
  $wrapper_classes[ ] = 'have_permalinks';
}
?>

<div class="wrap <?php echo implode( ' ', $wrapper_classes ); ?>">

  <h2 class='wpp_settings_page_header'><?php echo $wp_properties[ 'labels' ][ 'name' ] . ' ' . __( 'Settings', 'wpp' ) ?>
    <div class="wpp_fb_like" data-requires="">
      <div class="fb-like" data-href="https://www.facebook.com/wpproperty" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false"></div>
    </div>
  </h2>

  <?php if( isset( $wp_messages[ 'error' ] ) && $wp_messages[ 'error' ] ): ?>
    <div class="error">
    <?php foreach ($wp_messages[ 'error' ] as $error_message): ?>
      <p><?php echo $error_message; ?></p>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if( isset( $wp_messages[ 'notice' ] ) && $wp_messages[ 'notice' ] ): ?>
    <div class="updated fade">
    <?php foreach ($wp_messages[ 'notice' ] as $notice_message): ?>
      <p><?php echo $notice_message; ?></p>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form id="wpp_settings_form" method="post" action="<?php echo admin_url( 'edit.php?post_type=property&page=property_settings' ); ?>" enctype="multipart/form-data" data-requires="wpp.admin.settings">
    <?php wp_nonce_field( 'wpp_setting_save' ); ?>

    <div id="wpp_settings_tabs" class="wpp_tabs clearfix">
      <ul class="tabs"  data-requires="">
        <li><a href="#tab_main"><?php _e( 'Main', 'wpp' ); ?></a></li>
        <li><a href="#tab_display"><?php _e( 'Display', 'wpp' ); ?></a></li>
        <li><a href="#tab_maps"><?php _e( 'Maps', 'wpp' ); ?></a></li>
        <li><a href="#tab_images"><?php _e( 'Images', 'wpp' ); ?></a></li>
        <?php if( is_array( $wp_properties[ 'available_features' ] ) ) {

          foreach( $wp_properties[ 'available_features' ] as $plugin ) {
            if( @$plugin[ 'status' ] == 'disabled' ) {
              unset( $wpp_plugin_settings_nav[ $plugin ] );
            }
          }

          if( is_array( $wpp_plugin_settings_nav ) ) {
            foreach( $wpp_plugin_settings_nav as $nav ) {
              echo "<li><a href='#tab_{$nav['slug']}'>{$nav['title']}</a></li>\n";
            }
          }
        } ?>
        <li><a href="#tab_troubleshooting"><?php _e( 'Help', 'wpp' ); ?></a></li>
      </ul>

      <div id="tab_main">

        <?php do_action( 'wpp_settings_main_top', $wp_properties ); ?>

        <table class="form-table wpp-table">

          <tr>
          <th><?php _e( 'Options', 'wpp' ); ?></th>
          <td>
            <ul>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][include_in_regular_search_results]&label=" . sprintf( __( 'Include %1s in regular search results.', 'wpp' ), $object_label[ 'plural' ] ), $wp_properties[ 'configuration' ][ 'include_in_regular_search_results' ] ); ?></li>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][do_not_automatically_regenerate_thumbnails]&label=" . __( 'Disable "on-the-fly" image regeneration.', 'wpp' ), $wp_properties[ 'configuration' ][ 'do_not_automatically_regenerate_thumbnails' ] ); ?></li>
              <?php /* <li><?php echo UsabilityDynamics\WPP\Utility::checkbox("name=wpp_settings[configuration][do_not_automatically_geo_validate_on_property_view]&label=" . __('Disable front-end "on-the-fly" address validation.', 'wpp'), $wp_properties['configuration']['do_not_automatically_geo_validate_on_property_view']); ?></li> */ ?>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][auto_delete_attachments]&label=" . sprintf( __( 'Automatically delete all %1s images and attachments when a %2s is deleted.', 'wpp' ), $object_label[ 'singular' ], $object_label[ 'singular' ] ), $wp_properties[ 'configuration' ][ 'auto_delete_attachments' ] ); ?></li>
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
              <span wpp_scroll_to="h3.default_property_page" class="wpp_link wpp_toggle_contextual_help"><?php _e( 'What is this?', 'wpp' ); ?></span>
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
                <?php echo UsabilityDynamics\WPP\Utility::checkbox( 'name=wpp_settings[configuration][automatically_insert_overview]&label=' . __( 'Automatically overwrite this page\'s content with [property_overview].', 'wpp' ), $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] ); ?>
              </li>
              <li class="wpp_wpp_settings_configuration_do_not_override_search_result_page_row <?php if( $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) echo " hidden "; ?>">
                <?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][do_not_override_search_result_page]&label=" . __( 'When showing property search results, don\'t override the page content with [property_overview].', 'wpp' ), $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] ); ?>
                <div class="description"><?php _e( 'If checked, be sure to include [property_overview] somewhere in the content, or no properties will be displayed.', 'wpp' ); ?></div>
              </li>
          </ul>
          </td>
        </tr>

        <tr>
          <th><?php printf( __( 'Automatic Geolocation', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label() ); ?></th>
          <td>
            <ul>
              <li><?php _e( 'Attribute to use for physical addresses:', 'wpp' ); ?><?php echo UsabilityDynamics\WPP\Utility::draw_attribute_dropdown( "name=wpp_settings[configuration][address_attribute]&selected={$wp_properties[ 'configuration' ]['address_attribute']}" ); ?></li>
              <li><?php _e( 'Localize addresses in:', 'wpp' ); ?> <?php echo UsabilityDynamics\WPP\Utility::draw_localization_dropdown( "name=wpp_settings[configuration][google_maps_localization]&selected={$wp_properties[ 'configuration' ]['google_maps_localization']}" ); ?></li>
            </ul>
          </td>
        </tr>

        <tr>
          <th><?php _e( 'Styles and Scripts', 'wpp' ); ?></th>
          <td>
            <ul>
              <li><?php echo $using_custom_css ? UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][autoload_css]&label=" . __( 'Load default CSS. If unchecked, the wp-properties.css in your theme folder will not be loaded.', 'wpp' ), $wp_properties[ 'configuration' ][ 'autoload_css' ] ) : UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][autoload_css]&label=" . __( 'Load default CSS.', 'wpp' ), $wp_properties[ 'configuration' ][ 'autoload_css' ] ); ?></li>

              <?php if( UsabilityDynamics\WPP\Utility::has_theme_specific_stylesheet() ) { ?>
                <li>
                     <?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][do_not_load_theme_specific_css]&label=" . __( 'Do not load theme-specific stylesheet.', 'wpp' ), $wp_properties[ 'configuration' ][ 'do_not_load_theme_specific_css' ] ); ?>
                  <div class="description"><?php _e( 'This version of WP-Property has a stylesheet made specifically for the theme you are using.', 'wpp' ); ?></div>
                     </li>
                </li>
              <?php } /* UsabilityDynamics\WPP\Utility::has_theme_specific_stylesheet() */ ?>

              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][load_scripts_everywhere]&label=" . __( 'Load WP-Property scripts on all front-end pages.', 'wpp' ), $wp_properties[ 'configuration' ][ 'load_scripts_everywhere' ] ); ?></li>
            </ul>

          </td>
        </tr>

        <tr>
          <th><?php _e( 'Default Phone Number', 'wpp' ); ?></th>
          <td><?php echo UsabilityDynamics\WPP\Utility::input( "name=phone_number&label=" . sprintf( __( 'Phone number to use when a %1s-specific phone number is not specified.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ) . "&group=wpp_settings[configuration]&style=width: 200px;", $wp_properties[ 'configuration' ][ 'phone_number' ] ); ?></td>
        </tr>

          <?php do_action( 'wpp_settings_main_tab_bottom', $wp_properties ); ?>
        </table>

      </div>

      <div id="tab_display">

        <table class="form-table wpp-table">

        <tr>
          <th><?php _e( 'Overview Shortcode', 'wpp' ) ?></th>
          <td>
            <p>
            <?php printf( __( 'These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root %1s.<br />The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property-overview.php</b> file.  To avoid losing your changes during updates, create a <b>property-overview.php</b> file in your template directory, which will be automatically loaded.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'plural' ) ) ?>
            </p>
            <ul>
              <li><?php _e( 'Thumbnail size:', 'wpp' ) ?> <?php UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties[ 'configuration' ][ 'property_overview' ][ 'thumbnail_size' ] ); ?></li>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( 'name=wpp_settings[configuration][property_overview][show_children]&label=' . sprintf( __( 'Show children %1s.', 'wpp' ), $object_label[ 'plural' ] ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ); ?></li>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( 'name=wpp_settings[configuration][property_overview][fancybox_preview]&label=' . sprintf( __( 'Show larger image of %1s when image is clicked using fancybox.', 'wpp' ), $object_label[ 'singular' ] ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ] ); ?></li>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][bottom_insert_pagenation]&label=" . __( 'Show pagination on bottom of results.', 'wpp' ), $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] ); ?></li>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][property_overview][add_sort_by_title]&label=" . sprintf( __( 'Add sorting by %1s\'s title.', 'wpp' ), $object_label[ 'singular' ] ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'add_sort_by_title' ] ); ?></li>
              <?php do_action( 'wpp::settings::display::overview_shortcode' ); ?>
            </ul>

          </td>
        </tr>

        <tr>
          <th><?php printf( __( '%1s Page', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ) ?></th>
          <td>
            <p><?php _e( 'The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property.php</b> file.  To avoid losing your changes during updates, create a <b>property.php</b> file in your template directory, which will be automatically loaded.', 'wpp' ) ?>
            <ul>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][property_overview][sort_stats_by_groups]&label=" . sprintf( __( 'Sort %1s stats by groups.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'sort_stats_by_groups' ] ); ?></li>
              <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][property_overview][show_true_as_image]&label=" . sprintf( __( 'Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ) ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_true_as_image' ] ); ?></li>
              <?php do_action( 'wpp_settings_page_property_page' ); ?>
            </ul>

          </td>
        </tr>

        <tr>
          <th><?php _e( 'Address Display', 'wpp' ) ?></th>
          <td>

            <textarea name="wpp_settings[configuration][display_address_format]" style="width: 70%;"><?php echo $wp_properties[ 'configuration' ][ 'display_address_format' ]; ?></textarea>
            <br/>
            <span class="description">
                   <?php _e( 'Available tags:', 'wpp' ) ?> [street_number] [street_name], [city], [state], [state_code], [county],  [country], [zip_code].
            </span>
          </td>
        </tr>

        <tr>
          <th><?php _e( 'Currency & Numbers', 'wpp' ); ?></th>
          <td>
            <ul>
              <li><?php echo UsabilityDynamics\WPP\Utility::input( "name=currency_symbol&label=" . __( 'Currency symbol.', 'wpp' ) . "&group=wpp_settings[configuration]&style=width: 50px;", $wp_properties[ 'configuration' ][ 'currency_symbol' ] ); ?></li>
              <li>
                <?php _e( 'Thousands separator symbol:', 'wpp' ); ?>
                <select name="wpp_settings[configuration][thousands_sep]">
                  <option value=""> - </option>
                  <option value="." <?php selected( $wp_properties[ 'configuration' ][ 'thousands_sep' ], '.' ); ?>><?php _e( '. (period)', 'wpp' ); ?></option>
                  <option value="," <?php selected( $wp_properties[ 'configuration' ][ 'thousands_sep' ], ',' ); ?>><?php _e( ', (comma)', 'wpp' ); ?></option>
                 </select>
                 <span class="description"><?php _e( 'The character separating the 1 and the 5: $1<b>,</b>500', 'wpp' ); ?></span>

              </li>

              <li>
                <?php _e( 'Currency symbol placement:', 'wpp' ); ?>
                <select name="wpp_settings[configuration][currency_symbol_placement]">
                  <option value=""> - </option>
                  <option value="before" <?php selected( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ], 'before' ); ?>><?php _e( 'Before number', 'wpp' ); ?></option>
                  <option value="after" <?php selected( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ], 'after' ); ?>><?php _e( 'After number', 'wpp' ); ?></option>
                 </select>

              </li>

              <li>
                <?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][show_aggregated_value_as_average]&label=" . sprintf( __( 'Parent %1s\'s aggregated value should be set as average of children values. If not, - the aggregated value will be set as sum of children values.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ), $wp_properties[ 'configuration' ][ 'show_aggregated_value_as_average' ] ); ?>
                <br/><span class="description"><?php printf( __( 'Aggregated value is set only for numeric and currency attributes and can be updated ( set ) only on child %1s\'s saving.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ); ?></span>
              </li>

           </ul>
          </td>
        </tr>


        <tr>
          <th>
            <?php _e( 'Admin Settings', 'wpp' ) ?>
          </th>
            <td>
            <ul>
              <li><?php printf( __( 'Thumbnail size for %1s images displayed on %2s page: ', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ), UsabilityDynamics\WPP\Utility::property_label( 'plural' ) ) ?> <?php UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] ); ?></li>
              <li>
              <?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][completely_hide_hidden_attributes_in_admin_ui]&label=" . sprintf( __( 'Completely hide hidden attributes when editing %1s.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'plural' ) ), $wp_properties[ 'configuration' ][ 'completely_hide_hidden_attributes_in_admin_ui' ] ); ?>
              </li>
            </ul>
          </td>
        </tr>

          <?php do_action( 'wpp_settings_display_tab_bottom' ); ?>

        </table>

      </div>

      <div id="tab_maps">

        <table class="form-table wpp-table">

          <tr>
            <th><?php _e( 'Google Maps', 'wpp' ) ?></th>
            <td>

              <ul>
                <li><?php _e( 'Map Thumbnail Size:', 'wpp' ); ?> <?php UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . $wp_properties[ 'configuration' ][ 'single_property_view' ][ 'map_image_type' ] ); ?></li>
                <li><?php _e( 'Map Zoom Level:', 'wpp' ); ?> <?php echo UsabilityDynamics\WPP\Utility::input( "name=wpp_settings[configuration][gm_zoom_level]&style=width: 30px;", $wp_properties[ 'configuration' ][ 'gm_zoom_level' ] ); ?></li>
                <li><?php _e( 'Custom Latitude Coordinate', 'wpp' ); ?>
                  : <?php echo UsabilityDynamics\WPP\Utility::input( "name=wpp_settings[custom_coords][latitude]&style=width: 100px;", $wp_properties[ 'custom_coords' ][ 'latitude' ] ); ?>
                  <span class="description"><?php printf( __( 'Default is "%s"', 'wpp' ), $wp_properties[ 'default_coords' ][ 'latitude' ] ); ?></span></li>
                <li><?php _e( 'Custom Longitude Coordinate', 'wpp' ); ?>
                  : <?php echo UsabilityDynamics\WPP\Utility::input( "name=wpp_settings[custom_coords][longitude]&style=width: 100px;", $wp_properties[ 'custom_coords' ][ 'longitude' ] ); ?>
                  <span class="description"><?php printf( __( 'Default is "%s"', 'wpp' ), $wp_properties[ 'default_coords' ][ 'longitude' ] ); ?></span></li>
                <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][google_maps][show_true_as_image]&label=" . sprintf( __( 'Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ), __( 'Yes', 'wpp' ), __( 'No', 'wpp' ) ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'show_true_as_image' ] ); ?></li>
              </ul>

              <p><?php printf( __( 'Attributes to display in popup after a %1s on a map is clicked.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ); ?></p>
              <div class="wp-tab-panel">
              <ul>

                <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][show_property_title]&label=" . sprintf( __( 'Show %1s Title', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'show_property_title' ] ); ?></li>

                <?php foreach( $wp_properties[ 'property_stats' ] as $attrib_slug => $attrib_title ): ?>
                  <li><?php
                    $checked = ( in_array( $attrib_slug, $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_attributes' ] ) ? true : false );
                    echo UsabilityDynamics\WPP\Utility::checkbox( "id=google_maps_attributes_{$attrib_title}&name=wpp_settings[configuration][google_maps][infobox_attributes][]&label=$attrib_title&value={$attrib_slug}", $checked );
                    ?></li>
                <?php endforeach; ?>

                <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][show_direction_link]&label=" . __( 'Show Directions Link', 'wpp' ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'show_direction_link' ] ); ?></li>
                <li><?php echo UsabilityDynamics\WPP\Utility::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][do_not_show_child_properties]&label=" . sprintf( __( 'Do not show a list of child %1s in Infobox. ', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'plural' ) ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'do_not_show_child_properties' ] ); ?></li>
              </ul>
              </div>
            </td>
            </tr>

        </table>

      </div>

      <div id="tab_images">

        <table class="form-table wpp-table">

          <tr>
            <th><?php _e( 'Image Sizes', 'wpp' ); ?></th>
            <td>

              <table id="wpp_image_sizes" class="ud_ui_dynamic_table widefat">
                <thead>
                  <tr>
                    <th><?php _e( 'Slug', 'wpp' ); ?></th>
                    <th><?php _e( 'Width', 'wpp' ); ?></th>
                    <th><?php _e( 'Height', 'wpp' ); ?></th>
                    <th>&nbsp;</th>
                  </tr>
                </thead>
                <tbody>
              <?php
              $wpp_image_sizes = $wp_properties[ 'image_sizes' ];

              foreach( get_intermediate_image_sizes() as $slug ):

                $slug = trim( $slug );

                // We return all, including images with zero sizes, to avoid default data overriding what we save
                $image_dimensions = UsabilityDynamics\WPP\Utility::image_sizes( $slug, "return_all=true" );

                // Skip images w/o dimensions
                if( !$image_dimensions )
                  continue;

                // Disable if WP not a WPP image size
                if( @!is_array( $wpp_image_sizes[ $slug ] ) )
                  $disabled = true;
                else
                  $disabled = false;

                if( !$disabled ):
                  ?>
                  <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>">
                  <td class="wpp_slug">
                    <input class="slug_setter slug wpp_slug_can_be_empty" type="text" value="<?php echo $slug; ?>"/>
                  </td>
                  <td class="wpp_width">
                    <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][width]" value="<?php echo $image_dimensions[ 'width' ]; ?>"/>
                  </td>
                  <td class="wpp_height">
                    <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][height]" value="<?php echo $image_dimensions[ 'height' ]; ?>"/>
                  </td>
                  <td><span class="wpp_delete_row wpp_link"><?php _e( 'Delete', 'wpp' ) ?></span></td>
                </tr>

                <?php else: ?>
                  <tr>
                  <td>
                    <div class="wpp_permanent_image"><?php echo $slug; ?></div>
                  </td>
                  <td>
                    <div class="wpp_permanent_image"><?php echo $image_dimensions[ 'width' ]; ?></div>
                  </td>
                  <td>
                    <div class="wpp_permanent_image"><?php echo $image_dimensions[ 'height' ]; ?></div>
                  </td>
                  <td>&nbsp;</td>
                </tr>

                <?php endif; ?>


              <?php endforeach; ?>

                </tbody>
                <tfoot>
                  <tr>
                    <td colspan='4'><input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', 'wpp' ) ?>"/></td>
                  </tr>
                </tfoot>
              </table>

              <p class="description"><?php _e( 'Image sizes used throughout the plugin.', 'wpp' ); ?> </p>

           </td>
        </tr>

        </table>
      </div>

      <?php foreach( (array) $wpp_plugin_settings_nav as $nav ) {
        echo "<div id='tab_{$nav['slug']}'>";
        do_action( "wpp_settings_content_{$nav['slug']}" );
        echo "</div>";
      } ?>

      <div id="tab_troubleshooting">
        <div class="wpp_inner_tab">

          <div class="wpp_settings_block">
            <label>
            <?php _e( 'If prompted for your domain name during a premium feature purchase, enter as appears here:', 'wpp' ); ?>
              <input type="text" readonly="true" value="<?php echo $this_domain; ?>" size="<?php echo strlen( $this_domain ) + 10; ?>"/>
            </label>
          </div>

          <div class="wpp_settings_block">
            <?php _e( "Restore Backup of WP-Property Configuration", 'wpp' ); ?>
            : <input name="wpp_settings[settings_from_backup]" id="wpp_backup_file" type="file"/>
            <a href="<?php echo wp_nonce_url( "edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup", 'download-wpp-backup' ); ?>"><?php _e( 'Download Backup of Current WP-Property Configuration.', 'wpp' ); ?></a>
          </div>

          <div class="wpp_settings_block">
            <?php $google_map_localizations = UsabilityDynamics\WPP\Utility::draw_localization_dropdown( 'return_array=true' ); ?>
            <?php _e( 'Revalidate all addresses using', 'wpp' ); ?>
            <b><?php echo $google_map_localizations[ $wp_properties[ 'configuration' ][ 'google_maps_localization' ] ]; ?></b> <?php _e( 'localization', 'wpp' ); ?>
            .
             <input type="button" value="<?php _e( 'Revalidate', 'wpp' ); ?>" id="wpp_ajax_revalidate_all_addresses">
          </div>

          <div class="wpp_settings_block"><?php printf( __( 'Enter in the ID of the %1s you want to look up, and the class will be displayed below.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ) ?>
            <input type="text" id="wpp_property_class_id"/>
            <input type="button" value="<?php _e( 'Lookup', 'wpp' ) ?>" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden"><?php _e( 'Cancel', 'wpp' ) ?></span>
            <pre id="wpp_ajax_property_result" class="wpp_class_pre hidden"></pre>
          </div>

          <div class="wpp_settings_block"><?php printf( __( 'Get %1s image data.', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ) ?>
            <label for="wpp_image_id"><?php printf( __( '%1s ID:', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ) ?></label>
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

          <div class="wpp_settings_block"><?php printf( __( 'Set all %1s to same %2s type:', 'wpp' ), UsabilityDynamics\WPP\Utility::property_label( 'plural' ), UsabilityDynamics\WPP\Utility::property_label( 'singular' ) ) ?>
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

          <?php do_action( 'wpp_settings_help_tab' ); ?>
        </div>
      </div>

    </div>

    <br class="cb"/>

    <p class="wpp_save_changes_row">
      <input type="submit" value="<?php _e( 'Save Changes', 'wpp' ); ?>" class="button-primary btn" name="Submit">
    </p>

  </form>

</div>
