<?php
/**
 * Class Module
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Module' ) ) {

    /**
     * Feature Upgrader
     *
     * @package UsabilityDynamics\WPP
     */
    class Module extends \WP_Upgrader {

      var $result;
      var $bulk = false;

      function upgrade_strings() {
        $this->strings[ 'up_to_date' ]          = __( 'The plugin is at the latest version.' );
        $this->strings[ 'no_package' ]          = __( 'Update package not available.' );
        $this->strings[ 'downloading_package' ] = __( 'Downloading update from <span class="code">%s</span>&#8230;' );
        $this->strings[ 'unpack_package' ]      = __( 'Unpacking the update&#8230;' );
        $this->strings[ 'remove_old' ]          = __( 'Removing the old version of the plugin&#8230;' );
        $this->strings[ 'remove_old_failed' ]   = __( 'Could not remove the old plugin.' );
        $this->strings[ 'process_failed' ]      = __( 'Plugin update failed.' );
        $this->strings[ 'process_success' ]     = __( 'Plugin updated successfully.' );
      }

      function install_strings() {
        $this->strings[ 'no_package' ]          = __( 'Install package not available.' );
        $this->strings[ 'downloading_package' ] = __( 'Downloading install package from <span class="code">%s</span>&#8230;' );
        $this->strings[ 'unpack_package' ]      = __( 'Unpacking the package&#8230;' );
        $this->strings[ 'installing_package' ]  = __( 'Installing the plugin&#8230;' );
        $this->strings[ 'no_files' ]            = __( 'The plugin contains no files.' );
        $this->strings[ 'process_failed' ]      = __( 'Plugin install failed.' );
        $this->strings[ 'process_success' ]     = __( 'Plugin installed successfully.' );
      }

      function install( $package, $args = array() ) {

        $defaults    = array(
          'clear_update_cache' => true,
        );
        $parsed_args = wp_parse_args( $args, $defaults );

        $this->init();
        $this->install_strings();

        add_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );

        $this->run( array(
          'package'           => $package,
          'destination'       => WP_PLUGIN_DIR,
          'clear_destination' => false, // Do not overwrite files.
          'clear_working'     => true,
          'hook_extra'        => array(
            'type'   => 'plugin',
            'action' => 'install',
          )
        ) );

        remove_filter( 'upgrader_source_selection', array( $this, 'check_package' ) );

        if( !$this->result || is_wp_error( $this->result ) )
          return $this->result;

        // Force refresh of plugin update information
        wp_clean_plugins_cache( $parsed_args[ 'clear_update_cache' ] );

        return true;
      }

      function upgrade( $plugin, $args = array() ) {

        $defaults    = array(
          'clear_update_cache' => true,
        );
        $parsed_args = wp_parse_args( $args, $defaults );

        $this->init();
        $this->upgrade_strings();

        $current = get_site_transient( 'update_plugins' );
        if( !isset( $current->response[ $plugin ] ) ) {
          $this->skin->before();
          $this->skin->set_result( false );
          $this->skin->error( 'up_to_date' );
          $this->skin->after();

          return false;
        }

        // Get the URL to the zip file
        $r = $current->response[ $plugin ];

        add_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ), 10, 2 );
        add_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ), 10, 4 );
        //'source_selection' => array($this, 'source_selection'), //there's a trac ticket to move up the directory for zip's which are made a bit differently, useful for non-.org plugins.

        $this->run( array(
          'package'           => $r->package,
          'destination'       => WP_PLUGIN_DIR,
          'clear_destination' => true,
          'clear_working'     => true,
          'hook_extra'        => array(
            'plugin' => $plugin,
            'type'   => 'plugin',
            'action' => 'update',
          ),
        ) );

        // Cleanup our hooks, in case something else does a upgrade on this connection.
        remove_filter( 'upgrader_pre_install', array( $this, 'deactivate_plugin_before_upgrade' ) );
        remove_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ) );

        if( !$this->result || is_wp_error( $this->result ) )
          return $this->result;

        // Force refresh of plugin update information
        wp_clean_plugins_cache( $parsed_args[ 'clear_update_cache' ] );

        return true;
      }

      function bulk_upgrade( $plugins, $args = array() ) {

        $defaults    = array(
          'clear_update_cache' => true,
        );
        $parsed_args = wp_parse_args( $args, $defaults );

        $this->init();
        $this->bulk = true;
        $this->upgrade_strings();

        $current = get_site_transient( 'update_plugins' );

        add_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ), 10, 4 );

        $this->skin->header();

        // Connect to the Filesystem first.
        $res = $this->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR ) );
        if( !$res ) {
          $this->skin->footer();

          return false;
        }

        $this->skin->bulk_header();

        // Only start maintenance mode if:
        // - running Multisite and there are one or more plugins specified, OR
        // - a plugin with an update available is currently active.
        // @TODO: For multisite, maintenance mode should only kick in for individual sites if at all possible.
        $maintenance = ( is_multisite() && !empty( $plugins ) );
        foreach( $plugins as $plugin )
          $maintenance = $maintenance || ( is_plugin_active( $plugin ) && isset( $current->response[ $plugin ] ) );
        if( $maintenance )
          $this->maintenance_mode( true );

        $results = array();

        $this->update_count   = count( $plugins );
        $this->update_current = 0;
        foreach( $plugins as $plugin ) {
          $this->update_current++;
          $this->skin->plugin_info = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, true );

          if( !isset( $current->response[ $plugin ] ) ) {
            $this->skin->set_result( true );
            $this->skin->before();
            $this->skin->feedback( 'up_to_date' );
            $this->skin->after();
            $results[ $plugin ] = true;
            continue;
          }

          // Get the URL to the zip file
          $r = $current->response[ $plugin ];

          $this->skin->plugin_active = is_plugin_active( $plugin );

          $result = $this->run( array(
            'package'           => $r->package,
            'destination'       => WP_PLUGIN_DIR,
            'clear_destination' => true,
            'clear_working'     => true,
            'is_multi'          => true,
            'hook_extra'        => array(
              'plugin' => $plugin
            )
          ) );

          $results[ $plugin ] = $this->result;

          // Prevent credentials auth screen from displaying multiple times
          if( false === $result )
            break;
        } //end foreach $plugins

        $this->maintenance_mode( false );

        do_action( 'upgrader_process_complete', $this, array(
          'action'  => 'update',
          'type'    => 'plugin',
          'bulk'    => true,
          'plugins' => $plugins,
        ) );

        $this->skin->bulk_footer();

        $this->skin->footer();

        // Cleanup our hooks, in case something else does a upgrade on this connection.
        remove_filter( 'upgrader_clear_destination', array( $this, 'delete_old_plugin' ) );

        // Force refresh of plugin update information
        wp_clean_plugins_cache( $parsed_args[ 'clear_update_cache' ] );

        return $results;
      }

      function check_package( $source ) {
        global $wp_filesystem;

        if( is_wp_error( $source ) )
          return $source;

        $working_directory = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit( WP_CONTENT_DIR ), $source );
        if( !is_dir( $working_directory ) ) // Sanity check, if the above fails, lets not prevent installation.
          return $source;

        // Check the folder contains at least 1 valid plugin.
        $plugins_found = false;
        foreach( glob( $working_directory . '*.php' ) as $file ) {
          $info = get_plugin_data( $file, false, false );
          if( !empty( $info[ 'Name' ] ) ) {
            $plugins_found = true;
            break;
          }
        }

        if( !$plugins_found )
          return new WP_Error( 'incompatible_archive_no_plugins', $this->strings[ 'incompatible_archive' ], __( 'No valid plugins were found.' ) );

        return $source;
      }

      //return plugin info.
      function plugin_info() {
        if( !is_array( $this->result ) )
          return false;
        if( empty( $this->result[ 'destination_name' ] ) )
          return false;

        $plugin = get_plugins( '/' . $this->result[ 'destination_name' ] ); //Ensure to pass with leading slash
        if( empty( $plugin ) )
          return false;

        $pluginfiles = array_keys( $plugin ); //Assume the requested plugin is the first in the list

        return $this->result[ 'destination_name' ] . '/' . $pluginfiles[ 0 ];
      }

      //Hooked to pre_install
      function deactivate_plugin_before_upgrade( $return, $plugin ) {

        if( is_wp_error( $return ) ) //Bypass.
          return $return;

        // When in cron (background updates) don't deactivate the plugin, as we require a browser to reactivate it
        if( defined( 'DOING_CRON' ) && DOING_CRON )
          return $return;

        $plugin = isset( $plugin[ 'plugin' ] ) ? $plugin[ 'plugin' ] : '';
        if( empty( $plugin ) )
          return new WP_Error( 'bad_request', $this->strings[ 'bad_request' ] );

        if( is_plugin_active( $plugin ) ) {
          //Deactivate the plugin silently, Prevent deactivation hooks from running.
          deactivate_plugins( $plugin, true );
        }
      }

      //Hooked to upgrade_clear_destination
      function delete_old_plugin( $removed, $local_destination, $remote_destination, $plugin ) {
        global $wp_filesystem;

        if( is_wp_error( $removed ) )
          return $removed; //Pass errors through.

        $plugin = isset( $plugin[ 'plugin' ] ) ? $plugin[ 'plugin' ] : '';
        if( empty( $plugin ) )
          return new WP_Error( 'bad_request', $this->strings[ 'bad_request' ] );

        $plugins_dir     = $wp_filesystem->wp_plugins_dir();
        $this_plugin_dir = trailingslashit( dirname( $plugins_dir . $plugin ) );

        if( !$wp_filesystem->exists( $this_plugin_dir ) ) //If it's already vanished.
          return $removed;

        // If plugin is in its own directory, recursively delete the directory.
        if( strpos( $plugin, '/' ) && $this_plugin_dir != $plugins_dir ) //base check on if plugin includes directory separator AND that it's not the root plugin folder
          $deleted = $wp_filesystem->delete( $this_plugin_dir, true );
        else
          $deleted = $wp_filesystem->delete( $plugins_dir . $plugin );

        if( !$deleted )
          return new WP_Error( 'remove_old_failed', $this->strings[ 'remove_old_failed' ] );

        return true;
      }

    }

  }

}