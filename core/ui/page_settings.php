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


//** Check if premium folder is writable */
$wp_messages = WPP_F::check_premium_folder_permissions();

$object_label = array(
  'singular' => WPP_F::property_label( 'singular' ),
  'plural' => WPP_F::property_label( 'plural' )
);

$wrapper_classes = array('wpp_settings_page');

if(isset($_REQUEST['message'])) {

  switch($_REQUEST['message']) {

    case 'updated':
    $wp_messages['notice'][] = __("Settings updated.", 'wpp');
    break;

  }
}

//** We have to update Rewrite rules here. peshkov@UD */
flush_rewrite_rules();

$parseUrl = parse_url(trim(get_bloginfo('url')));
$this_domain = trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));

/** Check if custom css exists */
if ( file_exists( STYLESHEETPATH . '/wp_properties.css') || file_exists( TEMPLATEPATH . '/wp_properties.css')) {
  $using_custom_css = true;
}


if(get_option('permalink_structure') == '') {
  $wrapper_classes[] = 'no_permalinks';
} else {
  $wrapper_classes[] = 'have_permalinks';
}

?>

 <script type="text/javascript">

  jQuery(document).ready(function() {

    //* Tabs for various UI elements */
    jQuery('.wpp_subtle_tabs').tabs();

    wpp_setup_default_property_page();

    jQuery("#wpp_settings_base_slug").change(function() {
      wpp_setup_default_property_page();
    });


    if( document.location.hash != '' && jQuery( document.location.hash ).length > 0 ) {
      jQuery("#wpp_settings_tabs").tabs();
    } else {
      jQuery("#wpp_settings_tabs").tabs({ cookie: {  name: 'wpp_settings_tabs', expires: 30 } });
    }


    // Show settings array
    jQuery("#wpp_show_settings_array").click(function() {
      jQuery("#wpp_show_settings_array_cancel").show();
      jQuery("#wpp_show_settings_array_result").show();
    });

    // Hide settings array
    jQuery("#wpp_show_settings_array_cancel").click(function() {
      jQuery("#wpp_show_settings_array_result").hide();
      jQuery(this).hide();
    });

    // Hide property query
    jQuery("#wpp_ajax_property_query_cancel").click(function() {
      jQuery("#wpp_ajax_property_result").hide();
      jQuery(this).hide();
    });

    // Hide image query
    jQuery("#wpp_ajax_image_query_cancel").click(function() {
      jQuery("#wpp_ajax_image_result").hide();
      jQuery(this).hide();
    });

    // Check plugin updates
    jQuery("#wpp_ajax_check_plugin_updates").click(function() {
      jQuery('.plugin_status').remove();
      jQuery.post(ajaxurl, {
          action: 'wpp_ajax_check_plugin_updates'
        }, function(data) {
          message = "<div class='plugin_status updated fade'><p>" + data + "</p></div>";
          jQuery(message).insertAfter("h2");
        });
    });

    /** Clear Cache */
    jQuery("#wpp_clear_cache").click(function() {
      jQuery('.clear_cache_status').remove();
      jQuery.post(ajaxurl, {
          action: 'wpp_ajax_clear_cache'
        }, function(data) {
          message = "<div class='clear_cache_status updated fade'><p>" + data + "</p></div>";
          jQuery(message).insertAfter("h2");
        });
    });

    // Revalidate all addresses
    jQuery("#wpp_ajax_revalidate_all_addresses").click(function() {

      jQuery(this).val('Processing...');
      jQuery(this).attr('disabled', true);
      jQuery('.address_revalidation_status').remove();

      jQuery.post(ajaxurl, {
          action: 'wpp_ajax_revalidate_all_addresses'
          }, function(data) {

          jQuery("#wpp_ajax_revalidate_all_addresses").val('Revalidate again');
          jQuery("#wpp_ajax_revalidate_all_addresses").attr('disabled', false);

          if(data.success == 'true')
            message = "<div class='address_revalidation_status updated fade'><p>" + data.message + "</p></div>";
          else
            message = "<div class='address_revalidation_status error fade'><p>" + data.message + "</p></div>";

          jQuery(message).insertAfter("h2");
        }, 'json');
    });

    // Show property query
    jQuery("#wpp_ajax_property_query").click(function() {

      var property_id = jQuery("#wpp_property_class_id").val();

      jQuery("#wpp_ajax_property_result").html("");

      jQuery.post(ajaxurl, {
          action: 'wpp_ajax_property_query',
          property_id: property_id
         }, function(data) {
          jQuery("#wpp_ajax_property_result").show();
          jQuery("#wpp_ajax_property_result").html(data);
          jQuery("#wpp_ajax_property_query_cancel").show();

        });

    });

    //** Mass set property type */
    jQuery("#wpp_ajax_max_set_property_type").click(function() {

      if(!confirm("<?php _e('You are about to set ALL your properties to the selected property type. Are you sure?', 'wpp'); ?>")) {
        return;
      }

      var property_type = jQuery("#wpp_ajax_max_set_property_type_type").val();

      jQuery.post(ajaxurl, {
        action: 'wpp_ajax_max_set_property_type',
        property_type: property_type
        }, function(data) {
          jQuery("#wpp_ajax_max_set_property_type_result").show();
          jQuery("#wpp_ajax_max_set_property_type_result").html(data);
        });

    });

    // Show image data
    jQuery("#wpp_ajax_image_query").click(function() {

      var image_id = jQuery("#wpp_image_id").val();

      jQuery("#wpp_ajax_image_result").html("");

      jQuery.post(ajaxurl, {
          action: 'wpp_ajax_image_query',
          image_id: image_id
         }, function(data) {
          jQuery("#wpp_ajax_image_result").show();
          jQuery("#wpp_ajax_image_result").html(data);
          jQuery("#wpp_ajax_image_query_cancel").show();

        });

    });

    /** Show property query */
    jQuery("#wpp_check_premium_updates").click(function() {
      jQuery("#wpp_plugins_ajax_response").hide();
      jQuery.post(ajaxurl, {
           action: 'wpp_ajax_check_plugin_updates'
         }, function(data) {
           jQuery("#wpp_plugins_ajax_response").show();
           jQuery("#wpp_plugins_ajax_response").html(data);
        });
    });

  });

  /* Modifies UI to reflect Default Property Page selection */
  function wpp_setup_default_property_page() {
    var selection = jQuery("#wpp_settings_base_slug").val();

    /* Default Property Page is dynamic. */
    if(selection == "property") {
      jQuery(".wpp_non_property_page_settings").hide();
      jQuery(".wpp_non_property_page_settings input[type=checkbox]").attr("checked", false);
      jQuery(".wpp_non_property_page_settings input[type=checkbox]").attr("disabled", true);
    } else {
      jQuery(".wpp_non_property_page_settings").show();
      jQuery(".wpp_non_property_page_settings input[type=checkbox]").attr("disabled", false);
    }

  }
 </script>

<div class="wrap <?php echo implode(' ', $wrapper_classes); ?>">
<?php screen_icon(); ?>
<h2 class='wpp_settings_page_header'><?php  echo $wp_properties['labels']['name'] . ' ' . __('Settings','wpp') ?>
<div class="wpp_fb_like">
  <div class="fb-like" data-href="https://www.facebook.com/wpproperty" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false"></div>
</div>
</h2>

<?php if(isset($wp_messages['error']) && $wp_messages['error']): ?>
<div class="error">
  <?php foreach($wp_messages['error'] as $error_message): ?>
    <p><?php echo $error_message; ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if(isset($wp_messages['notice']) && $wp_messages['notice']): ?>
<div class="updated fade">
  <?php foreach($wp_messages['notice'] as $notice_message): ?>
    <p><?php echo $notice_message; ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?php echo admin_url('edit.php?post_type=property&page=property_settings'); ?>"  enctype="multipart/form-data" />
<?php wp_nonce_field('wpp_setting_save'); ?>

<div id="wpp_settings_tabs" class="wpp_tabs clearfix">
  <ul class="tabs">
    <li><a href="#tab_main"><?php _e('Main','wpp'); ?></a></li>
    <li><a href="#tab_display"><?php _e('Display','wpp'); ?></a></li>
      <?php
      if(is_array($wp_properties['available_features'])) {

        $wpp_plugin_settings_nav = apply_filters('wpp_settings_nav', array());

        foreach($wp_properties['available_features'] as $plugin) {
          if(@$plugin['status'] == 'disabled') {
            unset($wpp_plugin_settings_nav[$plugin]);
          }
        }

        if(is_array($wpp_plugin_settings_nav)) {
          foreach($wpp_plugin_settings_nav as $nav) {
            echo "<li><a href='#tab_{$nav['slug']}'>{$nav['title']}</a></li>\n";
          }
        }
      }
    ?>

    <?php if(count($wp_properties['available_features']) > 0): ?>
    <li><a href="#tab_plugins"><?php _e('Premium Features','wpp'); ?></a></li>
    <?php endif; ?>
    <li><a href="#tab_troubleshooting"><?php _e('Help','wpp'); ?></a></li>


  </ul>

  <div id="tab_main">

    <?php do_action('wpp_settings_main_top', $wp_properties); ?>

    <table class="form-table">

    <tr>
      <th><?php _e('Feature Updates','wpp'); ?></th>
      <td>

        <p id="wpp_plugins_ajax_response" class="hidden"></p>

        <div class="wpp_settings_block">
          <input type="button" value="<?php _e('Check Updates','wpp');?>" id="wpp_ajax_check_plugin_updates" />
          <?php _e('to download, or update, all premium features purchased for this domain.','wpp');?>
        </div>

        <?php /* if( get_option('ud_api_key') ) { ?>
        <div class="wpp_settings_block">
          <label><?php _e('If a feature or service requires an API Key, you may change it here:','wpp');?>
          <input size="70" type="text" readonly="true" value="<?php echo get_option('ud_api_key'); ?>" />
          </label>
        </div>
        <?php } */ ?>

      </td>
    </tr>

      <tr>
      <th><?php _e('Options','wpp'); ?></th>
      <td>
        <ul>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][include_in_regular_search_results]&label=" . sprintf( __('Include %1s in regular search results.', 'wpp'), $object_label['plural'] ) , $wp_properties['configuration']['include_in_regular_search_results']); ?></li>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][do_not_automatically_regenerate_thumbnails]&label=" . __('Disable "on-the-fly" image regeneration.', 'wpp'), $wp_properties['configuration']['do_not_automatically_regenerate_thumbnails']); ?></li>
          <?php /* <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][do_not_automatically_geo_validate_on_property_view]&label=" . __('Disable front-end "on-the-fly" address validation.', 'wpp'), $wp_properties['configuration']['do_not_automatically_geo_validate_on_property_view']); ?></li> */ ?>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][auto_delete_attachments]&label=" . __('Automatically delete all property images and attachments when a property is deleted.', 'wpp'), $wp_properties['configuration']['auto_delete_attachments']); ?></li>
        </ul>
      </td>
    </tr>

    <tr>
      <th><?php printf(__('Default %1s Page', 'wpp'), $wp_properties['labels']['name']);  ?></th>
      <td>

        <div class="must_have_permalinks">
          <select name="wpp_settings[configuration][base_slug]" id="wpp_settings_base_slug">
            <option <?php selected($wp_properties['configuration']['base_slug'], 'property'); ?> value="property"><?php _e('Property (Default)','wpp'); ?></option>
            <?php foreach(get_pages() as $page): ?>
              <option <?php selected($wp_properties['configuration']['base_slug'],$page->post_name); ?> value="<?php echo $page->post_name; ?>"><?php echo $page->post_title; ?></option>
            <?php endforeach; ?>
          </select>
          <span wpp_scroll_to="h3.default_property_page" class="wpp_link wpp_toggle_contextual_help"><?php _e('What is this?', 'wpp'); ?></span>
        </div>
        <div class="must_not_have_permalinks">
          <p class="description"><?php printf(__('You must have permalinks enabled to change the Default %1s page.', 'wpp'), $wp_properties['labels']['name']); ?></p>
        </div>

      </td>
    </tr>


    <tr class="wpp_non_property_page_settings hidden">
      <th>&nbsp;</th>
      <td>
        <ul>
          <li>
            <?php echo WPP_F::checkbox('name=wpp_settings[configuration][automatically_insert_overview]&label='. __('Automatically overwrite this page\'s content with [property_overview].','wpp'), $wp_properties['configuration']['automatically_insert_overview']); ?>
          </li>
          <li class="wpp_wpp_settings_configuration_do_not_override_search_result_page_row <?php if($wp_properties['configuration']['automatically_insert_overview'] == 'true') echo " hidden ";?>">
            <?php echo WPP_F::checkbox("name=wpp_settings[configuration][do_not_override_search_result_page]&label=" . __('When showing property search results, don\'t override the page content with [property_overview].', 'wpp'), $wp_properties['configuration']['do_not_override_search_result_page']); ?>
            <div class="description"><?php _e('If checked, be sure to include [property_overview] somewhere in the content, or no properties will be displayed.','wpp'); ?></div>
          </li>
      </ul>
      </td>
    </tr>

    <tr>
      <th><?php printf(__('Automatic Geolocation','wpp'), WPP_F::property_label()); ?></th>
      <td>
        <ul>
          <li><?php _e('Attribute to use for physical addresses:','wpp'); ?><?php echo WPP_F::draw_attribute_dropdown("name=wpp_settings[configuration][address_attribute]&selected={$wp_properties['configuration']['address_attribute']}"); ?></li>
          <li><?php _e('Localize addresses in:','wpp'); ?> <?php echo WPP_F::draw_localization_dropdown("name=wpp_settings[configuration][google_maps_localization]&selected={$wp_properties['configuration']['google_maps_localization']}"); ?></li>
        </ul>
      </td>
    </tr>

    <tr>
      <th><?php _e('Styles and Scripts','wpp'); ?></th>
      <td>
        <ul>
          <li><?php echo $using_custom_css ? WPP_F::checkbox("name=wpp_settings[configuration][autoload_css]&label=" . __('Load default CSS. If unchecked, the wp-properties.css in your theme folder will not be loaded.','wpp'), $wp_properties['configuration']['autoload_css']) : WPP_F::checkbox("name=wpp_settings[configuration][autoload_css]&label=" . __('Load default CSS.','wpp'), $wp_properties['configuration']['autoload_css']); ?></li>

            <?php if(WPP_F::has_theme_specific_stylesheet()) { ?>
            <li>
                 <?php echo WPP_F::checkbox("name=wpp_settings[configuration][do_not_load_theme_specific_css]&label=" .  __('Do not load theme-specific stylesheet.','wpp'), $wp_properties['configuration']['do_not_load_theme_specific_css']); ?>
                 <div class="description"><?php _e('This version of WP-Property has a stylesheet made specifically for the theme you are using.', 'wpp'); ?></div>
                 </li>
            </li>
            <?php } /* WPP_F::has_theme_specific_stylesheet() */  ?>

          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][load_scripts_everywhere]&label=" . __('Load WP-Property scripts on all front-end pages.','wpp'), $wp_properties['configuration']['load_scripts_everywhere']); ?></li>
        </ul>

      </td>
    </tr>

    <tr>
      <th><?php _e('Default Phone Number','wpp'); ?></th>
      <td><?php echo WPP_F::input("name=phone_number&label=" . __('Phone number to use when a property-specific phone number is not specified.','wpp') . "&group=wpp_settings[configuration]&style=width: 200px;", $wp_properties['configuration']['phone_number']); ?></td>
    </tr>


    <?php do_action('wpp_settings_main_tab_bottom', $wp_properties); ?>
    </table>


  </div>

  <div id="tab_display">

    <table class="form-table">

    <tr>
      <th><?php _e('Image Sizes','wpp'); ?></th>
      <td>
        <p><?php _e('Image sizes used throughout the plugin.','wpp'); ?> </p>

          <table id="wpp_image_sizes" class="ud_ui_dynamic_table widefat">
            <thead>
              <tr>
                <th><?php _e('Slug','wpp'); ?></th>
                <th><?php _e('Width','wpp'); ?></th>
                <th><?php _e('Height','wpp'); ?></th>
                <th>&nbsp;</th>
              </tr>
            </thead>
            <tbody>
          <?php
            $wpp_image_sizes = $wp_properties['image_sizes'];

            foreach(get_intermediate_image_sizes() as $slug):

            $slug = trim($slug);

            // We return all, including images with zero sizes, to avoid default data overriding what we save
            $image_dimensions = WPP_F::image_sizes($slug, "return_all=true");

            // Skip images w/o dimensions
            if(!$image_dimensions)
              continue;

            // Disable if WP not a WPP image size
            if(@!is_array($wpp_image_sizes[$slug]))
              $disabled = true;
            else
              $disabled = false;


            if(!$disabled):
          ?>
            <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>">
              <td  class="wpp_slug">
                <input class="slug_setter slug wpp_slug_can_be_empty"  type="text" value="<?php echo $slug; ?>" />
              </td>
              <td class="wpp_width">
                <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][width]" value="<?php echo $image_dimensions['width']; ?>" />
              </td>
              <td  class="wpp_height">
                <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][height]" value="<?php echo $image_dimensions['height']; ?>" />
              </td>
              <td><span class="wpp_delete_row wpp_link"><?php _e('Delete','wpp') ?></span></td>
            </tr>

            <?php else: ?>
            <tr>
              <td>
                <div class="wpp_permanent_image"><?php echo $slug; ?></div>
              </td>
              <td>
                <div class="wpp_permanent_image"><?php echo $image_dimensions['width']; ?></div>
              </td>
              <td>
                <div class="wpp_permanent_image"><?php echo $image_dimensions['height']; ?></div>
              </td>
              <td>&nbsp;</td>
            </tr>

            <?php endif; ?>


          <?php endforeach; ?>

            </tbody>
            <tfoot>
              <tr>
                <td colspan='4'><input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row','wpp') ?>" /></td>
              </tr>
            </tfoot>
          </table>


       </td>
    </tr>




    <tr>
      <th><?php _e('Overview Shortcode','wpp') ?></th>
      <td>
        <p>
        <?php _e('These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br />The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property-overview.php</b> file.  To avoid losing your changes during updates, create a <b>property-overview.php</b> file in your template directory, which will be automatically loaded.','wpp') ?>
        <ul>

          <li><?php _e('Thumbnail size:','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties['configuration']['property_overview']['thumbnail_size']); ?></li>
          <li><?php echo WPP_F::checkbox('name=wpp_settings[configuration][property_overview][show_children]&label=' . __('Show children properties.','wpp'), $wp_properties['configuration']['property_overview']['show_children']); ?></li>
          <li><?php echo WPP_F::checkbox('name=wpp_settings[configuration][property_overview][fancybox_preview]&label=' . __('Show larger image of property when image is clicked using fancybox.','wpp') , $wp_properties['configuration']['property_overview']['fancybox_preview']); ?></li>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][bottom_insert_pagenation]&label=" . __('Show pagination on bottom of results.','wpp'), $wp_properties['configuration']['bottom_insert_pagenation']); ?></li>
         </ul>

      </td>
    </tr>

    <tr>
      <th><?php _e('Property Page','wpp') ?></th>
      <td>
        <p><?php _e('These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br /> The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property.php</b> file.  To avoid losing your changes during updates, create a <b>property.php</b> file in your template directory, which will be automatically loaded.','wpp') ?>
        <ul>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][property_overview][sort_stats_by_groups]&label=" .__('Sort property stats by groups.','wpp'), $wp_properties['configuration']['property_overview']['sort_stats_by_groups']); ?></li>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][property_overview][show_true_as_image]&label=". sprintf(__('Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values','wpp'), __('Yes', 'wpp'),__('No', 'wpp'),__('Yes', 'wpp'),__('No', 'wpp')), $wp_properties['configuration']['property_overview']['show_true_as_image']); ?></li>
          <?php do_action('wpp_settings_page_property_page');?>
        </ul>

      </td>
    </tr>

    <tr>
      <th><?php _e('Google Maps','wpp') ?></th>
      <td>

        <ul>
          <li><?php _e('Map Thumbnail Size:','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . $wp_properties['configuration']['single_property_view']['map_image_type']); ?></li>
          <li><?php _e('Map Zoom Level:','wpp') ?> <?php echo WPP_F::input("name=wpp_settings[configuration][gm_zoom_level]&style=width: 30px;",$wp_properties['configuration']['gm_zoom_level']); ?></li>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][show_true_as_image]&label=". sprintf(__('Show Checkboxed Image instead of "%s" and hide "%s" for %s/%s values','wpp'), __('Yes', 'wpp'),__('No', 'wpp'),__('Yes', 'wpp'),__('No', 'wpp')), $wp_properties['configuration']['google_maps']['show_true_as_image']); ?></li>
        </ul>

        <p><?php _e('Attributes to display in popup after a property on a map is clicked.', 'wpp'); ?></p>
        <div class="wp-tab-panel">
        <ul>

          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][show_property_title]&label=" . __('Show Property Title', 'wpp'), $wp_properties['configuration']['google_maps']['infobox_settings']['show_property_title']); ?></li>

          <?php foreach($wp_properties['property_stats'] as $attrib_slug => $attrib_title): ?>
          <li><?php
          $checked = (in_array($attrib_slug, $wp_properties['configuration']['google_maps']['infobox_attributes']) ? true : false);
          echo WPP_F::checkbox("id=google_maps_attributes_{$attrib_title}&name=wpp_settings[configuration][google_maps][infobox_attributes][]&label=$attrib_title&value={$attrib_slug}", $checked);
          ?></li>
          <?php endforeach; ?>

          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][show_direction_link]&label=". __('Show Directions Link', 'wpp'), $wp_properties['configuration']['google_maps']['infobox_settings']['show_direction_link']); ?></li>
          <li><?php echo WPP_F::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][do_not_show_child_properties]&label=". __('Do not show a list of child properties in Infobox. ', 'wpp'), $wp_properties['configuration']['google_maps']['infobox_settings']['do_not_show_child_properties']); ?></li>
        </ul>
        </div>
      </td>
    </tr>

    <tr>
      <th><?php _e('Address Display','wpp') ?></th>
      <td>


        <textarea name="wpp_settings[configuration][display_address_format]" style="width: 70%;"><?php echo $wp_properties['configuration']['display_address_format']; ?></textarea>
        <br />
        <span class="description">
               <?php _e('Available tags:','wpp') ?> [street_number] [street_name], [city], [state], [state_code], [county],  [country], [zip_code].
        </span>
      </td>
    </tr>

    <tr>
      <th><?php _e('Currency & Numbers','wpp'); ?></th>
      <td>
        <ul>
          <li><?php echo WPP_F::input("name=currency_symbol&label=".__('Currency symbol.','wpp')."&group=wpp_settings[configuration]&style=width: 50px;",$wp_properties['configuration']['currency_symbol']); ?></li>
          <li>
            <?php _e('Thousands separator symbol:', 'wpp'); ?>
            <select name="wpp_settings[configuration][thousands_sep]">
              <option value=""> - </option>
              <option value="." <?php selected($wp_properties['configuration']['thousands_sep'],'.'); ?>><?php _e('. (period)', 'wpp'); ?></option>
              <option value="," <?php selected($wp_properties['configuration']['thousands_sep'],','); ?>><?php _e(', (comma)', 'wpp'); ?></option>
             </select>
             <span class="description"><?php _e('The character separating the 1 and the 5: $1<b>,</b>500'); ?></span>

          </li>

          <li>
            <?php _e('Currency symbol placement:', 'wpp'); ?>
            <select name="wpp_settings[configuration][currency_symbol_placement]">
              <option value=""> - </option>
              <option value="before" <?php selected($wp_properties['configuration']['currency_symbol_placement'],'before'); ?>><?php _e('Before number', 'wpp'); ?></option>
              <option value="after" <?php selected($wp_properties['configuration']['currency_symbol_placement'],'after'); ?>><?php _e('After number', 'wpp'); ?></option>
             </select>

          </li>

       </ul>
      </td>
    </tr>


    <tr>
      <th>
        <?php _e('Admin Settings','wpp') ?>
      </th>
        <td>
        <ul>
          <li><?php _e('Thumbnail size for property images displayed on Properties page: ','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . $wp_properties['configuration']['admin_ui']['overview_table_thumbnail_size']); ?></li>
          <li>
          <?php echo WPP_F::checkbox("name=wpp_settings[configuration][completely_hide_hidden_attributes_in_admin_ui]&label=" . __('Completely hide hidden attributes when editing properties.', 'wpp'), $wp_properties['configuration']['completely_hide_hidden_attributes_in_admin_ui']); ?>
          </li>
        </ul>
      </td>
    </tr>

    <?php do_action('wpp_settings_display_tab_bottom'); ?>

    </table>
  </div>



  <?php

    foreach( (array) $wpp_plugin_settings_nav as $nav) {
      echo "<div id='tab_{$nav['slug']}'>";
      do_action("wpp_settings_content_{$nav['slug']}");
      echo "</div>";
    }

  ?>


<?php if(count($wp_properties['available_features']) > 0): ?>
  <div id="tab_plugins">

      <table id="wpp_premium_feature_table" cellpadding="0" cellspacing="0">
      <?php foreach($wp_properties['available_features'] as $plugin_slug => $plugin_data): ?>

        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][title]" value="<?php echo $plugin_data['title']; ?>" />
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][tagline]" value="<?php echo $plugin_data['tagline']; ?>" />
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][image]" value="<?php echo $plugin_data['image']; ?>" />
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $plugin_data['description']; ?>" />

        <?php $installed = WPP_F::check_premium($plugin_slug); ?>
        <?php $active = (@$wp_properties['installed_features'][$plugin_slug]['disabled'] != 'false' ? true : false); ?>

        <?php if($installed): ?>
        <?php /* Do this to preserve settings after page save. */ ?>
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][disabled]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['disabled']; ?>" />
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][name]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['name']; ?>" />
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][version]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['version']; ?>" />
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['description']; ?>" />
        <?php endif; ?>

        <tr class="wpp_premium_feature_block">

          <td valign="top" class="wpp_premium_feature_image">
            <a href="http://usabilitydynamics.com/products/wp-property/"><img src="<?php echo $plugin_data['image']; ?>" /></a>
          </td>

          <td valign="top">
            <div class="wpp_box">
            <div class="wpp_box_header">
              <strong><?php echo $plugin_data['title']; ?></strong>
              <p><?php echo $plugin_data['tagline']; ?> <a href="https://usabilitydynamics.com/products/wp-property/premium/?wp_checkout_payment_domain=<?php echo $this_domain; ?>"><?php _e('[purchase feature]','wpp') ?></a>
              </p>
            </div>
            <div class="wpp_box_content">
              <p><?php echo $plugin_data['description']; ?></p>

            </div>

            <div class="wpp_box_footer clearfix">
              <?php if($installed) { ?>

                <div class="alignleft">
                <?php

                if($wp_properties['installed_features'][$plugin_slug]['needs_higher_wpp_version'] == 'true')  {
                  printf(__('This feature is disabled because it requires WP-Property %1$s or higher.'), $wp_properties['installed_features'][$plugin_slug]['minimum_wpp_version']);
                } else {
                  echo WPP_F::checkbox("name=wpp_settings[installed_features][$plugin_slug][disabled]&label=" . __('Disable plugin.','wpp'), $wp_properties['installed_features'][$plugin_slug]['disabled']);

                 ?>
                </div>
                <div class="alignright"><?php _e('Feature installed, using version','wpp') ?> <?php echo $wp_properties['installed_features'][$plugin_slug]['version']; ?>.</div>
              <?php }
              } else {
                  $pr_link = 'https://usabilitydynamics.com/products/wp-property/premium/'; echo sprintf(__('Please visit <a href="%s">UsabilityDynamics.com</a> to purchase this feature.','wpp'),$pr_link);
              } ?>
            </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </table>

  </div>
<?php endif; ?>

  <div id="tab_troubleshooting">
    <div class="wpp_inner_tab">

      <div class="wpp_settings_block">
        <label>
        <?php _e('If prompted for your domain name during a premium feature purchase, enter as appears here:','wpp'); ?>
        <input type="text" readonly="true" value="<?php echo $this_domain; ?>" size="<?php echo strlen( $this_domain ) + 10; ?>" />
        </label>
      </div>

      <div class="wpp_settings_block">
        <?php _e("Restore Backup of WP-Property Configuration", 'wpp'); ?>: <input name="wpp_settings[settings_from_backup]" type="file" />
        <a href="<?php echo wp_nonce_url( "edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup", 'download-wpp-backup'); ?>"><?php _e('Download Backup of Current WP-Property Configuration.', 'wpp');?></a>
      </div>

      <div class="wpp_settings_block">
        <?php $google_map_localizations = WPP_F::draw_localization_dropdown('return_array=true'); ?>
        <?php _e('Revalidate all addresses using', 'wpp'); ?> <b><?php echo $google_map_localizations[$wp_properties['configuration']['google_maps_localization']]; ?></b> <?php _e('localization', 'wpp'); ?>.
         <input type="button" value="<?php _e('Revalidate','wpp');?>" id="wpp_ajax_revalidate_all_addresses">
      </div>

      <div class="wpp_settings_block"><?php _e('Enter in the ID of the property you want to look up, and the class will be displayed below.','wpp') ?>
        <input type="text" id="wpp_property_class_id" />
        <input type="button" value="<?php _e('Lookup','wpp') ?>" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden"><?php _e('Cancel','wpp') ?></span>
        <pre id="wpp_ajax_property_result" class="wpp_class_pre hidden"></pre>
      </div>

      <div class="wpp_settings_block"><?php _e('Get property image data.','wpp') ?>
        <label for="wpp_image_id"><?php _e('Property ID:','wpp') ?></label>
        <input type="text" id="wpp_image_id" />
        <input type="button" value="<?php _e('Lookup','wpp') ?>" id="wpp_ajax_image_query"> <span id="wpp_ajax_image_query_cancel" class="wpp_link hidden"><?php _e('Cancel','wpp') ?></span>
        <pre id="wpp_ajax_image_result" class="wpp_class_pre hidden"></pre>
      </div>

      <div class="wpp_settings_block">
        <?php _e('Look up the <b>$wp_properties</b> global settings array.  This array stores all the default settings, which are overwritten by database settings, and custom filters.','wpp') ?>
        <input type="button" value="<?php _e('Show $wp_properties','wpp') ?>" id="wpp_show_settings_array"> <span id="wpp_show_settings_array_cancel" class="wpp_link hidden"><?php _e('Cancel','wpp') ?></span>
        <pre id="wpp_show_settings_array_result" class="wpp_class_pre hidden"><?php print_r($wp_properties); ?></pre>
      </div>

      <div class="wpp_settings_block">
        <?php _e('Clear WPP Cache. Some shortcodes and widgets use cache, so the good practice is clear it after widget, shortcode changes.','wpp') ?>
        <input type="button" value="<?php _e('Clear Cache','wpp') ?>" id="wpp_clear_cache">
      </div>

      <div class="wpp_settings_block"><?php _e('Set all properties to same property type:','wpp') ?>
        <select id="wpp_ajax_max_set_property_type_type">
        <?php foreach($wp_properties['property_types'] as $p_slug => $p_label) { ?>
        <option value="<?php echo $p_slug; ?>"><?php echo $p_label; ?></option>
        <?php } ?>
        <input type="button" value="<?php _e('Set','wpp') ?>" id="wpp_ajax_max_set_property_type">
        <pre id="wpp_ajax_max_set_property_type_result" class="wpp_class_pre hidden"></pre>
      </div>

      <div class="wpp_settings_block">
        <?php if(function_exists('memory_get_usage')): ?>
        <?php _e('Memory Usage:', 'wpp'); ?> <?php echo round((memory_get_usage() / 1048576), 2); ?> megabytes.
        <?php endif; ?>
        <?php if(function_exists('memory_get_peak_usage')): ?>
        <?php _e('Peak Memory Usage:', 'wpp'); ?> <?php echo round((memory_get_peak_usage() / 1048576), 2); ?> megabytes.
        <?php endif; ?>
      </div>

      <?php do_action('wpp_settings_help_tab'); ?>
    </div>
  </div>

</div>


<br class="cb" />

<p class="wpp_save_changes_row">
<input type="submit" value="<?php _e('Save Changes','wpp');?>" class="button-primary btn" name="Submit">
 </p>


</form>
</div>

 <!--fb-->
<div id="fb-root"></div>
<script type="text/javascript">(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=373515126019844"; fjs.parentNode.insertBefore(js, fjs); }(document, 'script', 'facebook-jssdk'));</script>