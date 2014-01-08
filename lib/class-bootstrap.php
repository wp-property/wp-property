<?php
/**
 * UsabilityDynamics\WPP Bootstrap
 *
 * Migrated
 * ========
 *
 * - WPP_F::load_premium
 * - WPP_F::check_premium
 * - WPP_F::check_plugin_updates
 * - WPP_F::manual_activation
 * - upgrade
 *
 * @verison 2.0.0
 * @author potanin@UD
 * @namespace UsabilityDynamics\WPP
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Bootstrap' ) ) {

    /**
     * WP-Property Bootstrap
     *
     * Contains primary functions for setting up the framework of the plugin.
     *
     * @class Bootstrap
     * @version 2.0.0
     * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
     * @package WP-Property
     * @subpackage Bootstrap
     * @namespace UsabilityDynamics\WPP
     */
    class Bootstrap {

      /**
       * Plugin Version.
       *
       * @static
       * @property $version
       * @type String
       */
      public static $version = '2.0.0';

      /**
       * Name of Primary Object.
       *
       * @static
       * @property $object
       * @type String
       */
      public static $object = 'property';

      /**
       * Textdomain String
       *
       * @public
       * @property text_domain
       * @var string
       */
      public static $text_domain = 'wpp';

      /**
       * Singleton Instance Reference.
       *
       * @public
       * @static
       * @property $instance
       * @type {Object}
       */
      public static $instance = false;

      /**
       * Settings Instance.
       *
       * @static
       * @property $_settings
       * @type {Object}
       */
      public $_settings = false;

      /**
       * UI Instance.
       *
       * @static
       * @property $_ui
       * @type {Object}
       */
      public $_ui = false;

      /**
       * API Instance.
       *
       * @static
       * @property $_api
       * @type {Object}
       */
      public $_api = false;

      /**
       * Current Theme.
       *
       * @static
       * @property $_theme
       * @type {Object}
       */
      public $_theme = false;

      /**
       * Constructor.
       *
       * UsabilityDynamics components should be avialable.
       * - class_exists( '\UsabilityDynamics\API' );
       * - class_exists( '\UsabilityDynamics\Utility' );
       *
       * @for Loader
       * @method __construct
       */
      public function __construct() {
        global $wpdb, $wpp, $wp_properties;

        // Return Singleton Instance.
        if( self::$instance ) {
          return self::$instance;
        }

        // Save Instance.
        self::$instance = $wpp = &$this;

        // Autoload Vendor Dependencies.
        $this->autoload();

        // Define Constants.
        $this->define_constants();

        // property_meta
        // location_matters
        // searchable_property_types
        // searchable_attributes
        // search_conversions
        // image_sizes
        // configuration.google_maps.infobox_attributes
        // hidden_attributes
        // property_stats
        // property_types
        // property_inheritance

        $this->_utility   = new Utility;

        // Instantiate and load settings.
        $this->_settings  = new Settings(array(
          "store" => "options",
          "key" => "wpp_settings",
          "schema" => WPP_Path . 'static/schemas/system.settings.schema.json'
        ));

        // Instantiate and load Settings.
        $this->_requires  = new \UsabilityDynamics\Requires(array(

        ));

        // Load Default API and Template Functions.
        Utility::load_template( 'default-api.php' );
        Utility::load_template( 'template-functions.php' );

        // Register activation hook -> has to be in the main plugin file
        register_activation_hook( __FILE__, array( &$this, 'activation' ) );

        // Register activation hook -> has to be in the main plugin file
        register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );

        // Initiate the plugin
        add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ) );

        // Hook in upper init
        add_action( 'init', array( &$this, 'init_upper' ), 0 );

        // Hook in lower init
        add_action( 'init', array( &$this, 'init_lower' ), 100 );

        // Setup Template Redirection.
        add_action( "template_redirect", array( &$this, 'template_redirect' ) );

        // Check settings data on accord with existing wp_properties data before option updates
        add_filter( 'wpp_settings_save', array( &$this, 'check_wp_settings_data' ), 0, 2 );

        // Modify request to change feed
        add_filter( 'request', array( &$this, 'property_feed' ) );

      }

      /**
       * Autoload Vendor Dependencies
       *
       * @author potanin@UD
       * @since 2.0.0
       */
      private function autoload() {

        // Seek ./vendor/autoload.php and autoload
        if( !is_file( dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor/autoload.php' ) ) {
          self::fail( 'WP-Property vendor directory missing; attempted to find it in: ' . dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor/autoload.php' );
        }

        // Vendor Autoloader.
        include_once( dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor/autoload.php' );

        // Legacy Support.
        include_once( dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'lib/legacy.php' );

      }

      /**
       * Define Plugin Constants
       *
       *
       * @author potanin@UD
       * @since 2.0.0
       */
      private function define_constants() {
        define( 'WPP_Version', self::$version );
        define( 'WPP_Object', self::$object );
        define( 'WPP_Option_Key', 'wpp_settings::' . WPP_Version );
        define( 'WPP_Directory', dirname( dirname( plugin_basename( __FILE__ ) ) ) );
        define( 'WPP_Path', trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) );
        define( 'WPP_URL', trailingslashit( dirname( plugin_dir_url( __FILE__ ) ) ) );
        define( 'WPP_Templates', WPP_Path . 'templates' );
        define( 'WPP_Premium', WPP_Path . 'core/premium' );
      }

      /**
       * Add 'property' to the list of RSSable post_types.
       *
       * @param string $request
       *
       * @return string
       * @author korotkov@ud
       * @since 1.36.2
       */
      public function property_feed( $qv ) {

        if( isset( $qv[ 'feed' ] ) && !isset( $qv[ 'post_type' ] ) ) {
          $qv[ 'post_type' ] = get_post_types( $args = array(
            'public'   => true,
            '_builtin' => false
          ) );
          array_push( $qv[ 'post_type' ], 'post' );
        }

        return $qv;

      }

      /**
       * Run on plugin activation.
       *
       * As of WP 3.1 this is not ran on automatic update.
       *
       * @todo Add check for cron usage via XMLI or other since cron.php is removed.
       *
       * @since 1.10
       */
      public function activation() {
        global $wp_rewrite;
        // Do close to nothing because only ran on activation, not updates, as of 3.1
        // Now handled by Utility::manual_activation().

        $wp_rewrite->flush_rules();
      }

      /**
       * Plugin Deactivation
       *
       */
      public function deactivation() {
        global $wp_rewrite;
        $timestamp = wp_next_scheduled( 'wpp_premium_feature_check' );
        wp_unschedule_event( $timestamp, 'wpp_premium_feature_check' );
        wp_clear_scheduled_hook( 'wpp_premium_feature_check' );

        $wp_rewrite->flush_rules();

      }

      /**
       * Renders a critical failure.
       *
       * @example
       *    self::fail( 'Critical plugin failure!' );
       *
       * @param $data
       */
      public function fail( $data ) {
        wp_die( '<h1>' . __( 'WP-Property Failure', 'wpp' ) . '</h1><p>' . $data . '</p>' );
      }

      /**
       * Adds thumbnail feature to WP-Property pages
       *
       *
       * @todo Make sure only ran on property pages
       * @since 0.60
       */
      public function after_setup_theme() {

        // Determine if memory limit is low and increase it
        if( (int) ini_get( 'memory_limit' ) < 128 ) {
          ini_set( 'memory_limit', '128M' );
        }

        //** Load premium features */
        //Utility::load_premium();

        //** Pre-init action hook */
        do_action( 'wpp_pre_init' );
        add_theme_support( 'post-thumbnails' );

        //include( '/Users/potanin/Sites/corporate.network.veneer.io/modules/wp-property/vendor/wordpress/meta-box/inc/meta-box.php' );


      }

      /**
       * Called on init, as early as possible.
       *
       * @since 1.11
       * @uses $wp_properties WP-Property configuration array
       * @access public
       *
       */
      public function init_upper() {
        global $wp_properties;

        //** Init action hook */
        do_action( 'wpp_init' );

        //** Load languages */
        load_plugin_textdomain( 'wpp', WPP_Path . false, 'wp-property/languages' );

        //** Load settings into $wp_properties and save settings if nonce exists */
        Utility::settings_action();

        //** Set up our custom object and taxonomyies */
        Utility::register_post_type_and_taxonomies();

        //* set WPP capabilities */
        $this->set_capabilities();

        //** Load all widgets and register widget areas */
        add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

        //** Add metaboxes hook */
        add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) );

        //** Check if Facebook tries to request site */
        add_action( 'init', array( 'WPP_F', 'check_facebook_tabs' ) );

      }

      /**
       * Setup widgets and widget areas.
       *
       * @since 1.31.0
       *
       */
      public function widgets_init() {
        global $wp_properties;

        // Loads Widgets.
        include_once WPP_Path . 'core/widgets/class-child-properties.php';
        include_once WPP_Path . 'core/widgets/class-featured-properties.php';
        include_once WPP_Path . 'core/widgets/class-gallery.php';
        include_once WPP_Path . 'core/widgets/class-latest-properties.php';
        include_once WPP_Path . 'core/widgets/class-other-properties.php';
        include_once WPP_Path . 'core/widgets/class-property-attributes.php';
        include_once WPP_Path . 'core/widgets/class-search-properties.php';

        if( class_exists( 'Property_Attributes_Widget' ) ) {
          register_widget( "Property_Attributes_Widget" );
        }

        if( class_exists( 'ChildPropertiesWidget' ) ) {
          register_widget( 'ChildPropertiesWidget' );
        }

        if( class_exists( 'SearchPropertiesWidget' ) ) {
          register_widget( "SearchPropertiesWidget" );
        }

        if( class_exists( 'FeaturedPropertiesWidget' ) ) {
          register_widget( "FeaturedPropertiesWidget" );
        }

        if( class_exists( 'GalleryPropertiesWidget' ) ) {
          register_widget( "GalleryPropertiesWidget" );
        }

        if( class_exists( 'LatestPropertiesWidget' ) ) {
          register_widget( "LatestPropertiesWidget" );
        }

        if( class_exists( 'OtherPropertiesWidget' ) ) {
          register_widget( "OtherPropertiesWidget" );
        }

        //** Register a sidebar for each property type */
        if( $wp_properties[ 'configuration' ][ 'do_not_register_sidebars' ] != 'true' ) {
          foreach( (array) $wp_properties[ 'property_types' ] as $property_slug => $property_title ) {
            register_sidebar( array(
              'name'          => sprintf( __( 'Property: %s', 'wpp' ), $property_title ),
              'id'            => "wpp_sidebar_{$property_slug}",
              'description'   => sprintf( __( 'Sidebar located on the %s page.', 'wpp' ), $property_title ),
              'before_widget' => '<li id="%1$s"  class="wpp_widget %2$s">',
              'after_widget'  => '</li>',
              'before_title'  => '<h3 class="widget-title">',
              'after_title'   => '</h3>',
            ));
          }
        }
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
      public function init_lower() {
        global $wp_properties;

        /** Ajax functions */
        add_action( 'wp_ajax_wpp_ajax_max_set_property_type', create_function( "", ' die(Utility::mass_set_property_type($_REQUEST["property_type"]));' ) );
        add_action( 'wp_ajax_wpp_ajax_property_query', create_function( "", ' $class = Utility::get_property(trim($_REQUEST["property_id"])); if($class) { echo "Utility::get_property() output: \n\n"; print_r($class); echo "\nAfter prepare_property_for_display() filter:\n\n"; print_r(prepare_property_for_display($class));  } else { echo sprintf(__("No %1s found.","wpp"), Utility::property_label( "singular" ) );; } die();' ) );
        add_action( 'wp_ajax_wpp_ajax_image_query', create_function( "", ' $class = Utility::get_property_image_data($_REQUEST["image_id"]); if($class)  print_r($class); else echo __("No image found.","wpp"); die();' ) );
        add_action( 'wp_ajax_wpp_ajax_check_plugin_updates', create_function( "", '  echo Utility::check_plugin_updates(); die();' ) );
        add_action( 'wp_ajax_wpp_ajax_clear_cache', create_function( "", '  echo Utility::clear_cache(); die();' ) );
        add_action( 'wp_ajax_wpp_ajax_revalidate_all_addresses', create_function( "", '  echo Utility::revalidate_all_addresses(); die();' ) );
        add_action( 'wp_ajax_wpp_ajax_list_table', create_function( "", ' die(Utility::list_table());' ) );
        add_action( 'wp_ajax_wpp_save_settings', create_function( "", ' die(Utility::save_settings());' ) );

        /** Localization */
        add_action( "wp_ajax_wpp_js_localization", array( &$this, "localize_scripts" ) );
        add_action( "wp_ajax_nopriv_wpp_js_localization", array( &$this, "localize_scripts" ) );

        add_filter( "manage_edit-property_sortable_columns", array( &$this, "sortable_columns" ) );
        add_filter( "manage_edit-property_columns", array( &$this, "edit_columns" ) );

        /** Called in setup_postdata().  We add property values here to make available in global $post variable on frontend */
        add_action( 'the_post', array( 'UsabilityDynamics\WPP\Utility', 'the_post' ) );

        add_action( "the_content", array( &$this, "the_content" ) );

        /** Admin interface init */
        add_action( "admin_init", array( &$this, "admin_init" ) );

        add_action( "admin_menu", array( &$this, 'admin_menu' ) );

        add_action( "post_submitbox_misc_actions", array( &$this, "post_submitbox_misc_actions" ) );
        add_action( 'save_post', array( &$this, 'save_property' ) );

        add_action( 'before_delete_post', array( 'UsabilityDynamics\WPP\Utility', 'before_delete_post' ) );
        add_filter( 'post_updated_messages', array( &$this, 'property_updated_messages' ), 5 );

        /** Fix toggale row actions -> get rid of "Quick Edit" on property rows */
        add_filter( 'page_row_actions', array( &$this, 'property_row_actions' ), 0, 2 );

        /** Disables meta cache for property obejcts if enabled */
        add_action( 'pre_get_posts', array( 'UsabilityDynamics\WPP\Utility', 'pre_get_posts' ) );

        /** Fix 404 errors */
        add_filter( "parse_request", array( &$this, "parse_request" ) );

        //** Determines if current request is for a child property */
        //add_filter( "posts_results", array( $this->_utility, "posts_results" ) );
        add_filter( "posts_results", array( 'UsabilityDynamics\WPP\Utility', "posts_results" ) );

        //** Hack. Used to avoid issues of some WPP capabilities */
        add_filter( 'current_screen', array(  &$this, 'current_screen' ) );

        //** Load admin header scripts */
        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

        //** Check premium feature availability */
        add_action( 'wpp_premium_feature_check', array( &$this, 'feature_check' ) );

        //** Contextual Help */
        add_action( 'wpp_contextual_help', array( $this, 'wpp_contextual_help' ) );

        //** Page loading handlers */
        add_action( 'load-property_page_all_properties', array( 'WPP_F', 'property_page_all_properties_load' ) );
        add_action( 'load-property_page_property_settings', array( 'WPP_F', 'property_page_property_settings_load' ) );

        add_filter( "manage_property_page_all_properties_columns", array( 'WPP_F', 'overview_columns' ) );
        add_filter( "wpp_overview_columns", array( 'WPP_F', 'custom_attribute_columns' ) );

        add_filter( "wpp_attribute_filter", array( 'WPP_F', 'attribute_filter' ), 10, 2 );

        //** Add custom image sizes */
        foreach( (array) $wp_properties[ 'image_sizes' ] as $image_name => $image_sizes ) {
          add_image_size( $image_name, $image_sizes[ 'width' ], $image_sizes[ 'height' ], true );
        }

        //** Determine if we are secure */
        $scheme = ( is_ssl() && !is_admin() ? 'https' : 'http' );

        // Load UDX Library Loader.
        wp_register_script( 'wpp.requires', '//cdn.udx.io/requires.js', array( 'jquery' ) );
        wp_register_script( 'wpp.admin.modules', WPP_URL . 'scripts/wpp.admin.modules.js', array( 'wpp-localization', 'wpp.requires' ), WPP_Version );
        wp_register_script( 'wpp.admin.settings', WPP_URL . 'scripts/wpp.admin.settings.js', array( 'wpp-localization', 'wpp.requires' ), WPP_Version );

        //** Load early so plugins can use them as well */
        wp_register_script( 'wpp-localization', get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php?action=wpp_js_localization', array(), WPP_Version );

        wp_register_script( 'wp-property-global', WPP_URL . 'scripts/wpp.global.js', array( 'jquery', 'wpp-localization', 'udx.requires' ), WPP_Version );
        wp_register_script( 'wp-property-admin-overview', WPP_URL . 'scripts/wpp.admin.overview.js', array( 'jquery', 'wpp-localization' ), WPP_Version );
        wp_register_script( 'wp-property-admin-widgets', WPP_URL . 'scripts/wpp.admin.widgets.js', array( 'jquery', 'wpp-localization' ), WPP_Version );
        wp_register_script( 'wp-property-backend-global', WPP_URL . 'scripts/wpp.admin.global.js', array( 'jquery', 'wp-property-global', 'wpp-localization' ), WPP_Version );
        wp_register_script( 'wp-property-galleria', WPP_URL . 'third-party/galleria/galleria-1.2.5.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-md5', WPP_URL . 'third-party/md5.js', array( 'wpp-localization' ), WPP_Version );
        wp_register_script( 'wpp-jquery-fancybox', WPP_URL . 'third-party/fancybox/jquery.fancybox-1.3.4.pack.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );
        wp_register_script( 'wpp-jquery-colorpicker', WPP_URL . 'third-party/colorpicker/colorpicker.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-easing', WPP_URL . 'third-party/fancybox/jquery.easing-1.3.pack.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );
        wp_register_script( 'wpp-jquery-ajaxupload', WPP_URL . 'scripts/fileuploader.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-gmaps', WPP_URL . 'scripts/jquery.ui.map.min.js', array( 'google-maps', 'jquery-ui-core', 'jquery-ui-widget', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-nivo-slider', WPP_URL . 'third-party/jquery.nivo.slider.pack.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-address', WPP_URL . 'scripts/jquery.address-1.5.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-scrollTo', WPP_URL . 'scripts/jquery.scrollTo-min.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-validate', WPP_URL . 'scripts/jquery.validate.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-number-format', WPP_URL . 'scripts/jquery.number.format.js', array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'wpp-jquery-data-tables', WPP_URL . "third-party/dataTables/jquery.dataTables.js", array( 'jquery', 'wpp-localization' ) );
        wp_register_script( 'jquery-cookie', WPP_URL . 'scripts/jquery.smookie.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );
        wp_register_script( 'google-maps', $scheme . '://maps.google.com/maps/api/js?sensor=true' );

        wp_register_style( 'wpp-jquery-fancybox-css', WPP_URL . 'third-party/fancybox/jquery.fancybox-1.3.4.css' );
        wp_register_style( 'wpp-jquery-colorpicker-css', WPP_URL . 'third-party/colorpicker/colorpicker.css' );
        wp_register_style( 'jquery-ui', WPP_URL . 'styles/jquery-ui.css' );
        wp_register_style( 'wpp-jquery-data-tables', WPP_URL . "styles/wpp-data-tables.css" );

        /** Find and register stylesheet  */
        if( file_exists( STYLESHEETPATH . '/wp-properties.css' ) ) {
          wp_register_style( 'wp-property-frontend', get_bloginfo( 'stylesheet_directory' ) . '/wp-properties.css', array(), WPP_Version );
        } elseif( file_exists( STYLESHEETPATH . '/wp_properties.css' ) ) {
          wp_register_style( 'wp-property-frontend', get_bloginfo( 'stylesheet_directory' ) . '/wp_properties.css', array(), WPP_Version );
        } elseif( file_exists( TEMPLATEPATH . '/wp-properties.css' ) ) {
          wp_register_style( 'wp-property-frontend', get_bloginfo( 'template_url' ) . '/wp-properties.css', array(), WPP_Version );
        } elseif( file_exists( TEMPLATEPATH . '/wp_properties.css' ) ) {
          wp_register_style( 'wp-property-frontend', get_bloginfo( 'template_url' ) . '/wp_properties.css', array(), WPP_Version );
        } elseif( file_exists( WPP_Templates . '/wp_properties.css' ) && $wp_properties[ 'configuration' ][ 'autoload_css' ] == 'true' ) {
          wp_register_style( 'wp-property-frontend', WPP_URL . 'templates/wp_properties.css', array(), WPP_Version );

          //** Find and register theme-specific style if a custom wp_properties.css does not exist in theme */
          if( $wp_properties[ 'configuration' ][ 'do_not_load_theme_specific_css' ] != 'true' && Utility::has_theme_specific_stylesheet() ) {
            wp_register_style( 'wp-property-theme-specific', WPP_URL . "templates/theme-specific/" . get_option( 'template' ) . ".css", array( 'wp-property-frontend' ), WPP_Version );
          }
        }

        //** Find front-end JavaScript and register the script */
        if( file_exists( STYLESHEETPATH . '/wp_properties.js' ) ) {
          wp_register_script( 'wp-property-frontend', get_bloginfo( 'stylesheet_directory' ) . '/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
        } elseif( file_exists( TEMPLATEPATH . '/wp_properties.js' ) ) {
          wp_register_script( 'wp-property-frontend', get_bloginfo( 'template_url' ) . '/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
        } elseif( file_exists( WPP_Templates . '/wp_properties.js' ) ) {
          wp_register_script( 'wp-property-frontend', WPP_URL . 'templates/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
        }

        //** Add troubleshoot log page */
        if( isset( $wp_properties[ 'configuration' ][ 'show_ud_log' ] ) && $wp_properties[ 'configuration' ][ 'show_ud_log' ] == 'true' ) {
          Utility::add_log_page();
        }

        //** Modify admin body class */
        add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ), 5 );

        //** Modify Front-end property body class */
        add_filter( 'body_class', array( &$this, 'properties_body_class' ) );

        add_filter( 'wp_get_attachment_link', array( 'WPP_F', 'wp_get_attachment_link' ), 10, 6 );

        /** Load all shortcodes */

        add_shortcode( 'property_search', array( $this, 'shortcode_property_search' ) );
        add_shortcode( 'featured_properties', array( $this, 'shortcode_featured_properties' ) );
        add_shortcode( 'property_map', array( $this, 'shortcode_property_map' ) );
        add_shortcode( 'property_attribute', array( $this, 'shortcode_property_attribute' ) );

        if( !empty( $wp_properties[ 'alternative_shortcodes' ][ 'property_overview' ] ) ) {
          add_shortcode( "{$wp_properties[ 'alternative_shortcodes' ]['property_overview']}", array( $this, 'shortcode_property_overview' ) );
        }

        //** Make Property Featured Via AJAX */
        if( isset( $_REQUEST[ '_wpnonce' ] ) ) {
          if( wp_verify_nonce( $_REQUEST[ '_wpnonce' ], "wpp_make_featured_" . $_REQUEST[ 'post_id' ] ) ) {
            add_action( 'wp_ajax_wpp_make_featured', create_function( "", '  $post_id = $_REQUEST[post_id]; echo Utility::toggle_featured($post_id); die();' ) );
          }
        }

        //** Post-init action hook */
        do_action( 'wpp_post_init' );

      }

      /**
       * Performs front-end pre-header functionality
       *
       * This function is not called on amdin side
       * Loads conditional CSS styles
       *
       * @since 1.11
       */
      public function template_redirect() {
        global $post, $property, $wp_query, $wp_properties, $wp_styles, $wpp_query, $wp_taxonomies;

        wp_localize_script( 'wpp-localization', 'wpp', array( 'instance' => $this->locale_instance() ) );

        //** Load global wp-property script on all frontend pages */
        wp_enqueue_script( 'wp-property-global' );

        //** Load essential styles that are used in widgets */
        wp_enqueue_style( 'wp-property-frontend' );
        wp_enqueue_style( 'wp-property-theme-specific' );

        //** Load non-essential scripts and styles if option is enabled to load them globally */
        if( $wp_properties[ 'configuration' ][ 'load_scripts_everywhere' ] == 'true' ) {
          Utility::console_log( 'Loading WP-Property scripts globally.' );
          Utility::load_assets( array( 'single', 'overview' ) );
        }

        if( $wp_properties[ 'configuration' ][ 'do_not_enable_text_widget_shortcodes' ] != 'true' ) {
          add_filter( 'widget_text', 'do_shortcode' );
        }

        do_action( 'wpp_template_redirect' );

        //** Handle single property page previews */
        if( !empty( $wp_query->query_vars[ 'preview' ] ) && $post->post_type == "property" && $post->post_status == "publish" ) {
          wp_redirect( get_permalink( $post->ID ) );
          die();
        }

        /*
          (count($wp_query->posts) < 2) added post 1.31.1 release to avoid
          taxonomy archives from being broken by single property pages
        */
        if( count( $wp_query->posts ) < 2 && ( $post->post_type == "property" || $wp_query->is_child_property ) ) {
          $wp_query->single_property_page = true;

          //** This is a hack and should be done better */
          if( !$post ) {
            $post                 = get_post( $wp_query->queried_object_id );
            $wp_query->posts[ 0 ] = $post;
            $wp_query->post       = $post;
          }
        }

        //** Monitor taxonomy archive queries */
        if( is_tax() && in_array( $wp_query->query_vars[ 'taxonomy' ], array_keys( (array) $wp_taxonomies ) ) ) {
          //** Once get_properties(); can accept taxonomy searches, we can inject a search request in here */
        }

        //** If viewing root property page that is the default dynamic page. */
        if( $wp_query->wpp_default_property_page ) {
          $wp_query->is_property_overview = true;
        }

        //** If this is the root page with a manually inserted shortcode, or any page with a PO shortcode */
        if( strpos( $post->post_content, "property_overview" ) ) {
          $wp_query->is_property_overview = true;
        }

        //** If this is the root page and the shortcode is automatically inserted */
        if( $wp_query->wpp_root_property_page && $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) {
          $wp_query->is_property_overview = true;
        }

        //** If search result page, and system not explicitly configured to not include PO on search result page automatically */
        if( $wp_query->wpp_search_page && $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] != 'true' ) {
          $wp_query->is_property_overview = true;
        }

        //** Scripts and styles to load on all overview and signle listing pages */
        if( $wp_query->single_property_page || $wp_query->is_property_overview ) {

          Utility::console_log( 'Including scripts for all single and overview property pages.' );

          Utility::load_assets( array( 'single', 'overview' ) );

          // Check for and load conditional browser styles
          $conditional_styles = apply_filters( 'wpp_conditional_style_slugs', array( 'IE', 'IE 7', 'msie' ) );

          foreach( (array) $conditional_styles as $type ) {

            // Fix slug for URL
            $url_slug = strtolower( str_replace( " ", "_", $type ) );

            if( file_exists( STYLESHEETPATH . "/wp_properties-{$url_slug}.css" ) ) {
              wp_register_style( 'wp-property-frontend-' . $url_slug, get_bloginfo( 'stylesheet_directory' ) . "/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), '1.13' );
            } elseif( file_exists( TEMPLATEPATH . "/wp_properties-{$url_slug}.css" ) ) {
              wp_register_style( 'wp-property-frontend-' . $url_slug, get_bloginfo( 'template_url' ) . "/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), '1.13' );
            } elseif( file_exists( WPP_Templates . "/wp_properties-{$url_slug}.css" ) && $wp_properties[ 'configuration' ][ 'autoload_css' ] == 'true' ) {
              wp_register_style( 'wp-property-frontend-' . $url_slug, WPP_URL . "templates/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), WPP_Version );
            }
            // Mark every style as conditional
            $wp_styles->add_data( 'wp-property-frontend-' . $url_slug, 'conditional', $type );
            wp_enqueue_style( 'wp-property-frontend-' . $url_slug );

          }

        }

        //** Scripts loaded only on single property pages */
        if( $wp_query->single_property_page && !post_password_required( $post ) ) {

          Utility::console_log( 'Including scripts for all single property pages.' );

          Utility::load_assets( array( 'single' ) );

          do_action( 'template_redirect_single_property' );

          add_action( 'wp_head', create_function( '', "do_action('wp_head_single_property'); " ) );

          $property = Utility::get_property( $post->ID, "load_gallery=true" );

          $property = prepare_property_for_display( $property );

          $type = $property[ 'property_type' ];

          //** Make certain variables available to be used within the single listing page */
          $single_page_vars = apply_filters( 'wpp_property_page_vars', array(
            'property'      => $property,
            'wp_properties' => $wp_properties
          ) );

          //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
          if( is_array( $single_page_vars ) ) {
            $wp_query->query_vars = array_merge( $wp_query->query_vars, $single_page_vars );
          }

          $template_found = Utility::get_template_part( array(
            "property-{$type}",
            "property",
          ), array( WPP_Templates ) );

          //** Load the first found template */
          if( $template_found ) {
            Utility::console_log( 'Found single property page template:' . $template_found );
            load_template( $template_found );
            die();
          }

        }

        //** Current requests includes a property overview.  PO may be via shortcode, search result, or due to this being the Default Dynamic Property page */
        if( $wp_query->is_property_overview ) {

          Utility::console_log( 'Including scripts for all property overview pages.' );

          if( $wp_query->wpp_default_property_page ) {
            Utility::console_log( 'Dynamic Default Property page detected, will load custom template.' );
          } else {
            Utility::console_log( 'Custom Default Property page detected, property overview content may be rendered via shortcode.' );
          }

          //** Make certain variables available to be used within the single listing page */
          $overview_page_vars = apply_filters( 'wpp_overview_page_vars', array(
            'wp_properties' => $wp_properties,
            'wpp_query'     => $wpp_query
          ) );

          //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
          if( is_array( $overview_page_vars ) ) {
            $wp_query->query_vars = array_merge( $wp_query->query_vars, $overview_page_vars );
          }

          do_action( 'template_redirect_property_overview' );

          add_action( 'wp_head', create_function( '', "do_action('wp_head_property_overview'); " ) );

          //** If using Dynamic Property Root page, we must load a template */
          if( $wp_query->wpp_default_property_page ) {

            //** Unset any post that may have been found based on query */
            $post = false;

            $template_found = Utility::get_template_part( array(
              "property-search-result",
              "property-overview-page",
            ), array( WPP_Templates ) );

            //** Load the first found template */
            if( $template_found ) {
              Utility::console_log( 'Found Default property overview page template:' . $template_found );
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

        Utility::fix_screen_options();

        // Plug page actions -> Add Settings Link to plugin overview page
        add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );

        //* Adds metabox 'General Information' to Property Edit Page */
        add_meta_box( 'wpp_property_meta', __( 'General Information', 'wpp' ), array( 'WPP_UI', 'metabox_meta' ), 'property', 'normal', 'high' );

        //* Adds 'Group' metaboxes to Property Edit Page */
        if( !empty( $wp_properties[ 'property_groups' ] ) ) {
          foreach( (array) $wp_properties[ 'property_groups' ] as $slug => $group ) {
            //* There is no sense to add metabox if no one attribute assigned to group */
            if( !in_array( $slug, $wp_properties[ 'property_stats_groups' ] ) ) {
              continue;
            }
            //* Determine if Group name is empty we add 'NO NAME', other way metabox will not be added */
            if( empty( $group[ 'name' ] ) ) {
              $group[ 'name' ] = __( 'NO NAME', 'wpp' );
            }
            add_meta_box( $slug, __( $group[ 'name' ], 'wpp' ), array( 'WPP_UI', 'metabox_meta' ), 'property', 'normal', 'high', array( 'group' => $slug ) );
          }
        }

        add_meta_box( 'propetry_filter', $wp_properties[ 'labels' ][ 'name' ] . ' ' . __( 'Search', 'wpp' ), array( 'WPP_UI', 'metabox_property_filter' ), 'property_page_all_properties', 'normal' );

        // Add Metaboxes.
        do_action( 'wpp:metaboxes', $this );

        self::manual_activation();

        // Handle Settings Download.
        if( $_REQUEST[ 'page' ] == 'property_settings' && $_REQUEST[ 'wpp_action' ] == 'download-wpp-backup' && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'download-wpp-backup' ) ) {

          $this->_settings->file_transfer(array(
            "format" => "json",
            "name" => sanitize_key( get_bloginfo( 'name' ) ) . '-wp-property',
            "charset" => get_option( 'blog_charset' )
          ));

        }

      }

      /**
       * Register metaboxes.
       *
       * @global type $post
       * @global type $wpdb
       */
      public function add_meta_boxes() {
        global $post, $wpdb;

        //** Add metabox for child properties */
        if( $post->post_type == 'property' && $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_parent = '{$post->ID}' AND post_status = 'publish' " ) ) {
          add_meta_box( 'wpp_property_children', sprintf( __( 'Child %1s', 'wpp' ), Utility::property_label( 'plural' ) ), array( 'WPP_UI', 'child_properties' ), 'property', 'side', 'high' );
        }
      }

      /**
       * Check if WP-Property RaaS Active
       *
       * @return bool
       */
      public function is_active() {
        return true;
      }

      /**
       * Check for premium features and load them
       *
       * @updated 1.6
       * @since 0.624
       *
       */
      public function load_premium() {
        global $wp_properties;

        $default_headers = array(
          'Name'                 => __( 'Name', 'wpp' ),
          'Version'              => __( 'Version', 'wpp' ),
          'Description'          => __( 'Description', 'wpp' ),
          'Minimum Core Version' => __( 'Minimum Core Version', 'wpp' )
        );

        if( !is_dir( WPP_Premium ) )
          return;

        if( $premium_dir = opendir( WPP_Premium ) ) {

          if( file_exists( WPP_Premium . "/index.php" ) ) {
            @include_once( WPP_Premium . "/index.php" );
          }

          while( false !== ( $file = readdir( $premium_dir ) ) ) {

            if( $file == 'index.php' )
              continue;

            if( end( @explode( ".", $file ) ) == 'php' ) {

              $plugin_slug = str_replace( array( '.php' ), '', $file );

              //** Admin tools premium feature was moved to core. So it must not be loaded twice. */
              if( $plugin_slug == 'class_admin_tools' ) {
                continue;
              }

              $plugin_data                                                            = @get_file_data( WPP_Premium . "/" . $file, $default_headers, 'plugin' );
              $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'name' ]        = $plugin_data[ 'Name' ];
              $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'version' ]     = $plugin_data[ 'Version' ];
              $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'description' ] = $plugin_data[ 'Description' ];

              if( $plugin_data[ 'Minimum Core Version' ] ) {
                $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum_wpp_version' ] = $plugin_data[ 'Minimum Core Version' ];
              }

              //** If feature has a Minimum Core Version and it is more than current version - we do not load **/
              $feature_requires_upgrade = ( !empty( $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum_wpp_version' ] ) && ( version_compare( WPP_Version, $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum_wpp_version' ] ) < 0 ) ? true : false );

              if( $feature_requires_upgrade ) {

                //** Disable feature if it requires a higher WPP version**/

                $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ]                 = 'true';
                $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_higher_wpp_version' ] = 'true';

              } elseif( !isset( $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] ) || $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] != 'true' ) {

                //** Load feature, everything is good**/

                $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_higher_wpp_version' ] = 'false';

                if( WP_DEBUG == true ) {
                  include_once( WPP_Premium . "/" . $file );
                } else {
                  @include_once( WPP_Premium . "/" . $file );
                }

                // Disable plugin if class does not exists - file is empty
                if( !class_exists( $plugin_slug ) ) {
                  unset( $wp_properties[ 'installed_features' ][ $plugin_slug ] );
                }

                $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] = 'false';
              } else {
                //* This happens when feature cannot be loaded and is disabled */

                //** We unset requires core upgrade in case feature was update while being disabled */
                $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_higher_wpp_version' ] = 'false';

              }

            }

          }
        }

      }

      /**
       * Check if premium feature is installed or not
       *
       * @param string $slug . Slug of premium feature
       *
       * @return boolean.
       */
      public function check_premium( $slug ) {
        global $wp_properties;

        if( empty( $wp_properties[ 'installed_features' ][ $slug ][ 'version' ] ) ) {
          return false;
        }

        $file = WPP_Premium . "/" . $slug . ".php";

        $default_headers = array(
          'Name'        => 'Name',
          'Version'     => 'Version',
          'Description' => 'Description'
        );

        $plugin_data = @get_file_data( $file, $default_headers, 'plugin' );

        if( !is_array( $plugin_data ) || empty( $plugin_data[ 'Version' ] ) ) {
          return false;
        }

        return true;
      }

      /**
       * Checks updates for premium features by AJAX
       * Prints results to body.
       *
       * @global array $wp_properties
       * @return null
       */
      public function check_plugin_updates() {
        global $wp_properties;

        $result = Utility::feature_check();

        if( is_wp_error( $result ) ) {
          printf( __( 'An error occurred during premium feature check: <b> %s </b>.', 'wpp' ), $result->get_error_message() );
        } else {
          echo $result;
        }

        return null;
      }

      /**
       * Run manually when a version mismatch is detected.
       *
       * Holds official current version designation.
       * Called in admin_init hook.
       *
       * @since 1.10
       * @version 1.13
       *
       */
      public function manual_activation() {

        $installed_ver = get_option( "wpp_version", 0 );
        $wpp_version   = WPP_Version;

        if( @version_compare( $installed_ver, $wpp_version ) == '-1' ) {
          // We are upgrading.

          // Unschedule event
          $timestamp = wp_next_scheduled( 'wpp_premium_feature_check' );
          wp_unschedule_event( $timestamp, 'wpp_premium_feature_check' );
          wp_clear_scheduled_hook( 'wpp_premium_feature_check' );

          // Schedule event
          wp_schedule_event( time(), 'daily', 'wpp_premium_feature_check' );

          //** Upgrade data if needed */
          self::upgrade();

          // Update option to latest version so this isn't run on next admin page load
          update_option( "wpp_version", $wpp_version );

          // Get premium features on activation
          @Utility::feature_check();

        }

        return;

      }

      /**
       * Moved from WPP_Legacy
       *
       */
      public function upgrade() {
        global $wpdb;

        $installed_ver = get_option( "wpp_version", 0 );
        $wpp_version   = WPP_Version;

        if( @version_compare( $installed_ver, WPP_Version ) == '-1' ) {

          switch( $installed_ver ) {

            /**
             * Upgrade:
             * - WPP postmeta data were saved to database with '&ndash;' instead of '-' in value. Function encode_sql_input was modified and it doesn't change '-' to '&ndash' anymore
             * So to prevent search result issues we need to update database data.
             * peshkov@UD
             */
            case ( version_compare( $installed_ver, '1.37.4' ) == '-1' ):

              $wpdb->query( "UPDATE {$wpdb->prefix}postmeta SET meta_value = REPLACE( meta_value, '&ndash;', '-')" );

              break;

          }

        }

      }

      /**
       * Adds "Settings" link to the plugin overview page
       *
       *  *
       * @since 0.60
       *
       */
      public function plugin_action_links( $links, $file ) {

        if( $file == 'wp-property/wp-property.php' ) {
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

        wp_localize_script( 'wpp-localization', 'wpp', array( 'instance' => $this->locale_instance() ) );

        switch( $current_screen->id ) {

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
            $thumbnail_attribs = Utility::image_sizes( $wp_properties[ 'configuration' ][ 'admin_ui' ][ 'overview_table_thumbnail_size' ] );
            $thumbnail_width   = ( !empty( $thumbnail_attribs[ 'width' ] ) ? $thumbnail_attribs[ 'width' ] : false );
            if( $thumbnail_width ) {
              ?>
              <style typ="text/css">
            #wp-list-table.wp-list-table .column-thumbnail {
              width: <?php echo $thumbnail_width + 20; ?>px;
            }

            #wp-list-table.wp-list-table td.column-thumbnail {
              text-align: right;
            }

            #wp-list-table.wp-list-table .column-type {
              width: 90px;
            }

            #wp-list-table.wp-list-table .column-menu_order {
              width: 50px;
            }

            #wp-list-table.wp-list-table td.column-menu_order {
              text-align: center;
            }

            #wp-list-table.wp-list-table .column-featured {
              width: 100px;
            }

            #wp-list-table.wp-list-table .check-column {
              width: 26px;
            }
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
            wp_enqueue_script( 'wpp.admin.settings' );
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
        if( file_exists( WPP_Path . "/styles/{$current_screen->id}.css" ) ) {
          wp_enqueue_style( $current_screen->id . '-style', WPP_URL . "/styles/{$current_screen->id}.css", array(), WPP_Version, 'screen' );
        }

        //** Automatically insert JS sheet if one exists with $current_screen->ID name */
        if( file_exists( WPP_Path . "scripts/{$current_screen->id}.js" ) ) {
          wp_enqueue_script( $current_screen->id . '-js', WPP_URL . "scripts/{$current_screen->id}.js", array( 'jquery' ), WPP_Version, 'wp-property-backend-global' );
        }

        // Enqueue Admin CSS on all backend pages.
        if( file_exists( WPP_Path . 'styles/wpp-admin.css' ) ) {
          wp_enqueue_style( 'wpp-admin', WPP_URL . 'styles/wpp-admin.css' );
        }

      }

      /**
       * Sets up additional pages and loads their scripts
       *
       * @since 0.5
       *
       */
      public function admin_menu() {
        global $wp_properties, $submenu;

        // Dashboard Page.
        // $dashboard_page   = add_submenu_page( 'edit.php?post_type=property', __( 'Dashboard', 'wpp' ), __( 'Dashboard', 'wpp' ), 'manage_wpp_dashboard', 'dashboard', create_function( '', 'global $wp_properties; include "ui/page-dashboard.php";' ) );

        // Modules Page.
        $modules_page   = add_submenu_page( 'edit.php?post_type=property', __( 'Modules', 'wpp' ), __( 'Modules', 'wpp' ), 'manage_wpp_modules', 'modules', create_function( '', 'global $wp_properties; include "ui/page-modules.php";' ) );

        // Settings Page.
        $settings_page  = add_submenu_page( 'edit.php?post_type=property', __( 'Settings', 'wpp' ), __( 'Settings', 'wpp' ), 'manage_wpp_settings', 'property_settings', create_function( '', 'global $wp_properties; include "ui/page-settings.php";' ) );

        // All Properties Overview Page.
        $all_properties = add_submenu_page( 'edit.php?post_type=property', $wp_properties[ 'labels' ][ 'all_items' ], $wp_properties[ 'labels' ][ 'all_items' ], 'edit_wpp_properties', 'all_properties', create_function( '', 'global $wp_properties, $screen_layout_columns; include "ui/page-properties.php";' ) );

        /**
         * Next used to add custom submenu page 'All Properties' with Javascript dataTable
         *
         * @author korotkov@UD
         */
        if( !empty( $submenu[ 'edit.php?post_type=property' ] ) ) {

          //** Comment next line if you want to get back old Property list page. */
          array_shift( $submenu[ 'edit.php?post_type=property' ] );

          foreach( (array) $submenu[ 'edit.php?post_type=property' ] as $key => $page ) {
            if( $page[ 2 ] == 'all_properties' ) {
              unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
              array_unshift( $submenu[ 'edit.php?post_type=property' ], $page );
            } elseif( $page[ 2 ] == 'post-new.php?post_type=property' ) {
              //** Removes 'Add Property' from menu if user can not edit properties. peshkov@UD */
              if( !current_user_can( 'edit_wpp_property' ) ) {
                unset( $submenu[ 'edit.php?post_type=property' ][ $key ] );
              }
            }
          }
        }

        do_action( 'wpp_admin_menu', $this );
        do_action( 'wpp:admin_menu', $this );

        // Load jQuery UI Tabs and Cookie into settings page (settings_page_property_settings)
        add_action( 'admin_print_scripts-' . $settings_page, create_function( '', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');" ) );
        add_action( 'admin_print_scripts-' . $modules_page, create_function( '', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');" ) );
        add_action( 'admin_print_scripts-' . $all_properties, create_function( '', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');" ) );

      }

      /**
       * Modify admin body class on property pages for CSS
       *
       * @todo $current_screen does not seem to work in 3.8.
       *
       * @since 0.5
       */
      public function admin_body_class( $admin_body_class ) {
        global $current_screen;

        $classes = explode( ' ', trim( $admin_body_class ) );

        $classes[ ] = self::is_active() ? 'wpp-connected' : 'wpp-disconnected';

        if( $current_screen->id == 'edit-property' ) {
          $classes[ ] = 'wpp_property_edit';
        }

        if( $current_screen->id == 'property' ) {
          $classes[ ] = 'wpp_property_edit';
        }

        return implode( ' ', array_unique( $classes ) );

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
      public function parse_request( $query ) {
        global $wp, $wp_query, $wp_properties, $wpdb;

        //** If we don't have permalinks, our base slug is always default */
        if( get_option( 'permalink_structure' ) == '' ) {
          $wp_properties[ 'configuration' ][ 'base_slug' ] = 'property';
        }

        //** If we are displaying search results, we can assume this is the default property page */
        if( is_array( $_REQUEST[ 'wpp_search' ] ) ) {

          if( isset( $_POST[ 'wpp_search' ] ) ) {
            $query = '?' . http_build_query( array( 'wpp_search' => $_REQUEST[ 'wpp_search' ] ), '', '&' );
            wp_redirect( Utility::base_url( $wp_properties[ 'configuration' ][ 'base_slug' ] ) . $query );
            die();
          }

          $wp_query->wpp_root_property_page = true;
          $wp_query->wpp_search_page        = true;
        }

        //** Determine if this is the Default Property Page */

        if( isset( $wp_properties[ 'configuration' ][ 'base_slug' ] ) && $wp->request == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( !empty( $wp_properties[ 'configuration' ][ 'base_slug' ] ) && $wp->query_string == "p=" . $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( isset( $query->query_vars[ 'name' ] ) && $query->query_vars[ 'name' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( isset( $query->query_vars[ 'pagename' ] ) && $query->query_vars[ 'pagename' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        if( isset( $query->query_vars[ 'category_name' ] ) && $query->query_vars[ 'category_name' ] == $wp_properties[ 'configuration' ][ 'base_slug' ] ) {
          $wp_query->wpp_root_property_page = true;
        }

        //** If this is a the root property page, and the Dynamic Default Property page is used */
        if( $wp_query->wpp_root_property_page && $wp_properties[ 'configuration' ][ 'base_slug' ] == 'property' ) {
          $wp_query->wpp_default_property_page = true;

          Utility::console_log( 'Overriding default 404 page status.' );

          /** Set to override the 404 status */
          add_action( 'wp', create_function( '', 'status_header( 200 );' ) );

          //** Prevent is_404() in template files from returning true */
          add_action( 'template_redirect', create_function( '', ' global $wp_query; $wp_query->is_404 = false;' ), 0, 10 );
        }

        if( $wp_query->wpp_search_page ) {
          $wpp_pages[ ] = 'Search Page';
        }

        if( $wp_query->wpp_default_property_page ) {
          $wpp_pages[ ] = 'Default Property Page';
        }

        if( $wp_query->wpp_root_property_page ) {
          $wpp_pages[ ] = 'Root Property Page.';
        }

        if( is_array( $wpp_pages ) ) {
          Utility::console_log( 'Utility::parse_request() ran, determined that request is for: ' . implode( ', ', $wpp_pages ) );
        }

      }

      /**
       * Modifies post content
       *
       * @since 1.04
       *
       */
      public function the_content( $content ) {
        global $post, $wp_properties, $wp_query;

        if( !isset( $wp_query->is_property_overview ) ) {
          return $content;
        }

        //** Handle automatic PO inserting for non-search root page */
        if( !$wp_query->wpp_search_page && $wp_query->wpp_root_property_page && $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) {
          Utility::console_log( 'Automatically inserted property overview shortcode into page content.' );

          return self::shortcode_property_overview();
        }

        //** Handle automatic PO inserting for search pages */
        if( $wp_query->wpp_search_page && $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] != 'true' ) {
          Utility::console_log( 'Automatically inserted property overview shortcode into search page content.' );

          return self::shortcode_property_overview();
        }

        return $content;
      }

      /**
       * Hooks into save_post function and saves additional property data
       *
       *
       * @todo Add some sort of custom capability so not only admins can make properties as featured. i.e. Agents can make their own properties featured.
       * @since 1.04
       */
      public function save_property( $post_id ) {
        global $wp_properties, $wp_version;

        $_wpnonce = ( version_compare( $wp_version, '3.5', '>=' ) ? 'update-post_' : 'update-property_' ) . $post_id;

        if( !wp_verify_nonce( $_POST[ '_wpnonce' ], $_wpnonce ) || $_POST[ 'post_type' ] !== 'property' ) {
          return $post_id;
        }

        //* Delete cache files of search values for search widget's form */
        $directory = WPP_Path . 'cache/searchwidget';

        if( is_dir( $directory ) ) {
          $dir = opendir( $directory );
          while( ( $cachefile = readdir( $dir ) ) ) {
            if( is_file( $directory . "/" . $cachefile ) ) {
              unlink( $directory . "/" . $cachefile );
            }
          }
        }

        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
          return $post_id;
        }

        $update_data = $_REQUEST[ 'wpp_data' ][ 'meta' ];

        //** Neccessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
        if( empty( $_REQUEST[ 'exclude_from_supermap' ] ) ) {
          if( !metadata_exists( 'post', $post_id, 'exclude_from_supermap' ) ) {
            $update_data[ 'exclude_from_supermap' ] = 'false';
          }
        }

        if( (float) $update_data[ 'latitude' ] == 0 ) $update_data[ 'latitude' ] = '';
        if( (float) $update_data[ 'longitude' ] == 0 ) $update_data[ 'longitude' ] = '';

        /* get old coordinates and location */
        $old_lat  = get_post_meta( $post_id, 'latitude', true );
        $old_lng  = get_post_meta( $post_id, 'longitude', true );
        $geo_data = array(
          'old_coordinates' => ( ( empty( $old_lat ) ) || ( empty( $old_lng ) ) ) ? "" : array( 'lat' => $old_lat, 'lng' => $old_lng ),
          'old_location'    => ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ) ? get_post_meta( $post_id, $wp_properties[ 'configuration' ][ 'address_attribute' ], true ) : ''
        );

        foreach( (array) $update_data as $meta_key => $meta_value ) {
          $attribute_data = Utility::get_attribute_data( $meta_key );

          //* Cleans the user input */
          $meta_value = Utility::encode_mysql_input( $meta_value, $meta_key );

          //* Only admins can mark properties as featured. */
          if( $meta_key == 'featured' && !current_user_can( 'manage_options' ) ) {
            //** But be sure that meta 'featured' exists at all */
            if( !metadata_exists( 'post', $post_id, $meta_key ) ) {
              $meta_value = 'false';
            } else {
              continue;
            }
          }

          //* Remove certain characters */

          if( $attribute_data[ 'currency' ] || $attribute_data[ 'numeric' ] ) {
            $meta_value = str_replace( array( "$", "," ), '', $meta_value );
          }

          //* Overwrite old post meta allowing only one value */
          delete_post_meta( $post_id, $meta_key );
          add_post_meta( $post_id, $meta_key, $meta_value );
        }

        //* Check if property has children */
        $children = get_children( "post_parent=$post_id&post_type=property" );

        //* Write any data to children properties that are supposed to inherit things */
        if( count( $children ) > 0 ) {
          foreach( (array) $children as $child_id => $child_data ) {
            //* Determine child property_type */
            $child_property_type = get_post_meta( $child_id, 'property_type', true );
            //* Check if child's property type has inheritence rules, and if meta_key exists in inheritance array */
            if( is_array( $wp_properties[ 'property_inheritance' ][ $child_property_type ] ) ) {
              foreach( (array) $wp_properties[ 'property_inheritance' ][ $child_property_type ] as $i_meta_key ) {
                $parent_meta_value = get_post_meta( $post_id, $i_meta_key, true );
                //* inheritance rule exists for this property_type for this meta_key */
                update_post_meta( $child_id, $i_meta_key, $parent_meta_value );
              }
            }
          }
        }

        Utility::maybe_set_gpid( $post_id );

        if( isset( $_REQUEST[ 'parent_id' ] ) ) {
          $_REQUEST[ 'parent_id' ] = Utility::update_parent_id( $_REQUEST[ 'parent_id' ], $post_id );
        }

        Utility::revalidate_address($post_id);

        do_action( 'wpp:save_property', $post_id, $this );

        return true;
      }

      /**
       * Inserts content into the "Publish" metabox on property pages
       *
       * @since 1.04
       *
       */
      public function post_submitbox_misc_actions() {
        global $post, $wp_properties;

        if( $post->post_type == 'property' ) {

          ?>
          <div class="misc-pub-section ">

        <ul>
          <li><?php _e( 'Menu Sort Order:', 'wpp' ) ?> <?php echo Utility::input( "name=menu_order&special=size=4", $post->menu_order ); ?></li>

          <?php if( current_user_can( 'manage_options' ) && $wp_properties[ 'configuration' ][ 'do_not_use' ][ 'featured' ] != 'true' ) { ?>
            <li><?php echo Utility::checkbox( "name=wpp_data[meta][featured]&label=" . __( 'Display in featured listings.', 'wpp' ), get_post_meta( $post->ID, 'featured', true ) ); ?></li>
          <?php } ?>

          <?php do_action( 'wpp_publish_box_options' ); ?>
        </ul>

      </div>
        <?php

        }

        return;

      }

      /**
       * Removes "quick edit" link on property type objects
       *
       * Called in via page_row_actions filter
       *
       * @since 0.5
       *
       */
      public function property_row_actions( $actions, $post ) {

        if( $post->post_type != 'property' )
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
      public function property_updated_messages( $messages ) {
        global $post_id, $post;

        $messages[ 'property' ] = array(
          0  => '', // Unused. Messages start at index 1.
          1  => sprintf( __( '%2s updated. <a href="%s">view %1s</a>', 'wpp' ), Utility::property_label( 'singular' ), esc_url( get_permalink( $post_id ) ), Utility::property_label( 'singular' ) ),
          2  => __( 'Custom field updated.', 'wpp' ),
          3  => __( 'Custom field deleted.', 'wpp' ),
          4  => sprintf( __( '%1s updated.', 'wpp' ), Utility::property_label( 'singular' ) ),
          /* translators: %s: date and time of the revision */
          5  => isset( $_GET[ 'revision' ] ) ? sprintf( __( '%1s restored to revision from %s', 'wpp' ), Utility::property_label( 'singular' ), wp_post_revision_title( (int) $_GET[ 'revision' ], false ) ) : false,
          6  => sprintf( __( '%1s published. <a href="%s">View %2s</a>', 'wpp' ), Utility::property_label( 'singular' ), esc_url( get_permalink( $post_id ) ), Utility::property_label( 'singular' ) ),
          7  => sprintf( __( '%1s saved.', 'wpp' ), Utility::property_label( 'singular' ) ),
          8  => sprintf( __( '%1s submitted. <a target="_blank" href="%s">Preview %2s</a>', 'wpp' ), Utility::property_label( 'singular' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), Utility::property_label( 'singular' ) ),
          9  => sprintf( __( '%1s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %2s</a>', 'wpp' ),
            // translators: Publish box date format, see http://php.net/date
            Utility::property_label( 'singular' ),
            date_i18n( __( 'M j, Y @ G:i', 'wpp' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ), Utility::property_label( 'singular' ) ),
          10 => sprintf( __( '%1s draft updated. <a target="_blank" href="%s">Preview %2s</a>', 'wpp' ), Utility::property_label( 'singular' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), Utility::property_label( 'singular' ) ),
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
      public function edit_columns( $columns ) {
        global $wp_properties;

        unset( $columns );

        $columns[ 'cb' ]            = "<input type=\"checkbox\" />";
        $columns[ 'title' ]         = __( 'Title', 'wpp' );
        $columns[ 'property_type' ] = __( 'Type', 'wpp' );

        if( is_array( $wp_properties[ 'property_stats' ] ) ) {
          foreach( (array) $wp_properties[ 'property_stats' ] as $slug => $title )
            $columns[ $slug ] = $title;
        } else {
          $columns = $columns;
        }

        $columns[ 'city' ]       = __( 'City', 'wpp' );
        $columns[ 'overview' ]   = __( 'Overview', 'wpp' );
        $columns[ 'featured' ]   = __( 'Featured', 'wpp' );
        $columns[ 'menu_order' ] = __( 'Order', 'wpp' );
        $columns[ 'thumbnail' ]  = __( 'Thumbnail', 'wpp' );

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
      public function sortable_columns( $columns ) {
        global $wp_properties;

        $columns[ 'type' ]     = 'type';
        $columns[ 'featured' ] = 'featured';

        if( is_array( $wp_properties[ 'property_stats' ] ) ) {
          foreach( (array) $wp_properties[ 'property_stats' ] as $slug => $title )
            $columns[ $slug ] = $slug;
        }

        $columns = apply_filters( 'wpp_admin_sortable_columns', $columns );

        return $columns;
      }

      /**
       * Adds wp-property-listing class in search results and property_overview pages
       *
       * @since 0.7260
       */
      public function properties_body_class( $classes ) {
        global $post, $wp_properties;

        if( strpos( $post->post_content, "property_overview" ) || ( is_search() && isset( $_REQUEST[ 'wpp_search' ] ) ) || ( $wp_properties[ 'configuration' ][ 'base_slug' ] == $post->post_name ) ) {
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
      public function check_wp_settings_data( $wpp_settings, $wp_properties ) {
        if( is_array( $wpp_settings ) && is_array( $wp_properties ) ) {
          foreach( (array) $wp_properties as $key => $value ) {
            if( !isset( $wpp_settings[ $key ] ) ) {
              switch( $key ) {
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
      public function current_screen( $screen ) {

        // property_page_all_properties
        // property_page_property_settings
        // property_page_features

        switch( $screen->id ) {
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
      public function set_capabilities() {
        global $wpp_capabilities;

        //* Get Administrator role for adding custom capabilities */
        $role =& get_role( 'administrator' );

        //* General WPP capabilities */
        $wpp_capabilities = array(

          //* Manage WPP Properties Capabilities */
          'edit_wpp_properties'        => sprintf( __( 'View %1s', 'wpp' ), Utility::property_label( 'plural' ) ),
          'edit_wpp_property'          => sprintf( __( 'Add/Edit %1s', 'wpp' ), Utility::property_label( 'plural' ) ),
          'edit_others_wpp_properties' => sprintf( __( 'Edit Other %1s', 'wpp' ), Utility::property_label( 'plural' ) ),
          //'read_wpp_property' => __( 'Read Property', 'wpp' ),
          'delete_wpp_property'        => sprintf( __( 'Delete %1s', 'wpp' ), Utility::property_label( 'plural' ) ),
          'publish_wpp_properties'     => sprintf( __( 'Publish %1s', 'wpp' ), Utility::property_label( 'plural' ) ),
          //'read_private_wpp_properties' => __( 'Read Private Properties', 'wpp' ),

          //* WPP Settings capability */
          'manage_wpp_settings'        => __( 'Manage Settings', 'wpp' ),
          'manage_wpp_modules'         => __( 'Manage Features', 'wpp' ),

          //* WPP Taxonomies capability */
          'manage_wpp_categories'      => __( 'Manage Taxonomies', 'wpp' )
        );

        //* Adds Premium Feature Capabilities */
        $wpp_capabilities = apply_filters( 'wpp_capabilities', $wpp_capabilities );

        if( !is_object( $role ) ) {
          return;
        }

        foreach( (array) $wpp_capabilities as $cap => $value ) {
          if( empty( $role->capabilities[ $cap ] ) ) {
            $role->add_cap( $cap );
          }
        }
      }

      /**
       * Generates javascript file with localization.
       * Adds localization support to all WP-Property scripts.
       * Accessible via wp-ajax.php calls.
       *
       * @since 1.37.3.2
       * @author peshkov@UD
       */
      public function localize_scripts() {

        $l10n = array();

        //** Include the list of translations */
        include_once WPP_Path . 'l10n.php';

        /** All additional localizations must be added using the filter below. */
        $l10n = apply_filters( 'wpp::js::localization', $l10n );

        foreach( (array) $l10n as $key => $value ) {
          if( !is_scalar( $value ) ) {
            continue;
          }
          $l10n[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
        }

        header( 'Content-type: application/x-javascript' );

        die( "var wpp = ( typeof wpp === 'object' ) ? wpp : {}; wpp.strings = " . json_encode( $l10n ) . ';' );

      }

      /**
       * WPP Contextual Help
       *
       * @global $current_screen
       *
       * @param  $args
       *
       * @author korotkov@ud
       */
      public function wpp_contextual_help( $args = array() ) {
        global $contextual_help;

        $args = Utility::parse_args( $args, array(
          'contextual_help' => array()
        ));

        get_current_screen()->add_help_tab(array(
          'content'  => '<p>' . __( 'Please upgrade Wordpress to the latest version for detailed help.', 'wpp' ) . '</p><p>' . __( 'Or visit <a href="https://usabilitydynamics.com/tutorials/wp-property-help/" target="_blank">WP-Property Help Page</a> on UsabilityDynamics.com', 'wpp' ) . '</p>'
        ));

      }

      /**
       * Returns specific instance data which is used by javascript
       * Javascript Reference: window.wpp.instance
       *
       * @author peshkov@UD
       * @since 1.38
       * @return array
       */
      public function locale_instance() {
        global $wp_properties;

        $data = array(
          'request'        => $_REQUEST,
          'get'            => $_GET,
          'post'           => $_POST,
          'iframe_enabled' => false,
          'ajax_url'       => admin_url( 'admin-ajax.php' ),
          'home_url'       => home_url(),
          'user_logged_in' => is_user_logged_in() ? 'true' : 'false',
          'settings'       => $wp_properties,
        );

        if( isset( $data[ 'request' ][ 'wp_customize' ] ) && $data[ 'request' ][ 'wp_customize' ] == 'on' ) {
          $data[ 'iframe_enabled' ] = true;
        }

        return apply_filters( 'wpp::get_instance', $data );

      }

      /**
       * Get Setting.
       *
       *    // Get Setting
       *    $wpp::get( 'my_key' )
       *
       * @method get
       *
       * @for Bootstrap
       * @author potanin@UD
       * @since 0.1.1
       */
      public function get( $key, $default = null ) {
        return self::$instance->_settings ? self::$instance->_settings->get( $key, $default ) : null;
      }

      /**
       * Set Setting.
       *
       * @usage
       *
       *    // Set Setting
       *    $wpp::set( 'my_key', 'my-value' )
       *
       * @method get
       * @for Bootstrap
       *
       * @author potanin@UD
       * @since 0.1.1
       */
      public function set( $key, $value = null ) {
        return self::$instance->_settings ? self::$instance->_settings->set( $key, $value ) : null;
      }

      /**
       * Get the WPP Singleton
       *
       * Concept based on the CodeIgniter get_instance() concept.
       *
       * @example
       *
       *      var settings = WPP::get_instance()->Settings;
       *      var api = WPP::$instance()->API;
       *
       * @static
       * @return object
       *
       * @method get_instance
       * @for WPP
       */
      public function &get_instance() {
        return self::$instance;
      }

    }

  }

}
