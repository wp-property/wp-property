<?php
/**
 * Layouts customizer
 *
 * @since 2.0.0
 * @author den@UD
 */
namespace UsabilityDynamics\WPP {

  use WP_Error;
  use WPP_F;
  use ChromePhp;
  use Exception;
  use WP_Customize_Control;

  if (!class_exists('UsabilityDynamics\WPP\WP_Property_Customizer')) {


    class WP_Property_Customizer
    {
      private $api_client;

      /**
       * Adds all required hooks
       */
      public function __construct()
      {

        if ( !WP_PROPERTY_LAYOUTS ) {
          return;
        }

        add_action('customize_register', array($this, 'property_layouts_customizer'));

        add_action('customize_controls_enqueue_scripts', array($this, 'wp_property_customizer_controls'));
        add_action('customize_preview_init', array($this, 'wp_property_customizer_live_preview'));

        add_filter('wpp::layouts::current', array($this, 'wpp_customize_configuration'), 11);

        // extend [wpp] variable for customizer
        add_filter('wpp::localization::instance', array($this, 'localization_instance'));

        $this->api_client = new Layouts_API_Client(array(
          'url' => defined('UD_API_LAYOUTS_URL') ? UD_API_LAYOUTS_URL : 'https://api.usabilitydynamics.com/product/property/layouts/v1'
        ));
      }

      /**
       * @param bool $text
       * @param null $detail
       * @return bool
       */
      static public function debug($text = false, $detail = null)
      {

        global $wp_properties;

        $_debug = false;

        if( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) || ( defined( 'WP_DEBUG_CONSOLE' ) && WP_DEBUG_CONSOLE ) ) ) {
          $_debug = true;
        }

        if ( !$_debug && ( !isset($wp_properties['configuration']['developer_mode']) || $wp_properties['configuration']['developer_mode'] !== 'true') ) {
          $_debug = false;
        }

        if($_debug && class_exists( 'ChromePhp' ) && !headers_sent() ) {

          // truncate strings to avoid sending oversized header.
          if( strlen( $text ) > 1000 ) {
            $text = '[truncated]';
          }

          if( $detail ) {
            ChromePhp::log( '[wp-property:customizer]', $text, $detail);
          } else {
            ChromePhp::log( '[wp-property:customizer]', $text );
          }

          return true;
        }

        return false;

      }

      /**
       * Extends [wpp] variable.
       *
       * Makes [wpp.instance.settings.configuration.base_property_single_url] available for admin that can use customizer.
       *
       * @todo Stop adding the test URLS to [configuration], should be added to dedicated field just for customizer. - potanin@UD
       * @todo Fix the [base_property_term_url] url to not be ghetto. - potanin@UD
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

        if( $properties && is_array( $properties ) && !empty( $properties ) ) {
          $post_id = $properties[0]->ID;

          $post_url = get_permalink($post_id);

          $data['_customizer'] = isset( $data['_customizer'] ) && is_array( $data['_customizer'] ) ? $data['_customizer'] : array();

          // store first property url
          $data['_customizer']['base_property_single_url'] = $post_url;

          // store first property url
          if ( WPP_FEATURE_FLAG_WPP_LISTING_CATEGORY ){
            $_popular_listing_category_terms = get_terms( array(
              'taxonomy' => 'wpp_listing_category',
              'hide_empty' => true,
              'orderby' => 'count',
              'number' => 1
            ) );
            if( !is_wp_error( $_popular_listing_category_terms ) && isset( $_popular_listing_category_terms[0] ) ) {
              $data['settings']['configuration']['base_property_term_url'] = home_url( '/listings' . get_term_meta( $_popular_listing_category_terms[0]->term_id, 'listing-category-url_path', true ));
            }
          }

          // get home url. This could/should be improved.
          $data['_customizer']['base_property_url'] = home_url($wp_properties['configuration']['base_slug']);

        }

        return $data;

      }

      /**
       * @param $false
       * @return array
       */
      public function wpp_customize_configuration($false)
      {

        if (empty($_POST) || !isset( $_POST['customized'] ) ) {
          return $false;
        }

        self::debug( 'layouts_property_overview_choice' );

        try {
          $selected_items = json_decode(stripslashes($_POST['customized']));

          if( isset( $selected_items->layouts_property_overview_id ) ) {
            $data['render_type'] = 'property-overview';
            $data['layout_id' ] = $selected_items->layouts_property_overview_id;
            $data['template_file' ] = get_theme_mod('layouts_property_overview_select', null );
            $data['templates'] = array( get_theme_mod('layouts_property_overview_select', null ) );
          }

          if( isset( $selected_items->layouts_property_term_id ) ) {
            $data['render_type'] = 'term-overview';
            $data['layout_id' ] = $selected_items->layouts_property_term_id;
            $data['template_file'] = get_theme_mod('layouts_term_overview_select', null );
            $data['templates'] = array( get_theme_mod('layouts_property_overview_select', null ) );
          }

          if( isset( $selected_items->layouts_property_single_id ) ) {
            $data['layout_id' ] = $selected_items->layouts_property_single_id;
            $data['render_type'] = 'single-property';
            $data['template_file'] = get_theme_mod('layouts_property_single_select', null );
            $data['templates'] = array( get_theme_mod('layouts_property_overview_select', null ) );
          }

          return isset( $data ) ? $data : $false;

        } catch (Exception $e) {
          error_log($e->getMessage());
        }


        return $false;
      }

      /**
       *
       */
      public function wp_property_customizer_controls()
      {
        wp_enqueue_script('wp-property-customizer-controls', WPP_URL . 'scripts/wp-property-customizer-controls.js', array('jquery', 'customize-controls'), WPP_Version);
      }

      public function wp_property_customizer_live_preview()
      {
        wp_enqueue_script('wp-property-customizer-live-preview', WPP_URL . 'scripts/wp-property-customizer-live-preview.js', array('jquery', 'customize-preview'), WPP_Version);
      }

      /**
       *
       * @todo Migrate layout iteration into standalone function with filter.
       *
       * @param $wp_customize
       */
      public function property_layouts_customizer($wp_customize)
      {
        WPP_F::debug( "WP_Property_Customizer::property_layouts_customizer");

        $template_files = apply_filters('wpp::layouts::template_files', wp_get_theme()->get_files('php', 0));
        $templates_names = array();

        foreach ($template_files as $file => $file_path) {
          $templates_names[$file] = $file;
        }

        $layouts = ud_get_wp_property()->layouts->get_public_layouts();

        $layouts = ud_get_wp_property()->layouts->add_local_layouts( $layouts );

        // Add local layouts to overview, term and single layouts.

        $single_radio_choices = array();
        $overview_radio_choices = array();
        $term_radio_choices = array();
        //die( '<pre>' . print_r( $layouts['single-property'], true ) . '</pre>' );

        foreach ( (array) $layouts['single-property'] as $layout) {

          if( !$layout->screenshot && ( !isset( $layout->local ) || !$layout->local ) ) {
            continue;
          }

          if (!empty($layout->screenshot)) {
            $layout_preview = $layout->screenshot;
          } else {
            $layout_preview = WPP_URL . 'images/no-preview.jpg';
          }

          $single_radio_choices[$layout->_id] = '<img class="wpp-layout-icon" src="' . $layout_preview . '" alt="' . $layout->title . '" />' . $layout->title;

        }

        foreach ( (array) $layouts['term-overview'] as $layout) {

          if( !$layout->screenshot && ( !isset( $layout->local ) || !$layout->local ) ) {
            continue;
          }

          if (!empty($layout->screenshot)) {
            $layout_preview = $layout->screenshot;
          } else {
            $layout_preview = WPP_URL . 'images/no-preview.jpg';
          }

          $term_radio_choices[$layout->_id] = '<img class="wpp-layout-icon" src="' . $layout_preview . '" alt="' . $layout->title . '" />' . $layout->title;

        }

        foreach ( (array) $layouts['property-overview'] as $layout) {

          if( !$layout->screenshot && ( !isset( $layout->local ) || !$layout->local ) ) {
            continue;
          }

          if (!empty($layout->screenshot)) {
            $layout_preview = $layout->screenshot;
          } else {
            $layout_preview = WPP_URL . 'images/no-preview.jpg';
          }

          $overview_radio_choices[ $layout->_id ] = '<img class="wpp-layout-icon" src="' . $layout_preview . '" alt="' . $layout->title . '" />' . $layout->title;

        }

        $wp_customize->add_panel('layouts_area_panel', array(
          'priority' => 20,
          'capability' => 'edit_theme_options',
          'title' => __('Property Layouts', ud_get_wp_property()->domain),
          'description' => __('Here you can change page layout in live preview.', ud_get_wp_property()->domain),
        ));

        // Property overview settings
        $wp_customize->add_section('layouts_property_overview_settings', array(
          'title' => __('Results Page', ud_get_wp_property()->domain),
          'description' => __('Overview layouts will apply to property search results.', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 10,
        ));

        // Property Term Landing pages.
        $wp_customize->add_section('layouts_property_term_settings', array(
          'title' => __('Landing Page', ud_get_wp_property()->domain),
          'description' => __('Term landing pages.', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 20,
        ));

        // Single Property.
        $wp_customize->add_section('layouts_property_single_settings', array(
          'title' => __('Property Page', ud_get_wp_property()->domain),
          'description' => __('Layout for single property page in live preview.', ud_get_wp_property()->domain),
          'panel' => 'layouts_area_panel',
          'priority' => 30,
        ));

        $wp_customize->add_setting('layouts_property_overview_id', array(
          'default' => isset( $layouts['property-overview'] ) ? reset($layouts['property-overview'])->_id : null,
          'transport' => 'refresh'
        ));

        $wp_customize->add_setting('layouts_property_term_id', array(
          'default' => isset( $layouts['term-overview'] ) ? reset($layouts['term-overview'])->_id : null,
          'transport' => 'refresh'
        ));

        $wp_customize->add_setting('layouts_property_single_id', array(
          'default' => isset( $layouts['single-property'] ) ? reset($layouts['single-property'])->_id : null,
          'transport' => 'refresh'
        ));

        $wp_customize->add_control(new Layouts_Custom_Control($wp_customize, 'layouts_property_overview_id', array(
          'label' => __('Select Layout for Property overview page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_overview_settings',
          'type' => 'checkbox',
          'choices' => $overview_radio_choices
        )));

        $wp_customize->add_control(new Layouts_Custom_Control($wp_customize, 'layouts_property_term_id', array(
          'label' => __('Select Layout for Term results page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_term_settings',
          'type' => 'checkbox',
          'choices' => $term_radio_choices
        )));

        $wp_customize->add_control(new Layouts_Custom_Control($wp_customize, 'layouts_property_single_id', array(
          'label' => __('Select Layout for Single Property page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'type' => 'radio',
          'choices' => $single_radio_choices
        )));


        $wp_customize->add_setting('layouts_property_overview_select', array(
          'default' => 'single.php',
          'transport' => 'refresh'
        ));

        $wp_customize->add_setting('layouts_property_term_select', array(
          'default' => 'single.php',
          'transport' => 'refresh'
        ));

        $wp_customize->add_setting('layouts_property_single_select', array(
          'default' => 'single.php',
          'transport' => 'refresh'
        ));

        $wp_customize->add_control('layouts_property_overview_select', array(
          'label' => __('Select template for Property Overview page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_overview_settings',
          'type' => 'select',
          'choices' => $templates_names
        ));

        $wp_customize->add_control('layouts_property_term_select', array(
          'label' => __('Select template for Term page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_term_settings',
          'type' => 'select',
          'choices' => $templates_names
        ));

        $wp_customize->add_control('layouts_property_single_select', array(
          'label' => __('Select template for Single Property page', ud_get_wp_property()->domain),
          'section' => 'layouts_property_single_settings',
          'type' => 'select',
          'choices' => $templates_names
        ));

      }

      /**
       * Local Layouts
       *
       *
       */
      public function get_local_layout()
      {

        self::debug( 'customizer::get_local_layout');

        $available_local_layouts = get_posts(array('post_type' => 'wpp_layout', 'post_status' => array( 'pending', 'publish' ) ));
        $local_layouts = array();

        foreach ($available_local_layouts as $local_layout) {
          $ID = $local_layout->ID;
          $_post = get_post($ID);
          $_meta = get_post_meta($ID, 'panels_data', 1);
          $_tags_objects = wp_get_post_terms($ID, 'wpp_layout_type');
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
        } catch (Exception $e) {
          return new WP_Error('100', 'Could not parse query data', $data);
        }
        return $data;
      }
    }
  }

  if (class_exists('WP_Customize_Control')) {
    /**
     * Class to create a custom layout control
     */
    class Layouts_Custom_Control extends WP_Customize_Control
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
            <input style="margin-top: -16px" type="radio" value="<?php echo esc_attr($value); ?>"  name="<?php echo esc_attr($name); ?>" <?php $this->link();
            checked($this->value(), $value); ?> />
          </label>
          <?php
        endforeach;
      }
    }
  }
}

