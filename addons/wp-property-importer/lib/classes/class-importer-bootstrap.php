<?php
/**
 * Bootstrap
 *
 * @since 5.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Importer_Bootstrap' ) ) {

    final class Importer_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {


      public $cron;

      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPP\Importer_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {

        //** @todo: get rid of the constant below. peshkov@UD */
        if( !defined( 'WPP_XMLI_Version' ) ) {
          define( 'WPP_XMLI_Version', $this->args['version'] );
        }

        require_once( dirname( __DIR__ ) . '/class-wpp-rets.php' );
        require_once( dirname( __DIR__ ) . '/class-gc-import.php' );
        require_once( dirname( __DIR__ ) . '/class-wpp-cli-xmli.php' );
        require_once( dirname( __DIR__ ) . '/class-wpp-property-import.php' );

        add_action( 'wpp_init', array( 'class_wpp_property_import', 'pre_init' ) );
        add_action( 'wpp_post_init', array( 'class_wpp_property_import', 'init' ) );

        //** @todo: What a hell is it? peshkov@UD */
        if( !is_admin() ) {
          do_action( 'wpp_init' );
        }

        //** Adds WP-Cron Management and handler */
        $this->cron = new Importer_Cron();

        add_filter( 'wpp::js::localization', array( $this, 'filter_js_localization' ) );
      }

      /**
       *
       */
      public function filter_js_localization( $l10n ) {
        $l10n[ 'xmli' ] = array(
          'request_error'                 => __( 'Request error:', $this->domain ),
          'evaluation_500_error'          => __( 'The source evaluation resulted in an Internal Server Error!', $this->domain ),
          'automatically_match'           => __( 'Automatically Match', $this->domain ),
          'unique_id_attribute'           => __( 'Unique ID attribute.', $this->domain ),
          'select_unique_id'              => __( 'Select a unique ID attribute.', $this->domain ),
          'settings'                      => __( 'Settings', $this->domain ),
          'enabled_options'               => __( 'Enabled Options', $this->domain ),
          'are_you_sure'                  => __( 'Are you sure?', $this->domain ),
          'error_occured'                 => __( 'An error occured.', $this->domain ),
          'save'                          => __( 'Save Configuration', $this->domain ),
          'saved'                         => __( 'Schedule has been saved.', $this->domain ),
          'saving'                        => __( 'Saving the XML Importer schedule, please wait...', $this->domain ),
          'updating'                      => __( 'Updating the XML Importer schedule, please wait...', $this->domain ),
          'updated'                       => __( 'Schedule has been updated.', $this->domain ),
          'out_of_memory'                 => __( '500 Internal Server Error! Your hosting account is most likely running out of memory.', $this->domain ),
          'loading'                       => __( 'Loading...', $this->domain ),
          'please_save'                   => __( 'Please save schedule first.', $this->domain ),
          'toggle_advanced'               => __( 'Toggle Advanced', $this->domain ),
          'processing'                    => __( 'Processing...', $this->domain ),
          'cannot_reload_source'          => __( 'Cannot Load Source: Reload.', $this->domain ),
          'internal_server_error'         => __( 'Internal Server Error!.', $this->domain ),
          'source_is_good'                => __( 'Source Is Good. Reload.', $this->domain ),
          'hide_matches'                  => __( 'Hide Matches', $this->domain ),
          'show_matches'                  => __( 'Show Matches', $this->domain ),
          'matches_via_comma'             => __( 'Matches via comma', $this->domain ),
          'value'                         => __( 'Value', $this->domain ),
          'remove'                        => __( 'Remove', $this->domain ),
        );
        return $l10n;
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

    }

  }

}
