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

$object_label = array(
  'singular' => WPP_F::property_label( 'singular' ),
  'plural' => WPP_F::property_label( 'plural' )
);

$wrapper_classes = array( 'wpp_settings_page' );

if ( isset( $_REQUEST[ 'message' ] ) ) {
  $wp_messages = array();
  switch ( $_REQUEST[ 'message' ] ) {
    case 'updated':
      $wp_messages[ 'notice' ][ ] = __( "Settings updated.", ud_get_wp_property()->domain );
      break;
  }
}

//** We have to update Rewrite rules here. peshkov@UD */
flush_rewrite_rules();

$parseUrl = parse_url( trim( get_bloginfo( 'url' ) ) );
$this_domain = trim( $parseUrl[ 'host' ] ? $parseUrl[ 'host' ] : array_shift( explode( '/', $parseUrl[ 'path' ], 2 ) ) );

/** Check if custom css exists */
$using_custom_css = ( file_exists( STYLESHEETPATH . '/wp_properties.css' ) || file_exists( TEMPLATEPATH . '/wp_properties.css' ) ) ? true : false;

if ( get_option( 'permalink_structure' ) == '' ) {
  $wrapper_classes[ ] = 'no_permalinks';
} else {
  $wrapper_classes[ ] = 'have_permalinks';
}

?>
<div class="wrap <?php echo implode( ' ', $wrapper_classes ); ?>">
<?php screen_icon(); ?>
<h2 class='wpp_settings_page_header'><?php echo ud_get_wp_property( 'labels.name' ) . ' ' . __( 'Settings', ud_get_wp_property()->domain ) ?>
  <div class="wpp_fb_like">
  <div class="fb-like" data-href="https://www.facebook.com/wpproperty" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false"></div>
</div>
</h2>

<?php if ( isset( $wp_messages[ 'error' ] ) && $wp_messages[ 'error' ] ): ?>
  <div class="error">
  <?php foreach ($wp_messages[ 'error' ] as $error_message): ?>
    <p><?php echo $error_message; ?>
      <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ( isset( $wp_messages[ 'notice' ] ) && $wp_messages[ 'notice' ] ): ?>
  <div class="updated fade">
  <?php foreach ($wp_messages[ 'notice' ] as $notice_message): ?>
    <p><?php echo $notice_message; ?>
      <?php endforeach; ?>
</div>
<?php endif; ?>

<form id="wpp_settings_form" method="post" action="<?php echo admin_url( 'edit.php?post_type=property&page=property_settings' ); ?>" enctype="multipart/form-data"/>
<?php wp_nonce_field( 'wpp_setting_save' ); ?>

<div id="wpp_settings_tabs" class="wpp_tabs clearfix">
  <ul class="tabs">
    <li><a href="#tab_main"><?php _e( 'Main', ud_get_wp_property()->domain ); ?></a></li>
    <li><a href="#tab_display"><?php _e( 'Display', ud_get_wp_property()->domain ); ?></a></li>
    <?php
    $wpp_plugin_settings_nav = apply_filters( 'wpp_settings_nav', array() );
    if ( is_array( $wpp_plugin_settings_nav ) ) {
      foreach ( $wpp_plugin_settings_nav as $nav ) {
        echo "<li><a href='#tab_{$nav['slug']}'>{$nav['title']}</a></li>\n";
      }
    }
    ?>
    <li><a href="#tab_troubleshooting"><?php _e( 'Help', ud_get_wp_property()->domain ); ?></a></li>
  </ul>

  <div id="tab_main">

    <?php do_action( 'wpp_settings_main_top', $wp_properties ); ?>

    <table class="form-table">

    <tr>
      <th><?php _e( 'Options', ud_get_wp_property()->domain ); ?></th>
      <td>
        <ul>
          <li class="configuration_enable_comments"><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][enable_comments]&label=" . __( 'Enable comments.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'enable_comments' ] ) ? $wp_properties[ 'configuration' ][ 'enable_comments' ] : false ) ); ?></li>
          <li class="configuration_enable_revisions" data-feature-since="2.0.0"><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][enable_revisions]&label=" . __( 'Enable revisions.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'enable_revisions' ] ) ? $wp_properties[ 'configuration' ][ 'enable_revisions' ] : false ) ); ?></li>
          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][exclude_from_regular_search_results]&label=" . sprintf( __( 'Exclude %1s from regular search results.', ud_get_wp_property()->domain ), $object_label[ 'plural' ] ), ( isset( $wp_properties[ 'configuration' ][ 'exclude_from_regular_search_results' ] ) ? $wp_properties[ 'configuration' ][ 'exclude_from_regular_search_results' ] : false ) ); ?></li>
          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][auto_delete_attachments]&label=" . sprintf(__( 'Automatically delete all %1s images and attachments when a %2s is deleted.', ud_get_wp_property()->domain ), $object_label[ 'singular' ], $object_label[ 'singular' ] ), ( isset( $wp_properties[ 'configuration' ][ 'auto_delete_attachments' ] ) ? $wp_properties[ 'configuration' ][ 'auto_delete_attachments' ] : false ) ); ?></li>
        </ul>
      </td>
    </tr>

    <tr>
      <th><?php printf( __( 'Default %1s Page', ud_get_wp_property()->domain ), ud_get_wp_property( 'labels.name' ) ); ?></th>
      <td>

        <div class="must_have_permalinks">
          <select name="wpp_settings[configuration][base_slug]" id="wpp_settings_base_slug">
            <option <?php selected( $wp_properties[ 'configuration' ][ 'base_slug' ], 'property' ); ?> value="property"><?php _e( 'Property (Default)', ud_get_wp_property()->domain ); ?></option>
            <?php foreach ( get_pages() as $page ): ?>
              <option <?php selected( $wp_properties[ 'configuration' ][ 'base_slug' ], $page->post_name ); ?> value="<?php echo $page->post_name; ?>"><?php echo $page->post_title; ?></option>
            <?php endforeach; ?>
          </select>
          <span wpp_scroll_to="h3.default_property_page" class="wpp_link wpp_toggle_contextual_help"><?php _e( 'What is this?', ud_get_wp_property()->domain ); ?></span>
        </div>
        <div class="must_not_have_permalinks">
          <p class="description"><?php printf( __( 'You must have permalinks enabled to change the Default %1s page.', ud_get_wp_property()->domain ), ud_get_wp_property( 'labels.name' ) ); ?></p>
        </div>

      </td>
    </tr>

    <tr class="wpp_non_property_page_settings hidden">
      <th>&nbsp;</th>
      <td>
        <ul>
          <li>
            <?php echo WPP_F::checkbox( 'name=wpp_settings[configuration][automatically_insert_overview]&label=' . __( 'Automatically overwrite this page\'s content with [property_overview].', ud_get_wp_property()->domain ), $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] ); ?>
          </li>
          <li class="wpp_wpp_settings_configuration_do_not_override_search_result_page_row <?php if ( $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) echo " hidden "; ?>">
            <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][do_not_override_search_result_page]&label=" . __( 'When showing property search results, don\'t override the page content with [property_overview].', ud_get_wp_property()->domain ), $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] ); ?>
            <div class="description"><?php _e( 'If checked, be sure to include [property_overview] somewhere in the content, or no properties will be displayed.', ud_get_wp_property()->domain ); ?></div>
          </li>
        </ul>
      </td>
    </tr>

    <tr>
      <th><?php printf( __( 'Single %s Template', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></th>
      <td>
        <p><?php printf( __( 'Select template which will be used to render Single %s page.', ud_get_wp_property( 'domain' ) ), WPP_F::property_label() ); ?></p>
        <p><?php printf( __( 'You also can redeclare selected template for specific %s on Edit %s page.', ud_get_wp_property( 'domain' ) ), WPP_F::property_label(), WPP_F::property_label() ); ?></p>
        <p><?php printf( __( 'Note, you can use Single or Page templates for building your own layouts via %s or another Layouts Framework.', ud_get_wp_property( 'domain' ) ), '<a target="_blank" href="https://siteorigin.com/page-builder/">SiteOrigin Page Builder</a>' ); ?></p><br/>
        <ul>
          <li>
            <label><input type="radio" name="wpp_settings[configuration][single_property][template]" value="property" <?php echo empty( $wp_properties[ 'configuration' ][ 'single_property']['template' ] ) || $wp_properties[ 'configuration' ][ 'single_property']['template' ] == 'property' ? 'checked' : ''; ?> /> <?php printf( 'Default Property Template', ud_get_wp_property( 'domain' ) ); ?>.</label>
            <p><span class="description"><?php printf( __( 'By default, %s plugin uses custom <b>%s</b> template for rendering Single %s page.', ud_get_wp_property( 'domain' ) ), 'WP-Property', 'property.php', WPP_F::property_label() ); ?></span></p>
            <p><span class="description"><?php printf( __( 'The template contains predefined sections such as attributes list, map and registered sidebars areas.', ud_get_wp_property( 'domain' ) ) ); ?></span></p>
            <p><span class="description"><?php printf( __( 'The display settings may be edited further by customizing the <b>%s</b> file.', ud_get_wp_property( 'domain' ) ), 'wp-content/plugins/wp-property/static/views/property.php' ) ?></span></p>
            <p><span class="description"><?php printf( __( 'To avoid losing your changes during updates, copy <b>%s</b> file to your template directory, which will be automatically loaded.', ud_get_wp_property( 'domain' ) ), 'property.php' ); ?></span></p><br/>
            <p><?php printf( __( 'Additional settings for Default %s Template', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?>:</p>
            <ul>
              <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][sort_stats_by_groups]&label=" . sprintf(__( 'Sort %1s stats by groups.', ud_get_wp_property()->domain ),  WPP_F::property_label( 'singular' )), ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'sort_stats_by_groups' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'sort_stats_by_groups' ] : false ) ); ?></li>
              <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][show_true_as_image]&label=" . sprintf( __( 'Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', ud_get_wp_property()->domain ), __( 'Yes', ud_get_wp_property()->domain ), __( 'No', ud_get_wp_property()->domain ), __( 'Yes', ud_get_wp_property()->domain ), __( 'No', ud_get_wp_property()->domain ) ), ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_true_as_image' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_true_as_image' ] : false ) ); ?></li>
              <?php do_action( 'wpp_settings_page_property_page' ); ?>
            </ul><br/>
          </li>
          <li>
            <label><input type="radio" name="wpp_settings[configuration][single_property][template]" value="single" <?php echo !empty( $wp_properties[ 'configuration' ][ 'single_property']['template' ] ) && $wp_properties[ 'configuration' ][ 'single_property']['template' ] == 'single' ? 'checked' : ''; ?> /> <?php printf( 'Single Post Template', ud_get_wp_property( 'domain' ) ); ?>.</label>
            <p><span class="description"><?php printf( __( 'The single post template file <b>%s</b> in your theme will be used to render a Single %s page', ud_get_wp_property( 'domain' ) ), 'single.php', WPP_F::property_label() ); ?></span></p>
            <p><span class="description"><?php printf( __( 'You can create your own single post template file <b>%s</b> in your theme which will be used instead of <b>%s</b>. %sMore Details%s.', ud_get_wp_property( 'domain' ) ), 'single-property.php', 'single.php', '<a target="_blank" href="https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post">', '</a>' ); ?></span></p>
            <p><span class="description"><?php printf( __( 'Note, registered <b>%s sidebars</b> are defined only in default <b>%s</b> template. You have to add them manually to your theme\'s template.', ud_get_wp_property( 'domain' ) ), 'WP-Property', 'property.php' ); ?></span></p><br/>
          </li>
          <li>
            <label><input type="radio" name="wpp_settings[configuration][single_property][template]" value="page" <?php echo !empty( $wp_properties[ 'configuration' ][ 'single_property']['template' ] ) && $wp_properties[ 'configuration' ][ 'single_property']['template' ] == 'page' ? 'checked' : ''; ?> /> <?php printf( 'Page Template', ud_get_wp_property( 'domain' ) ); ?>.</label>
            <span>
              <label><?php printf( __( 'Select page template which you want to use on single %s page', ud_get_wp_property( 'domain' ) ), WPP_F::property_label() ); ?></label>
              <select name="wpp_settings[configuration][single_property][page_template]">
                <option value="default" <?php echo !empty( $wp_properties[ 'configuration' ][ 'single_property']['page_template' ] ) && $wp_properties[ 'configuration' ][ 'single_property']['page_template' ] == 'default' ? 'selected="selected"' : ''; ?> ><?php _e( 'Default Template', ud_get_wp_property( 'domain' ) ); ?></option>
                <?php foreach ( get_page_templates() as $title => $slug ) : ?>
                  <option value="<?php echo $slug ?>" <?php echo !empty( $wp_properties[ 'configuration' ][ 'single_property']['page_template' ] ) && $wp_properties[ 'configuration' ][ 'single_property']['page_template' ] == $slug ? 'selected="selected"' : ''; ?> ><?php echo $title; ?></option>
                <?php endforeach; ?>
              </select>
            </span>
            <p><span class="description"><?php printf( __( 'Page template will be used to render a Single %s page. %sMore Details%s.', ud_get_wp_property( 'domain' ) ), WPP_F::property_label(), '<a target="_blank" href="https://developer.wordpress.org/themes/template-files-section/page-template-files/page-templates/">', '</a>' ); ?></span></p>
            <p><span class="description"><?php printf( __( 'Note, registered <b>%s sidebars</b> are defined only in default <b>%s</b> template. You have to add them manually to your theme\'s template.', ud_get_wp_property( 'domain' ) ), 'WP-Property', 'property.php' ); ?></span></p>
          </li>
        </ul>
      </td>
    </tr>

    <?php if( !isset( $wp_properties[ 'configuration' ][ 'do_not_register_sidebars' ] ) || ( isset( $wp_properties[ 'configuration' ][ 'do_not_register_sidebars' ] ) && $wp_properties[ 'configuration' ][ 'do_not_register_sidebars' ] != 'true' ) ) : ?>
    <tr>
      <th><?php printf( __( 'Widget Sidebars', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></th>
      <td>
        <p><?php printf( __( 'By default, %1$s registers widget sidebars for <b>Single %2$s page</b> based on defined %2$s types. But you can disable any of them here.', ud_get_wp_property( 'domain' ) ), 'WP-Property', WPP_F::property_label() ); ?></p>
        <p><?php printf( __( 'Note, the following sidebar are added only on default <b>%s</b> ( Default %s Template ).', ud_get_wp_property( 'domain' ) ), 'property.php', WPP_F::property_label() ); ?></p><br/>
        <ul>
          <?php foreach( (array)$wp_properties[ 'property_types' ] as $slug => $title ) : ?>
          <li>
            <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][disable_widgets][wpp_sidebar_{$slug}]&label=" . sprintf( __( 'Disable <b>%s</b> Sidebar.', ud_get_wp_property( 'domain' ) ), WPP_F::property_label() . ': ' . $title  ), ( isset( $wp_properties[ 'configuration' ]['disable_widgets']['wpp_sidebar_' . $slug ] ) ? $wp_properties[ 'configuration' ]['disable_widgets']['wpp_sidebar_' . $slug] : false )  ); ?>
            <span class="description"><code>dynamic_sidebar( "wpp_sidebar_<?php echo $slug ?>" )</code></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </td>
    </tr>
    <?php endif; ?>

    <tr>
      <th><?php printf( __( 'Automatic Geolocation', ud_get_wp_property()->domain ), WPP_F::property_label() ); ?></th>
      <td>
        <ul>
          <li><?php _e( 'Attribute to use for physical addresses:', ud_get_wp_property('domain') ); ?><?php echo WPP_F::draw_attribute_dropdown( "name=wpp_settings[configuration][address_attribute]&selected={$wp_properties[ 'configuration' ]['address_attribute']}" ); ?></li>
          <li><?php _e( 'Localize addresses in:', ud_get_wp_property('domain') ); ?> <?php echo WPP_F::draw_localization_dropdown( "name=wpp_settings[configuration][google_maps_localization]&selected={$wp_properties[ 'configuration' ]['google_maps_localization']}" ); ?></li>
          <li class="google-maps-api-section" data-feature-since="2.0.3">
            <?php printf(__( 'Google Maps API (optional):', ud_get_wp_property('domain') ) ); ?> <?php echo WPP_F::input( "name=wpp_settings[configuration][google_maps_api]", ud_get_wp_property( 'configuration.google_maps_api' ) ); ?>
            <br/><span class="description"><?php printf( __( 'Note, Google Maps has its own limit of usage. You can provide Google Maps API license ( key ) above to increase limit. See more details %shere%s.', ud_get_wp_property('domain') ), '<a href="https://developers.google.com/maps/documentation/javascript/usage#usage_limits" target="_blank">', '</a>' ); ?></span>
          </li>
        </ul>
      </td>
    </tr>

    <tr>
      <th><?php _e( 'Default Phone Number', ud_get_wp_property()->domain ); ?></th>
      <td><?php echo WPP_F::input( "name=phone_number&label=" . sprintf(__( 'Phone number to use when a %1s-specific phone number is not specified.', ud_get_wp_property()->domain ), WPP_F::property_label( 'singular' ) ) . "&group=wpp_settings[configuration]&style=width: 200px;", ( isset( $wp_properties[ 'configuration' ][ 'phone_number' ] ) ? $wp_properties[ 'configuration' ][ 'phone_number' ] : false ) ); ?></td>
    </tr>

    <tr>
      <th><?php _e( 'Advanced Options', ud_get_wp_property()->domain ); ?></th>
      <td>
        <div class="wpp_settings_block"><br/>
          <ul>
            <?php if ( apply_filters( 'wpp::custom_styles', false ) === false ) : ?>
              <li>
                <?php echo $using_custom_css ? WPP_F::checkbox( "name=wpp_settings[configuration][autoload_css]&label=" . __( 'Load default CSS.', ud_get_wp_property()->domain ), $wp_properties[ 'configuration' ][ 'autoload_css' ] ) : WPP_F::checkbox( "name=wpp_settings[configuration][autoload_css]&label=" . __( 'Load default CSS.', ud_get_wp_property()->domain ), $wp_properties[ 'configuration' ][ 'autoload_css' ] ); ?>
                <span class="description"><?php printf( __( 'If unchecked, the %s in your theme folder will not be loaded.', ud_get_wp_property()->domain ), 'wp-properties.css' )  ?></span>
              </li>
              <?php if ( WPP_F::has_theme_specific_stylesheet() ) : ?>
                <li>
                  <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][do_not_load_theme_specific_css]&label=" . __( 'Do not load theme-specific stylesheet.', ud_get_wp_property()->domain ), isset( $wp_properties[ 'configuration' ][ 'do_not_load_theme_specific_css' ] ) ? $wp_properties[ 'configuration' ][ 'do_not_load_theme_specific_css' ] : false ); ?>
                  <span class="description"><?php _e( 'This version of WP-Property has a stylesheet made specifically for the theme you are using.', ud_get_wp_property()->domain ); ?></span>
                </li>
              <?php endif; /* WPP_F::has_theme_specific_stylesheet() */ ?>
            <?php endif; ?>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][enable_legacy_features]&label=" . __( 'Enable Legacy Features.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'enable_legacy_features' ] ) ? $wp_properties[ 'configuration' ][ 'enable_legacy_features' ] : false ) ); ?>
              <span class="description"><?php printf( __( 'If checked deprecated features will be enabled. E.g.: Child %1$s and Featured %1$s Widgets, etc', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ) )  ?></span>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][allow_parent_deep_depth]&label=" . __( 'Enable \'Falls Under\' deep depth.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] ) ? $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] : false ) ); ?>
              <span class="description"><?php printf( __( 'Allows to set child %1s as parent.', ud_get_wp_property()->domain ), WPP_F::property_label( 'singular' ) )  ?></span>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][disable_wordpress_postmeta_cache]&label=" . __( 'Disable WordPress update_post_caches() function.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] ) ? $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] : false ) ); ?>
              <span class="description"><?php printf( __('This may solve Out of Memory issues if you have a lot of %1s.',ud_get_wp_property()->domain), WPP_F::property_label( 'plural' )); ?></span>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][developer_mode]&label=" . __( 'Enable developer mode - some extra information displayed via Firebug console.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'developer_mode' ] ) ? $wp_properties[ 'configuration' ][ 'developer_mode' ] : false ) ); ?>
              <br/>
            </li>
            <li>
              <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][do_not_automatically_regenerate_thumbnails]&label=" . __( 'Disable "on-the-fly" image regeneration.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'do_not_automatically_regenerate_thumbnails' ] ) ? $wp_properties[ 'configuration' ][ 'do_not_automatically_regenerate_thumbnails' ] : true ) ); ?>
              <span class="description"><?php _e('Enabling this option may cause performance issues.',ud_get_wp_property()->domain); ?></span>
            </li>
          </ul>
        </div>
      </td>
    </tr>


      <?php do_action( 'wpp_settings_main_tab_bottom', $wp_properties ); ?>
    </table>


  </div>

  <div id="tab_display">

    <table class="form-table">

    <tr>
      <th><?php _e( 'Image Sizes', ud_get_wp_property()->domain ); ?></th>
      <td>
        <p><?php _e( 'Image sizes used throughout the plugin.', ud_get_wp_property()->domain ); ?> </p>

          <table id="wpp_image_sizes" class="ud_ui_dynamic_table widefat">
            <thead>
              <tr>
                <th><?php _e( 'Slug', ud_get_wp_property()->domain ); ?></th>
                <th><?php _e( 'Width', ud_get_wp_property()->domain ); ?></th>
                <th><?php _e( 'Height', ud_get_wp_property()->domain ); ?></th>
                <th>&nbsp;</th>
              </tr>
            </thead>
            <tbody>
          <?php
          $wpp_image_sizes = $wp_properties[ 'image_sizes' ];

          foreach ( array_unique( (array) get_intermediate_image_sizes() ) as $slug ):

            $slug = trim( $slug );

            // We return all, including images with zero sizes, to avoid default data overriding what we save
            $image_dimensions = WPP_F::image_sizes( $slug, "return_all=true" );

            // Skip images w/o dimensions
            if ( !$image_dimensions )
              continue;

            // Disable if WP not a WPP image size
            if ( !isset( $wpp_image_sizes[ $slug ] ) || !is_array( $wpp_image_sizes[ $slug ] ) )
              $disabled = true;
            else
              $disabled = false;

            if ( !$disabled ):
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
              <td><span class="wpp_delete_row wpp_link"><?php _e( 'Delete', ud_get_wp_property()->domain ) ?></span></td>
            </tr>

            <?php else: ?>
              <tr>
              <td>
                <input class="slug_setter slug wpp_slug_can_be_empty" type="text" disabled="disabled" value="<?php echo $slug; ?>"/>
              </td>
              <td>
                <input type="text" disabled="disabled" value="<?php echo $image_dimensions[ 'width' ]; ?>"/>
              </td>
              <td>
                <input type="text" disabled="disabled" value="<?php echo $image_dimensions[ 'height' ]; ?>"/>
              </td>
              <td>&nbsp;</td>
            </tr>

            <?php endif; ?>


          <?php endforeach; ?>

            </tbody>
            <tfoot>
              <tr>
                <td colspan='4'><input type="button" class="wpp_add_row button-secondary" value="<?php _e( 'Add Row', ud_get_wp_property()->domain ) ?>"/></td>
              </tr>
            </tfoot>
          </table>

       </td>
    </tr>

    <tr>
      <th><?php printf( __( 'Default %s image', ud_get_wp_property('domain') ), \WPP_F::property_label() ); ?></th>
      <td>
        <p>
          <?php printf( __( 'Setup image which will be used by default for all %s without images.', ud_get_wp_property('domain') ), \WPP_F::property_label('plural') ); ?><br/>
          <?php printf( __( 'Note, you also can setup default image for every %s type on Developer tab. So, that image will be used instead of current one.', ud_get_wp_property('domain') ), \WPP_F::property_label() ); ?>
        </p>
        <div class="upload-image-section">
          <input type="hidden" name="wpp_settings[configuration][default_image][default][url]" class="input-image-url" value="<?php echo isset( $wp_properties[ 'configuration' ][ 'default_image' ][ 'default' ][ 'url' ] ) ? $wp_properties[ 'configuration' ][ 'default_image' ][ 'default' ]['url'] : ''; ?>">
          <input type="hidden" name="wpp_settings[configuration][default_image][default][id]" class="input-image-id" value="<?php echo isset( $wp_properties[ 'configuration' ][ 'default_image' ][ 'default' ][ 'id' ] ) ? $wp_properties[ 'configuration' ][ 'default_image' ][ 'default' ]['id'] : ''; ?>">
          <div class="image-actions">
            <input type="button" class="button-secondary button-setup-image" value="<?php _e( 'Setup Image', ud_get_wp_property('domain') ); ?>">
          </div>
          <div class="image-wrapper"></div>
        </div>
      </td>
    </tr>

    <tr>
      <th><?php _e( 'Overview Shortcode', ud_get_wp_property()->domain ) ?></th>
      <td>
        <p>
        <?php printf( __( 'These are the settings for the <b>%s</b> shortcode and %s Overview widget. The shortcode (widget) displays a list of all %s.<br />The display settings may be edited further by customizing the <b>%s</b> file.  To avoid losing your changes during updates, copy <b>%s</b> file in your theme\'s root directory, which will be automatically loaded.', ud_get_wp_property()->domain ), '[property_overview]', WPP_F::property_label(), WPP_F::property_label( 'plural' ), 'wp-content/plugins/wp-property/static/views/property-overview.php', 'property-overview.php' ); ?>
        </p>
        <br/>
        <ul>
          <li><?php _e( 'Thumbnail size:', ud_get_wp_property()->domain ) ?> <?php WPP_F::image_sizes_dropdown( "name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties[ 'configuration' ][ 'property_overview' ][ 'thumbnail_size' ] ); ?></li>
          <li><?php _e( "Default Type of Pagination", ud_get_wp_property()->domain ) ?>:
            <select name="wpp_settings[configuration][property_overview][pagination_type]">
              <option value="slider" <?php if( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'pagination_type' ] ) ) selected( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'pagination_type' ], 'slider' ); ?>><?php _e( 'Slider', ud_get_wp_property()->domain ); ?> (slider)</option>
              <option value="numeric" <?php if( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'pagination_type' ] ) ) selected( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'pagination_type' ], 'numeric' ); ?>><?php _e( 'Numeric', ud_get_wp_property()->domain ); ?> (numeric)</option>
            </select>
            <span class="description"><?php printf( __( 'You always can set pagination type for specific shortcode or widget manually. Example: %s', ud_get_wp_property('domain') ), '<code>[property_overview pagination_type=numeric]</code>' ); ?></span>
          </li>
          <li><?php echo WPP_F::checkbox( 'name=wpp_settings[configuration][property_overview][show_children]&label=' . sprintf(__( 'Show children %1s.', ud_get_wp_property()->domain ), $object_label[ 'plural' ] ), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ); ?></li>
          <li><?php echo WPP_F::checkbox( 'name=wpp_settings[configuration][property_overview][fancybox_preview]&label=' . sprintf(__( 'Show larger image of %1s when image is clicked using fancybox.', ud_get_wp_property()->domain ), $object_label[ 'singular' ]), $wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ] ); ?></li>
          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][bottom_insert_pagenation]&label=" . __( 'Show pagination on bottom of results.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] ) ? $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] : false ) ); ?></li>
          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][property_overview][add_sort_by_title]&label=" . sprintf(__( 'Add sorting by %1s\'s title.', ud_get_wp_property()->domain ), $object_label[ 'singular' ]), ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'add_sort_by_title' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'add_sort_by_title' ] : false ) ); ?></li>
          <?php do_action( 'wpp::settings::display::overview_shortcode' ); ?>
        </ul>

      </td>
    </tr>

    <tr>
      <th><?php _e( 'Google Maps', ud_get_wp_property()->domain ) ?></th>
      <td>

        <ul>
          <li><?php _e( 'Map Thumbnail Size:', ud_get_wp_property()->domain ); ?> <?php WPP_F::image_sizes_dropdown( "name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . ( isset( $wp_properties[ 'configuration' ][ 'single_property_view' ][ 'map_image_type' ] ) ? $wp_properties[ 'configuration' ][ 'single_property_view' ][ 'map_image_type' ] : '' ) ); ?></li>
          <li><?php _e( 'Map Zoom Level:', ud_get_wp_property()->domain ); ?> <?php echo WPP_F::input( "name=wpp_settings[configuration][gm_zoom_level]&style=width: 30px;", ( isset( $wp_properties[ 'configuration' ][ 'gm_zoom_level' ] ) ? $wp_properties[ 'configuration' ][ 'gm_zoom_level' ] : false ) ); ?></li>
          <li><?php _e( 'Custom Latitude Coordinate', ud_get_wp_property()->domain ); ?>
            : <?php echo WPP_F::input( "name=wpp_settings[custom_coords][latitude]&style=width: 100px;", ( isset( $wp_properties[ 'custom_coords' ][ 'latitude' ] ) ? $wp_properties[ 'custom_coords' ][ 'latitude' ] : false ) ); ?>
            <span class="description"><?php printf( __( 'Default is "%s"', ud_get_wp_property()->domain ), $wp_properties[ 'default_coords' ][ 'latitude' ] ); ?></span></li>
          <li><?php _e( 'Custom Longitude Coordinate', ud_get_wp_property()->domain ); ?>
            : <?php echo WPP_F::input( "name=wpp_settings[custom_coords][longitude]&style=width: 100px;", ( isset( $wp_properties[ 'custom_coords' ][ 'longitude' ] ) ? $wp_properties[ 'custom_coords' ][ 'longitude' ] : false ) ); ?>
            <span class="description"><?php printf( __( 'Default is "%s"', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'default_coords' ][ 'longitude' ] ) ? $wp_properties[ 'default_coords' ][ 'longitude' ] : false ) ); ?></span></li>
          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][show_true_as_image]&label=" . sprintf( __( 'Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', ud_get_wp_property()->domain ), __( 'Yes', ud_get_wp_property()->domain ), __( 'No', ud_get_wp_property()->domain ), __( 'Yes', ud_get_wp_property()->domain ), __( 'No', ud_get_wp_property()->domain ) ), ( isset( $wp_properties[ 'configuration' ][ 'google_maps' ][ 'show_true_as_image' ] ) ? $wp_properties[ 'configuration' ][ 'google_maps' ][ 'show_true_as_image' ] : false ) ); ?></li>
        </ul>

        <p><?php printf(__( 'Attributes to display in popup after a %1s on a map is clicked.', ud_get_wp_property()->domain ),  WPP_F::property_label( 'singular' )); ?></p>
        <div class="wp-tab-panel">
        <ul>

          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][show_property_title]&label=" . sprintf(__( 'Show %1s Title', ud_get_wp_property()->domain ),  WPP_F::property_label( 'singular' )), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'show_property_title' ] ); ?></li>

          <?php foreach ( $wp_properties[ 'property_stats' ] as $attrib_slug => $attrib_title ): ?>
            <li><?php
              $checked = ( in_array( $attrib_slug, $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_attributes' ] ) ? true : false );
              echo WPP_F::checkbox( "id=google_maps_attributes_{$attrib_title}&name=wpp_settings[configuration][google_maps][infobox_attributes][]&label=$attrib_title&value={$attrib_slug}", $checked );
              ?></li>
          <?php endforeach; ?>

          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][show_direction_link]&label=" . __( 'Show Directions Link', ud_get_wp_property()->domain ), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'show_direction_link' ] ); ?></li>
          <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][do_not_show_child_properties]&label=" . sprintf(__( 'Do not show a list of child %1s in Infobox. ', ud_get_wp_property()->domain ),  WPP_F::property_label( 'plural' )), $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'do_not_show_child_properties' ] ); ?></li>
        </ul>
        </div>
      </td>
    </tr>

    <tr>
      <th><?php _e( 'Address Display', ud_get_wp_property()->domain ) ?></th>
      <td>


        <textarea name="wpp_settings[configuration][display_address_format]" style="width: 70%;"><?php echo $wp_properties[ 'configuration' ][ 'display_address_format' ]; ?></textarea>
        <br/>
        <span class="description">
               <?php _e( 'Available tags:', ud_get_wp_property()->domain ) ?> [street_number] [street_name], [city], [state], [state_code], [county],  [country], [zip_code].
        </span>
      </td>
    </tr>

    <tr>
      <th><?php _e( 'Currency & Numbers', ud_get_wp_property()->domain ); ?></th>
      <td>
        <ul>
          <li><?php echo WPP_F::input( "name=currency_symbol&label=" . __( 'Currency symbol.', ud_get_wp_property()->domain ) . "&group=wpp_settings[configuration]&style=width: 50px;", $wp_properties[ 'configuration' ][ 'currency_symbol' ] ); ?></li>
          <li>
            <?php _e( 'Thousands separator symbol:', ud_get_wp_property()->domain ); ?>
            <select name="wpp_settings[configuration][thousands_sep]">
              <option value=""> - </option>
              <option value="." <?php if( isset( $wp_properties[ 'configuration' ][ 'thousands_sep' ] ) ) selected( $wp_properties[ 'configuration' ][ 'thousands_sep' ], '.' ); ?>><?php _e( '. (period)', ud_get_wp_property()->domain ); ?></option>
              <option value="," <?php if( isset( $wp_properties[ 'configuration' ][ 'thousands_sep' ] ) ) selected( $wp_properties[ 'configuration' ][ 'thousands_sep' ], ',' ); ?>><?php _e( ', (comma)', ud_get_wp_property()->domain ); ?></option>
             </select>
             <span class="description"><?php _e( 'The character separating the 1 and the 5: $1<b>,</b>500' ); ?></span>

          </li>

          <li>
            <?php _e( 'Currency symbol placement:', ud_get_wp_property()->domain ); ?>
            <select name="wpp_settings[configuration][currency_symbol_placement]">
              <option value=""> - </option>
              <option value="before" <?php if( isset( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] ) ) selected( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ], 'before' ); ?>><?php _e( 'Before number', ud_get_wp_property()->domain ); ?></option>
              <option value="after" <?php if( isset( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ] ) ) selected( $wp_properties[ 'configuration' ][ 'currency_symbol_placement' ], 'after' ); ?>><?php _e( 'After number', ud_get_wp_property()->domain ); ?></option>
             </select>

          </li>

          <li>
            <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][show_aggregated_value_as_average]&label=" . __( 'Parent property\'s aggregated value should be set as average of children values. If not, - the aggregated value will be set as sum of children values.', ud_get_wp_property()->domain ), ( isset( $wp_properties[ 'configuration' ][ 'show_aggregated_value_as_average' ] ) ? $wp_properties[ 'configuration' ][ 'show_aggregated_value_as_average' ] : false ) ); ?>
            <br/><span class="description"><?php printf(__( 'Aggregated value is set only for numeric and currency attributes and can be updated ( set ) only on child %1s\'s saving.', ud_get_wp_property()->domain ), WPP_F::property_label( 'singular' ) ); ?></span>
          </li>

       </ul>
      </td>
    </tr>


    <tr>
      <th>
        <?php _e( 'Admin Settings', ud_get_wp_property()->domain ) ?>
      </th>
        <td>
        <ul>
          <li><?php printf(__( 'Thumbnail size for %1s images displayed on %2s page: ', ud_get_wp_property()->domain ), WPP_F::property_label( 'singular' ), WPP_F::property_label( 'plural' ) ) ?> <?php WPP_F::image_sizes_dropdown( "name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . ( isset( $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] ) ? $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] : false ) ); ?></li>
          <li>
          <?php echo WPP_F::checkbox( "name=wpp_settings[configuration][completely_hide_hidden_attributes_in_admin_ui]&label=" . sprintf(__( 'Completely hide hidden attributes when editing %1s.', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ) ), ( isset( $wp_properties[ 'configuration' ][ 'completely_hide_hidden_attributes_in_admin_ui' ] ) ? $wp_properties[ 'configuration' ][ 'completely_hide_hidden_attributes_in_admin_ui' ] : false ) ); ?>
          </li>
        </ul>
      </td>
    </tr>

    <?php do_action( 'wpp_settings_display_tab_bottom' ); ?>

    </table>
  </div>

  <?php
  if( isset( $wpp_plugin_settings_nav ) ) {
    foreach ( (array) $wpp_plugin_settings_nav as $nav ) {
      echo "<div id='tab_{$nav['slug']}'>";
      do_action( "wpp_settings_content_{$nav['slug']}" );
      echo "</div>";
    }
  }
  ?>

  <div id="tab_troubleshooting">
    <div class="wpp_inner_tab wp-core-ui">

      <div class="wpp_settings_block">
        <?php _e( "Restore Backup of WP-Property Configuration", ud_get_wp_property()->domain ); ?>
        : <input name="wpp_settings[settings_from_backup]" class="" id="wpp_backup_file" type="file"/>
        <a href="<?php echo wp_nonce_url( "edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup", 'download-wpp-backup' ); ?>"><?php _e( 'Download Backup of Current WP-Property Configuration.', ud_get_wp_property()->domain ); ?></a>
      </div>

      <div class="wpp_settings_block">
        <?php $google_map_localizations = WPP_F::draw_localization_dropdown( 'return_array=true' ); ?>
        <?php _e( 'Revalidate all addresses using', ud_get_wp_property()->domain ); ?>
        <b><?php echo $google_map_localizations[ $wp_properties[ 'configuration' ][ 'google_maps_localization' ] ]; ?></b> <?php _e( 'localization', ud_get_wp_property()->domain ); ?>
         <input type="button" value="<?php _e( 'Revalidate', ud_get_wp_property()->domain ); ?>" id="wpp_ajax_revalidate_all_addresses" class="button">
      </div>

      <div class="wpp_settings_block"><?php printf(__( 'Enter in the ID of the %1s you want to look up, and the class will be displayed below.', ud_get_wp_property()->domain ), WPP_F::property_label( 'singular' )) ?>
        <input type="text" id="wpp_property_class_id"/>
        <input type="button" class="button" value="<?php _e( 'Lookup', ud_get_wp_property()->domain ) ?>" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden"><?php _e( 'Cancel', ud_get_wp_property()->domain ) ?></span>
        <pre id="wpp_ajax_property_result" class="wpp_class_pre hidden"></pre>
      </div>

      <div class="wpp_settings_block"><?php printf(__( 'Get %1s image data.', ud_get_wp_property()->domain ), WPP_F::property_label( 'singular' )) ?>
        <label for="wpp_image_id"><?php printf(__( '%1s ID:', ud_get_wp_property()->domain ), WPP_F::property_label( 'singular' )) ?></label>
        <input type="text" id="wpp_image_id"/>
        <input type="button" class="button" value="<?php _e( 'Lookup', ud_get_wp_property()->domain ) ?>" id="wpp_ajax_image_query"> <span id="wpp_ajax_image_query_cancel" class="wpp_link hidden"><?php _e( 'Cancel', ud_get_wp_property()->domain ) ?></span>
        <pre id="wpp_ajax_image_result" class="wpp_class_pre hidden"></pre>
      </div>

      <div class="wpp_settings_block">
        <?php _e( 'Look up the <b>$wp_properties</b> global settings array.  This array stores all the default settings, which are overwritten by database settings, and custom filters.', ud_get_wp_property()->domain ) ?>
        <input type="button" class="button" value="<?php _e( 'Show $wp_properties', ud_get_wp_property()->domain ) ?>" id="wpp_show_settings_array"> <span id="wpp_show_settings_array_cancel" class="wpp_link hidden"><?php _e( 'Cancel', ud_get_wp_property()->domain ) ?></span>
        <pre id="wpp_show_settings_array_result" class="wpp_class_pre hidden"><?php print_r( $wp_properties ); ?></pre>
      </div>

      <div class="wpp_settings_block">
        <?php _e( 'Clear WPP Cache. Some shortcodes and widgets use cache, so the good practice is clear it after widget, shortcode changes.', ud_get_wp_property()->domain ) ?>
        <input type="button" class="button" value="<?php _e( 'Clear Cache', ud_get_wp_property()->domain ) ?>" id="wpp_clear_cache">
      </div>

      <div class="wpp_settings_block"><?php printf(__( 'Set all %1s to same %2s type:', ud_get_wp_property()->domain ), WPP_F::property_label( 'plural' ), WPP_F::property_label( 'singular' )) ?>
        <select id="wpp_ajax_max_set_property_type_type">
        <?php foreach ( $wp_properties[ 'property_types' ] as $p_slug => $p_label ) { ?>
          <option value="<?php echo $p_slug; ?>"><?php echo $p_label; ?></option>
        <?php } ?>
          <input type="button" class="button" value="<?php _e( 'Set', ud_get_wp_property()->domain ) ?>" id="wpp_ajax_max_set_property_type">
        <pre id="wpp_ajax_max_set_property_type_result" class="wpp_class_pre hidden"></pre>
      </div>

      <div class="wpp_settings_block">
        <?php if ( function_exists( 'memory_get_usage' ) ): ?>
          <?php _e( 'Memory Usage:', ud_get_wp_property()->domain ); ?> <?php echo round( ( memory_get_usage() / 1048576 ), 2 ); ?> megabytes.
        <?php endif; ?>
        <?php if ( function_exists( 'memory_get_peak_usage' ) ): ?>
          <?php _e( 'Peak Memory Usage:', ud_get_wp_property()->domain ); ?> <?php echo round( ( memory_get_peak_usage() / 1048576 ), 2 ); ?> megabytes.
        <?php endif; ?>
      </div>

      <?php do_action( 'wpp_settings_help_tab' ); ?>
    </div>
  </div>

</div>


<br class="cb"/>

<p class="wpp_save_changes_row">
<input type="submit" value="<?php _e( 'Save Changes', ud_get_wp_property()->domain ); ?>" class="button-primary btn" name="Submit">
 </p>


</form>
</div>

<!--fb-->
<div id="fb-root"></div>
<script type="text/javascript">(function ( d, s, id ) {
    var js, fjs = d.getElementsByTagName( s )[0];
    if ( d.getElementById( id ) ) return;
    js = d.createElement( s );
    js.id = id;
    js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=373515126019844";
    fjs.parentNode.insertBefore( js, fjs );
  }( document, 'script', 'facebook-jssdk' ));</script>