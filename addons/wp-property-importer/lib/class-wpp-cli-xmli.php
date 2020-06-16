<?php
/**
 * WP CLI Commands
 */


if( defined( 'WP_CLI' ) && WP_CLI ) {

  /** Clear the buffer */
  while( ob_get_level() ) {
    ob_end_flush();
  }

  /**
   * Work with the WP-Property XML Importer premium feature
   */
  class WPP_CLI_XMLI extends WP_CLI_Command {

    /**
     * Removes imported properties from the DB
     *
     * ## OPTIONS
     *
     * <schedule>
     * : The schedule for properties to remove, otherwise it removes all properties associated with any import
     *
     * ## EXAMPLES
     *
     *     wp xmli remove_imported_properties
     *     wp xmli remove_imported_properties 4a6851a1f0e8a9e37aed33e64af5bf19
     *     wp xlmi remove_imported_properties 1388743549
     *
     * @synopsis [<schedule>]
     */
    function remove_imported_properties( $args, $assoc_args ) {
      if( !defined( 'DOING_WPP_CRON' ) ) {
        define( 'DOING_WPP_CRON', true );
      }
      /** Pull our global */
      global $wpp_property_import;
      /** Pull our the hash */
      list( $to_find ) = $args;
      /** If we have a schedule, find the hash */
      if( $to_find ) {
        WP_CLI::line( 'Trying to find XML import for: ' . $to_find );
        if( !( $schedule = class_wpp_property_import::get_schedule( $to_find ) ) ) {
          WP_CLI::error( 'Invalid schedule identifier.' );
        }
        WP_CLI::line( 'Found the proper schedule, attempting to erase all of it\'s properties.' );
      } else {
        WP_CLI::line( 'Attempting to erase all properties that have been imported.' );
      }
      /** Confirm */
      WP_CLI::confirm( 'Are you sure you want to remove all the associated properties? This action cannot be undone.' );
      /** Call our function */
      if( $to_find ) {
        class_wpp_property_import::delete_feed_properties( $schedule[ 'schedule_id' ], $schedule[ 'schedule' ] );
      } elseif( is_array( $wpp_property_import ) && count( $wpp_property_import ) ) {
        foreach( $wpp_property_import[ 'schedules' ] as $schedule_id => $schedule ) {
          class_wpp_property_import::delete_feed_properties( $schedule_id, $schedule );
        }
      }
      /** We're done */
      WP_CLI::success( 'Done removing all properties.' );
    }

    /**
     * List import schedules
     *
     * ## OPTIONS
     *
     * ## EXAMPLES
     *
     *     wp xmli list_imports
     */
    function list_imports( $args, $assoc_args ) {
      if( !defined( 'DOING_WPP_CRON' ) ) {
        define( 'DOING_WPP_CRON', true );
      }
      /** Pull our global */
      global $wpp_property_import;

      /** Loop through it */
      if( is_array( $wpp_property_import[ 'schedules' ] ) && count( $wpp_property_import[ 'schedules' ] ) ) {
        foreach( $wpp_property_import[ 'schedules' ] as $schedule_id => $schedule ) {

          if( isset( $schedule[ 'hash' ] ) ) {
            WP_CLI::line( $schedule[ 'hash' ] . ' :: ' . $schedule[ 'name' ] );
          } else {
            // if missing hash it might have been imported but never re-saved. - potanin@UD
            WP_CLI::line( '[unsaved] :: ' . $schedule[ 'name' ] );
          }

        }
      } else {
        WP_CLI::line( 'No import schedules were found.' );
      }
    }

    /**
     * Actually performs the scheduled import
     *
     * ## OPTIONS
     *
     * <schedule>
     * : The schedule identifier to run the import for
     *
     * ## EXAMPLES
     *
     *     wp xmli do_xml_import 4a6851a1f0e8a9e37aed33e64af5bf19
     *     wp xlmi do_xml_import 1388743549
     *
     * @synopsis <schedule>
     */
    function do_xml_import( $args, $assoc_args ) {
      if( !defined( 'DOING_WPP_CRON' ) ) {
        define( 'DOING_WPP_CRON', true );
      }
      /** Pull our the hash */
      list( $to_find ) = $args;
      /** If we have a schedule, find the hash */
      WP_CLI::line( 'Trying to find XML import for: ' . $to_find );
      if( !( $schedule = class_wpp_property_import::get_schedule( $to_find ) ) ) {
        WP_CLI::error( 'Invalid schedule identifier.' );
      }
      WP_CLI::line( 'Found the proper schedule, attempting the import.' );
      /** Call our function */
      class_wpp_property_import::handle_browser_import( $schedule[ 'schedule_id' ], $schedule[ 'schedule' ] );
      /** We're done */
      WP_CLI::success( 'Done running the import.' );
    }

    /**
     * Manage processes to handle image importing
     *
     * ## OPTIONS
     *
     * <schedule>
     * : The schedule identifier to run the image handling for
     *
     * ## EXAMPLES
     *
     *     wp xmli perform_rets_import 4a6851a1f0e8a9e37aed33e64af5bf19
     *     wp xlmi perform_rets_import 1388743549
     *
     * @synopsis <schedule>
     * @alias wpp_manage_pending_images
     */
    function manage_pending_images( $args, $assoc_args ) {
      if( !defined( 'DOING_WPP_CRON' ) ) {
        define( 'DOING_WPP_CRON', true );
      }
      /** Pull our the hash */
      list( $to_find ) = $args;
      /** If we have a schedule, find the hash */
      WP_CLI::line( 'Trying to find XML import for: ' . $to_find );
      if( !( $schedule = class_wpp_property_import::get_schedule( $to_find ) ) ) {
        WP_CLI::error( 'Invalid schedule identifier.' );
      }
      WP_CLI::line( 'Found the proper schedule, attempting to manage the pending images.' );
      /** Call our function */
      class_wpp_property_import::manage_pending_images( $schedule[ 'schedule_id' ], $schedule[ 'schedule' ] );
      /** We're done */
      WP_CLI::success( 'Done running the management.' );
    }

    /**
     * Actually download the images for a pending image
     *
     * ## OPTIONS
     *
     * <schedule>
     * : The schedule identifier to run the image handling for
     * <postid>
     * : The post ID to work with
     *
     * ## EXAMPLES
     *
     *     wp xmli perform_rets_import 4a6851a1f0e8a9e37aed33e64af5bf19 123
     *     wp xlmi perform_rets_import 1388743549 123
     *
     * @synopsis <schedule> <postid>
     * @alias wpp_update_pending_images
     */
    function update_pending_images( $args, $assoc_args ) {
      if( !defined( 'DOING_WPP_CRON' ) ) {
        define( 'DOING_WPP_CRON', true );
      }
      /** Pull our the hash */
      list( $to_find, $post_id ) = $args;
      /** If we have a schedule, find the hash */
      WP_CLI::line( 'Trying to find XML import for: ' . $to_find );
      if( !( $schedule = class_wpp_property_import::get_schedule( $to_find ) ) ) {
        WP_CLI::error( 'Invalid schedule identifier.' );
      }
      WP_CLI::line( 'Found the proper schedule, attempting to download the pending images.' );
      /** Call our function */
      class_wpp_property_import::update_pending_images( $schedule[ 'schedule_id' ], $post_id );
      /** We're done */
      WP_CLI::success( 'Done running the management.' );
    }
  }

  /** Add the commands from above */
  WP_CLI::add_command( 'xmli', 'WPP_CLI_XMLI' );
}
