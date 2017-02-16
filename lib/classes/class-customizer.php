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
        'single-property', 'property-overview'
      );

      /**
       * Adds all required hooks
       */
      public function __construct()
      {
        global $wp_properties;

        if ((defined('WP_PROPERTY_LAYOUTS') && WP_PROPERTY_LAYOUTS === true) && (isset($wp_properties['configuration']) && isset($wp_properties['configuration']['enable_layouts']) && $wp_properties['configuration']['enable_layouts'] == 'false')) {
          add_action('customize_register', array($this, 'property_layouts_customizer'));

          add_action('customize_controls_enqueue_scripts', array($this, 'wp_property_customizer_controls'));
          add_action('customize_preview_init', array($this, 'wp_property_customizer_live_preview'));

          add_filter('wpp::layouts::configuration', array($this, 'wpp_customize_configuration'), 11);

          // extend [wpp] variable for customizer
          add_filter('wpp::localization::instance', array($this, 'localization_instance'));

          /*
           *
           */
          $this->api_client = new Layouts_API_Client(array(
            'url' => defined('UD_API_LAYOUTS_URL') ? UD_API_LAYOUTS_URL : 'https://api.usabilitydynamics.com/product/property/layouts/v1'
          ));
        }
      }

      /**
       * Extends [wpp] variable.
       *
       * Makes [wpp.instance.settings.configuration.base_property_single_url] available for admin that can use customizer.
       *
       * @author potanin@UD
       * @param $data
       * @return mixed
       */
      public function localization_instance($data)
      {
        global $wp_properties;

        if (!is_admin() || !current_user_can('customize')) {
          return $data;
        }

        // Get first, most recent, property.
        $properties = get_posts(array(
          'post_type' => 'property',
          'orderby' => 'date',
          'order' => 'desc',
          'post_status' => 'publish',
          'per_page' => 1
        ));

        $post_id = $properties[0]->ID;

        $post_url = get_permalink($post_id);

        // store first property url
        $data['settings']['configuration']['base_property_single_url'] = $post_url;

        // get home url. This could/should be improved.
        $data['settings']['configuration']['base_property_url'] = home_url($wp_properties['configuration']['base_slug']);

        return $data;

      }


      public function wpp_customize_configuration($false)
      {
        global $wp_properties;

        $this->get_local_layout();

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
        $local_layouts = get_option('wpp_available_local_layouts', false);
        $overview_layouts = $layouts['property-overview'];
        $single_layouts = $layouts['single-property'];
        if (!empty($local_layouts)) {
          foreach ($local_layouts as $value) {
            $tag = $value->tags[0]->tag;
            if ($tag == 'property-overview') {
              $overview_layouts = array_merge($overview_layouts, array($value));
            } else if ($tag == 'single-property') {
              $single_layouts = array_merge($single_layouts, array($value));
            }
          }
        }

        $wp_customize->add_panel('layouts_area_panel', array(
          'priority' => 20,
          'capability' => 'edit_theme_options',
          'title' => __('Property Layouts', ud_get_wp_property()->domain),
          'description' => __('Here you can change page layout in live preview.', ud_get_wp_property()->domain),
        ));

        // Property overview settings
        $wp_customize->add_section('layouts_property_overview_settings', array(
          'title' => __('Property Overview', ud_get_wp_property()->domain),
          'description' => __('Overview layouts will apply to default properties page, search results and terms pages.', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 1,
        ));

        $overview_radio_choices = array();
        if (!empty($overview_layouts)) {
          foreach ($overview_layouts as $layout) {
            if (!empty($layout->screenshot)) {
              $layout_preview = $layout->screenshot;
            } else {
              $layout_preview = WPP_URL . 'images/no-preview.jpg';
            }
            $overview_radio_choices[$layout->layout] = '<img style="display: block; width: 150px; height: 150px;" src="' . $layout_preview . '" alt="' . $layout->title . '" />' . $layout->title;
          }
          $first_overview_layout = array_shift($overview_layouts)->layout;
        } else {
          $first_overview_layout = '';
        }
        $wp_customize->add_setting('layouts_property_overview_choice', array(
          'default' => $first_overview_layout,
          'transport' => 'refresh'
        ));
        $wp_customize->add_control(new Layouts_Custom_Control($wp_customize, 'layouts_property_overview_choice', array(
          'label' => __('Select Layout for Property overview page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_overview_settings',
          'type' => 'checkbox',
          'choices' => $overview_radio_choices
        )));

        $wp_customize->add_setting('layouts_property_overview_select', array(
          'default' => 'page.php',
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
          'title' => __('Single Property', ud_get_wp_property()->domain),
          'description' => __('Layout for single property page in live preview.', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 2,
        ));

        $single_radio_choices = array();
        if (!empty($single_layouts)) {
          foreach ($single_layouts as $layout) {
            if (!empty($layout->screenshot)) {
              $layout_preview = $layout->screenshot;
            } else {
              $layout_preview = WPP_URL . 'images/no-preview.jpg';
            }
            $single_radio_choices[$layout->layout] = '<img style="display: block; width: 150px; height: 150px;" src="' . $layout_preview . '" alt="' . $layout->title . '" />' . $layout->title;
          }
          $first_single_layout = array_shift($single_layouts)->layout;
        } else {
          $first_single_layout = '';
        }
        $wp_customize->add_setting('layouts_property_single_choice', array(
          'default' => $first_single_layout,
          'transport' => 'refresh'
        ));
        $wp_customize->add_control(new Layouts_Custom_Control($wp_customize, 'layouts_property_single_choice', array(
          'label' => __('Select Layout for Single Property page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'type' => 'radio',
          'choices' => $single_radio_choices
        )));

        $wp_customize->add_setting('layouts_property_single_select', array(
          'default' => 'single.php',
          'transport' => 'refresh'
        ));
        $wp_customize->add_control('layouts_property_single_select', array(
          'label' => __('Select template for Single Property page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'type' => 'select',
          'choices' => $templates_names
        ));
      }

      public function get_local_layout()
      {
        $available_local_layouts = get_posts(array('post_type' => 'wpp_layout', 'post_status' => 'pending'));
        $local_layouts = array();
        foreach ($available_local_layouts as $local_layout) {
          $ID = $local_layout->ID;
          $_post = get_post($ID);
          $_meta = get_post_meta($ID, 'panels_data', 1);
          $_tags_objects = wp_get_post_terms($ID, 'layout_type');
          $_tags = array();

          if (!empty($_tags_objects) && is_array($_tags_objects)) {
            foreach ($_tags_objects as $_tags_object) {
              $_tags[] = array(
                'label' => $_tags_object->name,
                'tag' => $_tags_object->slug
              );
            }
          }

          $res = $this->create_layout_metas(array(
            'title' => $_post->post_title,
            'screenshot' => get_post_meta($ID, 'screenshot', 1),
            'layout' => $_meta,
            'tags' => $_tags
          ));


          if (!is_wp_error($res)) {
            $res = json_decode($res);
            $local_layouts[] = $res;
          }
        }
        update_option('wpp_available_local_layouts', $local_layouts);
      }

      public function create_layout_metas($data)
      {
        try {
          $data['layout'] = base64_encode(json_encode($data['layout']));
          $data = json_encode($data);
        } catch (\Exception $e) {
          return new \WP_Error('100', 'Could not parse query data', $data);
        }
        return $data;
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

