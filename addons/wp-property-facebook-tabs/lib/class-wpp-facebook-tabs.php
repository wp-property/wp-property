<?php
/**
 * Name: Facebook Tabs
 * Feature ID: 18
 * Minimum Core Version: 1.42.0
 * Version: 1.1.0
 * Description: Allows Facebook users insert Page Tabs with WP-Property Canvas to Facebook pages.
 * Class: class_wpp_facebook_tabs
 */

//** Be sure we do not try to define class twice */
if( !class_exists( 'class_wpp_facebook_tabs' ) ) {

  /**
   * class_wpp_facebook_tabs Class
   *
   * @version 1.0
   * @author Korotkov@UD
   * @package WP-Property
   * @subpackage Facebook
   */
  class class_wpp_facebook_tabs {

    /**
     * Capability to manage the current feature
     *
     * @var string
     * @author korotkov@UD
     */
    const capability = "manage_wpp_facebook_tabs";

    /**
     * Input name base
     *
     * @var string
     * @author korotkov@ud
     */
    const post_name_base = "wpp_settings[configuration][feature_settings][facebook_tabs]";

    /**
     * Query var which is used for parse request.
     * Determines if the request to facebook tab canvas
     *
     * @var string
     * @author peshkov@UD
     */
    const query_var = "wpp_fb_canvas";

    /**
     * Special functions that must be called prior to init
     *
     * @action wpp_pre_init (10)
     * @author korotkov@UD
     */
    static public function wpp_pre_init() {
      global $wp_properties;

      /** Add Facebook Tabs page under Properties nav menu */
      add_action( "admin_menu", array( __CLASS__, "admin_menu" ), 11 );

      /** Admin specific */
      add_action( "admin_init", array( __CLASS__, "admin_init" ) );
      add_filter( 'wpp_settings_save', array( __CLASS__, 'wpp_settings_save'), 0, 2 );

      //** Add filter to hide selected pages */
      add_filter('get_pages', array(__CLASS__, "hide_pages"));


      //** Template Redirect: Capture Single Properties and Overview Pages */
      add_action('template_redirect_single_property', array(__CLASS__, "template_redirect") );
      add_action('template_redirect_property_overview', array(__CLASS__, "template_redirect") );

      //** Template Redirect: Capture all other pages after WPP's template_redirect has gone */
      add_action('wpp_template_redirect_post_scripts', array(__CLASS__, "template_redirect") );

      //** Add capability */
      add_filter('wpp_capabilities', array(__CLASS__, "add_capability"));

      //** Add AJAX action for get_properties */
      add_action('wp_ajax_wpp_fb_tabs_get_properties', array(__CLASS__, "get_properties"));

      //** Updates all WPP rewrite rules on flush_rewrite_rules() */
      add_action( 'rewrite_rules_array', array( __CLASS__, '_rewrite_rules' ), 20 );
      add_filter( 'query_vars', array( __CLASS__ , '_insertqv' ) );
      add_filter( 'the_posts',array( __CLASS__ , '_wpp_fb_page' ) );
      add_action( 'wp_loaded',array( __CLASS__ , '_flush_rules' ) );
      add_filter( 'ud::template_part::fb_tabs', array( __CLASS__, "template_part_fb_tabs"), 10, 2 );
      add_filter( 'ud::current_instance', array( __CLASS__ , 'current_template_instance' ) );

    }

    /**
     * Apply feature's Hooks and other functionality
     *
     * @global array $wp_properties
     * @author korotkov@ud
     */
    static public function wpp_init() {

      //** If user have permissions */
      if(current_user_can(self::capability)) {
        global $wp_properties;

        //** FT contextual help */
        add_action('load-property_page_facebook_tabs', array(__CLASS__, 'add_contextual_help'));

      }
    }

    /**
     * Handle pre-headers functions.
     *
     * @author peshkov@UD
     */
    static public function admin_init() {

      add_action( "admin_enqueue_scripts", array( __CLASS__, "admin_enqueue_scripts" ) );

      if( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpp_save_facebook_tabs_page') ) {
        //** Update facebook tabs settings */
        $_wp_properties = get_option( 'wpp_settings' );
        $_wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'] = $_REQUEST['wpp_fb'];
        update_option( 'wpp_settings', $_wp_properties );

        wp_redirect('edit.php?post_type=property&page=facebook_tabs&message=updated');
      }

    }

    /**
     *
     * @author peshkov@UD
     */
    static public function admin_menu() {
      add_submenu_page( 'edit.php?post_type=property', __( 'Facebook Tabs', ud_get_wpp_fbtabs()->domain ), __( 'Facebook Tabs', ud_get_wpp_fbtabs()->domain ), self::capability, 'facebook_tabs', array( __CLASS__, 'page_facebook_tabs' ) );
    }

    /**
     * Prevents missing canvases configuration on global settings saving
     *
     * @global array $wp_properties
     * @param array $settings
     * @return array
     * @author peshkov@UD
     */
    static public function wpp_settings_save( $settings ) {
      global $wp_properties;

      if( !empty( $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'] ) ) {
        $settings['configuration']['feature_settings']['facebook_tabs']['canvases'] = $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'];
      } else {
        $settings['configuration']['feature_settings']['facebook_tabs']['canvases'] = array();
      }

      return $settings;
    }


    /**
     * Ajax handler for searching properties in autocomplete
     *
     * @author korotkov@ud
     */
    static public function get_properties() {

      //** Flush variables */
      $found_post_data = array();

      //** Search properties */
      $properties = new WP_Query(
        array(
          's' => $_REQUEST['s'],
          'post_type' => 'property'
        )
      );

      //** Get required data */
      if ( !empty( $properties ) && !empty( $properties->posts ) ) {
        foreach ( $properties->posts as $post ) {
          $data = new stdClass();
          $data->title = $post->post_title;
          $data->id = $post->ID;
          $found_post_data[] = $data;
        }
      }

      //** Print json that will be recognized as Object by JS */
      die( json_encode($found_post_data) );
    }


    /**
     * Contextual Help
     *
     * @author korotkov@ud
     */
    static public function add_contextual_help() {

      $contextual_help['About'][] = '<h3>'.__('Facebook Tabs', ud_get_wpp_fbtabs()->domain).'</h3>';
      $contextual_help['About'][] = '<p>'.__('Feature allows you to create Facebook Canvases for Tabs or Applications.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['About'][] = '<p>'.__('Note that SSL support for your page tab app has been mandatory since October 1, 2011.', ud_get_wpp_fbtabs()->domain).'</p>';

      $contextual_help['Name'][] = '<p>'.__('<b>Name</b><br />', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Name'][] = '<p>'.__('General name of your Canvas.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Name'][] = '<p>'.__('Name is used for canvas links generation. See Facebook App Setup tab.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Name'][] = '<p>'.__('Links use sanitized name of your Canvas\' name. So they will be recreated every time you change the name.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Name'][] = '<p>'.__('Make sure you do not have Canvases with the same slugs.', ud_get_wpp_fbtabs()->domain).'</p>';

      $contextual_help['Page'][] = '<p>'.__('<b>Page Settings</b><br/>', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Page'][] = '<p>'.__('<b>Type</b><br />The list of available post types. Available pages depend on the selected type.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Page'][] = '<p>'.__('<b>Page</b><br />The page of your site which has the content which you need to display in your Facebook Tab or Application.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Page'][] = '<p>'.__('<b>Hide the selected page from Regular WordPress</b><br />Hidden pages will not be accessible in Regular Wordpress and in other Premium Features too. The current option is only related to pages.', ud_get_wpp_fbtabs()->domain).'</p>';

      $contextual_help['Template Settings'][] = '<p>'.__('<b>Template Settings</b><br/>', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Template Settings'][] = '<p>'.__('<b>Template</b><br />Specify one of existing templates for displaying Page content. Default are <code>static/views/fb-tab-page.php</code> and <code>static/views/fb-tab-property.php</code>.<br/>', ud_get_wpp_fbtabs()->domain );
      $contextual_help['Template Settings'][] = __('You can add your own templates named like <code>fb-tab-{your name}.php</code> in your theme folder and with the comment inside like:', ud_get_wpp_fbtabs()->domain );
      $contextual_help['Template Settings'][] = __('<pre><code>/**<br/>&nbsp;* Template Name: {your name}<br/>&nbsp;* Type: {page|property}<br/>&nbsp*/</code></pre>Use Default template as a base for your own.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Template Settings'][] = '<p>'.__('<b>Enable theme\'s template parts</b><br />Canvas uses default WP-Property template parts by default ( e.g. <code>templates/property_overview.php</code> , <code>templates/property_search.php</code> ).<br/>If enabled (checked), application will load template parts from your theme\'s folder if they exist. <b>But, remember, custom template parts can be incompatible with default CSS styles.</b>', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Template Settings'][] = '<p>'.__('<b>Open links in new window</b><br />Check this option if you need to open EACH link of your WP-Property Facebook Tab in new window.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Template Settings'][] = '<p>'.__('<b>Open forms in new window</b><br />The same as <b>Open links in new window</b> but for forms. Every form will be submitted in new window.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Template Settings'][] = '<p>'.__('<b>Disable default CSS</b><br/>Canvases use default specific CSS styles which can be disabled if this option is checked.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Template Settings'][] = '<p>'.__('<b>Allow inline CSS</b><br/>Enables to add specific CSS styles to the current canvas. Toggles <b>Inline CSS</b> textarea for adding CSS styles.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Template Settings'][] = '<p>'.__('<b>Custom CSS and Javascript files</b><br/>You can create custom CSS and Javascript files which will be loaded on all your canvases.<br/>', ud_get_wpp_fbtabs()->domain);
      $contextual_help['Template Settings'][] = __('To add custom CSS or JS file, you need to create css or js file in your theme folder like <code>fb-tab-{specific_name}.{css|js}</code>.', ud_get_wpp_fbtabs()->domain).'</p>';


      $contextual_help['Facebook App Setup'][] = '<p>'.__('<b>Facebook Application Setup</b><br/>', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Facebook App Setup'][] = '<p>'.__('<a href="https://developers.facebook.com/docs/guides/canvas/" target="_blank">How to create Facebook Application</a>', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Facebook App Setup'][] = '<p>'.__('<b>App ID</b><br />App ID is your application\'s App ID/API Key which you can get from Application Settings page.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Facebook App Setup'][] = '<p>'.__('<b>Secret</b><br />Secret is long hashed string associated with your application. You can get it from Application Settings page.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Facebook App Setup'][] = '<p>'.__('<b>Canvas URL</b><br />Copy the current URL to your facebook application settings.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Facebook App Setup'][] = '<p>'.__('<b>Secure Canvas URL</b><br />Copy the current URL to your facebook application settings.', ud_get_wpp_fbtabs()->domain).'</p>';
      $contextual_help['Facebook App Setup'][] = '<p>'.__('<b>Debug URL</b><br />This link can be used for troubleshooting the issues directly.', ud_get_wpp_fbtabs()->domain).'</p>';

      //** Hook this is you need to add some helps to Agents Settings page */
      $contextual_help = apply_filters('wpp_facebook_tabs_help', $contextual_help);

      do_action('wpp_contextual_help', array('contextual_help'=>$contextual_help));
    }


    /**
     * Filter
     * Returns current template instance
     *
     * @see UD_API::get_template_part()
     * @author peshkov@UD
     */
    static public function current_template_instance( $instance ) {
      global $wp_query;
      if( isset( $_SERVER["HTTP_X_FB_CANVAS"] ) || ( isset( $wp_query->query_vars["wpp_fb_canvas"] ) && isset( $wp_query->query_vars["signed_request"] ) ) ){
        $instance = "fb_tabs";
      }
      return $instance;
    }


    /**
     * Search template in plugin directory, ignores stylesheet
     *
     * @param type $template
     * @param type $args
     * @return type
     * @author odokienko@UD
     */
    static public function template_part_fb_tabs( $template,  $args ) {
      global $wp_query, $wp_properties;

      //** Determine if canvas is defined */
      $canvas = isset( $_SERVER["HTTP_X_FB_CANVAS"] ) ? $_SERVER["HTTP_X_FB_CANVAS"] : ( isset( $wp_query->query_vars["wpp_fb_canvas"] ) ? $wp_query->query_vars["wpp_fb_canvas"] : false );
      if( !$canvas || empty( $wp_properties['configuration']['feature_settings']['facebook_tabs'][ 'canvases' ][ $canvas ] ) ) {
        return $template;
      }

      //** If enabled theme template parts for current canvas we don't continue; */
      $settings = $wp_properties['configuration']['feature_settings']['facebook_tabs'][ 'canvases' ][ $canvas ];
      if( isset( $settings[ 'settings' ][ 'enable_theme_templates' ] ) && $settings[ 'settings' ][ 'enable_theme_templates' ] == 'true' ) {
        return $template;
      }
      $name = '';
      extract($args);

      $new_template = '';

      foreach((array)$name as $n) {
        $n = "{$n}.php";
        if(!empty($path)) {
          foreach((array)$path as $p) {
            if(file_exists($p . "/" . $n)) {
              $new_template = $p . "/" . $n;
              break(2);
            }
          }
        }

      }

      WPP_F::console_log( $new_template );

      return !empty( $new_template ) ? $new_template : $template;
    }


    /**
     * Template redirect for facebook tabs.
     *
     * Listens for a signed Facebook request.
     *
     * @author korotkov@ud
     */
    static public function template_redirect() {
      global $wp_properties, $wp_query;

      $wpp_fb_canvas  = get_query_var( self::query_var );
      $signed_request = get_query_var('signed_request');

      //** If request goes not from facebook */
      if ( empty( $signed_request ) || empty( $wpp_fb_canvas ) ) {

        $hidden_pages = self::get_hidden_pages();

        //** If we have hidden pages */
        if ( !empty( $hidden_pages ) && is_array( $hidden_pages ) && $wp_query->is_page ) {
          //** Set 404 for hidden pages */
          foreach ( $hidden_pages as $id ) {
            if ( $wp_query->post->ID == $id ) {
              status_header('404');
              $wp_query->set_404();
            }
          }
        }

        return;
      }
      
      if( !defined( 'IFRAME_REQUEST' ) ) {
        define( 'IFRAME_REQUEST', true );
      }

      add_filter( 'use_default_gallery_style', '__return_true' );

      //** Current canvas configuration */
      $current_canvas = $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'][$wpp_fb_canvas];

      //** If no such canvas */
      if ( empty( $current_canvas ) ) die( __('Requested Canvas does not exist.', ud_get_wpp_fbtabs()->domain) );

      //** Decode 'signed_request' using 'secret' */
      $canvas_request_data = self::parse_signed_request( $signed_request, $current_canvas['secret'] );

      //** Decode failed */
      if( !is_array( $canvas_request_data ) && $signed_request !== md5( "debug::{$wpp_fb_canvas}" ) . '.' . md5( "debug::payload" ) ) {
        die( __('Canvas settings are invalid. Check "Secret" field.', ud_get_wpp_fbtabs()->domain) );
      }

      if( empty( $current_canvas['template'] ) ) {
        //** Template is not specified */
        die( __('You did not specify Facebook Template for requested Canvas', ud_get_wpp_fbtabs()->domain) );
      }

      //** Hide Admin bar */
      show_admin_bar( false );
      //wp_deregister_script('admin-bar');
      //wp_deregister_style('admin-bar');

      //** Facebook page must not use theme scripts and styles. Only specific ones with prefix 'fb-tab-' are allowed. */
      self::deregister_assets( 'css' );
      self::deregister_assets( 'js' );

      //** Enqueue FB style */
      if ( $current_canvas['disable_default_css']!=='true' ){
        wp_enqueue_style( 'fb-properties', ud_get_wpp_fbtabs()->path( "static/styles/fb_properties.css" ) );
      }

      //** Available Facebook Tab assets */
      $assets = self::get_facebook_assets();
      if( !empty( $assets['css'] ) && is_array( $assets['css'] ) ) {
        foreach ( $assets['css'] as $style_asset => $data ){
          wp_enqueue_style( $style_asset, $data[ 'url' ] );
        }
      }
      if( !empty( $assets['js'] ) && is_array( $assets['js'] ) ) {
        foreach ( $assets['js'] as $js_asset => $data ){
          wp_enqueue_script( $js_asset, $data[ 'url' ] );
        }
      }
      if ( isset( $current_canvas['allow_inline_css'] ) && $current_canvas['allow_inline_css']=='true' && !empty( $current_canvas['inline_css_data'] ) ){
        global $wpp_inline_css;
        $wpp_inline_css = WPP_F::minify_css( $current_canvas['inline_css_data'] );
        add_action('wp_head', create_function('', '
          global $wpp_inline_css;
          echo "<style type=\"text/css\">";
          echo $wpp_inline_css;
          echo "</style>";
        '));
        unset($wpp_inline_css);
      }

      //** Enqueue FB scripts */
      wp_enqueue_script('fb-properties', ud_get_wpp_fbtabs()->path( "static/scripts/fb_properties.js" ), array( 'jquery' ) );
      wp_localize_script( 'fb-properties', 'wpp_fb_tabs', array( 'canvas' => $wpp_fb_canvas, 'data' => json_encode( $current_canvas ) ) );

      //** */
      switch( $current_canvas[ 'use_page_as' ] ) {

        //** */
        case 'property':

          $property = WPP_F::get_property( $current_canvas[ 'page' ], "load_gallery=true" );

          if( empty( $property ) || is_wp_error( $property ) ) {
            //** Page is not specified */
            die( __('You did not specify the page for requested Canvas', ud_get_wpp_fbtabs()->domain) );
          }

          $property = prepare_property_for_display( $property );

          //** Make certain variables available to be used within the single listing page */
          $single_page_vars = apply_filters( 'wpp_property_page_vars', array(
            'property' => $property,
            'wp_properties' => $wp_properties
          ));

          //** By merging our extra variables into $wp_query->query_vars they will be extracted in load_template() */
          if( is_array( $single_page_vars ) ) {
            $wp_query->query_vars = array_merge( $wp_query->query_vars, $single_page_vars );
          }
          break;

        //** */
        case 'page':
          // There is nothing to do here for now
          break;

      }

      //** Remove unwanted meta from header */
      remove_action( 'wp_head', 'wlwmanifest_link');
      remove_action( 'wp_head', 'rsd_link');
      remove_action( 'wp_head', 'wp_enqueue_scripts');
      remove_action( 'wp_head', 'feed_links');
      remove_action( 'wp_head', 'feed_links_extra');
      remove_action( 'wp_head', 'rsd_link');
      remove_action( 'wp_head', 'wlwmanifest_link');
      remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head');
      remove_action( 'wp_head', 'locale_stylesheet');
      remove_action( 'wp_head', 'noindex');
      remove_action( 'wp_head', 'wp_print_styles');
      remove_action( 'wp_head', 'wp_print_head_scripts');
      remove_action( 'wp_head', 'wp_generator' );
      remove_action( 'wp_head', 'rel_canonical');
      remove_action( 'wp_head', 'wp_shortlink_wp_head');
      remove_action( 'wp_head', '_admin_bar_bump_cb' );

      //** Try find specific template */
      $template_found = WPP_F::get_template_part(array(
        $current_canvas['template'][ $current_canvas['use_page_as'] ]
      ), array( ud_get_wpp_fbtabs()->path( 'static/views', 'dir' ) ), array( 'instance' => 'default' ) );

      //** if page used as regular page - load the first found template */
      if( !empty( $template_found ) ) {
        load_template( $template_found );
        die();
      } else {
        //** Template file does not exists */
        die( __('Can\'t find proper Facebook Template file "'.$current_canvas['template'][ $current_canvas['use_page_as'] ].'"', ud_get_wpp_fbtabs()->domain) );
      }

    }


    /**
     * Deregisters all theme assets ( styles or scripts ) and specific ones which are not related to Facebook Tabs
     *
     * @param string $type. File extension. Available 'css','js'. Default is 'css'
     * @author peshkov@UD
     */
    static private function deregister_assets( $type = 'css' ) {

      $prefix = 'fb-tab-';
      $exceptions = array();
      $predefined = array();

      if( !in_array( $type, array( 'css', 'js' ) ) ) return false;

      switch( $type ) {
        case 'css':
          global $wp_styles;
          $inst = $wp_styles->registered;
          $exceptions = apply_filters( 'wpp::fb_tabs::exceptions::styles', array() );
          $predefined = apply_filters( 'wpp::fb_tabs::predefined::styles', array() );
          break;
        case 'js':
          global $wp_scripts;
          $inst = $wp_scripts->registered;
          $exceptions = apply_filters( 'wpp::fb_tabs::exceptions::scripts', array() );
          $predefined = apply_filters( 'wpp::fb_tabs::predefined::scripts', array( 'wp-property-global' ) );
          break;
        default:
          return false;
          break;
      }

      if( !is_array( $inst ) ) return false;

      $exceptions = self::get_asset_dependencies( array_unique( (array)$exceptions ), $type );

      foreach ( $inst as $key => $data ) {

        if( in_array( $key, $exceptions ) ) continue;

        $flag = false;

        //** Determine if the current asset is related to theme */
        if( is_string( $data->src ) && strpos( $data->src, 'wp-content/themes' ) !== false || strpos( $data->src, 'templates/theme-specific' ) !== false ) {
          preg_match( '#\/('.$prefix.'.+)\.'.$type.'$#', $data->src, $matches );
          if( empty( $matches ) ) {
            $flag = true;
          }
        }

        if( !$flag && in_array( $key, $predefined ) ) {
          $flag = true;
        }

        if( $flag ) {
          switch( $type ) {
            case 'css':
              if( wp_style_is( $key, 'registered' ) || wp_style_is( $key, 'enqueued' ) ) {
                @wp_deregister_style( $key );
              }
              break;
            case 'js':
              if( wp_script_is( $key, 'registered' ) || wp_script_is( $key, 'enqueued' ) ) {
                @wp_deregister_script( $key );
              }
              break;
          }
        }

      }

    }


    /**
     * Recursively goes through registered assets and finds dependencies of passed $assets
     *
     * @global type $wp_styles
     * @global type $wp_scripts
     * @param mixed $assets
     * @param string $type
     * @return array
     * @author peshkov@UD
     */
    static public function get_asset_dependencies( $assets, $type ) {

      if( !in_array( $type, array( 'css', 'js' ) ) ) return $assets;

      switch( $type ) {
        case 'css':
          global $wp_styles;
          $inst = $wp_styles->registered;
          break;
        case 'js':
          global $wp_scripts;
          $inst = $wp_scripts->registered;
          break;
      }

      if( !is_array( $assets ) ) {
        $assets = array( $assets );
      }

      $deps = array();
      foreach( $assets as $ex ) {
        if( !empty( $inst[ $ex ] ) && !empty( $inst[ $ex ]->deps ) && is_array( $inst[ $ex ]->deps ) ) {
          foreach( $inst[ $ex ]->deps as $dep ) {
            $dep = self::get_asset_dependencies( $dep, $type );
            $deps = array_unique( array_merge( $deps, (array)$dep ) );
          }
        }
      }

      $assets = array_unique( array_merge( $assets, $deps ) );

      return $assets;

    }


    /**
     * Adds Custom capability to the current premium feature
     *
     * @param array $capabilities
     * @return array
     */
    static public function add_capability($capabilities) {
      $capabilities[self::capability] = __('Manage Facebook Tabs',ud_get_wpp_fbtabs()->domain);

      return $capabilities;
    }


    /**
     * Echos full input name
     *
     * @param string $field_name
     * @param array $args
     * @return mixed
     * @author korotkov@ud
     */
    static public function post_name( $field_name, $args=array() ) {
      if ( empty( $field_name ) ) return;
      $return = false;
      $defaults = array(
        'return' => false
      );

      extract( wp_parse_args($args, $defaults) );

      if ( $return ) return self::post_name_base.trim($field_name);
      echo self::post_name_base.trim($field_name);
    }


    /**
     * Rewrite Rules - called only on flush.
     *
     * @action rewrite_rules_array ( 20 )
     * @author odokienko@UD
     */
    static public function _rewrite_rules( $rules ) {

      $rules = array( self::query_var . '/(.+?)/?$' => "index.php?" . self::query_var . "=\$matches[1]" ) + $rules;

      return $rules;
    }

    //

    /**
     * flush_rules() if our rules are not yet included
     *
     * @global object $wp_rewrite
     * @author odokienko@UD
     */
    static public function _flush_rules(){
      $rules = get_option( 'rewrite_rules' );

      if ( ! isset( $rules[ self::query_var . '/(.+?)/?$' ] ) ) {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
      }
    }


    /**
     * Tell WordPress to accept our custom query variable
     *
     * @param type $vars
     * @return array $vars
     * @author odokienko@UD
     */
    static public function _insertqv($vars) {
      array_push( $vars, self::query_var );
      array_push( $vars, 'signed_request' );
      return $vars;
    }


    /**
     * Creates $posts array based on fb_tabs canvas data
     *
     * @global type $wp
     * @global type $wp_query->query_vars[ self::query_var ]
     * @global array $wp_properties
     * @param array $posts
     * @return array $posts
     * @author odokienko@UD
     */
    static public function _wpp_fb_page( $posts ){
      global $wp_query, $wp_properties;

      if(
        !isset( $wp_query->query_vars[ self::query_var ] )
        || !isset( $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'][$wp_query->query_vars[ self::query_var ]] )
      ) {
        return $posts;
      }
      
      $current_canvas = $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'][$wp_query->query_vars[ self::query_var ]];

      //** check if user is requesting our fb_tabs page */
      if( !empty( $current_canvas['page'] ) ){

        $post = get_post($current_canvas['page']);

        $posts = NULL;
        $posts[] = $post;

        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;
        unset($wp_query->query["error"]);
        $wp_query->query_vars["error"]="";
        $wp_query->is_404 = false;
      }

      return $posts;
    }


    /**
     * Returns list of available templates.
     *
     * @return array
     * @author korotkov@ud
     */
    static public function get_templates() {

      $files     = array();
      $templates = array();

      //** Available template dirs */
      $files[ STYLESHEETPATH ] = scandir( STYLESHEETPATH );
      $files[ TEMPLATEPATH ]   = scandir( TEMPLATEPATH );
      $files[ ud_get_wpp_fbtabs()->path( 'static/views', 'dir' ) ]  = scandir( ud_get_wpp_fbtabs()->path( 'static/views', 'dir' ) );

      //** Find template files */
      foreach ( $files as $key => $file_list ) {

        //** Loop each file in dirs */
        foreach ( $file_list as $dirfile ) {
          $ext = pathinfo( $dirfile, PATHINFO_EXTENSION );
          //** If file is FT template */
          if ( strstr( $dirfile, 'fb-tab-' ) && $ext === 'php' ) {
            if( !isset( $templates[ $dirfile ] ) ) {
              $file_data = get_file_data( $key .'/'. $dirfile, array( 'name' => 'Template Name', 'type' => 'Type' ) );
              $file_data[ 'type' ] = !empty( $file_data[ 'type' ] ) ? $file_data[ 'type' ] : 'page';
              $templates[ str_replace( '.php', '', $dirfile ) ] = $file_data;
            }
          }
        }
      }

      return $templates;
    }


    /**
     * Returns list of facebook assets
     *
     * @param string $prefix
     * @author peshkov@UD
     */
    static public function get_facebook_assets( $prefix = '' ) {

      $assets = array();

      $prefix = ( !empty( $prefix ) && is_string( $prefix ) ) ? $prefix : 'fb-tab';

      //** Available template dirs */
      $files = array(
        'stylesheet' => array( STYLESHEETPATH, get_stylesheet_directory_uri(), scandir( STYLESHEETPATH ) ),
        'templatepath' => array( TEMPLATEPATH, get_template_directory_uri(), scandir( TEMPLATEPATH ) ),
      );

      //** Find template files */
      foreach ( $files as $key => $p ) {
        //** Loop each file in dirs */
        foreach ( $p[2] as $dirfile ) {
          preg_match( '#('.$prefix.'.*)\.(css|js)$#', $dirfile, $matches );

          if( !empty( $matches ) ) {
            if( empty( $assets[ $matches[2] ][ $matches[1] ] ) ) {
              $assets[ $matches[2] ][ sanitize_key($matches[1]) ] = array(
                'path' => $p[0] .'/'. $dirfile,
                'url' => $p[1] .'/'. $dirfile,
                'filename' => $dirfile,
              );
            }
          }
        }
      }

      //var_dump($assets);

      return $assets;
    }


    /**
     * Parse signed_request from Facebook
     *
     * @param string $signed_request
     * @param string $secret
     * @return array
     * @author korotkov@ud
     */
    static private function parse_signed_request($signed_request, $secret) {
      $signed_request = explode('.', $signed_request, 2);
      $encoded_sig = isset( $signed_request[0] ) ? $signed_request[0] : '';
      $payload = isset( $signed_request[1] ) ? $signed_request[1] : '';

      //** decode the data */
      $sig = self::base64_url_decode($encoded_sig);
      $data = json_decode(self::base64_url_decode($payload), true);

      if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
        return null;
      }

      //** check sig */
      $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
      if ($sig !== $expected_sig) {
        return null;
      }

      return $data;
    }


    /**
     * base64 URL Decode
     *
     * @param string $input
     * @return string
     * @author korotkov@ud
     */
    static private function base64_url_decode($input) {
      return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Get available pages to display
     *
     * @return array
     * @author korotkov@ud
     */
    static private function get_available_pages() {

      //** Temporary remove filter to get all pages */
      remove_filter('get_pages', array(__CLASS__, "hide_pages"));
      //** Get ALL pages */
      $pages = get_pages();
      //** Set filter back */
      add_filter('get_pages', array(__CLASS__, "hide_pages"));

      $page_data = array();

      foreach ($pages as $key => $page) {
        $page_data[$page->ID]['title'] = $page->post_title;
      }

      return $page_data;
    }


    /**
     * Collects affected page IDs
     *
     * @param array $canvases
     * @return array
     * @author korotkov@ud
     */
    static private function get_affected_pages( $canvases ) {

      //** Flush used variables */
      $pages = array();
      $ids   = array();

      //**  Loop canvases to look for pages used */
      if ( !empty( $canvases ) ) {
        foreach( $canvases as $canvas ) {

          //** If canvas used as page */
          if ( $canvas['use_page_as'] == 'page' ) {

            //** If it is not processed yet */
            if ( !in_array($canvas['page'], $ids) ) {

              //** Collect page data */
              $pages[] = array(
                'id' => $canvas['page'],
                'title' => get_page($canvas['page'])->post_title
              );
              //** Mark as processed to be unique */
              $ids[] = $canvas['page'];
            }
          }
        }
      }

      //** Return collected data */
      return $pages;

    }


    /**
     * Hide selected pages
     *
     * @global array $wp_properties
     * @param array $pages
     * @return array
     * @author korotkov@ud
     */
    static public function hide_pages( $pages ) {
      global $wp_properties;

      $hidden_pages = self::get_hidden_pages();

      if ( !empty( $hidden_pages ) && is_array( $hidden_pages ) ) {
        foreach( $pages as $key => $page ) {
          if ( in_array( $page->ID, $hidden_pages ) ) {
            unset($pages[$key]);
          }
        }
      }

      return $pages;
    }


    /**
     * Returns ids of hidden canvas pages
     *
     * @return array
     * @author peshkov@UD
     */
    static public function get_hidden_pages() {
      global $wp_properties;

      $ids = array();

      if( !empty( $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'] ) ) {
        foreach( (array)$wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'] as $canvas => $data ) {
          if( $data[ 'use_page_as' ] == 'page' && !empty( $data[ 'page' ] ) && $data[ 'settings' ][ 'hide_from_wp' ] == 'true' ) {
            $ids[] = $data[ 'page' ];
          }
        }
      }

      return $ids;
    }


    /**
     * Load admin scripts
     *
     * @author peshkov@UD
     */
    static public function admin_enqueue_scripts() {
      global $current_screen;

      // Load scripts on specific pages
      switch($current_screen->id)  {

        case 'property_page_facebook_tabs':
          wp_enqueue_script( 'wp-property-backend-global' );
          wp_enqueue_script( 'wpp-md5');
          wp_enqueue_script( 'jquery-ui-tabs' );
          break;
      }

    }


    /**
     *
     * @author peshkov@UD
     */
    static public function page_facebook_tabs() {
      global $wp_properties;

      $canvases = array();
      if( !empty( $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'] ) ) {
        $canvases = $wp_properties['configuration']['feature_settings']['facebook_tabs']['canvases'];
      } else {
        $canvases[ 'sample_canvas' ] = array(
          'name' => 'Sample Canvas',
          'app_id' => '',
          'secret' => '',
          'page_title' => '',
          'use_page_as' => '',
          'page' => '',
          'disable_default_css' => '',
          'allow_inline_css' => '',
          'inline_css_data' => '',
          'template' => array(
            'property' => '',
            'page' => '',
          ),
          'settings' => array(
            'hide_from_wp' => '',
            'enable_theme_templates' => '',
            'open_links_in_new_window' => '',
            'open_forms_in_new_window' => '',
          ),
        );
      }

      //** Templates */
      $templates = self::get_templates();

      //echo "<pre>"; print_r( $templates ); echo "</pre>"; die();
      
      //** The variables below are used by javascript */
      $is_permalink = get_option( 'permalink_structure' ) !== '' ? 'true' : 'false';
      $site_url = site_url();
      $query_var = self::query_var;

      $wp_messages = array();
      if(isset($_REQUEST['message'])) {
        switch($_REQUEST['message']) {
          case 'updated':
          $wp_messages['notice'][] = __( "Facebook Tabs updated.", ud_get_wpp_fbtabs()->domain );
          break;
        }
      }
      ?>

      <script type="text/javascript">
        var wpp_fb_tabs = {
          auto_complete_timer : null,
          is_permalink : <?php echo $is_permalink; ?>,
          site_url : '<?php echo $site_url; ?>',
          query_var : '<?php echo $query_var; ?>',

          //** Init basic actions */
          init : function() {
            var params = {
              create: function( event, ui ) {
                jQuery( '.wpp_add_tab' ).click( wpp_fb_tabs.add_canvas );
                jQuery( 'input.slug_setter' ).live( 'change', wpp_fb_tabs.set_slug );
                jQuery( '.wpp_fb_page_type' ).live( 'change', wpp_fb_tabs.update_fields_by_type ).trigger( 'change' );
                jQuery( 'input.wpp_fb_property_input' )
                  .live( 'keyup', wpp_fb_tabs.property_input_keyup )
                  .live( 'focus', wpp_fb_tabs.property_input_focus )
                  .live( 'blur', wpp_fb_tabs.property_input_blur )
                  .live( 'keydown', function(event){
                    if(event.keyCode == 13) {
                      event.preventDefault();
                      return false;
                    }
                  });
                jQuery( '.wpp_fb_app_id' ).live( 'change', wpp_fb_tabs.set_add_to_fb_link ).trigger( 'change' );
                jQuery( 'a.wpp_fb_tabs_property_link' ).live( 'click', wpp_fb_tabs.property_link_click );
                jQuery('.current_slug').live('change', wpp_fb_tabs.set_urls ).each( function( i, e ) {
                  jQuery( e ).trigger( 'change' );
                } );
                jQuery( '#save_form' ).show();
                wpp_fb_tabs.init_close_btn();
              }
            }
            if( !wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
              params.add = wpp_fb_tabs.canvas_added;
            }
            jQuery(".wpp_fb_tabs").tabs( params );
          },

          canvas_added : function( event, ui ) {
            jQuery( '.wpp_fb_tab table:first' ).clone().appendTo( ui.panel );
            wpp_fb_tabs.set_default_values( ui.panel );
            wpp_fb_tabs.init_close_btn();
          },

          add_canvas : function() {
            var new_tab_href_id = parseInt( Math.random()*1000000 );
            if( wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
              var tabs = jQuery(".wpp_fb_tabs"),
              ul = tabs.find( ">ul" ),
              index = tabs.find( '>ul >li').size(),
              panel = jQuery( '<div id="fb_form_' + new_tab_href_id + '"></div>' );

              jQuery( "<li><a href='#fb_form_" + new_tab_href_id + "'></a></li>" ).appendTo( ul );
              jQuery('.wpp_fb_tabs table:first').clone().appendTo( panel );
              panel.appendTo( tabs );
              tabs.tabs( "refresh" );

              wpp_fb_tabs.set_default_values( panel );
              wpp_fb_tabs.init_close_btn();

              tabs.tabs( "option", "active", index );
            } else {
              jQuery( '.wpp_fb_tabs' ).tabs( "add", "#fb_canvas_" + new_tab_href_id, '' );
              jQuery( '.wpp_fb_tabs' ).tabs( "select", jQuery(".wpp_fb_tabs").tabs( 'length' )-1 );
            }
          },

          set_slug : function( event ) {
            var value = jQuery( event.currentTarget ).val(),
                panel = jQuery( jQuery( event.currentTarget ).parents( 'div.ui-tabs-panel' ).get(0) ),
                old_slug = jQuery( 'input.current_slug', panel ).val(),
                new_slug = wpp_create_slug( value );

            jQuery( 'a[href="#'+ panel.attr('id') +'"]' ).html( value ).closest('li').attr( 'fb_canvas_id', new_slug );
            jQuery( 'input.current_slug', panel ).val( new_slug ).trigger( 'change' );

            // Cycle through all child elements and fix names
            jQuery( 'input,select, textarea', panel ).each( function(i,e) {
              var old_name = jQuery(e).attr('name');
              if ( typeof old_name != 'undefined' ) {
                var new_name =  old_name.replace('['+old_slug+']','['+new_slug+']');
                // Update to new name
                jQuery(e).attr('name', new_name);
              }
              var old_id = jQuery(e).attr('id');
              if( typeof old_id != 'undefined' ) {
                var new_id =  old_id.replace( old_slug, new_slug );
                jQuery(e).attr('id', new_id);
              }
              // Cycle through labels too
              jQuery( 'label', panel ).each(function(i,e) {
                if( typeof jQuery(e).attr('for') != 'undefined' ) {
                  var old_for = jQuery(e).attr('for');
                  var new_for =  old_for.replace(old_slug,new_slug);
                  // Update to new name
                  jQuery(e).attr('for', new_for);
                }
              });
            });
          },

          //** Set URLs for canvases */
          set_urls : function() {
            var slug = jQuery( this ).val(),
                panel = jQuery( jQuery( this ).parents( 'div.ui-tabs-panel' ).get(0) ),
                url = wpp_fb_tabs.site_url,
                secure_url,
                debug_url;
            if( wpp_fb_tabs.is_permalink ) {
              url += '/' + wpp_fb_tabs.query_var + '/' + slug + '/';
              debug_url = url + '?signed_request=' + md5( 'debug::' + slug ) + '.' + md5( 'debug::payload' );
            } else {
              url += '?' + wpp_fb_tabs.query_var + '=' + slug;
              debug_url = url + '&signed_request=' + md5( 'debug::' + slug ) + '.' + md5( 'debug::payload' );
            }
            secure_url = url.replace( 'http://', 'https://' );

            jQuery( 'input.default_canvas_url', panel ).val( url );
            jQuery( 'input.secure_canvas_url', panel ).val( secure_url );
            jQuery( 'input.debug_canvas_url', panel ).val( debug_url );
          },

          set_default_values : function( ui ) {
            jQuery( 'input.wpp_default_empty[type="text"]', ui ).val( '' );
            jQuery( 'input.wpp_default_empty[type="checkbox"]', ui ).attr( 'checked', false );
            jQuery( '.wpp_fb_page_type', ui ).val( 'page' ).trigger( 'change' );
            jQuery( 'input.slug_setter', ui ).val( '<?php _e( 'Unnamed Canvas', ud_get_wpp_fbtabs()->domain ); ?>' ).trigger( 'change' );
          },

          set_add_to_fb_link : function( event ) {
            var panel = jQuery( jQuery( event.currentTarget ).parents( 'div.ui-tabs-panel' ).get(0) );
            var value = jQuery( event.currentTarget ).val();
            var button = jQuery( 'a.wpp_fb_tabs_add_to_page', panel );
            button.attr( 'href', 'https://www.facebook.com/dialog/pagetab?app_id=' + value + '&redirect_uri=http%3A%2F%2Fwww.facebook.com' );
            if( value == '' ) button.hide();
            else button.show();
          },

          update_fields_by_type : function( event ) {
            var panel = jQuery( jQuery( event.currentTarget ).parents( 'div.ui-tabs-panel' ).get(0) );
            var value = jQuery( event.currentTarget ).val();
            switch( value ) {
              case 'page':
                jQuery( '.wpp_fb_type_property', panel ).hide().attr( 'disabled', 'disabled' );
                jQuery( '.wpp_fb_type_page', panel ).show().removeAttr( 'disabled' );
                break;
              case 'property':
                jQuery( '.wpp_fb_type_page', panel ).hide().attr( 'disabled', 'disabled' );
                jQuery( '.wpp_fb_type_property', panel ).show().removeAttr( 'disabled' );
                break;
            }
          },

          init_close_btn : function() {
            // Add remove button for tabs
            jQuery('ul.tabs li.ui-state-default:not(:first):not(:has(a.remove-tab))')
              .append('<a href="javascript:void(0);" class="remove-tab">x</a>')
              .mouseenter(function(){
                jQuery('a.remove-tab', this).show();
              })
              .mouseleave(function(){
                jQuery('a.remove-tab', this).hide();
              });

            // On remove tab button click
            jQuery('ul.tabs li a.remove-tab').unbind('click');
            jQuery('ul.tabs li a.remove-tab').click(function( e ){
              if( wpp.version_compare( jQuery.ui.version, '1.10', '>=' ) ) {
                var index = jQuery(this).parent().index();
                if( jQuery( '.wpp_fb_tabs' ).tabs( 'option', 'active' ) == index ) {
                  jQuery( '.wpp_fb_tabs' ).tabs( "option", "active", index-1 );
                }
                // Remove the tab
                var tab = jQuery( ".wpp_fb_tabs" ).find( ".ui-tabs-nav li:eq(" + index + ")" ).remove();
                // Find the id of the associated panel
                var panelId = tab.attr( "aria-controls" );
                // Remove the panel
                jQuery( "#" + panelId ).remove();
                // Refresh the tabs widget
                jQuery( ".wpp_feps_tabs" ).tabs( "refresh" );
              } else {
                jQuery(".wpp_fb_tabs").tabs( 'remove', jQuery( e ).parent().index() );
              }
            });
          },

          //** Process keyup */
          property_input_keyup : function() {
            var typing_timeout = 600;
            var input = jQuery(this);
            var panel = input.parents( 'div.ui-tabs-panel' ).get(0);
            jQuery( '.wpp_fb_tabs_found_properies', panel ).hide().empty();
            window.clearTimeout( wpp_fb_tabs.auto_complete_timer );
            wpp_fb_tabs.auto_complete_timer = window.setTimeout( function(){
              jQuery( '.wpp_fb_tabs_loader_image', panel ).show();
              jQuery.post(
                wpp.instance.ajax_url,
                {
                  action: 'wpp_fb_tabs_get_properties',
                  s: input.val()
                },
                function( response ) {
                  jQuery( '.wpp_fb_tabs_loader_image', panel ).hide();
                  if ( response && typeof response == 'object' ) {
                    jQuery.each(response, function(){
                      jQuery( '.wpp_fb_tabs_found_properies', panel )
                        .width(input.outerWidth())
                        .append( '<li><a class="wpp_fb_tabs_property_link" href="'+this.id+'">'+this.title+'</a></li>' )
                        .show();
                    });
                  }
                }, 'json'
              );
            }, typing_timeout);
          },

          //** Process focus */
          property_input_focus : function() {
            var panel = jQuery(this).parents( 'div.ui-tabs-panel' ).get(0);
            jQuery( '.wpp_fb_tabs_found_properies', panel ).hide().empty();
          },

          //** Process blur */
          property_input_blur : function() {
            var panel = jQuery(this).parents( 'div.ui-tabs-panel' ).get(0);
            jQuery( '.wpp_fb_tabs_found_properies', panel ).delay(300).queue(function(){
              jQuery(this).hide().empty();
            });
          },

          //** Process click */
          property_link_click : function() {
            var a = jQuery(this);
            var panel = a.parents( 'div.ui-tabs-panel' ).get(0);
            jQuery( '.wpp_fb_property_input', panel ).val(a.text());
            jQuery( '.wpp_fb_property_input_hidden', panel ).val(a.attr('href'));
            jQuery( '.wpp_fb_tabs_found_properies', panel ).hide().empty();
            return false;
          }
        }

        jQuery(document).ready( wpp_fb_tabs.init );
      </script>


      <div id="wpp_facebook_canvases" class="wrap wpp_facebook_wrapper wpp_settings_page">
        <h2><?php _e( 'Facebook Tabs', ud_get_wpp_fbtabs()->domain ); ?>
        <span class="wpp_add_tab add-new-h2"><?php _e('Add New', ud_get_wpp_fbtabs()->domain); ?></span>
        </h2>

        <p><?php _e('Apps on Facebook are web apps that are loaded in the context of Facebook in what we refer to as a Canvas Page.', ud_get_wpp_fbtabs()->domain); ?></p>

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

        <form id="save_form" class="hidden" action="<?php echo admin_url('edit.php?post_type=property&page=facebook_tabs'); ?>" method="POST">

          <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_save_facebook_tabs_page'); ?>" />
          <input class="current_tab" type="hidden" name="current_tab" value="" />

          <div class="wpp_fb_tabs wpp_tabs">

            <ul class="tabs">
              <?php foreach( $canvases as $slug => $canvas ) : ?>
                <li fb_canvas_id="<?php echo $slug; ?>"><a href="#fb_canvas_<?php echo $slug; ?>"><span><?php echo $canvas['name']; ?></span></a></li>
              <?php endforeach; ?>
            </ul>

            <?php foreach( $canvases as $slug => $canvas ) : ?>
            <div id="fb_canvas_<?php echo $slug; ?>" class="wpp_fb_tab ui-tabs-panel" feps_form_id="<?php echo esc_attr($slug); ?>">

              <!-- Settings form table -->
              <table class="form-table wpp_option_table">
                <tr>
                  <th>
                    <p><strong><?php _e('Name:', ud_get_wpp_fbtabs()->domain); ?></strong></p>
                    <div class="description">
                      <p>
                        <?php _e('Note that Name is used for canvas link generation ( see Facebook Application Setup options below ).', ud_get_wpp_fbtabs()->domain); ?>
                      </p>
                    </div>
                  </th>
                  <td>
                    <input class="slug_setter" type="text" name="wpp_fb[<?php echo $slug; ?>][name]" value="<?php echo $canvas['name']; ?>" />
                    <input class="current_slug hidden" type="text" value="<?php echo $slug; ?>" readonly="readonly" />
                  </td>
                </tr>
                <tr>
                  <th>
                    <p><strong><?php _e('Page:', ud_get_wpp_fbtabs()->domain); ?></strong></p>
                    <div class="description">
                      <p>
                        <?php _e('Set the page which will be shown in your Facebook Application/Tab canvas.', ud_get_wpp_fbtabs()->domain); ?>
                      </p>
                    </div>
                  </th>
                  <td>
                    <ul class="wpp_fb_canvas_options_list">
                      <li>
                        <label><?php _e( 'Type', ud_get_wpp_fbtabs()->domain ); ?></label>
                        <select class="wpp_fb_page_type" name="wpp_fb[<?php echo $slug; ?>][use_page_as]">
                          <option value="page" <?php echo $canvas['use_page_as']=='page'?'selected="selected"':''; ?> ><?php _e( 'Page', ud_get_wpp_fbtabs()->domain ); ?></option>
                          <option value="property" <?php echo $canvas['use_page_as']=='property'?'selected="selected"':''; ?> ><?php printf(__('%1$s', ud_get_wpp_fbtabs()->domain), ucfirst(WPP_F::property_label('singular'))); ?></option>
                        </select>
                        <span class="description"><?php _e( "Note: Select the type to determine the available pages below.", ud_get_wpp_fbtabs()->domain ); ?></span>
                      </li>
                      <li>
                        <label><?php _e( 'Page', ud_get_wpp_fbtabs()->domain ); ?></label>
                        <select class="wpp_fb_page_select wpp_fb_type_page" name="wpp_fb[<?php echo $slug; ?>][page]">
                          <?php foreach ( self::get_available_pages() as $page_key => $page) : ?>
                            <option <?php echo $canvas['page']==$page_key?'selected="selected"':''; ?> value="<?php echo $page_key; ?>"><?php echo $page['title']; ?></option>
                          <?php endforeach; ?>
                        </select>
                        <input value="<?php echo $canvas['page']; ?>" type="hidden" class="wpp_default_empty wpp_fb_property_input_hidden wpp_fb_type_property" name="wpp_fb[<?php echo $slug; ?>][page]" />
                        <input autocomplete="off" placeholder="<?php _e('Search...', ud_get_wpp_fbtabs()->domain); ?>" value="<?php echo $canvas['page_title']; ?>" type="text" class="wpp_default_empty wpp_fb_property_input wpp_fb_type_property" name="wpp_fb[<?php echo $slug; ?>][page_title]" />
                        <img class="wpp_fb_tabs_loader_image hidden" src="<?php echo ud_get_wpp_fbtabs()->path( 'static/images/ajax_loader.gif' ); ?>" />
                        <ul class="wpp_fb_tabs_found_properies hidden"></ul>
                      </li>
                      <li class="wpp_fb_type_page">
                        <?php echo WPP_F::checkbox("class=wpp_default_empty&name=wpp_fb[{$slug}][settings][hide_from_wp]&label=" . __('Hide the selected page from Regular WordPress.',ud_get_wpp_fbtabs()->domain), $canvas['settings']['hide_from_wp']); ?>
                        <span class="description"><?php _e( "Note: Hidden page will not be accessible in other Premium Features too.", ud_get_wpp_fbtabs()->domain ); ?></span>
                      </li>
                    </ul>
                  </td>
                </tr>
                <tr>
                  <th>
                    <p><strong><?php _e('Template Settings:', ud_get_wpp_fbtabs()->domain); ?></strong></p>
                    <div class="description">
                      <p>
                        <?php _e('Set the template, CSS and other options for your Facebook Application/Tab canvas.', ud_get_wpp_fbtabs()->domain); ?>
                      </p>
                    </div>
                  </th>
                  <td>
                    <ul class="wpp_fb_canvas_options_list">
                      <li>
                        <label class="wpp_fb_template_label"><?php _e('Template:', ud_get_wpp_fbtabs()->domain); ?></label>
                        <select class="wpp_default_empty wpp_fb_template_page wpp_fb_template_select wpp_fb_type_page" name="wpp_fb[<?php echo $slug; ?>][template][page]">
                          <?php foreach ( $templates as $key => $template ) : ?>
                            <?php if( $template[ 'type' ] !== 'page' ) continue; ?>
                            <option <?php echo ( isset( $canvas['template']['page'] ) && $key == $canvas['template']['page'] ) ? 'selected="selected"' : ''; ?> value="<?php echo $key; ?>"><?php _e($template['name'], ud_get_wpp_fbtabs()->domain); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <select class="wpp_default_empty wpp_fb_template_property wpp_fb_template_select wpp_fb_type_property" name="wpp_fb[<?php echo $slug; ?>][template][property]">
                          <?php foreach ( $templates as $key => $template ) : ?>
                            <?php if( $template[ 'type' ] !== 'property' ) continue; ?>
                            <option <?php echo ( isset( $canvas['template']['property'] ) && $key == $canvas['template']['property'] ) ? 'selected="selected"' : ''; ?> value="<?php echo $key; ?>"><?php _e($template['name'], ud_get_wpp_fbtabs()->domain); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <span class="description"><?php _e( "Note: The list of available templates depends on selected page type. You can add your own templates. See help link above.", ud_get_wpp_fbtabs()->domain ); ?></span>
                      </li>
                      <li>
                        <?php echo WPP_F::checkbox("class=wpp_default_empty&name=wpp_fb[{$slug}][settings][enable_theme_templates]&label=" . __('Enable theme\'s template parts.',ud_get_wpp_fbtabs()->domain), $canvas['settings']['enable_theme_templates']); ?>
                        <span class="description"><?php _e( "Note: canvas uses default WP-Property template parts by default. If enabled, application will try to load parts from theme folder. But custom part templates can be incompatible with default CSS styles.", ud_get_wpp_fbtabs()->domain ); ?></span>
                      </li>
                      <li>
                        <?php echo WPP_F::checkbox("class=wpp_default_empty&name=wpp_fb[{$slug}][settings][open_links_in_new_window]&label=" . __('Open links in new window.',ud_get_wpp_fbtabs()->domain), $canvas['settings']['open_links_in_new_window']); ?>
                      </li>
                      <li>
                        <?php echo WPP_F::checkbox("class=wpp_default_empty&name=wpp_fb[{$slug}][settings][open_forms_in_new_window]&label=" . __('Open forms in new window.',ud_get_wpp_fbtabs()->domain), $canvas['settings']['open_forms_in_new_window']); ?>
                      </li>
                      <li>
                        <?php echo WPP_F::checkbox("class=wpp_default_empty&name=wpp_fb[$slug][disable_default_css]&label=" . __('Disable default CSS.',ud_get_wpp_fbtabs()->domain), $canvas['disable_default_css']); ?>
                      </li>
                      <li>
                        <input class="wpp_fb_allow_inline_css wpp_show_advanced" wrapper="wpp_fb_canvas_options_list" advanced_option_class="wpp_advanced_option" id="wpp_fb_allow_inline_css_<?php echo $slug; ?>" type="checkbox" name="wpp_fb[<?php echo $slug; ?>][allow_inline_css]" <?php if( isset( $canvas['allow_inline_css'] ) ) checked( $canvas['allow_inline_css'], 'true' );?> value="true" />
                        <label for="wpp_fb_allow_inline_css_<?php echo $slug; ?>"><?php _e('Allow inline CSS.', ud_get_wpp_fbtabs()->domain); ?></label>
                      </li>
                      <li class="wpp_advanced_option" <?php echo ( !isset( $canvas['allow_inline_css'] ) || 'true' !== $canvas['allow_inline_css'] ) ? " style='display:none' ":'';?>>
                        <label for="wpp_fb_inline_css_data_<?php echo $slug; ?>"><?php _e('Inline CSS:', ud_get_wpp_fbtabs()->domain); ?></label><br>
                        <textarea id="wpp_fb_inline_css_data_<?php echo $slug; ?>" name="wpp_fb[<?php echo $slug; ?>][inline_css_data]" type="text" class="wpp_full_width wpp_default_empty" ><?php echo isset( $canvas['inline_css_data'] ) ? $canvas['inline_css_data'] : ''; ?></textarea>
                      </li>
                    </ul>
                  </td>
                </tr>
                <tr>
                  <th>
                    <p><strong><?php _e('Facebook Application Setup:', ud_get_wpp_fbtabs()->domain); ?></strong></p>
                    <div class="description">
                      <p><?php _e( 'Applicable for both Application and Page Tab', ud_get_wpp_fbtabs()->domain ); ?></p>
                      <p><?php _e('You will need to create an app using your Facebook account.  Once the app is created, you will be able to add it to one of your pages.', ud_get_wpp_fbtabs()->domain); ?> <a target="_blank" href="https://developers.facebook.com/apps"><?php _e('Create Facebook App.', ud_get_wpp_fbtabs()->domain); ?></a></p>
                    </div>
                  </th>
                  <td>
                    <ul class="wpp_fb_canvas_options_list">
                      <li>
                        <label for="wpp_canvas_app_id_<?php echo $slug; ?>"><?php _e('App ID:', ud_get_wpp_fbtabs()->domain); ?></label>
                        <input class="wpp_default_empty wpp_fb_app_id" id="wpp_canvas_app_id_<?php echo $slug; ?>" type="text" name="wpp_fb[<?php echo $slug; ?>][app_id]" value="<?php echo $canvas['app_id']; ?>" />
                        <a target="_blank" class="button-secondary btn wpp_fb_tabs_add_to_page" style="display:none;" href=""><?php _e('Add to Facebook', ud_get_wpp_fbtabs()->domain); ?></a>
                      </li>
                      <li>
                        <label for="wpp_canvas_secret_<?php echo $slug; ?>"><?php _e('Secret:', ud_get_wpp_fbtabs()->domain); ?></label>
                        <input class="wpp_default_empty" id="wpp_canvas_secret_<?php echo $slug; ?>" type="text" name="wpp_fb[<?php echo $slug; ?>][secret]" value="<?php echo $canvas['secret']; ?>" />
                      </li>
                    </ul>
                    <ul class="wpp_fb_canvas_urls">
                      <li>
                        <label><?php _e('Canvas URL:', ud_get_wpp_fbtabs()->domain); ?></label>
                        <input type="text" class="default_canvas_url wpp_default_empty" readonly="readonly" value="" />
                        <small><i></i></small>
                      </li>
                      <li>
                        <label><?php _e('Secure Canvas URL:', ud_get_wpp_fbtabs()->domain); ?></label>
                        <input type="text" class="secure_canvas_url wpp_default_empty" readonly="readonly" value="" />
                      </li>
                      <li>
                        <label><?php _e('Debug URL:', ud_get_wpp_fbtabs()->domain); ?></label>
                        <input type="text" class="debug_canvas_url wpp_default_empty" readonly="readonly" value="" />
                      </li>
                    </ul>
                  </td>
                </tr>
              </table>
              <!-- EO Settings form table -->

            </div>
            <div class="clear"></div>
            <?php endforeach; ?>
          </div>
          <br class="cb" />
          <p class="wpp_save_changes_row">
            <input type="submit"  value="<?php _e( 'Save Changes',ud_get_wpp_fbtabs()->domain );?>" class="button-primary btn" name="Submit" />
          </p>
        </form>

      </div>
      <?php
    }

  }

}
