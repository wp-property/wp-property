<?php
/**
 * Bootstrap
 *
 * @namespace UsabilityDynamics
 *
 * This file can be used to bootstrap any of the UD plugins.
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Bootstrap' ) ) {

    /**
     * Bootstrap the plugin in WordPress.
     *
     * @class Bootstrap
     * @author: peshkov@UD
     */
    class Bootstrap extends Scaffold {
    
      public static $version = '1.0.3';
    
      /**
       * Schemas
       *
       * @public
       * @property schema
       * @var array
       */
      public $schema = null;

      /**
       * Admin Notices handler object
       *
       * @public
       * @property errors
       * @var object UsabilityDynamics\WP\Errors object
       */
      public $errors = false;
      
      /**
       * Settings
       *
       * @public
       * @static
       * @property $settings
       * @type \UsabilityDynamics\Settings object
       */
      public $settings = null;
      
      /**
       * Constructor
       * Attention: MUST NOT BE CALLED DIRECTLY! USE get_instance() INSTEAD!
       *
       * @author peshkov@UD
       */
      protected function __construct( $args ) {
        parent::__construct( $args );
        //** Define our Admin Notices handler object */
        $this->errors = new Errors( $args );
        //** Determine if Composer autoloader is included and modules classes are up to date */
        $this->composer_dependencies();
        //** Determine if plugin/theme requires or recommends another plugin(s) */
        $this->plugins_dependencies();
        //** Maybe define license client */
        $this->define_license_client();
        //** Set install/upgrade pages if needed */
        $this->define_splash_pages();
        //** Load text domain */
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 1 );
        //** Add additional conditions on 'plugins_loaded' action before we start plugin initialization. */
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
        //** Initialize plugin here. All plugin actions must be added on this step */
        add_action( 'plugins_loaded', array( $this, 'init' ), 100 );
        
        //** Maybe need to show UD splash page. Used static functions intentionaly. */
        if ( !has_action( 'admin_init', array( Dashboard::instance(), 'maybe_ud_splash_page' ) ) ) {
          add_action( 'admin_init', array( Dashboard::instance(), 'maybe_ud_splash_page' ) );
        }
        
        if ( !has_action( 'admin_menu', array( Dashboard::instance(), 'add_ud_splash_page') ) ) {
          add_action( 'admin_menu', array( Dashboard::instance(), 'add_ud_splash_page') );
        }

        $this->boot();
      }
      
      /**
       * Returns absolute DIR or URL path
       *
       * @since 1.0.2
       */
      public function path( $short_path, $type = 'url' ) {
        switch( $type ) {
          case 'url':
            return $this->plugin_url . ltrim( $short_path, '/\\' );
            break;
          case 'dir':
            return dirname( $this->plugin_file ) . '/' . ltrim( $short_path, '/\\' );
            break;
        }
        return false;
      }
      
      /**
       * Determine if errors exist
       * Just wrapper.
       */
      public function has_errors() {
        return $this->errors->has_errors();
      }
      
      /**
       * Initialize application.
       * Redeclare the method in child class!
       *
       * @author peshkov@UD
       */
      public function init() {}
      
      /**
       * Called in the end of constructor.
       * Redeclare the method in child class!
       *
       * @author peshkov@UD
       */
      public function boot() {}
      
      /**
       * Load Text Domain
       *
       * @author peshkov@UD
       */
      public function load_textdomain() {
        load_plugin_textdomain( $this->domain, false, basename( $this->plugin_file, '.php' ) . '/static/languages/' );
      }
      
      /**
       * Go through additional conditions on 'plugins_loaded' action before we start plugin initialization
       *
       * @author peshkov@UD
       */
      public function plugins_loaded() {
        //** Determine if we have TGMA Plugin Activation initialized. */
        $is_tgma = $this->is_tgma;
        if( $is_tgma ) {
          $tgma = TGM_Plugin_Activation::get_instance();
          //** Maybe get TGMPA notices. */
          $notices = $tgma->notices( get_class( $this ) );
          if( !empty( $notices[ 'messages' ] ) && is_array( $notices[ 'messages' ] ) ) {
            $error_links = false;
            $message_links = false;
            foreach( $notices[ 'messages' ] as $m ) {
              if( $m[ 'type' ] == 'error' ) $error_links = true;
              elseif( $m[ 'type' ] == 'message' ) $message_links = true;
              $this->errors->add( $m[ 'value' ], $m[ 'type' ] );
            }
            //** Maybe add footer action links to errors and|or notices block. */
            if( !empty( $notices[ 'links' ] ) && is_array( $notices[ 'links' ] ) ) {
              foreach( $notices[ 'links' ] as $type => $links ) {
                foreach( $links as $link ) {
                  $this->errors->add_action_link( $link, $type );
                }
              }
            }
          }
        }
        //** Maybe define license manager */
        $this->define_license_manager();
      }
      
      /**
       * Determine if instance already exists and Return Instance
       *
       * Attention: The method MUST be called from plugin core file at first to set correct path to plugin!
       *
       * @author peshkov@UD
       */
      public static function get_instance( $args = array() ) {
        $class = get_called_class();
        //** We must be sure that final class contains static property $instance to prevent issues. */
        if( !property_exists( $class, 'instance' ) ) {
          exit( "{$class} must have property \$instance" );
        }
        $prop = new \ReflectionProperty( $class, 'instance' );
        if( !$prop->isStatic() ) {
          exit( "Property \$instance must be <b>static</b> for {$class}" );
        }
        if( null === $class::$instance ) {    
          $dbt = debug_backtrace();
          if( !empty( $dbt[0]['file'] ) && file_exists( $dbt[0]['file'] ) ) {
            $pd = get_file_data( $dbt[0]['file'], array(
              'name' => 'Plugin Name',
              'version' => 'Version',
              'domain' => 'Text Domain',
            ), 'plugin' );
            $args = array_merge( (array)$pd, (array)$args, array(
              'plugin_file' => $dbt[0]['file'],
              'plugin_url' => plugin_dir_url( $dbt[0]['file'] ),
            ) );
            $class::$instance = new $class( $args );
            //** Register activation hook */
            register_activation_hook( $dbt[0]['file'], array( $class::$instance, 'activate' ) );
            //** Register activation hook */
            register_deactivation_hook( $dbt[0]['file'], array( $class::$instance, 'deactivate' ) );
          } else {
            $class::$instance = new $class( $args );
          }
        }
        return $class::$instance;
      }
      
      /**
       * Plugin Activation
       * Redeclare the method in child class!
       */
      public function activate() {}
      
      /**
       * Plugin Deactivation
       * Redeclare the method in child class!
       */
      public function deactivate() {}
      
      /**
       * @param string $key
       * @param mixed $value
       *
       * @author peshkov@UD
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = null, $value = null ) {
        if( !is_object( $this->settings ) || !is_callable( array( $this->settings, 'set' ) ) ) {
          return false;
        }
        return $this->settings->set( $key, $value );
      }

      /**
       * @param string $key
       * @param mixed $default
       *
       * @author peshkov@UD
       * @return \UsabilityDynamics\type
       */
      public function get( $key = null, $default = null ) {
        if( !is_object( $this->settings ) || !is_callable( array( $this->settings, 'get' ) ) ) {
          return $default;
        }
        return $this->settings->get( $key, $default );
      }
      
      /**
       * Returns specific schema from composer.json file.
       *
       * @param string $file Path to file
       * @author peshkov@UD
       * @return mixed array or false
       */
      public function get_schema( $key = '' ) {
        if( $this->schema === null ) {
          $file = dirname( $this->plugin_file ) . '/composer.json';
          if( file_exists( $file ) ) {
            $this->schema = (array)\UsabilityDynamics\Utility::l10n_localize( json_decode( file_get_contents( $file ), true ), (array)$this->get_localization() );
          }
        }
        //** Break if composer.json does not exist */
        if( !is_array( $this->schema ) ) {
          return false;
        }
        //** Resolve dot-notated key. */
        if( strpos( $key, '.' ) ) {
          $current = $this->schema;
          $p = strtok( $key, '.' );
          while( $p !== false ) {
            if( !isset( $current[ $p ] ) ) {
              return false;
            }
            $current = $current[ $p ];
            $p = strtok( '.' );
          }
          return $current;
        } 
        //** Get default key */
        else {
          return isset( $this->schema[ $key ] ) ? $this->schema[ $key ] : false;
        }
      }
      
      /**
       * Return localization's list.
       *
       * Example:
       * If schema contains l10n.{key} values:
       *
       * { 'config': 'l10n.hello_world' }
       *
       * the current function should return something below:
       *
       * return array(
       *   'hello_world' => __( 'Hello World', $this->domain ),
       * );
       *
       * @author peshkov@UD
       * @return array
       */
      public function get_localization() {
        return array();
      }
      
      /**
       * Defines License Client if 'licenses' schema is set
       *
       * @author peshkov@UD
       */
      protected function define_license_client() {
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
        $args = array_merge( $args, $schema, array( 
          'errors_callback' => array( $this->errors, 'add' ),
        ) );
        if( empty( $args[ 'screen' ] ) ) {
          $this->errors->add( __( 'Licenses client can not be activated due to invalid \'licenses\' schema.', $this->domain ) );
        }
        $this->client = new \UsabilityDynamics\UD_API\Bootstrap( $args );
      }
      
      /**
       * Defines License Manager if 'license' schema is set
       *
       * @author peshkov@UD
       */
      protected function define_license_manager() {
        //** Break if we already have errors to prevent fatal ones. */
        if( $this->has_errors() ) {
          return false;
        }
        //** Be sure we have license scheme to continue */
        $schema = $this->get_schema( 'extra.schemas.licenses.product' );
        if( !$schema ) {
          return false;
        }
        if( empty( $schema[ 'product_id' ] ) || empty( $schema[ 'referrer' ] ) ) {
          $this->errors->add( __( 'Product requires license, but product ID and (or) referrer is undefined. Please, be sure, that license schema has all required data.', $this->domain ) );
        }
        $schema = array_merge( (array)$schema, array( 
          'plugin_name' => $this->name,
          'plugin_file' => $this->plugin_file,
          'errors_callback' => array( $this->errors, 'add' )
        ) );
        //** Licenses Manager */
        if( !class_exists( '\UsabilityDynamics\UD_API\Manager' ) ) {
          $this->errors->add( __( 'Class \UsabilityDynamics\UD_API\Manager does not exist. Be sure all required plugins installed and activated.', $this->domain ) );
          return false;
        }
        $this->license_manager = new \UsabilityDynamics\UD_API\Manager( $schema );
        return true;
      }
      
      /**
       * Determine if plugin/theme requires or recommends another plugin(s)
       *
       * @author peshkov@UD
       */
      private function plugins_dependencies() {
        $plugins = $this->get_schema( 'extra.schemas.dependencies.plugins' );
        if( !empty( $plugins ) && is_array( $plugins ) ) {
          $tgma = TGM_Plugin_Activation::get_instance();
          foreach( $plugins as $plugin ) {
            $plugin[ '_referrer' ] = get_class( $this );
            $plugin[ '_referrer_name' ] = $this->name;
            $tgma->register( $plugin );
          }
          $this->is_tgma = true;
        }
      }
      
      /**
       * Maybe determines if Composer autoloader is included and modules classes are up to date
       *
       * @author peshkov@UD
       */
      private function composer_dependencies() {
        $dependencies = $this->get_schema( 'extra.schemas.dependencies.modules' );
        if( !empty( $dependencies ) && is_array( $dependencies ) ) {
          foreach( $dependencies as $module => $classes ) {
            if( !empty( $classes ) && is_array( $classes ) ) {
              foreach( $classes as $class => $v ) {
                if( !class_exists( $class ) ) {
                  $this->errors->add( sprintf( __( 'Module <b>%s</b> is not installed or the version is old, class <b>%s</b> does not exist.', $this->domain ), $module, $class ) );
                  continue;
                }
                if ( '*' != trim( $v ) && ( !property_exists( $class, 'version' ) || $class::$version < $v ) ) {
                  $this->errors->add( sprintf( __( 'Module <b>%s</b> should be updated to the latest version, class <b>%s</b> must have version <b>%s</b> or higher.', $this->domain ), $module, $class, $v ) );
                }
              }
            }
          }
        }
      }
      
      /**
       * Define splash pages for plugins if needed.
       * @return boolean
       * @author korotkov@ud
       */
      public function define_splash_pages() {
        
        //** If not defined in schemas or not determined - skip */
        if ( !$_splashes = $this->get_schema( 'extra.splashes' ) ) {
          return false;
        }
        
        $_page = false;
        
        //** Determine what to show depending on version installed */
        $_installed_version = get_option( $this->plugin . '-splash-version', 0 );
        
        //** Just installed */
        if ( !$_installed_version ) {
          $_page = 'install';
        
        //** Upgraded */
        } elseif ( version_compare( $_installed_version,  $this->args['version'] ) == -1 ) {
          $_page = 'upgrade';
          
        //** In other case do not do this */
        } else {
          return false;
        }
        
        //** Abort if no files exist */
        if ( !file_exists( $this->path($_splashes[$_page], 'dir') ) ) {
          return false;
        }
          
        //** Push data to temp transient */
        $_current_pages_to_show = get_transient( Dashboard::instance()->transient_key );

        //** If empty - create */
        if ( !$_current_pages_to_show ) {
          set_transient( Dashboard::instance()->transient_key, array(
            $this->plugin => array(
              'name' => $this->name,
              'content' => $this->path($_splashes[$_page], 'dir'),
              'version' => $this->args['version']
            )
          ), 30 );

        //** If not empty - update */
        } else {
          $_current_pages_to_show[$this->plugin] = array(
            'name' => $this->name,
            'content' => $this->path($_splashes[$_page], 'dir'),
            'version' => $this->args['version']
          );
          set_transient( Dashboard::instance()->transient_key, $_current_pages_to_show, 30 ); 
        }
        
        set_transient( Dashboard::instance()->need_splash_key, Dashboard::instance()->transient_key, 30 );

      }
    
    }
  
  }
  
}