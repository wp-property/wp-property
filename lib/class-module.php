<?php
/**
 * Class Module
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Module' ) ) {

    abstract class Module {

      /**
       * Recognized Headers.
       *
       * @var array
       */
      public static $headers = array(
        'name'         => 'Name',
        'slug'           => 'Slug',
        'description'  => 'Description',
        'version'      => 'Version',
        'class'        => 'Class',
        'minimum.core' => 'Minimum Core Version',
        'minimum.php'  => 'Minimum PHP Version',
        'capability'   => 'Capability',
      );

      /**
       * Module Declared Capabilities.
       *
       */
      private $capability = null;

      /**
       * Module Settings Store.
       *
       */
      private $_settings = null;

      /**
       * Initialize Module
       *
       */
      function __construct() {

        // Compute Dynamic Settings.
        $this->set( '_computed', array(
          'path' => '',
          'url' => '',
        ));

      }

      /**
       * Get Setting.
       *
       * @param $key
       * @param $default
       *
       * @internal param $value
       *
       * @return null
       */
      private function get( $key, $default ) {
        return $this->_settings ? $this->_settings->get( $key, $default ) : null;
      }

      /**
       * Set Setting.
       *
       * @param $key
       * @param $value
       *
       * @return null
       */
      private function set( $key, $value ) {
        return $this->_settings ? $this->_settings->set( $key, $value ) : null;
      }

      /**
       * Activate Module.
       *
       * @param null $args
       *
       * @internal param string $id
       */
      private function activation( $args = null ) {

      }

      /**
       * Deactivate Module.
       *
       * @param null $args
       *
       * @internal param string $id
       */
      private function deactivation( $args = null ) {

      }

      /**
       * Upgrade Installed Feature.
       *
       * @todo Ensure that
       *
       * @param null $args
       *
       * @internal param array $modules
       * @internal param $id
       * @return bool|void
       */
      static public function load( $args = null ) {

        $args = Utility::parse_args( $args, array(
          'path'  => WP_CONTENT_DIR,
          'required'  => array()
        ));

        // Path not provided or not found.
        if( !$args->path || !is_dir( $args->path ) ) {
          return;
        }
        foreach( (array) $args->required as $name ) {

          // Load Module.
          $_module = self::get_installed( array( 'path' => $args->path ) )->{$name};

          try {

            if( !$_module || !file_exists( $_module->path ) ) {

              // Attempt to Download.
              self::install(array(
                "name" => $_module ? $_module->slug : $name,
                "version" => $_module ? $_module->version : null,
                "path" => $args->path
              ));

              // Reload Installed Modules.
              $_module = self::get_installed( array(
                "path" => $args->path,
                "cache" => false
              ))->{$name};

            }

            // Include.
            if( file_exists( $_module->path ) ) {
              include_once( $_module->path );
            }

            // Instantiate.
            if( $_module->class ) {
              new $_module->class();
            }

          } catch( Exception $e ) {
            // throw new Exception( 'Something really gone wrong', 0, $e );
          }


        }

      }

      /**
       * Install Feature from Repository.
       *
       * @param null $args
       *
       * @internal param $id
       * @return bool|void
       */
      static public function install( $args = null ) {

        include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

        $args = Utility::parse_args( $args, array(
          'name'   => '',
          'version' => '',
          'path' => WP_PLUGIN_DIR
        ));

        $args->url = array(
          'http://',
          'repository.usabilitydynamics.com/',
          $args->name
        );

        if( $args->version ) {
          array_push( $args->url, '.', $args->version );
        }

        array_push( $args->url, '.zip' );

        $args->url = implode( $args->url );

        // Concatenate full path.
        $args->path = trailingslashit( $args->path ) . $args->name;

        // Initialize silent skin.
        $_upgrader = new \WP_Upgrader( new Upgrader_Skin() );

        if( is_wp_error( $_upgrader->fs_connect( array( WP_CONTENT_DIR, $args->path )))) {
          $_upgrader->skin->error( new WP_Error( 'Unable to connect to file system.' ) );
        };

        $_source = $_upgrader->unpack_package( $_upgrader->download_package( $args->url ) );

        $_result = $_upgrader->install_package(array(
          'source' => $_source,
          'destination' => $args->path,
          'abort_if_destination_exists' => false,
          'clear_destination' => true,
          'hook_extra' => $args
        ));

        // e.g. folder_exists
        if( is_wp_error( $_result ) ) {
          $_upgrader->skin->error( new WP_Error( 'Installation failed.' ) );
        }

        return $_result;
      }

      /**
       * Check for premium features and load them
       *
       * @since 2.0.0
       */
      static public function get_installed( $args = null ) {

        $args = Utility::parse_args( $args, array(
          'path'  => WP_CONTENT_DIR,
          'cache' => true
        ));

        $_modules = array();
        $module_files = array();

        // Load Cached if not explicitly disabled.
        if( $args->cache && !$cache_modules = wp_cache_get( 'modules', 'wpp' ) ) {
          $cache_modules = array();
        }

        if( isset( $cache_modules ) && isset( $cache_modules->{$args->path} ) ) {
          return (object) $cache_modules->{$args->path};
        }


        // Files in wp-content/plugins directory
        $plugins_dir = @ opendir( $args->path );


        if( $plugins_dir ) {
          while( ( $file = readdir( $plugins_dir ) ) !== false ) {

            if( substr( $file, 0, 1 ) == '.' )
              continue;

            if( is_dir( $args->path . '/' . $file . '/lib' ) ) {
              $plugins_subdir = @ opendir( $args->path . '/' . $file . '/lib' );

              if( $plugins_subdir ) {
                while( ( $subfile = readdir( $plugins_subdir ) ) !== false ) {
                  if( substr( $subfile, 0, 1 ) == '.' )
                    continue;
                  if( substr( $subfile, -4 ) == '.php' )
                    $module_files[ ] = "$file/lib/$subfile";
                }
                closedir( $plugins_subdir );
              }
            } else {
              if( substr( $file, -4 ) == '.php' )
                $module_files[ ] = $file;
            }
          }
          closedir( $plugins_dir );
        }

        if( empty( $module_files ) ) {
          return $_modules;
        }

        foreach( (array) $module_files as $plugin_file ) {

          if( !is_readable( "$args->path/$plugin_file" ) )
            continue;

          $plugin_data = (object) get_file_data( "$args->path/$plugin_file", self::$headers );

          if( empty ( $plugin_data->name ) )
            continue;

          $plugin_data->path = "$args->path/$plugin_file";

          $plugin_data->slug = $plugin_data->slug != '' ? $plugin_data->slug : Utility::create_slug( $plugin_data->name );

          // Convert dot-notation to nested object.
          $_modules[ $plugin_data->slug ] = (object) Utility::unwrap( $plugin_data );

        }

        $cache_modules[ $args->path ] = $_modules;

        wp_cache_set( 'modules', $cache_modules, 'wpp' );

        return (object) $_modules;

      }

    }

  }

}