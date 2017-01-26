<?php
/**
 * Bootstrap
 *
 * @since 4.0.0
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Supermap_Bootstrap' ) ) {

    final class Supermap_Bootstrap extends \UsabilityDynamics\WP\Bootstrap_Plugin {
      
      /**
       * Singleton Instance Reference.
       *
       * @protected
       * @static
       * @property $instance
       * @type UsabilityDynamics\WPP\Supermap_Bootstrap object
       */
      protected static $instance = null;

      /**
       * License Manager: to BE or NOT TO BE
       *
       * @author peshkov@UD
       */
      protected function define_license_client() {
        // Well, we just loaded Plugin via Vendor.
        // So, we ignore license manager
        if( defined( 'WPP_SUPERMAP_VENDOR_LOADED' ) && WPP_SUPERMAP_VENDOR_LOADED ) {
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
       * Instantaite class.
       */
      public function init() {
        require_once( dirname( __DIR__ ) . '/class-wpp-supermap.php' );
        add_action( 'wpp_init', array( 'class_wpp_supermap', 'pre_init' ), 0 );
        add_action( 'wpp_init', array( 'class_wpp_supermap', 'init' ), 10 );
      }
      
      /**
       * Plugin Activation
       *
       */
      public function activate() {}
      
      /**
       * Plugin Deactivation
       *
       */
      public function deactivate() {}

      /**
       * Determine if Utility class contains missed function
       * in other case, just return NULL to prevent ERRORS
       *
       * @author peshkov@UD
       * @param $name
       * @param $arguments
       * @return mixed|null
       */
      public function __call($name, $arguments) {
        if (is_callable(array("\\UsabilityDynamics\\WPP\\Supermap_Utility", $name))) {
          return call_user_func_array(array("\\UsabilityDynamics\\WPP\\Supermap_Utility", $name), $arguments);
        } else {
          return NULL;
        }
      }

    }

  }

}
