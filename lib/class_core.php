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
  function __construct() {
    global $wp_properties;

    // Determine if memory limit is low and increase it
    if( (int)ini_get( 'memory_limit' ) < 128 && (int)ini_get( 'memory_limit' ) > 0 ) {
      ini_set( 'memory_limit', '128M' );
    }

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

    add_action( 'template_include', array( $this, 'template_include' ) );

    //** Pre-init action hook */
    do_action( 'wpp_pre_init' );

    // Check settings data on accord with existing wp_properties data before option updates
    add_filter( 'wpp_settings_save', array( $this, 'check_wp_settings_data' ), 0, 2 );

    /**
     * May be provide Google Maps API key on address validation request.
     *
     * @since 2.0.5
     */
    add_filter( 'wpp:geocoding_request', function( $args ){
      $key = ud_get_wp_property( 'configuration.google_maps_api_server' );
      if( !empty( $key ) ) {
        $args[ 'key' ] = $key;
      }
      return $args;
    } );

    /**
     * May be return ID of default image for property.
     * We are doing it only for Front End! peshkov@UD
     */
    add_filter( 'get_post_metadata', array( $this, 'maybe_get_thumbnail_id' ), 10, 4 );

    /**
     * Updated remote settings to UD
     */

    add_filter( 'wpp_get_properties_query', array( $this, 'fix_tax_property_query' ));


    /**
     * Heartbeat filter for locking settings page.
     * 
     */

    if(WPP_FEATURE_FLAG_SETTINGS_V2){
      add_filter( 'heartbeat_received', array($this, 'wpp_settings_lock_heartbeat_received'), 10, 3 );
      add_filter( 'heartbeat_send', array($this, 'wpp_settings_lock_heartbeat_send'), 10, 2 );
    }
  }

  /**
   * @param $query
   * @return mixed
   */
  public function fix_tax_property_query( $query ) {
    global $wp_query;

    if ( !is_tax() || empty( $wp_query->queried_object )
        || !is_a( $wp_query->queried_object, 'WP_Term' )
        || !is_object_in_taxonomy( 'property', $wp_query->queried_object->taxonomy )
        || !function_exists( 'ud_get_wpp_terms' )
        || !empty( $query[$wp_query->queried_object->taxonomy] )) return $query;

    $query[$wp_query->queried_object->taxonomy] = $wp_query->queried_object->slug;

    return $query;
  }

  /**
   * Update remote settings
   *
   * @depreciated
   *
   */
  public function update_site_settings() {
    global $wpdb, $wp_properties;

    $ud_site_secret_token = get_site_option('ud_site_secret_token');
    if(is_multisite()){
      $site_url = network_site_url();
    } else{
      $site_url = get_site_url();
    }
    $ud_site_id = get_site_option('ud_site_id');
    $ud_site_public_key = get_site_option('ud_site_public_key');
    $url = 'https://api.usabilitydynamics.com/product/property/settings/v1/update';
    $find = array( 'http://', 'https://' );
    $replace = '';
    $output = str_replace( $find, $replace, $site_url );

    $configuration = array(
        'searchable_attributes' => !empty( $wp_properties['searchable_attributes'] ) ? $wp_properties['searchable_attributes'] : array(),
        'property_stats' => !empty( $wp_properties['property_stats'] ) ? $wp_properties['property_stats'] : array(),
        'property_types' => !empty( $wp_properties['property_types'] ) ? $wp_properties['property_types'] : array(),
        'geo_type_attributes' => !empty( $wp_properties['geo_type_attributes'] ) ? $wp_properties['geo_type_attributes'] : array(),
        'predefined_values' => !empty( $wp_properties['predefined_values'] ) ? $wp_properties['predefined_values'] : array(),
        'searchable_attr_fields' => !empty( $wp_properties['searchable_attr_fields'] ) ? $wp_properties['searchable_attr_fields'] : array(),
        'admin_attr_fields' => !empty( $wp_properties['admin_attr_fields'] ) ? $wp_properties['admin_attr_fields'] : array(),
        'numeric_attributes' => !empty( $wp_properties['numeric_attributes'] ) ? $wp_properties['numeric_attributes'] : array(),
        'currency_attributes' => !empty( $wp_properties['currency_attributes'] ) ? $wp_properties['currency_attributes'] : array()
    );

    $data_settings = apply_filters( 'wpp::backup::data', array( 'wpp_settings' => $configuration ) );
    $data_settings_json = json_encode($data_settings);

    $args = array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'headers' => array(),
        'body' => array(
            'host' => $output,
            'ud_site_secret_token' => $ud_site_secret_token,
            'ud_site_id' => $ud_site_id,
	          'ud_site_public_key' => $ud_site_public_key,
            'db_name' => DB_NAME,
            'home_url' => $site_url,
            'table_refix' => $wpdb->prefix,
            'wpp_settings' => $data_settings_json,
            'message' => "Hello, I'm WP-property plugin. Give me ID, please."
        ),
    );

    $response = wp_remote_post( $url, $args );

    $api_body = json_decode(wp_remote_retrieve_body($response));

    if ( !is_wp_error( $response ) ) {
      if( !empty( $api_body->ud_site_id ) && $api_body->ud_site_secret_token == $ud_site_secret_token){
        add_site_option('ud_site_id', $api_body->ud_site_id);
        if( !empty( $api_body->ud_site_public_key ) ){
          add_site_option('ud_site_public_key', $api_body->ud_site_public_key);
        }
      }
    }
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

    //** Init action hook */
    do_action( 'wpp_init' );

    /** Making template-functions global but load after the premium features, giving the premium features priority. */
    include_once WPP_Path . 'lib/template-functions.php';

    //* set WPP capabilities */
    $this->set_capabilities();

    //** Set up our custom object and taxonomyies */
    WPP_F::register_post_type_and_taxonomies();

    //** Load settings into $wp_properties and save settings if nonce exists */
    WPP_F::settings_action();

    //** Set up our default page for properties */
    WPP_F::register_properties_page();

    //** Load all widgets and register widget areas */
    add_action( 'widgets_init', array( 'WPP_F', 'widgets_init' ) );

    do_action( 'wpp_init:end', $this );

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
    add_action( 'wp_ajax_wpp_ajax_max_set_property_type', function() {
      die(WPP_F::mass_set_property_type($_REQUEST["property_type"]));
    } );
    add_action( 'wp_ajax_wpp_ajax_image_query', function() {
      $class = WPP_F::get_property_image_data($_REQUEST["image_id"]);
      if($class) print_r($class);
      else echo __("No image found.","wpp"); die();
    } );
    add_action( 'wp_ajax_wpp_ajax_revalidate_all_addresses', function() {
      echo WPP_F::revalidate_all_addresses(); die();
    } );
    add_action( 'wp_ajax_wpp_ajax_create_settings_backup', function() {
      echo WPP_F::create_settings_backup(); die();
    } );
    add_action( 'wp_ajax_wpp_save_settings', function() {
      die(WPP_F::save_settings());
    } );
    if(WPP_FEATURE_FLAG_SETTINGS_V2){
      add_action( 'wp_ajax_wpp_get_settings_page', function() {
        die(WPP_F::wpp_ajax_get_settings_page());
      } );
    }
    add_action( 'wp_ajax_wpp_apply_default_value', function() {
      die(WPP_F::apply_default_value());
    } );
    add_action( 'wp_ajax_wpp_ajax_print_wp_properties', function() {
      global $wp_properties;
      print_r($wp_properties);
      die();
    } );
    add_action( 'wp_ajax_wpp_ajax_generate_is_remote_meta', function() {
      WPP_F::generate_is_remote_meta(); die();
    } );

    /** Called in setup_postdata().  We add property values here to make available in global $post variable on frontend */
    add_action( 'the_post', array( 'WPP_F', 'the_post' ) );

    add_action( "the_content", array( &$this, "the_content" ) );

    add_action( "post_submitbox_misc_actions", array( &$this, "post_submitbox_misc_actions" ) );
    add_action( 'save_post', array( $this, 'save_property' ), 11 );

    //** Address revalidation @since 1.37.2 @author odokienko@UD */
    add_action( 'save_property', function( $post_id ) {
      WPP_F::revalidate_address($post_id);
    } );

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

    //** Contextual Help */
    add_action( 'wpp_contextual_help', array( $this, 'wpp_contextual_help' ) );

    //** Page loading handlers */
    add_action( 'load-property_page_property_settings', array( 'WPP_F', 'property_page_property_settings_load' ) );

    //** Add custom image sizes */
    foreach( $wp_properties[ 'image_sizes' ] as $image_name => $image_sizes ) {
      add_image_size( $image_name, $image_sizes[ 'width' ], $image_sizes[ 'height' ], true );
    }

    //** Determine if we are secure */
    $scheme = ( is_ssl() && !is_admin() ? 'https' : 'http' );

    //** Load Localization early so plugins can use them as well */
    wp_register_script( 'wpp-localization', ud_get_wp_property()->path( 'static/scripts/l10n.js', 'url' ), array(), WPP_Version );
    wp_localize_script( 'wpp-localization', 'wpp_l10n', $this->get_l10n_data() );

    // wp_register_script( 'wpp-jquery-fancybox', WPP_URL . 'scripts/fancybox.2.1.5/jquery.fancybox.pack.js', array( 'jquery', 'wpp-localization' ), '2.1.5' );
    if(isset($wp_properties['configuration']['using_fancybox']) && $wp_properties['configuration']['using_fancybox'] == 'false' && !is_admin()) {
      wp_register_script('wpp-jquery-fancybox', WPP_URL . 'scripts/fancybox/jquery.fancybox-1.3.4.pack.js', array('jquery', 'wpp-localization'), '1.7.3');
    }
    wp_register_script( 'wpp-jquery-swiper', WPP_URL . 'scripts/swiper.jquery.min.js', array( 'jquery' ), '1.7.3' );

    wp_register_script( 'wpp-jquery-colorpicker', WPP_URL . 'scripts/colorpicker/colorpicker.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-select2', WPP_URL . 'scripts/select2.min.js', array( 'jquery' ));
    wp_register_script( 'wpp-jquery-easing', WPP_URL . 'scripts/fancybox/jquery.easing-1.3.pack.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );

    wp_register_script( 'wpp-jquery-ajaxupload', WPP_URL . 'scripts/fileuploader.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wp-property-admin-overview', WPP_URL . 'scripts/wpp.admin.overview.js', array( 'jquery', 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wp-property-admin-widgets', WPP_URL . 'scripts/wpp.admin.widgets.js', array( 'jquery', 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wp-property-admin-settings', WPP_URL . 'scripts/wpp.admin.settings.js', array( 'jquery', 'heartbeat', 'wpp-localization', 'backbone' ), WPP_Version );
    // _ template js
    wp_register_script( 'lodash-js', WPP_URL . 'scripts/lodash.js', array('jquery', 'underscore'), WPP_Version );
    wp_register_script( 'wpp-settings-developer-attributes', WPP_URL . 'scripts/view/settings-developer-attributes.js', array( 'wp-property-admin-settings', 'lodash-js' ), WPP_Version );
    wp_register_script( 'wpp-settings-developer-types', WPP_URL . 'scripts/view/settings-developer-types.js', array( 'wp-property-admin-settings', 'lodash-js' ), WPP_Version );
    
    $_featureFlags = array();
    $featureFlags = ud_get_wp_property()->get_feature_flags();
    foreach ($featureFlags as $flag) {
      $_featureFlags[$flag->constant] = $flag->enabled;
    }
    wp_localize_script( 'wp-property-admin-settings', 'featureFlags', $_featureFlags );

    wp_register_script( 'wp-property-backend-global', WPP_URL . 'scripts/wpp.admin.global.js', array( 'jquery', 'wp-property-global', 'wpp-localization', 'underscore' ), WPP_Version );
    wp_register_script( 'wp-property-backend-editor', WPP_URL . 'scripts/wpp.admin.editor.js', array( 'jquery', 'wp-property-global', 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wp-property-global', WPP_URL . 'scripts/wpp.global.js', array( 'jquery', 'wpp-localization', 'jquery-ui-tabs', 'jquery-ui-sortable' ), WPP_Version );
    wp_register_script( 'jquery-cookie', WPP_URL . 'scripts/jquery.smookie.js', array( 'jquery', 'wpp-localization' ), '1.7.3' );

    // Use Google Maps API Key, if provided.
    if( ud_get_wp_property( 'configuration.google_maps_api' ) ) {
      wp_register_script( 'google-maps', 'https://maps.google.com/maps/api/js?key='.ud_get_wp_property( 'configuration.google_maps_api' ) );
    } else {
      wp_register_script( 'google-maps', 'https://maps.google.com/maps/api/js?sensor=true' );
    }

    wp_register_script( 'wpp-md5', WPP_URL . 'scripts/md5.js', array( 'wpp-localization' ), WPP_Version );
    wp_register_script( 'wpp-jquery-gmaps', WPP_URL . 'scripts/jquery.ui.map.min.js', array( 'google-maps', 'jquery-ui-core', 'jquery-ui-widget', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-nivo-slider', WPP_URL . 'scripts/jquery.nivo.slider.pack.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-address', WPP_URL . 'scripts/jquery.address-1.5.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-scrollTo', WPP_URL . 'scripts/jquery.scrollTo-min.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-validate', WPP_URL . 'scripts/jquery.validate.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wpp-jquery-number-format', WPP_URL . 'scripts/jquery.number.format.js', array( 'jquery', 'wpp-localization' ) );
    wp_register_script( 'wp-property-galleria', WPP_URL . 'scripts/galleria/galleria-1.2.5.js', array( 'jquery', 'wpp-localization' ) );

    /* New script for property search shortcode */
    wp_register_script( 'wpp-search-form', WPP_URL . 'scripts/wpp.search_form.js', array( 'jquery' ) );

    /* New script for property gallery */
    wp_register_script( 'wp-property-gallery', WPP_URL . 'scripts/wp-property-gallery.js', array( 'jquery' ) );


    // Load localized scripts
    $locale = str_replace('_', '-', get_locale());
    $file_paths = array('jqueryui/datepicker-i18n/jquery.ui.datepicker-' . $locale . '.js');
    // Also check alternate i18n filename (e.g. jquery.ui.datepicker-de.js instead of jquery.ui.datepicker-de-DE.js)
    if (strlen($locale) > 2)
      $file_paths[] = 'jqueryui/datepicker-i18n/jquery.ui.datepicker-' . substr($locale, 0, 2) . '.js';
    $deps = array('jquery-ui-datepicker');
    foreach ($file_paths as $file_path) {
      $path = ud_get_wp_property()->path( 'vendor/libraries/usabilitydynamics/lib-ui/static/scripts/fields/' . $file_path, 'dir' );
      if (file_exists($path)) {
        wp_register_script('jquery-ui-datepicker-i18n', ud_get_wp_property()->path( 'vendor/libraries/usabilitydynamics/lib-ui/static/scripts/fields/' . $file_path, 'url' ), $deps, '1.8.17', true);
        $deps[] = 'jquery-ui-datepicker-i18n';
        break;
      }
    }

    wp_register_style('jquery-ui-core', ud_get_wp_property()->path( 'vendor/libraries/usabilitydynamics/lib-ui/static/styles/fields/jqueryui/jquery.ui.core.css', 'url' ), array(), '1.8.17');
    wp_register_style('jquery-ui-theme', ud_get_wp_property()->path( 'vendor/libraries/usabilitydynamics/lib-ui/static/styles/fields/jqueryui/jquery.ui.theme.css', 'url' ), array(), '1.8.17');
    wp_register_style('jquery-ui-datepicker', ud_get_wp_property()->path( 'vendor/libraries/usabilitydynamics/lib-ui/static/styles/fields/jqueryui/jquery.ui.datepicker.css', 'url' ), array('jquery-ui-core', 'jquery-ui-theme'), '1.8.17');
    wp_register_script('uisf-date', ud_get_wp_property()->path( 'vendor/libraries/usabilitydynamics/lib-ui/static/scripts/fields/date.js', 'url' ), array('jquery-ui-datepicker'), false, true);


    wp_register_style( 'wpp-jquery-fancybox-css', WPP_URL . 'scripts/fancybox/jquery.fancybox-1.3.4.css' );
    wp_register_style( 'wpp-jquery-swiper', WPP_URL . 'styles/swiper.min.css' );
    wp_register_style( 'wpp-jquery-colorpicker-css', WPP_URL . 'scripts/colorpicker/colorpicker.css' );
    wp_register_style( 'jquery-ui', WPP_URL . 'styles/wpp.admin.jquery.ui.css' );
    wp_register_style( 'select2', WPP_URL . 'styles/select2.min.css' );
    wp_register_style( 'wpp-jquery-ui-dialog', WPP_URL . 'styles/jquery-ui-dialog.min.css' );

    wp_register_style( 'wpp-fa-icons', WPP_URL . 'fonts/icons/fa/css/font-awesome.min.css', array(), '4.5.0' );


    /** Find and register stylesheet  */
    if (!WPP_LEGACY_WIDGETS) {
      // load the new v2.3 styles
      wp_register_style('wp-property-frontend', WPP_URL . 'styles/wpp.public.v2.3.css', array(), WPP_Version);
    } else {
      // these are the legacy styles.
      if (file_exists(STYLESHEETPATH . '/wp-properties.css')) {
        wp_register_style('wp-property-frontend', get_bloginfo('stylesheet_directory') . '/wp-properties.css', array(), WPP_Version);
      } elseif (file_exists(STYLESHEETPATH . '/wp_properties.css')) {
        wp_register_style('wp-property-frontend', get_bloginfo('stylesheet_directory') . '/wp_properties.css', array(), WPP_Version);
      } elseif (file_exists(TEMPLATEPATH . '/wp-properties.css')) {
        wp_register_style('wp-property-frontend', get_bloginfo('template_url') . '/wp-properties.css', array(), WPP_Version);
      } elseif (file_exists(TEMPLATEPATH . '/wp_properties.css')) {
        wp_register_style('wp-property-frontend', get_bloginfo('template_url') . '/wp_properties.css', array(), WPP_Version);
      } elseif ($wp_properties['configuration']['autoload_css'] == 'true') {

        wp_register_style('wp-property-frontend', WPP_URL . 'styles/wp_properties.css', array(), WPP_Version);

        //** Find and register theme-specific style if a custom wp_properties.css does not exist in theme */
        if ((
            empty($wp_properties['configuration']['do_not_load_theme_specific_css']) ||
            $wp_properties['configuration']['do_not_load_theme_specific_css'] != 'true'
          ) &&
          WPP_F::has_theme_specific_stylesheet()
        ) {
          wp_register_style('wp-property-theme-specific', WPP_URL . "styles/theme-specific/" . get_option('template') . ".css", array('wp-property-frontend'), WPP_Version);
        }
      }
    }

    //** Find front-end JavaScript and register the script */
    if( file_exists( STYLESHEETPATH . '/wp_properties.js' ) ) {
      wp_register_script( 'wp-property-frontend', get_bloginfo( 'stylesheet_directory' ) . '/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
    } elseif( file_exists( TEMPLATEPATH . '/wp_properties.js' ) ) {
      wp_register_script( 'wp-property-frontend', get_bloginfo( 'template_url' ) . '/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization' ), WPP_Version, true );
    } else {
      wp_register_script( 'wp-property-frontend', WPP_URL . 'scripts/wp_properties.js', array( 'jquery-ui-core', 'wpp-localization', 'wpp-jquery-swiper' ), WPP_Version, true );
    }

    //** Add troubleshoot log page */
    //** Modify admin body class */
    add_filter( 'admin_body_class', array( $this, 'admin_body_class' ), 5 );

    add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );

    //** Modify Front-end property body class */
    add_filter( 'body_class', array( $this, 'properties_body_class' ) );

    add_filter( 'wp_get_attachment_link', array( 'WPP_F', 'wp_get_attachment_link' ), 10, 6 );

    //** Make Property Featured Via AJAX */
    if( isset( $_REQUEST[ 'post_id' ] ) && isset( $_REQUEST[ '_wpnonce' ] ) && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], "wpp_make_featured_" . $_REQUEST[ 'post_id' ] ) ) {
      add_action( 'wp_ajax_wpp_make_featured', function() {
        $post_id = $_REQUEST['post_id'];
        echo WPP_F::toggle_featured( $post_id );
        die();
      } );
    }

    add_filter( 'wpp::draw_stats::attributes', array( __CLASS__, 'make_attributes_hidden' ), 100, 2 );

    //** Post-init action hook */
    do_action( 'wpp_post_init' );

  }

  static public function make_attributes_hidden( $attributes, $property ) {
    global $wp_properties;

    if (
      !empty( $property->property_type ) &&
      !empty( $attributes ) &&
      !empty( $wp_properties['hidden_attributes'][$property->property_type] ) &&
      is_array( $attributes ) &&
      is_array( $wp_properties['hidden_attributes'][$property->property_type] )
    ) {
      foreach( $attributes as $slug => $attr ) {
        if ( in_array( $slug, $wp_properties['hidden_attributes'][$property->property_type] ) ) {
          unset($attributes[$slug]);
        }
      }
    }

    return $attributes;
  }

  /**
   * Limiting to view only own property if 
   * user don't have edit_others_posts capability.
   * 
   * @since 2.2.0.1
   * @author alim
   */

  public function capability_wpp_property($query) {
    global $current_screen;

    if( (!empty($query->query['post_type']) && 'property' != $query->query['post_type']) || !$query->is_admin )
        return $query;

    if( !current_user_can( 'edit_others_posts' ) ) {
      global $user_ID;
      $query->set('author', $user_ID );
    }
    return $query;
  }
  
  /**
   * May be return thumbnail ID for property.
   * HOOK on get_post_meta
   * It'being done only on Front End to prevent different issues!
   *
   * @author peshkov@UD
   * @since 2.1.3
   */
  public function maybe_get_thumbnail_id( $value, $object_id, $meta_key, $single ) {
    if( !is_admin() && $meta_key == '_thumbnail_id' && get_post_type( $object_id ) == 'property' ) {
      $v = \UsabilityDynamics\WPP\Property_Factory::get_thumbnail_id( $object_id );
      if( !empty( $v ) ) {
        if( $single )
          return $v;
        else
          return array( $v );
      }
    }
    return $value;
  }

  /**
   * Modify admin body class on property pages for CSS
   *
   * @since 0.5
   */
  function admin_body_class( $content ) {
    global $current_screen;

    if( $current_screen->id == 'edit-property' ) {
      $content .= ' wp-list-table ';
    }

    if( $current_screen->id == 'property' ) {
      $content .= ' wpp_property_edit ';
    }

    return $content;
  }

  function display_post_states( $post_states, $post ) {
    global $wp_properties;

    if ($post->post_name === $wp_properties['configuration']['base_slug']) {
      $post_states['properties_page'] = __('Properties Page');
    }
    return $post_states;
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
   * @return mixed
   */
  function parse_request( $query ) {
    global $wp, $wp_query, $wp_properties, $wpdb;

    //** If we don't have permalinks, our base slug is always default */
    if( get_option( 'permalink_structure' ) == '' ) {
      $wp_properties[ 'configuration' ][ 'base_slug' ] = 'property';
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

    //** If we are displaying search results: */
    if( isset( $_REQUEST[ 'wpp_search' ] ) && is_array( $_REQUEST[ 'wpp_search' ] ) ) {

      if( isset( $_POST[ 'wpp_search' ] ) ) {
        $_query = http_build_query( apply_filters( 'wpp::search::query', array( 'wpp_search' => $_POST[ 'wpp_search' ] ) ), '', '&' );
        $_redirect = WPP_F::base_url( $wp_properties[ 'configuration' ][ 'base_slug' ] );
        $_redirect .= ( strpos( $_redirect, '?' ) === false ? '?' : '&' ) . $_query;
        wp_redirect( $_redirect );
        die();
      }

      if( isset( $wp_query->wpp_root_property_page ) && $wp_query->wpp_root_property_page ) {
        $wp_query->wpp_search_page = true;
      }

    }

    //** If this is a the root property page, and the Dynamic Default Property page is used */
    if( isset( $wp_query->wpp_root_property_page ) && $wp_properties[ 'configuration' ][ 'base_slug' ] == 'property' ) {
      $wp_query->wpp_default_property_page = true;

      WPP_F::console_log( 'Overriding default 404 page status.' );

      /** Set to override the 404 status */
      add_action( 'wp', function() {
        status_header( 200 );
      } );

      //** Prevent is_404() in template files from returning true */
      add_action( 'template_redirect', function() {
        global $wp_query;
        $wp_query->is_404 = false;
      }, 0, 10 );
    }

    $wpp_pages = array();
    if( isset( $wp_query->wpp_search_page ) ) {
      $wpp_pages[ ] = 'Search Page';
    }
    if( isset( $wp_query->wpp_default_property_page ) ) {
      $wpp_pages[ ] = 'Default Property Page';
    }
    if( isset( $wp_query->wpp_root_property_page ) ) {
      $wpp_pages[ ] = 'Root Property Page.';
    }
    if( !empty( $wpp_pages ) ) {
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
        )
      ) {
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

    if( !isset( $wp_query->is_property_overview ) ) {
      return $content;
    }

    //** Handle automatic PO inserting for non-search root page */
    if(
      !isset( $wp_query->wpp_search_page )
      && isset( $wp_query->wpp_root_property_page )
      && isset( $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] )
      && $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true'
    ) {
      WPP_F::console_log( 'Automatically inserted property overview shortcode into page content.' );
      return do_shortcode( '[property_overview]' );
    }

    //** Handle automatic PO inserting for search pages */
    if(
      isset( $wp_query->wpp_search_page )
      && ( !isset( $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] ) || $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] != 'true' )
    ) {
      WPP_F::console_log( 'Automatically inserted property overview shortcode into search page content.' );
      return do_shortcode( '[property_overview]' );
    }

    return $content;
  }

  /**
   * Hooks into save_post function and saves additional property data
   *
   * @todo Add some sort of custom capability so not only admins can make properties as featured. i.e. Agents can make their own properties featured.
   * @since 1.04
   * @param null $post_id
   * @return null
   */
  function save_property( $post_id = null ) {
    global $wp_properties, $wp_version;

    $_wpnonce = ( version_compare( $wp_version, '3.5', '>=' ) ? 'update-post_' : 'update-property_' ) . $post_id;

    if( !isset( $_POST[ '_wpnonce' ] ) || !wp_verify_nonce( $_POST[ '_wpnonce' ], $_wpnonce ) || $_POST[ 'post_type' ] !== 'property' ) {
      return $post_id;
    }

    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return $post_id;
    }

    $update_data = $_REQUEST[ 'wpp_data' ][ 'meta' ];

    //** Necessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
    if( empty( $_REQUEST[ 'exclude_from_supermap' ] ) ) {
      if( !metadata_exists( 'post', $post_id, 'exclude_from_supermap' ) ) {
        $update_data[ 'exclude_from_supermap' ] = 'false';
      }
    }

    if( !isset( $update_data[ 'latitude' ] ) || (float)$update_data[ 'latitude' ] == 0 ) $update_data[ 'latitude' ] = '';
    if( !isset( $update_data[ 'longitude' ] ) || (float)$update_data[ 'longitude' ] == 0 ) $update_data[ 'longitude' ] = '';

    /* get old coordinates and location */
    $old_lat = get_post_meta( $post_id, 'latitude', true );
    $old_lng = get_post_meta( $post_id, 'longitude', true );

    $geo_data = array(
      'old_coordinates' => ( ( empty( $old_lat ) ) || ( empty( $old_lng ) ) ) ? "" : array( 'lat' => $old_lat, 'lng' => $old_lng ),
      'old_location' => ( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ) ? get_post_meta( $post_id, $wp_properties[ 'configuration' ][ 'address_attribute' ], true ) : ''
    );

//    die( '<pre>' . $post_id . print_r( $update_data, true ) . '</pre>' );
    foreach( $update_data as $meta_key => $meta_value ) {
      $attribute_data = UsabilityDynamics\WPP\Attributes::get_attribute_data( $meta_key );

      $meta_value = html_entity_decode( $meta_value );
      $meta_value = stripslashes( $meta_value );

      /* Handle logic for featured property. */
      if( $meta_key == 'featured' ) {
        //* Only admins can mark properties as featured. */
        if( !current_user_can( 'manage_wpp_make_featured' ) ) {
          //** But be sure that meta 'featured' exists at all */
          if( !metadata_exists( 'post', $post_id, $meta_key ) ) {
            $meta_value = 'false';
          } else {
            continue;
          }
        }
        do_action( 'wpp::toggle_featured', $meta_value, $post_id );
      }

      //* Remove certain characters */
      if( isset( $attribute_data[ 'currency' ] ) || isset( $attribute_data[ 'numeric' ] ) ) {
        $meta_value = str_replace( array( "$", "," ), '', $meta_value );
      }

      //* Overwrite old post meta allowing only one value */
      delete_post_meta( $post_id, $meta_key );
      add_post_meta( $post_id, $meta_key, $meta_value );
    }

    //* Check if property has children */
    $children = get_children( array(
      'post_parent' => $post_id,
      'post_type' => 'property'
    ) );

    // Checking if this is a child property if then add it to children array.
    if($prev_parent_id = wp_get_post_parent_id($post_id)){
      $children[$post_id] = null;
    }

    //* Write any data to children properties that are supposed to inherit things */
    //* 1) Go through all children */
    foreach( (array) $children as $child_id => $child_data ) {

      //* Determine child property_type */
      $child_property_type = get_post_meta( $child_id, 'property_type', true );

      //* Check if child's property type has inheritance rules, and if meta_key exists in inheritance array */
      if(
        isset( $wp_properties[ 'property_inheritance' ][ $child_property_type ] ) &&
        is_array( $wp_properties[ 'property_inheritance' ][ $child_property_type ] )
      ) {
        // Getting parent id //because current property could be a child.
        $parent_id = wp_get_post_parent_id($child_id);

        foreach( $wp_properties[ 'property_inheritance' ][ $child_property_type ] as $i_meta_key ) {
          $parent_meta_value = get_post_meta( $parent_id, $i_meta_key, true );
          //* inheritance rule exists for this property_type for this meta_key */
          update_post_meta( $child_id, $i_meta_key, $parent_meta_value );
        }
      }
    }

    $_gpid = WPP_F::maybe_set_gpid( $post_id );


    /**
     * Flush all object caches related to current property
     */
    \UsabilityDynamics\WPP\Property_Factory::flush_cache( $post_id );
    /**
     * Flush WP-Property caches
     */

    // We need to do it after flashing the cache.
    do_action( 'save_property', $post_id, array(
      'parent_id' => $prev_parent_id,
      'children' => $children,
      'gpid' => $_gpid,
      'update_data' => $update_data,
      'geo_data' => $geo_data
    ));

  }

  /**
   * Inserts content into the "Publish" metabox on property pages
   *
   * @since 1.04
   *
   */
  function post_submitbox_misc_actions() {
    global $post, $wp_properties;
    if( $post->post_type == 'property' ) {
      ?>
      <div class="misc-pub-section ">
        <ul>
          <li><label>Current property ID: </label><span><strong><?php echo $post->ID; ?></strong></span></li>
          <li><?php _e( 'Menu Sort Order:', ud_get_wp_property()->domain ) ?> <?php echo WPP_F::input( "name=menu_order&special=size=4", $post->menu_order ); ?></li>
          <?php if( current_user_can( 'manage_wpp_make_featured' ) ) { ?>
            <li><?php echo WPP_F::checkbox( "name=wpp_data[meta][featured]&label=" . __( 'Display in featured listings.', ud_get_wp_property()->domain ), get_post_meta( $post->ID, 'featured', true ) ); ?></li>
          <?php } ?>
          <?php do_action( 'wpp_publish_box_options', $post ); ?>
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
   * @param $actions
   * @param $post
   * @return mixed
   */
  function property_row_actions( $actions, $post ) {
    if( $post->post_type != 'property' ) {

      global $wp_properties;
      if ($post->post_type == 'page' && $post->post_name == $wp_properties['configuration']['base_slug']) {
        unset($actions['trash']); // remove Trash action for current property overview page
        return $actions;
      }

      return $actions;
    }

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
      1 => sprintf( __( '%2$s updated. <a href="%s">View %2$s</a>', ud_get_wp_property()->domain ), esc_url( get_permalink( $post_id ) ), WPP_F::property_label() ),
      2 => __( 'Custom field updated.', ud_get_wp_property()->domain ),
      3 => __( 'Custom field deleted.', ud_get_wp_property()->domain ),
      4 => sprintf( __( '%s updated.', ud_get_wp_property()->domain ), WPP_F::property_label() ),
      /* translators: %s: date and time of the revision */
      5 => isset( $_GET[ 'revision' ] ) ? sprintf( __( '%2$s restored to revision from %s', ud_get_wp_property()->domain ), wp_post_revision_title( (int)$_GET[ 'revision' ], false ), WPP_F::property_label() ) : false,
      6 => sprintf( __( '%2$s published. <a href="%s">View %2$s</a>', ud_get_wp_property()->domain ), esc_url( get_permalink( $post_id ) ), WPP_F::property_label() ),
      7 => sprintf( __( '%s saved.', ud_get_wp_property()->domain ), WPP_F::property_label() ),
      8 => sprintf( __( '%2$s submitted. <a target="_blank" href="%s">Preview %2$s</a>', ud_get_wp_property()->domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), WPP_F::property_label() ),
      9 => sprintf( __( '%2$s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %2$s</a>', ud_get_wp_property()->domain ),
        // translators: Publish box date format, see http://php.net/date
        date_i18n( __( 'M j, Y @ G:i', ud_get_wp_property()->domain ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_id ) ), WPP_F::property_label()),
      10 => sprintf( __( '%2$s draft updated. <a target="_blank" href="%s">Preview %2$s</a>', ud_get_wp_property()->domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_id ) ) ), WPP_F::property_label() ),
    );

    $messages = apply_filters( 'wpp_updated_messages', $messages );

    return $messages;
  }

  /**
   *
   */
  public function template_include( $template ) {
    global $wp_query, $post;

    /** SINGLE PROPERTY PAGE TEMPLATE */

    if( isset( $wp_query->single_property_page ) ) {

      $config = ud_get_wp_property( 'configuration.single_property', array() );
      $redeclare = get_post_meta( $post->ID, '_wpp_redeclare_template', true );
      $property_type = get_post_meta( $post->ID, 'property_type', true );

      if( !empty( $redeclare ) && $redeclare == 'true' ) {
        $tmpl = get_post_meta( $post->ID, '_wpp_template', true );
        $page_tmpl = get_post_meta( $post->ID, '_wpp_page_template', true );
      }

      if( empty( $tmpl ) ) {
        $tmpl = !empty( $config[ 'template' ] ) ? $config[ 'template' ] : 'property';
      }

      if( empty( $page_tmpl ) ) {
        $page_tmpl = !empty( $config[ 'page_template' ] ) ? $config[ 'page_template' ] : 'default';
      }

      /*
       * If template is not defined or it's 'property', we are using our
       * predefined property.php template.
       * This logic is mostly legacy.
       */
      if( $tmpl == 'property' ) {

        $_template = WPP_F::get_template_part( array_filter( array(
          ( !empty( $property_type ) ? "property-{$property_type}" : false ),
          "property",
        ) ), array( WPP_Templates ) );

        //** Load the first found template */
        if( $_template ) {
          WPP_F::console_log( 'Found single property page template:' . wp_normalize_path($_template) );
          return $_template;
        }

        return $template;
      }
      /*
       * If template is 'page', we are using theme's page templates
       * for rendering page.
       */
      elseif( $tmpl == 'page' ) {
        $_template = false;
        if( $page_tmpl == 'default' ) {
          $_template = locate_template( 'page.php' );
        } else {
          $_template = locate_template( $page_tmpl );
        }

        if( !empty( $_template ) ) {
          return $_template;
        }

      }

      return $template;

    }

    /** PROPERTY OVERVIEW PAGE TEMPLATE */

    /* Current requests includes a property overview.
     * PO may be via shortcode, search result, or due to this being the Default Dynamic Property page.
     * If using Dynamic Property Root page, we must load a template
     */
    if( isset( $wp_query->is_property_overview ) && isset( $wp_query->wpp_default_property_page ) ) {

      //** Unset any post that may have been found based on query */
      $post = false;

      $_template = WPP_F::get_template_part( array(
        "property-search-result",
        "property-overview-page",
      ), array( WPP_Templates ) );

      //** Load the first found template */
      if( $_template ) {
        return $_template;
      }

    }

    return $template;
  }

  /**
   * Performs front-end pre-header functionality
   *
   * - This function is not called on admin side.
   * - Loads conditional CSS styles.
   * - Determines if page is single property or property overview.
   *
   * @since 1.11
   */
  public function template_redirect() {
    global $post, $property, $wp_query, $wp_properties, $wp_styles, $wpp_query;

    /**
     * HACK.
     * @see self::parse_request();
     * @author peshkov@UD
     */
    if( get_query_var( '_fix_to_page_template' ) ) {
      $wp_query->is_single = false;
      $wp_query->is_page = true;
    }

    wp_localize_script( 'wpp-localization', 'wpp',array( 'instance' =>  apply_filters( 'wpp::localization::instance', $this->get_instance() ) ) );

    //** Load global wp-property script on all frontend pages */
    wp_enqueue_script( 'wp-property-global' );

    if( apply_filters( 'wpp::custom_styles', false ) === false ) {
      //** Possibly load essential styles that are used in widgets */
      wp_enqueue_style( 'wp-property-frontend' );
      wp_enqueue_style( 'wpp-public-frontend' );
      //** Possibly load theme specific styles */
      wp_enqueue_style( 'wp-property-theme-specific' );
    }

    if( !isset( $wp_properties[ 'configuration' ][ 'do_not_enable_text_widget_shortcodes' ] ) || $wp_properties[ 'configuration' ][ 'do_not_enable_text_widget_shortcodes' ] != 'true' ) {
      add_filter( 'widget_text', 'do_shortcode' );
    }

    do_action( 'wpp_template_redirect' );

    //** Handle single property page previews */
    if( !empty( $wp_query->query_vars[ 'preview' ] ) && $post->post_type == "property" && $post->post_status == "publish" ) {
      wp_redirect( get_permalink( $post->ID ) );
      die();
    }

    /* (count($wp_query->posts) < 2) added post 1.31.1 release */
    /* to avoid taxonomy archives from being broken by single property pages */
    if(
      isset( $post ) &&
      ( count( $wp_query->posts ) < 2 || ( !empty( $wp_query->queried_object ) && $wp_query->queried_object->post_type == 'property' ) ) &&
      ( $post->post_type == "property" || isset( $wp_query->is_child_property ) )
    ) {
      $wp_query->single_property_page = true;

      //** This is a hack and should be done better */
      if( !$post ) {
        $post = get_post( $wp_query->queried_object_id );
        $wp_query->posts[ 0 ] = $post;
        $wp_query->post = $post;
      }

    }

    //** If viewing root property page that is the default dynamic page. */
    if( isset( $wp_query->wpp_default_property_page ) ) {
      $wp_query->is_property_overview = true;
    }

    //** If this is the root page with a manually inserted shortcode, or any page with a PO shortcode */
    if( isset( $post ) && strpos( $post->post_content, "property_overview" ) ) {
      $wp_query->is_property_overview = true;
    }

    //** If this is the root page and the shortcode is automatically inserted */
    if( isset( $wp_query->wpp_root_property_page ) && $wp_properties[ 'configuration' ][ 'automatically_insert_overview' ] == 'true' ) {
      $wp_query->is_property_overview = true;
    }

    //** If search result page, and system not explicitly configured to not include PO on search result page automatically */
    if(
      isset( $wp_query->wpp_search_page ) &&
      ( !isset( $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] ) || $wp_properties[ 'configuration' ][ 'do_not_override_search_result_page' ] != 'true' )
    ) {
      $wp_query->is_property_overview = true;
    }

    //** Scripts and styles to load on all overview and single listing pages */
    if( isset( $wp_query->single_property_page ) || isset( $wp_query->is_property_overview ) ) {

      // Check for and load conditional browser styles
      $conditional_styles = apply_filters( 'wpp_conditional_style_slugs', array( 'IE', 'IE 7', 'msie' ) );

      foreach( $conditional_styles as $type ) {

        // Fix slug for URL
        $url_slug = strtolower( str_replace( " ", "_", $type ) );

        if( file_exists( STYLESHEETPATH . "/wp_properties-{$url_slug}.css" ) ) {
          wp_register_style( 'wp-property-frontend-' . $url_slug, get_bloginfo( 'stylesheet_directory' ) . "/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), '1.13' );
        } elseif( file_exists( TEMPLATEPATH . "/wp_properties-{$url_slug}.css" ) ) {
          wp_register_style( 'wp-property-frontend-' . $url_slug, get_bloginfo( 'template_url' ) . "/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), '1.13' );
        } elseif( file_exists( WPP_URL . "styles/wp_properties-{$url_slug}.css" ) && $wp_properties[ 'configuration' ][ 'autoload_css' ] == 'true' ) {
          wp_register_style( 'wp-property-frontend-' . $url_slug, WPP_URL . "styles/wp_properties-{$url_slug}.css", array( 'wp-property-frontend' ), WPP_Version );
        }
        // Mark every style as conditional
        $wp_styles->add_data( 'wp-property-frontend-' . $url_slug, 'conditional', $type );
        wp_enqueue_style( 'wp-property-frontend-' . $url_slug );

      }

    }

    //** Scripts loaded only on single property pages */
    if( isset( $wp_query->single_property_page ) ) {

      WPP_F::console_log( 'Including scripts for all single property pages.' );

      WPP_F::load_assets( array( 'single' ) );

      do_action( 'template_redirect_single_property' );

      add_action( 'wp_head', function() {
        do_action('wp_head_single_property');
      });

      $property = (array) WPP_F::get_property( $post->ID, "load_gallery=true" );

      $property_type = !empty( $property['property_type'] ) ? $property['property_type'] : false;

      // Redirect to parent if property type is non-public.
      if( isset( $wp_properties[ 'redirect_to_parent' ] ) && is_array( $wp_properties[ 'redirect_to_parent' ] ) && in_array( $property_type, $wp_properties[ 'redirect_to_parent' ] ) && $property['post_parent'] ) {
        die( wp_redirect( get_permalink( $property[ 'post_parent' ] )) );
      }

      $property = prepare_property_for_display( $property );

      //** Make certain variables available to be used within the single listing page */
      $single_page_vars = apply_filters( 'wpp_property_page_vars', array(
        'property' => $property,
        'wp_properties' => $wp_properties
      ) );

      //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
      if( is_array( $single_page_vars ) ) {
        $wp_query->query_vars = array_merge( $wp_query->query_vars, $single_page_vars );
      }

    }

    //** Current requests includes a property overview.  PO may be via shortcode, search result, or due to this being the Default Dynamic Property page */
    if( isset( $wp_query->is_property_overview ) ) {

      WPP_F::console_log( 'Including scripts for all property overview pages.' );

      WPP_F::load_assets( array( 'overview' ) );

      if( isset( $wp_query->wpp_default_property_page ) ) {
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
      if( is_array( $overview_page_vars ) ) {
        $wp_query->query_vars = array_merge( $wp_query->query_vars, $overview_page_vars );
      }

      do_action( 'template_redirect_property_overview' );

      add_action( 'wp_head', function() {
        do_action('wp_head_property_overview');
      } );

    }

    do_action( 'wpp_template_redirect_post_scripts' );

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

    if(
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
    if( is_array( $wpp_settings ) && is_array( $wp_properties ) ) {
      foreach( $wp_properties as $key => $value ) {
        if( !isset( $wpp_settings[ $key ] ) ) {
          switch( $key ) {
            case 'hidden_attributes':
            case 'property_inheritance':
              $wpp_settings[ $key ] = array();
              break;
          }
        }
      }
      
      if(empty($wpp_settings['configuration']['google_maps_api_server']) && !empty($wpp_settings['configuration']['google_maps_api'])){
        $wpp_settings['configuration']['google_maps_api_server'] = $wpp_settings['configuration']['google_maps_api'];
      }
    }

    return $wpp_settings;
  }

  /**
   * Hack to avoid issues with capabilities and views.
   *
   */
  function current_screen( $screen ) {

    switch( $screen->id ) {
      case "edit-property":
        die( wp_redirect( add_query_arg( $_GET, 'edit.php?post_type=property&page=all_properties' ) ) );
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
      'edit_wpp_properties' => sprintf(__( 'View %s', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),

      'edit_wpp_property' => sprintf(__( 'Add/Edit %s', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),
      'delete_wpp_property' => sprintf(__( 'Delete %s', ud_get_wp_property()->domain ), WPP_F::property_label()),

      'publish_wpp_properties' => sprintf(__( 'Publish %s', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),

      //* WPP others property capability */
      'edit_others_wpp_properties' => sprintf(__( 'Edit Others %s', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),
      'delete_others_wpp_properties' => sprintf(__( 'Delete Others %s', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),
      //* WPP private property capability */
      'edit_private_wpp_properties' => sprintf(__( 'Edit Private %s', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),
      'delete_private_wpp_properties' => sprintf(__( 'Delete Private %s', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),

      //* WPP make featured capability */
      'manage_wpp_make_featured' => sprintf(__( 'Allow to mark %s as featured', ud_get_wp_property()->domain ), WPP_F::property_label('plural')),
      //* WPP Settings capability */
      'manage_wpp_settings' => __( 'Manage Settings', ud_get_wp_property()->domain ),
      //* WPP Taxonomies capability */
      'manage_wpp_categories' => __( 'Manage Taxonomies', ud_get_wp_property()->domain ),
    );

    //* Adds Premium Feature Capabilities */
    $wpp_capabilities = apply_filters( 'wpp_capabilities', $wpp_capabilities );

    if( !is_object( $role ) ) {
      return;
    }

    $is_cap_added = false;
    foreach( $wpp_capabilities as $cap => $value ) {
      if( empty( $role->capabilities[ $cap ] ) ) {
        $role->add_cap( $cap );
        $is_cap_added = true;
      }
      if($cap == 'edit_wpp_property' && empty($role->capabilities[ 'create_wpp_properties' ])){
        $role->add_cap( 'create_wpp_properties' );
      }
      elseif($cap == 'delete_wpp_property' && empty($role->capabilities[ 'delete_wpp_properties' ])){
        $role->add_cap( 'delete_wpp_properties' );
      }
    }

    // If current user with admin privileges
    // And we just set new caps for admin role
    // We re-set the current user with new caps
    // Issue: https://github.com/wp-property/wp-property/issues/413
    if ( $is_cap_added && current_user_can( 'manage_options' ) ) {
      global $current_user;
      $user_id = get_current_user_id();
      $current_user = null;
      WPP_F::debug( 'Update current user with new caps' );
      wp_set_current_user($user_id);
    }

  }

  /**
   *
   */
  public function get_l10n_data() {

    $l10n = array();
    //** Include the list of translations */
    $l10n_dir = ud_get_wp_property()->path( 'l10n.php', 'dir' );
    include( $l10n_dir );
    /** All additional localizations must be added using the filter below. */
    $l10n = apply_filters( 'wpp::js::localization', $l10n );
    foreach( (array)$l10n as $key => $value ) {
      if( !is_scalar( $value ) ) {
        continue;
      }
      $l10n[ $key ] = html_entity_decode( (string)$value, ENT_QUOTES, 'UTF-8' );
    }
    return $l10n;
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
    if( is_callable( array( 'WP_Screen', 'add_help_tab' ) ) ) {

      //** Loop through help items and build tabs */
      foreach( (array)$contextual_help as $help_tab_title => $help ) {

        //** Add tab with current info */
        get_current_screen()->add_help_tab(
          array(
            'id' => sanitize_title( $help_tab_title ),
            'title' => __( $help_tab_title, ud_get_wp_property()->domain ),
            'content' => implode( "\n", (array)$contextual_help[ $help_tab_title ] ),
          )
        );

      }

      //** Add help sidebar with More Links */
      get_current_screen()->set_help_sidebar(
        '<p><strong>' . __( 'For more information:', ud_get_wp_property()->domain ) . '</strong></p>' .
        '<p>' . __( '<a href="https://www.usabilitydynamics.com/product/wp-property/" target="_blank">WP-Property Product Page</a>', ud_get_wp_property()->domain ) . '</p>' .
        '<p>' . __( '<a href="https://wordpress.org/support/plugin/wp-property/" target="_blank">WP-Property Forums</a>', ud_get_wp_property()->domain ) . '</p>' .
        '<p>' . __( '<a href="https://www.usabilitydynamics.com/product/wp-property/docs/home" target="_blank">WP-Property Tutorials</a>', ud_get_wp_property()->domain ) . '</p>'
      );

    } else {
      global $current_screen;
      add_contextual_help( $current_screen->id, '<p>' . __( 'Please upgrade Wordpress to the latest version for detailed help.', ud_get_wp_property()->domain ) . '</p><p>' . __( 'Or visit <a href="https://usabilitydynamics.com/tutorials/wp-property-help/" target="_blank">WP-Property Help Page</a> on UsabilityDynamics.com', ud_get_wp_property()->domain ) . '</p>' );
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

    $data = array(
      'request' => $_REQUEST,
      'get' => $_GET,
      'post' => $_POST,
      'iframe_enabled' => false,
      'ajax_url' => admin_url( 'admin-ajax.php' ),
      'home_url' => home_url(),
      'user_logged_in' => is_user_logged_in() ? 'true' : 'false',
      'is_permalink' => ( get_option( 'permalink_structure' ) !== '' ? true : false ),
      'settings' => ud_get_wp_property()->get(),
    );

    if( isset( $data[ 'request' ][ 'wp_customize' ] ) && $data[ 'request' ][ 'wp_customize' ] == 'on' ) {
      $data[ 'iframe_enabled' ] = true;
    }

    $data = apply_filters( 'wpp::get_instance', $data );

    /** Security: If we're not on an admin, we should remove the XMLI info */
    if( !( is_admin() && current_user_can( 'manage_options' ) ) && isset( $data[ 'settings' ][ 'configuration' ][ 'feature_settings' ][ 'property_import' ] ) ) {
      unset( $data[ 'settings' ][ 'configuration' ][ 'feature_settings' ][ 'property_import' ] );
    }

    return $data;
  }

  /**
   * Renders property overview.
   * Deprecated. Use do_shortcode( '[property_overview]' ) instead.
   *
   * @deprecated 2.1.0
   */
  static function shortcode_property_overview( $atts = '' ) {
    //_deprecated_function( __FUNCTION__, '2.1.0', 'do_shortcode([property_overview])' );
    return UsabilityDynamics\WPP\Property_Overview_Shortcode::render( $atts );
  }

  /**
   * Heartbeat filter for locking settings page.
   *
   * 
   *
   *
   */

  public function wpp_settings_lock_heartbeat_received( $response, $data, $screen_id ){
    if(isset( $data['property_settings_lock'] )){
      if($data['property_settings_lock'] === true || $data['property_settings_lock'] == 'true') {
        if ( $new_lock = $this->wpp_settings_set_lock() )
          $response['property_settings_lock']['new_lock'] = implode( ':', $new_lock );
      }
      else{
        $response['property_settings_lock']['lock_removed'] = $this->wpp_settings_remove_lock();
      }
    }

    return $response;
  }
  /**
   * Heartbeat filter for locking settings page.
   *
   * 
   *
   *
   */

  public function wpp_settings_lock_heartbeat_send( $response, $screen_id ){
    // Checking if it's being edited by other user
    if ( ( $user_id = $this->wpp_settings_check_lock() ) && ( $user = get_userdata( $user_id ) ) ) {
      $lock_error = &$response['property_settings_lock']['lock_error'];
      $lock_error['text'] = sprintf( __( '%s is currently editing settings.' ), $user->display_name );

      if ( $avatar = get_avatar( $user->ID, 64 ) ) {
        if ( preg_match( "|src='([^']+)'|", $avatar, $matches ) )
          $lock_error['avatar_src'] = $matches[1];
      }
    }

    return $response;
  }


  public function wpp_settings_set_lock() {
    if ( 0 == ($user_id = get_current_user_id()) )
      return false;
    
    $now = time();
    $lock = "$now:$user_id";

    update_option( 'wpp_settings_lock', $lock );
    return array( $now, $user_id );
  }

  public function wpp_settings_remove_lock() {
    delete_option('wpp_settings_lock');
    return true;
  }

  public function wpp_settings_check_lock() {
    //$lock = get_option( 'wpp_settings_lock' );
    if ( !$lock = get_option( 'wpp_settings_lock' ) )
      return false;

    $lock = explode( ':', $lock );
    $time = $lock[0];
    $user = isset( $lock[1] ) ? $lock[1] : 0;
    
    /** This filter is similar to wp_check_post_lock_window */
    $time_window = apply_filters( 'wpp_settings_lock_window', 150 );
 
    if ( $time && $time > time() - $time_window && $user != get_current_user_id() )
      return $user;
    return false;
  }

}


