<?php
/**
 * Name: XML Property Importer
 * Class: class_wpp_property_import
 * Global Variable: wpp_property_import
 * Internal Slug: property_import
 * JS Slug: wpp_property_import
 * Minimum Core Version: 1.42.0
 * Feature ID: 5
 * Version: 5.0.0
 * Description: WP-Property premium feature for automated importing of XML, CSV, JSON, Google Spreadsheet and MLS/RETS data.
 *
 */

/**
 * WP-Property Premium Importer Function
 *
 * Handles XML, CSV, JSON and RETS importing;
 *
 * @version 2.33
 * @package WP-Property
 * @subpackage WP-Property XML Property Importer
 */
class class_wpp_property_import {

  /**
   * ( custom ) Capability to manage the current feature
   */
  static protected $capability = "manage_wpp_import";

  /**
   * Setup our custom variables for rets
   */
  static protected $default_rets_pk = 'ListingKey';
  static protected $default_rets_query = '(ListingStatus=|Active)';
  static protected $default_rets_photo = 'Photo';
  static protected $default_rets_resource = 'Property';

  /**
   * Static request map
   */
  static protected $static_request_map = array(
    'wpp_schedule_import' => array(
      'function' => 'handle_browser_import',
      'description' => 'Running XML import schedule.'
    ),
    'wpp_manage_pending_images' => array(
      'function' => 'manage_pending_images',
      'description' => 'Managing pending images.'
    ),
    'wpp_update_pending_images' => array(
      'function' => 'update_pending_images',
      'description' => 'Updating pending images.'
    )
  );

  /**
   * Special functions that must be called prior to init
   *
   */
  static public function pre_init() {
    /* Add capability */
    add_filter( 'wpp_capabilities', array( 'class_wpp_property_import', "add_capability" ) );
  }

  /**
   * Called at end of WPP init hook, in WP init hook
   *
   * Run-time settings are stored in $wpp_property_import['runtime']
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function init() {
    global $wpp_property_import, $wp_properties, $wpp_import_result_stats;

    /* Load settings */
    if( isset( $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ] ) ) {
      $wpp_property_import = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ];
    }

    /* Load default settings */
    if( empty( $wpp_property_import ) ) {
      class_wpp_property_import::load_default_settings();
    }

    // Load run-time settings
    $wpp_property_import[ 'post_table_columns' ] = array(
      'post_title' => __( 'Property Title', ud_get_wpp_importer()->domain ),
      'post_content' => __( 'Property Content', ud_get_wpp_importer()->domain ),
      'post_excerpt' => __( 'Property Excerpt', ud_get_wpp_importer()->domain ),
      'post_status' => __( 'Property Status', ud_get_wpp_importer()->domain ),
      'menu_order' => __( 'Property Order', ud_get_wpp_importer()->domain ),
      'post_date' => __( 'Property Date', ud_get_wpp_importer()->domain ),
      'post_modified' => __( 'Modified Date', ud_get_wpp_importer()->domain ),
      'post_author' => __( 'Property Author', ud_get_wpp_importer()->domain )
    );

    //** If cron, do not load rest. */
    if( defined( 'DOING_WPP_CRON' ) ) {
      return;
    }

    if( current_user_can( self::$capability ) ) {
      add_action( 'wpp_publish_box_options', array( 'class_wpp_property_import', 'wpp_publish_box_options' ) );
      add_action( 'save_post', array( __CLASS__, 'save_post' ) );

      /* Adds Custom Search Filters on Property Overview page on admin panel */
      add_filter( 'wpp_get_search_filters', array( __CLASS__, 'wpp_get_search_filters') );
      add_filter( 'wpp::get_properties::custom_case', array( __CLASS__, 'wpp_get_properties_by_custom_case'), 10, 2 );
      add_filter( 'wpp::get_properties::custom_key', array( __CLASS__, 'wpp_get_properties_by_custom_filter'), 10, 3 );
    }

    /* Setup pages */
    add_action( 'admin_menu', array( 'class_wpp_property_import', 'admin_menu' ) );
    /* Admin before header actions */
    add_action( 'admin_init', array( 'class_wpp_property_import', 'admin_init' ) );

    /* Handle all AJAX calls*/
    add_action( 'wp_ajax_wpp_property_import_handler', array( 'class_wpp_property_import', 'admin_ajax_handler' ) );

    /* Handle Matches on import */
    add_filter( 'wpp_xml_import_value_on_import', array( __CLASS__, 'maybe_replace_matched_value' ), 10, 5 );

    /* Load Scripts */
    add_action( 'admin_enqueue_scripts', array( 'class_wpp_property_import', 'admin_enqueue_scripts' ) );
    /* Manual update from hash */
    add_action( 'wpp_post_init', array( 'class_wpp_property_import', 'run_from_cron_hash' ) );

    add_action( 'wp_ajax_wpp_ajax_show_xml_imort_history', array( 'class_wpp_property_import', 'wpp_ajax_show_xml_imort_history' ) );
    add_action( 'wpp_settings_help_tab', array( 'class_wpp_property_import', 'wpp_settings_help_tab' ) );
    add_filter( 'upload_mimes', array( 'class_wpp_property_import', 'add_upload_mimes' ) );
    add_filter( 'wpp_admin_overview_columns', array( 'class_wpp_property_import', 'wpp_admin_overview_columns' ) );
    add_filter( 'wpp_stat_filter_wpp_xml_import', array( 'class_wpp_property_import', 'wpp_stat_filter_wpp_xml_import' ) );
    // Modify admin body class of imported properties. Order of 10 is important because class_core::admin_body_class is ran on 5 */
    add_filter( 'admin_body_class', array( 'class_wpp_property_import', 'admin_body_class' ), 10 );
    /** Add in our custom cron handlers */
    add_action( 'wpp_manage_pending_images', array( 'class_wpp_property_import', 'manage_pending_images' ) );
    add_action( 'wpp_update_pending_images', array( 'class_wpp_property_import', 'update_pending_images' ), 10, 2 );

    // Schedule API Jobs.
    add_filter( 'wpp::xmli::update_schedule', array( 'class_wpp_property_import', 'api_scheduler' ), 10, 2 );

    /** Listen for browser-based requests */
    if( count( $request = array_intersect( array_keys( self::$static_request_map ), array_keys( $_REQUEST ) ) ) ) {
      $request = array_shift( $request );
      /** Now, try to find the schedule */
      if( empty( $request ) ) {
        self::show_400();
      }
      /** Flatten the results */
      self::route_static_request( $request );
    }
  }

  /**
   * Create/Update API Schedule Information
   *
   * @param $schedule
   * @param $detail
   * @return mixed
   */
  static public function api_scheduler( $schedule, $detail ) {

    //die( '<pre>' . print_r( $schedule['schedule'], true ) . '</pre>' );

    // already scheduled.
    if( isset( $schedule['schedule'] ) && $schedule['schedule']['uuid'] ) {
      //return $schedule;
    }

    if( $schedule['alt_cron_enabled'] === 'true' ) {

    }

    $current_user = wp_get_current_user();

    $_response = wp_remote_post( 'https://usabilitydynamics-node-product-api-staging.c.rabbit.ci/property/importer/v1/scheduler/job/' . $detail['schedule_id'], array(
      'method' => 'POST',
      'body' => array(
        'callback_url' => site_url( '/wp-api/importer/?wpp_schedule_import=' . $detail['schedule_hash'] . '&echo_log=true' ),
        'callback_hash' => $detail['schedule_hash'],
        'interval' => $schedule['alt_cron_run'],
        'meta' => array(
          'title' => 'Import for ' . get_bloginfo( 'title' ),
          'schedule_id' => $detail['schedule_id'],
          'owner_name' => $current_user->user_login,
          'notify_mail' => $current_user->user_email,
        )
      )
    ));

    $_parsed_response = json_decode(wp_remote_retrieve_body($_response));

    $schedule['schedule'] = array( 'uuid' => $_parsed_response->uuid, 'id' => $_parsed_response->id );

    return $schedule;
  }

  /**
   * Routes static request
   *
   * @since 4.0
   */
  static public function route_static_request( $request ) {
    global $wpp_property_import, $wp_properties, $wpp_import_result_stats;
    /** Find the hash */
    $to_find = $_REQUEST[ $request ];
    $schedule_hash = false;
    /** Try to find the proper schedule */
    foreach( $wpp_property_import[ 'schedules' ] as $schedule_id => $schedule ) {
      switch( true ) {
        case( $schedule_id == $to_find ):
          $schedule_hash = $schedule[ 'hash' ];
          break;
        case( isset( $schedule[ 'hash' ] ) && $schedule[ 'hash' ] == $to_find ):
          $schedule_hash = $schedule[ 'hash' ];
          break;
      }
      /** If we have the hash, bail out of the loop */
      if( $schedule_hash ) {
        break;
      }
    }
    /** If we didn't find the id or hash, bail */
    if( !$schedule_hash ) {
      self::show_400();
    }
    /** We have everything we need to continue, start our try catch block */
    $output = ( isset( $_REQUEST[ 'output' ] ) && $_REQUEST[ 'output' ] == 'xml' ? 'xml' : 'html' );
    /** Setup general options */
    set_time_limit( 0 );
    ignore_user_abort( true );
    if( ob_get_level() == 0 ) {
      ob_start();
    }
    /** Now, show our header */
    self::header( $schedule, self::$static_request_map[ $request ][ 'description' ], $output );
    try {
      /** Just call the function */
      call_user_func_array( array( __CLASS__, self::$static_request_map[ $request ][ 'function' ] ), array( $schedule_id, $schedule ) );
    } catch ( Exception $e ) {
      /** Show errors */
      self::maybe_echo_log( 'There was an issue with the request: ' . $e->getMessage() );
    }
    /** Now, show our footer */
    self::footer( $schedule, $output );
    /** We gone */
    die();
  }

  /**
   * Displays our header for browser requests
   *
   * @since 4.0
   */
  static public function header( $schedule, $description, $output = 'html' ) {
    if( defined( 'WP_CLI' ) && WP_CLI ) {
      self::maybe_echo_log( $schedule[ 'name' ] );
      self::maybe_echo_log( $description );
    } elseif( $output == 'xml' ) {
      header( 'Content-type: text/xml' );
      print "<?xml version=\"1.0\"?>\n<xml_import>\n";
    } else { ?>
      <html xmlns="http://www.w3.org/1999/xhtml" class="graceful_death">
      <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>XMLI: <?php echo $schedule[ 'name' ]; ?></title>
        <style type="text/css">
          html {
            height: 100%;
            background-color: #F2F2F2
          }

          body {
            background: rgba(255, 255, 255, 0.39);
            border: 10px solid rgba(227, 227, 227, 0.54);
            border-radius: 0.5em;
            color: #333333;
            font-family: Calibri, Times New Roman;
            line-height: 1.5em;
            margin: 3em 5em;
            padding: 1.5em;
          }

          h1.title {
            padding: 0;
            margin: 0;
          }

          p.subtitle {
            padding: 10px 0;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #dadada;
          }

          ul.summary {
            background: #E7E7E7;
            border: 1px solid #A4A4A4;
            line-height: 1.7em;
            list-style: none outside none;
            margin-top: 1em;
            padding: 10px;
          }

          span.time {
            color: #929292;
            margin-right: 8px;
          }
        </style>
      </head>
      <body>
      <h1 class="title">XMLI: <?php echo $schedule[ 'name' ]; ?></h1>
      <p class="subtitle"><?php echo $description; ?></p> <?php
    }
  }

  /**
   * Displays our footer for browser requests
   *
   * @since 4.0
   */
  static public function footer( $schedule, $output = 'html' ) {
    global $wpp_import_result_stats;
    if( defined( 'WP_CLI' ) && WP_CLI ) {
      return;
    } elseif( $output == 'xml' ) {
      print "<result_stats>\n";
      foreach( $wpp_import_result_stats as $row ) {
        print "\t<stat>" . $row . "</stat>\n";
      }
      print "</result_stats>\n";
      print "</xml_import>";
    } else { ?>
      </body></html> <?php
    }
  }

  /**
   * Shows 404 page on errors
   *
   * @since 4.0
   */
  static public function show_400() {
    status_header( 400 );
    nocache_headers();
    echo 'Bad request.';
    exit;
  }

  /**
   * This is the function that handles downloading our schedule's images. Basically this function will
   * find the properties that have pending images, and then cause the processes to run which will actually
   * do the work. It will spawn {$num_worker_threads} simultaneously  that each represent 1 wp-property object, and when it
   * is done, it will then turn around and check again for any additional missing images, and reschedule itself.
   *
   * @since 4.0
   */
  static public function manage_pending_images( $schedule_id, $schedule = false ) {
    global $wp_properties, $wpp_property_import, $wpdb;
    /** If we don't the schedule, route back to the router */
    if( !$schedule ) {
      $_REQUEST[ 'wpp_manage_pending_images' ] = $schedule_id;
      $_REQUEST[ 'echo_log' ] = 'true';
      self::route_static_request( 'wpp_manage_pending_images' );
      return;
    }
    /** Ok, we're here, lets go ahead and manage those processes */
    self::maybe_echo_log( 'Attempting to manage pending images for schedule #: ' . $schedule_id . '.' );
    /** Setup the number of threads, and my transient base */
    $num_worker_threads = isset( $schedule[ 'num_worker_threads' ] ) && is_numeric( $schedule[ 'num_worker_threads' ] ) && (int)$schedule[ 'num_worker_threads' ] > 0 ? (int)$schedule[ 'num_worker_threads' ] : 10;
    $transient_prefix = 'wpp_xlmi' . $schedule_id . '_';
    /** Setup our cache block for our queries */
    $cb = rand();
    /** First, get all the possible ids */
    $schedule_post_ids = $wpdb->get_col( "
      SELECT DISTINCT post_id
      FROM {$wpdb->postmeta}
      WHERE meta_key = 'wpp_import_schedule_id'
        AND meta_value = '{$schedule_id}'
        AND {$cb} = {$cb}
    " );
    if( !( is_array( $schedule_post_ids ) && count( $schedule_post_ids ) ) ) {
      $schedule_post_ids = array( 0 );
    }

    //** Determine if process already runs. */
    $query = "
      SELECT DISTINCT post_id, meta_value
      FROM {$wpdb->postmeta}
      WHERE post_id IN ( " . implode( ',', $schedule_post_ids ) . ")
        AND meta_key = 'wpp::image_status'
        AND meta_value LIKE 'working:%'
        AND {$cb} = {$cb}
      ORDER BY post_id ASC
    ";
    $working_ids = $wpdb->get_results( $query, ARRAY_A );
    if( is_array( $working_ids ) && count( $working_ids ) ) {
      self::maybe_echo_log( 'We found ' . count( $working_ids ) . ' existing posts with images being imported. Inspecting them.' );
      foreach( $working_ids as $key => $row ) {
        list( $status, $time_started ) = explode( ':', $row[ 'meta_value' ] );
        self::maybe_echo_log( 'Found post # ' . $row[ 'post_id' ] . ' which was started at ' . $time_started );
        /** Ok, make sure that this wasn't more than 15 minutes ago */
        if( time() - (int)$time_started > 15 * 60 ) {
          self::maybe_echo_log( 'This post has exceeded the timeout - resetting it\'s status.' );
          $query = "UPDATE {$wpdb->postmeta} SET meta_value = 'failed::timed out' WHERE post_id = {$row['post_id']} AND meta_key = 'wpp::image_status'";
          $wpdb->query( $query );
          unset( $working_ids[ $key ] );
        } else {
          self::maybe_echo_log( 'This post still has time to complete, started ' . human_time_diff( $time_started ) . ' ago.' );
        }
      }
    } else {
      self::maybe_echo_log( 'No working IDs found for schedule #: ' . $schedule_id . ', have ' . count( $working_ids ) . ' pending items.' );
      $working_ids = array();
    }

    /** Now, go ahead get the highest post primary key that has already been run */
    if( !( $latest_id = get_transient( $transient_prefix . 'latest_id' ) ) ) {
      /** Just make one up now */
      $latest_id = 0;
    }

    /** Now, we're going to run that query against the DB meta */
    /** But be sure before that Limit value is valid */
    $limit = $num_worker_threads - count( $working_ids );
    if( $limit > 0 ) {
      $query = "
        SELECT DISTINCT post_id
        FROM {$wpdb->postmeta}
        WHERE post_id > {$latest_id}
          AND post_id IN ( " . implode( ',', $schedule_post_ids ) . " )
          AND meta_key = 'wpp::image_status'
          AND meta_value = 'pending'
          AND {$cb} = {$cb}
          ORDER BY post_id ASC
          LIMIT " . ( $num_worker_threads - count( $working_ids ) ) . "
      ";
      /** Now get the pending IDs we'll need to update */
      $pending_ids = $wpdb->get_col( $query );
    }
    if( isset( $pending_ids ) && count( $pending_ids ) ) {
      //** If we have a result set, update them to be 'working' status, and then update the 'latest id' transient */
      $query = "UPDATE {$wpdb->postmeta} SET meta_value = 'working:" . time() . "' WHERE post_id IN ( " . implode( ',', $pending_ids ) . " ) AND meta_key = 'wpp::image_status'";
      $wpdb->query( $query );
      /** Now, since we've done that lets loop through and schedule the tasks */
      foreach( $pending_ids as $k => $id ) {
        self::maybe_echo_log( 'Found post, attempting to schedule update for post #' . $id . '.' );
        $args = array_filter( array(
          'wpp_update_pending_images' => $schedule_id,
          'post_id' => $id
        ) );
        /** Now, try to execute that thang, without scheduling */
        self::maybe_schedule_cron( 'wpp_update_pending_images', $args );
        self::maybe_run_cron( 'wpp_update_pending_images', $args );
      }
    } else {
      /** Otherwise, we don't have any additional items to process, reset the 'latest id' transient */
      delete_transient( $transient_prefix . 'latest_id' );
    }
    /** Ok, we're done managing processing */
    self::maybe_echo_log( 'Done processing images until next run.' );
  }

  /**
   * This is the function that handles downloading our schedule's images
   *
   * @since 4.0
   */
  static public function update_pending_images( $schedule_id, $schedule = false ) {
    global $wp_properties, $wpp_property_import, $wpdb;
    self::maybe_debug_log( "Staring update_pending_images for [" . $schedule_id . "]." );

    /** If we don't the schedule, route back to the router */
    if( !is_array( $schedule ) ) {
      $_REQUEST[ 'wpp_update_pending_images' ] = $schedule_id;
      $_REQUEST[ 'echo_log' ] = 'true';
      $_REQUEST[ 'post_id' ] = $schedule;
      self::route_static_request( 'wpp_update_pending_images' );
      return;
    }

    try {
      /** Get our post id from the request */
      if( !( isset( $_REQUEST[ 'post_id' ] ) && is_numeric( $_REQUEST[ 'post_id' ] ) ) ) {
        throw new Exception( 'Invalid post id.' );
      }
      $post_id = $_REQUEST[ 'post_id' ];
      self::maybe_echo_log( 'Attempting to update pending images for schedule #: ' . $schedule_id . ' and post #: ' . $post_id . '.' );
      /** Ok, first get the post */
      if( !( $property = WPP_F::get_property( $post_id, 'load_parent=false&get_children=false' ) ) ) {
        throw new Exception( 'Invalid property id.' );
      }

      //** Clean Up Attached Images:
      //** Get all attached images - in ascending post_date order (oldest attachments first) */
      $wp_upload_dir = wp_upload_dir();
      $all_attachments = $wpdb->get_results( $wpdb->prepare( "SELECT ID as attachment_id, post_date, post_content_filtered, guid, post_name, meta_value as _wp_attached_file FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE post_type = 'attachment' AND post_parent = %d AND meta_key = '_wp_attached_file' ORDER BY post_date ASC ", $post_id ) );
      //** Cycle through all attached files, remove non-existing ones and verify md5 */
      foreach( $all_attachments as $key => $attached ) {
        $all_attachments[ $key ]->full_path = trailingslashit( $wp_upload_dir[ 'basedir' ] ) . $attached->_wp_attached_file;
        $all_attachments[ $key ]->attachment_url = trailingslashit( $wp_upload_dir[ 'baseurl' ] ) . $attached->_wp_attached_file;
        if( !file_exists( $all_attachments[ $key ]->full_path ) ) {
          class_wpp_property_import::maybe_echo_log( sprintf( __( 'Attachment referenced in database not found on disk: (%1s), removing reference from DB.', ud_get_wpp_importer()->domain ), $all_attachments[ $key ]->attachment_url ) );
          wp_delete_attachment( $attached->attachment_id );
        }
      }

      /** Get the unique key */
      $unique_id_key = $schedule[ 'unique_id' ];
      $property_unique_id = !( isset( $property[ $unique_id_key ] ) && $property[ $unique_id_key ] ) ? $property[ 'ID' ] : $property[ $unique_id_key ];
      /** Create a temp directory using the import ID as name */
      $image_directory = class_wpp_property_import::create_import_directory( array( 'ad_hoc_temp_dir' => $schedule_id . 'x' . $property_unique_id ) );
      if( $image_directory ) {
        $image_directory = $image_directory[ 'ad_hoc_temp_dir' ];
      } else {
        self::maybe_echo_log( sprintf( __( 'Image directory %1s could not be created.', ud_get_wpp_importer()->domain ), $image_directory ) );
      }
      $images_meta = array();
      /** Start a new try/catch block, we have to finish off this code */
      try {
        /** Ok, see if we're a RETS request, versus a regular */
        if( $schedule[ 'source_type' ] == 'rets' ) {
          /** Setup what our image data should look like */
          $image_data = array(
            'featured-image' => array(),
            'images' => array()
          );
          $rets_pk_value = !empty( $property[ 'wpp::rets_pk' ] ) ? $property[ 'wpp::rets_pk' ] : false;
          if( !$rets_pk_value ) {
            throw new Exception( __( 'System (Primary) Key is not found.', ud_get_wpp_importer()->domain ) );
          }
          /** Ok, first connect to RETS */
          $rets = self::connect_rets( $schedule );
          /** Determine RETS resource */
          $rets_res = !empty( $schedule[ 'rets_resource' ] ) ? $schedule[ 'rets_resource' ] : self::$default_rets_resource;
          /** Determine our Photo object */
          $rets_photo = !empty( $schedule[ 'rets_photo' ] ) ? $schedule[ 'rets_photo' ] : self::$default_rets_photo;
          /** Do our query */

          self::maybe_echo_log( "Making RETS request to [$rets_res/$rets_photo] using [$rets_pk_value] primary key." );

          $photos = $rets->GetObject( $rets_res, $rets_photo, $rets_pk_value );

          //self::maybe_echo_log( print_r($photos,true) );die();

          /** Begin image cycle - go through every image and write it to schedule's temp directory */
          foreach( (array)$photos as $image_count => $photo ) {
            self::keep_hope_alive();

            self::maybe_debug_log( "Checking each RETS photo returned, currently [$image_count]." );

            if( !preg_match( '/^image/', $photo[ 'Content-Type' ] ) ) {
              self::maybe_debug_log( "Content Type is [" . $photo[ 'Content-Type' ] . "], which is invalid, skipping." );
              continue;
            }
            try {
              if( empty( $photo[ 'Data' ] ) ) {
                Throw new exception( sprintf( __( 'Could not save image saved image - empty file returned by server.', ud_get_wpp_importer()->domain ) ) );
              }
              //** Determine if image already exists */
              //** Set unique hash for matching the image with existing one. */
              $_unique_id = ( isset( $photo[ 'Content-ID' ] ) ? $photo[ 'Content-ID' ] : '' ) . ':' . ( isset( $photo[ 'Object-ID' ] ) ? $photo[ 'Object-ID' ] : '' );

              $_unique_id = $_hashed_id = md5( $_unique_id );

              $image_exists = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'unique_id' AND meta_value = %s LIMIT 1", $_unique_id ) );

              if( $image_exists ) {
                self::maybe_debug_log( "Checking unique RETS photo [$_unique_id] / [$_hashed_id], found, it has [$image_exists] ID." );

                $_double_check_query = $wpdb->prepare( "SELECT parent.ID as property_id, parent.post_status as property_status, image.post_type as image_type FROM {$wpdb->posts} parent LEFT JOIN {$wpdb->posts} image ON image.post_parent = parent.ID  WHERE image.ID = '%s';", $image_exists  );

                $images_parent_property_exists = $wpdb->get_row( $_double_check_query );

                if( !$images_parent_property_exists ) {
                  self::maybe_debug_log( "The [$_unique_id] / [$_hashed_id], RETS media item exists but it does not have a valid parent property, we assume its orphaned." );
                } else {
                continue;
              }

              } else {
                self::maybe_debug_log( "Checking unique RETS photo [$_unique_id] / [$_hashed_id]." );
              }

              //** Looks like image does not exist, so continue process */
              $filetype = preg_split( "%/%", $photo[ 'Content-Type' ] );
              $filename = str_replace( '\\', '/', $image_directory . '/' . $property_unique_id . '_' . ( $image_count + 1 ) . '.' . $filetype[ 1 ] );
              /** Write image data to file */
              @file_put_contents( $filename, $photo[ 'Data' ] );
              if( is_file( $filename ) ) {
                $this_image_size = @getimagesize( $filename );
                //** Check if image is valid, delete if not, and log message if detail is on*/
                if( !$this_image_size ) {
                  if( @unlink( $filename ) && $schedule[ 'log_detail' ] == 'on' ) {
                    class_wpp_property_import::maybe_echo_log( sprintf( __( 'Image %1s downloaded, but appears corrupt - deleting.', ud_get_wpp_importer()->domain ), $image_count ) );
                  }
                  continue;
                }
                //** Set attachment meta */
                $images_meta[ md5( $filename ) ] = array( 'unique_id' => $_unique_id );
                /** Ok, if we're the first image, we're also the featured image */
                $image_data[ 'images' ][ ] = $filename;
              } else {
                Throw new exception( "Could not save image {$property_unique_id} to {$filename}. " );
              }
            } catch ( Exception $e ) {
              self::maybe_echo_log( $e->getMessage() );
            }
          }
        } else {
          //** Ok, we have the property object, lets see if we can abstract the images from it */
          if( ( $raw_images = ( isset( $property[ 'wpp::images' ] ) ? $property[ 'wpp::images' ] : false ) ) ) {
            $image_data = @json_decode( html_entity_decode( $raw_images ), 1 );
          }
        }
      } catch ( Exception $e ) {
        /** Don't really do anything, except log it out */
        self::maybe_debug_log( 'Error on RETS image import for Property ID # ' . $property[ 'ID' ] . ' : ' . $e->getMessage() );
        self::maybe_echo_log( 'Oops, there was an issue: ' . $e->getMessage() );
      }
      /** Ok, bail if we're having issues */
      if( !is_array( $image_data ) ) {
        throw new Exception( 'Image data could not be properly decoded.' );
      }

      /** Now, go through the images and attach them all */
      $attached_images = array();

      if( isset( $image_data[ 'images' ] ) && is_array( $image_data[ 'images' ] ) && count( $image_data[ 'images' ] ) ) {
        $image_count = 0;
        $last_photos = count($image_data[ 'images' ]);
        foreach( $image_data[ 'images' ] as $image ) {
          if( !empty( $schedule[ 'limit_images' ] ) && count( $attached_images ) >= $schedule[ 'limit_images' ] ) {
            break;
          }
          $image_count ++;
          $attached_image = self::attach_image( array(
            'post_id' => $post_id,
            'image' => $image,
            'data' => $property,
            'mode' => 'u',
            'schedule_settings' => $schedule,
            'schedule_id' => $schedule_id,
          ) );

          // Featured Image Start
          if(isset($schedule['automatically_feature_image_enabled']) && $schedule['automatically_feature_image_enabled']){
            if( $image_count == 1                && !empty( $schedule[ 'automatically_feature_image' ] ) && $schedule[ 'automatically_feature_image' ] == 'first'){
              add_post_meta( $post_id, '_thumbnail_id', $attached_image[ 'thumb_id' ] );
            }
            elseif( $image_count == $last_photos && !empty( $schedule[ 'automatically_feature_image' ] ) && $schedule[ 'automatically_feature_image' ] == 'last' ){
              add_post_meta( $post_id, '_thumbnail_id', $attached_image[ 'thumb_id' ] );
            }
          }
          // Featured Image End

          if( $attached_image ) {
            $attached_images[ ] = $attached_image[ 'thumb_id' ];
            if( !empty( $images_meta[ md5( $image ) ] ) ) {
              foreach( (array)$images_meta[ md5( $image ) ] as $meta_key => $meta_value ) {
                update_post_meta( $attached_image[ 'thumb_id' ], $meta_key, $meta_value );
              }
            }
            self::maybe_echo_log( "Imported image with thumb {$attached_image['thumb_id']}." );
          }
        }
      }

      //** Automatically setup slideshows */
      if( !empty( $attached_images ) && $schedule[ 'automatically_load_slideshow_images' ] == 'on' ) {
        update_post_meta( $post_id, 'slideshow_images', $attached_images );
        class_wpp_property_import::maybe_echo_log( "Imported images have been automatically loaded to property slideshow images." );
      }

      /** So, remove the keys we no longer need, and then also publish the post */
      $post = array_filter( array(
        'ID' => $post_id,
        'post_title' => isset( $property[ 'wpp::post_title' ] ) ? $property[ 'wpp::post_title' ] : str_replace( ' (' . __( 'Pending Image Downloads', ud_get_wpp_importer()->domain ) . ')', ' ', $property[ 'post_title' ] ),
        'post_status' => $property[ 'post_status' ] == 'draft' ? 'publish' : null,
      ) );
      /** Get rid of the rest */
      $remove_keys = array(
        'wpp::image_status',
        'wpp::images',
        'wpp::post_title'
      );
      foreach( $remove_keys as $key ) {
        delete_post_meta( $post_id, $key );
      }
      /** Go ahead and update the property now */
      wp_update_post( $post );
      /** Log it */
      self::maybe_echo_log( 'Updated post.' );
      /** Ok, if we have a post id, try to make sure the directory has something in it */
      if( is_numeric( $post_id ) ) {
        $post_image_directory = class_wpp_property_import::create_import_directory( array( 'post_id' => $post_id ) );
        if( is_array( $post_image_directory ) && isset( $post_image_directory[ 'post_dir' ] ) && is_dir( $post_image_directory[ 'post_dir' ] ) ) {
          $folders = scandir( $post_image_directory[ 'post_dir' ] );
          foreach( $folders as $key => $value ) {
            if( $value == '.' || $value == '..' ) {
              unset( $folders[ $key ] );
            }
          }
          if( !count( $folders ) ) {
            self::maybe_echo_log( 'Removing empty post directory.' );
            self::rrmdir( $post_image_directory[ 'post_dir' ] );
          }
        }
      }
      /** Do new thread shot. */
      self::maybe_echo_log( 'Attempting to run the next management job.' );
      self::maybe_schedule_cron( 'wpp_manage_pending_images', array( 'wpp_manage_pending_images' => $schedule_id ) );
      self::maybe_run_cron( 'wpp_manage_pending_images', array( 'wpp_manage_pending_images' => $schedule_id ), true );
      /** Now that we're done with all that, lets remove the temp directory */
      self::maybe_echo_log( 'Removing the temporary image directory.' );
      self::rrmdir( $image_directory );
    } catch ( Exception $e ) {
      self::maybe_debug_log( 'Error on RETS image import for Property ID # ' . $property[ 'ID' ] . ' : ' . $e->getMessage() );
      self::maybe_echo_log( 'Oops, there was an issue with updating the images: ' . $e->getMessage() );
      /** Ok, we have the thing here, let's update our image status to failed */
      if( is_numeric( $post_id ) ) {
        update_post_meta( $post_id, 'wpp::image_status', 'failed::' . $e->getMessage() );
      }
      /** Also, we need to try and remove the image directory if it exists */
      if( is_string( $image_directory ) && is_dir( $image_directory ) ) {
        self::rrmdir( $image_directory );
      }
      /** Do new thread call */
      self::maybe_echo_log( 'Attempting to run the next management job.' );
      self::maybe_schedule_cron( 'wpp_manage_pending_images', array( 'wpp_manage_pending_images' => $schedule_id ) );
      self::maybe_run_cron( 'wpp_manage_pending_images', array( 'wpp_manage_pending_images' => $schedule_id ), true );
      /** Toss the exception up the chain */
      throw $e;
    }

  }

  /**
   * Attempts to loop through a directory and remove everything recursively
   */
  static public function rrmdir( $dir ) {
    self::maybe_echo_log( 'Attempting to remove directory: ' . $dir );
    foreach( glob( $dir . '/*' ) as $file ) {
      if( is_dir( $file ) ) {
        @rrmdir( $file );
      } else {
        @unlink( $file );
      }
    }
    $_result = @rmdir( $dir );

    self::maybe_echo_log( 'Finished removing directory: ' . $dir );

    return $_result;
  }

  /**
   * This function simply checks to make sure an event isn't scheduled, before trying to add another one
   * in wp_cron, so that wp_cron will act as a failsafe for anything we might not be handling
   *
   * @since 4.0
   */
  static public function maybe_schedule_cron( $job_identifier, $args ) {
    /** Make sure that args is an array */
    if( !is_array( $args ) ) {
      $args = array( $args );
    }
    if( !wp_next_scheduled( $job_identifier, $args ) ) {
      /** Schedule it for 1 second since the epoch */
      wp_schedule_single_event( 1, $job_identifier, $args );
    }
  }

  /**
   * This function uses wp_remote_get to cause the cron job to run immediately, and then removes
   * the job once it is successful
   *
   * @since 4.0
   */
  static public function maybe_run_cron( $job_identifier, $args, $force = false ) {
    global $wpp_property_import;

    /** Make sure that args is an array */
    if( !is_array( $args ) ) {
      $args = array( $args );
    }

    /** Now, ensure the event is scheduled, and run */
    if( $force || wp_next_scheduled( $job_identifier, $args ) ) {
      /** Build the URL, and run the job with wp_remote_get, then remove the job from the cron queue */
      wp_unschedule_event( 1, $job_identifier, $args );
      /** Create our args */
      $url = home_url() . '/?echo_log=true';
      foreach( $args as $key => $value ) {
        $url .= "&" . urlencode( $key ) . "=" . urlencode( $value );
      }
      /** Add our cache buster */
      $url .= '&cb=' . rand();
      /**
       * There might be a way to do this, if only it was non blocking - williams@ud
      if( defined( 'WP_CLI' ) && WP_CLI ){
       * $cli_args = array( 'xmli', $job_identifier, $args[ $job_identifier ] );
       * unset( $args[ $job_identifier ] );
       * $cli_args = array_merge( $cli_args, array_values( $args ) );
       * WP_CLI::run_command( $cli_args, array() );
       * } */

      if( !defined( 'XMLI_SYSTEM_COMMAND_CRON' ) ) {
        $const = false;
        //** Get schedule data */
        if( count( $identifier = array_intersect( array_keys( self::$static_request_map ), array_keys( $args ) ) ) ) {
          $identifier = array_shift( $identifier );
          if( empty( $identifier ) ) {
            return false;
          }
          $to_find = $args[ $identifier ];
          /** Try to find the proper schedule and get option */
          foreach( $wpp_property_import[ 'schedules' ] as $id => $data ) {
            if( $id == $to_find || ( isset( $data[ 'hash' ] ) && $data[ 'hash' ] == $to_find ) ) {
              $const = ( isset( $data[ 'run_system_command_cron' ] ) && $data[ 'run_system_command_cron' ] == 'on' ) ? true : false;
              break;
            }
          }
        }
        define( 'XMLI_SYSTEM_COMMAND_CRON', $const );
      }

      /** Run a normal curl command, although we're not relying on Curl, as it doesn't handle non blocking requests well */
      self::maybe_echo_log( 'Attempting to call URL: ' . $url );
      add_filter( 'use_curl_transport', create_function( '$value', 'return false;' ) );
      if( !XMLI_SYSTEM_COMMAND_CRON ) {
        $result = wp_remote_get( $url, array( 'blocking' => false, 'sslverify'   => false, 'headers' => array( 'Cache-Control' => 'private, max-age=0, no-cache, no-store, must-revalidate' ) ) );
        do_action( 'xmli_wp_remote_get', $url );
      } else {
        exec( 'nohup curl "' . $url . '" > /dev/null 2>&1 &' );
      }
    }
  }

  /**
   * Performs the import while in browser.
   *
   */
  static public function handle_browser_import( $sch_id, $import_data ) {
    global $wpp_import_result_stats;
    //** Match found.  **/
    $_REQUEST[ 'wpp_schedule_import' ] = true;
    $_REQUEST[ 'schedule_id' ] = $sch_id;
    $_REQUEST[ 'wpp_action' ] = 'execute_schedule_import';
    $_REQUEST[ 'echo_log' ] = 'true';

    //** Try to increase memory_limit if it's less than 1024M */
    $memory_limit = @ini_get( 'memory_limit' );
    if( (int)$memory_limit < 1024 && $memory_limit != '-1' ) {
      @ini_set( 'memory_limit', '1024M' );
      $memory_limit = @ini_get( 'memory_limit' );
    }

    if( !headers_sent() ) {
      header( 'Cache-Control:private, no-cache, no-store' );
    }

    class_wpp_property_import::maybe_echo_log( sprintf( __( 'Starting ' . ( defined( 'WP_CLI' ) && WP_CLI ? 'CLI' : 'Browser' ) . '-Initiated Import: %1s. Using XML Importer %2s and WP-Property %3s.', ud_get_wpp_importer()->domain ), $import_data[ 'name' ], WPP_XMLI_Version, WPP_Version ) );
    self::maybe_echo_memory_usage( sprintf( __( 'on process start. Memory limit: %s. Before %s', ud_get_wpp_importer()->domain ), $memory_limit, 'admin_ajax_handler()' ), $sch_id );
    class_wpp_property_import::admin_ajax_handler();

    $last_time_entry = class_wpp_property_import::maybe_echo_log( "Total run time %s seconds.", true, true, true );

    if( !isset( $_REQUEST[ 'output' ] ) || $_REQUEST[ 'output' ] != 'xml' ) {
      echo $last_time_entry[ 'message' ];
    }

    $total_processing_time = $last_time_entry[ 'timetotal' ];

    if( is_array( $wpp_import_result_stats ) ) {

      $added_properties = isset( $wpp_import_result_stats[ 'quantifiable' ][ 'added_properties' ] ) ? $wpp_import_result_stats[ 'quantifiable' ][ 'added_properties' ] : 0;
      $updated_properties = isset( $wpp_import_result_stats[ 'quantifiable' ][ 'updated_properties' ] ) ? $wpp_import_result_stats[ 'quantifiable' ][ 'updated_properties' ] : 0;
      $total_properties = $added_properties + $updated_properties;

      if( $total_properties ) {
        $time_per_property = round( ( $total_processing_time / $total_properties ), 3 );
      }

      if( isset( $time_per_property ) && $time_per_property ) {
        $wpp_import_result_stats[ ] = $last_time_entry[ 'timetotal' ] . ' seconds total processing time, averaging ' . $time_per_property . ' seconds per property.';
      }

      unset( $wpp_import_result_stats[ 'quantifiable' ] );

      $result_stats = '<ul class="summary"><li>' . implode( '</li><li>', $wpp_import_result_stats ) . '</li></ul>';

      if( !isset( $_REQUEST[ 'output' ] ) || $_REQUEST[ 'output' ] != 'xml' ) {
        self::maybe_echo_log( $result_stats );
      }
    }

    if( isset( $import_data[ 'send_email_updates' ] ) && $import_data[ 'send_email_updates' ] == 'on' ) {
      //** Send email about import end with all data. */
      class_wpp_property_import::email_notify( $result_stats, ' ' . $import_data[ 'name' ] . ' ( #' . $sch_id . ' ) is complete.' );
    }

  }

  /**
   * Adds Custom capability to the current premium feature
   * @param $capabilities
   * @return
   */
  static public function add_capability( $capabilities ) {

    $capabilities[ self::$capability ] = __( 'Manage XML Importer', ud_get_wpp_importer()->domain );;

    return $capabilities;
  }

  /**
   * Modify body class of imported properties on back-end
   *
   */
  static public function admin_body_class( $id ) {
    global $current_screen, $post;

    if( $current_screen->id == 'property' ) {

      $wpp_import_schedule_id = get_post_meta( $post->ID, 'wpp_import_schedule_id', true );

      if( $wpp_import_schedule_id ) {
        return 'wpp_property_edit wpp_imported_property';
      } else {
        return 'wpp_property_edit';
      }

    }

  }

  /**
   * Hook.
   * Runs on save post.
   *
   * @author peshkov@UD
   * @since 4.0
   */
  static public function save_post( $post_id ) {
    if( isset( $_POST[ 'wpp::disable_xmli_update' ] ) ) {
      update_post_meta( $post_id, 'wpp::disable_xmli_update', $_POST[ 'wpp::disable_xmli_update' ] );
    }
  }

  /**
   * Displays information on property editing pages for properties that came from an XML Import
   *
   */
  static public function wpp_publish_box_options( $id ) {
    global $post, $wp_properties;

    if( !$wpp_import_schedule_id = get_post_meta( $post->ID, 'wpp_import_schedule_id', true ) ) {
      return;
    }

    //** Get time stamp from new format ( Version 2.6.0+ ) */
    $import_time = get_post_meta( $post->ID, 'wpp_import_time', true );

    //** Get time stamp from old meta_key ( pre-version 2.6.0 ) if new meta_key does not exist */
    if( empty( $import_time ) ) {
      $import_time = get_post_meta( $post->ID, 'wpp_xml_import', true );
    }

    $import_url = admin_url( "edit.php?post_type=property&page=wpp_property_import#{$wpp_import_schedule_id}" );

    $import_name = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $wpp_import_schedule_id ][ 'name' ];

    $disable_update = get_post_meta( $post->ID, 'wpp::disable_xmli_update', true );
    $text = __( 'Ignore updates on XMLI process', ud_get_wpp_importer()->domain );

    if( !empty( $import_time ) ) {
      $import_time = date_i18n( __( 'M j, Y @ G:i', ud_get_wpp_importer()->domain ), strtotime( $import_time ) );
      ?>

      <div class="misc-pub-section-last">
        <?php echo WPP_F::checkbox( "name=wpp::disable_xmli_update&id=wpp_xmli_disable_update&label=$text", $disable_update ); ?>
      </div>

      <div class="misc-pub-section xml_import_time misc-pub-section-last">
        <span class="wpp_i_time_stamp"><?php printf( __( 'Imported on: <b>%1$s</b> <a href="%2$s" title="%3$s">Importer</a>', ud_get_wpp_importer()->domain ), $import_time, $import_url, $import_name ); ?>
          <b></b></span>
      </div>
    <?php
    }

  }

  /**
   * Deletes a non empty directory, directory must end with '/'
   *
   */
  static public function delete_directory( $dirname, $delete_files = false ) {

    if( is_dir( $dirname ) ) {
      $dir_handle = opendir( $dirname );
    } else {
      return 0;
    }

    while( $file = readdir( $dir_handle ) ) {

      if( $file == '.' || $file == '..' ) {
        continue;
      }

      if( $delete_files && !is_dir( trailingslashit( $dirname ) . $file ) ) {
        unlink( $dirname . "/" . $file );
      }

      if( is_dir( trailingslashit( $dirname ) . $file ) ) {
        class_wpp_property_import::delete_directory( trailingslashit( $dirname ) . $file, $delete_files );
      }

    }

    closedir( $dir_handle );

    return @rmdir( $dirname );
  }

  /**
   * Get Orphan Attachments
   *
   */
  static public function get_orphan_attachments( $dirname = false ) {
    global $wpdb;
    $orphan = $wpdb->get_col( "
    SELECT ID FROM {$wpdb->posts}
    WHERE (
      post_title = 'Property Image'
      OR post_content LIKE  'PDF flyer for%'
      OR LENGTH( post_content_filtered ) = 32
    )  AND ( post_parent = 0 AND post_type ='attachment')" );

    return $orphan;

  }

  /**
   * Deletes a non empty directory, directory must end with '/'
   * In support of 'delete_post'
   */
  static public function delete_orphan_directories( $dirname = false ) {

    if( !$dirname ) {
      $uploads = wp_upload_dir();
      $dirname = trailingslashit( $uploads[ 'basedir' ] ) . 'wpp_import_files';
    }

    if( is_dir( $dirname ) ) {
      $dir_handle = opendir( $dirname );
    } else {
      return false;
    }

    while( $file = readdir( $dir_handle ) ) {
      if( $file == "." || $file == ".." || $file == 'temp' ) {
        continue;
      }

      if( is_dir( trailingslashit( $dirname ) . $file ) ) {
        class_wpp_property_import::delete_directory( trailingslashit( $dirname ) . $file );
      }

    }

    closedir( $dir_handle );

    if( @rmdir( $dirname ) ) {
      //class_wpp_property_import::maybe_echo_log( "Removed directory: {$dirname}" );
      return true;
    } else {
      return false;
    }

  }

  /**
   * Adds a colum to the overview table
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function wpp_stat_filter_wpp_xml_import( $timestamp ) {
    return human_time_diff( $timestamp ) . ' ' . __( 'ago', ud_get_wpp_importer()->domain );
  }

  /**
   * Adds a colum to the overview table
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function wpp_admin_overview_columns( $columns ) {
    $columns[ 'wpp_xml_import' ] = __( 'Last Import', ud_get_wpp_importer()->domain );
    return $columns;
  }

  /**
   * Renders Memory Usage Log if 'Enable detailed logging to assist with troubleshooting' option is enabled
   *
   * @staticvar int $last_usage
   * @param string $text
   * @param int $schedule_id
   * @since 4.0
   * @author peshkov@UD
   */
  static public function maybe_echo_memory_usage( $text = '', $schedule_id = false ) {
    global $wp_properties;
    static $last_usage = 0;

    $schedule = false;
    if( !empty( $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] ) ) {
      $schedule = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ];
    }
    if( !$schedule || empty( $schedule[ 'log_detail' ] ) ) {
      return false;
    }

    $differents = $last_usage ? number_format( ( memory_get_usage() / 1024 / 1024 ) - $last_usage, 3 ) . 'Mb' : __( 'none', ud_get_wpp_importer()->domain );
    $current_usage = number_format( $last_usage = memory_get_usage() / 1024 / 1024, 3 ) . 'Mb';
    $log = sprintf( __( "Memory Usage: %s. Difference: %s. Details: %s", "wpp" ), $current_usage, $differents, !empty( $text ) ? $text : __( 'none', ud_get_wpp_importer()->domain ) );
    $log = "<span style=\"color:green;\">{$log}</span>";
    self::maybe_echo_log( $log );
  }

  /**
   * Adds logs to file in uploads directory if constant UD_FILE_DEBUG_LOG defined
   *
   * @since 4.0.8
   * @author peshkov@UD
   */
  static public function maybe_debug_log( $message ) {
    if( is_callable( array( 'WPP_F', 'maybe_debug_log' ) ) ) {
      $m = WPP_F::maybe_debug_log( $message, 'xmli' );
      @ob_flush();
      flush();
      return $m;
    }

    if( defined( 'WPP_DEBUG_MODE' ) && defined( 'WP_DEBUG_LOG' ) ) {
      error_log( 'wp-property-importer:debug: ' . print_r( $message, true ) . '' );
    }

    return false;
  }

  /**
   * Checks if the current view should display a log during import, or perform the import silently.
   *
   * If should be echoed, does so, unless explicitly told not to.
   * If no text is passed, returns bool
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function maybe_echo_log( $text = false, $echo = true, $last_entry = false, $return_times = false ) {
    global $wpp_runtime_log, $wpp_import_result_stats;

    if( defined( 'WPP_DEBUG_MODE' ) && defined( 'WP_DEBUG_LOG' ) ) {
      error_log( 'wp-property-importer:log: ' . print_r( $text, true ) . '' );
    };

    $newline = ( ( php_sapi_name() == 'cli' || defined( 'DOING_WPP_CRON' ) ) ? PHP_EOL : '<br />' . PHP_EOL );

    if( empty( $wpp_runtime_log[ 'first_entry' ] ) ) {
      $mtime = explode( ' ', microtime() );
      $timestart = $mtime[ 1 ] + $mtime[ 0 ];
      $wpp_runtime_log[ 'first_entry' ] = $timestart;
    }

    if( $last_entry && isset( $wpp_runtime_log[ 'first_entry' ] ) ) {

      $mtime = microtime();
      $mtime = explode( ' ', $mtime );
      $timeend = $mtime[ 1 ] + $mtime[ 0 ];
      $timetotal = $timeend - $wpp_runtime_log[ 'first_entry' ];

      $timetotal = ( function_exists( 'number_format_i18n' ) ) ? number_format_i18n( $timetotal ) : number_format( $timetotal );
      $text = str_replace( '%s', $timetotal, $text );

    }

    if( !$text ) {
      return;
    }

    /** Return time, meant for running at end of script */
    if( $return_times ) {

      if( ( defined( 'WP_CLI' ) && WP_CLI ) || ( isset( $_REQUEST[ 'output' ] ) && $_REQUEST[ 'output' ] == 'xml' ) ) {
        return array(
          'timetotal' => $timetotal,
          'message' => __( 'Time: ', ud_get_wpp_importer()->domain ) . date( 'H:i:s' ) . ': ' . $text . $newline
        );
      } else {
        return array(
          'timetotal' => $timetotal,
          'message' => '<span class="time">' . __( 'Time: ', ud_get_wpp_importer()->domain ) . date( 'H:i:s' ) . ':</span>' . $text . $newline
        );
      }

    }

    /** If we're on the command line, spit it out */
    if( defined( 'WP_CLI' ) && WP_CLI ) {
      WP_CLI::line( trim( strip_tags( html_entity_decode( $text ) ) ) );
    } elseif( count( $request = array_intersect( array_keys( self::$static_request_map ), array_keys( $_REQUEST ) ) ) && $_REQUEST[ 'echo_log' ] == 'true' ) {
      /** Otherwise, we're handling a browser import */
      if( $text && $echo ) {
        if( !isset( $_REQUEST[ 'do_not_pad' ] ) ) {
          $end = str_pad( $newline, 4096 );
        } else {
          $end = $newline;
        }
        if( isset( $_REQUEST[ 'output' ] ) && $_REQUEST[ 'output' ] == 'xml' ) {
          echo "<entry>\n";
          echo "\t<timestamp>" . time() . "</timestamp>\n";
          echo "\t<time>" . date( 'H:i:s' ) . "</time>\n";
          echo "\t<event>" . $text . "</event>\n";
          echo "</entry>\n";
        } elseif( php_sapi_name() == 'cli' || defined( 'DOING_WPP_CRON' ) ) {
          echo __( 'Time: ', ud_get_wpp_importer()->domain ) . date( 'H:i:s' ) . $text . '' . $end;
        } else {
          echo '<span class="time">' . __( 'Time: ', ud_get_wpp_importer()->domain ) . date( 'H:i:s' ) . ':</span>' . $text . '' . $end;
        }
        if( !isset( $_REQUEST[ 'do_not_pad' ] ) ) {
          ob_flush();
          flush();
        }
      } else {
        return $echo;
      }
    }

    return false;
  }

  /**
   * Called via AJAX function from settings page to display the import history
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function wpp_ajax_show_xml_imort_history() {
    global $wpdb;

    $imported = $wpdb->get_results( "SELECT post_title, post_id, meta_value FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE meta_key = 'wpp_import_time' AND meta_value != '' AND post_title IS NOT NULL ORDER BY meta_value DESC LIMIT 0, 500" );

    echo "<ol style='padding-left: 10px;'>";
    foreach( $imported as $object )
      echo '<li><a href="' . get_permalink( $object->post_id ) . '">' . $object->post_title . '</a> Edit: <a href="' . get_edit_post_link( $object->post_id ) . ' ">( ' . $object->post_id . ' )</a> - ' . human_time_diff( $object->meta_value ) . ' ago</li>';
    echo "</ol>";

    die();
  }

  /**
   * Add things to Help tab
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function wpp_settings_help_tab() {
    global $wp_properties, $wpdb;

    wp_enqueue_script( 'wpp-settings-xmli', ud_get_wpp_importer()->path( 'static/scripts/wpp.admin.settings.xmli.js' ), array( 'jquery', 'wp-property-global' ), ud_get_wpp_importer()->version );

    //** Check for orphan images */
    $orphan_attachments = count( class_wpp_property_import::get_orphan_attachments() );
    ?>
    <?php if( $orphan_attachments ) : ?>
      <div class="wpp_settings_block"><?php printf( __( 'There are (%1s) unattached files related to listings that were imported using the XML Importer.', ud_get_wpp_importer()->domain ), $orphan_attachments ); ?>
        <input type="button" value="<?php _e( 'Delete Unattached Files', ud_get_wpp_importer()->domain ) ?>" class="wppi_delete_all_orphan_attachments">
        <div class="hidden wppi_delete_all_orphan_attachments_result wpp_class_pre" style="height: auto;"></div>
        <div class="description"></div>
      </div>
    <?php endif; ?>

    <div class='wpp_settings_block'><?php _e( 'Look up XML import history.', ud_get_wpp_importer()->domain ) ?>
      <input type="button" class="button" value="<?php _e( 'Show XML Import History', ud_get_wpp_importer()->domain ) ?>" id="wpp_ajax_show_xml_imort_history">
      <div class="hidden wpp_ajax_show_xml_imort_history_result wpp_class_pre"></div>
      <div class="description"><?php _e( 'Show last 500 imported items in descending order.', ud_get_wpp_importer()->domain ) ?></div>
    </div>
  <?php

  }

  /**
   * Hooks into 'admin_init'
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function admin_init() {
    global $wpp_property_import, $wp_properties, $wp_messages;

    if( isset( $wpp_property_import[ 'settings' ][ 'allow_xml_uploads_via_media_uploader' ] ) && $wpp_property_import[ 'settings' ][ 'allow_xml_uploads_via_media_uploader' ] == 'true' ) {
      add_filter( 'upload_mimes', array( 'class_wpp_property_import', 'add_upload_mimes' ) );
    }

    // Download backup of configuration
    if(
      isset( $_REQUEST[ 'page' ] )
      && $_REQUEST[ 'page' ] == 'wpp_property_import'
      && isset( $_REQUEST[ 'wpp_action' ] )
      && $_REQUEST[ 'wpp_action' ] == 'download-wpp-import-schedule'
      && isset( $_REQUEST[ '_wpnonce' ] )
      && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'download-wpp-import-schedule' )
    ) {

      $schedule_id = $_REQUEST[ 'schedule_id' ];

      $schedule_data = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ];

      $filename[ ] = 'wpp-schedule';
      $filename[ ] = sanitize_key( get_bloginfo( 'name' ) );
      $filename[ ] = sanitize_key( $schedule_data[ 'name' ] );
      $filename[ ] = date( 'Y-m-d' ) . '.json';

      header( "Cache-Control: public" );
      header( "Content-Description: File Transfer" );
      header( "Content-Disposition: attachment; filename=" . implode( '-', $filename ) );
      header( "Content-Transfer-Encoding: binary" );
      header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ), true );

      echo json_encode( $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] );

      die();

    }

    //* Handle Import of schedule from an uploaded file */
    if(
      isset( $_REQUEST[ 'page' ] )
      && $_REQUEST[ 'page' ] == 'wpp_property_import'
      && isset( $_REQUEST[ 'wpp_action' ] )
      && $_REQUEST[ 'wpp_action' ] == 'import_wpp_schedule'
      && isset( $_REQUEST[ '_wpnonce' ] )
      && wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'wpp_import_import_schedule' )
    ) {

      if( $backup_file = $_FILES[ 'wpp_import' ][ 'tmp_name' ][ 'import_schedule' ] ) {

        $imported_schedule = file_get_contents( $backup_file );

        if( !empty( $imported_schedule ) ) {
          $imported_schedule = @json_decode( $imported_schedule, true );
        }

        if( is_array( $imported_schedule ) ) {

          $schedule_id = time();

          // generate new hash
          $imported_schedule[ 'hash' ] = md5( sha1( $schedule_id ) );
          $imported_schedule[ 'name' ] = $imported_schedule[ 'name' ] . ' ' . __( '( Imported )', ud_get_wpp_importer()->domain );

          $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] = $imported_schedule;

          update_option( 'wpp_settings', $wp_properties );

          wp_redirect( admin_url( "edit.php?post_type=property&page=wpp_property_import&message=imported" ) );

        } else {
          $wp_messages[ 'error' ][ ] = __( 'Schedule coult not be imported.', ud_get_wpp_importer()->domain );
        }

      }
    }
  }

  /**
   * Add XML/CSV/JSON mimes to allow WP Media Uploader to handle import files
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function add_upload_mimes( $current ) {
    global $wpp_property_import;

    $current[ 'xml' ] = 'text/xml';
    $current[ 'csv' ] = 'text/csv';
    $current[ 'json' ] = 'application/json';
    $current[ 'json' ] = 'text/json';

    return $current;

  }

  /**
   * Hooks into 'admin_enqueue_scripts"
   *
   * Loads all admin scripts.
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function admin_enqueue_scripts() {
    global $wpp_property_import, $current_screen;

    wp_register_script( 'wpp-xmli', ud_get_wpp_importer()->path( 'static/scripts/wpp.admin.xmli.js' ), array( 'jquery', 'wp-property-global' ), ud_get_wpp_importer()->version );

    if( !isset( $current_screen->id ) ) {
      return;
    }

    if( $current_screen->id == 'property_page_wpp_property_import' ) {
      wp_enqueue_script( 'wp-property-backend-global' );
      wp_enqueue_script( 'wp-property-global' );
      wp_enqueue_script( 'wpp-xmli' );

      wp_enqueue_style( 'wpp-xmli', ud_get_wpp_importer()->path( 'static/styles/style.css' ), array(), ud_get_wpp_importer()->version );
    }

  }

  /**
   * Hooks into 'admin_menu"
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function admin_menu() {
    global $wpp_property_import;
    $page = add_submenu_page( 'edit.php?post_type=property', __( 'Importer', ud_get_wpp_importer()->domain ), __( 'Importer', ud_get_wpp_importer()->domain ), self::$capability, 'wpp_property_import', array( 'class_wpp_property_import', 'page_main' ) );
    add_action( "load-{$page}", array( 'class_wpp_property_import', 'wpp_importer_page_load' ) );
  }

  /**
   * Load default settings, should only be done on first run
   *
   * Also used to lay out data structure.
   * Don't run this if settings exist, they will be overwritten.
   *
   * Settings boolean values must be stored in string format to compy with WPP
   *
   * @todo Add settings
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function load_default_settings() {
    $d[ 'settings' ] = array(
      'allow_xml_uploads_via_media_uploader' => 'true'
    );
    $d[ 'schedules' ] = false;
    return $d;
  }

  /**
   * Run cron job from hash
   *
   *
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function run_from_cron_hash() {
    global $wpp_property_import, $wpp_import_result_stats, $wp_properties;

    if( !isset( $wpp_property_import ) ) {
      $wpp_property_import = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ];
    }

    if( !defined( 'WPP_DEBUG_MODE' ) ) {
      define( 'WPP_DEBUG_MODE', false );
    }

    //** Cycle through schedules and try to mach. **/
    foreach( (array) $wpp_property_import[ 'schedules' ] as $sch_id => $sch ) {

      if( $sch[ 'hash' ] == WPP_IMPORTER_HASH ) {

        //** Match found.  **/
        $_REQUEST[ 'wpp_schedule_import' ] = true;
        $_REQUEST[ 'schedule_id' ] = $sch_id;
        $_REQUEST[ 'wpp_action' ] = 'execute_schedule_import';
        $_REQUEST[ 'echo_log' ] = ( WPP_DEBUG_MODE === true ? 'true' : 'false' );
        $_REQUEST[ 'do_not_pad' ] = true;
        $_REQUEST[ 'do_not_flush' ] = true;

        if( $sch[ 'send_email_updates' ] == 'on' ) {
          //** Send email about import start */
          /* class_wpp_property_import::email_notify( 'Import has begun.', 'Schedule #'. $sch_id . ' Initiated' ); */
        }

        $import_result = '';

        //** Wrap all echoed data into ob */
        if( !WPP_DEBUG_MODE ) {
          ob_start();
        }

        class_wpp_property_import::maybe_echo_log( sprintf( __( 'Starting Cron-Initiated Import: %1s. Using XML Importer %2s and WP-Property %3s.', ud_get_wpp_importer()->domain ), $sch[ 'name' ], WPP_XMLI_Version, WPP_Version ) );
        class_wpp_property_import::admin_ajax_handler();
        $last_time_entry = class_wpp_property_import::maybe_echo_log( "Total run time %s seconds.", true, true, true );

        if( !WPP_DEBUG_MODE ) {
          $import_result .= ob_get_contents();
          ob_end_clean();
        }

        $total_processing_time = $last_time_entry[ 'timetotal' ];

        if( is_array( $wpp_import_result_stats ) ) {

          $added_properties = $wpp_import_result_stats[ 'quantifiable' ][ 'added_properties' ];
          $updated_properties = $wpp_import_result_stats[ 'quantifiable' ][ 'updated_properties' ];
          $total_properties = $added_properties + $updated_properties;

          if( $total_properties > 0 ) {
            $time_per_property = round( ( $total_processing_time / $total_properties ), 2 );
            $wpp_import_result_stats[ ] = $last_time_entry[ 'timetotal' ] . ' seconds total processing time, averaging ' . $time_per_property . ' seconds per property.';
          }

          unset( $wpp_import_result_stats[ 'quantifiable' ] );

          $result_stats = '<ul class="summary"><li>' . implode( '</li><li>', $wpp_import_result_stats ) . '</li></ul>';
          $cron_result = implode( "\n", $wpp_import_result_stats );

        } else {
          $cron_result = 'No stats were returned by import process.';
        }

        $import_header = $sch[ 'name' ] . ' ( #' . $sch_id . ' ) is complete.';

        if( $sch[ 'send_email_updates' ] == 'on' ) {
          //** Send email about import end with all data. */
          class_wpp_property_import::email_notify( $result_stats . nl2br( $import_result ), $import_header );
        } else {

        }

        //** Display on stats in the cron email. */
        if( !empty( $import_result ) ) {
          die( strtoupper( $import_header ) . "\n\n" . $cron_result . "\n\n" . $import_result );
        }

        die( "\n\n" . $cron_result );

      }

    }

  }

  /**
   * Settings page load handler
   *
   */
  static public function wpp_importer_page_load() {

    $contextual_help[ 'XML Importer Help' ][ ] = '<h3>' . __( "XML Importer Help", "wpp" ) . '</h3>';
    $contextual_help[ 'XML Importer Help' ][ ] = '<p>' . __( 'By default, xPath are executed in the xPath input boxes. <a target="_blank" href="http://www.w3schools.com/xsl/xpath_syntax.asp">W3 Schools XPath Syntax</a>. ', "wpp" ) . '</p>';
    $contextual_help[ 'XML Importer Help' ][ ] = '<p>' . __( 'Example: get all the option values that have a label for "height": <b>options/option[label = "Height"]/value </b>', "wpp" ) . '</p>';

    $contextual_help[ 'RETS' ][ ] = '<p>' . __( '<b>Property Resource Class:</b> Typically this is used to specify the type of property listing, such as Commercial or Residential, the naming convention varies depending on RETS provider. Use <a href="http://rets.usabilitydynamics.com/" target="_blank">rets.usabilitydynamics.com</a> to determine. ', ud_get_wpp_importer()->domain ) . '</p>';
    $contextual_help[ 'RETS' ][ ] = '<p>' . __( '<b>Dynamic DMQL Query Tags:</b> The DMQL query for RETS supports the following dynamic tags: [this_month], [next_month] and [previous_month]. Example to get all listings modified within the current month: (DATE_MODIFIED=[this_month]+); to get all the listings modified before this month: (DATE_MODIFIED=[previous_month]-). These examples assume that the SystemName for the modified data is DATE_MODIFIED, which will actually vary from one MLS provider to the next.</p>', ud_get_wpp_importer()->domain );

    $contextual_help[ 'FTP' ][ ] = '<p>' . __( '<p>Data may be fetched from an FTP source by formatting the URL to start with the <b>ftp://</b> prefix.</p>', ud_get_wpp_importer()->domain );

    $contextual_help[ 'XPath Query to Property Elements' ][ ] = '<h3>' . __( "XPath Query to Property Elements", "wpp" ) . '</h3>';
    $contextual_help[ 'XPath Query to Property Elements' ][ ] = '<p>' . __( 'In order to begin importing data, you must first identify what the "repeating property element" is in the XML file.  Typically this would be something like <b>property</b> or <b>object</b>, where the corresponding XPath rules would be <b>//property</b> or <b>//object</b>, respectively. The easiest way to identify it is to look through the feed for a repeating pattern. The query must select the elements in order to cycle through them and apply the XPath Rules in the Attribute Map section. ', "wpp" ) . '</p>';

    $contextual_help[ 'Import Limits' ][ ] = '<h3>' . __( 'Import Limits', "wpp" ) . '</h3>';
    $contextual_help[ 'Import Limits' ][ ] = '<p>' . __( 'There are two type of limits - the first limit will stop the import after a certain number of objects have been processed before they are checked for quality, while the second limit will stop only after the specified number of objects has actually passsed quality inspection, and have been saved to the database.', "wpp" ) . '</p>';
    $contextual_help[ 'Import Limits' ][ ] = '<p>' . __( 'Limiting imports works well when you are running incremental imports.  A limit of <b>10</b> will stop after 10 properties have been created.  The importer does not count properties that were skipped during import or that already exist in the system - properties that already exist will be marked as updated.', "wpp" ) . '</p>';

    $contextual_help[ 'Running the Import' ][ ] = '<h3>' . __( "Running the Import", "wpp" ) . '</h3>';
    $contextual_help[ 'Running the Import' ][ ] = '<p>' . __( 'There are two ways to process an import, using the browser, or by setting up a cron job.  Using the browser is easy, and viable when you have a small feed, or a very good server. ', "wpp" ) . '</p>';
    $contextual_help[ 'Running the Import' ][ ] = '<p>' . __( 'When working with larger feeds, or for the purposes of automation, it is advisable to execute your import script using a cron job.  For every "Import Schedule" you create, a <b>Cron Command</b> field will be displayed, followed by the command you would need to enter into the cron job builder, for the import schedule to be executed.', "wpp" ) . '</p>';

    $contextual_help[ 'Function: free_text' ][ ] = '<h3>' . __( "Function: free_text", "wpp" ) . '</h3>';
    $contextual_help[ 'Function: free_text' ][ ] = '<p>' . __( 'To insert some common text, use the <b>free_text</b> command, like so: <b>free_text: Imported from Some List</b> and the text will be kept as is.', "wpp" ) . '</p>';

    $contextual_help[ 'Function: free_list' ][ ] = '<h3>' . __( "Function: free_list", "wpp" ) . '</h3>';
    $contextual_help[ 'Function: free_list' ][ ] = '<p>' . __( 'To insert  multiple values, use the <b>free_list</b> function with values separated by comma, like so: <b>free_list: First item, Second item, Third item</b>.', "wpp" ) . '</p>';

    $contextual_help[ 'Function: uppercase' ][ ] = '<h3>' . __( "Function: uppercase", "wpp" ) . '</h3>';
    $contextual_help[ 'Function: uppercase' ][ ] = '<p>' . __( 'To convert all alphabetic characters to uppercase, use the <b>uppercase</b> command, like so: <b>uppercase: {xpath}</b>.', "wpp" ) . '</p>';

    $contextual_help[ 'Function: concat' ][ ] = '<h3>' . __( "Function: concat", "wpp" ) . '</h3>';
    $contextual_help[ 'Function: concat' ][ ] = '<p>' . __( "You can also combine free text wtih xPath rule results using <b>concat</b>, example: <b>concat:http://sourcesite.com/images/'Photo7'</b> will result in the text between the quotes being executed as xPath rules, while text outside of quotes being inserted as it is.", "wpp" ) . '</p>';
    $contextual_help[ 'Function: concat' ][ ] = '<p>' . __( "You can also use concat to combine multiple xPath rules together, for example you can create the Property Title from a few XML attributes: <b>concat:'bedrooms' bedroom house in 'location/city'</b>", "wpp" ) . '</p>';

    $contextual_help[ 'Function: concat_list' ][ ] = '<h3>' . __( "Function: concat_list", "wpp" ) . '</h3>';
    $contextual_help[ 'Function: concat_list' ][ ] = '<p>' . __( 'Example: <b>concat_list root_path="options/option" label_path="label" value_path="value" concat_character=":" paste_together=","</b>  will look for options/option path, then grab child "value" and "label" paths and import them as a single line. If "paste_together" specified then all collected "label / value" pairs will be joined in single-line value using "paste_together".', "wpp" ) . '</p>';

    //** Hook this action is you want to add info */
    $contextual_help = apply_filters( 'wpp_importer_page_help', $contextual_help );

    if( is_callable( array( 'WPP_Core', 'wpp_contextual_help' ) ) ) {
      do_action( 'wpp_contextual_help', array( 'contextual_help' => $contextual_help ) );

    } else if( is_callable( array( 'WP_Screen', 'add_help_tab' ) ) ) {

      //** Loop through help items and build tabs */
      foreach( (array)$contextual_help as $help_tab_title => $help ) {

        //** Add tab with current info */
        get_current_screen()->add_help_tab( array(
          'id' => sanitize_title( $help_tab_title ),
          'title' => $help_tab_title,
          'content' => implode( '', (array)$contextual_help[ $help_tab_title ] ),
        ) );

      }

    }

  }

  /**
   * Handle deleting properties that originated from a feed
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function delete_feed_properties( $schedule_id, $schedule_settings, $exclude = false ) {
    global $wpdb, $wp_properties;
    if( !is_array( $exclude ) ) {
      $exclude = false;
    }
    self::maybe_echo_log( 'Attempting to delete properties for: ' . $schedule_id );
    if( $all_feed_properties = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE post_type = 'property' AND meta_key = 'wpp_import_schedule_id' and meta_value = %s  GROUP BY p.ID", $schedule_id ) ) ) {
      $r[ 'total_found' ] = count( $all_feed_properties );
      class_wpp_property_import::maybe_echo_log( sprintf( __( 'Found %1s properties from database that were imported from this feed.', ud_get_wpp_importer()->domain ), $r[ 'total_found' ] ) );

      foreach( $all_feed_properties as $property_id ) {
        //** If an array of property IDs to exclude is passed, check if property is in array, if so - bail */
        if( $exclude && in_array( $property_id, $exclude ) ) {
          continue;
        }
        //** Delete the actual object */
        if( wp_delete_post( $property_id, true ) ) {
          $r[ 'deleted_objects' ][ ] = $property_id;
          class_wpp_property_import::maybe_echo_log( sprintf( __( 'Property ID %1s has been deleted. Total deleted so far: %2s', ud_get_wpp_importer()->domain ), $property_id, count( $r[ 'deleted_objects' ] ) ) );
        } else {
          //** Unable to delete property for some reason.
        }
      }
      if( isset(  $r[ 'deleted_objects' ] ) && is_array( $r[ 'deleted_objects' ] ) ) {
        $r[ 'deleted_count' ] = count( $r[ 'deleted_objects' ] );
      }
      if( isset( $property_delete_counter ) && $r[ 'total_found' ] != $property_delete_counter ) {
        $r[ 'remaining' ] = ( $r[ 'total_found' ] - $property_delete_counter );
      }
    } else {
      $r[ 'total_found' ] = 0;
    }
    return $r;
  }

  /**
   * Handle all admin ajax actions
   *
   * Success or failure is returned as string 'true' or 'false in $return['success']
   * Whatever is to be displayed return in $return['ui']
   *
   * @todo $data and $schedule_settings seem to be used intermittently, should consolidate into one
   * @todo see if raw_preview and source_evaluation do not do some of the same functions
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function admin_ajax_handler( $from_cron = false ) {
    global $wpp_property_import, $wp_properties, $wpdb, $wpp_import_result_stats;

    $wpp_action = isset( $_REQUEST[ 'wpp_action' ] ) ? $_REQUEST[ 'wpp_action' ] : false;
    $action_type = isset( $_REQUEST[ 'wpp_action_type' ] ) ? $_REQUEST[ 'wpp_action_type' ] : false;
    $do_not_use_cache = isset( $_REQUEST[ 'do_not_use_cache' ] ) ? true : false;
    $schedule_settings = false;
    //** wpp_schedule_import is passed when an actual import is being executed, it is supposed to contain the hash.  If it does not, schedule_id should be set. */
    $wpp_schedule_import = ( !empty( $_REQUEST[ 'wpp_schedule_import' ] ) ? $_REQUEST[ 'wpp_schedule_import' ] : false );
    //** $schedule_id should always be passed, otherwise we will not be able to use cache. If no ID, set it to false. */
    $schedule_id = ( !empty( $_REQUEST[ 'schedule_id' ] ) ? $_REQUEST[ 'schedule_id' ] : false );

    /*
      The following switch goes through various actions that can be handled, and the results are loaded into an array
      which is returned in JSON format after the switch is complete

      The following are the currently used.

      $result[ui] = whatever will be displayed, can contain HTML
      $result[success]   = true or false
      $result[data_from_cache]  = if the current data is loaded from cache
      $result[message]  = a non-HTML message
      $result[schedule_id]  = if working with a schedule, it's ID
      $result[hash]   = hash of schedule
      $result[common_tag_name]  = used when auto-matching and WPP tries to guess the tag name
      $result[auto_matched_tags]  = used when auto-matching , returns array of found tags
    */
    $result = array(
      'schedule_exists' => !empty( $wpp_property_import[ 'schedules' ][ $schedule_id ] ) ? true : false,
      'success' => empty( $wpp_action ) ? 'false' : 'true',
    );

    $data = !empty( $wpp_property_import[ 'schedules' ][ $schedule_id ] ) ? array( 'wpp_property_import' => $wpp_property_import[ 'schedules' ][ $schedule_id ] ) : array();

    //** Load the import data, this is used by CRON and Browser Access */
    if( defined( 'DOING_WPP_CRON' ) || ( $wpp_schedule_import && $result[ 'schedule_exists' ] ) ) {
      $doing_full_import = true;
    } elseif( $wpp_action == 'execute_schedule_import' && !empty( $_REQUEST[ 'data' ] ) ) {
      //** Entire data array is passed, this happens when a schedule is Saved or "Preview Import" has been initiated */
      parse_str( $_REQUEST[ 'data' ], $data );
      $data = stripslashes_deep( $data );
      //** When we are running a preview on an unsaved schedule ( schedule may exist, but changes made and not commited ) - which happens a lot when testing */
      if( ( isset( $_REQUEST[ 'raw_preview' ] ) && $_REQUEST[ 'raw_preview' ] == 'true' ) || $action_type == 'source_evaluation' || $_REQUEST[ 'preview' ] == 'true' ) {
        $preview_import = true;
        /* Generate temporary $schedule_id for this preview ONLY if it was not passed.  We return this later so same ID is used for this session.  */
        if( !$schedule_id ) {
          $schedule_id = time();
        }
      }
    } elseif( ( $wpp_action == 'save_new_schedule' || $wpp_action == 'update_schedule' ) && !empty( $_REQUEST[ 'data' ] ) ) {
      $data = WPP_F::parse_str( $_REQUEST[ 'data' ] );
      $data = stripslashes_deep( $data );
      //** Generate plain ( internal ) hash based on current timestamp. schedule_id may already be pased though. */
      if( !$schedule_id ) {
        $schedule_id = time();
      }
    }

    //** Regardless of schedule data loaded from DB or from $_POST, if its there its stored in $data['wpp_property_import'] */
    if( $schedule_id && !empty( $data[ 'wpp_property_import' ] ) ) {
      //** Load the schedule_id into $data variable for convenience */
      $data[ 'schedule_id' ] = $schedule_id;
      //** $schedule_settings should be referenced from now on in this function */
      $schedule_settings = $data[ 'wpp_property_import' ];
      /** @todo this may need to be fixed to get source type from memory */
      $data[ 'source_type' ] = $schedule_settings[ 'source_type' ];
    }

    //** If enabled, enable query tracking */
    if( !empty( $schedule_settings[ 'show_sql_queries' ] ) ) {
      define( 'SAVEQUERIES', true );
    }

    //** wpp_schedule_import is set when doing import via CRON or HTTP */
    if( !$wpp_schedule_import ) {
      ob_start();
    }

    //** Handle actions */
    switch( $wpp_action ) {

      case 'save_new_schedule':
        //** Not sure if this is necessary, why not use global variable? andy@UD */
        $wpp_settings = get_option( 'wpp_settings' );
        /** Load data from _REQUEST data */
        $new_schedule = $schedule_settings;
        //** Assign new hash to it based on time. */
        $schedule_hash = md5( sha1( $schedule_id ) );
        $new_schedule[ 'hash' ] = $schedule_hash;
        //** Commit to DB */

        $new_schedule = apply_filters( 'wpp::xmli::update_schedule', $new_schedule, array(
          'action' => 'save_new_schedule',
          'schedule_id' => $schedule_id,
          'schedule_hash' => $schedule_hash,
          'schedule_data' => $new_schedule
        ) );

        $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] = $new_schedule;
        update_option( 'wpp_settings', $wpp_settings );
        //** Add hash to return json */
        $result[ 'hash' ] = $new_schedule[ 'hash' ];
        break;

      case 'update_schedule':
        $upd_schedule = $schedule_settings;
        $wpp_settings = get_option( 'wpp_settings' );
        $schedule_hash = md5( sha1( $schedule_id ) );
        $upd_schedule[ 'hash' ] = $schedule_hash;
        //** Preserve lastrun settings ( not passed via $_POST ) */
        $upd_schedule[ 'lastrun' ] = $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ][ 'lastrun' ];

        $upd_schedule = apply_filters( 'wpp::xmli::update_schedule', $upd_schedule, array(
          'action' => 'update_schedule',
          'schedule_hash' => $schedule_hash,
          'schedule_id' => $schedule_id,
          'schedule_data' => $upd_schedule
        ) );

        //die( '<pre>' . print_r( $upd_schedule, true ) . '</pre>' );

        $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] = $upd_schedule;


        //** Remove any messed up schedules */
        foreach( $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ] as $this_id => $data ) {
          if( strlen( $this_id ) != 10 || empty( $data ) ) {
            unset( $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $this_id ] );
          }
        }


        update_option( 'wpp_settings', $wpp_settings );
        break;

      case 'delete_schedule':
        if( $schedule_id ) {
          $wpp_settings = get_option( 'wpp_settings' );
          unset( $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] );
          update_option( 'wpp_settings', $wpp_settings );
          $result[ 'success' ] = 'true';
          $import_directory = class_wpp_property_import::create_import_directory( array( 'ad_hoc_temp_dir' => $schedule_id ) );
          if( $import_directory[ 'ad_hoc_temp_dir' ] ) {
            class_wpp_property_import::delete_directory( $import_directory[ 'ad_hoc_temp_dir' ], true );
          }
        }
        break;

      case 'delete_all_orphan_attachments':
        set_time_limit( 0 );
        ignore_user_abort( true );
        $deleted_orphan_image_count = 0;
        foreach( class_wpp_property_import::get_orphan_attachments() as $orphan_image_id ) {
          if( wp_delete_attachment( $orphan_image_id, true ) ) {
            $deleted_orphan_image_count++;
          }
        }
        if( $deleted_orphan_image_count > 0 ) {
          class_wpp_property_import::delete_orphan_directories();
          $result[ 'success' ] = true;
          $result[ 'ui' ] = sprintf( __( 'Deleted %1s unattached property files that were created from an XML import.', ud_get_wpp_importer()->domain ), $deleted_orphan_image_count );
          WPP_F::log( $result[ 'ui' ] );
        } else {
          $result[ 'success' ] = false;
          $result[ 'ui' ] = __( 'Something went wrong, did not delete any unattached images.', ud_get_wpp_importer()->domain );
        }
        break;

      case 'delete_all_schedule_properties':
        if( $schedule_id ) {
          set_time_limit( 0 );
          ignore_user_abort( true );
          $deleted_count = 0;
          $all_properties = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE post_type = 'property' AND meta_key = 'wpp_import_schedule_id' and meta_value = %s  GROUP BY p.ID", $schedule_id ) );
          if( $all_properties ) {
            $operation_start = time();
            foreach( $all_properties as $property_id ) {
              if( wp_delete_post( $property_id, true ) ) {
                $deleted_count++;
              }
            }
            $operation_length = WPP_F::format_numeric( time() - $operation_start );
            //** Remove last run stats from schedule */
            $wpp_settings = get_option( 'wpp_settings' );
            $this_schedule = $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ];
            unset( $this_schedule[ 'lastrun' ] );
            $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] = $this_schedule;
            update_option( 'wpp_settings', $wpp_settings );
            if( $deleted_count == count( $all_properties ) ) {
              $result[ 'ui' ] = sprintf( __( 'All %1$s properties have been deleted in %2$s seconds.', ud_get_wpp_importer()->domain ), $deleted_count, $operation_length );
            } else {
              $result[ 'ui' ] = sprintf( __( 'Although %1$s properties were found, only %2$s have been deleted in %3$s seconds.', ud_get_wpp_importer()->domain ), count( $all_properties ), $deleted_count, $operation_length );
            }
            $all_properties = null;
          } else {
            $result[ 'ui' ] = __( 'Something went wrong, no properties were found to delete.', ud_get_wpp_importer()->domain );
          }
          $result[ 'success' ] = 'true';
        }
        break;

      case 'execute_schedule_import':
        //** Not the most elegant solution, but some of these imports can take a while. Using cron is advised. */
        set_time_limit( 0 );
        @ini_set( 'zlib.output_compression', 0 );
        @ini_set( 'implicit_flush', 1 );
        @ob_implicit_flush( 1 );

        //** Try to increase memory_limit if it's less than 1024M */
        $memory_limit = @ini_get( 'memory_limit' );
        if( (int)$memory_limit < 1024 && $memory_limit != '-1' ) {
          @ini_set( 'memory_limit', '1024M' );
        }

        class_wpp_property_import::maybe_echo_log( str_pad( "Started loading XML from source.", 4096 ) );

        /* Update last import before starting process to prevent cron loops on failure. */
        $wpp_settings = get_option( 'wpp_settings' );
        if( !empty( $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] ) ) {
          $schedule_settings['lastrun']['time'] = time();
          $wpp_settings['configuration']['feature_settings']['property_import']['schedules'][$schedule_id] = $schedule_settings;
          update_option('wpp_settings', $wpp_settings);
        }

        //** For now do not cache live source full imports */
        if( ( isset( $doing_full_import ) && $doing_full_import ) || $data[ 'source_type' ] == 'rets' || $do_not_use_cache ) {
          //** Do not use cache data when doing full RETS import */
          $result[ 'data_from_cache' ] = false;
        } else {
          //** Try to get source from cache if it exists.  If found, returned as SimpleXMLElement */
          $cached_data = class_wpp_property_import::get_cached_source( $schedule_id, $data[ 'source_type' ] );
          if( !empty( $cached_data ) ) {
            $cache_age = time() - $cached_data[ 'time' ];
            //** If cached file is over 30 minutes old, we do not use it */
            if( $cache_age > 1800 ) {
              $cached_data = false;
              class_wpp_property_import::maybe_echo_log( "Cached file is too old, data not used." );
            } else {
              class_wpp_property_import::maybe_echo_log( "Using data in cached file." );
            }
          }
        }

        $xml_data = '';

        if( isset( $cached_data ) && !empty( $cached_data ) ) {
          $xml_data = $cached_data[ 'xml_data' ];
          //** Loaded from cache */
          $result[ 'data_from_cache' ] = true;
          class_wpp_property_import::maybe_echo_log( "Raw feed data loaded from cache." );
        } else {
          //** Set custom post type for URL queries that need to pass credentials via post */
          $request_method = !empty( $data[ 'wpp_property_import' ][ 'postauth' ] ) ? 'post' : 'get';
          //** Try to open the provided file, if RETS feed, data converted into XML and images are downloaded. If source eval, RETS only gets first result. */
          self::maybe_echo_memory_usage( sprintf( __( 'before %s', ud_get_wpp_importer()->domain ), 'wpp_make_request()' ), $schedule_id );
          $response = class_wpp_property_import::wpp_make_request( $data[ 'wpp_property_import' ][ 'url' ], $request_method, $data );
          self::maybe_echo_memory_usage( sprintf( __( 'after %s', ud_get_wpp_importer()->domain ), 'wpp_make_request()' ), $schedule_id );
          class_wpp_property_import::maybe_echo_log( "Raw feed data loaded from live source." );
          $result[ 'data_from_cache' ] = false;
          //** If error object returned, keep processing, it will be echoed later */
          if( !is_wp_error( $response ) && !empty( $response[ 'body' ] ) ) {
            //** If response exists load the raw contents into a variable. */
            $xml_data = $response[ 'body' ];
          }
        }

        self::maybe_echo_memory_usage( __( 'before XML object initialization from response (string)', ud_get_wpp_importer()->domain ), $schedule_id );
        //* Remove namespaces since there is little support for them */
        $xml_data = str_replace( 'xmlns=', 'nothing=', $xml_data );
        $xml = @simplexml_load_string( $xml_data, 'SimpleXMLElement', LIBXML_NOCDATA );
        self::maybe_echo_memory_usage( __( 'after XML object initialization', ud_get_wpp_importer()->domain ), $schedule_id );

        //** Main function where we load the XML data and convert into object */
        if( !empty( $xml ) ) {
          class_wpp_property_import::maybe_echo_log( "XML Object loaded successfully from raw data." );
          //** Create temp folder and images. */
          if( $schedule_temp_path = class_wpp_property_import::create_import_directory( array( 'ad_hoc_temp_dir' => $schedule_id ) ) ) {
            class_wpp_property_import::maybe_echo_log( sprintf( __( 'Created temporary directory for import: %1$s.', ud_get_wpp_importer()->domain ), $schedule_temp_path[ 'ad_hoc_temp_dir' ] ) );
            $data[ 'temporary_directory' ] = $schedule_temp_path[ 'ad_hoc_temp_dir' ];
            //** Determine cache file name */
            if( $data[ 'source_type' ] ) {
              $cache_file_name = $data[ 'source_type' ] . '_cache.xml';
            } else {
              $cache_file_name = 'cache.xml';  /* This should not realy happen */
            }
            $cache_file = $data[ 'temporary_directory' ] . '/' . $cache_file_name;
            //** Cache the source */
            if( file_put_contents( $cache_file, $xml_data ) ) {
              $xml_file_size = class_wpp_property_import::format_size( filesize( $cache_file ) );
              $result[ 'file_size' ] = $xml_file_size;
              class_wpp_property_import::maybe_echo_log( "XML data ( {$xml_file_size} ), loaded from source, cached in: " . $cache_file );
              $cache_file_url = $schedule_temp_path[ 'ad_hoc_temp_url' ] . '/' . $cache_file_name;
            } else {
              class_wpp_property_import::maybe_echo_log( 'Unable to to create cache of source into temorary directory: ' . $data[ 'temporary_directory' ] );
            }
          }
          //** All good to go, we can proceed with cycles */
          $process_import = true;
        } else {
          if( is_wp_error( $response ) ) {
            $mes = sprintf( __( 'Could not load XML Object from raw data: %1s.', ud_get_wpp_importer()->domain ), $response->get_error_message() );
          } elseif( empty( $xml ) && $xml !== false ) {
            $mes = __( 'Could not load XML Object from raw data - empty result returned. Check your settings.', ud_get_wpp_importer()->domain );
          } else {
            $mes = __( 'Could not load XML Object from raw data. Looks like data has errors and can not be converted to XML Object.', ud_get_wpp_importer()->domain );
          }
          class_wpp_property_import::maybe_echo_log( $mes );
          $result[ 'success' ] = 'false';
          $result[ 'message' ] = $mes;
          break; /* break throws the logic to the end of the $wpp_action function, and returns all $result data */
        }

        $root_element_xpath = $data[ 'wpp_property_import' ][ 'root_element' ];

        self::maybe_echo_memory_usage( __( 'before getting the list of XML objects (listings)', ud_get_wpp_importer()->domain ), $schedule_id );
        //** If no root element xpath passed, we return the raw data */
        if( !empty( $root_element_xpath ) ) {
          $objects = @$xml->xpath( $root_element_xpath );
        } else {
          $objects = $xml;
          $root_element_xpath = false;
        }
        self::maybe_echo_memory_usage( __( 'after getting the list of XML objects (listings)', ud_get_wpp_importer()->domain ), $schedule_id );

        if( $wpp_schedule_import ) {
          if( $objects ) {
            class_wpp_property_import::maybe_echo_log( "Extracted " . count( $objects ) . " total objects from the repeating property elements query." );
          } else {
            class_wpp_property_import::maybe_echo_log( "Failed to extract any objects from the repeating property elements query. Quitting." );
            return;
          }
        }

        //** Handle raw preview or no objects. */
        if( !$objects || ( isset( $_REQUEST[ 'raw_preview' ] ) && $_REQUEST[ 'raw_preview' ] == 'true' ) ) {
          $result[ 'ui' ] = isset( $result[ 'ui' ] ) ? $result[ 'ui' ] : '';
          if( $result[ 'data_from_cache' ] ) {
            $result[ 'ui' ] .= __( 'Data loaded from cache.', ud_get_wpp_importer()->domain ) . "\n\n";
          }
          if( $root_element_xpath ) {
            if( $objects ) {
              $result[ 'ui' ] .= count( $objects ) . __( ' object(s) found with XPath Rule: ', ud_get_wpp_importer()->domain ) . $root_element_xpath . "\n\n";
              $result[ 'preview_bar_message' ] = sprintf( __( '%1s objects identified: <a href="%2s" target="_blank">download processed XML file</a> ( %3s ).', ud_get_wpp_importer()->domain ), count( $objects ), $cache_file_url, $result[ 'file_size' ] );
            } else {
              $result[ 'ui' ] .= __( 'Root Element XPath Rule: ', ud_get_wpp_importer()->domain ) . $root_element_xpath . "\n\n";
            }
          } else {
            $result[ 'ui' ] .= __( 'No Root Element XPath Rule, displaying most root elements.', ud_get_wpp_importer()->domain ) . "\n\n";
          }
          if( !$objects ) {
            $result[ 'ui' ] .= __( 'No objects found.', ud_get_wpp_importer()->domain ) . "\n\n";
          } else {
            //** Analayze data, always - shouldn't take too long once its loaded. */
            if( $auto_matched_tags = class_wpp_property_import::analyze_feed( $xml, $data[ 'wpp_property_import' ][ 'root_element' ] ) ) {
              $result[ 'auto_matched_tags' ] = $auto_matched_tags;
            }
            $truncate_limit = 50000;
            $total_length = strlen( print_r( $objects, true ) );
            if( $total_length > $truncate_limit ) {
              $result[ 'ui' ] .= sprintf( __( 'Preview truncated: showing: %1s of full feed:', ud_get_wpp_importer()->domain ), ( round( ( $truncate_limit / $total_length ), 4 ) * 100 ) . '%' ) . "\n\n";
            }
            $result[ 'ui' ] .= htmlentities( substr( print_r( $objects, true ), 0, $truncate_limit ) );
            $result[ 'ui' ] .= "\n\n\n" . sprintf( __( 'Available tags in source: %1s', ud_get_wpp_importer()->domain ), "\n\n" . print_r( $result[ 'auto_matched_tags' ], true ) ) . "\n\n";
            $result[ 'success' ] = 'true';
          }
          //** Blank out auto matched tags */
          /* $result['auto_matched_tags'] = 'none'; */
          break; /* break throws the logic to the end of the $wpp_action function, and returns all $result data */
        }

        unset( $xml );

        //** Load schedule data from DB, if it isn't already loaded, such as by a preview. */
        if( !$schedule_settings ) {
          $schedule_settings = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ];
        }

        //** Build array of slugs that may have multiple values */
        $allow_multiple = array( 'images', 'wpp_agents' );
        foreach( $wp_properties[ 'taxonomies' ] as $slug => $tax ) {
          $allow_multiple[ ] = $slug;
        }
        $allow_multiple = apply_filters( 'wpp_import_attributes_allow_multiple', $allow_multiple );

        //** Stop here if we are only evaluating, and return tags */
        if( isset( $action_type ) && $action_type == 'source_evaluation' ) {
          $result[ 'common_tag_name' ] = $common_tag_name;
          $result[ 'success' ] = 'true';
          break; /* break throws the logic to the end of the $wpp_action function, and returns all $result data */
        }
        /** End source_evaluation */

        //** Add certain rules automatically */
        if( $data[ 'wpp_property_import' ][ 'source_type' ] == 'wpp' ) {
          //** Add parent GPID */
          array_push( $data[ 'wpp_property_import' ][ 'map' ], array(
            'wpp_attribute' => 'parent_gpid',
            'xpath_rule' => 'parent_gpid'
          ) );
        } elseif( $data[ 'wpp_property_import' ][ 'source_type' ] == 'rets' ) {
          //** Add System (Primary) key field */
          array_push( $data[ 'wpp_property_import' ][ 'map' ], array(
            'wpp_attribute' => 'wpp::rets_pk',
            'xpath_rule' => $data[ 'wpp_property_import' ][ 'rets_pk' ]
          ) );
        }

        $wpp_import_result_stats[ ] = "Extracted " . count( $objects ) . " total objects from the repeating property elements query.";

        //** Cycle through individual objects and load queried information into $import_data array; */
        if( $objects && isset( $schedule_settings[ 'log_detail' ] ) && $schedule_settings[ 'log_detail' ] == 'on' ) {
          class_wpp_property_import::maybe_echo_log( "Beginning object cycle." );
        }

        self::maybe_echo_memory_usage( __( 'before parsing the list of XML objects (listings)', ud_get_wpp_importer()->domain ), $schedule_id );
        $counter = 0;
        foreach( $objects as $import_object ) {

          $import_data[ $counter ] = array();

          //** Process every rule and run query against the object */
          foreach( $data[ 'wpp_property_import' ][ 'map' ] as $rule ) {

            $return = null;
            $rule_attribute = $rule[ 'wpp_attribute' ];
            $xpath_rule = stripslashes( $rule[ 'xpath_rule' ] );
            $conditions = array();

            if( empty( $xpath_rule ) ) {
              continue;
            }

            if( strpos( $xpath_rule, 'free_text:' ) !== false ) {

              //* Handle plain text */
              $import_data[ $counter ][ $rule_attribute ][ ] = trim( str_replace( 'free_text:', '', $xpath_rule ) );

            } elseif( strpos( $xpath_rule, 'free_list:' ) !== false ) {

              //* Handle plain text array */
              $free_list = trim( str_replace( 'free_list:', '', $xpath_rule ) );
              $free_list = explode(',', $free_list);
              foreach ($free_list as $key => $value) {
                $import_data[ $counter ][ $rule_attribute ][ ] = trim($value);
              }
            } elseif( strpos( $xpath_rule, 'uppercase:' ) !== false ) {

              $xpath_rule = trim( trim( trim( str_replace( 'uppercase:', '', $xpath_rule ) ), "'" ), '"' );
              //* Handle regular xpath */
              $return = @$import_object->xpath( $xpath_rule );
              $conditions[ 'uppercase' ] = true;

            } elseif( strpos( $xpath_rule, 'concat_list' ) !== false ) {
              //* Import label/value pairs for non-existant meta_keys   */

              //** Breaks xpath rule into array */
              $xpath_atts = shortcode_parse_atts( $xpath_rule, $defaults );
              //* Get Root Path */
              $concat_results = @$import_object->xpath( $xpath_atts[ 'root_path' ] );
              if( is_array( $concat_results ) ) {
                foreach( $concat_results as $single_result ) {
                  $label = ( $xpath_atts[ 'label_path' ] ) ? @$single_result->xpath( $xpath_atts[ 'label_path' ] ) : array();
                  $label = trim( ( string )$label[ 0 ] );
                  $value = ( $xpath_atts[ 'value_path' ] ) ? @$single_result->xpath( $xpath_atts[ 'value_path' ] ) : array();
                  $value = trim( ( string )$value[ 0 ] );
                  $value = class_wpp_property_import::format_single_value( array( 'value' => $value, 'rule_attribute' => $rule_attribute, 'schedule_settings' => $schedule_settings ) );
                  $import_data[ $counter ][ $rule_attribute ][ ] = $label . ( !empty( $label ) && !empty( $value ) ? $xpath_atts[ 'concat_character' ] : '' ) . $value;
                }
                if( !empty( $xpath_atts[ 'paste_together' ] ) ) {
                  $import_data[ $counter ][ $rule_attribute ] = (array)implode( $xpath_atts[ 'paste_together' ], (array)$import_data[ $counter ][ $rule_attribute ] );
                }
              }
              $concat_results = null;
              continue;

            } elseif( strpos( $xpath_rule, 'concat:' ) !== false ) {

              //* concat: 'expression one' some text 'expression two'  */
              $xpath_rule = str_replace( 'concat:', '', $xpath_rule );
              $num_matched = preg_match_all( "/'([^']*)'/", $xpath_rule, $matches, PREG_SET_ORDER );

              //* concat matches found  */
              if( $matches ) {
                $to_concat = array();
                foreach( $matches as $match_rule ) {
                  //* get the requeted attributes from concat list and load into temporary array*/
                  $concat_results = @$import_object->xpath( $match_rule[ 1 ] );
                  foreach( $concat_results as $concat_result ) {
                    $this_value = ( string )$concat_result[ 0 ];
                    $this_value = class_wpp_property_import::format_single_value( array( 'value' => $this_value, 'rule_attribute' => $rule_attribute, 'schedule_settings' => $schedule_settings ) );
                    //* load single-item results into another temp array*/
                    $to_concat[ $match_rule[ 1 ] ] = $this_value;
                  }
                }
                foreach( $to_concat as $match_key => $match_value ) {
                  //* replace the original rule with the real XML values if they exist */
                  $xpath_rule = str_replace( "'" . $match_key . "'", $match_value, $xpath_rule );
                }
                $to_concat = null;
                //* remove extra apostraphes and trim line */
                $import_data[ $counter ][ $rule_attribute ][ ] = trim( str_replace( "'", '', $xpath_rule ) );
                continue;
              }

            } else {
              //* Handle regular xpath */
              $return = @$import_object->xpath( $xpath_rule );
            }

            //* If nothing returned at all, go to next rule */
            if( !$return ) {
              continue;
            }

            //* Cycle through returns and save them into $import_data */
            foreach( $return as $attribute ) {
              //* Add first matched value to rule_attribute */
              $this_value = ( string )$attribute[ 0 ];
              $args = array( 'value' => $this_value, 'rule_attribute' => $rule_attribute, 'schedule_settings' => $schedule_settings );
              $args = array_filter( array_merge( $args, $conditions ) );
              $this_value = class_wpp_property_import::format_single_value( $args );
              $import_data[ $counter ][ $rule_attribute ][ ] = $this_value;
            }

          } //** end single rule cycle */

          //**  All Rules have been processed. Cycle back through rules and concatenate any values that are not allowed to have multiple values */
          if( !empty( $import_data[ $counter ] ) ) {
            foreach( $import_data[ $counter ] as $rule_attribute => $values ) {
              $attribute_data = WPP_F::get_attribute_data($rule_attribute);
              if( !(in_array( $rule_attribute, $allow_multiple ) || $attribute_data['multiple'])) {
                $values = ( array )$values;
                if( count( $values ) > 1 ) {
                  //** Make sure featured-image is not being concatenated */
                  //** Notice, we must ignore the current condition for RETS, because we get RETS images later during image import job. */
                  if( $rule_attribute == 'featured-image' && $schedule_settings[ 'source_type' ] != 'rets' ) {
                    //** Make sure there is a regular image array */
                    if( !is_array( $import_data[ $counter ][ 'images' ] ) ) {
                      $import_data[ $counter ][ 'images' ] = array();
                    }
                    //** Move all but the first featured image into regular image array, into the beginning, because its probably important */
                    $import_data[ $counter ][ 'images' ] = array_merge( array_slice( $values, 1 ), $import_data[ $counter ][ 'images' ] );
                    //** Remove all but the first image for the featured image array */
                    $import_data[ $counter ][ $rule_attribute ] = array_slice( $values, 0, 1 );
                  } else {
                    $import_data[ $counter ][ $rule_attribute ] = null;
                    unset( $import_data[ $counter ][ $rule_attribute ] );
                    $import_data[ $counter ][ $rule_attribute ][ 0 ] = implode( apply_filters( 'wpp_import_attributes_implode_non_multiple', "\n" ), $values );
                  }
                }
              }
              elseif($attribute_data['multiple']){
                if(count($import_data[ $counter ][ $rule_attribute ]) == 1){
                  $import_data[ $counter ][ $rule_attribute ] = explode(',', $import_data[ $counter ][ $rule_attribute ][0]);
                }
              }
            }
          }

          $import_data[ $counter ][ 'unique_id' ] = $data[ 'wpp_property_import' ][ 'unique_id' ];
          $import_data[ $counter ] = apply_filters( 'wpp_xml_import_do_rule', $import_data[ $counter ], $import_object, $data[ 'wpp_property_import' ] );

          //** If preview, stop after first processed property */
          if( isset( $preview_import ) && $preview_import ) {
            $result[ 'success' ] = 'true';
            $result[ 'ui' ] = "XPath Rule: {$root_element_xpath}\n\n" . htmlentities( print_r( $import_data[ $counter ], true ) );
            break; /* break throws the logic to the end of the $wpp_action function, and returns all $result data */
          }

          //** If skipping properties without images, cycle back through and remove any properties without images */
          //** Notice, we must ignore the current condition for RETS, because we get RETS images later during image import job. */
          if( $schedule_settings[ 'source_type' ] != 'rets' && isset( $schedule_settings[ 'minimum_images' ] ) && $schedule_settings[ 'minimum_images' ] > 0 ) {
            $total_images = count( $import_data[ $counter ][ 'images' ] ) + count( $import_data[ $counter ][ 'featured-image' ] );
            if( $total_images < $schedule_settings[ 'minimum_images' ] ) {
              $import_data[ $counter ] = null;
              unset( $import_data[ $counter ] );
              $no_image_skip[ ] = true;
              continue;
            }
          }

          if( isset( $schedule_settings[ 'log_detail' ] ) && $schedule_settings[ 'log_detail' ] == 'on' ) {
            if( is_array( $import_data[ $counter ] ) ) {
              $extracted_attributes = count( array_keys( $import_data[ $counter ] ) );
            } else {
              $extracted_attributes = 0;
            }
            class_wpp_property_import::maybe_echo_log( sprintf( __( 'XPath rules for object #%1d processed with %2d extracted attributes.', ud_get_wpp_importer()->domain ), ( $counter + 1 ), $extracted_attributes ) );
          }

          $counter++;

          if( !empty( $schedule_settings[ 'limit_scanned_properties' ] ) && $schedule_settings[ 'limit_scanned_properties' ] == $counter ) {
            class_wpp_property_import::maybe_echo_log( sprintf( __( 'Stopping import due to specified pre-QC limit of %1d.', ud_get_wpp_importer()->domain ), $counter ) );
            break;
          }
        } //** end $objects loop */

        unset( $objects );
        self::maybe_echo_memory_usage( __( 'after parsing the list of XML objects (listings)', ud_get_wpp_importer()->domain ), $schedule_id );

        //** In case didn't get stopped in the loop above */
        if( isset( $preview_import ) && $preview_import ) {
          break;
        }

        //** Check how many properties had no images */
        if( isset( $no_image_skip ) && is_array( $no_image_skip ) ) {
          $no_image_skip = array_sum( $no_image_skip );
          class_wpp_property_import::maybe_echo_log( "Skipped {$no_image_skip} properties because they had no images." );
          $wpp_import_result_stats[ ] = "{$no_image_skip} properties skipped because they have no images.";
        }

        class_wpp_property_import::maybe_echo_log( "All XPath rules processed, " . count( $import_data ) . " properties remain." );
        $wpp_import_result_stats[ ] = count( $import_data ) . " properties remaining after processing XPath rules on objects.";

        class_wpp_property_import::maybe_echo_log( sprintf( __( 'Importing %1s', ud_get_wpp_importer()->domain ), htmlspecialchars( $schedule_settings[ 'url' ] ) ) );

        $import_updated = $import_created = $existing_images = 0;

        //** Do the actual import **/
        if( $process_import && !empty( $import_data ) ) {

          UD_F::log( 'Running XML Import job ' . $schedule_id . ' at ' . date( "F j, Y, g:i a", time() ) );

          //** Dump all properties and their attachments before importing anything new */
          if( isset( $schedule_settings[ 'remove_all_before_import' ] ) && $schedule_settings[ 'remove_all_before_import' ] == 'on' ) {
            $all_properties = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property'" );
            class_wpp_property_import::maybe_echo_log( "Deleting all old properties. " . count( $all_properties ) . " found." );
            $property_delete_counter = 0;
            foreach( (array)$all_properties as $property_id ) {
              if( wp_delete_post( $property_id, true ) ) {
                $property_delete_counter++;
              }
            }
            if( $property_delete_counter ) {
              class_wpp_property_import::maybe_echo_log( "{$property_delete_counter} properties deleted." );
              $wpp_import_result_stats[ ] = "All existing properties ( " . $property_delete_counter . " ) deleted.";
            }
            $all_properties = null;
            $property_delete_counter = null;
          } elseif( isset( $schedule_settings[ 'remove_all_from_this_source' ] ) && $schedule_settings[ 'remove_all_from_this_source' ] == 'on' ) {
            //** Remove all objects that originated from this feed */
            $result = class_wpp_property_import::delete_feed_properties( $schedule_id, $schedule_settings );
            if( $result[ 'deleted_count' ] > 0 ) {
              $wpp_import_result_stats[ ] = sprintf( __( 'Deleted all ( %1s ) properties that originated from this feed.', ud_get_wpp_importer()->domain ), $result[ 'deleted_count' ] );
              class_wpp_property_import::maybe_echo_log( sprintf( __( 'Deleted all ( %1s ) properties that originated from this feed.', ud_get_wpp_importer()->domain ), $result[ 'deleted_count' ] ) );
            } elseif( $result[ 'total_found' ] == 0 ) {
              class_wpp_property_import::maybe_echo_log( __( 'Did not find any properties that have been imported from this feed to remove.', ud_get_wpp_importer()->domain ) );
            }
            $result = null;
            //** End: $schedule_settings['remove_all_from_this_source'] == 'on' */
          } else {
            class_wpp_property_import::maybe_echo_log( __( 'Did not remove any old properties.', ud_get_wpp_importer()->domain ) );
          }

          // Handle the actual import
          class_wpp_property_import::maybe_echo_log( 'Beginning object cycle. We have ' . count( $import_data ) . ' objects.' );
          self::maybe_echo_memory_usage( '', $schedule_id );

          //** Cycle through each XML object **/
          foreach( $import_data as $zero_counter => $single_object_data ) {

            //** Updated counter to not be zero based */
            $counter = ( $zero_counter + 1 );

            if( !empty( $schedule_settings[ 'limit_properties' ] ) && $schedule_settings[ 'limit_properties' ] == $import_created ) {
              class_wpp_property_import::maybe_echo_log( sprintf( __( 'Stopping import due to specified post-QC limit of %1d.', ud_get_wpp_importer()->domain ), $counter ) );
              $wpp_import_result_stats[ ] = $import_created . " new properties imported, stopping due to limit.";
              break;
            }

            $unique_id = $single_object_data[ $schedule_settings[ 'unique_id' ] ][ 0 ];

            //** Skip object import if no unique ID value exists ( @todo may need to add an option to not do this, some feeds may not have unique attributes - potanin@UD ) */
            if( empty( $unique_id ) ) {
              class_wpp_property_import::maybe_echo_log( "Skipping property, unique ID not found. " );
              continue;
            }

            /** Perform single object importing */
            //self::maybe_echo_memory_usage( sprintf( __( 'before %s', ud_get_wpp_importer()->domain ), 'import_object()' ), $schedule_id );
            $iobject = class_wpp_property_import::import_object( $single_object_data, $schedule_id, $counter );
            self::maybe_echo_memory_usage( sprintf( __( 'after %s', ud_get_wpp_importer()->domain ), 'import_object()' ), $schedule_id );

            if( is_wp_error( $iobject ) ) {
              //** Error occured */
              class_wpp_property_import::maybe_echo_log( 'Error on single object import: ' . $iobject->get_error_message() );
              //** Stop this object import */
              continue;
            } elseif( is_numeric( $iobject[ 0 ] ) ) {
              // Actual post_id stored in $iobject[0]
              $imported_objects[ ] = $iobject[ 0 ];
              if( $iobject[ 1 ] == 'u' ) {
                $import_updated += 1;
              } else if( $iobject[ 1 ] == 'c' ) {
                $import_created += 1;
              }
              do_action( 'wpp::xmli::after_import_object', $iobject, $single_object_data, $schedule_id );
            } else {
              // This happens if the property was not inserted, or deleted.
            }

            $iobject = null;
            unset( $iobject );
            unset( $import_data[ $zero_counter ] );
          }

          class_wpp_property_import::maybe_echo_log( sprintf( __( 'Object cycle done. Completed %1d cycles.', ud_get_wpp_importer()->domain ), $counter - 1 ) );
          self::maybe_echo_memory_usage( '', $schedule_id );

          //** Remove any objects that are no longer in source ( do not remove non existing if we only did a limited import ) */
          if(
            empty( $schedule_settings[ 'limit_properties' ] ) &&
            empty( $schedule_settings[ 'limit_scanned_properties' ] ) &&
            isset( $schedule_settings[ 'remove_non_existant' ] ) &&
            $schedule_settings[ 'remove_non_existant' ] == 'on' &&
            ( !isset( $schedule_settings[ 'remove_all_from_this_source' ] ) || $schedule_settings[ 'remove_all_from_this_source' ] != 'on' )
          ) {
            $result = class_wpp_property_import::delete_feed_properties( $schedule_id, $schedule_settings, $imported_objects );
            if( isset( $result[ 'deleted_count' ] ) && $result[ 'deleted_count' ] > 0 ) {
              $wpp_import_result_stats[ ] = sprintf( __( 'Deleted ( %1s ) properties that are no longer in the feed.', ud_get_wpp_importer()->domain ), $result[ 'deleted_count' ] );
              class_wpp_property_import::maybe_echo_log( sprintf( __( 'Deleted ( %1s ) properties that are no longer in the feed.', ud_get_wpp_importer()->domain ), $result[ 'deleted_count' ] ) );
            } elseif( $result[ 'total_found' ] == 0 ) {
              class_wpp_property_import::maybe_echo_log( __( 'Did not find any properties that have been imported from this feed to remove.', ud_get_wpp_importer()->domain ) );
            }
            $result = null;
          } //** End: $schedule_settings['remove_non_existant'] == 'on' */

          //** Reassociate WP parent IDs **//
          class_wpp_property_import::reassociate_parent_ids();

          //** Delete temporary files and folder */
          if( $data[ 'temporary_directory' ] ) {
            if( $rescleanup = class_wpp_property_import::delete_directory( $data[ 'temporary_directory' ], true ) ) {
              class_wpp_property_import::maybe_echo_log( __( 'Deleted the import temporary directory.', ud_get_wpp_importer()->domain ) );
            } else {
              class_wpp_property_import::maybe_echo_log( $rescleanup === 0 ? __( 'Import temporary directory is not been created', ud_get_wpp_importer()->domain ) : __( 'Unable to delete the import temporary directory', ud_get_wpp_importer()->domain ) );
            }
          }

          UD_F::log( 'Completed XML Import job ' . $schedule_id . ' at ' . date( "F j, Y, g:i a", time() ) . ', ( ' . $import_created . ' )  created and ( ' . $import_updated . ' ) objects updated. ' );

          if( $import_created ) {
            $wpp_import_result_stats[ ] = "Total of " . $import_created . " new properties added.";
            $wpp_import_result_stats[ 'quantifiable' ][ 'added_properties' ] = $import_created;
          }

          if( $import_updated ) {
            $wpp_import_result_stats[ ] = "Total of " . $import_updated . " properties updated.";
            $wpp_import_result_stats[ 'quantifiable' ][ 'updated_properties' ] = $import_updated;
          }

          //** Handle updating settings after import is complete */
          if( $schedule_id ) {
            //** Cannot get get_option() because it uses cached values, and since the importer could have taken a while, changes may have been made to options */
            $wpp_settings = maybe_unserialize( $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name ='wpp_settings'" ) );
            $schedule_settings[ 'lastrun' ][ 'time' ] = time();
            $schedule_settings[ 'lastrun' ][ 'u' ] = ( !empty( $import_updated ) ) ? $import_updated : 0;
            $schedule_settings[ 'lastrun' ][ 'c' ] = ( !empty( $import_created ) ) ? $import_created : 0;
            $wpp_settings[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ] = $schedule_settings;
            update_option( 'wpp_settings', $wpp_settings );
            $wpp_settings = null;
          }

        }

        class_wpp_property_import::delete_orphan_directories();

        do_action( 'wpp_xml_import_complete' );

        //** Finally, we're just going to run our scheduled task if needed */
        if( isset( $schedule_id ) && wp_next_scheduled( 'wpp_manage_pending_images', array( 'wpp_manage_pending_images' => $schedule_id ) ) ) {
          /** Fire the event */
          self::maybe_run_cron( 'wpp_manage_pending_images', array( 'wpp_manage_pending_images' => $schedule_id ) );
          /** Log */
          self::maybe_echo_log( 'Properties have been imported with pending images. An additional process has been launched that will download and publish the properties.' );
        }

        //** Print out queries up to this point, and blank out the query log */
        if( !empty( $schedule_settings[ 'show_sql_queries' ] ) && !empty( $wpdb->queries ) ) {
          foreach( (array)$wpdb->queries as $query_data ) {
            class_wpp_property_import::maybe_echo_log( $query_data[ 0 ] );
          }
        }

        break; /* end case: execute_schedule_import */

      case 'add_edit_schedule':
        $edit_current = array();
        if( !empty( $_REQUEST[ 'schedule_id' ] ) ) {
          //** Existing Schedule */
          $edit_current = $wpp_property_import[ 'schedules' ][ $_REQUEST[ 'schedule_id' ] ];
          $new_schedule = false;
        } else {
          //** New Schedule - load defaults */
          $edit_current[ 'map' ][ 1 ][ 'wpp_attribute' ] = 'post_title';
          $new_schedule = true;
        }
        self::edit_schedule_template( $edit_current, $new_schedule );
        break;

    } //** end: $wpp_action switch */

    //** Load some attributes into return if they exist */
    if( isset( $schedule_hash ) ) {
      $result[ 'hash' ] = $schedule_hash;
    }

    if( isset( $schedule_id ) ) {
      $result[ 'schedule_id' ] = $schedule_id;
    }

    //** if not doing an import, this this function is used to generate a JSON responde for UI */
    if( !$wpp_schedule_import && !isset( $result[ 'ui' ] ) ) {
      $result[ 'ui' ] = ob_get_contents();
      ob_end_clean();
    }

    if( isset( $wp_properties[ 'configuration' ][ 'developer_mode' ] ) && $wp_properties[ 'configuration' ][ 'developer_mode' ] == 'true' ) {
      //** Check encoding. Not necessary because we still force UTF8, for debugging purposes */
      $encoding = !empty( $result[ 'ui' ] ) ? WPP_F::detect_encoding( $result[ 'ui' ] ) : 'UTF-8';
      if( is_wp_error( $encoding ) ) {
        $result[ 'encoding' ] = $encoding->get_error_message();
      } else {
        $result[ 'encoding' ] = !empty( $encoding ) ? $encoding : 'UTF-8';
      }
    }

    //** Check if this is being called from an AJAX request  */
    if( !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest' ) {
      if( !is_callable( 'mb_encode_numericentity' ) || !is_callable( 'mb_decode_numericentity' ) ) {
        $result[ 'ui' ] = '<div class="error updated " style="padding:10px;">' . __( 'Note. You can continue working on the current Schedule. But, your PHP build doe\'s not have required functions <b>mb_encode_numericentity</b> and/or <b>mb_decode_numericentity</b>. You may have UI issues during editting your Schedule because of it. Please, ask your host provider to enable these functions to prevent possible issues. Thanks.', ud_get_wpp_importer()->domain ) . '</div>' . $result[ 'ui' ];
        $json_encode = json_encode( $result );
      } else {
        $json_encode = WPP_F::json_encode( $result );
      }
      die( $json_encode );
    } else {
      $result[ 'ui' ] = !empty( $result[ 'ui' ] ) ? utf8_encode( $result[ 'ui' ] ) : false;
      return $result;
    }

    /** Important - do not die() this function unless we have an AJAX request */

  }

  /**
   * Traverse through XML DOM
   *
   * Everything handles via this page. Other functions are done via AJAX.
   *
   * @todo Could be improved
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function traverse( DomNode $node, $level = 0 ) {
    global $wpp_property_import;
    $this_tag = class_wpp_property_import::handle_node( $node, $level );
    if( $node->hasChildNodes() ) {
      $children = $node->childNodes;
      foreach( $children as $kid ) {
        if( $kid->nodeType == XML_ELEMENT_NODE ) {
          class_wpp_property_import::traverse( $kid, $level + 1 );
        } else {
          $wpp_property_import[ 'runtime' ][ 'tags' ][ ] = $this_tag;
        }
      }
    } else {
      // means that there's no value
      $wpp_property_import[ 'runtime' ][ 'tags' ][ ] = $this_tag;
    }
  }

  /**
   * Functions used by traverse()
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function handle_node( DomNode $node, $level ) {
    global $wpp_property_import;
    if( $node->nodeType == XML_ELEMENT_NODE ) {
      return $node->tagName;
    }
  }

  /**
   * Import overview page.
   *
   * Everything handles via this page. Other functions are done via AJAX.
   *
   * @Page ID: property_page_wpp_property_import
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function page_main() {
    include ud_get_wpp_importer()->path( 'static/views/overview.php', 'dir' );
  }

  /**
   * Renders Add/Edit Schedule page
   *
   * @param array $settings
   * @param bool $new_schedule
   */
  static public function edit_schedule_template( $settings, $new_schedule = false ) {
    include ud_get_wpp_importer()->path( 'static/views/edit.php', 'dir' );
  }

  /**
   * Returns the list of Attributes Map
   */
  static public function get_total_attribute_array() {
    global $wp_properties, $wpp_property_import;

    $data = array(
      'property_type' => __( 'Property Type', ud_get_wpp_importer()->domain ),
      'wpp_agents' => __( 'Property Agent', ud_get_wpp_importer()->domain ),
      'wpp_gpid' => __( 'Global Property ID', ud_get_wpp_importer()->domain ),
      'display_address' => __( 'Display Address', ud_get_wpp_importer()->domain ),
    );

    $data = array_merge( $data, WPP_F::get_total_attribute_array() );
    $data = array_merge( $data, $wpp_property_import[ 'post_table_columns' ] );

    return $data;
  }

  /**
   * Imports an object into WP-Property
   *
   * @todo May want to move more of the core save_property() into filters, so they can be used here, and elsewhere
   * @todo Need way of settings property type
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   */
  static public function import_object( $data, $schedule_id, $counter = false ) {
    global $wpp_property_import, $wpdb, $wp_properties, $wpp_cache;

    //** Be sure we have the stable DB connection. */
    if( $wp_properties[ 'configuration' ][ 'developer_mode' ] == 'true' ) {
      $wpdb->show_errors();
    } else {
      $wpdb->suppress_errors();
    }

    if( !empty( $wpdb->error ) ) {
      $error = is_wp_error( $wpdb->error ) ? $wpdb->error->get_error_message() : $wpdb->error;
      class_wpp_property_import::maybe_echo_log( sprintf( __( "Database connection failed. Error: %s", ud_get_wpp_importer()->domain ), $error ) );
      class_wpp_property_import::maybe_echo_log( __( "Trying to do new DB connection", ud_get_wpp_importer()->domain ) );
      $wpdb->db_connect();
    }

    // Load schedule settings
    $schedule_settings = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'property_import' ][ 'schedules' ][ $schedule_id ];

    // Load wp_posts columns and their labels
    $post_table_columns = array_keys( (array)$wpp_property_import[ 'post_table_columns' ] );

    //** Load WPP taxonomies */
    $taxonomies = array();
    foreach( $wp_properties[ 'taxonomies' ] as $slug => $tax ) {
      $taxonomies[ ] = $slug;
    }

    // Load defaults for new properties
    $defaults = apply_filters( 'wpp_import_object_defaults', $defaults = array(
      'post_title' => isset( $data[ 'post_title' ][ 0 ] ) ? $data[ 'post_title' ][ 0 ] : '',
      'post_content' => isset( $data[ 'post_content' ][ 0 ] ) ? $data[ 'post_content' ][ 0 ] : '',
      'post_status' => 'publish',
      'post_type' => 'property',
      'ping_status' => get_option( 'default_ping_status' ),
      'post_parent' => 0,
	  'comment_status' => get_option( 'default_comment_status' )
    ), $data );

    //* Handle WPP Import in a special way */
    if( $schedule_settings[ 'source_type' ] == "wpp" ) {
      $wpp_gpid = $data[ 'wpp_gpid' ][ 0 ];
      $post_exists = WPP_F::get_property_from_gpid( $wpp_gpid );
    }

    $unique_id_attribute = $data[ 'unique_id' ];
    $unique_id_value = $data[ $unique_id_attribute ][ 0 ];

    if( !isset( $post_exists ) || !$post_exists ) {
      if( !isset( $wpp_cache ) || !is_array( $wpp_cache ) || !isset( $wpp_cache[ 'existing_posts_by_meta' ] ) ) {
        $wpp_cache = array(
          'existing_posts_by_meta' => array(),
          'existing_posts_by_main' => array()
        );
        /** First, get our values by meta*/
        $existing_posts_by_meta = $wpdb->get_results( "
          SELECT DISTINCT post_id, meta_value
          FROM {$wpdb->postmeta}
          WHERE post_id IN (
              SELECT DISTINCT post_id FROM {$wpdb->postmeta}
              WHERE meta_key='wpp_import_schedule_id'
                AND meta_value='{$schedule_id}'
            )
            AND meta_key='{$unique_id_attribute}'
        ", ARRAY_A );
        if( is_array( $existing_posts_by_meta ) && count( $existing_posts_by_meta ) ) {
          foreach( $existing_posts_by_meta as $row ) {
            $wpp_cache[ 'existing_posts_by_meta' ][ $row[ 'meta_value' ] ] = $row[ 'post_id' ];
          }
        }
        /** Now, do the same for the main table */
        if( in_array( $unique_id_attribute, $post_table_columns ) ) {
          $existing_posts_by_main = @$wpdb->query( "
            SELECT DISTINCT ID, {$unique_id_attribute} AS unique_value
            FROM {$wpdb->posts}
            WHERE ID IN (
                SELECT DISTINCT post_id FROM {$wpdb->postmeta}
                WHERE meta_key='wpp_import_schedule_id'
                  AND meta_value='{$schedule_id}'
              )
          ", ARRAY_A );
          if( is_array( $existing_posts_by_main ) && count( $existing_posts_by_main ) ) {
            foreach( $existing_posts_by_main as $row ) {
              $wpp_cache[ 'existing_posts_by_main' ][ $row[ 'unique_value' ] ] = $row[ 'ID' ];
            }
          }
        }
      }
      /** Ok, actually, now do the comparison */
      if( isset( $wpp_cache[ 'existing_posts_by_meta' ][ $unique_id_value ] ) ) {
        $post_exists = $wpp_cache[ 'existing_posts_by_meta' ][ $unique_id_value ];
      }
      if( isset( $wpp_cache[ 'existing_posts_by_main' ][ $unique_id_value ] ) ) {
        $post_exists = $wpp_cache[ 'existing_posts_by_main' ][ $unique_id_value ];
      }
    }

    //** Property Skipping. Only applicable to existing properties. */
    if( !empty( $post_exists ) ) {
      do_action( 'wpp_import_property_before_skip', $post_exists, $data );
      $last_import = get_post_meta( $post_exists, 'wpp_import_time', true );
      $time_since_last_import = time() - $last_import;
      $reimport_delay_in_seconds = !empty( $schedule_settings[ 'reimport_delay' ] ) ? (int)$schedule_settings[ 'reimport_delay' ] * 60 * 60 : false;
      $skip = ( $reimport_delay_in_seconds && $time_since_last_import < $reimport_delay_in_seconds ) ? true : false;
      $disable_update = get_post_meta( $post_exists, 'wpp::disable_xmli_update', true );
      $disable_update = in_array( $disable_update, array( '1', 'true' ) ) ? true : false;
      //** Allow override of skip or not */
      switch( true ) {
        case apply_filters( 'wpp_import_skip_import', $skip, $post_exists, $schedule_settings ):
          class_wpp_property_import::maybe_echo_log( '#' . $counter . " - skipping property, last import " . human_time_diff( $last_import ) . " ago. <a href='" . get_permalink( $post_exists ) . "' target='_blank'>#{$post_exists}</a>" );
          //** Stop this import and return to next object */
          return array( $post_exists, $mode );
        case apply_filters( 'wpp_import_disable_update', $disable_update, $post_exists, $schedule_settings ):
          class_wpp_property_import::maybe_echo_log( '#' . $counter . " - skipping property, because 'Ignore updates on XMLI process' option is checked for the current one. <a href='" . get_permalink( $post_exists ) . "' target='_blank'>#{$post_exists}</a>" );
          //** Stop this import and return to next object */
          return array( $post_exists, $mode );
        default:
          class_wpp_property_import::maybe_echo_log( "Importing. Unique ID: ( {$unique_id_value} )" );
          break;
      }
    }

    //** Ok, if we're actually a RETS resource, or if we have images that need downloaded, then we're going to be pending */
    if( $schedule_settings[ 'source_type' ] == 'rets' ||
      ( isset( $data[ 'images' ] ) && is_array( $data[ 'images' ] ) && count( $data[ 'images' ] ) ) ||
      ( isset( $data[ 'featured-image' ] ) && is_array( $data[ 'featured-image' ] ) && count( $data[ 'featured-image' ] ) )
    ) {

      /** If option 'Skip images if images already downloaded' enabled and post exists and has images, skip this step. */
      $skip_images = false;
      if( !empty( $schedule_settings[ 'skip_images' ] ) && $schedule_settings[ 'skip_images' ] == 'on' && !empty( $post_exists ) ) {
        $images = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = %d ", $post_exists ), ARRAY_A );
        if( !empty( $images ) ) {
          $skip_images = true;
        }
      }

      if( !$skip_images ) {
        if( empty( $post_exists ) ) {
          /** First, change our post status to draft */
          $defaults[ 'post_status' ] = 'draft';
          //** Now update to a temp post title */
          $data[ 'wpp::post_title' ] = $data[ 'post_title' ];
          $data[ 'post_title' ] = array( (string)$data[ 'post_title' ][ 0 ] . ' (' . __( 'Pending Image Downloads', ud_get_wpp_importer()->domain ) . ')' );
        } else {
          //** Just make sure we're not updating the post status */
          unset( $defaults[ 'post_status' ] );
        }
        //** Setup our new image data holders */
        $data[ 'wpp::image_status' ] = 'pending';
        $data[ 'wpp::images' ] = json_encode( array(
          'images' => ( isset( $data[ 'images' ] ) && is_array( $data[ 'images' ] ) && count( $data[ 'images' ] ) ? $data[ 'images' ] : null ),
          'featured-image' => ( isset( $data[ 'featured-image' ] ) && is_array( $data[ 'featured-image' ] ) && count( $data[ 'featured-image' ] ) ? $data[ 'featured-image' ] : null ),
        ) );
        /** Unset anything that might be there */
        unset( $data[ 'images' ] );
        unset( $data[ 'featured-image' ] );
        /** Set if we have the process already scheduled */
        self::maybe_schedule_cron( 'wpp_manage_pending_images', array( 'wpp_manage_pending_images' => $schedule_id ) );
      } else {
        self::maybe_echo_log( "Images downloading is skipped because current listing (#{$post_exists}) already has images." );
        /** Unset anything that might be there */
        unset( $data[ 'images' ] );
        unset( $data[ 'featured-image' ] );
      }
    }

    //** Insert/Update post */
    if( !empty( $post_exists ) ) {
      //** Existing property */
      if( isset( $schedule_settings[ 'log_detail' ] ) && $schedule_settings[ 'log_detail' ] == 'on' ) {
        class_wpp_property_import::maybe_echo_log( __( 'Updating existing listing.', ud_get_wpp_importer()->domain ) );
      }
      //** Set ID to match old post ( so duplicate doesn't get created ) */
      $defaults[ 'ID' ] = $post_exists;
      //** Update post with default data ( which may be overwritten later during full import ) */
      $post_id = wp_update_post( $defaults );
      if( is_numeric( $post_id ) ) {
        /** Post ID exists */
        $mode = 'u';
        $property_url = get_permalink( $post_id );
        class_wpp_property_import::maybe_echo_log( $counter . " - updated <a href='{$property_url}' target='_blank'>#{$post_id}</a>." );
        $exclude_from_supermap = get_post_meta( $post_id, 'exclude_from_supermap', true );
      } else {
        return new WP_Error( 'fail', __( "Attempted to update property, but wp_update_post() did not return an ID. ", ud_get_wpp_importer()->domain ) );
      }
    } else {
      if( isset( $schedule_settings[ 'log_detail' ] ) && $schedule_settings[ 'log_detail' ] == 'on' ) {
        class_wpp_property_import::maybe_echo_log( __( 'Creating new listing.', ud_get_wpp_importer()->domain ) );
      }
      if( isset( $schedule_settings[ 'show_sql_queries' ] ) && $schedule_settings[ 'show_sql_queries' ] == 'true' ) {
        $wpdb->show_errors();
      }
      //** New property. */
      $post_id = wp_insert_post( $defaults, true );
      if( $post_id && !is_wp_error( $post_id ) ) {
        $mode = 'c';
        $property_url = get_permalink( $post_id );
        class_wpp_property_import::maybe_echo_log( '#' . $counter . " - created <a href='{$property_url}' target='_blank'>#{$post_id}</a>" );
      }
    }

    // At this point a blank property is either created or the existing $post_id is set, should be no reason it not be set, but just in case. */
    if( is_wp_error( $post_id ) ) {
      return new WP_Error( 'fail', sprintf( __( 'Object import failed. Error: %1s.', ud_get_wpp_importer()->domain ), $post_id->get_error_message() ) );
    }

    if( !is_numeric( $post_id ) && empty( $defaults[ 'post_title' ] ) ) {
      return new WP_Error( 'fail', __( 'Object import failed - no Property Title detected or set, a requirement to creating a property.', ud_get_wpp_importer()->domain ) );
    }

    if( !$post_id ) {
      return new WP_Error( 'fail', __( 'Object import failed.  Post cold not be created nor updated, and post_id was not found or created.', ud_get_wpp_importer()->domain ) );
    }

    unset( $data[ 'unique_id' ] );

    //** Remove any orphaned image */
    if( isset( $schedule_settings[ 'remove_images' ] ) && $schedule_settings[ 'remove_images' ] == 'on' ) {
      $removed_images = 0;
      $all_images = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent = %d ", $post_id ), ARRAY_A );
      foreach( $all_images as $image_row ) {
        if( wp_delete_attachment( $image_row['ID'], true ) ) {
          $removed_images++;
        }
      }
      if( $removed_images ) {
        class_wpp_property_import::maybe_echo_log( "Removed ( {$removed_images} ) old images." );
      }
    }

    //** Keep track of all attributes that have values and have been imported */
    $processed_attributes = array();
    
    //** Cycle through attributes ( which include meta value, images and taxonomies ) */
    foreach( $data as $attribute => $values ) {

      $attribute_data = WPP_F::get_attribute_data( $attribute );
      //** If no values, stop processing this attribute */
      if( empty( $values ) ) {
        continue;
      }

      //** Convert value to array format if it isn't */
      if( !is_array( $values ) ) {
        $values = array( $values );
      }

      //** Get array of keys we will not encode on import */
      $keys_to_not_encode = apply_filters( 'wpp_import_do_not_encode_attributes', array( 'wpp_agents' ) );

      // Previous value 
      $prev_value = get_post_meta($post_id, $attribute);
      //** Values are in array format, cycle through them */
      foreach( $values as $value ) {

        //** Handle Agent Matching */
        if( $attribute == 'wpp_agents' ) {
          $value = apply_filters( 'wpp_xml_import_value_on_import', $value, $attribute, 'meta_field', $post_id, $schedule_settings );
          $agent_match_bridge = ( $schedule_settings[ 'wpp_agent_attribute_match' ] ? $schedule_settings[ 'wpp_agent_attribute_match' ] : 'display_name' );
          $users_columns = array(
            'ID',
            'user_login',
            'user_pass',
            'user_nicename',
            'user_email',
            'user_url',
            'user_registered',
            'user_activation_key',
            'user_status',
            'display_name',
          );
          //** Attempt to find agent ID based on provided data */
          $possible_match = false;
          if( in_array( $agent_match_bridge, $users_columns ) ) {
            $possible_match = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE  {$agent_match_bridge} = '%s' LIMIT 0, 1", $value ) );
          }
          if( !is_numeric( $possible_match ) ) {
            //** Try meta table */
            $possible_match = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE  meta_key='{$agent_match_bridge}' AND meta_value= '%s'  LIMIT 0, 1 ", $value ) );
          }
          if( is_numeric( $possible_match ) ) {
            if( $schedule_settings[ 'log_detail' ] == 'on' ) {
              class_wpp_property_import::maybe_echo_log( "Property agent found based on {$agent_match_bridge} with ID of $possible_match - adding to {$post_id}  property." );
            }
            if(!in_array($possible_match, $prev_value))
              add_post_meta( $post_id, 'wpp_agents', $possible_match );
          }
          $possible_match = null;

          //** Handle taxonomies */
        } elseif( in_array( $attribute, ( array )$taxonomies ) ) {
          $value = explode( ',', (string)$value );
          foreach( $value as $v ) {
            $v = trim( $v );
            if( !empty( $v ) ) {
              $to_add_taxonomies[ $attribute ][ ] = apply_filters( 'wpp_xml_import_value_on_import', $v, $attribute, 'taxonomy', $post_id, $schedule_settings );
            }
          }

          //** Handle Main Post Table data */
        } elseif( in_array( $attribute, $post_table_columns ) ) {
          /** Handle values that are stored in main posts table */
          $wpdb->update( $wpdb->posts, array( $attribute => apply_filters( 'wpp_xml_import_value_on_import', $value, $attribute, 'post_table', $post_id, $schedule_settings ) ), array( 'ID' => $post_id ) );
        } elseif($attribute_data['multiple']){
            if(!in_array($value, $prev_value)) // checking whether value already exist. If not then add it.
              add_post_meta( $post_id, $attribute, $value );

          //** Handle regular meta fields */
        } else {
          $value = apply_filters( 'wpp_xml_import_value_on_import', $value, $attribute, 'meta_field', $post_id, $schedule_settings );
          if( !empty( $value ) ) {
            update_post_meta( $post_id, $attribute, $value );
          }
        }

      }

      /** Add processed attribute to array */
      $processed_attributes[ ] = $attribute;

    } /* end: foreach( $data as $attribute => $values )  */

    //** Add all taxonomies */
    if( !empty( $to_add_taxonomies ) ) {
      foreach( $to_add_taxonomies as $tax_slug => $terms ) {
        //** We are not appending, but replacing all of them */
        if( !taxonomy_exists( $tax_slug ) ) {
          continue;
        }
        $tt_ids = wp_set_object_terms( $post_id, $terms, $tax_slug );
      }
    }

    //** Set property_type to default if not set from individual property */
    if( !in_array( 'property_type', $processed_attributes ) ) {
      update_post_meta( $post_id, 'property_type', $schedule_settings[ 'property_type' ] );
    }

    //** Take note of which import schedule this property came from for future association */
    update_post_meta( $post_id, 'wpp_import_schedule_id', $schedule_id );

    //** Update last imported timestamp to current */
    update_post_meta( $post_id, 'wpp_import_time', time() );

    //** Set GPID for property if one isnt set */
    WPP_F::maybe_set_gpid( $post_id );

    update_post_meta( $post_id, 'exclude_from_supermap', ( isset( $exclude_from_supermap ) && $exclude_from_supermap !== false ? $exclude_from_supermap : 'false' ) );

    /**
     * New experimental behavior:
     * If we have a coordinates then we have to keep them with help of 'manual_coordinates' flag.
     * As result, it will protect address field from modification of by Google Validation Service (but only if Address is not empty, other vice we will receive address by coordinates on Revalidate All Addresses)
     *
     * @date 23.02.2013
     * @author odokienko@UD
     */
    if( !empty( $data[ 'latitude' ] ) && !empty( $data[ 'longitude' ] ) ) {
      update_post_meta( $post_id, 'manual_coordinates', 'true' );
    }

    //** Attempt to reassemble the 'address_attribute' if it is not set */
    if( $address_attribute = $wp_properties[ 'configuration' ][ 'address_attribute' ] ) {
      $current_address = get_post_meta( $post_id, $address_attribute, true );
      if( empty( $current_address ) ) {
        if( $fixed_address = WPP_F::reassemble_address( $post_id ) ) {
          update_post_meta( $post_id, $address_attribute, $fixed_address );
          class_wpp_property_import::maybe_echo_log( "No address found for property, reassembled it from parts: {$fixed_address}" );
        }
      }
    }

    //** (Re)Validate address */
    if( isset( $schedule_settings[ 'revalidate_addreses_on_completion' ] ) && $schedule_settings[ 'revalidate_addreses_on_completion' ] == 'on' ) {
      self::maybe_echo_memory_usage( __( 'before revalidating process', ud_get_wpp_importer()->domain ), $schedule_id );
      $validation_result = WPP_F::revalidate_address( $post_id, array( 'skip_existing' => 'true' ) );
      $validation_statuses = array(
        'skipped' => __( 'Address validation was skipped because address has already been validated.', ud_get_wpp_importer()->domain ),
        'empty_address' => __( 'Address validation has been skipped because address/coordinates is empty. Check your Attribute Map for \'Address\' attribute.', ud_get_wpp_importer()->domain ),
        'over_query_limit' => __( 'Address validation has failed because the Google service has denied the request ( OVER QUERY LIMIT ).', ud_get_wpp_importer()->domain ),
        'failed' => __( 'Address validation has failed.', ud_get_wpp_importer()->domain ),
        'updated' => __( 'Address validation has been successfully completed.', ud_get_wpp_importer()->domain ),
      );
      if( !empty( $validation_result[ 'status' ] ) && key_exists( $validation_result[ 'status' ], $validation_statuses ) ) {
        class_wpp_property_import::maybe_echo_log( $validation_statuses[ $validation_result[ 'status' ] ] );
      }
      self::maybe_echo_memory_usage( __( 'after revalidating process', ud_get_wpp_importer()->domain ), $schedule_id );
    }

    if( isset( $revalidation_result[ 'geo_data' ] ) && count( $revalidation_result[ 'geo_data' ] ) ) {
      $wpp_import_result_stats[ ] = count( $revalidation_result[ 'geo_data' ] ) . " addresses re-validated.";
    }

    //** Save parent GPID association to meta for later association */
    if( !empty( $data[ 'parent_gpid' ][ 0 ] ) ) {

      // Get parent's post_id from GPID (Global Property ID)
      $_parent_property_id = WPP_F::get_property_from_gpid( $data[ 'parent_gpid' ][ 0 ] );

      // Ensure that the found parent does not have the same post_id as the current listings, if a parent is found at all.  - potanin@UD
      if( !$_parent_property_id || ( $_parent_property_id !== $post_id ) ) {
        update_post_meta( $post_id, 'parent_gpid', $data[ 'parent_gpid' ][ 0 ] );
      }

      class_wpp_property_import::maybe_echo_log( "Parent GPID found for {$post_id} -> {$data['parent_gpid'][0]}  ." );

      // Update this property's post_parent with parent's post_id
      if( $_parent_property_id && ( $post_id !== $_parent_property_id ) ) {
        class_wpp_property_import::maybe_echo_log( "Parent post_id [{$_parent_property_id}] found for {$post_id} form  {$data['parent_gpid'][0]}  ." );
        $wpdb->update( $wpdb->posts, array( 'post_parent' => $_parent_property_id ), array( 'ID' => $post_id ) );
      } else {
        class_wpp_property_import::maybe_echo_log( "Failed to get post_id for parent of {$post_id} form  {$data['parent_gpid'][0]}  ." );
      }

    }

    do_action( 'wpp_import_property', $post_id, $data );

    $return_data = array(
      '0' => $post_id,
      '1' => $mode,
    );

    return $return_data;
  }

  /**
   * This function gets and inits our rets object
   *
   */
  static public function connect_rets( $import ) {
    global $wp_properties;

    /** Create my new rets feed */
    $rets = new WPP_RETS();

    /** @updated 3.2.6 - potanin@UD */
    if( $wp_properties[ 'configuration' ][ 'developer_mode' ] == 'true' ) {
      $upload_dir = wp_upload_dir();
      $rets->SetParam( 'debug_mode', true );
      $rets->SetParam( 'debug_file', $upload_dir[ 'basedir' ] . '/xmli.rets.log' );
    }

    // @Allows for method override on RETS requests.  - potanin@UD
    if( isset( $import[ 'rets_use_post_method' ] ) && $import[ 'rets_use_post_method' ] == 'true' ) {
      $rets->SetParam( 'use_post_method', true );
    }

    $rets->AddHeader( 'Accept', '*/*' );
    $rets->AddHeader( 'User-Agent', !empty( $import[ 'rets_agent' ] ) ? $import[ 'rets_agent' ] : 'WP-Property/1.0' );
    $rets->AddHeader( 'RETS-Version', !empty( $import[ 'rets_version' ] ) ? $import[ 'rets_version' ] : 'RETS/1.7' );

    if( isset( $import[ 'rets_agent_password' ] ) && !empty( $import[ 'rets_agent_password' ] ) ) {
      $connect = $rets->Connect( $import[ 'url' ], $import[ 'rets_username' ], $import[ 'rets_password' ], $import[ 'rets_agent_password' ] );
    } else {
      $connect = $rets->Connect( $import[ 'url' ], $import[ 'rets_username' ], $import[ 'rets_password' ] );
    }

    if( !$connect ) {
      $error_details = $rets->Error();
      $error_text = !empty( $error_details[ 'text' ] ) ? ' - ' . strip_tags( $error_details[ 'text' ] ) : '';
      $error_code = !empty( $error_details[ 'code' ] ) ? ' - ' . $error_details[ 'code' ] : '';
      $error_type = strtoupper( $error_details[ 'type' ] );
      throw new Exception( "Could not connect to RETS server: {$error_type}{$error_code}{$error_text}" );
      return false;
    }

    return $rets;
  }

  /**
   * Fetch a file from an FTP server.
   *
   * @author potanin@UD
   * @param $_connection_string
   * @param array $options
   * @return array|WP_Error
   */
  static public function get_remote_ftp( $_connection_string, $options = array() ) {
    //die('ftp_get');

    try {

      class_wpp_property_import::maybe_echo_log( "Trying connection to [{$_connection_string}]." );

      $_ftp_result = file_get_contents( $_connection_string );

      if( !$_ftp_result ) {
        return new WP_Error( 'fail', __( 'Unable to get data from FTP server.', ud_get_wpp_importer()->domain ) );
      }


    } catch ( \Exception $e ) {
      return new WP_Error( 'fail', __( 'Unable to establish FTP connection.', ud_get_wpp_importer()->domain ) );
    }


    return array( 'body' => $_ftp_result );

  }

  /**
   * Makes a request to specified url
   *
   * Recognized XML or Google Spreadsheet feed
   *
   * @todo $limit=1 on RETS seems to prevent preview query from looking past the first result, and doing something like /ROWS/ROW[L_Class = "COMMERCIAL"] does not work, but need to test. - potanin@UD
   * @todo Add error handling, right now rets class jus dies on errors, such as with GetObject, no feedback to user.
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   * @param $url
   * @param string $method
   * @param $data
   * @return array|WP_Error
   */
  static public function wpp_make_request( $url, $method = 'get', $data ) {
    global $wpp_property_import, $wpp_import_result_stats, $wp_properties;

    //** Set schedule ID */
    $schedule_id = $data[ 'schedule_id' ];

    // Detect protocol.
    if( strpos( $url, 'ftp://' ) === 0 ) {
      $_protocol = 'ftp';
    } else {
      $_protocol = 'http';
    }

    /** Go ahead and include our Zend directory */
    set_include_path( ud_get_wpp_importer()->path( 'lib/third-party/', 'dir' ) );
    require_once( 'Zend/Gdata/Spreadsheets.php' );
    require_once( 'Zend/Gdata/ClientLogin.php' );

    if( isset( $_REQUEST[ 'stepping' ] ) ) {
      // Open up our temp file for writing
      if( !is_dir( WPP_Path . "cache" ) ) {
        mkdir( WPP_Path . "cache" );
        chmod( WPP_Path . "cache", 0755 );
      }
    }

    $newvars = array();

    // Google Spreadsheet Importing
    if( $data[ 'wpp_property_import' ][ 'source_type' ] == 'gs' ) {

      try {

        /** Only connect if we aren't a stepping element */
        if( !isset( $_REQUEST[ 'stepping_element' ] ) ) {
          $gdata = new gc_import();
          /* Build our query */
          $query = new Zend_Gdata_Spreadsheets_ListQuery();

          if( $data[ 'wpp_property_import' ][ 'spreadsheet_key' ] ) {
            $query->setSpreadsheetKey( $data[ 'wpp_property_import' ][ 'spreadsheet_key' ]  );
          } else {
            $query->setSpreadsheetKey( $gdata->parse_spreadsheet_key( $data[ 'wpp_property_import' ][ 'url' ] ) );
          }

          if( $data[ 'wpp_property_import' ][ 'worksheet_key' ] ) {
            $query->setWorksheetId( $data[ 'wpp_property_import' ][ 'worksheet_key' ] );
          } else {
            $query->setWorksheetId( 1 );
          }

          if( $data[ 'wpp_property_import' ][ 'worksheet_query_url' ] ) {
            $query_url = $data[ 'wpp_property_import' ][ 'worksheet_query_url' ];
          } else {
            $query_url = $query->getQueryUrl();
          }

          if( !empty( $data[ 'wpp_property_import' ][ 'google_extra_query' ] ) ) $query_url .= "?" . $data[ 'wpp_property_import' ][ 'google_extra_query' ];

          /** Connect to the spreadsheet */
          $gdata->gdata_connect( $data[ 'wpp_property_import' ][ 'google_username' ], $data[ 'wpp_property_import' ][ 'google_password' ] );

          $listFeed = $gdata->gdata[ 'ss_service' ]->getListFeed( $query_url );

          /** Loop through the rows, building our XML string */
          $str = '<?xml version="1.0"?><GC>';
          $rows = 1;


          foreach( $listFeed->entries AS $entry ) {
            $str .= '<ROW>';
            $str .= '<IMPORT_ROWID>' . $rows . '</IMPORT_ROWID>';
            /* Build a generic random number */
            $rowData = $entry->getCustom();
            foreach( $rowData as $customEntry ) {
              $str .= '<' . strtoupper( $customEntry->getColumnName() ) . '><![CDATA[' . htmlentities( $customEntry->getText(), ENT_QUOTES, "UTF-8" ) . ']]></' . strtoupper( $customEntry->getColumnName() ) . '>';
            }
            $str .= '</ROW>';
            $rows++;
          }
          $str .= '</GC>';

          if( isset( $_REQUEST[ 'stepping' ] ) ) {
            file_put_contents( WPP_Path . "cache/" . $data[ 'wpp_property_import' ][ 'hash' ] . ".xml", $str );
          }

        } else {
          $str = file_get_contents( WPP_Path . "cache/" . $data[ 'wpp_property_import' ][ 'hash' ] . ".xml" );
        }

        return array( 'body' => $str );
      } catch ( Exception $e ) {
        die( json_encode( array( 'success' => 'false', 'message' => $e->getMessage() ) ) );
      }

    }

    // CSV Importing
    if( $data[ 'wpp_property_import' ][ 'source_type' ] == 'csv' ) {

      $url_array = parse_url( $url );

      if( !empty( $url_array[ 'query' ] ) ) {
        parse_str( $url_array[ 'query' ], $newvars );
      }

      if( $method == 'post' && count( $newvars ) && !empty( $newvars ) ) {
        $return = wp_remote_post( $url, array( 'timeout' => apply_filters( 'wpp_xi_wp_remote_timeout', 300, array( 'method' => $method, 'url' => $url ) ), 'body' => array( 'request' => serialize( $newvars ) ) ) );
      } else {

        if( $_protocol === 'ftp' ) {
          $return = self::get_remote_ftp( $url, array( 'timeout' => apply_filters( 'wpp_xi_wp_remote_timeout', 300, array( 'method' => $method, 'url' => $url ) ) ) );
        } else {
          $return = wp_remote_get( $url, array( 'sslverify' => false, 'timeout' => apply_filters( 'wpp_xi_wp_remote_timeout', 300, array( 'method' => $method, 'url' => $url ) ) ) );
        }

      }

      //** Check if data is JSON or XML */
      if( is_wp_error( $return ) ) {
        return $return;
      } else {

        //** Create a temporary file that fgetcsv() can read through */
        if( !empty( $return[ 'body' ] ) ) {

          $xml_from_csv = WPP_F::csv_to_xml( $return[ 'body' ] );

          //** Load the converted XML Back into body - as if nothing even happened */
          $return[ 'body' ] = $xml_from_csv;

          return $return;

        }

      }

    }

    // RETS Importing
    if( $data[ 'wpp_property_import' ][ 'source_type' ] == 'rets' ) {

      try {

        $import = $data[ 'wpp_property_import' ];

        /** Get my rets object */
        $rets = class_wpp_property_import::connect_rets( $import );

        //class_wpp_property_import::maybe_echo_log( "Raw feed data loaded from cache." );

        /** Include our function here to write arrays to XML */
        if( !function_exists( 'write' ) ) {
          function write( XMLWriter $xml, $data ) {

            foreach( $data as $key => $value ) {

              $key = !is_numeric( $key ) ? $key : '_' . $key;

              if( is_array( $value ) ) {
                $xml->startElement( $key );
                write( $xml, $value );
                $xml->endElement();
                continue;
              }

              @$xml->writeElement( $key, $value );

            }
          }
        }

        /** Start our XML */
        $xml = new XmlWriter();

        // Let's trying not to use openMemory() but openUri() odokienko@UD
        //$xml->openMemory()

        $upload_dir = wp_upload_dir();

        // Create file to processed RETS XML
        $temp_directory = $upload_dir[ 'basedir' ] . "/wpp_import_files/temp/";
        if( !is_dir( $temp_directory ) ) {
          @mkdir( $temp_directory, 0755, 1 );
          @chmod( $temp_directory, 0755 );
          if( !is_dir( $temp_directory ) ) {
            throw new Exception( 'Could not create the directory: ' . $temp_directory );
          }
        }
        @touch( $xml_file = $upload_dir[ 'basedir' ] . "/wpp_import_files/temp/{$schedule_id}_rets_output.xml" );

        $xml_file = realpath( $xml_file );

        $xml->openUri( $xml_file );
        $xml->startDocument( '1.0', 'UTF-8' );
        $xml->startElement( 'ROWS' );

        /** set limit */
        $limit = !empty( $import[ 'limit_scanned_properties' ] ) ? $import[ 'limit_scanned_properties' ] : 0;
        /** Determine RETS resource */
        $resource = !empty( $import[ 'rets_resource' ] ) ? $import[ 'rets_resource' ] : self::$default_rets_resource;
        /** Determine our main ID */
        $rets_pk = !empty( $import[ 'rets_pk' ] ) ? $import[ 'rets_pk' ] : self::$default_rets_pk;
        /** Determine our Photo object */
        //$rets_photo = !empty( $import['rets_photo'] ) ? $import['rets_photo'] : self::$default_rets_photo;
        /** Determine our Query */
        $rets_query = !empty( $import[ 'rets_query' ] ) ? $import[ 'rets_query' ] : self::$default_rets_query;

        /** Do our dynamic dates */
        $rets_query = str_replace( array(
          '[yesterday]',
          '[this_week]',
          '[this_month]',
          '[next_month]',
          '[previous_month]'
        ), array(
          date( "Y-m-d", strtotime( '-1 day' ) ),
          date( "Y-m-d", strtotime( '-7 day' ) ),
          date( "Y-m", strtotime( 'now' ) ) . '-01',
          date( "Y-m-d", strtotime( '+1 month' ) ),
          date( "Y-m-d", strtotime( '-1 month' ) )
        ), $rets_query );

        /** On preview, we have to get the FULL feed, but not all the images */
        if(
          ( isset( $_REQUEST[ 'wpp_action_type' ] ) && $_REQUEST[ 'wpp_action_type' ] == 'source_evaluation' )
          || ( isset( $_REQUEST[ 'preview' ] ) && $_REQUEST[ 'preview' ] == 'true' )
          || ( isset( $_REQUEST[ 'raw_preview' ] ) && $_REQUEST[ 'raw_preview' ] == 'true' )
        ) {
          $partial_cache = true;
          $limit = 1;
          $_required = array();
          $_one_required = array();
          $_searchable = array();
          /* Do quick analysis of meta data @updated 1.3.6 */
          foreach( (array)$rets->GetMetadata( $resource, $import[ 'rets_class' ] ) as $item ) {
            $_attribute_key = $item[ 'StandardName' ] ? $item[ 'StandardName' ] : $item[ 'LongName' ];
            $item = array_filter( (array)$item );
            if( isset($item[ 'Required' ]) && $item[ 'Required' ] == 1 ) {
              $_required[ $_attribute_key ] = $item;
            }
            if( isset($item[ 'Required' ]) && $item[ 'Required' ] == 2 ) {
              $_one_required[ $_attribute_key ] = $item;
            }
            if( isset($item[ 'Searchable' ]) && $item[ 'Searchable' ] ) {
              $_searchable[ $_attribute_key ] = $item;
            }
          }
        }

        // $limit = 20;
        /** Search for Properties */
        $search = $rets->SearchQuery( $resource, $import[ 'rets_class' ], $rets_query, array( 'Limit' => $limit ) );

        //echo "<br />rets_query: " . $rets_query;
        //echo "<br />NumRows: " . $rets->NumRows();
        //echo "<br />TotalRecordsFound: " . $rets->TotalRecordsFound();
        //die();

        if( !$search ) {

          preg_match_all( '/\(([^=]+)=(.*?)\)/sim', $rets_query, $matches, PREG_SET_ORDER );

          foreach( (array)$matches as $match ) {
            $_used_keys[ ] = $match[ 1 ];
          }

          /* See if we are missing required attributes */
          if( $_error = $rets->Error() ) {

            if( !empty( $_error[ 'code' ] ) ) {
              switch( $_error[ 'code' ] ) {
                /* Missing close parenthesis on subquery. | Required search fields missing. | Illegal number in range for field List Price. */
                case 20203:
                  if( count( $_used_keys ) != count( $_required ) ) {
                    throw new Exception( "The search query failed because this provider requires certain attributes to be included in the search query. Required attribute(s): " . implode( ', ', array_keys( $_required ) ) . ( $_one_required ? ". At least one: " . implode( ', ', array_keys( $_one_required ) ) : '' ) );
                  }
                  break;
              }
            }

            throw new Exception( "There was an issue doing the RETS search: " . $_error[ 'text' ] );

          } else {
            throw new Exception( "No Listings are found. Check  your Query." );
          }

        }

        class_wpp_property_import::maybe_echo_log( 'RETS connection established. Got ' . $rets->NumRows( $search ) . ' out of ' . $rets->TotalRecordsFound( $search ) . ' total listings.' );

        $processed_properties = array();

        $row_count = 1;

        //** Create a temp directory using the import ID as name */
        $image_directory = class_wpp_property_import::create_import_directory( array( 'ad_hoc_temp_dir' => $schedule_id ) );

        if( $image_directory ) {
          $image_directory = $image_directory[ 'ad_hoc_temp_dir' ];
        } else {
          class_wpp_property_import::maybe_echo_log( sprintf( __( 'Image directory %1s could not be created.', ud_get_wpp_importer()->domain ), $image_directory ) );
        }

        while( $row = $rets->FetchRow( $search ) ) {
          class_wpp_property_import::keep_hope_alive();
          $processed_properties[ $row[ $rets_pk ] ] = true;
          //** Write row data, with formatted image data, back to $xml object */
          write( $xml, array(
            'ROW' => $row
          ) );
          $row_count++;
        }
        /** End of RETS $search cycle */

        if( is_array( $processed_properties ) ) {
          $processed_properties = count( $processed_properties );
        }

        class_wpp_property_import::maybe_echo_log( "Initial RETS cycle complete, processed {$processed_properties} properties." );
        $wpp_import_result_stats[ ] = "Found {$processed_properties} properties in RETS feed.";

        $xml->endElement();
        $xml->endDocument();

        $rets->FreeResult( $search );

        $rets->Disconnect(); //Disconnect and logout from RETS

        unset ( $xml );
        unset ( $rets );

      } catch ( Exception $e ) {
        die( json_encode( array( 'success' => 'false', 'message' => mb_convert_encoding( $e->getMessage(), 'UTF8' ) ) ) );
      }

      $str = file_get_contents( $xml_file );
      return array( 'body' => $str );

    }

    // XML / JSON and WP-Property Exports
    if( $data[ 'wpp_property_import' ][ 'source_type' ] == 'xml' || $data[ 'wpp_property_import' ][ 'source_type' ] == 'wpp' || $data[ 'wpp_property_import' ][ 'source_type' ] == 'json' ) {

      /** Only connect if we aren't a stepping element */
      if( !isset( $_REQUEST[ 'stepping_element' ] ) ) {

        $url_array = parse_url( $url );

        if( !empty( $url_array[ 'query' ] ) ) {
          parse_str( $url_array[ 'query' ], $newvars );
        }

        if( $method == 'post' && !empty( $newvars ) && count( $newvars ) ) {
          $return = wp_remote_post( $url, array( 'timeout' => apply_filters( 'wpp_xi_wp_remote_timeout', 300, array( 'method' => $method, 'url' => $url ) ), 'body' => array( 'request' => serialize( $newvars ) ) ) );
        } else {

          if( $_protocol === 'ftp' ) {
            $return = self::get_remote_ftp( $url, array( 'timeout' => apply_filters( 'wpp_xi_wp_remote_timeout', 300, array( 'method' => $method, 'url' => $url ) ) ) );
          } else {
            $return = wp_remote_get( $url, array( 'timeout' => apply_filters( 'wpp_xi_wp_remote_timeout', 300, array( 'method' => $method, 'url' => $url ) ) ) );
          }

        }

        //** Check if data is JSON or XML */
        if( is_wp_error( $return ) ) {
          return $return;
        } else {
          $maybe_json = WPP_F::json_to_xml( $return[ 'body' ] );
        }

        //** If json_to_xml() returns something then data was in JSON, but is now converted into XML */
        if( $maybe_json ) {
          $return[ 'body' ] = $maybe_json;
        }

        /** Write our cached file */
        if( isset( $_REQUEST[ 'stepping' ] ) ) {
          file_put_contents( WPP_Path . "cache/" . $data[ 'wpp_property_import' ][ 'hash' ] . ".xml", $return[ 'body' ] );
        }

      } else {
        $str = file_get_contents( WPP_Path . "cache/" . $data[ 'wpp_property_import' ][ 'hash' ] . ".xml" );
        $return[ 'body' ] = $str;
      }

      return $return;
    }

  }

  /**
   * Attaches an image from url to specified post
   *
   * @args passed via $settings: $schedule_settings
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function attach_image( $settings = false ) {
    global $wpp_property_import, $wpdb, $wpp_import_result_stats;

    $do_not_check_existance = false;
    $post_id = false;
    $data = false;
    $schedule_settings = false;

    if( !$settings ) {
      return false;
    }

    $image_request_method = false;

    //** Extra image import settings */
    extract( $settings );

    $image = isset( $image ) ? trim( $image ) : '';

    if( empty( $image ) ) {
      return false;
    }

    if( !( ( $uploads = wp_upload_dir( current_time( 'mysql' ) ) ) && false === $uploads[ 'error' ] ) ) {
      return false; // upload dir is not accessible
    }

    //** Figure out if this file is local, or remote based off the URL */

    //$local_file = ( stripos( 'http://', $image ) === false || stripos( 'https://', $image ) === false ) ? true : false;
    $local_file = ( preg_match( '%^https?://%i', $image ) ? false : true );

    $url_parts = parse_url( $image );
    if( !empty( $url_parts[ 'path' ] ) ) {
      $file_parts = pathinfo( $url_parts[ 'path' ] );
      if( isset( $url_parts[ 'query' ] ) ) {
        $filename = ( $file_parts[ 'filename' ] . '-' . $url_parts[ 'query' ] );
      } else {
        $filename = $file_parts[ 'filename' ];
      }
      // remove all character symbols
      $filename = preg_replace( "/[^-_A-Za-z0-9]/", "", $filename );
      $filename .= '.' . ( ( in_array( strtolower( $file_parts[ 'extension' ] ), array( 'jpg', 'jpeg', 'gif' ) ) ) ? $file_parts[ 'extension' ] : 'jpg' );
    } else {
      //** Will break out URL or Path properly. File filename cannot be generated for whatever reason, we create a random one. */
      $filename = rand( 100000000, 999999999 ) . '.jpg';
    }

    $filename = sanitize_file_name( $filename );

    $filename = apply_filters( 'wpp_xi_temp_file_path', $filename, array( 'filename' => $filename, 'settings' => $settings, 'hash_image' => $hash_image, 'image' => $image ) );

    // Create md5 hash for the new image, to see if it already exists */
    $hash_image = @md5_file( $image );

    //** Create directory structure if it isn't there already */
    $import_directory = class_wpp_property_import::create_import_directory( array( 'post_id' => $post_id ) );

    if( $import_directory[ 'post_dir' ] ) {
      $property_directory = $import_directory[ 'post_dir' ];
    }

    if( !is_dir( $property_directory ) ) {
      class_wpp_property_import::maybe_echo_log( "Unable to create image directory: {$property_directory}." );
      return false;
    }

    //** Update uploads path for our unique file storage structure */
    $new_file_path = trailingslashit( $property_directory ) . wp_unique_filename( $property_directory, $filename );

    $new_file_path = apply_filters( 'wpp_xi_new_file_path', $new_file_path, array( 'dir' => $import_directory[ 'post_dir' ], 'url' => $import_directory[ 'post_url' ], 'filename' => $filename, 'settings' => $settings, 'hash_image' => $hash_image, 'return' => 'path' ) );

    //** If do_not_check_existance is passed, we skip this step */
    if( !$do_not_check_existance ) {

      $file_exists = $wpdb->get_row( $wpdb->prepare( "SELECT ID, guid, post_date, post_parent FROM {$wpdb->posts} WHERE post_content_filtered = '" . $hash_image . "' AND post_parent = %d LIMIT 1", $post_id ) );

      if( $file_exists && $post_id == $file_exists->post_parent ) {
        do_action( 'wpp_xml_import_attach_image', $post_id, $image, $file_exists->ID, $data );

        return array(
          'thumb_id' => $file_exists->ID,
          'action' => 'image_exists'
        );

      }

      if( !empty( $file_exists ) ) {
        $uploads_old = wp_upload_dir( $file_exists->post_date );
        $old_file = pathinfo( $file_exists->guid );
        $old_file_size = @filesize( $uploads_old[ 'path' ] . '/' . $old_file[ 'basename' ] );
        $new_file_size = intval( class_wpp_property_import::get_remote_file_size( $image, false ) );

        if( ( $old_file_size == $new_file_size ) && ( intval( $file_exists->post_parent ) == intval( $post_id ) ) ) {
          return false;
        } else if( $old_file_size == $new_file_size ) {
          wp_delete_attachment( $post_id, true );
        }
      }

    } /* end do_not_check_existance */

    //** Frist method of getting images */
    if( $local_file ) {

      //** If URL appears to be a path, we load contents, but what if it's not there? */
      if( file_exists( $image ) ) {
        $content = file_get_contents( $image );
        $this_image_size = @getimagesize( $image );
      } else {
        //class_wpp_property_import::maybe_echo_log( sprintf( __( 'Listing image  ( ' .  $image . ' )  appears to be local, but could not be accessed.', ud_get_wpp_importer()->domain ), $image ) );
        return;
      }

      $image_request_method = __( 'on disk using file_get_contents()', ud_get_wpp_importer()->domain );

    } else {

      if( $schedule_settings[ 'log_detail' ] == 'on' ) {
        class_wpp_property_import::maybe_echo_log( sprintf( __( 'Attempting to get image  ( ' . $image . ' )  using wp_remote_get()', ud_get_wpp_importer()->domain ), $image ) );
      }

      $image_request = wp_remote_get( preg_replace( '~\s~', '%20', $image ), array( 'sslverify'   => false, 'timeout' => apply_filters( 'wpp_xi_wp_remote_timeout', 300 ) ) );

      if( is_wp_error( $image_request ) ) {
        class_wpp_property_import::maybe_echo_log( "Unable to get image ( {$image} ) : " . ( !empty( $image_request ) ) ? $image_request->get_error_message() : '' );
        return;
      }

      $content = $image_request[ 'body' ];

      //** By now we have tried all possible venues of download the image */
      if( empty( $content ) ) {
        class_wpp_property_import::maybe_echo_log( sprintf( __( 'Unable to get image %1s using the %2s method.', ud_get_wpp_importer()->domain ), $image, $image_request_method ) );
        return false;
      }

    }

    //** Save the new image to disk */
    file_put_contents( $new_file_path, $content );
    unset( $content );

    $this_image_size = @getimagesize( $new_file_path );

    //** Check if image is valid, delete if not, and log message if detail is on*/
    if( !$this_image_size ) {

      if( $schedule_settings[ 'log_detail' ] == 'on' ) {
        class_wpp_property_import::maybe_echo_log( sprintf( __( 'Image %1s corrupted - skipped.', ud_get_wpp_importer()->domain ), $image ) );
      }

      @unlink( $new_file_path );

      return false;
    }

    //** If minimum width or height are set, we check them here, and delete image if does  not meet quality standards */
    if(
      ( $schedule_settings[ 'min_image_width' ] > 0 && ( $this_image_size[ 0 ] < $schedule_settings[ 'min_image_width' ] ) ) ||
      ( $schedule_settings[ 'max_image_height' ] > 0 && ( $this_image_size[ 1 ] < $schedule_settings[ 'max_image_height' ] ) )
    ) {
      $image_size_fail = true;
    }

    if( $image_size_fail ) {

      if( $schedule_settings[ 'log_detail' ] == 'on' ) {
        class_wpp_property_import::maybe_echo_log( sprintf( __( 'Image %1s downloaded, but image size failed quality standards - deleting.', ud_get_wpp_importer()->domain ), $image ) );
        $wpp_import_result_stats[ 'quality_control' ][ 'skipped_images' ]++;
      }

      if( $local_file ) {
        @unlink( $new_file_path );
      }

      return false;
    }

    /** Try to remove the old one, if it still exists */
    if( !preg_match( '/^http/', $image ) ) {
      @unlink( $image );
    }

    $new_file_path = apply_filters( 'wpp_xi_image_save', $new_file_path, $settings );

    //** Bail if it didn't work for some reason */
    if( !file_exists( $new_file_path ) ) {
      class_wpp_property_import::maybe_echo_log( sprintf( __( 'Unable to save image %1s.', ud_get_wpp_importer()->domain ), $image ) );
      return false;
    }

    // Set correct file permissions
    $stat = stat( dirname( $new_file_path ) );

    chmod( $new_file_path, 0644 );

    // get file type
    $wp_check_filetype = wp_check_filetype( $new_file_path );

    // No file type! No point to proceed further
    if( ( !$wp_check_filetype[ 'type' ] || !$wp_check_filetype[ 'ext' ] ) && !current_user_can( 'unfiltered_upload' ) ) {
      class_wpp_property_import::maybe_echo_log( "Image saved to disk, but some problem occured with file type." );
      return false;
    }

    include_once ABSPATH . 'wp-admin/includes/image.php';

    // use image exif/iptc data for title and caption defaults if possible
    if( $image_meta = wp_read_image_metadata( $new_file_path ) ) {
      if( trim( $image_meta[ 'title' ] ) )
        $title = $image_meta[ 'title' ];
      if( trim( $image_meta[ 'caption' ] ) )
        $post_content = $image_meta[ 'caption' ];
    }

    // Compute the URL
    $url = $uploads[ 'baseurl' ] . "/wpp_import_files/$post_id/$filename";

    $url = apply_filters( 'wpp_xi_compute_url', $url, array( 'dir' => $import_directory[ 'post_dir' ], 'url' => $import_directory[ 'post_url' ], 'filename' => $filename, 'settings' => $settings, 'hash_image' => $hash_image, 'return' => 'url' ) );

    $attachment = array(
      'post_mime_type' => $wp_check_filetype[ 'type' ],
      'guid' => $url,
      'post_name' => 'wpp_i_' . time() . '_' . rand( 10000, 100000 ),
      'post_parent' => $post_id,
      'post_title' => ( $title ? $title : 'Property Image' ),
      'post_content' => ( $post_content ? $post_content : '' ),
      'post_content_filtered' => $hash_image
    );

    $thumb_id = wp_insert_attachment( $attachment, $new_file_path, $post_id );

    if( !is_wp_error( $thumb_id ) ) {
      // first include the image.php file
      // for the function wp_generate_attachment_metadata() to work
      require_once( ABSPATH . 'wp-admin/includes/image.php' );
      $attach_data = wp_generate_attachment_metadata( $thumb_id, $new_file_path );
      wp_update_attachment_metadata( $thumb_id, $attach_data );

      update_post_meta( $thumb_id, 'wpp_imported_image', true );
      do_action( 'wpp_xml_import_attach_image', $post_id, $image, $thumb_id, $data );
    }

    return array(
      'thumb_id' => $thumb_id,
      'action' => 'image_downloaded',
      'image_size' => filesize( $new_file_path )
    );

  }

  /**
   * Cycle through all properties with parent_gpid and update post_parent variable to match
   *
   * @todo This seems to run every time regardles of if there were any parent/child relationships in the first place.
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc.
   */
  static public function reassociate_parent_ids() {
    global $wpdb;

    class_wpp_property_import::maybe_echo_log( "Beginning parent IDs association." );

    //** Find properties that have a parent GPID. Get IDs and GPIDs of all properties that have parent_gpid meta_key value *//
    $orphans = $wpdb->get_results( "SELECT post_id, meta_value as gpid FROM {$wpdb->postmeta} WHERE meta_key = 'parent_gpid' AND meta_value IS NOT NULL" );

    if( empty( $orphans ) ) {
      //** No orphan properties *//
      class_wpp_property_import::maybe_echo_log( "No orphan properties - association stopped." );
      return;
    }

    //** Find properties' parents by GPID. *//
    foreach( $orphans as $orphan ) {
      $post_parent = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wpp_gpid' AND meta_value = %s LIMIT 0, 1", $orphan->gpid ) );

      if( $post_parent && $post_parent !== $orphan->post_id ) {
        //** Parent found *//
        $wpdb->update( $wpdb->posts, array( 'post_parent' => $post_parent ), array( 'ID' => $orphan->post_id ) );
        class_wpp_property_import::maybe_echo_log( "Associated child {$orphan->post_id} with parent {$post_parent}." );
      }

    }

    class_wpp_property_import::maybe_echo_log( "Parent ID association complete." );
  }

  /**
   * Gets remote file size from url
   *
   * Copyright 2010 - 2012 Usability Dynamics, Inc. <info@usabilitydynamics.com>
   *
   */
  static public function get_remote_file_size( $url, $readable = true ) {
    $parsed = parse_url( $url );
    $host = $parsed[ "host" ];
    $fp = @fsockopen( $host, 80, $errno, $errstr, 20 );
    if( !$fp ) return false;
    else {
      @fputs( $fp, "HEAD $url HTTP/1.1\r\n" );
      @fputs( $fp, "HOST: $host\r\n" );
      @fputs( $fp, "Connection: close\r\n\r\n" );
      $headers = "";
      while( !@feof( $fp ) ) $headers .= @fgets( $fp, 128 );
    }
    @fclose( $fp );
    $return = false;
    $arr_headers = explode( "\n", $headers );
    foreach( $arr_headers as $header ) {
      // follow redirect
      $s = 'Location: ';
      if( substr( strtolower( $header ), 0, strlen( $s ) ) == strtolower( $s ) ) {
        $url = trim( substr( $header, strlen( $s ) ) );
        return get_remote_file_size( $url, $readable );
      }

      // parse for content length
      $s = "Content-Length: ";
      if( substr( strtolower( $header ), 0, strlen( $s ) ) == strtolower( $s ) ) {
        $return = trim( substr( $header, strlen( $s ) ) );
        break;
      }
    }
    if( $return && $readable ) {
      $size = round( $return / 1024, 2 );
      $sz = "KB"; // Size In KB
      if( $size > 1024 ) {
        $size = round( $size / 1024, 2 );
        $sz = "MB"; // Size in MB
      }
      $return = "$size $sz";
    }
    return $return;
  }

  /**
   * Gets data from cached source if it exists
   *
   *
   * @returns false on failure, XML in SimpleXMLElement format if data is there.
   */
  static public function get_cached_source( $schedule_id, $source_type = false ) {
    if( !( ( $uploads = wp_upload_dir( current_time( 'mysql' ) ) ) && false === $uploads[ 'error' ] ) ) {
      return false; // upload dir is not accessible
    }
    if( $source_type ) {
      $source_type = $source_type . '_';
    }
    $cache_file = $uploads[ 'basedir' ] . "/wpp_import_files/temp/{$schedule_id}/{$source_type}cache.xml";
    //** Check if  a source_cache file exists and is not empty */
    if( file_exists( $cache_file ) && filesize( $cache_file ) ) {
      $xml_data = file_get_contents( $cache_file );
      $result[ 'time' ] = filemtime( $cache_file );
      $result[ 'xml_data' ] = $xml_data;
      return $result;
    }
    return false;
  }

  /**
   * This function creates the cached image directory
   *
   * @param array|bool $args If passed, creates a folder in the temp directory
   * @return false on failure, directory path if known
   */
  static public function create_import_directory( $args = false ) {
    if( !( ( $uploads = wp_upload_dir( current_time( 'mysql' ) ) ) && false === $uploads[ 'error' ] ) ) {
      return false; // upload dir is not accessible
    }
    //** The base directory all the other files and directories will be in */
    $base_dir = $uploads[ 'basedir' ] . '/wpp_import_files';
    $base_url = $uploads[ 'baseurl' ] . '/wpp_import_files';
    //** Check if directory is there, or create it and chmod it, for true */
    if( is_dir( $base_dir ) || ( mkdir( $base_dir ) && chmod( $base_dir, 0755 ) ) ) {
      $exists[ 'base_dir' ] = $base_dir;
      $exists[ 'base_url' ] = $base_url;
    }
    /** Create a generic temporary directory */
    $generic_temp_dir = $exists[ 'base_dir' ] . '/temp';
    $generic_temp_url = $exists[ 'base_url' ] . '/temp';
    if( is_dir( $generic_temp_dir ) || ( mkdir( $generic_temp_dir ) && chmod( $generic_temp_dir, 0755 ) ) ) {
      $exists[ 'generic_temp_dir' ] = $generic_temp_dir;
      $exists[ 'generic_temp_url' ] = $generic_temp_url;
    }
    if( $args[ 'ad_hoc_temp_dir' ] ) {
      $ad_hoc_temp_dir = $exists[ 'generic_temp_dir' ] . '/' . $args[ 'ad_hoc_temp_dir' ];
      $ad_hoc_temp_url = $exists[ 'generic_temp_url' ] . '/' . $args[ 'ad_hoc_temp_dir' ];
      if( is_dir( $ad_hoc_temp_dir ) || ( mkdir( $ad_hoc_temp_dir ) && chmod( $ad_hoc_temp_dir, 0755 ) ) ) {
        $exists[ 'ad_hoc_temp_dir' ] = $ad_hoc_temp_dir;
        $exists[ 'ad_hoc_temp_url' ] = $ad_hoc_temp_url;
      }
    }
    if( !empty( $args[ 'post_id' ] ) ) {
      /** Ok find and remove any empty sub directories of the main directory */
      $sub_folders = scandir( $base_dir );
      if( is_array( $sub_folders ) && count( $sub_folders ) ) {
        foreach( $sub_folders as $folder ) {
          if( $folder == '.' || $folder == '..' ) {
            continue;
          }
          if( is_dir( $base_dir . '/' . $folder ) ) {
            $folder_sub_folder = @scandir( $base_dir . '/' . $folder );
            foreach( $folder_sub_folder as $key => $value ) {
              if( $value == '.' || $value == '..' ) {
                unset( $folder_sub_folder[ $key ] );
              }
            }
            if( !count( $folder_sub_folder ) ) {
              self::rrmdir( $base_dir . '/' . $folder );
            }
          }
        }
      }
      /** We're going to change this to round to the nearest 500th, with leading 0s, so find the sub directory */
      $sub_folder = ( ceil( $args[ 'post_id' ] / 500 ) * 500 ) - 500;
      $sub_folder = str_pad( $sub_folder, 15, '0', STR_PAD_LEFT );
      $current_sub_dir = $exists[ 'base_dir' ] . '/' . $sub_folder;
      $current_sub_url = $exists[ 'base_url' ] . '/' . $sub_folder;
      /** Create directory structure if it isn't there already */
      if( is_dir( $current_sub_dir ) || ( mkdir( $current_sub_dir ) && chmod( $current_sub_dir, 0755 ) ) ) {
        $exists[ 'current_sub_dir' ] = $current_sub_dir;
        $exists[ 'current_sub_url' ] = $current_sub_url;
      }
      /** Now make the post directory */
      $post_dir = $current_sub_dir . '/' . $args[ 'post_id' ];
      $post_url = $current_sub_url . '/' . $args[ 'post_id' ];
      if( is_dir( $post_dir ) || ( mkdir( $post_dir ) && chmod( $post_dir, 0755 ) ) ) {
        $exists[ 'post_dir' ] = $post_dir;
        $exists[ 'post_url' ] = $post_url;
      }
    }
    if( is_array( $exists = apply_filters( 'wpp_xi_create_import_directory', $exists, $args ) ) ) {
      return $exists;
    }
    return false;
  }

  /**
   * Called during rule processing for all single values.
   *
   * @params array ( value, rule_attribute, schedule_settings )
   */
  static public function format_single_value( $data ) {

    $data = wp_parse_args( $data, array(
      'uppercase' => false,
      'value' => '',
    ) );

    $data[ 'value' ] = trim( $data[ 'value' ] );

    //** Be sure that value is not empty */
    $output = trim( str_replace( array( "\r\n", "\r", PHP_EOL ), "", $data[ 'value' ] ) );
    if( empty( $output ) ) {
      return '';
    }

    $to_skip_attributes = array( 'images', 'featured-image' );
    $schedule_settings = $data[ 'schedule_settings' ];

    //** Set uppercase if needed */
    if( $data[ 'uppercase' ] ) {
      $data[ 'value' ] = strtoupper( $data[ 'value' ] );
    }

    //** Certain fields should be skipped because they will not use any text formatting */
    if( isset( $data[ 'rule_attribute' ] ) && in_array( $data[ 'rule_attribute' ], $to_skip_attributes ) ) {
      return $data[ 'value' ];
    }

    //* Property type must be a slug */
    if( isset( $data[ 'rule_attribute' ] ) && $data[ 'rule_attribute' ] == 'property_type' ) {
      return UD_F::create_slug( $data[ 'value' ], array( 'separator' => '_' ) );
    }

    //** If caps lock fixing is enabled, and this string is ALL caps */
    if( isset( $data[ 'schedule_settings' ][ 'fix_caps' ] ) && ( $data[ 'schedule_settings' ][ 'fix_caps' ] == 'on' && ( strtoupper( $data[ 'value' ] ) == $data[ 'value' ] ) ) ) {
      $data[ 'value' ] = ucwords( strtolower( $data[ 'value' ] ) );
    }

    //** Attempt to remove any formatting */
    if( isset( $data[ 'schedule_settings' ][ 'force_remove_formatting' ] ) && $data[ 'schedule_settings' ][ 'force_remove_formatting' ] == 'on' ) {
      $data[ 'value' ] = strip_tags( $data[ 'value' ] );
    }

    $data[ 'value' ] = str_replace( '&nbsp;', '&', $data[ 'value' ] );

    return $data[ 'value' ];

  }

  /**
   * Email notification system, for using from cron.
   *
   * @version 2.5.6
   *
   **/
  static public function email_notify( $message_text, $short_text = false ) {
    global $wpdb;

    //** Try to get custom WPP email. If not, the default admin_email will work. */
    if( !$notification_email = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'wpp_importer_cron_email'" ) ) {
      $notification_email = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'admin_email'" );
    }

    //** Need to get the domain from, DB since $_SERVER is not available in cron */
    $siteurl = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'siteurl'" );
    $domain = parse_url( $siteurl, PHP_URL_HOST );

    $subject = 'Update from XML Importer' . ( $short_text ? ': ' . $short_text : '' );
    $headers = 'From: "XML Importer" <xml_importer@' . $domain . '>';

    //$message[] = "Update from XML Importer:\n";
    $message[ ] = '<div style="font-size: 1.6em;margin-bottom: 5px;">XML Importer: ' . $short_text . '</div><div style="font-size: 1em;color:#555555;">' . $message_text . '</div>';

    add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );

    if( wp_mail( $notification_email, $subject, implode( '', $message ), $headers ) ) {
      return true;
    }

    return false;

  }

  /**
   * Convert bytes to a more appropriate format.
   *
   * @version 2.5.6
   */
  static public function format_size( $size ) {
    $sizes = array( " Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB" );
    if( $size == 0 ) {
      return ( 'n/a' );
    } else {
      return ( round( $size / pow( 1024, ( $i = floor( log( $size, 1024 ) ) ) ), 2 ) . $sizes[ $i ] );
    }
  }

  /**
   * Analyzes source feed after it has been converted into XML.
   *
   * @version 3.0.0
   */
  static public function analyze_feed( $xml, $root = '' ) {
    $root_element_parts = explode( '/', $root );
    $common_tag_name = end( $root_element_parts );
    $query = "//{$common_tag_name}/*[not( * )]";
    /** Get all unique tags */
    $isolated_tags = @$xml->xpath( $query );
    $matched_tags = array();
    if( is_array( $isolated_tags ) ) {
      foreach( $isolated_tags as $node ) {
        $matched_tags[ $node->getName() ] = true;
      }
      /** Isolate tag names in array */
      $matched_tags = array_keys( $matched_tags );
    }
    return is_array( $matched_tags ) ? $matched_tags : false;
  }

  /**
   * Maybe replace matched value with the value defined by user.
   *
   * @param $value
   * @param $attribute
   * @param $type
   * @param $post_id
   * @param $schedule_settings
   */
  static function maybe_replace_matched_value( $value, $attribute, $type, $post_id, $schedule_settings ) {

    if( !isset( $schedule_settings[ 'map' ] ) || !is_array( $schedule_settings[ 'map' ] ) ) {
      return $value;
    }

    $matches = array();
    foreach( $schedule_settings[ 'map' ] as $item ){
      if( !empty( $item[ 'wpp_attribute' ] ) && $item[ 'wpp_attribute' ] == $attribute ) {
        if( !empty( $item['matches'] ) && is_array( $item['matches'] ) ) {
          $matches = $item['matches'];
        }
        break;
      }
    }

    if( empty( $matches ) ) {
      return $value;
    }

    $matched_values = array();
    foreach( $matches as $m ) {
      if( empty( $m[ 'match' ]  ) ) {
        continue;
      }
      $_matches = explode( ',', $m[ 'match' ] );
      foreach( $_matches as $match ) {
        $match = trim( $match );
        if( empty( $match ) ) {
          continue;
        }
        if(
          $match == $value ||
          ( in_array( $match, array( '[any]', '[other]' ) ) && !empty( $value ) ) ||
          ( $match == '[empty]' && empty( $value ) )
        ) {
          $matched_values[ $match ] = $m[ 'value' ];
        }
      }
    }

    if( empty( $matched_values ) ) {
      return $value;
    }

    if( count( $matched_values ) == 1 ) {
      return array_shift( array_values( $matched_values ) );
    }

    $matched_values = array_reverse( $matched_values, true );

    $replaced = false;
    foreach( $matched_values as $m => $v ) {
      if( is_numeric( $m ) || !in_array( $m, array( '[any]', '[other]' ) ) ) {
        $value = $v;
        $replaced = true;
        break;
      }
    }

    if( !$replaced ) {
      return array_shift( array_values( $matched_values ) );
    } else {
      return $value;
    }

  }

  /**
   * Keep the MySQL Connection alive (and hope).
   *
   * @since 3.2.1
   * @author potanin@UD
   */
  static public function keep_hope_alive() {
    global $wpdb;
    $wpdb->query( "SELECT 1" );
  }

  /**
   * This function tries to find the schedule from the id or schedule hash
   */
  static public function get_schedule( $to_find ) {
    global $wpp_property_import;
    $schedule_hash = false;
    /** Try to find the proper schedule */
    foreach( $wpp_property_import[ 'schedules' ] as $schedule_id => $schedule ) {
      switch( true ) {
        case( $schedule_id == $to_find ):
          $schedule_hash = $schedule[ 'hash' ];
          break;
        case( isset( $schedule[ 'hash' ] ) && $schedule[ 'hash' ] == $to_find ):
          $schedule_hash = $schedule[ 'hash' ];
          break;
      }
      /** If we have the hash, bail out of the loop */
      if( $schedule_hash ) {
        break;
      }
    }
    /** Ok, if we don't have a schedule hash, return false */
    if( !$schedule_hash ) {
      return false;
    } else {
      return array(
        'schedule_id' => $schedule_id,
        'schedule_hash' => $schedule_hash,
        'schedule' => $schedule
      );
    }
  }

  /**
   * Adds Search Filter on Property Overview page on admin panel
   *
   * @author peshkov@UD
   * @since 5.0.0
   */
  static public function wpp_get_search_filters( $filter ) {
    global $wpdb, $wpp_property_import;

    $schedules = $wpdb->get_col( "
      SELECT distinct meta_value
      FROM {$wpdb->postmeta}
      WHERE meta_key = 'wpp_import_schedule_id'
    " );

    $values = array();
    if(
      !empty( $schedules ) &&
      !empty( $wpp_property_import[ 'schedules' ] ) &&
      is_array( $wpp_property_import[ 'schedules' ] )
    ) {
      foreach( $wpp_property_import[ 'schedules' ] as $k => $data ) {
        if( in_array( $k, $schedules ) ) {
          $values[ $k ] = $data[ 'name' ];
        }
      }
    }

    if ( !empty( $values ) ) {

      /* Add filter by XML Schedules */
      $filter[ 'wpp_import_schedule_id' ] = array (
        'type' => 'multi_checkbox',
        'label' => __( 'Importer',ud_get_wpp_importer()->domain ),
        'values' => $values
      );

      /* Maybe Add filter by Manual/Imported Properties */

      $mtotal = $wpdb->get_var( "
        SELECT count( ID )
          FROM {$wpdb->posts}
          WHERE ID NOT IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wpp_import_schedule_id' )
            AND post_type = 'property'
            AND post_status != 'auto-draft'
      " );

      $itotal = $wpdb->get_var( "
        SELECT count( p.ID )
          FROM {$wpdb->posts} AS p
          LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
          WHERE p.post_type = 'property'
            AND p.post_status != 'auto-draft'
            AND pm.meta_key = 'wpp_import_schedule_id'
      " );

      /* Be sure we have manual AND imported properties. In other case, it does not make sense to show filter. */
      if( !empty( $mtotal ) && !empty( $itotal ) ) {

        $filter[ '_custom_filter_created_type' ] = array(
          'type' => 'multi_checkbox',
          'label' => __( 'Created',ud_get_wpp_importer()->domain ),
          'values' => array(
            'manual' => sprintf( __( 'Manually (%s)', ud_get_wpp_importer()->domain ), number_format( $mtotal ) ),
            'xmli' => sprintf( __( 'Imported via Schedule (%s)', ud_get_wpp_importer()->domain ), number_format( $itotal ) ),
          )
        );

      }

    }

    return $filter;

  }

  /**
   * Adds condition to filter properties by 'created type' ( manual/imported ).
   *
   * @action wpp::get_properties::custom_case
   * @see WPP_F::get_properties()
   *
   * @param bool $bool
   * @param string $key
   * @return bool
   */
  static public function wpp_get_properties_by_custom_case( $bool, $key ) {
    return $key == '_custom_filter_created_type' ? true : $bool;
  }

  /**
   * Adds condition to filter properties by 'created type' ( manual/imported ).
   *
   * @action wpp::get_properties::custom_key
   * @see WPP_F::get_properties()
   *
   * @param array $matching_ids
   * @param string $key Meta Key ( or any other custom key )
   * @param string $criteria Value
   * @return bool
   */
  static public function wpp_get_properties_by_custom_filter( $matching_ids, $key, $criteria ) {
    global $wpdb;

    if( $key == '_custom_filter_created_type' ) {

      switch( $criteria ) {

        case 'manual':
          if( is_array( $matching_ids ) ) {
            if( empty( $matching_ids ) ) break;
            $matching_ids = $wpdb->get_col( "
              SELECT ID
                FROM {$wpdb->posts}
                WHERE ID NOT IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wpp_import_schedule_id' )
                  AND post_type = 'property'
                  AND post_status != 'auto-draft'
                  AND ID IN ( " . implode( ',', $matching_ids ) .  " )
            " );
          } else {
            $matching_ids = $wpdb->get_col( "
              SELECT ID
                FROM {$wpdb->posts}
                WHERE ID NOT IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wpp_import_schedule_id' )
                  AND post_type = 'property'
                  AND post_status != 'auto-draft'
            " );

          }
          break;

        case 'xmli':
          if( is_array( $matching_ids ) ) {
            if( empty( $matching_ids ) ) break;
            $matching_ids = $wpdb->get_col( "
              SELECT p.ID
                FROM {$wpdb->posts} AS p
                LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
                WHERE p.post_type = 'property'
                  AND p.post_status != 'auto-draft'
                  AND p.ID IN ( " . implode( ',', $matching_ids ) .  " )
  	              AND pm.meta_key = 'wpp_import_schedule_id'
            " );
          } else {
            $matching_ids = $wpdb->get_col( "
              SELECT p.ID
                FROM {$wpdb->posts} AS p
                LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.ID
                WHERE p.post_type = 'property'
                  AND p.post_status != 'auto-draft'
  	              AND pm.meta_key = 'wpp_import_schedule_id'
            " );
          }
          break;

      }

    }

    return $matching_ids;

  }

}
