<?php
/**
 * Bootstrap
 *
 * @since 2.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Agents_Bootstrap' ) ) {

    final class Agents_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type \UsabilityDynamics\WPP\Agents_Bootstrap object
       */
      protected static $instance = null;
      
      /**
       * Instantaite class.
       */
      public function init() {
        require_once( dirname( __DIR__ ) . '/class-agents.php' );
        add_action('wpp_init', array('class_agents', 'init'));

        /**
         * May be load Widgets
         */
        $this->load_files( $this->path('lib/widgets', 'dir') );
      }

      /**
       * License Manager: to BE or NOT TO BE
       *
       * @author peshkov@UD
       */
      protected function define_license_client() {
        // Well, we just loaded Plugin via Vendor.
        // So, we ignore license manager
        if( defined( 'WPP_AGENTS_VENDOR_LOADED' ) && WPP_AGENTS_VENDOR_LOADED ) {
          return false;
        }
        //** Break if we already have errors to prevent fatal ones. */
        if( $this->has_errors() ) {
          return false;
        }
        //** Be sure we have licenses scheme to continue */
        $schema = $this->get_schema( 'extra.schemas.licenses.client' );
        if( !$schema ) {
          return false;
        }
        //** Licenses Manager */
        if( !class_exists( '\UsabilityDynamics\UD_API\Bootstrap' ) ) {
          $this->errors->add( __( 'Class \UsabilityDynamics\UD_API\Bootstrap does not exist. Be sure all required plugins and (or) composer modules installed and activated.', $this->domain ) );
          return false;
        }
        $args = $this->args;
        $args = array_merge( $args, array(
          'type' => $this->type,
          'name' => $this->name,
          'slug' => $this->slug,
          'referrer_slug' => $this->slug,
          'domain' => $this->domain,
          'errors_callback' => array( $this->errors, 'add' ),
        ), $schema );
        if( empty( $args[ 'screen' ] ) ) {
          $this->errors->add( __( 'Licenses client can not be activated due to invalid \'licenses\' schema.', $this->domain ) );
        }
        $this->client = new \UsabilityDynamics\UD_API\Bootstrap( $args );
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

    }

  }

}
