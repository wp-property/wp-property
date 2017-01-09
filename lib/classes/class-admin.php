<?php
/**
 * Admin Panel UI functionality
 *
 * @since 2.0.0
 * @author peshkov@UD
 */
namespace UsabilityDynamics\WPP {

  use WPP_F;

  if (!class_exists('UsabilityDynamics\WPP\Admin')) {

    class Admin extends Scaffold
    {

      /**
       * Adds all required hooks
       */
      public function __construct()
      {

        parent::__construct();

        /**
         * Init 'All Properties' page.
         */
        new Admin_Overview();

        //** Load admin header scripts */
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        /** Admin interface init */
        add_action("admin_init", array($this, "admin_init"));

        // @todo Move back to Settings -> Properties as it was years ago.
        add_action("admin_menu", array($this, 'admin_menu'), 150);

        // @todo Make directory names dynamic, since it may change.
        add_action("in_plugin_update_message-wp-property/wp-property.php", array($this, 'product_update_message'), 20, 2);

        // Init customizer
        if (!isset($wp_properties['configuration']['enable_layouts']) || $wp_properties['configuration']['enable_layouts'] != "true") :
          add_action('customize_register', array($this, 'property_layouts_customizer'), 20, 2);
        endif;

      }

      /**
       * Can enqueue scripts on specific pages, and print content into head
       *
       *
       * @uses $current_screen global variable
       * @since 0.53
       *
       */
      public function enqueue_scripts()
      {
        global $current_screen;

        wp_localize_script('wpp-localization', 'wpp', array('instance' => $this->instance->core->get_instance()));

        switch ($current_screen->id) {

          //** Edit Property page */
          case 'property':
            global $post;

            $post_type_object = get_post_type_object('property');
            if (current_user_can($post_type_object->cap->create_posts)) {
              wp_enqueue_script('wpp-clone-property', $this->instance->path('static/scripts/wpp.admin.clone.js', 'url'), array('jquery', 'wp-property-global'), $this->instance->get('version'), true);
            }

            wp_enqueue_script('wp-property-global');
            wp_enqueue_script('wp-property-backend-editor');
            //** Enabldes fancybox js, css and loads overview scripts */
            wp_enqueue_script('post');
            wp_enqueue_script('postbox');
            wp_enqueue_script('wpp-jquery-fancybox');
            wp_enqueue_style('wpp-jquery-fancybox-css');
            wp_enqueue_script('wp-property-backend-global');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_style('jquery-ui');
            wp_enqueue_script('wp-property-admin-widgets');
            break;

          //** Settings Page */
          case 'property_page_property_settings':
            wp_enqueue_script('wp-property-backend-global');
            wp_enqueue_script('wp-property-global');
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('wpp-jquery-colorpicker');
            wp_enqueue_script('select2');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script('jquery-cookie');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('wp-property-admin-settings');

            wp_enqueue_script('custom-jqueryui-script', '//code.jquery.com/ui/1.12.1/jquery-ui.js', array('jquery'));
            wp_enqueue_style('jquery-ui');
            wp_enqueue_style('wpp-jquery-ui-dialog');
            wp_enqueue_style('wpp-jquery-colorpicker-css');
            wp_enqueue_style('select2');
            // This will enqueue the Media Uploader script
            wp_enqueue_media();
            break;

          //** Widgets Page */
          case 'widgets':
          case 'customize':
          case 'wpp_layout':
            wp_enqueue_script('wp-property-backend-global');
            wp_enqueue_script('wp-property-global');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_style('jquery-ui');
            wp_enqueue_script('wp-property-admin-widgets');
            break;

        }

        //** Automatically insert styles sheet if one exists with $current_screen->ID name */
        if (file_exists($this->instance->path("static/styles/{$current_screen->id}.css", 'dir'))) {
          wp_enqueue_style($current_screen->id . '-style', $this->instance->path("static/styles/{$current_screen->id}.css", 'url'), array(), WPP_Version, 'screen');
        }

        //** Automatically insert JS sheet if one exists with $current_screen->ID name */
        if (file_exists($this->instance->path("static/scripts/{$current_screen->id}.js", 'dir'))) {
          wp_enqueue_script($current_screen->id . '-js', $this->instance->path("static/scripts/{$current_screen->id}.js", 'url'), array('jquery'), WPP_Version, 'wp-property-backend-global');
        }

        //** Enqueue CSS styles on all pages */
        if (file_exists($this->instance->path('static/styles/wpp.admin.css', 'dir'))) {
          wp_register_style('wpp-admin-styles', $this->instance->path('static/styles/wpp.admin.css', 'url'), array(), WPP_Version);
          wp_enqueue_style('wpp-admin-styles');
        }

      }

      /**
       * Runs pre-header functions on admin-side only
       *
       * Checks if plugin has been updated.
       *
       * @since 1.10
       *
       */
      public function admin_init()
      {
        global $wp_properties;

        // Add metaboxes
        do_action('wpp_metaboxes');

        //** Download backup of configuration */
        if (
          isset($_REQUEST['page'])
          && $_REQUEST['page'] == 'property_settings'
          && isset($_REQUEST['wpp_action'])
          && $_REQUEST['wpp_action'] == 'download-wpp-backup'
          && isset($_REQUEST['_wpnonce'])
          && wp_verify_nonce($_REQUEST['_wpnonce'], 'download-wpp-backup')
        ) {
          $sitename = sanitize_key(get_bloginfo('name'));
          $filename = $sitename . '-wp-property.' . date('Y-m-d') . '.json';

          header("Cache-Control: public");
          header("Content-Description: File Transfer");
          header("Content-Disposition: attachment; filename=$filename");
          header("Content-Transfer-Encoding: binary");
          header('Content-Type: text/plain; charset=' . get_option('blog_charset'), true);

          //if backup of data from setup-assistant
          // get backed-up data for download
          if (isset($_REQUEST['timestamp'])) {
            $data = get_option('wpp_property_backups');
            $wp_properties = $data[$_REQUEST['timestamp']];
          }
          // May be extend backup data by add-ons options.
          $data = apply_filters('wpp::backup::data', array('wpp_settings' => $wp_properties));

          echo json_encode($data, JSON_PRETTY_PRINT);

          die();
        }
      }

      /**
       *
       */
      public function admin_menu()
      {

        $settings_page = add_submenu_page('edit.php?post_type=property', __('Settings', ud_get_wp_property()->domain), __('Settings', ud_get_wp_property()->domain), 'manage_wpp_settings', 'property_settings', function () {
          global $wp_properties;
          include ud_get_wp_property()->path("static/views/admin/settings.php", 'dir');
        });

      }

      /**
       * Display a hopefully helpful message next to "there's an update available" message, in particular if pre-release updates are enabled.
       *
       * @author potanin@UD
       * @param $plugin_data
       * @param $response
       */
      public function product_update_message($plugin_data, $response)
      {
        global $wp_properties;

        // pre-release updates not enabled or no update.
        if (!isset($wp_properties['configuration']['pre_release_update']) || $wp_properties['configuration']['pre_release_update'] !== 'true' || !$plugin_data['update']) {
          return;
        }

        if (isset($response) && isset($response->message)) {
          echo ' <span class="wpp-update-message">' . $response->message . '</span>';
        } else {
          echo ' <span class="wpp-update-message">' . __('You are seeing this because you subscribed to latest updates.', ud_get_wp_property()->domain) . '</span>';
        }

      }

      public function property_layouts_customizer($wp_customize)
      {
//        global $wp_properties;

        $wp_customize->add_panel('layouts_area_panel', array(
          'priority' => 41,
          'capability' => 'edit_theme_options',
          'title' => __('Layouts section', ud_get_wp_property()->domain)
        ));

//    Property overview settings
        $wp_customize->add_section('layouts_property_overview_settings', array(
          'title' => __('Property overview settings', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 1,
        ));
//    Property overview settings
        $wp_customize->add_setting('layouts_property_overview', array(
          'default' => __('', ud_get_wp_property()->domain),
          'sanitize_callback' => 'avalon_sanitize_callback',
          'capability' => 'edit_theme_options',
          'transport' => 'postMessage'
        ));
        $wp_customize->add_control('layouts_property_overview', array(
          'label' => __('Lol', ud_get_wp_property()->domain),
          'section' => 'layouts_property_overview_settings',
          'priority' => 1
        ));


//        $layouts_settings = wp_parse_args(!empty($wp_properties['configuration']['layouts']['templates']) ? $wp_properties['configuration']['layouts']['templates'] : array(), array(
//          'property_term_single' => 'false',
//          'property_single' => 'false',
//          'search_results' => 'false'
//        ));

//        $layouts_template_files = wp_parse_args(!empty($wp_properties['configuration']['layouts']['files']) ? $wp_properties['configuration']['layouts']['files'] : array(), array(
//          'property_term_single' => 'page.php',
//          'property_single' => 'single.php',
//          'search_results' => 'page.php'
//        ));

//        $template_files = apply_filters('wpp::layouts::template_files', wp_get_theme()->get_files('php', 0));

//        $layouts = new Layouts_Settings();
//  $data_layouts = $layouts->setup_assistant_layouts();
//        $layouts->preloaded_layouts = $layouts->preload_layouts();
//        $data = $layouts->preloaded_layouts;
//  print_r($layouts);
//  die();

//        foreach ($data as $field) {
//          print_r($field);
//    $wp_customize->add_setting('layouts_property_overview_select_'. $field->id, array(
//      'default' => false,
//      'transport' => 'postMessage'
//    ));
//    $wp_customize->add_control('avalon_search_field_5_type', array(
//      'label' => __('Field 5 Type', ud_get_wp_property()->domain),
//      'section' => 'welcome_property_search_settings',
//      'type' => 'checkbox',
//      'choices' => $field_types,
//      'settings' => 'avalon_search_field_5_type'
//    ));
//        }


//    Single property settings
        $wp_customize->add_section('layouts_property_single_settings', array(
          'title' => __('Single property settings', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 2,
        ));
//    Property overview settings
        $wp_customize->add_setting('layouts_property_single', array(
          'default' => __('', ud_get_wp_property()->domain),
          'sanitize_callback' => 'avalon_sanitize_callback',
          'capability' => 'edit_theme_options',
          'transport' => 'postMessage'
        ));
        $wp_customize->add_control('layouts_property_single', array(
          'label' => __('Lol', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'priority' => 1
        ));
      }

    }

  }

}