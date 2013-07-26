<?php
/**
Name: Cron for WP Property
Description: Allow cron jobs to be executed from Command Line interface.
@todo Fix so that we're using the correct deletion function.
*/

/** Need to at least have the do_xml_import argument */
if (empty($argv[0])) {
  die("Missing arguments.\r\n"); 
}

ini_set( "display_errors", 0);
set_time_limit(0);  
ignore_user_abort(true);
    
//** Load WP */
$wp_load_path = str_replace('wp-content/plugins/wp-property/cron.php', 'wp-load.php', __FILE__);
  
if(!file_exists($wp_load_path)) {
  die('Cannot load WP using: ' . $wp_load_path);
} else {
  require_once $wp_load_path;
}
  
//** Ensure file was loaded and procesed */
if(ABSPATH && class_exists('class_wpp_property_import')) {
  define('DOING_WPP_CRON', true);
} else {
  die('Unable to load XML Importer.');
}  

$action = $argv[1];
$schedule_hash = $argv[2];

if(empty($action)) {
  die("Missing arguments.\r\n");
}

/** Begin Loading Import*/
if($action == 'do_xml_import' && !empty($schedule_hash)) {   
        
    define('WPP_IMPORTER_HASH', $schedule_hash);

    //class_wpp_property_import::init();
    class_wpp_property_import::run_from_cron_hash();
    
    die();
  
} elseif($action == 'erase_all_properties') {
  global $wpdb;
  $wpdb->show_errors();
  /** First, delete attachments */
  $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_parent IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property') OR post_excerpt = 'qr_code'";
  foreach($wpdb->get_results($sql) as $row) {
    wp_delete_attachment($row->ID, true);
    print "Deleted attachment: ".$row->ID."\r\n";
  }
  /** Now, delete posts */
  $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property'";
  foreach($wpdb->get_results($sql) as $row){
    wp_delete_post($row->ID, true);
    print "Deleted post: ".$row->ID."\r\n";
  }
  die("Done deleting posts.\r\n");
}
else {
  die("Nothing done.\r\n");
}
