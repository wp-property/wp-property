<?php
/**
 * WP-Property Core Framework
 *
 * Contains primary functions for setting up the framework of the plugin.
 *
 * @version 1.08
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @subpackage Main
 */

class WPP_Core {

  /**
   * Highest-level function initialized on plugin load
   *
   * @since 1.11
   *
   */
  function WPP_Core() {
    global $wp_properties;

    // Determine if memory limit is low and increase it
    if ( (int) ini_get( 'memory_limit' ) < 128 ) {
      ini_set( 'memory_limit', '128M' );
    }

    //** Load premium features */
    WPP_F::load_premium();

    //** Modify request to change feed */
    add_filter( 'request', 'property_feed' );

    //** Check if Facebook tries to request site */
    add_action( 'init', array( 'WPP_F', 'check_facebook_tabs' ) );

    //** Hook in upper init */
    add_action( 'init', array( $this, 'init_upper' ), 0 );

    //** Hook in lower init */
    add_action( 'init', array( $this, 'init_lower' ), 100 );

    //** Setup template_redirect */
    add_action( "template_redirect", array( $this, 'template_redirect' ) );

    //** Pre-init action hook */
    do_action( 'wpp_pre_init' );

    // Check settings data on accord with existing wp_properties data before option updates
    add_filter( 'wpp_settings_save', array( $this, 'check_wp_settings_data' ), 0, 2 );

  }


  /**
   * Called on init, as early as possible.
   *
   * @since 1.11
   * @uses $wp_properties WP-Property configuration array
   * @access public
   *
   */
  function init_upper() {
    global $wp_properties;

    //** Init action hook */
    do_action( 'wpp_init' );

    //** Load languages */
    load_plugin_textdomain( 'wpp', WPP_Path . false, 'wp-property/langs' );

    /** Making template-functions global but load after the premium features, giving the premium features priority. */
    include_once WPP_Templates . '/template-functions.php';

    //* set WPP capabilities */
    $this->set_capabilities();
    
    //** Set up our custom object and taxonomyies */
    WPP_F::register_post_type_and_taxonomies();
    
    //** Load settings into $wp_properties and save settings if nonce exists */
    WPP_F::settings_action();
    
    //** Load all widgets and register widget areas */
    add_action( 'widgets_init', array( 'WPP_F', 'widgets_init' ) );

    //** Add metaboxes hook */
    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

  }


  /**
   * Secondary WPP Initialization ran towards the end of init()
   *
   * Loads things that we want make accessible for modification via other plugins.
   *
   * @since 1.31.0
   * @uses $wp_properties WP-Property configuration array
   * @access public
   *
   */
  function init_lower() {
    global $wp_properties;

    /** Ajax functions */
    add_action( 'wp_ajax_wpp_ajax_max_set_property_type', create_function( "", ' die(WPP_F::mass_set_property_type($_REQUEST["property_type"]));' ) );
    add_action( 'wp_ajax_wpp_ajax_property_query', create_function( "", ' $class = WPP_F::get_property(trim($_REQUEST["property_id"])); if($class) { echo "WPP_F::get_property() output: \n\n"; print_r($class); echo "\nAfter prepare_property_for_display() filter:\n\n"; print_r(prepare_property_for_display($class));  } else { echo __("No property found.","wpp"); } die();' ) );
    add_action( 'wp_ajax_wpp_ajax_image_query', create_function( "", ' $class = WPP_F::get_property_image_data($_REQUEST["image_id"]); if($class)  print_r($class); else echo __("No image found.","wpp"); die();' ) );
    add_action( 'wp_ajax_wpp_ajax_check_plugin_updates', create_function( "", '  echo WPP_F::check_plugin_updates(); die();' ) );
    add_action( 'wp_ajax_wpp_ajax_clear_cache', create_function( "", '  echo WPP_F::clear_cache(); die();' ) );
    add_action( 'wp_ajax_wpp_ajax_revalidate_all_addresses', create_function( "", '  echo WPP_F::revalidate_all_addresses(); die();' ) );
    add_action( 'wp_ajax_wpp_ajax_list_table', create_function( "", ' die(WPP_F::list_table());' ) );
    add_action( 'wp_ajax_wpp_save_settings', create_function( "", ' die(WPP_F::save_settings());' ) );

    /** Ajax pagination for property_overview */
    add_action( "wp_ajax_wpp_property_overview_pagination", array( $this, "ajax_property_overview" ) );
    add_action( "wp_ajax_nopriv_wpp_property_overview_pagination", array( $this, "ajax_property_overview" ) );

    /** Localization. Deprecated way! Now static l10n file is used. */
    add_action( "wp_ajax_wpp_js_localization", array( __CLASS__, "localize_scripts" ) );
    add_action( "wp_ajax_nopriv_wpp_js_localization", array( __CLASS__, "localize_scripts" ) );

    add_filter( "manage_edit-property_sortable_columns", array( &$this, "sortable_columns" ) );
    add_filter( "manage_edit-property_columns", array( &$this, "edit_columns" ) );

    /** Called in setup_postdata().  We add property values here to make available in global $post variable on frontend */
    add_action( 'the_post', array( 'WPP_F', 'the_post' ) );

    add_action( "the_content", array( &$this, "the_content" ) );

    /** Admin interface init */
    add_action( "admin_init", array( &$this, "admin_init" ) );

    add_action( "admin_menu", array( &$this, 'admin_menu' ) );

    add_action( "post_submitbox_misc_actions", array( &$this, "post_submitbox_misc_actions" ) );
    add_action( 'save_post', array( $this, 'save_property' ) );

    //** Address revalidation @since 1.37.2 @author odokienko@UD */
    add_action( 'save_property', create_function( '$post_id', 'WPP_F::revalidate_address($post_id);' ) );

    add_action( 'before_delete_post', array( 'WPP_F', 'before_delete_post' ) );
    add_filter( 'post_updated_messages', array( $this, 'property_updated_messages' ), 5 );

    /** Fix toggale row actions -> get rid of "Quick Edit" on property rows */
    add_filter( 'page_row_actions', array( &$this, 'property_row_actions' ), 0, 2 );

    /** Disables meta cache for property obejcts if enabled */
    add_action( 'pre_get_posts', array( 'WPP_F', 'pre_get_posts' ) );

    /** Fix 404 errors */
    add_filter( "parse_request", array( $this, "parse_request" ) );

    //** Determines if current request is for a child property */
    add_filter( "posts_results", array( 'WPP_F', "posts_results" ) );

    //** Hack. Used to avoid issues of some WPP capabilities */
    add_filter( 'current_screen', array( $this, 'current_screen' ) );

    //** Load admin header scripts */
    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

    //** Check premium feature availability */
    add_action( 'wpp_premium_feature_check', array( 'WPP_F', 'feature_check' ) );

    //** Contextual Help */
    add_action( 'wpp_contextual_help', array( $this, 'wpp_contextual_help' ) );

    //** Page loading handlers */
    add_action( 'load-property_page_all_properties', array( 'WPP_F', 'property_page_all_properties_load' ) );
    add_action( 'load-property_page_property_settings', array( 'WPP_F', 'property_page_property_settings_load' ) );

    add_filter( "manage_property_page_all_properties_columns", array( 'WPP_F', 'overview_columns' ) );
    add_filter( "wpp_overview_columns", array( 'WPP_F', 'custom_attribute_columns' ) );

    add_filter( "wpp_attribute_filter", array( 'WPP_F', 'attribute_filter' ), 10, 2 );

    //** Add custom image sizes */
    foreach ( $wp_properties[ 'image_sizes' ] as $image_name => $image_sizes ) {
      add_image_size( $image_name, $image_sizes[ 'width' ], $image_sizes[ 'height' ], true );
    }

    //** Determine if we are secure */
    $scheme = ( is_ssl() && !is_admin() ? 'https' : 'http' );

    //** Load Localization early so plugins can use them as well */
    //** Try to generate static localization script. It can be flushed on Clear Cache! */
    if( $this->maybe_generate_l10n_script() ) {
      wp_register_script( 'wpp-localization', WPP_URL . 'cache/l10n.js', array(), WPP_Version );
    } else {
      //** This is old way of getting localization for WPP scripts. Deprecated way! */
      wp_register_script( 'wpp-localization', get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php?action=wpp_js_localization', array(), WPP_Version );
    }
    
    wp_register_script( 'wpp-jquery-fancybox', WPP_URL . 'third-party/fancybox/jquery.fancybox-1.3.4.pack.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );
    wp_register_script( 'wpp-jquery-colorpicker', WPP_URL . 'third-party/colorpicker/colorpicker.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-easing', WPP_URL . 'third-party/fancybox/jquery.easing-1.3.pack.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );
    wp_register_script( 'wpp-jquery-ajaxupload', WPP_URL . 'js/fileuploader.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wp-property-admin-overview', WPP_URL . 'js/wpp.admin.overview.js', array( 'jquery', 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wp-property-admin-widgets', WPP_URL . 'js/wpp.admin.widgets.js', array( 'jquery', 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wp-property-admin-settings', WPP_URL . 'js/wpp.admin.settings.js', array( 'jquery', 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wp-property-backend-global', WPP_URL . 'js/wpp.admin.global.js', array( 'jquery', 'wp-property-global', 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wp-property-global', WPP_URL . 'js/wpp.global.js', array( 'jquery', 'wpp-localization', 'jquery-ui-tabs', 'jquery-ui-sortable' ), WPP_Version );
    wp_register_script( 'jquery-cookie', WPP_URL . 'js/jquery.smookie.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );

    if ( WPP_F::can_get_script( $scheme . '://maps.google.com/maps/api/js?sensor=true' ) ) {
      wp_register_script( 'google-maps', $scheme . '://maps.google.com/maps/api/js?sensor=true' );
    }

    wp_register_script( 'wpp-md5', WPP_URL . 'third-party/md5.js', array( 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wpp-jquery-gmaps', WPP_URL . 'js/jquery.ui.map.min.js', array( 'google-maps', 'jquery-ui-core', 'jquery-ui-widget', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-nivo-slider', WPP_URL . 'third-party/jquery.nivo.slider.pack.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-address', WPP_URL . 'js/jquery.address-1.5.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-scrollTo', WPP_URL . 'js/jquery.scrollTo-min.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-validate', WPP_URL . 'js/jquery.validate.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-number-format', WPP_URL . 'js/jquery.number.format.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-data-tables', WPP_URL . "third-party/dataTables/jquery.dataTables.js", array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wp-property-galleria', WPP_URL . 'third-party/galleria/galleria-1.2.5.js', array( 'jquery', 'wpp-localization' ) );

    wp_register_style( 'wpp-jquery-fancybox-css', WPP_URL . 'third-party/fancybox/jquery.fancybox-1.3.4.css' );
    wp_register_style( 'wpp-jquery-colorpicker-css', WPP_URL . 'third-party/colorpicker/colorpicker.css' );
    wp_register_style( 'jquery-ui', WPP_URL . 'css/wpp.admin.jquery.ui.css' );
    wp_register_style( 'wpp-jquery-data-tables', WPP_URL . "css/wpp.admin.data.tables.css" );

    /** Find and register stylesheet  */
    if ( file_exists( STYLESHEETPATH . '/wp-properties.css' ) ) {
      wp_register_style( 'wp-property-frontend', get_bloginfo( 'stylesheet_directory' ) . '/wp-properties.css', array(), WPP_Version );
    } elseif ( file_exists( STYLESHEETPATH . '/wp_properties.css' ) ) {
      wp_register_style( 'wp-property-frontend', get_bloginfo( 'stylesheet_directory' ) . '/wp_properties.css', array(), WPP_Version );
    } elseif ( file_exists( TEMPLATEPATH . '/wp-properties.css' ) ) {
      wp_register_style( 'wp-property-frontend', get_bloginfo( 'template_url' ) . '/wp-properties.css', array(), WPP_Version );
    } elseif ( file_exists( TEMPLATEPATH . '/wp_properties.css' ) ) {
      wp_register_style( 'wp-property-frontend', get_bloginfo( 'template_url' ) . '/wp_properties.css', array(), WPP_Version );
    } elseif ( file_exists( WPP_Templates . '/wp_properties.css' ) && $wp_properties[ 'configuration' ][ 'autoload_css' ] == 'true' ) {
      wp_register_style( 'wp-property-frontend', WPP_URL . 'templates/wp_properties.css', array(), WPP_Version );

      //** Find and register theme-specific style if a custom wp_properties.css does not exist in theme */
      if ( 
        isset( $wp_properties[ 'configuration' ][ 'do_not_load_theme_specific_css' ] ) && 
        $wp_properties[ 'configuration' ][ 'do_not_load_theme_specific_css' ] != 'true' && 
        WPP_F::has_theme_specific_stylesheet() 
      ) {
        wp_register_style( 'wp-property-theme-specific', WPP_URL . "templates/theme-specific/" . get_option( 'template' ) . ".css", array( 'wp-property-frontend' ), WPP_Version );
      }
    }

    //** Find front-end JavaScript and register the script */
    if ( file_exists( STYLESHEETPATH . '/wp_properties.js' ) ) {
      wp_register_script( 'wp-property-frontend', get_bloginfo( 'stylesheet_directory' ) . '/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
    } elseif ( file_exists( TEMPLATEPATH . '/wp_properties.js' ) ) {
      wp_register_script( 'wp-property-frontend', get_bloginfo( 'template_url' ) . '/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
    } elseif ( file_exists( WPP_Templates . '/wp_properties.js' ) ) {
      wp_register_script( 'wp-property-frontend', WPP_URL . 'templates/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
    }

    //** Add troubleshoot log page */
    if ( isset( $wp_properties[ 'configuration' ][ 'show_ud_log' ] ) && $wp_properties[ 'configuration' ][ 'show_ud_log' ] == 'true' ) {
      WPP_F::add_log_page();
    }

    //** Modify admin body class */
    add_filter( 'admin_body_class', array( $this, 'admin_body_class' ), 5 );

    //** Modify Front-end property body class */
    add_filter( 'body_class', array( $this, 'properties_body_class' ) );

    add_filter( 'wp_get_attachment_link', array( 'WPP_F', 'wp_get_attachment_link' ), 10, 6 );

    /** Load all shortcodes */
    add_shortcode( 'property_overview', array( __CLASS__, 'shortcode_property_overview' ) );
    add_shortcode( 'property_search', array( __CLASS__, 'shortcode_property_search' ) );
    add_shortcode( 'featured_properties', array( __CLASS__, 'shortcode_featured_properties' ) );
    add_shortcode( 'property_map', array( __CLASS__, 'shortcode_property_map' ) );
    add_shortcode( 'property_attribute', array( __CLASS__, 'shortcode_property_attribute' ) );

    if ( !empty( $wp_properties[ 'alternative_shortcodes' ][ 'property_overview' ] ) ) {
      add_shortcode( "{$wp_properties[ 'alternative_shortcodes' ]['property_overview']}", array( __CLASS__, 'shortcode_property_overview' ) );
    }

    //** Make Property Featured Via AJAX */
    if ( 
      isset( $_REQUEST[ 'post_id' ] ) 
      && isset( $_REQUEST[ '_wpnonce' ] ) 
      && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], "wpp_make_featured_" . $_REQUEST[ 'post_id' ] ) 
    ) {
      add_action( 'wp_ajax_wpp_make_featured', create_function( "", '  $post_id = $_REQUEST[\'post_id\']; echo WPP_F::toggle_featured( $post_id ); die();' ) );
    }

    //** Post-init action hook */
    do_action( 'wpp_post_init' );

  }


  /**
   * Register metaboxes.
   *
   * @global type $post
   * @global type $wpdb
   */
  function add_meta_boxes() {
    global $post, $wpdb;

    //** Add metabox for child properties */
    if ( $post->post_type == 'property' && $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = '{$post->ID}' AND post_status = 'publish' " ) ) {
      add_meta_box( 'wpp_property_children', __( 'Child Properties', 'wpp' ), array( 'WPP_UI', 'child_properties' ), 'property', 'side', 'high' );
    }
  }


  /**
   * Adds thumbnail feature to WP-Property pages
   *
   *
   * @todo Make sure only ran on property pages
   * @since 0.60
   *
   */
  static public function after_setup_theme() {
    add_theme_support( 'post-thumbnails' );
  }


  /**
   * Adds "Settings" link to the plugin overview page
   *

   *  *
   * @since 0.60
   *
   */
  public function plugin_action_links( $links, $file ) {

    if ( $file == 'wp-property/wp-property.php' ) {
      $settings_link = '<a href="' . admin_url( "edit.php?post_type=property&page=property_settings" ) . '">' . __( 'Settings', 'wpp' ) . '</a>';
      array_unshift( $links, $settings_link ); // before other links
    }
    return $links;
  }


  /**
   * Can enqueue scripts on specific pages, and print content into head
   *
   *
   * @uses $current_screen global variable
   * @since 0.53
   *
   */
  public function admin_enqueue_scripts( $hook ) {
    global $current_screen, $wp_properties, $wpdb;
    
    wp_localize_script( 'wpp-localization', 'wpp', array( 'instance' => $this->get_instance() ) );

    switch ( $current_screen->id ) {

      //** Property Overview Page and Edit Property page */
      case 'property_page_all_properties':
        wp_enqueue_script( 'wp-property-backend-global' );
        wp_enqueue_script( 'wp-property-admin-overview' );

      case 'property':
        wp_enqueue_script( 'wp-property-global' );
        //** Enabldes fancybox js, css and loads overview scripts */
        wp_enqueue_script( 'post' );
        wp_enqueue_script( 'postbox' );
        wp_enqueue_script( 'wpp-jquery-fancybox' );
        wp_enqueue_script( 'wpp-jquery-data-tables' );
        wp_enqueue_style( 'wpp-jquery-fancybox-css' );
        wp_enqueue_style( 'wpp-jquery-data-tables' );
        //** Get width of overview table thumbnail, and set css */
        $thumbnail_attribs = WPP_F::image_sizes( $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] );
        $thumbnail_width = ( !empty( $thumbnail_attribs[ 'width' ] ) ? $thumbnail_attribs[ 'width' ] : false );
        if ( $thumbnail_width ) {
          ?>
          <style typ="text/css">
            #wp-list-table.wp-list-table .column-thumbnail { width: <?php echo $thumbnail_width + 20; ?>px; }
            #wp-list-table.wp-list-table td.column-thumbnail { text-align: right; }
            #wp-list-table.wp-list-table .column-type { width: 90px; }
            #wp-list-table.wp-list-table .column-menu_order { width: 50px; }
            #wp-list-table.wp-list-table td.column-menu_order { text-align: center; }
            #wp-list-table.wp-list-table .column-featured { width: 100px; }
            #wp-list-table.wp-list-table .check-column { width: 26px; }
          </style>
          <?php
        }
        break;

      //** Settings Page */
      case 'property_page_property_settings':
        wp_enqueue_script( 'wp-property-backend-global' );
        wp_enqueue_script( 'wp-property-global' );
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'wpp-jquery-colorpicker' );
        wp_enqueue_script( 'wp-property-admin-settings' );
        wp_enqueue_style( 'wpp-jquery-colorpicker-css' );
        break;

      //** Widgets Page */
      case 'widgets':
        wp_enqueue_script( 'wp-property-backend-global' );
        wp_enqueue_script( 'wp-property-global' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_style( 'jquery-ui' );
        wp_enqueue_script( 'wp-property-admin-widgets' );
        break;

    }

    //** Automatically insert styles sheet if one exists with $current_screen->ID name */
    if ( file_exists( WPP_Path . "/css/{$current_screen->id}.css" ) ) {
      wp_enqueue_style( $current_screen->id . '-style', WPP_URL . "/css/{$current_screen->id}.css", array(), WPP_Version, 'screen' );
    }

    //** Automatically insert JS sheet if one exists with $current_screen->ID name */
    if ( file_exists( WPP_Path . "js/{$current_screen->id}.js" ) ) {
      wp_enqueue_script( $current_screen->id . '-js', WPP_URL . "js/{$current_screen->id}.js", array( 'jquery' ), WPP_Version, 'wp-property-backend-global' );
    }

    //** Enqueue CSS styles on all pages */
    if ( file_exists( WPP_Path . 'css/wpp.admin.css' ) ) {
      wp_register_style( 'wpp-admin-styles', WPP_URL . 'css/wpp.admin.css' );
      wp_enqueue_style( 'wpp-admin-styles' );
    }

  }

  /**
   * Sets up additional pages and loads their scripts
   *
   * @since 0.5
   *
   */
  function admin_menu() {
    global $wp_properties, $submenu;

    // Create property settings page
    $settings_page = add_submenu_page( 'edit.php?post_type=property', __( 'Settings', 'wpp' ), __( 'Settings', 'wpp' ), 'manage_wpp_settings', 'property_settings', create_function( '', 'global $wp_properties; include "ui/page_settings.php";' ) );
    $all_properties = add_submenu_page( 'edit.php?post_type=property', $wp_properties[ 'labels' ][ 'all_items' ], $wp_properties[ 'labels' ][ 'all_items' ], 'edit_wpp_properties', 'all_properties', create_function( '', 'global $wp_properties, $screen_layout_columns; include "ui/page_all_properties.php";' ) );

    /**
     * Next used to add custom submenu page 'All Properties' with Javascript dataTable
     *
     * @author Anton K
     */
    if ( !empty( $submenu[ 'edit.php?post_type=property' ] ) ) {

      //** Comment next line if you want to get back old Property list page. */
      array_shift( $submenu[ 'edit.php?post_type=property' ] );

      foreach ( $submenu[ 'edit.php?post_type=property' ] as $key => $page ) {
        if ( $page[ 2 ] == 'all_properties' ) {
          unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
          array_unshift( $submenu[ 'edit.php?post_type=property' ], $page );
        } elseif ( $page[ 2 ] == 'post-new.php?post_type=property' ) {
          //** Removes 'Add Property' from menu if user can not edit properties. peshkov@UD */
          if ( !current_user_can( 'edit_wpp_property' ) ) {
            unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
          }
        }
      }
    }

    do_action( 'wpp_admin_menu' );

    // Load jQuery UI Tabs and Cookie into settings page (settings_page_property_settings)
    add_action( 'admin_print_scripts-' . $settings_page, create_function( '', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');" ) );

  }

  /**
   * Modify admin body class on property pages for CSS
   *
   * @since 0.5
   */
  function admin_body_class( $content ) {
    global $current_screen;

    if ( $current_screen->id == 'edit-property' ) {
      return 'wp-list-table ';
    }

    if ( $current_screen->id == 'property' ) {
      return 'wpp_property_edit';
    }

  }

  /**
   * Fixed property pages being seen as 404 pages
   *
   * Ran on parse_request;
   *
   * WP handle_404() function decides if current request should be a 404 page
   * Marking the global variable $wp_query->is_search to true makes the function
   * assume that the request is a search.
   *
   * @param $query
   *
   * @since 0.5
   */
  function parse_request( $query ) {
    global $wp, $wp_query, $wp_properties, $wpdb;

    //** If we don't have permalinks, our base slug is always default */
    if ( get_option( 'permalink_structure' ) == '' ) {
      $wp_properties[ 'configuration' ][ 'base_slug' ] = 'property';
    }

    //** If we are displaying search results, we can assume this is the default property page */
    if ( isset( $_REQUEST[ 'wpp_search' ] ) && is_array( $_REQUEST[ 'wpp_search' ] ) ) {

      if ( isset( $_POST[ 'wpp_search' ] ) ) {
        $_query = '?' . http_build_query( array( 'wpp_search' => $_REQUEST[ 'wpp_search' ] ), '', '&' );
        wp_redirect( WPP_F::base_url( $wp_properties[ 'configuration' ][ 'base_slug' ] ) . $_query );
        die();
      }

      $wp_query->wpp_root_property_page = true;
      $wp_query->wpp_search_page = true;
    }

    //** Determine if this is the Default Property Page */

    if ( isset( $wp_properties[ 'configuration' ][ 'base_slug' ] ) && $wp->request == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
      $wp_query->wpp_root_property_page = true;
    }

    if ( !empty( $wp_properties[ 'configuration' ][ 'base_slug' ] ) && $wp->query_string == "p=" . $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
      $wp_query->wpp_root_property_page = true;
    }

    if ( isset( $query->query_vars[ 'name' ] ) && $query->query_vars[ 'name' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
      $wp_query->wpp_root_property_page = true;
    }

    if ( isset( $query->query_vars[ 'pagename' ] ) && $query->query_vars[ 'pagename' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
      $wp_query->wpp_root_property_page = true;
    }

    if ( isset( $query->query_vars[ 'category_name' ] ) && $query->query_vars[ 'category_name' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
      $wp_query->wpp_root_property_page = true;
    }

    //** If this is a the root property page, and the Dynamic Default Property page is used */
    if ( isset( $wp_query->wpp_root_property_page ) && $wp_properties[ 'configuration' ][ 'base_slug' ] == 'property' ) {
      $wp_query->wpp_default_property_page = true;

      WPP_F::console_log( 'Overriding default 404 page status.' );

      /** Set to override the 404 status */
      add_action( 'wp', create_function( '', 'status_header( 200 );' ) );

      //** Prevent is_404() in template files from returning true */
      add_action( 'template_redirect', create_function( '', ' global $wp_query; $wp_query->is_404 = false;' ), 0, 10 );
    }

    $wpp_pages = array();
    if ( isset( $wp_query->wpp_search_page ) ) {
      $wpp_pages[ ] = 'Search Page';
    }
    if ( isset( $wp_query->wpp_default_property_page ) ) {
      $wpp_pages[ ] = 'Default Property Page';
    }
    if ( isset( $wp_query->wpp_root_property_page ) ) {
      $wpp_pages[ ] = 'Root Property Page.';
    }
    if ( !empty( $wpp_pages ) ) {
      WPP_F::console_log( 'WPP_F::parse_request() ran, determined that request is for: ' . implode( ', ', $wpp_pages ) );
    }
    
    if( !is_admin() ) {
      /**
       * HACK.
       * 
       * The issue:
       * When parent page is set as 'Default Properties Page',
       * child page will be rendered as 'property' page. 
       * So Wordpress thinks that it's not a page and uses single template instead of page template.
       *
       * Tablet:
       * We determine if current post is 'page' but uses incorrect post_type 'property'
       * and fix it to valid post_type.
       *
       * @todo it's rough way to fix the problem, should be another one.
       * @see self::template_redirect(). hack is used there.
       * @author peshkov@UD
       */
      if( 
        isset( $query->query_vars[ 'post_type' ] ) && 
        $query->query_vars[ 'post_type' ] == 'property' && 
        isset( $query->query_vars[ $wp_properties[ 'configuration' ][ 'base_slug' ] ] 
      ) ) {
        $posts = get_posts( array( 
          'name' => $query->query_vars[ $wp_properties[ 'configuration' ][ 'base_slug' ] ],
          'post_type' => 'page',
        ) );
        if( !empty( $posts ) && count( $posts ) == 1 ) {
          $query->query_vars[ 'post_type' ] = 'page';
          $query->query_vars[ '_fix_to_page_template' ] = true;
        }
      }
    }
    
    return $query;
  }

  /**
   * Modifies post content
   *
   * @since 1.04
   *
   */
  function the_content( $content ) {
    global $post, $wp_properties, $wp_query;

    if ( !isset( $wp_query->is_property_overview ) ) {
      return $content;
    }

    //** Handle automatic PO inserting for non-search root page */
    if ( 
      !isset( $wp_query->wpp_search_page ) 
      && isset( $wp_query->wpp_root_property_page ) 
      && isset( $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] ) 
      && $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true'
    ) {
      WPP_F::console_log( 'Automatically inserted property overview shortcode into page content.' );
      return WPP_Core::shortcode_property_overview();
    }

    //** Handle automatic PO inserting for search pages */
    if ( 
      isset( $wp_query->wpp_search_page ) 
      && ( !isset( $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] ) || $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] != 'true' )
    ) {
      WPP_F::console_log( 'Automatically inserted property overview shortcode into search page content.' );
      return WPP_Core::shortcode_property_overview();
    }

    return $content;
  }


  /**
   * Hooks into save_post function and saves additional property data
   *
   *
   * @todo Add some sort of custom capability so not only admins can make properties as featured. i.e. Agents can make their own properties featured.
   * @since 1.04
   *
   */
  function save_property( $post_id ) {
    global $wp_properties, $wp_version;

    $_wpnonce = ( version_compare( $wp_version, '3.5', '>=' ) ? 'update-post_' : 'update-property_' ) . $post_id;
    if ( !isset( $_POST[ '_wpnonce' ] ) || !wp_verify_nonce( $_POST[ '_wpnonce' ], $_wpnonce ) || $_POST[ 'post_type' ] !== 'property' ) {
      return $post_id;
    }

    //* Delete cache files of search values for search widget's form */
    $directory = WPP_Path . 'cache/searchwidget';

    if ( is_dir( $directory ) ) {
      $dir = opendir( $directory );
      while ( ( $cachefile = readdir( $dir ) ) ) {
        if ( is_file( $directory . "/" . $cachefile ) ) {
          unlink( $directory . "/" . $cachefile );
        }
      }
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return $post_id;
    }

    $update_data = $_REQUEST[ 'wpp_data' ][ 'meta' ];

    //** Neccessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
    if ( empty( $_REQUEST[ 'exclude_from_supermap' ] ) ) {
      if ( !metadata_exists( 'post', $post_id, 'exclude_from_supermap' ) ) {
        $update_data[ 'exclude_from_supermap' ] = 'false';
      }
    }

    if ( (float) $update_data[ 'latitude' ] == 0 ) $update_data[ 'latitude' ] = '';
    if ( (float) $update_data[ 'longitude' ] == 0 ) $update_data[ 'longitude' ] = '';

    /* get old coordinates and location */
    $old_lat = get_post_meta( $post_id, 'latitude', true );
    $old_lng = get_post_meta( $post_id, 'longitude', true );
    $geo_data = array(
      'old_coordinates' => ( ( empty( $old_lat ) ) || ( empty( $old_lng ) ) ) ? "" : array( 'lat' => $old_lat, 'lng' => $old_lng ),
      'old_location' => ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ) ? get_post_meta( $post_id, $wp_properties[ 'configuration' ][ 'address_attribute' ], true ) : ''
    );
    
    foreach ( $update_data as $meta_key => $meta_value ) {
      $attribute_data = WPP_F::get_attribute_data( $meta_key );
      
      $meta_value = html_entity_decode( $meta_value );
      $meta_value = stripslashes( $meta_value );
      
      //* Only admins can mark properties as featured. */
      if ( $meta_key == 'featured' && !current_user_can( 'manage_options' ) ) {
        //** But be sure that meta 'featured' exists at all */
        if ( !metadata_exists( 'post', $post_id, $meta_key ) ) {
          $meta_value = 'false';
        } else {
          continue;
        }
      }

      //* Remove certain characters */
      if ( isset( $attribute_data[ 'currency' ] ) || isset( $attribute_data[ 'numeric' ] ) ) {
        $meta_value = str_replace( array( "$", "," ), '', $meta_value );
      }

      //* Overwrite old post meta allowing only one value */
      delete_post_meta( $post_id, $meta_key );
      add_post_meta( $post_id, $meta_key, $meta_value );
    }

    //* Check if property has children */
    $children = get_children( "post_parent=$post_id&post_type=property" );

    //* Write any data to children properties that are supposed to inherit things */
    if ( count( $children ) > 0 ) {
      //* 1) Go through all children */
      foreach ( $children as $child_id => $child_data ) {
        //* Determine child property_type */
        $child_property_type = get_post_meta( $child_id, 'property_type', true );
        //* Check if child's property type has inheritence rules, and if meta_key exists in inheritance array */
        if ( is_array( $wp_properties[ 'property_inheritance' ][ $child_property_type ] ) ) {
          foreach ( $wp_properties[ 'property_inheritance' ][ $child_property_type ] as $i_meta_key ) {
            $parent_meta_value = get_post_meta( $post_id, $i_meta_key, true );
            //* inheritance rule exists for this property_type for this meta_key */
            update_post_meta( $child_id, $i_meta_key, $parent_meta_value );
          }
        }
      }
    }

    WPP_F::maybe_set_gpid( $post_id );

    if ( isset( $_REQUEST[ 'parent_id' ] ) ) {
      $_REQUEST[ 'parent_id' ] = WPP_F::update_parent_id( $_REQUEST[ 'parent_id' ], $post_id );
    }

    do_action( 'save_property', $post_id );

    return true;
  }

  /**
   * Inserts content into the "Publish" metabox on property pages
   *
   * @since 1.04
   *
   */
  function post_submitbox_misc_actions() {
    global $post, $wp_properties;
    if ( $post->post_type == 'property' ) {
      ?>
      <div class="misc-pub-section ">
        <ul>
          <li><?php _e( 'Menu Sort Order:', 'wpp' ) ?> <?php echo WPP_F::input( "name=menu_order&special=size=4", $post->menu_order ); ?></li>
          <?php if ( current_user_can( 'manage_options' ) ) { ?>
            <li><?php echo WPP_F::checkbox( "name=wpp_data[meta][featured]&label=" . __( 'Display in featured listings.', 'wpp' ), get_post_meta( $post->ID, 'featured', true ) ); ?></li>
          <?php } ?>
          <?php do_action( 'wpp_publish_box_options' ); ?>
        </ul>
      </div>
      <?php
    }
  }

  /**
   * Removes "quick edit" link on property type objects
   *
   * Called in via page_row_actions filter
   *
   * @since 0.5
   *
   */
  function property_row_actions( $actions, $post ) {
    if ( $post->post_type != 'property' )
      return $actions;

    unset( $actions[ 'inline' ] );

    return $actions;
  }

  /**
   * Adds property-relevant messages to the property post type object
   *
   *
   * @since 0.5
   *
   */
  function property_updated_messages( $messages ) {
    global $post_id, $post;

    $messages[ 'property' ] = array(
      0 => '', // Unused. Messages start at index 1.
      1 => sprintf( __( 'Property updated. <a href="%s">view property</a>', 'wpp' ), esc_url( get_permalink( $post_id ) ) ),
      2 => __( 'Custom field updated.', 'wpp' ),
      3 => __( 'Custom field deleted.', 'wpp' ),
      4 => __( 'Property updated.', 'wpp' ),
      /* translators: %s: date and time of the revision */
      5 => isset( $_GET[ 'revision' ] ) ? sprintf( __( 'Property restored to revision from %s', 'wpp' ), wp_post_revision_title( (int) $_GET[ 'revision' ], false ) ) : false,
      6 => sprintf( __( 'Property published. <a href="%s">View property</a>', 'wpp' ), esc_url( get_permalink( $post_id ) ) ),
      7 => __( 'Property saved.', 'wpp' ),
      8 => sprintf( __( 'Property submitted. <a target="_blank" href="%s">Preview property</a>', 'wpp' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
      9 => sprintf( __( 'Property scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview property</a>', 'wpp' ),
        // translators: Publish box date format, see http://php.net/date
        date_i18n( __( 'M j, Y @ G:i', 'wpp' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ) ),
      10 => sprintf( __( 'Property draft updated. <a target="_blank" href="%s">Preview property</a>', 'wpp' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ) ),
    );

    $messages = apply_filters( 'wpp_updated_messages', $messages );

    return $messages;
  }

  /**
   * Sets up property-type columns
   *
   * @since 0.54
   * @uses $wp_properties WP-Property configuration array
   * @access public
   *
   */
  function edit_columns( $columns ) {
    global $wp_properties;

    unset( $columns );

    $columns[ 'cb' ] = "<input type=\"checkbox\" />";
    $columns[ 'title' ] = __( 'Title', 'wpp' );
    $columns[ 'property_type' ] = __( 'Type', 'wpp' );

    if ( is_array( $wp_properties[ 'property_stats' ] ) ) {
      foreach ( $wp_properties[ 'property_stats' ] as $slug => $title )
        $columns[ $slug ] = $title;
    } else {
      $columns = $columns;
    }

    $columns[ 'city' ] = __( 'City', 'wpp' );
    $columns[ 'overview' ] = __( 'Overview', 'wpp' );
    $columns[ 'featured' ] = __( 'Featured', 'wpp' );
    $columns[ 'menu_order' ] = __( 'Order', 'wpp' );
    $columns[ 'thumbnail' ] = __( 'Thumbnail', 'wpp' );

    $columns = apply_filters( 'wpp_admin_overview_columns', $columns );
    //
    return $columns;
  }

  /**
   * Sets up sortable columns columns
   *
   * @since 1.08
   *
   */
  function sortable_columns( $columns ) {
    global $wp_properties;

    $columns[ 'type' ] = 'type';
    $columns[ 'featured' ] = 'featured';

    if ( is_array( $wp_properties[ 'property_stats' ] ) ) {
      foreach ( $wp_properties[ 'property_stats' ] as $slug => $title )
        $columns[ $slug ] = $slug;
    }

    $columns = apply_filters( 'wpp_admin_sortable_columns', $columns );

    return $columns;
  }

  /**
   * Performs front-end pre-header functionality
   *
   * This function is not called on amdin side
   * Loads conditional CSS styles
   *
   * @since 1.11
   */
  function template_redirect() {
    global $post, $property, $wp_query, $wp_properties, $wp_styles, $wpp_query, $wp_taxonomies;
    
    /**
     * HACK.
     * @see self::parse_request();
     * @author peshkov@UD
     */
    if( get_query_var( '_fix_to_page_template' ) ) {
      $wp_query->is_single = false;
      $wp_query->is_page = true;
    }
    
    wp_localize_script( 'wpp-localization', 'wpp', array( 'instance' => $this->get_instance() ) );
    
    //** Load global wp-property script on all frontend pages */
    wp_enqueue_script( 'wp-property-global' );

    if ( apply_filters( 'wpp::custom_styles', false ) === false ) {
      //** Possibly load essential styles that are used in widgets */
      wp_enqueue_style( 'wp-property-frontend' );
      //** Possibly load theme specific styles */
      wp_enqueue_style( 'wp-property-theme-specific' );
    }

    if ( !isset( $wp_properties[ 'configuration' ][ 'do_not_enable_text_widget_shortcodes' ] ) || $wp_properties[ 'configuration' ][ 'do_not_enable_text_widget_shortcodes' ] != 'true' ) {
      add_filter( 'widget_text', 'do_shortcode' );
    }

    do_action( 'wpp_template_redirect' );

    //** Handle single property page previews */
    if ( !empty( $wp_query->query_vars[ 'preview' ] ) && $post->post_type == "property" && $post->post_status == "publish" ) {
      wp_redirect( get_permalink( $post->ID ) );
      die();
    }

    /*
      (count($wp_query->posts) < 2) added post 1.31.1 release to avoid
      taxonomy archives from being broken by single property pages
    */
    if ( count( $wp_query->posts ) < 2 && ( $post->post_type == "property" || isset( $wp_query->is_child_property ) ) ) {
      $wp_query->single_property_page = true;

      //** This is a hack and should be done better */
      if ( !$post ) {
        $post = get_post( $wp_query->queried_object_id );
        $wp_query->posts[ 0 ] = $post;
        $wp_query->post = $post;
      }
    }

    //** Monitor taxonomy archive queries */
    if ( is_tax() && in_array( $wp_query->query_vars[ 'taxonomy' ], array_keys( (array) $wp_taxonomies ) ) ) {
      //** Once get_properties(); can accept taxonomy searches, we can inject a search request in here */
    }

    //** If viewing root property page that is the default dynamic page. */
    if ( isset( $wp_query->wpp_default_property_page ) ) {
      $wp_query->is_property_overview = true;
    }

    //** If this is the root page with a manually inserted shortcode, or any page with a PO shortcode */
    if ( strpos( $post->post_content, "property_overview" ) ) {
      $wp_query->is_property_overview = true;
    }

    //** If this is the root page and the shortcode is automatically inserted */
    if ( isset( $wp_query->wpp_root_property_page ) && $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) {
      $wp_query->is_property_overview = true;
    }

    //** If search result page, and system not explicitly configured to not include PO on search result page automatically */
    if ( 
      isset( $wp_query->wpp_search_page ) && 
      ( !isset( $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] ) || $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] != 'true' ) 
    ) {
      $wp_query->is_property_overview = true;
    }

    //** Scripts and styles to load on all overview and signle listing pages */
    if ( isset( $wp_query->single_property_page ) || isset( $wp_query->is_property_overview ) ) {

      WPP_F::console_log( 'Including scripts for all single and overview property pages.' );

      WPP_F::load_assets( array( 'single', 'overview' ) );

      // Check for and load conditional browser styles
      $conditional_styles = apply_filters( 'wpp_conditional_style_slugs', array( 'IE', 'IE 7', 'msie' ) );

      foreach ( $conditional_styles as $type ) {

        // Fix slug for URL
        $url_slug = strtolower( str_replace( " ", "_", $type ) );

        if ( file_exists( STYLESHEETPATH . "/wp_properties-{$url_slug}.css" ) ) {
          wp_register_style( 'wp-property-frontend-' . $url_slug, get_bloginfo( 'stylesheet_directory' ) . "/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), '1.13' );
        } elseif ( file_exists( TEMPLATEPATH . "/wp_properties-{$url_slug}.css" ) ) {
          wp_register_style( 'wp-property-frontend-' . $url_slug, get_bloginfo( 'template_url' ) . "/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), '1.13' );
        } elseif ( file_exists( WPP_Templates . "/wp_properties-{$url_slug}.css" ) && $wp_properties[ 'configuration' ][ 'autoload_css' ] == 'true' ) {
          wp_register_style( 'wp-property-frontend-' . $url_slug, WPP_URL . "templates/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), WPP_Version );
        }
        // Mark every style as conditional
        $wp_styles->add_data( 'wp-property-frontend-' . $url_slug, 'conditional', $type );
        wp_enqueue_style( 'wp-property-frontend-' . $url_slug );

      }

    }

    //** Scripts loaded only on single property pages */
    if ( isset( $wp_query->single_property_page ) && !post_password_required( $post ) ) {

      WPP_F::console_log( 'Including scripts for all single property pages.' );

      WPP_F::load_assets( array( 'single' ) );

      do_action( 'template_redirect_single_property' );

      add_action( 'wp_head', create_function( '', "do_action('wp_head_single_property'); " ) );

      $property = WPP_F::get_property( $post->ID, "load_gallery=true" );

      $property = prepare_property_for_display( $property );

      //** Make certain variables available to be used within the single listing page */
      $single_page_vars = apply_filters( 'wpp_property_page_vars', array(
        'property' => $property,
        'wp_properties' => $wp_properties
      ) );

      //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
      if ( is_array( $single_page_vars ) ) {
        $wp_query->query_vars = array_merge( $wp_query->query_vars, $single_page_vars );
      }

      $template_found = WPP_F::get_template_part( array_filter( array(
        ( !empty( $property[ 'property_type' ] ) ? "property-{$property[ 'property_type' ]}" : false ),
        "property",
      ) ), array( WPP_Templates ) );

      //** Load the first found template */
      if ( $template_found ) {
        WPP_F::console_log( 'Found single property page template:' . $template_found );
        load_template( $template_found );
        die();
      }

    }

    //** Current requests includes a property overview.  PO may be via shortcode, search result, or due to this being the Default Dynamic Property page */
    if ( isset( $wp_query->is_property_overview ) ) {

      WPP_F::console_log( 'Including scripts for all property overview pages.' );

      if ( isset( $wp_query->wpp_default_property_page ) ) {
        WPP_F::console_log( 'Dynamic Default Property page detected, will load custom template.' );
      } else {
        WPP_F::console_log( 'Custom Default Property page detected, property overview content may be rendered via shortcode.' );
      }

      //** Make certain variables available to be used within the single listing page */
      $overview_page_vars = apply_filters( 'wpp_overview_page_vars', array(
        'wp_properties' => $wp_properties,
        'wpp_query' => $wpp_query
      ) );

      //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
      if ( is_array( $overview_page_vars ) ) {
        $wp_query->query_vars = array_merge( $wp_query->query_vars, $overview_page_vars );
      }

      do_action( 'template_redirect_property_overview' );

      add_action( 'wp_head', create_function( '', "do_action('wp_head_property_overview'); " ) );

      //** If using Dynamic Property Root page, we must load a template */
      if ( isset( $wp_query->wpp_default_property_page ) ) {

        //** Unset any post that may have been found based on query */
        $post = false;

        $template_found = WPP_F::get_template_part( array(
          "property-search-result",
          "property-overview-page",
        ), array( WPP_Templates ) );

        //** Load the first found template */
        if ( $template_found ) {
          WPP_F::console_log( 'Found Default property overview page template:' . $template_found );
          load_template( $template_found );
          die();
        }

      }

    }

    do_action( 'wpp_template_redirect_post_scripts' );

  }

  /**
   * Runs pre-header functions on admin-side only
   *
   * Checks if plugin has been updated.
   *
   * @since 1.10
   *
   */
  public function admin_init() {
    global $wp_properties, $post;

    WPP_F::fix_screen_options();

    // Plug page actions -> Add Settings Link to plugin overview page
    add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

    //* Adds metabox 'General Information' to Property Edit Page */
    add_meta_box( 'wpp_property_meta', __( 'General Information', 'wpp' ), array( 'WPP_UI', 'metabox_meta' ), 'property', 'normal', 'high' );
    //* Adds 'Group' metaboxes to Property Edit Page */
    if ( !empty( $wp_properties[ 'property_groups' ] ) ) {
      foreach ( $wp_properties[ 'property_groups' ] as $slug => $group ) {
        //* There is no sense to add metabox if no one attribute assigned to group */
        if ( !in_array( $slug, $wp_properties[ 'property_stats_groups' ] ) ) {
          continue;
        }
        //* Determine if Group name is empty we add 'NO NAME', other way metabox will not be added */
        if ( empty( $group[ 'name' ] ) ) {
          $group[ 'name' ] = __( 'NO NAME', 'wpp' );
        }
        add_meta_box( $slug, __( $group[ 'name' ], 'wpp' ), array( 'WPP_UI', 'metabox_meta' ), 'property', 'normal', 'high', array( 'group' => $slug ) );
      }
    }

    add_meta_box( 'propetry_filter', $wp_properties[ 'labels' ][ 'name' ] . ' ' . __( 'Search', 'wpp' ), array( 'WPP_UI', 'metabox_property_filter' ), 'property_page_all_properties', 'normal' );

    // Add metaboxes
    do_action( 'wpp_metaboxes' );

    WPP_F::manual_activation();

    //** Download backup of configuration */
    if (
      isset( $_REQUEST[ 'page' ] )
      && $_REQUEST[ 'page' ] == 'property_settings'
      && isset( $_REQUEST[ 'wpp_action' ] )
      && $_REQUEST[ 'wpp_action' ] == 'download-wpp-backup'
      && isset( $_REQUEST[ '_wpnonce' ] )
      && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'download-wpp-backup' )
    ) {
      $sitename = sanitize_key( get_bloginfo( 'name' ) );
      $filename = $sitename . '-wp-property.' . date( 'Y-m-d' ) . '.txt';

      header( "Cache-Control: public" );
      header( "Content-Description: File Transfer" );
      header( "Content-Disposition: attachment; filename=$filename" );
      header( "Content-Transfer-Encoding: binary" );
      header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );

      echo json_encode( $wp_properties );

      die();
    }
  }

  /**
   * Displays featured properties
   *
   * Performs searching/filtering functions, provides template with $properties file
   * Retirms html content to be displayed after location attribute on property edit page
   *
   * @todo Consider making this function depend on shortcode_property_overview() more so pagination and sorting functions work.
   *
   * @since 0.60
   *
   * @param string $listing_id Listing ID must be passed
   *
   * @uses WPP_F::get_properties()
   *
   */
  static public function shortcode_featured_properties( $atts = false ) {
    global $wp_properties, $wpp_query, $post;

    if ( !$atts ) {
      $atts = array();
    }
    $hide_count = '';
    $defaults = array(
      'property_type' => 'all',
      'type' => '',
      'class' => 'shortcode_featured_properties',
      'per_page' => '6',
      'sorter_type' => 'none',
      'show_children' => 'false',
      'hide_count' => 'true',
      'fancybox_preview' => 'false',
      'bottom_pagination_flag' => 'false',
      'pagination' => 'off',
      'stats' => '',
      'thumbnail_size' => 'thumbnail'
    );

    $args = shortcode_atts( $defaults, $atts );
    
    //** Using "image_type" is obsolete */
    if ( $args[ 'thumbnail_size' ] == $defaults[ 'thumbnail_size' ] && !empty( $args[ 'image_type' ] ) ) {
      $args[ 'thumbnail_size' ] = $args[ 'image_type' ];
    }

    //** Using "type" is obsolete. If property_type is not set, but type is, we set property_type from type */
    if ( !empty( $args[ 'type' ] ) && empty( $args[ 'property_type' ] ) ) {
      $args[ 'property_type' ] = $args[ 'type' ];
    }

    // Convert shortcode multi-property-type string to array
    if ( !empty( $args[ 'stats' ] ) ) {

      if ( strpos( $args[ 'stats' ], "," ) ) {
        $args[ 'stats' ] = explode( ",", $args[ 'stats' ] );
      }

      if ( !is_array( $args[ 'stats' ] ) ) {
        $args[ 'stats' ] = array( $args[ 'stats' ] );
      }

      foreach ( $args[ 'stats' ] as $key => $stat ) {
        $args[ 'stats' ][ $key ] = trim( $stat );
      }

    }
    
    /** We hide wrapper to use our custom one. */
    $args[ 'disable_wrapper' ] = 'true';
    
    $args[ 'featured' ] = 'true';
    $args[ 'template' ] = 'featured-shortcode';
    $args[ 'unique_hash' ] = rand( 10000, 99900 );
    
    unset( $args[ 'image_type' ] );
    unset( $args[ 'type' ] );

    $result = WPP_Core::shortcode_property_overview( $args );    
    if( !empty( $result ) ) {
      $result = '<div id="wpp_shortcode_' . $args[ 'unique_hash' ] . '" class="' . $args[ 'class' ] . '">' . $result . '</div>';
    }
    
    return $result;
  }

  /**
   * Returns the property search widget
   *
   *
   * @since 1.04
   *
   */
  static public function shortcode_property_search( $atts = "" ) {
    global $post, $wp_properties;
    $group_attributes = '';
    $per_page = '';
    $pagination = '';
    extract( shortcode_atts( array(
      'searchable_attributes' => '',
      'searchable_property_types' => '',
      'pagination' => 'on',
      'group_attributes' => 'off',
      'per_page' => '10',
      'strict_search' => 'false',
    ), $atts ) );

    if ( empty( $searchable_attributes ) ) {

      //** get first 3 attributes to prevent people from accidentally loading them all (long query) */
      $searchable_attributes = array_slice( $wp_properties[ 'searchable_attributes' ], 0, 5 );

    } else {
      $searchable_attributes = explode( ",", $searchable_attributes );
    }

    $searchable_attributes = array_unique( $searchable_attributes );

    if ( empty( $searchable_property_types ) ) {
      $searchable_property_types = $wp_properties[ 'searchable_property_types' ];
    } else {
      $searchable_property_types = explode( ",", $searchable_property_types );
    }

    $widget_id = $post->ID . "_search";

    ob_start();
    echo '<div class="wpp_shortcode_search">';

    $search_args[ 'searchable_attributes' ] = $searchable_attributes;
    $search_args[ 'searchable_property_types' ] = $searchable_property_types;
    $search_args[ 'group_attributes' ] = ( $group_attributes == 'on' || $group_attributes == 'true' ? true : false );
    $search_args[ 'per_page' ] = $per_page;
    $search_args[ 'pagination' ] = $pagination;
    $search_args[ 'instance_id' ] = $widget_id;
    $search_args[ 'strict_search' ] = $strict_search;

    draw_property_search_form( $search_args );

    echo "</div>";
    $content = ob_get_contents();
    ob_end_clean();

    return $content;

  }

  /**
   * Displays property overview
   *
   * Performs searching/filtering functions, provides template with $properties file
   * Retirms html content to be displayed after location attribute on property edit page
   *
   * @since 1.081
   *
   * @param string $listing_id Listing ID must be passed
   *
   * @return string $result
   *
   * @uses WPP_F::get_properties()
   *
   */
  static public function shortcode_property_overview( $atts = "" ) {
    global $wp_properties, $wpp_query, $property, $post, $wp_query;

    $atts = wp_parse_args( $atts, array() );
    
    WPP_F::force_script_inclusion( 'jquery-ui-widget' );
    WPP_F::force_script_inclusion( 'jquery-ui-mouse' );
    WPP_F::force_script_inclusion( 'jquery-ui-slider' );
    WPP_F::force_script_inclusion( 'wpp-jquery-address' );
    WPP_F::force_script_inclusion( 'wpp-jquery-scrollTo' );
    WPP_F::force_script_inclusion( 'wpp-jquery-fancybox' );
    WPP_F::force_script_inclusion( 'wp-property-frontend' );

    //** Load all queriable attributes **/
    foreach ( WPP_F::get_queryable_keys() as $key ) {
      //** This needs to be done because a key has to exist in the $deafult array for shortcode_atts() to load passed value */
      $queryable_keys[ $key ] = false;
    }

    //** Allow the shorthand of "type" as long as there is not a custom attribute of "type". If "type" does exist as an attribute, then users need to use the full "property_type" query tag. **/
    if ( !array_key_exists( 'type', $queryable_keys ) && ( is_array( $atts ) && array_key_exists( 'type', $atts ) ) ) {
      $atts[ 'property_type' ] = $atts[ 'type' ];
      unset( $atts[ 'type' ] );
    }

    //** Get ALL allowed attributes that may be passed via shortcode (to include property attributes) */
    $defaults[ 'strict_search' ] = false;
    $defaults[ 'show_children' ] = ( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] ) ? $wp_properties[ 'configuration' ][ 'property_overview' ][ 'show_children' ] : 'true' );
    $defaults[ 'child_properties_title' ] = __( 'Floor plans at location:', 'wpp' );
    $defaults[ 'fancybox_preview' ] = $wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ];
    $defaults[ 'bottom_pagination_flag' ] = ( isset( $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] ) && $wp_properties[ 'configuration' ][ 'bottom_insert_pagenation' ] == 'true' ? true : false );
    $defaults[ 'thumbnail_size' ] = $wp_properties[ 'configuration' ][ 'property_overview' ][ 'thumbnail_size' ];
    $defaults[ 'sort_by_text' ] = __( 'Sort By:', 'wpp' );
    $defaults[ 'sort_by' ] = 'post_date';
    $defaults[ 'sort_order' ] = 'DESC';
    $defaults[ 'template' ] = false;
    $defaults[ 'ajax_call' ] = false;
    $defaults[ 'disable_wrapper' ] = false;
    $defaults[ 'sorter_type' ] = 'buttons';
    $defaults[ 'sorter' ] = 'on';
    $defaults[ 'pagination' ] = 'on';
    $defaults[ 'hide_count' ] = false;
    $defaults[ 'per_page' ] = 10;
    $defaults[ 'starting_row' ] = 0;
    $defaults[ 'unique_hash' ] = rand( 10000, 99900 );
    $defaults[ 'detail_button' ] = false;
    $defaults[ 'stats' ] = '';
    $defaults[ 'class' ] = 'wpp_property_overview_shortcode';
    $defaults[ 'in_new_window' ] = false;
    
    $defaults = apply_filters( 'shortcode_property_overview_allowed_args', $defaults, $atts );

    //* Determine if we should disable sorter */
    if( isset( $atts[ 'sorter' ] ) && in_array( $atts[ 'sorter' ], array( 'off', 'false' ) ) ) {
      $atts[ 'sorter' ] = false;
      $atts[ 'sorter_type' ] = 'none';
    }

    if ( !empty( $atts[ 'ajax_call' ] ) ) {
      //** If AJAX call then the passed args have all the data we need */
      $wpp_query = $atts;

      //* Fix ajax data. Boolean value false is returned as string 'false'. */
      foreach ( $wpp_query as $key => $value ) {
        if ( $value == 'false' ) {
          $wpp_query[ $key ] = false;
        }
      }

      $wpp_query[ 'ajax_call' ] = true;

      //** Everything stays the same except for sort order and page */
      $wpp_query[ 'starting_row' ] = ( ( $wpp_query[ 'requested_page' ] - 1 ) * $wpp_query[ 'per_page' ] );

      //** Figure out current page */
      $wpp_query[ 'current_page' ] = $wpp_query[ 'requested_page' ];

    } else {
      /** Determine if fancybox style is included */
      WPP_F::force_style_inclusion( 'wpp-jquery-fancybox-css' );
      
      //** Merge defaults with passed arguments */
      $wpp_query = shortcode_atts( $defaults, $atts );
      $wpp_query[ 'query' ] = shortcode_atts( $queryable_keys, $atts );
      
      //** Handle search */
      if ( !empty( $_REQUEST[ 'wpp_search' ] ) ) {
        $wpp_query[ 'query' ] = shortcode_atts( $wpp_query[ 'query' ], $_REQUEST[ 'wpp_search' ] );
        $wpp_query[ 'query' ] = WPP_F::prepare_search_attributes( $wpp_query[ 'query' ] );

        if ( isset( $_REQUEST[ 'wpp_search' ][ 'sort_by' ] ) ) {
          $wpp_query[ 'sort_by' ] = $_REQUEST[ 'wpp_search' ][ 'sort_by' ];
        }

        if ( isset( $_REQUEST[ 'wpp_search' ][ 'sort_order' ] ) ) {
          $wpp_query[ 'sort_order' ] = $_REQUEST[ 'wpp_search' ][ 'sort_order' ];
        }

        if ( isset( $_REQUEST[ 'wpp_search' ][ 'pagination' ] ) ) {
          $wpp_query[ 'pagination' ] = $_REQUEST[ 'wpp_search' ][ 'pagination' ];
        }

        if ( isset( $_REQUEST[ 'wpp_search' ][ 'per_page' ] ) ) {
          $wpp_query[ 'per_page' ] = $_REQUEST[ 'wpp_search' ][ 'per_page' ];
        }
        
        if ( isset( $_REQUEST[ 'wpp_search' ][ 'strict_search' ] ) ) {
          $wpp_query[ 'strict_search' ] = $_REQUEST[ 'wpp_search' ][ 'strict_search' ];
        }
      }

    }

    //** Load certain settings into query for get_properties() to use */
    $wpp_query[ 'query' ][ 'sort_by' ] = $wpp_query[ 'sort_by' ];
    $wpp_query[ 'query' ][ 'sort_order' ] = $wpp_query[ 'sort_order' ];

    $wpp_query[ 'query' ][ 'pagi' ] = $wpp_query[ 'starting_row' ] . '--' . $wpp_query[ 'per_page' ];

    if ( !isset( $wpp_query[ 'current_page' ] ) ) {
      $wpp_query[ 'current_page' ] = ( $wpp_query[ 'starting_row' ] / $wpp_query[ 'per_page' ] ) + 1;
    }

    //** Load settings that are not passed via shortcode atts */
    $wpp_query[ 'sortable_attrs' ] = WPP_F::get_sortable_keys();

    //** Replace dynamic field values */

    //** Detect currently property for conditional in-shortcode usage that will be replaced from values */
    if ( isset( $post ) && is_object( $post ) ) {

      $dynamic_fields[ 'post_id' ] = isset( $post->ID ) ? $post->ID : 0;
      $dynamic_fields[ 'post_parent' ] = isset( $post->parent_id ) ? $post->parent_id : 0;
      $dynamic_fields[ 'property_type' ] = isset( $post->property_type ) ? $post->property_type : false;

      $dynamic_fields = apply_filters( 'shortcode_property_overview_dynamic_fields', $dynamic_fields );

      if ( is_array( $dynamic_fields ) ) {
        foreach ( $wpp_query[ 'query' ] as $query_key => $query_value ) {
          if ( !empty( $dynamic_fields[ $query_value ] ) ) {
            $wpp_query[ 'query' ][ $query_key ] = $dynamic_fields[ $query_value ];
          }
        }
      }
    }

    //** Remove all blank values */
    $wpp_query[ 'query' ] = array_filter( $wpp_query[ 'query' ] );

    //echo "<pre>"; print_r( $wpp_query ); echo "</pre>";
    
    //** We add # to value which says that we don't want to use LIKE in SQL query for searching this value. */
    $required_strict_search = apply_filters( 'wpp::required_strict_search', array( 'wpp_agents' ) );
    $ignored_strict_search_field_types = apply_filters( 'wpp:ignored_strict_search_field_types', array( 'range_dropdown', 'range_input' ) );
    foreach ( $wpp_query[ 'query' ] as $key => $val ) {
      if ( !key_exists( $key, $defaults ) && $key != 'property_type' ) {
        //** Be sure that the attribute exists of parameter is required for strict search */
        if( 
          ( in_array( $wpp_query[ 'strict_search' ], array( 'true', 'on' ) ) && isset( $wp_properties[ 'property_stats' ][ $key ] ) ) 
          || in_array( $key, $required_strict_search ) 
        ) {
          /** 
           * Ignore specific search attribute fields for strict search. 
           * For example, range values must not be included to strict search. 
           * Also, be sure to ignore list of values
           */
          if(
            ( isset( $wp_properties[ 'searchable_attr_fields' ][ $key ] ) && in_array( $wp_properties[ 'searchable_attr_fields' ][ $key ], (array)$ignored_strict_search_field_types ) )
            || substr_count( $val, ',' ) 
            || substr_count( $val, '&ndash;' ) 
            || substr_count( $val, '--' )
          ) {
            continue;
          } 
          //** Determine if value contains range of numeric values, and ignore it, if so. */
          elseif ( substr_count( $val, '-' ) ) {
            $_val = explode( '-', $val );
            if( count( $_val ) == 2 && is_numeric( $_val[0] ) && is_numeric( $_val[1] ) ) {
              continue;
            }
          }
          $wpp_query[ 'query' ][ $key ] = '#' . $val . '#';
        }
      }
    }
    
    //** Unset this because it gets passed with query (for back-button support) but not used by get_properties() */
    unset( $wpp_query[ 'query' ][ 'per_page' ] );
    unset( $wpp_query[ 'query' ][ 'pagination' ] );
    unset( $wpp_query[ 'query' ][ 'requested_page' ] );
    
    //echo "<pre>"; print_r( $wpp_query[ 'query' ] ); echo "</pre>"; die();
    
    //** Load the results */
    $wpp_query[ 'properties' ] = WPP_F::get_properties( $wpp_query[ 'query' ], true );

    //** Calculate number of pages */
    if ( $wpp_query[ 'pagination' ] == 'on' ) {
      $wpp_query[ 'pages' ] = ceil( $wpp_query[ 'properties' ][ 'total' ] / $wpp_query[ 'per_page' ] );
    }
    
    $property_type = isset( $wpp_query[ 'query' ][ 'property_type' ] ) ? $wpp_query[ 'query' ][ 'property_type' ] : false;

    if ( !empty( $property_type ) && isset( $wp_properties[ 'hidden_attributes' ][ $property_type ] ) ) {
      foreach ( (array) $wp_properties[ 'hidden_attributes' ][ $property_type ] as $attr_key ) {
        unset( $wpp_query[ 'sortable_attrs' ][ $attr_key ] );
      }
    }

    //** Legacy Support - include variables so old templates still work */
    $properties = $wpp_query[ 'properties' ][ 'results' ];
    $thumbnail_sizes = WPP_F::image_sizes( $wpp_query[ 'thumbnail_size' ] );
    $child_properties_title = $wpp_query[ 'child_properties_title' ];
    $unique = $wpp_query[ 'unique_hash' ];
    $thumbnail_size = $wpp_query[ 'thumbnail_size' ];

    //* Debugger */
    if ( isset( $wp_properties[ 'configuration' ][ 'developer_mode' ] ) && $wp_properties[ 'configuration' ][ 'developer_mode' ] == 'true' && !$wpp_query[ 'ajax_call' ] ) {
      echo '<script type="text/javascript">console.log( ' . json_encode( $wpp_query ) . ' ); </script>';
    }

    ob_start();

    //** Make certain variables available to be used within the single listing page */
    $wpp_overview_shortcode_vars = apply_filters( 'wpp_overview_shortcode_vars', array(
      'wp_properties' => $wp_properties,
      'wpp_query' => $wpp_query
    ) );

    //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
    if ( is_array( $wpp_overview_shortcode_vars ) ) {
      $wp_query->query_vars = array_merge( $wp_query->query_vars, $wpp_overview_shortcode_vars );
    }

    $template = $wpp_query[ 'template' ];
    $fancybox_preview = $wpp_query[ 'fancybox_preview' ];
    $show_children = $wpp_query[ 'show_children' ];
    $class = $wpp_query[ 'class' ];
    $stats = $wpp_query[ 'stats' ];
    $in_new_window = ( !empty( $wpp_query[ 'in_new_window' ] ) ? " target=\"_blank\" " : "" );

    //** Make query_vars available to emulate WP template loading */
    extract( $wp_query->query_vars, EXTR_SKIP );

    //** Try find custom template */
    $template_found = WPP_F::get_template_part( array(
      "property-overview-{$template}",
      "property-overview-" . sanitize_key( $property_type ),
      "property-{$template}",
      "property-overview",
    ), array( WPP_Templates ) );

    if ( $template_found ) {
      include $template_found;
    }

    $ob_get_contents = ob_get_contents();
    ob_end_clean();

    $ob_get_contents = apply_filters( 'shortcode_property_overview_content', $ob_get_contents, $wpp_query );

    // Initialize result (content which will be shown) and open wrap (div) with unique id
    if ( $wpp_query[ 'disable_wrapper' ] != 'true' ) {
      $result[ 'top' ] = '<div id="wpp_shortcode_' . $defaults[ 'unique_hash' ] . '" class="wpp_ui ' . $wpp_query[ 'class' ] . '">';
    }

    $result[ 'top_pagination' ] = wpp_draw_pagination( array(
      'class' => 'wpp_top_pagination',
      'sorter_type' => $wpp_query[ 'sorter_type' ],
      'hide_count' => $wpp_query[ 'hide_count' ],
      'sort_by_text' => $wpp_query[ 'sort_by_text' ],
    ) );
    $result[ 'result' ] = $ob_get_contents;

    if ( $wpp_query[ 'bottom_pagination_flag' ] == 'true' ) {
      $result[ 'bottom_pagination' ] = wpp_draw_pagination( array(
        'class' => 'wpp_bottom_pagination',
        'sorter_type' => $wpp_query[ 'sorter_type' ],
        'hide_count' => $wpp_query[ 'hide_count' ],
        'sort_by_text' => $wpp_query[ 'sort_by_text' ],
        'javascript' => false
      ) );
    }

    if ( $wpp_query[ 'disable_wrapper' ] != 'true' ) {
      $result[ 'bottom' ] = '</div>';
    }

    $result = apply_filters( 'wpp_property_overview_render', $result );

    if ( $wpp_query[ 'ajax_call' ] ) {
      return json_encode( array( 'wpp_query' => $wpp_query, 'display' => implode( '', $result ) ) );
    } else {
      return implode( '', $result );
    }
  }

  /**
   * Retrevie property attribute using shortcode.
   *
   *
   * @since 1.26.0
   *
   */
  static public function shortcode_property_attribute( $atts = false ) {
    global $post, $property;

    $this_property = $property;

    if ( empty( $this_property ) && $post->post_type == 'property' ) {
      $this_property = $post;
    }

    $this_property = (array) $this_property;

    if ( !$atts ) {
      $atts = array();
    }

    $defaults = array(
      'property_id' => $this_property[ 'ID' ],
      'attribute' => '',
      'before' => '',
      'after' => '',
      'if_empty' => '',
      'do_not_format' => '',
      'make_terms_links' => 'false',
      'separator' => ' ',
      'strip_tags' => ''
    );

    $args = array_merge( $defaults, $atts );

    if ( empty( $args[ 'attribute' ] ) ) {
      return false;
    }

    $attribute = $args[ 'attribute' ];

    if ( $args[ 'property_id' ] != $this_property[ 'ID' ] ) {

      $this_property = WPP_F::get_property( $args[ 'property_id' ] );

      if ( $args[ 'do_not_format' ] != "true" ) {
        $this_property = prepare_property_for_display( $this_property );
      }

    } else {
      $this_property = $this_property;
    }

    if ( is_taxonomy( $attribute ) && is_object_in_taxonomy( 'property', $attribute ) ) {
      foreach ( wp_get_object_terms( $this_property[ 'ID' ], $attribute ) as $term_data ) {

        if ( $args[ 'make_terms_links' ] == 'true' ) {
          $terms[ ] = '<a class="wpp_term_link" href="' . get_term_link( $term_data, $attribute ) . '"><span class="wpp_term">' . $term_data->name . '</span></a>';
        } else {
          $terms[ ] = '<span class="wpp_term">' . $term_data->name . '</span>';
        }
      }

      if ( is_array( $terms ) && !empty( $terms ) ) {
        $value = implode( $args[ 'separator' ], $terms );
      }

    }

    //** Try to get value using get get_attribute() function */
    if ( !$value && function_exists( 'get_attribute' ) ) {
      $value = get_attribute( $attribute, array(
        'return' => 'true',
        'property_object' => $this_property
      ) );
    }

    if ( !empty( $args[ 'before' ] ) ) {
      $return[ 'before' ] = html_entity_decode( $args[ 'before' ] );
    }

    $return[ 'value' ] = apply_filters( 'wpp_property_attribute_shortcode', $value, $this_property );

    if ( $args[ 'strip_tags' ] == "true" && !empty( $return[ 'value' ] ) ) {
      $return[ 'value' ] = strip_tags( $return[ 'value' ] );
    }

    if ( !empty( $args[ 'after' ] ) ) {
      $return[ 'after' ] = html_entity_decode( $args[ 'after' ] );
    }

    //** When no value is found */
    if ( empty( $return[ 'value' ] ) ) {

      if ( !empty( $args[ 'if_empty' ] ) ) {
        return $args[ 'if_empty' ];
      } else {
        return false;
      }
    }

    if ( is_array( $return ) ) {
      return implode( '', $return );
    }

    return false;

  }

  /**
   * Displays a map for the current property.
   *
   * Must be used on a property page, or within a property loop where the global $post or $property variable is for a property object.
   *
   * @since 1.26.0
   *
   */
  static public function shortcode_property_map( $atts = false ) {
    global $post, $property;

    if ( !$atts ) {
      $atts = array();
    }

    $defaults = array(
      'width' => '100%',
      'height' => '450px',
      'zoom_level' => '13',
      'hide_infobox' => 'false',
      'property_id' => false
    );

    $args = array_merge( $defaults, $atts );

    //** Try to get property if an ID is passed */
    if ( is_numeric( $args[ 'property_id' ] ) ) {
      $property = WPP_F::get_property( $args[ 'property_id' ] );
    }

    //** Load into $property object */
    if ( !isset( $property ) ) {
      $property = $post;
    }

    //** Convert to array */
    $property = (array) $property;

    //** Force map to be enabled here */
    $skip_default_google_map_check = true;

    $map_width = $args[ 'width' ];
    $map_height = $args[ 'height' ];
    $hide_infobox = ( $args[ 'hide_infobox' ] == 'true' ? true : false );

    //** Find most appropriate template */
    $template_found = WPP_F::get_template_part( array( "content-single-property-map", "property-map" ), array( WPP_Templates ) );
    if ( !$template_found ) {
      return false;
    }
    ob_start();
    include $template_found;
    $html = ob_get_contents();
    ob_end_clean();

    $html = apply_filters( 'shortcode_property_map_content', $html, $args );

    return $html;
  }

  /**
   * Return property overview data for AJAX calls
   *
   * @since 0.723
   *
   * @uses WPP_Core::shortcode_property_overview()
   *
   */
  function ajax_property_overview() {

    $params = $_REQUEST[ 'wpp_ajax_query' ];

    if ( !empty( $params[ 'action' ] ) ) {
      unset( $params[ 'action' ] );
    }

    $params[ 'ajax_call' ] = true;

    $data = WPP_Core::shortcode_property_overview( $params );

    die( $data );
  }

  /**
   * Adds wp-property-listing class in search results and property_overview pages
   *
   * @since 0.7260
   */
  function properties_body_class( $classes ) {
    global $post, $wp_properties;
    
    if( !is_object( $post ) ) {
      return $classes;
    }
    
    if ( 
      strpos( $post->post_content, "property_overview" ) 
      || ( is_search() && isset( $_REQUEST[ 'wpp_search' ] ) ) 
      || ( $wp_properties[ 'configuration' ][ 'base_slug' ] == $post->post_name ) 
    ) {
      $classes[ ] = 'wp-property-listing';
    }
    return $classes;
  }

  /**
   * Checks settings data on accord with existing wp_properties data ( before option updates )
   *
   * @param array $wpp_settings New wpp settings data
   * @param array $wp_properties Old wpp settings data
   *
   * @return array $wpp_settings
   */
  function check_wp_settings_data( $wpp_settings, $wp_properties ) {
    if ( is_array( $wpp_settings ) && is_array( $wp_properties ) ) {
      foreach ( $wp_properties as $key => $value ) {
        if ( !isset( $wpp_settings[ $key ] ) ) {
          switch ( $key ) {
            case 'hidden_attributes':
            case 'property_inheritance':
              $wpp_settings[ $key ] = array();
              break;
          }
        }
      }
    }

    return $wpp_settings;
  }

  /**
   * Hack to avoid issues with capabilities and views.
   *
   */
  function current_screen( $screen ) {

    switch ( $screen->id ) {
      case "edit-property":
        wp_redirect( 'edit.php?post_type=property&page=all_properties' );
        exit();
        break;
    }

    return $screen;
  }

  /**
   * Adds all WPP custom capabilities to administrator role.
   * Premium feature capabilities are added by filter in this function, see below.
   *
   * @author peshkov@UD
   */
  function set_capabilities() {
    global $wpp_capabilities;

    //* Get Administrator role for adding custom capabilities */
    $role = get_role( 'administrator' );

    //* General WPP capabilities */
    $wpp_capabilities = array(
      //* Manage WPP Properties Capabilities */
      'edit_wpp_properties' => __( 'View Properties', 'wpp' ),
      'edit_wpp_property' => __( 'Add/Edit Properties', 'wpp' ),
      'edit_others_wpp_properties' => __( 'Edit Other Properties', 'wpp' ),
      //'read_wpp_property' => __( 'Read Property', 'wpp' ),
      'delete_wpp_property' => __( 'Delete Properties', 'wpp' ),
      'publish_wpp_properties' => __( 'Publish Properties', 'wpp' ),
      //'read_private_wpp_properties' => __( 'Read Private Properties', 'wpp' ),
      //* WPP Settings capability */
      'manage_wpp_settings' => __( 'Manage Settings', 'wpp' ),
      //* WPP Taxonomies capability */
      'manage_wpp_categories' => __( 'Manage Taxonomies', 'wpp' )
    );

    //* Adds Premium Feature Capabilities */
    $wpp_capabilities = apply_filters( 'wpp_capabilities', $wpp_capabilities );

    if ( !is_object( $role ) ) {
      return;
    }

    foreach ( $wpp_capabilities as $cap => $value ) {
      if ( empty( $role->capabilities[ $cap ] ) ) {
        $role->add_cap( $cap );
      }
    }
  }
  
  /**
   * Generates javascript file with localization.
   * Adds localization support to all WP-Property scripts.
   *
   * @todo deprecated way of loading localization data. See self::maybe_generate_l10n_script()
   * @since 1.37.3.2
   * @author peshkov@UD
   */
  static function localize_scripts() {
    $l10n = WPP_F::get_cache( 'localize_scripts' );
    if( !$l10n ) {
      $l10n = array();
      //** Include the list of translations */
      include_once WPP_Path . 'l10n.php';
      /** All additional localizations must be added using the filter below. */
      $l10n = apply_filters( 'wpp::js::localization', $l10n );
      foreach ( (array) $l10n as $key => $value ) {
        if ( !is_scalar( $value ) ) {
          continue;
        }
        $l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
      }
      WPP_F::set_cache( 'localize_scripts', $l10n );
    }
    header( 'Content-type: application/x-javascript' );
    die( "var wpp = ( typeof wpp === 'object' ) ? wpp : {}; wpp.strings = " . json_encode( $l10n ) . ';' );
  }

  /**
   * Generates javascript file with localization.
   * Adds localization support to all WP-Property scripts.
   *
   * @since 1.41.5
   * @author peshkov@UD
   */
  static function maybe_generate_l10n_script() {
    $dir = WPP_Path . 'cache/';
    $file = $dir . 'l10n.js';
    //** File already created! */
    if( file_exists( $file ) ){
      return true;
    }
    //** Try to create directory if it doesn't exist */
    if( !is_dir( $dir ) && !wp_mkdir_p( $dir ) ) {
      return false;
    }
    $l10n = array();
    //** Include the list of translations */
    include_once WPP_Path . 'l10n.php';
    /** All additional localizations must be added using the filter below. */
    $l10n = apply_filters( 'wpp::js::localization', $l10n );
    foreach ( (array) $l10n as $key => $value ) {
      if ( !is_scalar( $value ) ) {
        continue;
      }
      $l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
    }
    //** Save file */
    if( @file_put_contents( $file, 'var wpp = ( typeof wpp === \'object\' ) ? wpp : {}; wpp.strings = ' . json_encode( $l10n ) . ';' ) ) {
      return false;
    }
    return true;
  }

  /**
   * WPP Contextual Help
   *
   * @global $current_screen
   *
   * @param $args
   *
   * @author korotkov@ud
   */
  function wpp_contextual_help( $args = array() ) {
    global $contextual_help;

    $defaults = array(
      'contextual_help' => array()
    );

    extract( wp_parse_args( $args, $defaults ) );

    //** If method exists add_help_tab in WP_Screen */
    if ( is_callable( array( 'WP_Screen', 'add_help_tab' ) ) ) {

      //** Loop through help items and build tabs */
      foreach ( (array) $contextual_help as $help_tab_title => $help ) {

        //** Add tab with current info */
        get_current_screen()->add_help_tab(
          array(
            'id' => sanitize_title( $help_tab_title ),
            'title' => __( $help_tab_title, 'wpp' ),
            'content' => implode( "\n", (array) $contextual_help[ $help_tab_title ] ),
          )
        );

      }

      //** Add help sidebar with More Links */
      get_current_screen()->set_help_sidebar(
        '<p><strong>' . __( 'For more information:', 'wpp' ) . '</strong></p>' .
        '<p>' . __( '<a href="https://usabilitydynamics.com/products/wp-property/" target="_blank">WP-Property Product Page</a>', 'wpp' ) . '</p>' .
        '<p>' . __( '<a href="https://usabilitydynamics.com/products/wp-property/forum/" target="_blank">WP-Property Forums</a>', 'wpp' ) . '</p>' .
        '<p>' . __( '<a href="https://usabilitydynamics.com/help/" target="_blank">WP-Property Tutorials</a>', 'wpp' ) . '</p>'
      );

    } else {
      global $current_screen;
      add_contextual_help( $current_screen->id, '<p>' . __( 'Please upgrade Wordpress to the latest version for detailed help.', 'wpp' ) . '</p><p>' . __( 'Or visit <a href="https://usabilitydynamics.com/tutorials/wp-property-help/" target="_blank">WP-Property Help Page</a> on UsabilityDynamics.com', 'wpp' ) . '</p>' );
    }
  }

  /**
   * Returns specific instance data which is used by javascript
   * Javascript Reference: window.wpp.instance
   *
   * @author peshkov@UD
   * @since 1.38
   * @return array
   */
  function get_instance() {
    global $wp_properties;

    $data = array(
      'request' => $_REQUEST,
      'get' => $_GET,
      'post' => $_POST,
      'iframe_enabled' => false,
      'ajax_url' => admin_url('admin-ajax.php'),
      'home_url' => home_url(),
      'user_logged_in' => is_user_logged_in() ? 'true' : 'false',
      'is_permalink' => ( get_option( 'permalink_structure' ) !== '' ? true : false ),
      'settings' => $wp_properties,
    );

    if ( isset( $data[ 'request' ][ 'wp_customize' ] ) && $data[ 'request' ][ 'wp_customize' ] == 'on' ) {
      $data[ 'iframe_enabled' ] = true;
    }

    $data = apply_filters( 'wpp::get_instance', $data );
    
    /** Security: If we're not on an admin, we should remove the XMLI info */
    if( !( is_admin() && current_user_can( 'manage_options' ) ) && isset( $data[ 'settings' ][ 'configuration' ][ 'feature_settings' ][ 'property_import' ] ) ){
      unset( $data[ 'settings' ][ 'configuration' ][ 'feature_settings' ][ 'property_import' ] );
    }

    return $data;
  }

}


