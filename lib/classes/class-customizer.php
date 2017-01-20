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
       * @var
       */
      private $api_client;

      /**
       * @var array
       */
      private $possible_tags = array(
        'search-results', 'single-property', 'single-property-term'
      );

      /**
       * Adds all required hooks
       */
      public function __construct()
      {
        global $wp_properties;
        if ((defined('WP_PROPERTY_LAYOUTS')) && (isset($wp_properties['configuration']) && isset($wp_properties['configuration']['enable_layouts']) && $wp_properties['configuration']['enable_layouts'] == 'false')) {
          add_action('customize_register', array($this, 'property_layouts_customizer'));

          add_action('customize_controls_enqueue_scripts', array($this, 'wp_property_customizer_controls'));
          add_action('customize_preview_init', array($this, 'wp_property_customizer_live_preview'));

          add_filter('wpp::layouts::configuration', array($this, 'wpp_customize_configuration'), 11);

          /*
           *
           */
          $this->api_client = new Layouts_API_Client(array(
            'url' => 'https://api.usabilitydynamics.com/v1/layouts/'
          ));
        }
      }

      public function wpp_customize_configuration($false)
      {
        global $wp_properties;
        if (!empty($_POST) && (isset($wp_properties['configuration']) && isset($wp_properties['configuration']['enable_layouts']) && $wp_properties['configuration']['enable_layouts'] == 'false')) {
          try {
            $selected_items = json_decode(stripslashes($_POST['customized']));
          } catch (\Exception $e) {
            echo $e->getMessage();
          }
          /** If is single property page */
          if (is_singular('property')) {
            if (isset($selected_items->layouts_property_single_choice)) {
              $layout_id = $selected_items->layouts_property_single_choice;
            } else {
              $layout_id = 'false';
            }
            if ($layout_id == 'none') {
              return $false;
            }
            if (isset($selected_items->layouts_property_single_select)) {
              $template_file = $selected_items->layouts_property_single_select;
            } else {
              $template_file = 'index.php';
            }
            if (!empty($layout_id) && $layout_id !== 'false') {
              try {
                $layout = json_decode(base64_decode($layout_id), true);
              } catch (\Exception $e) {
                echo $e->getMessage();
              }
              return array(
                'templates' => array($template_file, 'page.php', 'single.php', 'index.php'),
                'layout_meta' => $layout
              );
            }
          }

          /** If is property overview page */
          global $wp_query;

          if (!empty($wp_query->wpp_search_page) || is_property_overview_page() || is_tax() && in_array('property', get_taxonomy(get_queried_object()->taxonomy)->object_type)) {
            if (isset($selected_items->layouts_property_overview_choice)) {
              $layout_id = $selected_items->layouts_property_overview_choice;
            } else {
              $layout_id = 'false';
            }
            if ($layout_id == 'none') {
              return $false;
            }
            if (isset($selected_items->layouts_property_overview_select)) {
              $template_file = $selected_items->layouts_property_overview_select;
            } else {
              $template_file = 'index.php';
            }
            if (!empty($layout_id) && $layout_id !== 'false') {
              try {
                $layout = json_decode(base64_decode($layout_id), true);
              } catch (\Exception $e) {
                echo $e->getMessage();
              }
              return array(
                'templates' => array($template_file, 'page.php', 'single.php', 'index.php'),
                'layout_meta' => $layout
              );
            }
          }
        }
        return $false;
      }

      public function wp_property_customizer_controls()
      {
        wp_enqueue_script('wp-property-customizer-controls', WPP_URL . 'scripts/wp-property-customizer-controls.js', array('jquery', 'customize-controls'), WPP_Version);
      }

      public function wp_property_customizer_live_preview()
      {
        wp_enqueue_script('wp-property-customizer-live-preview', WPP_URL . 'scripts/wp-property-customizer-live-preview.js', array('jquery', 'customize-preview'), WPP_Version);
      }

      /**
       * @return array
       */
      public function preload_layouts()
      {

        $res = $this->api_client->get_layouts();

        try {
          $res = json_decode($res);
        } catch (\Exception $e) {
          return array();
        }

        if ($res->ok && !empty($res->data) && is_array($res->data)) {

          $_available_layouts = array();

          foreach ($this->possible_tags as $p_tag) {
            foreach ($res->data as $layout) {

              if (empty($layout->tags) || !is_array($layout->tags)) continue;
              $_found = false;
              foreach ($layout->tags as $_tag) {

                if ($_tag->tag == $p_tag) {
                  $_found = true;
                }
              }
              if (!$_found) continue;

              $_available_layouts[$p_tag][$layout->_id] = $layout;
            }
          }

          update_option('wpp_available_layouts', $_available_layouts);
          return $_available_layouts;
        } else {
          if ($_available_layouts = get_option('wpp_available_layouts', false)) {
            return $_available_layouts;
          } else {
            return array();
          }
        }

        return array();

      }

      public function property_layouts_customizer($wp_customize)
      {
        $template_files = apply_filters('wpp::layouts::template_files', wp_get_theme()->get_files('php', 0));
        $templates_names = array();
        foreach ($template_files as $file => $file_path) {
          $templates_names[$file] = $file;
        }

        $this->preload_layouts();
        $layouts = get_option('wpp_available_layouts', false);
        $overview_layouts = $layouts['single-property-term'];
        $single_layouts = $layouts['single-property'];

        $wp_customize->add_panel('layouts_area_panel', array(
          'priority' => 10,
          'capability' => 'edit_theme_options',
          'title' => __('Layouts section', ud_get_wp_property()->domain),
          'description' => __('Here you can change page layout in live preview.', ud_get_wp_property()->domain),
        ));

        // Property overview settings
        $wp_customize->add_section('layouts_property_overview_settings', array(
          'title' => __('Property overview page settings', ud_get_wp_property()->domain),
          'description' => __('Changing layout for property overview page in live preview.', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 1,
        ));

        $overview_radio_choices = array();
        foreach ($overview_layouts as $layout) {
          if (!empty($layout->screenshot)) {
            $layout_preview = $layout->screenshot;
          } else {
            $layout_preview = WPP_URL . 'images/no-preview.jpg';
          }
          $overview_radio_choices[$layout->layout] = '<img style="display: block; width: 150px; height: 150px;" src="' . $layout_preview . '" alt="' . $layout->title . '" />' . $layout->title;
        }
        $wp_customize->add_setting('layouts_property_overview_choice', array(
          'default' => 'none',
          'transport' => 'refresh'
        ));
        $wp_customize->add_control(new Layouts_Custom_Control($wp_customize, 'layouts_property_overview_choice', array(
          'label' => __('Select Layout for Property overview page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_overview_settings',
          'type' => 'checkbox',
          'choices' => $overview_radio_choices
        )));

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


        // Single property settings
        $wp_customize->add_section('layouts_property_single_settings', array(
          'title' => __('Single property page settings', ud_get_wp_property()->domain),
          'description' => __('Changing layout for single property page in live preview.', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 2,
        ));

        $single_radio_choices = array();
        foreach ($single_layouts as $layout) {
          if (!empty($layout->screenshot)) {
            $layout_preview = $layout->screenshot;
          } else {
            $layout_preview = WPP_URL . 'images/no-preview.jpg';
          }
          $single_radio_choices[$layout->layout] = '<img style="display: block; width: 150px; height: 150px;" src="' . $layout_preview . '" alt="' . $layout->title . '" />' . $layout->title;
        }
        $wp_customize->add_setting('layouts_property_single_choice', array(
          'default' => 'none',
          'transport' => 'refresh'
        ));
        $wp_customize->add_control(new Layouts_Custom_Control($wp_customize, 'layouts_property_single_choice', array(
          'label' => __('Select Layout for Single Property page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'type' => 'radio',
          'choices' => $single_radio_choices
        )));

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
      }
    }
  }

  if (class_exists('WP_Customize_Control')) {
    /**
     * Class to create a custom layout control
     */
    class Layouts_Custom_Control extends \WP_Customize_Control
    {
      public function render_content()
      {
        if (empty($this->choices))
          return;

        $name = '_customize-radio-' . $this->id;

        if (!empty($this->label)) : ?>
          <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
        <?php endif;
        if (!empty($this->description)) : ?>
          <span class="description customize-control-description"><?php echo $this->description; ?></span>
        <?php endif;

        foreach ($this->choices as $value => $label) :
          ?>
          <label>
            <?php echo $label; ?><br/>
            <input style="margin-top: -16px" type="radio" value="<?php echo esc_attr($value); ?>"
                   name="<?php echo esc_attr($name); ?>" <?php $this->link();
            checked($this->value(), $value); ?> />
          </label>
          <?php
        endforeach;
      }
    }
  }
}

