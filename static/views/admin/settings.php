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
global $wp_properties;


$object_label = array(
  'singular' => WPP_F::property_label('singular'),
  'plural' => WPP_F::property_label('plural')
);

$wrapper_classes = array('wpp_settings_page');

if (isset($_REQUEST['message'])) {
  $wp_messages = array();
  switch ($_REQUEST['message']) {
    case 'updated':
      $wp_messages['notice'][] = __("Settings updated.", ud_get_wp_property()->domain);
    break;
    case 'restored':
      $wp_messages['notice'][] = __("Settings restored from backup.", ud_get_wp_property()->domain);
    break;
  }
}

//** We have to update Rewrite rules here. peshkov@UD */
flush_rewrite_rules();

$parseUrl = parse_url(trim(get_bloginfo('url')));
$this_domain = trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));

/** Check if custom css exists */
$using_custom_css = (file_exists(STYLESHEETPATH . '/wp_properties.css') || file_exists(TEMPLATEPATH . '/wp_properties.css')) ? true : false;

if (get_option('permalink_structure') == '') {
  $wrapper_classes[] = 'no_permalinks';
} else {
  $wrapper_classes[] = 'have_permalinks';
}

if( isset( $_GET['splash'] ) && $_GET['splash'] === 'setup-assistant' ) {
  UsabilityDynamics\WPP\Setup_Assistant::render_page();
  return;
}

$l10n_url = '';
$l10n_id = get_option('wp-property-l10n-attachment');

?>
<div class="wrap <?php echo implode(' ', $wrapper_classes); ?>">



  <h2 class='wpp_settings_page_header'><?php echo ud_get_wp_property('labels.name') . ' ' . __('Settings', ud_get_wp_property()->domain) ?>

    <?php  if( WP_PROPERTY_SETUP_ASSISTANT ) { ?>
      <a class="wpp-setup-asst" href="<?php echo admin_url('edit.php?post_type=property&page=property_settings&splash=setup-assistant'); ?>">
        <?php echo __('Setup Assistant', ud_get_wp_property()->domain); ?>
      </a>
    <?php } ?>


  </h2>

  <?php if (isset($wp_messages['error']) && $wp_messages['error']): ?>
    <div class="error">
      <?php foreach ($wp_messages['error'] as $error_message): ?>
      <p><?php echo $error_message; ?>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (isset($wp_messages['notice']) && $wp_messages['notice']): ?>
    <div class="updated fade">
      <?php foreach ($wp_messages['notice'] as $notice_message): ?>
      <p><?php echo $notice_message; ?>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form id="wpp_settings_form" method="post" action="<?php echo admin_url('edit.php?post_type=property&page=property_settings'); ?>" enctype="multipart/form-data">
    <?php wp_nonce_field('wpp_setting_save'); ?>

    <div id="wpp_settings_tabs" class="wpp_tabs clearfix">

      <?php if( WP_PROPERTY_SETTINGS_SEARCH ) { ?>
        <div id="label-search-text"><input type="text" id="wpp_search_tags" placeholder="Settings search" name="search_tags"/></div>
      <?php } ?>

      <ul class="tabs">
        <li><a href="#tab_main"><?php _e('Main', ud_get_wp_property()->domain); ?></a></li>
        <li><a href="#tab_display"><?php _e('Display', ud_get_wp_property()->domain); ?></a></li>
        <?php
        $wpp_plugin_settings_nav = apply_filters('wpp_settings_nav', array());
        if (is_array($wpp_plugin_settings_nav)) {
          foreach ($wpp_plugin_settings_nav as $nav) {
            echo "<li><a href='#tab_{$nav['slug']}'>{$nav['title']}</a></li>\n";
          }
        }
        ?>
        <li><a href="#tab_troubleshooting"><?php _e('Help', ud_get_wp_property()->domain); ?></a></li>
        <li><a href="#tab_feedback"><?php _e('Support', ud_get_wp_property()->domain); ?></a></li>
        <!--<li id="label-search-text"></li>-->
      </ul>

      <div id="tab_main">

        <?php do_action('wpp_settings_main_top', $wp_properties); ?>

        <table class="form-table">

          <tr>
            <th><?php _e('Options', ud_get_wp_property()->domain); ?></th>
            <td>
              <ul>
                <li class="configuration_enable_comments"><?php echo WPP_F::checkbox("name=wpp_settings[configuration][enable_comments]&label=" . __('Enable comments', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['enable_comments']) ? $wp_properties['configuration']['enable_comments'] : false)); ?></li>
                <li class="configuration_enable_revsions" data-feature-since="2.0.0"><?php echo WPP_F::checkbox("name=wpp_settings[configuration][enable_revisions]&label=" . __('Enable revisions', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['enable_revisions']) ? $wp_properties['configuration']['enable_revisions'] : false)); ?></li>
                <li class="wpp-setting-exclude-from-regular-search-results"><?php echo WPP_F::checkbox("name=wpp_settings[configuration][exclude_from_regular_search_results]&label=" . sprintf(__('Exclude %1s from regular search results.', ud_get_wp_property()->domain), $object_label['plural']), (isset($wp_properties['configuration']['exclude_from_regular_search_results']) ? $wp_properties['configuration']['exclude_from_regular_search_results'] : false)); ?></li>

              </ul>
            </td>
          </tr>

          <tr class="wpp-setting wpp-setting-default-property-page">
            <th><?php printf(__('Default %1s Page', ud_get_wp_property()->domain), ud_get_wp_property('labels.name')); ?></th>
            <td>

              <div class="must_have_permalinks">
                <select name="wpp_settings[configuration][base_slug]" id="wpp_settings_base_slug"
                        class="wpp_settings_base_slug">
                  <?php foreach (get_pages() as $page): ?>
                    <option <?php selected($wp_properties['configuration']['base_slug'], $page->post_name); ?>
                      value="<?php echo $page->post_name; ?>"><?php echo $page->post_title; ?></option>
                  <?php endforeach; ?>
                </select>
                <span wpp_scroll_to="h3.default_property_page"
                      class="wpp_link wpp_toggle_contextual_help"><?php _e('What is this?', ud_get_wp_property()->domain); ?></span>
              </div>
              <div class="must_not_have_permalinks">
                <p
                  class="description"><?php printf(__('You must have permalinks enabled to change the Default %1s page.', ud_get_wp_property()->domain), ud_get_wp_property('labels.name')); ?></p>
              </div>

            </td>
          </tr>

          <tr class="wpp_non_property_page_settings hidden">
            <th>&nbsp;</th>
            <td>
              <ul>
                <li>
                  <?php echo WPP_F::checkbox('name=wpp_settings[configuration][automatically_insert_overview]&label=' . __('Automatically overwrite this page\'s content with [property_overview].', ud_get_wp_property()->domain), $wp_properties['configuration']['automatically_insert_overview']); ?>
                </li>
                <li
                  class="wpp_wpp_settings_configuration_do_not_override_search_result_page_row <?php if ($wp_properties['configuration']['automatically_insert_overview'] == 'true') echo " hidden "; ?>">
                  <?php echo WPP_F::checkbox("name=wpp_settings[configuration][do_not_override_search_result_page]&label=" . __('When showing property search results, don\'t override the page content with [property_overview].', ud_get_wp_property()->domain), isset($wp_properties['configuration']['do_not_override_search_result_page']) ? $wp_properties['configuration']['do_not_override_search_result_page'] : false); ?>
                  <div
                    class="description"><?php _e('If checked, be sure to include [property_overview] somewhere in the content, or no properties will be displayed.', ud_get_wp_property()->domain); ?></div>
                </li>
              </ul>
            </td>
          </tr>

          <?php if( !WP_PROPERTY_LAYOUTS )  { ?>

            <tr class="wpp-setting wpp-setting-single-template">
              <th><?php printf(__('Single %s Template', ud_get_wp_property()->domain), WPP_F::property_label()); ?></th>
              <td>
                <p><?php printf(__('Select template which will be used to render Single %s page.', ud_get_wp_property('domain')), WPP_F::property_label()); ?></p>
                <p><?php printf(__('You also can redeclare selected template for specific %s on Edit %s page.', ud_get_wp_property('domain')), WPP_F::property_label(), WPP_F::property_label()); ?></p>
                <p><?php printf(__('Note, you can use Single or Page templates for building your own layouts via %s or another Layouts Framework.', ud_get_wp_property('domain')), '<a target="_blank" href="https://siteorigin.com/page-builder/">SiteOrigin Page Builder</a>'); ?></p>
                <br/>
                <ul>
                  <li>
                    <label><input type="radio" name="wpp_settings[configuration][single_property][template]"
                                  value="property" <?php echo empty($wp_properties['configuration']['single_property']['template']) || $wp_properties['configuration']['single_property']['template'] == 'property' ? 'checked' : ''; ?> /> <?php printf('Default Property Template', ud_get_wp_property('domain')); ?>
                      .</label>
                    <p><span
                        class="description"><?php printf(__('By default, %s plugin uses custom <b>%s</b> template for rendering Single %s page.', ud_get_wp_property('domain')), 'WP-Property', 'property.php', WPP_F::property_label()); ?></span>
                    </p>
                    <p><span
                        class="description"><?php printf(__('The template contains predefined sections such as attributes list, map and registered sidebars areas.', ud_get_wp_property('domain'))); ?></span>
                    </p>
                    <p><span
                        class="description"><?php printf(__('The display settings may be edited further by customizing the <b>%s</b> file.', ud_get_wp_property('domain')), 'wp-content/plugins/wp-property/static/views/property.php') ?></span>
                    </p>
                    <p><span
                        class="description"><?php printf(__('To avoid losing your changes during updates, copy <b>%s</b> file to your template directory, which will be automatically loaded.', ud_get_wp_property('domain')), 'property.php'); ?></span>
                    </p><br/>
                    <p><?php printf(__('Additional settings for Default %s Template', ud_get_wp_property()->domain), WPP_F::property_label()); ?>
                      :</p>
                    <ul>
                      <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][property_overview][sort_stats_by_groups]&label=" . sprintf(__('Sort %1s stats by groups.', ud_get_wp_property()->domain), WPP_F::property_label('singular')), (isset($wp_properties['configuration']['property_overview']['sort_stats_by_groups']) ? $wp_properties['configuration']['property_overview']['sort_stats_by_groups'] : false)); ?></li>
                      <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][property_overview][show_true_as_image]&label=" . sprintf(__('Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', ud_get_wp_property()->domain), __('Yes', ud_get_wp_property()->domain), __('No', ud_get_wp_property()->domain), __('Yes', ud_get_wp_property()->domain), __('No', ud_get_wp_property()->domain)), (isset($wp_properties['configuration']['property_overview']['show_true_as_image']) ? $wp_properties['configuration']['property_overview']['show_true_as_image'] : false)); ?></li>
                      <?php do_action('wpp_settings_page_property_page'); ?>
                    </ul>
                    <br/>
                  </li>
                  <li>
                    <label><input type="radio" name="wpp_settings[configuration][single_property][template]"
                                  value="single" <?php echo !empty($wp_properties['configuration']['single_property']['template']) && $wp_properties['configuration']['single_property']['template'] == 'single' ? 'checked' : ''; ?> /> <?php printf('Single Post Template', ud_get_wp_property('domain')); ?>
                      .</label>
                    <p><span
                        class="description"><?php printf(__('The single post template file <b>%s</b> in your theme will be used to render a Single %s page', ud_get_wp_property('domain')), 'single.php', WPP_F::property_label()); ?></span>
                    </p>
                    <p><span
                        class="description"><?php printf(__('You can create your own single post template file <b>%s</b> in your theme which will be used instead of <b>%s</b>. %sMore Details%s.', ud_get_wp_property('domain')), 'single-property.php', 'single.php', '<a target="_blank" href="https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post">', '</a>'); ?></span>
                    </p>
                    <p><span
                        class="description"><?php printf(__('Note, registered <b>%s sidebars</b> are defined only in default <b>%s</b> template. You have to add them manually to your theme\'s template.', ud_get_wp_property('domain')), 'WP-Property', 'property.php'); ?></span>
                    </p><br/>
                  </li>
                  <li>
                    <label><input type="radio" name="wpp_settings[configuration][single_property][template]"
                                  value="page" <?php echo !empty($wp_properties['configuration']['single_property']['template']) && $wp_properties['configuration']['single_property']['template'] == 'page' ? 'checked' : ''; ?> /> <?php printf('Page Template', ud_get_wp_property('domain')); ?>
                      .</label>
                    <span>
              <label><?php printf(__('Select page template which you want to use on single %s page', ud_get_wp_property('domain')), WPP_F::property_label()); ?></label>
              <select name="wpp_settings[configuration][single_property][page_template]">
                <option
                  value="default" <?php echo !empty($wp_properties['configuration']['single_property']['page_template']) && $wp_properties['configuration']['single_property']['page_template'] == 'default' ? 'selected="selected"' : ''; ?> ><?php _e('Default Template', ud_get_wp_property('domain')); ?></option>
                <?php foreach (get_page_templates() as $title => $slug) : ?>
                  <option
                    value="<?php echo $slug ?>" <?php echo !empty($wp_properties['configuration']['single_property']['page_template']) && $wp_properties['configuration']['single_property']['page_template'] == $slug ? 'selected="selected"' : ''; ?> ><?php echo $title; ?></option>
                <?php endforeach; ?>
              </select>
            </span>
                    <p><span
                        class="description"><?php printf(__('Page template will be used to render a Single %s page. %sMore Details%s.', ud_get_wp_property('domain')), WPP_F::property_label(), '<a target="_blank" href="https://developer.wordpress.org/themes/template-files-section/page-template-files/page-templates/">', '</a>'); ?></span>
                    </p>
                    <p><span
                        class="description"><?php printf(__('Note, registered <b>%s sidebars</b> are defined only in default <b>%s</b> template. You have to add them manually to your theme\'s template.', ud_get_wp_property('domain')), 'WP-Property', 'property.php'); ?></span>
                    </p>
                  </li>
                </ul>
              </td>
            </tr>
          <?php } ?>

          <?php if ((!isset($wp_properties['configuration']['do_not_register_sidebars']) || (isset($wp_properties['configuration']['do_not_register_sidebars']) && $wp_properties['configuration']['do_not_register_sidebars'] != 'true')) && !WP_PROPERTY_LAYOUTS ) : ?>
            <tr class="wpp-setting wpp-setting-widget-sidebars">
              <th><?php printf(__('Widget Sidebars', ud_get_wp_property()->domain), WPP_F::property_label()); ?></th>
              <td>
                <p><?php printf(__('By default, %1$s registers widget sidebars for <b>Single %2$s page</b> based on defined %2$s types. But you can disable any of them here.', ud_get_wp_property('domain')), 'WP-Property', WPP_F::property_label()); ?></p>
                <p><?php printf(__('Note, the following sidebar are added only on default <b>%s</b> ( Default %s Template ).', ud_get_wp_property('domain')), 'property.php', WPP_F::property_label()); ?></p>
                <br/>
                <ul>
                  <?php foreach ((array)$wp_properties['property_types'] as $slug => $title) : ?>
                    <li>
                      <?php echo WPP_F::checkbox("name=wpp_settings[configuration][disable_widgets][wpp_sidebar_{$slug}]&label=" . sprintf(__('Disable <b>%s</b> Sidebar.', ud_get_wp_property('domain')), WPP_F::property_label() . ': ' . $title), (isset($wp_properties['configuration']['disable_widgets']['wpp_sidebar_' . $slug]) ? $wp_properties['configuration']['disable_widgets']['wpp_sidebar_' . $slug] : false)); ?>
                      <span class="description"><code>dynamic_sidebar( "wpp_sidebar_<?php echo $slug ?>" )</code></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </td>
            </tr>
          <?php endif; ?>

          <tr>
            <th><?php printf(__('Automatic Geolocation', ud_get_wp_property()->domain), WPP_F::property_label()); ?></th>
            <td>
              <ul>
                <li><?php _e('Attribute to use for physical addresses:', ud_get_wp_property('domain')); ?><?php echo WPP_F::draw_attribute_dropdown("name=wpp_settings[configuration][address_attribute]&selected={$wp_properties[ 'configuration' ]['address_attribute']}"); ?></li>
                <li><?php _e('Localize addresses in:', ud_get_wp_property('domain')); ?><?php echo WPP_F::draw_localization_dropdown("name=wpp_settings[configuration][google_maps_localization]&selected={$wp_properties[ 'configuration' ]['google_maps_localization']}"); ?></li>
                <li class="google-maps-api-section" data-feature-since="2.0.3">
                  <?php printf(__('Google Maps API (Browser Key):', ud_get_wp_property('domain'))); ?>
                  <?php echo WPP_F::input("name=wpp_settings[configuration][google_maps_api]", ud_get_wp_property('configuration.google_maps_api')); ?>

                  <br/><span
                    class="description"><?php printf(__('Note, Google Maps has its own limit of usage. You need to provide Google Maps API license ( browser key ) above to increase limit. See more details in %shelp tab%s.', ud_get_wp_property('domain')), '<a href="#tab-link-google-map-api-key" class="open-help-tab">', '</a>'); ?></span>
                </li>
                <li class="google-maps-api-section" data-feature-since="2.0.3">
                  <?php printf(__('Google Maps API (Server Key):', ud_get_wp_property('domain'))); ?>
                  <?php echo WPP_F::input("name=wpp_settings[configuration][google_maps_api_server]", ud_get_wp_property('configuration.google_maps_api_server')); ?>

                  <br/><span
                    class="description"><?php printf(__('You need to  provide Google Maps API license ( server key ) above. See more details in %shelp tab%s.', ud_get_wp_property('domain')), '<a href="#tab-link-google-map-api-key" class="open-help-tab">', '</a>'); ?></span>
                </li>
              </ul>
            </td>
          </tr>

          <?php if( WP_PROPERTY_LEGACY_META_ATTRIBUTES ) { ?>
          <tr class="wpp-setting wpp-setting-default-phone-number">
            <th><?php _e('Default Phone Number', ud_get_wp_property()->domain); ?></th>
            <td><?php echo WPP_F::input("name=phone_number&label=" . sprintf(__('Phone number to use when a %1s-specific phone number is not specified.', ud_get_wp_property()->domain), WPP_F::property_label('singular')) . "&group=wpp_settings[configuration]&style=width: 200px;", (isset($wp_properties['configuration']['phone_number']) ? $wp_properties['configuration']['phone_number'] : false)); ?></td>
          </tr>
          <?php } ?>

          <tr>
            <th><?php _e('Advanced Options', ud_get_wp_property()->domain); ?></th>
            <td>
              <div class="wpp_settings_block"><br/>
                <ul>
                  <?php if (apply_filters('wpp::custom_styles', false) === false) : ?>
                    <li>
                      <?php echo $using_custom_css ? WPP_F::checkbox("name=wpp_settings[configuration][autoload_css]&label=" . __('Load default CSS.', ud_get_wp_property()->domain), $wp_properties['configuration']['autoload_css']) : WPP_F::checkbox("name=wpp_settings[configuration][autoload_css]&label=" . __('Load default CSS.', ud_get_wp_property()->domain), $wp_properties['configuration']['autoload_css']); ?>
                      <span
                        class="description"><?php printf(__('If unchecked, the %s in your theme folder will not be loaded.', ud_get_wp_property()->domain), 'wp-properties.css') ?></span>
                    </li>
                    <?php if (WPP_F::has_theme_specific_stylesheet()) : ?>
                      <li>
                        <?php echo WPP_F::checkbox("name=wpp_settings[configuration][do_not_load_theme_specific_css]&label=" . __('Do not load theme-specific stylesheet.', ud_get_wp_property()->domain), isset($wp_properties['configuration']['do_not_load_theme_specific_css']) ? $wp_properties['configuration']['do_not_load_theme_specific_css'] : false); ?>
                        <span
                          class="description"><?php _e('This version of WP-Property has a stylesheet made specifically for the theme you are using.', ud_get_wp_property()->domain); ?></span>
                      </li>
                    <?php endif; /* WPP_F::has_theme_specific_stylesheet() */ ?>
                  <?php endif; ?>

                  <li>
                    <?php echo WPP_F::checkbox("name=wpp_settings[configuration][enable_legacy_features]&label=" . __('Enable Legacy Features.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['enable_legacy_features']) ? $wp_properties['configuration']['enable_legacy_features'] : false)); ?>
                    <span class="description"><?php printf(__('If checked deprecated features will be enabled. E.g.: Child %1$s and Featured %1$s Widgets, etc', ud_get_wp_property()->domain), WPP_F::property_label('plural')) ?></span>
                  </li>

                  <li class="wpp-depreciated-option">
                    <?php echo WPP_F::checkbox("name=wpp_settings[configuration][allow_parent_deep_depth]&label=" . __('Enable \'Falls Under\' deep depth.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['allow_parent_deep_depth']) ? $wp_properties['configuration']['allow_parent_deep_depth'] : false)); ?>
                    <span class="description"><?php printf(__('Allows to set child %1s as parent.', ud_get_wp_property()->domain), WPP_F::property_label('singular')) ?></span>
                  </li>

                  <li class="wpp-depreciated-option">
                    <?php echo WPP_F::checkbox("name=wpp_settings[configuration][disable_wordpress_postmeta_cache]&label=" . __('Disable WordPress update_post_caches() function.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['disable_wordpress_postmeta_cache']) ? $wp_properties['configuration']['disable_wordpress_postmeta_cache'] : false)); ?>
                    <span class="description"><?php printf(__('This may solve Out of Memory issues if you have a lot of %1s.', ud_get_wp_property()->domain), WPP_F::property_label('plural')); ?></span>
                  </li>
                  <li>
                    <?php echo WPP_F::checkbox("name=wpp_settings[configuration][developer_mode]&label=" . __('Enable developer mode - some extra information displayed via Firebug console.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['developer_mode']) ? $wp_properties['configuration']['developer_mode'] : false)); ?>
                    <br/>
                  </li>
                  <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][auto_delete_attachments]&label=" . sprintf(__('Automatically delete all %1s images and attachments when a %2s is deleted.', ud_get_wp_property()->domain), $object_label['singular'], $object_label['singular']), (isset($wp_properties['configuration']['auto_delete_attachments']) ? $wp_properties['configuration']['auto_delete_attachments'] : false)); ?></li>

                  <li class="wpp-legacy-feature">
                    <?php echo WPP_F::checkbox("name=wpp_settings[configuration][automatically_regenerate_thumbnail]&label=" . __('Enable "on-the-fly" image regeneration.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['automatically_regenerate_thumbnail']) ? $wp_properties['configuration']['automatically_regenerate_thumbnail'] : true)); ?>
                    <span class="description"><?php _e('Enabling this option may cause performance issues.', ud_get_wp_property()->domain); ?></span>
                  </li>

                  <?php if( WP_PROPERTY_FLAG_ENABLE_STANDARD_ATTRIBUTES_MATCHING ) { ?>
                  <li>
                    <?php //show standard attribute matching
                    echo WPP_F::checkbox("name=wpp_settings[configuration][show_advanced_options]&label=" . __('Enable Standard Attributes Matching and Terms', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['show_advanced_options']) ? $wp_properties['configuration']['show_advanced_options'] : false)); ?>
                    <i class="description wpp-notice-for-match" title="<?php _e('This option is designed to help us find which attribute you want to show as Price, Address, etc and place it in correct place in our templates.', ud_get_wp_property()->domain); ?>"></i>
                  </li>
                  <?php } ?>

                  <li>
                    <?php echo WPP_F::checkbox("name=wpp_settings[configuration][pre_release_update]&label=" . __('Enable pre-release updates.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['pre_release_update']) ? $wp_properties['configuration']['pre_release_update'] : false)); ?>
                    <br/>
                  </li>

                  <li><?php echo WPP_F::checkbox('name=wpp_settings[configuration][using_fancybox]&label=' . sprintf(__('Disable Fancybox option.', ud_get_wp_property()->domain), $object_label['singular']), (isset($wp_properties['configuration']['using_fancybox']) ? $wp_properties['configuration']['using_fancybox'] : false)); ?></li>

                </ul>
              </div>
            </td>
          </tr>

          <?php do_action('wpp_settings_main_tab_bottom', $wp_properties); ?>
        </table>

      </div>

      <div id="tab_display">

        <table class="form-table">
          <tr>
            <th><?php _e('Image Sizes', ud_get_wp_property()->domain); ?></th>
            <td>
              <p><?php _e('Image sizes used throughout the plugin.', ud_get_wp_property()->domain); ?> </p>

              <table id="wpp_image_sizes" class="ud_ui_dynamic_table widefat">
                <thead>
                <tr>
                  <th><?php _e('Slug', ud_get_wp_property()->domain); ?></th>
                  <th><?php _e('Width', ud_get_wp_property()->domain); ?></th>
                  <th><?php _e('Height', ud_get_wp_property()->domain); ?></th>
                  <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $wpp_image_sizes = $wp_properties['image_sizes'];

                foreach (array_unique((array)get_intermediate_image_sizes()) as $slug):

                  $slug = trim($slug);

                  // We return all, including images with zero sizes, to avoid default data overriding what we save
                  $image_dimensions = WPP_F::image_sizes($slug, "return_all=true");

                  // Skip images w/o dimensions
                  if (!$image_dimensions)
                    continue;

                  // Disable if WP not a WPP image size
                  if (!isset($wpp_image_sizes[$slug]) || !is_array($wpp_image_sizes[$slug]))
                    $disabled = true;
                  else
                    $disabled = false;

                  if (!$disabled):
                    ?>
                    <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>">
                      <td class="wpp_slug">
                        <input class="slug_setter slug wpp_slug_can_be_empty" type="text" value="<?php echo $slug; ?>"/>
                      </td>
                      <td class="wpp_width">
                        <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][width]"
                               value="<?php echo $image_dimensions['width']; ?>"/>
                      </td>
                      <td class="wpp_height">
                        <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][height]"
                               value="<?php echo $image_dimensions['height']; ?>"/>
                      </td>
                      <td><span
                          class="wpp_delete_row wpp_link"><?php _e('Delete', ud_get_wp_property()->domain) ?></span>
                      </td>
                    </tr>

                  <?php else: ?>
                    <tr>
                      <td>
                        <input class="slug_setter slug wpp_slug_can_be_empty" type="text" disabled="disabled"
                               value="<?php echo $slug; ?>"/>
                      </td>
                      <td>
                        <input type="text" disabled="disabled" value="<?php echo $image_dimensions['width']; ?>"/>
                      </td>
                      <td>
                        <input type="text" disabled="disabled" value="<?php echo $image_dimensions['height']; ?>"/>
                      </td>
                      <td>&nbsp;</td>
                    </tr>

                  <?php endif; ?>


                <?php endforeach; ?>

                </tbody>
                <tfoot>
                <tr>
                  <td colspan='4'><input type="button" class="wpp_add_row button-secondary"
                                         value="<?php _e('Add Row', ud_get_wp_property()->domain) ?>"/></td>
                </tr>
                </tfoot>
              </table>

            </td>
          </tr>

          <tr>
            <th><?php printf(__('Default %s image', ud_get_wp_property('domain')), \WPP_F::property_label()); ?></th>
            <td>
              <p>
                <?php printf(__('Setup image which will be used by default for all %s without images.', ud_get_wp_property('domain')), \WPP_F::property_label('plural')); ?>
                <br/>
                <?php printf(__('Note, you also can setup default image for every %s type on Developer tab. So, that image will be used instead of current one.', ud_get_wp_property('domain')), \WPP_F::property_label()); ?>
              </p>
              <div class="upload-image-section">
                <input type="hidden" name="wpp_settings[configuration][default_image][default][url]"
                       class="input-image-url"
                       value="<?php echo isset($wp_properties['configuration']['default_image']['default']['url']) ? $wp_properties['configuration']['default_image']['default']['url'] : ''; ?>">
                <input type="hidden" name="wpp_settings[configuration][default_image][default][id]"
                       class="input-image-id"
                       value="<?php echo isset($wp_properties['configuration']['default_image']['default']['id']) ? $wp_properties['configuration']['default_image']['default']['id'] : ''; ?>">
                <div class="image-actions">
                  <input type="button" class="button-secondary button-setup-image"
                         value="<?php _e('Setup Image', ud_get_wp_property('domain')); ?>">
                </div>
                <div class="image-wrapper"></div>
              </div>
            </td>
          </tr>

          <tr>
            <th><?php _e('Overview Shortcode', ud_get_wp_property()->domain) ?></th>
            <td>
              <p>
                <?php printf(__('These are the settings for the <b>%s</b> shortcode and %s Overview widget. The shortcode (widget) displays a list of all %s.<br />The display settings may be edited further by customizing the <b>%s</b> file.  To avoid losing your changes during updates, copy <b>%s</b> file in your theme\'s root directory, which will be automatically loaded.', ud_get_wp_property()->domain), '[property_overview]', WPP_F::property_label(), WPP_F::property_label('plural'), 'wp-content/plugins/wp-property/static/views/property-overview.php', 'property-overview.php'); ?>
              </p>
              <br/>
              <ul>
                <li><?php _e('Thumbnail size:', ud_get_wp_property()->domain) ?><?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties['configuration']['property_overview']['thumbnail_size']); ?></li>
                <li><?php _e("Default Type of Pagination", ud_get_wp_property()->domain) ?>:
                  <select name="wpp_settings[configuration][property_overview][pagination_type]">
                    <option
                      value="slider" <?php if (isset($wp_properties['configuration']['property_overview']['pagination_type'])) selected($wp_properties['configuration']['property_overview']['pagination_type'], 'slider'); ?>><?php _e('Slider', ud_get_wp_property()->domain); ?>
                      (slider)
                    </option>
                    <option
                      value="numeric" <?php if (isset($wp_properties['configuration']['property_overview']['pagination_type'])) selected($wp_properties['configuration']['property_overview']['pagination_type'], 'numeric'); ?>><?php _e('Numeric', ud_get_wp_property()->domain); ?>
                      (numeric)
                    </option>
                    <option
                      value="loadmore" <?php if (isset($wp_properties['configuration']['property_overview']['pagination_type'])) selected($wp_properties['configuration']['property_overview']['pagination_type'], 'loadmore'); ?>><?php _e('Load more', ud_get_wp_property()->domain); ?>
                      (button)
                    </option>
                  </select>
                  <span
                    class="description"><?php printf(__('You always can set pagination type for specific shortcode or widget manually. Example: %s', ud_get_wp_property('domain')), '<code>[property_overview pagination_type=numeric]</code>'); ?></span>
                </li>
                <li><?php echo WPP_F::checkbox('name=wpp_settings[configuration][property_overview][show_children]&label=' . sprintf(__('Show children %1s.', ud_get_wp_property()->domain), $object_label['plural']), $wp_properties['configuration']['property_overview']['show_children']); ?></li>
                <li><?php echo WPP_F::checkbox('name=wpp_settings[configuration][property_overview][fancybox_preview]&label=' . sprintf(__('Show larger image of %1s when image is clicked using fancybox.', ud_get_wp_property()->domain), $object_label['singular']), $wp_properties['configuration']['property_overview']['fancybox_preview']); ?></li>
                <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][bottom_insert_pagenation]&label=" . __('Show pagination on bottom of results.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['bottom_insert_pagenation']) ? $wp_properties['configuration']['bottom_insert_pagenation'] : false)); ?></li>
                <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][property_overview][add_sort_by_title]&label=" . sprintf(__('Add sorting by %1s\'s title.', ud_get_wp_property()->domain), $object_label['singular']), (isset($wp_properties['configuration']['property_overview']['add_sort_by_title']) ? $wp_properties['configuration']['property_overview']['add_sort_by_title'] : false)); ?></li>
                <?php do_action('wpp::settings::display::overview_shortcode'); ?>
              </ul>

            </td>
          </tr>

          <tr>
            <th><?php _e('Google Maps', ud_get_wp_property()->domain) ?></th>
            <td>

              <ul>
                <li><?php _e('Map Thumbnail Size:', ud_get_wp_property()->domain); ?><?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . (isset($wp_properties['configuration']['single_property_view']['map_image_type']) ? $wp_properties['configuration']['single_property_view']['map_image_type'] : '')); ?></li>
                <li><?php _e('Map Zoom Level:', ud_get_wp_property()->domain); ?><?php echo WPP_F::input("name=wpp_settings[configuration][gm_zoom_level]&style=width: 30px;", (isset($wp_properties['configuration']['gm_zoom_level']) ? $wp_properties['configuration']['gm_zoom_level'] : false)); ?></li>
                <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][show_true_as_image]&label=" . sprintf(__('Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values', ud_get_wp_property()->domain), __('Yes', ud_get_wp_property()->domain), __('No', ud_get_wp_property()->domain), __('Yes', ud_get_wp_property()->domain), __('No', ud_get_wp_property()->domain)), (isset($wp_properties['configuration']['google_maps']['show_true_as_image']) ? $wp_properties['configuration']['google_maps']['show_true_as_image'] : false)); ?></li>
              </ul>

              <p><?php printf(__('Attributes to display in popup after a %1s on a map is clicked.', ud_get_wp_property()->domain), WPP_F::property_label('singular')); ?></p>
              <div class="wp-tab-panel">
                <ul>

                  <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][show_property_title]&label=" . sprintf(__('Show %1s Title', ud_get_wp_property()->domain), WPP_F::property_label('singular')), $wp_properties['configuration']['google_maps']['infobox_settings']['show_property_title']); ?></li>

                  <?php foreach ($wp_properties['property_stats'] as $attrib_slug => $attrib_title): ?>
                    <li><?php
                      $checked = (in_array($attrib_slug, $wp_properties['configuration']['google_maps']['infobox_attributes']) ? true : false);
                      echo WPP_F::checkbox("id=google_maps_attributes_{$attrib_title}&name=wpp_settings[configuration][google_maps][infobox_attributes][]&label=$attrib_title&value={$attrib_slug}", $checked);
                      ?></li>
                  <?php endforeach; ?>

                  <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][show_direction_link]&label=" . __('Show Directions Link', ud_get_wp_property()->domain), $wp_properties['configuration']['google_maps']['infobox_settings']['show_direction_link']); ?></li>
                  <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][do_not_show_child_properties]&label=" . sprintf(__('Do not show a list of child %1s in Infobox. ', ud_get_wp_property()->domain), WPP_F::property_label('plural')), isset( $wp_properties['configuration']['google_maps']['infobox_settings']['do_not_show_child_properties']) ? $wp_properties['configuration']['google_maps']['infobox_settings']['do_not_show_child_properties'] : false ); ?></li>
                  <li><?php echo WPP_F::checkbox( "name=wpp_settings[configuration][google_maps][infobox_settings][show_child_property_attributes]&label=" . sprintf(__( 'Show attributes of child %1s in Infobox. ', ud_get_wp_property()->domain ),  WPP_F::property_label( 'plural' )), isset( $wp_properties['configuration']['google_maps']['infobox_settings']['show_child_property_attributes']) ? $wp_properties['configuration']['google_maps']['infobox_settings']['show_child_property_attributes'] : false ); ?></li>
                </ul>
              </div>
              <ul>
                <li>
                  <?php _e("Infobox window style", ud_get_wp_property()->domain) ?>:
                  <select name="wpp_settings[configuration][google_maps][infobox_settings][infowindow_styles]">
                    <option value="default" <?php if (isset($wp_properties['configuration']['google_maps']['infobox_settings']['infowindow_styles'])) selected($wp_properties['configuration']['google_maps']['infobox_settings']['infowindow_styles'], 'default'); ?>><?php _e('Default', ud_get_wp_property()->domain); ?></option>
                    <option value="new" <?php if (isset($wp_properties['configuration']['google_maps']['infobox_settings']['infowindow_styles'])) selected($wp_properties['configuration']['google_maps']['infobox_settings']['infowindow_styles'], 'new'); ?>><?php _e('New', ud_get_wp_property()->domain); ?></option>
                  </select>
                </li>
              </ul>
            </td>
          </tr>

          <tr>
            <th><?php _e('Address Display', ud_get_wp_property()->domain) ?></th>
            <td>


              <textarea name="wpp_settings[configuration][display_address_format]"
                        style="width: 70%;"><?php echo $wp_properties['configuration']['display_address_format']; ?></textarea>
              <br/>
              <span class="description">
               <?php _e('Available tags:', ud_get_wp_property()->domain) ?> [street_number] [street_name], [city], [state], [state_code], [county],  [country], [zip_code].
        </span>
            </td>
          </tr>

          <tr>
            <th><?php _e('Area dimensions', ud_get_wp_property()->domain) ?></th>
            <td>
              <p>
                <?php _e('Choose which dimension will have Area attribute.', ud_get_wp_property()->domain); ?>
                <br/>
                <span class="description">
                  <?php _e('Attribute with the slug area should be added in developer tab and numeric data entry should be set up. Then you will see following dimension after attribute’s value', ud_get_wp_property()->domain); ?>
                </span>
              </p>
              <br/>
              <ul>
                <li><?php echo WPP_F::input("name=area_dimensions&group=wpp_settings[configuration]&style=width: 150px;", $wp_properties['configuration']['area_dimensions'] ? $wp_properties['configuration']['area_dimensions'] : 'sq. ft'); ?></li>
              </ul>
            </td>
          </tr>

          <tr>
            <th><?php _e('Currency & Numbers', ud_get_wp_property()->domain); ?></th>
            <td>
              <ul>
                <li><?php echo WPP_F::input("name=currency_symbol&label=" . __('Currency symbol.', ud_get_wp_property()->domain) . "&group=wpp_settings[configuration]&style=width: 50px;", $wp_properties['configuration']['currency_symbol']); ?></li>
                <li>
                  <?php _e('Thousands separator symbol:', ud_get_wp_property()->domain); ?>
                  <select name="wpp_settings[configuration][thousands_sep]">
                    <option value=""> -</option>
                    <option
                      value="." <?php if (isset($wp_properties['configuration']['thousands_sep'])) selected($wp_properties['configuration']['thousands_sep'], '.'); ?>><?php _e('. (period)', ud_get_wp_property()->domain); ?></option>
                    <option
                      value="," <?php if (isset($wp_properties['configuration']['thousands_sep'])) selected($wp_properties['configuration']['thousands_sep'], ','); ?>><?php _e(', (comma)', ud_get_wp_property()->domain); ?></option>
                  </select>
                  <span
                    class="description"><?php _e('The character separating the 1 and the 5: $1<b>,</b>500'); ?></span>

                </li>

                <li>
                  <?php _e('Currency symbol placement:', ud_get_wp_property()->domain); ?>
                  <select name="wpp_settings[configuration][currency_symbol_placement]">
                    <option value=""> -</option>
                    <option
                      value="before" <?php if (isset($wp_properties['configuration']['currency_symbol_placement'])) selected($wp_properties['configuration']['currency_symbol_placement'], 'before'); ?>><?php _e('Before number', ud_get_wp_property()->domain); ?></option>
                    <option
                      value="after" <?php if (isset($wp_properties['configuration']['currency_symbol_placement'])) selected($wp_properties['configuration']['currency_symbol_placement'], 'after'); ?>><?php _e('After number', ud_get_wp_property()->domain); ?></option>
                  </select>

                </li>

                <li>
                  <?php echo WPP_F::checkbox("name=wpp_settings[configuration][show_aggregated_value_as_average]&label=" . __('Parent property\'s aggregated value should be set as average of children values. If not, - the aggregated value will be set as sum of children values.', ud_get_wp_property()->domain), (isset($wp_properties['configuration']['show_aggregated_value_as_average']) ? $wp_properties['configuration']['show_aggregated_value_as_average'] : false)); ?>
                  <br/><span
                    class="description"><?php printf(__('Aggregated value is set only for numeric and currency attributes and can be updated ( set ) only on child %1s\'s saving.', ud_get_wp_property()->domain), WPP_F::property_label('singular')); ?></span>
                </li>

              </ul>
            </td>
          </tr>


          <tr>
            <th>
              <?php _e('Admin Settings', ud_get_wp_property()->domain) ?>
            </th>
            <td>
              <ul>
                <li><?php printf(__('Thumbnail size for %1s images displayed on %2s page: ', ud_get_wp_property()->domain), WPP_F::property_label('singular'), WPP_F::property_label('plural')) ?><?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . (isset($wp_properties['configuration']['admin_ui']['overview_table_thumbnail_size']) ? $wp_properties['configuration']['admin_ui']['overview_table_thumbnail_size'] : false)); ?></li>
                <li>
                  <?php echo WPP_F::checkbox("name=wpp_settings[configuration][completely_hide_hidden_attributes_in_admin_ui]&label=" . sprintf(__('Completely hide hidden attributes when editing %1s.', ud_get_wp_property()->domain), WPP_F::property_label('plural')), (isset($wp_properties['configuration']['completely_hide_hidden_attributes_in_admin_ui']) ? $wp_properties['configuration']['completely_hide_hidden_attributes_in_admin_ui'] : false)); ?>
                </li>
              </ul>
            </td>
          </tr>

          <?php do_action('wpp_settings_display_tab_bottom'); ?>

        </table>
      </div>

      <?php
      if (isset($wpp_plugin_settings_nav)) {
        foreach ((array)$wpp_plugin_settings_nav as $nav) {
          echo "<div id='tab_{$nav['slug']}'>";
          do_action("wpp_settings_content_{$nav['slug']}");
          echo "</div>";
        }
      }
      ?>

      <div id="tab_troubleshooting">
        <div class="wpp_inner_tab wp-core-ui">

          <div class="wpp_settings_block">
            <?php _e("Restore Backup of WP-Property Configuration", ud_get_wp_property()->domain); ?>: <input name="wpp_settings[settings_from_backup]" class="" id="wpp_backup_file" type="file" />
            <br />
            <a href="<?php echo wp_nonce_url("edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup&wpp-backup-type=full", 'download-wpp-backup'); ?>"><?php _e('Download Entire WP-Property Configuration.', ud_get_wp_property()->domain); ?></a>
            <br />
            <a href="<?php echo wp_nonce_url("edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup&wpp-backup-type=fields", 'download-wpp-backup'); ?>"><?php _e('Download Attributes Configuration.', ud_get_wp_property()->domain); ?></a>
          </div>

          <?php
          //list automatic backups taken from setup-assistant screen
          if ( WPP_FEATURE_FLAG_SETTINGS_BACKUPS && get_option("wpp_property_backups")) { ?>
            <div class="wpp_settings_block">
              <?php _e("Automatic Backups of WP-Property Configuration", ud_get_wp_property()->domain); ?>
              <input type="button" value="<?php _e('Backup Now', ud_get_wp_property()->domain); ?>" id="wpp_ajax_create_settings_backup" class="button">

              <?php  if( WP_PROPERTY_SETUP_ASSISTANT ) { ?>
              <span class="description"><?php _e('Backups created when you use Setup Assistant,or create one now.', ud_get_wp_property()->domain); ?> </span>
              <?php } ?>
              <br>
              <div class="wpp_backups_list">
                <?php
                $auto_backups = get_option("wpp_property_backups");
                foreach ($auto_backups as $time => $backups) {
                  echo '<a href="' . wp_nonce_url("edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup&timestamp=" . $time, 'download-wpp-backup') . '">' . date('d-m-Y H:i', $time) . '</a>&nbsp;&nbsp;&nbsp;';
                } ?>
              </div>
            </div>
          <?php } ?>

          <div class="wpp_settings_block">
            <?php $google_map_localizations = WPP_F::draw_localization_dropdown('return_array=true'); ?>
            <?php _e('Revalidate all addresses using', ud_get_wp_property()->domain); ?>
            <b><?php echo $google_map_localizations[$wp_properties['configuration']['google_maps_localization']]; ?></b> <?php _e('localization', ud_get_wp_property()->domain); ?>
            <input type="button" value="<?php _e('Revalidate', ud_get_wp_property()->domain); ?>" id="wpp_ajax_revalidate_all_addresses" class="button">
          </div>

          <div class="wpp_settings_block"><?php printf(__('Enter in the ID of the %1s you want to look up, and the class will be displayed below.', ud_get_wp_property()->domain), WPP_F::property_label('singular')) ?>
            <input type="text" id="wpp_property_class_id"/>
            <input type="button" class="button" value="<?php _e('Lookup', ud_get_wp_property()->domain) ?>" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden"><?php _e('Cancel', ud_get_wp_property()->domain) ?></span>
            <pre id="wpp_ajax_property_result" class="wpp-json-viewer hidden"></pre>
          </div>

          <div
            class="wpp_settings_block"><?php printf(__('Get %1s image data.', ud_get_wp_property()->domain), WPP_F::property_label('singular')) ?>
            <label
              for="wpp_image_id"><?php printf(__('%1s ID:', ud_get_wp_property()->domain), WPP_F::property_label('singular')) ?></label>
            <input type="text" id="wpp_image_id"/>
            <input type="button" class="button" value="<?php _e('Lookup', ud_get_wp_property()->domain) ?>" id="wpp_ajax_image_query"> <span id="wpp_ajax_image_query_cancel" class="wpp_link hidden"><?php _e('Cancel', ud_get_wp_property()->domain) ?></span>
            <pre id="wpp_ajax_image_result" class="wpp_class_pre hidden"></pre>
          </div>

          <div class="wpp_settings_block">
            <?php _e('Look up the <b>$wp_properties</b> global settings array.  This array stores all the default settings, which are overwritten by database settings, and custom filters.', ud_get_wp_property()->domain) ?>
            <input type="button" class="button" value="<?php _e('Show $wp_properties', ud_get_wp_property()->domain) ?>" id="wpp_show_settings_array"> <span id="wpp_show_settings_array_cancel" class="wpp_link hidden"><?php _e('Cancel', ud_get_wp_property()->domain) ?></span>
            <pre id="wpp_show_settings_array_result" class="wpp_class_pre hidden"></pre>
          </div>

          <?php if (function_exists('icl_object_id')): ?>
            <div class="wpp_settings_block">
              <?php _e('Generate images for duplicates of properties (WPML plugin option). ', ud_get_wp_property()->domain) ?>
              <input type="button" class="button" value="<?php _e('Generate', ud_get_wp_property()->domain) ?>" id="wpp_is_remote_meta">
            </div>
          <?php endif; ?>

          <div class="wpp_settings_block"><?php printf(__('Set all %1s to same %2s type:', ud_get_wp_property()->domain), WPP_F::property_label('plural'), WPP_F::property_label('singular')) ?>
            <select id="wpp_ajax_max_set_property_type_type">
              <?php foreach ($wp_properties['property_types'] as $p_slug => $p_label) { ?>
                <option value="<?php echo $p_slug; ?>"><?php echo $p_label; ?></option>
              <?php } ?>
              <input type="button" class="button" value="<?php _e('Set', ud_get_wp_property()->domain) ?>" id="wpp_ajax_max_set_property_type">
              <pre id="wpp_ajax_max_set_property_type_result" class="wpp_class_pre hidden"></pre>
          </div>

          <div class="wpp_settings_block">
            <?php if (function_exists('memory_get_usage')): ?>
              <?php _e('Memory Usage:', ud_get_wp_property()->domain); ?><?php echo round((memory_get_usage() / 1048576), 2); ?> megabytes.
            <?php endif; ?>
            <?php if (function_exists('memory_get_peak_usage')): ?>
              <?php _e('Peak Memory Usage:', ud_get_wp_property()->domain); ?><?php echo round((memory_get_peak_usage() / 1048576), 2); ?> megabytes.
            <?php endif; ?>
          </div>

          <div class="wpp_settings_block wpp_settings_block_toggle hidden_setting" data-wpp-section="api-registration-status">
            <p>
              <?php _e( 'API Registration Status', ud_get_wp_property()->domain ); ?>
              <i class="dashicons dashicons-arrow-down"></i>
              <i class="dashicons dashicons-arrow-up"></i>
            </p>

            <ul class="wpp-feature-api-status-list">
              <?php foreach( array( 'ud_site_secret_token' => 'Secret Token', 'ud_site_id' => 'Site ID', 'ud_site_public_key' => 'Public Key', 'ud_api_key' => 'Generic API Key' ) as $_option => $_option_label) { ?>
                <li class="wpp-feature-flag wpp-feature-flag-<?php echo get_site_option( $_option ) ? 'enabled': 'disabled';  ?>">
                  <span class="dashicons dashicons-yes"></span>
                  <label>
                    <b class="wpp-feature-name"><?php echo $_option_label; ?>:</b>
                    <span class="wpp-feature-description"><input type="text" size="48" readonly="readonly" value="<?php echo get_option($_option); ?>" /></span>
                  </label>
                </li>
              <?php } ?>
            </ul>

          </div>

          <div class="wpp_settings_block wpp_settings_block_toggle hidden_setting" data-wpp-section="feature-flags">
            <p>
              <?php _e( 'Available feature flags and their status.', ud_get_wp_property()->domain ); ?>
              <i class="dashicons dashicons-arrow-down"></i>
              <i class="dashicons dashicons-arrow-up"></i>
            </p>

            <ul class="wpp-feature-flag-list">
              <?php foreach( ud_get_wp_property()->get_feature_flags() as $_flagDetail ) { ?>
                <li class="wpp-feature-flag wpp-feature-flag-<?php echo defined( $_flagDetail->constant ) && constant( $_flagDetail->constant ) ? 'enabled': 'disabled';  ?>">
                  <span class="dashicons dashicons-yes"></span>
                  <b class="wpp-feature-name"><?php echo $_flagDetail->name; ?></b>
                  <span class="wpp-feature-description"><?php echo $_flagDetail->description; ?></span>
                </li>
              <?php } ?>
            </ul>

          </div>

          <?php do_action('wpp_settings_help_tab'); ?>
        </div>
      </div>

      <div id="tab_feedback">

        <style type="text/css">
          input.hs-input[type="checkbox"] {
            height: 16px !important;
          }
        </style>
        <div class="tab_feedback_form">
          <!--[if lte IE 8]>
          <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2-legacy.js"></script>
          <![endif]-->
          <script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2.js"></script>
          <script>
            jQuery(window).load(function () {
              if (jQuery("#tab_feedback").attr('aria-hidden') == 'false') {
                hbspt.forms.create({
                  portalId: "3453418",
                  formId: "16a6f927-9a75-43f2-9444-57034db38930",
                  target: '.tab_feedback_form'
                });
              }
              jQuery(document).on('click', '#wpp_settings_form ul.tabs li', function () {
                if (jQuery(this).attr('aria-controls') == 'tab_feedback') {
                  hbspt.forms.create({
                    portalId: "3453418",
                    formId: "16a6f927-9a75-43f2-9444-57034db38930",
                    target: '.tab_feedback_form'
                  });
                } else {
                  jQuery('#tab_feedback form.hbspt-form').remove();
                }
              });
            });
          </script>
        </div>

      </div>

    </div>


    <br class="cb"/>

    <p class="wpp_save_changes_row">
      <input type="submit" value="<?php _e('Save Changes', ud_get_wp_property()->domain); ?>" class="button-primary btn"
             name="Submit">
    </p>


  </form>
</div>