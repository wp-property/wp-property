<?php
/**
Name: Cron for WP Property
Description: Allow cron jobs to be executed from Command Line interface.
@todo Fix so that we're using the correct deletion function.
*/
ini_set( "display_errors", 1);
set_time_limit(0);
ignore_user_abort(true);

//** It seams cron service in CPanel works not like normal Linux Cron, so we have to emulate $argv */
$VERBOSE = true;
if (!empty($_REQUEST) && array_key_exists('do_xml_import',$_REQUEST)){
  $argv = array_keys($_REQUEST);
}

/** Need to at least have the do_xml_import argument */
if (empty($argv[0])) {
  if($VERBOSE){ print("Missing arguments.\n"); }
  exit(1);
}

$cron_action = $argv[1];
$schedule_hash = $argv[2];
$ms_url = (!empty($argv[3])) ? $argv[3] : '';

//** Load WP */
$wp_load_path = preg_replace('%wp-content[/\\\\]plugins[/\\\\]wp-property[/\\\\]cron.php%ix', 'wp-load.php', __FILE__);

if(!file_exists($wp_load_path)) {
  if($VERBOSE){ print('Cannot load WP using: ' . $wp_load_path ."\n"); }
  exit(1);
} else {

  /** these argumants should be passed in multisite mode */
  if (!empty($ms_url) ) {

    $site_name = parse_url('http://'.$ms_url,PHP_URL_PATH);
    $site_domain = parse_url('http://'.$ms_url, PHP_URL_HOST);
    /**
    * Construct a fake $_SERVER global to get WordPress to load a specific site.
    * This avoids alot of messing about with switch_to_blog() and all its pitfalls.
    */
    $_SERVER=array(
      'HTTP_HOST'=>$site_domain,
      'REQUEST_METHOD'=>'GET',
      'REQUEST_URI'=>"{$site_name}/",
      'SERVER_NAME'=>$site_domain,
    );

    // Remove all our bespoke variables as they'll be in scope as globals and could affect WordPress
    unset($site_name,$site_domain);

    // Pretend that we're executing an AJAX process. This should help WordPress not load all of the things.
    define('DOING_AJAX',true);

    // Stop WordPress doing any of its normal output handling.
    define('WP_USE_THEMES',false);
  }

  // Load WordPress - intentionally using an absolute URL due to issues with relative paths on the CLI.
  include $wp_load_path;

}

//** Enable/disable debug mode. debug_mode argv must be passed to enable it. */
if( !defined( 'WPP_DEBUG_MODE' ) ) {
  define( 'WPP_DEBUG_MODE', ( !empty( $argv[3] ) && $argv[3] == 'debug_mode' ? true : false ) );
}

//** Ensure file was loaded and procesed */
if(ABSPATH && class_exists('class_wpp_property_import')) {
  define('DOING_WPP_CRON', true);
} else {
  if($VERBOSE){ print('Unable to load XML Importer.' . "\n"); }
  exit(1);
}


if(empty($cron_action)) {
  if($VERBOSE){ print("Missing action argument.\n"); }
  exit(1);
}

/** Begin Loading Import*/
if($cron_action == 'do_xml_import' && !empty($schedule_hash)) {

    define('WPP_IMPORTER_HASH', $schedule_hash);

    //class_wpp_property_import::init();
    class_wpp_property_import::run_from_cron_hash();

    exit(0);

} elseif($cron_action == 'erase_all_properties') {
  global $wpdb;
  $wpdb->show_errors();
  /** First, delete attachments */
  $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_parent IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property') OR post_excerpt = 'qr_code'";
  foreach($wpdb->get_results($sql) as $row) {
    wp_delete_attachment($row->ID, true);
    if($VERBOSE){ print( "Deleted attachment: ".$row->ID."\r\n"); }
  }
  /** Now, delete posts */
  $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property'";
  foreach($wpdb->get_results($sql) as $row){
    wp_delete_post($row->ID, true);
    if($VERBOSE){ print( "Deleted post: ".$row->ID."\r\n"); }
  }
  print( "Done deleting posts.\r\n");
  exit(0);
}
else {
  print( "Nothing done.\r\n");
  exit(0);
}
exit(0);