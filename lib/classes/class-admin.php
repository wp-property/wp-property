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


        if( WP_PROPERTY_SETUP_ASSISTANT ) {
          new Setup_Assistant();
        }

        //** Load admin header scripts */
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        /** Admin interface init */
        add_action("admin_init", array($this, "admin_init"));

        // @todo Move back to Settings -> Properties as it was years ago.
        add_action("admin_menu", array($this, 'admin_menu'), 150);

        // @todo Make directory names dynamic, since it may change.
        add_action("in_plugin_update_message-wp-property/wp-property.php", array($this, 'product_update_message'), 20, 2);

        // wpp-disable-term-editing
        add_filter( 'admin_body_class', array($this, 'admin_body_class'), 20, 2);

      }

      /**
       * Term Meta Column Data
       *
       * @todo Fix hardcoded /listing/ prefix.
       *
       * @author potanin@UD
       *
       * @param $nothing
       * @param $column_name
       * @param $term_id
       */
      public function term_meta_columns( $nothing, $column_name, $term_id ) {

        $_type = get_term_meta( $term_id, '_type', true );

        if( $column_name === 'term_id' ) {
          echo $term_id;
          return;
        }

        if( $column_name === 'source' ) {
          $source = get_term_meta( $term_id, $column_name, true );
          echo $source ? $source : '-';
          return;
        }

        if( $column_name === '_id' ) {
          $type = get_term_meta( $term_id, '_id', true );
          echo $type ? $type : '-';
          return;
        }

        if( $column_name === '_type' ) {
          $type = get_term_meta( $term_id, '_type', true );
          echo $type ? $type : '-';
          return;
        }

        if( $column_name === '_updated' ) {
          $_value = get_term_meta( $term_id, '_updated', true );
          echo $_value ? human_time_diff( $_value ) . ' ago' : '-';
          return;
        }

        if( $column_name === '_created' ) {
          $_value = get_term_meta( $term_id, '_created', true );
          echo $_value ? human_time_diff( $_value ) . ' ago' : '-';
          return;
        }

        if( $column_name === 'url_path' ) {
          $_value = get_term_meta( $term_id, $_type . '-' . $column_name, true );
          echo $_value ? ( '<a href="' . home_url( '/listings' . $_value . '' ) . '" target="_blank">/listings' . $_value . '</a>' ) : '-';
          return;
        }


        if( $_type ) {
          $source = get_term_meta( $term_id, $_type .'-'.$column_name, true );
          echo $source ? $source : '-';
        }


      }

      /**
       * Term Overview Columns
       *
       * @author potanin@UD
       *
       * @param $columns
       * @return mixed
       */
      public function taxonomy_meta_columns( $columns ) {

        //$columns['slug'];

        $columns['_id'] = 'Unique ID';
        $columns['term_id'] = 'ID';

        $columns['url_slug'] = 'Slug';
        $columns['url_path'] = 'Path';

        $columns['_type'] = 'Type';
        $columns['_updated'] = 'Updated';
        $columns['_created'] = 'Created';

        //$columns['_id'] = 'ID';
        return $columns;
      }

      /**
       * Render Term Editor fields.
       *
       * @author potanin@UD
       * @param $tag
       * @param $taxonomy
       */
      public function edit_form_fields( $tag, $taxonomy ) {
        include ud_get_wp_property()->path( "static/views/admin/edit-term-fields.php", 'dir' );
      }

      /**
       * Manipulates admin body class for WPP UX.
       *
       * - Disable term-editing on WP-Property term pages.
       *
       * @todo Make this automatic for "readonly" taxonomies. - potanin@UD
       *
       * @author potanin@UD
       * @return string
       */
      public function admin_body_class( $classes ) {
        global $current_screen, $wp_properties;

        // Do nothing.
        if( !isset( $current_screen->base ) || !isset( $current_screen->post_type ) || !isset( $current_screen->taxonomy ) || $current_screen->post_type !== 'property' ) {
          return $classes;
        }

        // When developer mode is enabeld, allow editing.
        if( isset( $wp_properties['configuration'] ) && isset( $wp_properties['configuration']['developer_mode'] ) && $wp_properties['configuration']['developer_mode'] === 'true' ) {
          return $classes;
        }

        $_readonly_taxonomies = array();

        if (!empty($wp_properties['taxonomies']) && is_array($wp_properties['taxonomies'])) {
          foreach ($wp_properties['taxonomies'] as $_tax => $_tax_detail) {

            if (isset($_tax_detail['readonly']) && ($_tax_detail['readonly'] === 'true' || $_tax_detail['readonly'] == 1 || $_tax_detail['readonly'] === '1')) {
              $_readonly_taxonomies[] = $_tax;
            }
          }
        }

        // Hide term editing UI.
        if( $current_screen->base === 'edit-tags' && in_array($current_screen->taxonomy, $_readonly_taxonomies ) ) {
          return $classes . ' wpp-disable-term-editing wpp-readonly-taxonomy ';
        }

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

        wp_localize_script('wpp-localization', 'wpp', array(
          'instance' => apply_filters( 'wpp::localization::instance', $this->instance->core->get_instance() )
        ));

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
            wp_enqueue_script('underscore');
            wp_enqueue_script('lodash-js');
            wp_enqueue_script('wp-property-backend-global');
            wp_enqueue_script('wp-property-global');
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('wpp-jquery-colorpicker');
            wp_enqueue_script('wpp-select2');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script('jquery-cookie');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script('wp-property-admin-settings');
            wp_enqueue_script('wpp-settings-developer-attributes');
            wp_enqueue_script('wpp-settings-developer-types');

            wp_enqueue_script('custom-jqueryui-script', '//code.jquery.com/ui/1.12.1/jquery-ui.js', array('jquery'));
            wp_enqueue_style('jquery-ui');
            wp_enqueue_style('wpp-jquery-ui-dialog');
            wp_enqueue_style('wpp-jquery-colorpicker-css');
            wp_enqueue_style('select2');
            wp_enqueue_script('jquery-jjsonviewer', $this->instance->path('static/scripts/vendor/jjsonviewer.js', 'url'), array( 'jquery' ), WPP_Version, true );
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
          if ($current_screen->id == 'property_page_all_properties' || $current_screen->post_type == 'property') {
            wp_register_style('wpp-admin-styles', $this->instance->path('static/styles/wpp.admin.css', 'url'), array(), WPP_Version);
            wp_enqueue_style('wpp-admin-styles');
          }
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

        // Advanced rewrite rules.
        if( WPP_FEATURE_FLAG_ADVANCED_REWRITE_RULES ) {
          register_setting( 'wp-property', 'wpp_permalinks' );
          add_settings_section( 'wpp-permalink', 'WP-Property Permalinks', array( 'UsabilityDynamics\WPP\Admin', 'render_permalink_settings' ), 'permalink' );
        }

        // Download backup of configuration or fields
        if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'property_settings' && isset($_REQUEST['wpp_action']) && $_REQUEST['wpp_action'] == 'download-wpp-backup' && isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'download-wpp-backup') ) {
          self::download_settings_backup();
        }

        // Add custom columns and fields to taxonomies that have custom term-meta defined.
        if (!empty($wp_properties['taxonomies']) && is_array($wp_properties['taxonomies'])) {
          foreach ((array)$wp_properties['taxonomies'] as $_tax => $_tax_detail) {

            if (isset($_tax_detail['wpp_term_meta_fields'])) {
              add_action($_tax . '_edit_form_fields', array($this, 'edit_form_fields'), 20, 2);
              add_action('manage_' . $_tax . '_custom_column', array($this, 'term_meta_columns'), 20, 3);
              add_filter('manage_edit-' . $_tax . '_columns', array($this, 'taxonomy_meta_columns'), 20);

            }

          }
        }


      }

      /**
       * Add Rewrite Options section.
       *
       * @todo Implement permalink selection UI based on available rewrite patterns.
       *
       * @author potanin@UD
       */
      static public function render_permalink_settings() {
        global $wp_properties;

        $rewrite_rules = isset( $wp_properties['configuration']['rewrite_rules'] ) ? $wp_properties['configuration']['rewrite_rules'] : '';
        $structures = array();

        //echo 'eg_setting_section_callback_function';
        include ud_get_wp_property()->path( "static/views/admin/permalink-settings.php", 'dir' );
      }

      /**
       * Download backup of configuration or fields
       *
       * @author potanin@UD
       */
      static public function download_settings_backup() {
        global $wp_properties;

        $sitename = sanitize_key(get_bloginfo('name'));

        header("Cache-Control: private,no-cache,no-store");
        header("Content-Description: File Transfer");
        header("Content-Transfer-Encoding: binary");
        header('Content-Type: text/plain; charset=' . get_option('blog_charset'), true);

        $_options = array(
          'type' => 'full',
          'timestamp' => time(),
          'filename' => $sitename . '-wp-property.' . date('Y-m-d') . '.json'
        );

        //if backup of data from setup-assistant
        // get backed-up data for download
        if (isset($_REQUEST['timestamp'])) {
          $data = get_option('wpp_property_backups');
          $wp_properties = $data[$_REQUEST['timestamp']];
        }

        // May be extend backup data by add-ons options.
        if( isset( $_GET['wpp-backup-type'] ) && $_GET['wpp-backup-type'] === 'fields' ) {

          // overwrite some backup options.
          $_options['type'] = 'fields';
          $_options['filename'] = $sitename . '-wp-property.fields.' . date('Y-m-d') . '.json';

          $data = apply_filters('wpp::backup::data', array('wpp_settings' => array(
            'location_matters' => $wp_properties['location_matters'],
            'hidden_attributes' => $wp_properties['hidden_attributes'],
            'searchable_attributes' => $wp_properties['searchable_attributes'],
            'searchable_property_types' => $wp_properties['searchable_property_types'],
            'property_inheritance' => $wp_properties['property_inheritance'],
            'property_stats' => $wp_properties['property_stats'],
            'property_types' => $wp_properties['property_types'],
            'property_stats_groups' => $wp_properties['property_stats_groups'],
            'sortable_attributes' => $wp_properties['sortable_attributes'],
            'searchable_attr_fields' => $wp_properties['searchable_attr_fields'],
            'predefined_search_values' => $wp_properties['predefined_search_values'],
            'admin_attr_fields' => $wp_properties['admin_attr_fields'],
            'predefined_values' => $wp_properties['predefined_values'],
            'default_values' => $wp_properties['default_values'],
            'property_groups' => $wp_properties['property_groups'],
            'geo_type_attributes' => $wp_properties['geo_type_attributes'],
            'numeric_attributes' => $wp_properties['numeric_attributes'],
            'currency_attributes' => $wp_properties['currency_attributes']
          )), $_options );

        }

        if( isset( $_GET['wpp-backup-type'] ) && $_GET['wpp-backup-type'] === 'full' ) {
          $data = apply_filters('wpp::backup::data', array('wpp_settings' => $wp_properties), $_options );
        }

        if( isset( $_options['filename'] ) ) {
          header("Content-Disposition: attachment; filename=" . $_options['filename'] );
        }

        if( isset( $_options['filename'] ) && isset( $data ) ) {
          die(json_encode($data, JSON_PRETTY_PRINT));
        }

        die();

      }

      /**
       * Add Settings page, under Property menu.
       *
       */
      public function admin_menu()
      {

        add_submenu_page('edit.php?post_type=property', __('Settings', ud_get_wp_property()->domain), __('Settings', ud_get_wp_property()->domain), 'manage_wpp_settings', 'property_settings', array( 'UsabilityDynamics\WPP\Settings', 'render_page' ) );

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

      /**
       * @param $input
       * @return mixed
       */
      public function wp_property_sanitize_callback($input)
      {
        return $input;
      }

    }

  }

}