<?php
/**
 * Layouts customizer
 *
 * @since 2.0.0
 * @author den@UD
 */
namespace UsabilityDynamics\WPP {

  if (!class_exists('UsabilityDynamics\WPP\WP_Property_Customizer')) {

    class WP_Property_Customizer
    {

      /**
       * Adds all required hooks
       */
      public function __construct()
      {
        add_action('customize_register', array($this, 'property_layouts_customizer'));

        add_action('customize_controls_enqueue_scripts', array($this, 'wp_property_customizer_controls'));
        add_action('customize_preview_init', array($this, 'wp_property_customizer_live_preview'));

//        add_action('customize_save_response', array($this, 'wpp_action_customize_save'));

        add_filter('wpp::layouts::configuration', array($this, 'wpp_customize_configuration'), 11);
      }

      public function wpp_customize_configuration($false)
      {
        $selected_items = json_decode(stripslashes($_POST['customized']));
        if (isset($selected_items->layouts_property_single_choice)) {
          $layout_id = $selected_items->layouts_property_single_choice;
        } else {
          $layout_id = '';
        }
        if (isset($selected_items->layouts_property_single_select)) {
          $template_file = $selected_items->layouts_property_single_select;
        } else {
          $template_file = '';
        }
        if (is_singular('property')) {
          if (!empty($layout_id)) {
            $layout = json_decode(base64_decode($layout_id), true);
          } else {
            $layout = '';
          }
          return array(
            'templates' => array($template_file, 'page.php', 'single.php', 'index.php'),
            'layout_meta' => $layout
          );
        }
      }

      public function wp_property_customizer_controls()
      {
        wp_enqueue_script('wp-property-customizer-controls', WPP_URL . 'scripts/wp-property-customizer-controls.js', array('jquery', 'customize-controls'), WPP_Version);
      }

      public function wp_property_customizer_live_preview()
      {
        wp_enqueue_script('wp-property-customizer-live-preview', WPP_URL . 'scripts/wp-property-customizer-live-preview.js', array('jquery', 'customize-preview'), WPP_Version);
      }

      public function property_layouts_customizer($wp_customize)
      {
        $template_files = apply_filters('wpp::layouts::template_files', wp_get_theme()->get_files('php', 0));
        $templates_names = array();
        foreach ($template_files as $file => $file_path) {
          $templates_names[$file] = $file;
        }

        $layouts = get_option('wpp_available_layouts', false);
        $overview_layouts = $layouts['search-results'];
        $single_layouts = $layouts['single-property'];

        $wp_customize->add_panel('layouts_area_panel', array(
          'priority' => 10,
          'capability' => 'edit_theme_options',
          'title' => __('Layouts section', ud_get_wp_property()->domain)
        ));

        // Property overview settings
        $wp_customize->add_section('layouts_property_overview_settings', array(
          'title' => __('Property overview settings', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 1,
        ));

        // Property overview settings
        $wp_customize->add_setting('layouts_property_overview_select', array(
          'default' => false,
          'transport' => 'refresh'
        ));
        $wp_customize->add_control('layouts_property_overview_select', array(
          'label' => __('Select template for Property Overview page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_overview_settings',
          'type' => 'select',
          'choices' => $templates_names
        ));

        $overview_radio_choices = array();
        foreach ($overview_layouts as $layout) {
          $overview_radio_choices[$layout->layout] = $layout->title;
        }
        $wp_customize->add_setting('layouts_property_overview_choice', array(
          'default' => false,
          'transport' => 'refresh'
        ));
        $wp_customize->add_control('layouts_property_overview_choice', array(
          'label' => __('Select Layout for Property Overview page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_overview_settings',
          'type' => 'radio',
          'choices' => $overview_radio_choices
        ));

        // Single property settings
        $wp_customize->add_section('layouts_property_single_settings', array(
          'title' => __('Single property settings', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 2,
        ));
        // Single property settings
        $wp_customize->add_setting('layouts_property_single_select', array(
          'default' => false,
          'transport' => 'refresh'
        ));
        $wp_customize->add_control('layouts_property_single_select', array(
          'label' => __('Select template for Single Property page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'type' => 'select',
          'choices' => $templates_names
        ));

        $single_radio_choices = array();
        foreach ($single_layouts as $layout) {
          $single_radio_choices[$layout->layout] = $layout->title;
        }
        $wp_customize->add_setting('layouts_property_single_choice', array(
          'default' => false,
          'transport' => 'refresh'
        ));
        $wp_customize->add_control('layouts_property_single_choice', array(
          'label' => __('Select Layout for Single Property page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'type' => 'radio',
          'choices' => $single_radio_choices
        ));
      }
    }
  }
}