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

        //** Init Settings */
        $this->settings = new Settings( array(
          'key'  => 'wpp_settings',
          'store'  => 'options',
          'data' => array(
            'name' => $this->name,
            'version' => $this->args[ 'version' ],
            'domain' => $this->domain,
          )
        ));

        //** Initiate Attributes Handler */
        new Attributes();

        /** Legacy filters and hooks */
        include_once $this->path( 'lib/default_api.php', 'dir' );
        /** Loads general functions used by WP-Property */
        include_once $this->path( 'lib/class_functions.php', 'dir' );
        /** Loads Admin Tools feature */
        include_once $this->path( 'lib/class_admin_tools.php', 'dir' );
        /** Loads export functionality */
        include_once $this->path( 'lib/class_property_export.php', 'dir' );
        /** Loads all the metaboxes for the property page */
        include_once $this->path( 'lib/class_core.php', 'dir' );
        /** Load set of static methods for mail notifications */
        include_once $this->path( 'lib/class_mail.php', 'dir' );
        /** Load in hooks that deal with legacy and backwards-compat issues */
        include_once $this->path( 'lib/class_legacy.php', 'dir' );

        //** Initiate AJAX Handler */
        new Ajax();

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
         *
         */

        //** Initiate the plugin */
        $this->core = new \WPP_Core();
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
        //** flush Rewrite Rules */
        flush_rewrite_rules();
        //** flush Object Cache */
        wp_cache_flush();
      }
      
      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {
        //** flush Rewrite Rules */
        flush_rewrite_rules();
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
        /* Do automatic Settings backup! */
        $settings = get_option( 'wpp_settings' );

        if( !empty( $settings ) ) {

          /**
           * Fixes allowed mime types for adding download files on Edit Product page.
           *
           * @see https://wordpress.org/support/topic/2310-download-file_type-missing-in-variations-filters-exe?replies=5
           * @author peshkov@UD
           */
          add_filter( 'upload_mimes', function( $t ){
            if( !isset( $t['json'] ) ) {
              $t['json'] = 'application/json';
            }
            return $t;
          }, 99 );

          $filename = md5( 'wpp_settings_backup' ) . '.json';
          $upload = wp_upload_bits( $filename, null, json_encode( $settings ) );

          if( !empty( $upload ) && empty( $upload[ 'error' ] ) ) {
            if( isset( $upload[ 'error' ] ) ) unset( $upload[ 'error' ] );
            $upload[ 'version' ] = $this->old_version;
            $upload[ 'time' ] = time();
            update_option( 'wpp_settings_backup', $upload );
          }

        }

        /**
         * WP-Property 1.42.4 and less compatibility
         */
        update_option( "wpp_version", $this->args['version'] );

        do_action( $this->slug . '::upgrade', $this->old_version, $this->args[ 'version' ], $this );
      }

    }

  }

}
