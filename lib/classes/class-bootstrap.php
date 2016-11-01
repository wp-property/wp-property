<?php
/**
 * Bootstrap
 *
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {


  if( !class_exists( 'UsabilityDynamics\WPP\Bootstrap' ) ) {

    final class Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {

      public $core;

      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type \UsabilityDynamics\WPP\Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {
        global $wp_properties;

        /**
         * Duplicates UsabilityDynamics\WP\Bootstrap_Plugin::load_textdomain();
         *
         * There is a bug with localisation in lib-wp-bootstrap 1.1.3 and lower.
         * So we load textdomain here again, in case old version lib-wp-bootstrap is being loaded
         * by another plugin.
         *
         * @since 2.0.2
         */
        load_plugin_textdomain( $this->domain, false, dirname( plugin_basename( $this->boot_file ) ) . '/static/languages/' );

        /** This Version  */
        if( !defined( 'WPP_Version' ) ) {
          define( 'WPP_Version', $this->args[ 'version' ] );
        }

        /** Loads general functions used by WP-Property */
        include_once $this->path( 'lib/class_functions.php', 'dir' );
        /** Loads Admin Tools feature */
        include_once $this->path( 'lib/class_admin_tools.php', 'dir' );
        /** Loads all the metaboxes for the property page */
        include_once $this->path( 'lib/class_core.php', 'dir' );
        /** Load set of static methods for mail notifications */
        include_once $this->path( 'lib/class_mail.php', 'dir' );
        /** Load in hooks that deal with legacy and backwards-compat issues */
        include_once $this->path( 'lib/class_legacy.php', 'dir' );

        $upload_dir = wp_upload_dir();

        //** Init Settings */
        $this->settings = new Settings( array(
          'key'  => 'wpp_settings',
          'store'  => 'options',
          'data' => array(
            'name' => $this->name,
            'version' => $this->args[ 'version' ],
            'domain' => $this->domain,
            'cache_dir' => $upload_dir[ 'basedir' ] . '/wpp_cache',
            'cache_url' => $upload_dir[ 'baseurl' ] . '/wpp_cache',
          )
        ));

        //** Initiate Attributes Handler */
        new Attributes();

        //** Initiate AJAX Handler */
        new Ajax();

        //** Handles Export (XML/JSON/CSV) functionality */
        new Export();
        
        /** Initiate WPML class if WPML plugin activated. **/
        if( function_exists('icl_object_id') ){
          new \UsabilityDynamics\WPP\WPML();
        }
        //** Initiate Admin UI */
        if( is_admin() ) {
          //** Initiate Admin Handler */
          new Admin();
          //** Initiate Meta Box Handler */
          new Meta_Box();
          //** Setup Gallery Meta Box ( wp-gallery-metabox ) */
          add_action( 'be_gallery_metabox_post_types', function ( $post_types = array() ) { return array( 'property' ); } );
          add_filter( 'be_gallery_metabox_remove', '__return_false' );
        }

        /**
         * Load WP List Table library.
         */
        new \UsabilityDynamics\WPLT\Bootstrap();

        /**
         * May be load Shortcodes
         */
        add_action( 'init', function() {
          ud_get_wp_property()->load_files( ud_get_wp_property()->path('lib/shortcodes', 'dir') );
        }, 999 );

        /**
         * May be load Widgets
         */
        add_action( 'widgets_init', function() {
          ud_get_wp_property()->load_files( ud_get_wp_property()->path('lib/widgets', 'dir') );
        }, 1 );

	      /** Legacy filters and hooks */
        include_once $this->path( 'lib/default_api.php', 'dir' );

        /**
         * Initiate the plugin
         */
        $this->core = new \WPP_Core();

        /**
         * Flush WP-Property cache
         */
        if( get_transient( 'wpp_cache_flush' ) ) {
          \WPP_F::clear_cache();
          delete_transient( 'wpp_cache_flush' );
        }

        // Handle forced pre-release update checks.
        if ( is_admin() && isset( $_GET[ 'force-check' ] ) && $_GET[ 'force-check' ] === '1' ) {
          add_filter( 'site_transient_update_plugins', array( 'UsabilityDynamics\WPP\Bootstrap', 'update_check_handler' ), 50, 2 );
        }

        // Handle regular pre-release checks.
        add_filter( 'pre_update_site_option__site_transient_update_plugins', array( 'UsabilityDynamics\WPP\Bootstrap', 'update_check_handler' ), 50, 2 );

        //
        if ( apply_filters( 'wpp::layouts::enabled', false ) ) new Layouts();

      }

      /**
       * Includes all PHP files from specific folder
       *
       * @param string $dir Directory's path
       * @author peshkov@UD
       */
      public function load_files($dir = '') {
        $dir = trailingslashit($dir);
        if (!empty($dir) && is_dir($dir)) {
          if ($dh = opendir($dir)) {
            while (( $file = readdir($dh) ) !== false) {
              if (!in_array($file, array('.', '..')) && is_file($dir . $file) && 'php' == pathinfo($dir . $file, PATHINFO_EXTENSION)) {
                include_once( $dir . $file );
              }
            }
            closedir($dh);
          }
        }
      }
      
      /**
       * Return localization's list.
       *
       * @author peshkov@UD
       * @return array
       */
      public function get_localization() {
        return apply_filters( 'wpp::get_localization', array(
          'licenses_menu_title' => __( 'Add-ons', $this->domain ),
          'licenses_page_title' => __( 'WP-Property Add-ons Manager', $this->domain ),
        ) );
      }
      
      /**
       * Plugin Activation
       *
       */
      public function activate() {
        //** flush Object Cache */
        wp_cache_flush();
        //** set transient to flush WP-Property cache */
        set_transient( 'wpp_cache_flush', time() );
      }
      
      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {
        //** flush Object Cache */
        wp_cache_flush();
      }

      /**
       * Run Install Process.
       *
       * @param string $old_version Old version.
       * @author peshkov@UD
       */
      public function run_install_process() {
        /* Compatibility with WP-Property 1.42.4 and less versions */
        $old_version = get_option( 'wpp_version' );
        if( $old_version ) {
          $this->run_upgrade_process();
        }
      }

      /**
       * Run Upgrade Process:
       * - do WP-Property settings backup.
       *
       * @author peshkov@UD
       */
      public function run_upgrade_process() {
        Upgrade::run( $this->old_version, $this->args['version'] );
      }

      /**
       * Check pre-release updates.
       *
       * @todo Refine the "when-to-check" logic. Right now multple requests may be made per request.
       *
       * @author potanin@UD
       *
       * @param $response
       * @param $old_value
       *
       * @return mixed
       */
      static public function update_check_handler( $response, $old_value = null ) {
        global $wp_properties;

        // if ( current_filter() === 'pre_update_site_option__site_transient_update_plugins' ) {}
        // if ( current_filter() === 'site_transient_update_plugins' ) {}

        if ( ! $response || !isset( $response->response ) || ! is_array( $response->response ) || ! isset( $wp_properties ) || ! isset( $wp_properties[ 'configuration' ][ 'pre_release_update' ] ) ) {
          return $response;
        }

        // If pre-release update checks are disabled, do nothing.
        if ( $wp_properties[ 'configuration' ][ 'pre_release_update' ] !== 'true' ) {
          return $response;
        }

        // Last check was very recent. (This doesn't seem to be right place for this). That being said, if it's being forced, we ignore last time we tried.
        if ( current_filter() === 'site_transient_update_plugins' && !( isset( $_GET[ 'force-check' ] ) && $_GET[ 'force-check' ] === '1' ) && $response->last_checked && ( time() - $response->last_checked ) < 360 ) {
            return $response;
        }

        // e.g. "wp-property", the clean directory name that we are runnig from.
        $_plugin_name = plugin_basename( dirname( dirname( __DIR__ ) ) );

        // e.g. "wp-property/wp-property.php". Directory name may vary but the main plugin file should not.
        $_plugin_local_id = $_plugin_name . '/wp-property.php';

        // Bail, no composer.json file, something broken badly.
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $_plugin_name . '/composer.json' ) ) {
          return $response;
        }

        try {

          // Must be able to parse composer.json from plugin file, hopefully to detect the "_build.sha" field.
          $_composer = json_decode( file_get_contents( WP_PLUGIN_DIR . '/' . $_plugin_name . '/composer.json' ) );

          if ( is_object( $_composer ) && isset( $_composer->extra ) && isset( $_composer->extra->_build ) && isset( $_composer->extra->_build->sha ) ) {
            $_version = $_composer->extra->_build->sha;
          }

          // @todo Allow for latest branch to be swapped out for another track.
          $_response = wp_remote_get( 'https://api.usabilitydynamics.com/v1/product/updates/' . $_plugin_name . '/latest/' . ( isset( $_version ) && $_version ? '?version=' . $_version : '' ), array(
            "headers" => array(
              // "x-set-branch"=> "staging",
              // "cache-control"=> "no-cache",
              // "pragma"=> "no-cache"
            )
          ) );

          if ( wp_remote_retrieve_response_code( $_response ) === 200 ) {
            $_body = wp_remote_retrieve_body( $_response );
            $_body = json_decode( $_body );

            // If there is no "data" field then we have nothing to update.
            if ( isset( $_body->data ) ) {

              if( !isset( $response->response ) ) {
                $response->response = array();
              }

              if( !isset( $response->no_update ) ) {
                $response->no_update = array();
              }

              $response->response[ $_plugin_local_id ] = $_body->data;

              if ( isset( $response->no_update[ $_plugin_local_id ] ) ) {
                unset( $response->no_update[ $_plugin_local_id ] );
              }

            }

          }

        } catch( \Exception $e ) {}

        return $response;

      }


    }

  }

}
