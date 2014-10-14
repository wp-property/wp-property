<?php
/**
 * WP-Property General Functions
 *
 * Contains all the general functions used by the plugin.
 *
 * @version 1.00
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package WP-Property
 * @subpackage Functions
 */
class WPP_F extends UD_API {

  /**
   * This function grabs the API key from UD's servers
   *
   * @updated 1.36.0
   */
  static public function get_api_key( $args = false ) {

    $args = wp_parse_args( $args, array(
      'force_check' => false
    ) );

    //** check if API key already exists */
    $ud_api_key = get_option( 'ud_api_key' );

    //** if key exists, and we are not focing a check, return what we have */
    if( $ud_api_key && !$args[ 'force_check' ] ) {
      return $ud_api_key;
    }

    $blogname    = get_bloginfo( 'url' );
    $blogname    = urlencode( str_replace( array( 'http://', 'https://' ), '', $blogname ) );
    $system      = 'wpp';
    $wpp_version = get_option( "wpp_version" );

    $check_url = "http://updates.usabilitydynamics.com/key_generator.php?system=$system&site=$blogname&system_version=$wpp_version";

    $response = @wp_remote_get( $check_url );

    if( !$response ) {
      return false;
    }

    // Check for errors
    if( is_wp_error( $response ) ) {
      WPP_F::log( 'API Check Error: ' . $response->get_error_message() );

      return false;
    }

    // Quit if failture
    if( $response[ 'response' ][ 'code' ] != '200' ) {
      return false;
    }

    $response[ 'body' ] = trim( $response[ 'body' ] );

    //** If return is not in MD5 format, it is an error */
    if( strlen( $response[ 'body' ] ) != 40 ) {

      if( $args[ 'return' ] ) {
        return $response[ 'body' ];
      } else {
        WPP_F::log( "API Check Error: " . sprintf( __( 'An error occurred during API key request: <b>%s</b>.', 'wpp' ), $response[ 'body' ] ) );

        return false;
      }
    }

    //** update wpp_key is DB */
    update_option( 'ud_api_key', $response[ 'body' ] );

    // Go ahead and return, it should just be the API key
    return $response[ 'body' ];

  }

  /**
   *  Wrapper for the UD_API::log() function that includes the prefix automatically.
   *
   * @see UD_API::log()
   * @author peshkov@UD
   *
   * @param bool   $message
   * @param string $type
   * @param bool   $object
   * @param array  $args
   *
   * @return boolean
   */
  static public function log( $message = false, $type = 'default', $object = false, $args = array() ) {
    $args = wp_parse_args( (array) $args, array(
      'type'   => $type,
      'object' => $object,
      'instance' => 'WP-Property',
    ) );

    return parent::log( $message, $args );
  }

  /**
   * Get the label for "Property"
   *
   * @since 1.10
   *
   */
  static public function property_label( $type = 'singular' ) {
    global $wp_post_types;

    if( $type == 'plural' ) {
      return ( $wp_post_types[ 'property' ]->labels->name ? $wp_post_types[ 'property' ]->labels->name : __( 'Properties' ) );
    }

    if( $type == 'singular' ) {
      return ( $wp_post_types[ 'property' ]->labels->singular_name ? $wp_post_types[ 'property' ]->labels->singular_name : __( 'Property' ) );
    }

  }

  /**
   * Setup widgets and widget areas.
   *
   * @since 1.31.0
   *
   */
  static public function widgets_init() {
    global $wp_properties;

    /** Loads widgets */
    include_once WPP_Path . 'core/class_widgets.php';

    if( class_exists( 'Property_Attributes_Widget' ) ) {
      register_widget( "Property_Attributes_Widget" );
    }

    if( class_exists( 'ChildPropertiesWidget' ) ) {
      register_widget( "ChildPropertiesWidget" );
    }

    if( class_exists( 'SearchPropertiesWidget' ) ) {
      register_widget( "SearchPropertiesWidget" );
    }

    if( class_exists( 'FeaturedPropertiesWidget' ) ) {
      register_widget( "FeaturedPropertiesWidget" );
    }

    if( class_exists( 'GalleryPropertiesWidget' ) ) {
      register_widget( "GalleryPropertiesWidget" );
    }

    if( class_exists( 'LatestPropertiesWidget' ) ) {
      register_widget( "LatestPropertiesWidget" );
    }

    if( class_exists( 'OtherPropertiesWidget' ) ) {
      register_widget( "OtherPropertiesWidget" );
    }

    //** Register a sidebar for each property type */
    if( 
      !isset( $wp_properties[ 'configuration' ][ 'do_not_register_sidebars' ] ) ||
      ( isset( $wp_properties[ 'configuration' ][ 'do_not_register_sidebars' ] ) && $wp_properties[ 'configuration' ][ 'do_not_register_sidebars' ] != 'true' )
      ) {
      foreach( (array)$wp_properties[ 'property_types' ] as $property_slug => $property_title ) {
        register_sidebar( array(
          'name'          => sprintf( __( 'Property: %s', 'wpp' ), $property_title ),
          'id'            => "wpp_sidebar_{$property_slug}",
          'description'   => sprintf( __( 'Sidebar located on the %s page.', 'wpp' ), $property_title ),
          'before_widget' => '<li id="%1$s"  class="wpp_widget %2$s">',
          'after_widget'  => '</li>',
          'before_title'  => '<h3 class="widget-title">',
          'after_title'   => '</h3>',
        ) );
      }
    }
  }

  /**
   * Registers post types and taxonomies.
   *
   * @since 1.31.0
   *
   */
  static public function register_post_type_and_taxonomies() {
    global $wp_properties;

    // Setup taxonomies
    $wp_properties[ 'taxonomies' ] = apply_filters( 'wpp_taxonomies', array(
      'property_feature'  => array(
        'hierarchical' => false,
        'label'        => _x( 'Features', 'taxonomy general name', 'wpp' ),
        'labels'       => array(
          'name'              => _x( 'Features', 'taxonomy general name', 'wpp' ),
          'singular_name'     => _x( 'Feature', 'taxonomy singular name', 'wpp' ),
          'search_items'      => __( 'Search Features', 'wpp' ),
          'all_items'         => __( 'All Features', 'wpp' ),
          'parent_item'       => __( 'Parent Feature', 'wpp' ),
          'parent_item_colon' => __( 'Parent Feature:', 'wpp' ),
          'edit_item'         => __( 'Edit Feature', 'wpp' ),
          'update_item'       => __( 'Update Feature', 'wpp' ),
          'add_new_item'      => __( 'Add New Feature', 'wpp' ),
          'new_item_name'     => __( 'New Feature Name', 'wpp' ),
          'menu_name'         => __( 'Feature', 'wpp' )
        ),
        'query_var'    => 'property_feature',
        'rewrite'      => array( 'slug' => 'feature' )
      ),
      'community_feature' => array(
        'hierarchical' => false,
        'label'        => _x( 'Community Features', 'taxonomy general name', 'wpp' ),
        'labels'       => array(
          'name'              => _x( 'Community Features', 'taxonomy general name', 'wpp' ),
          'singular_name'     => _x( 'Community Feature', 'taxonomy singular name', 'wpp' ),
          'search_items'      => __( 'Search Community Features', 'wpp' ),
          'all_items'         => __( 'All Community Features', 'wpp' ),
          'parent_item'       => __( 'Parent Community Feature', 'wpp' ),
          'parent_item_colon' => __( 'Parent Community Feature:', 'wpp' ),
          'edit_item'         => __( 'Edit Community Feature', 'wpp' ),
          'update_item'       => __( 'Update Community Feature', 'wpp' ),
          'add_new_item'      => __( 'Add New Community Feature', 'wpp' ),
          'new_item_name'     => __( 'New Community Feature Name', 'wpp' ),
          'menu_name'         => __( 'Community Feature', 'wpp' )
        ),
        'query_var'    => 'community_feature',
        'rewrite'      => array( 'slug' => 'community_feature' )
      )
    ) );

    $wp_properties[ 'labels' ] = apply_filters( 'wpp_object_labels', array(
      'name'               => __( 'Properties', 'wpp' ),
      'all_items'          => __( 'All Properties', 'wpp' ),
      'singular_name'      => __( 'Property', 'wpp' ),
      'add_new'            => __( 'Add Property', 'wpp' ),
      'add_new_item'       => __( 'Add New Property', 'wpp' ),
      'edit_item'          => __( 'Edit Property', 'wpp' ),
      'new_item'           => __( 'New Property', 'wpp' ),
      'view_item'          => __( 'View Property', 'wpp' ),
      'search_items'       => __( 'Search Properties', 'wpp' ),
      'not_found'          => __( 'No properties found', 'wpp' ),
      'not_found_in_trash' => __( 'No properties found in Trash', 'wpp' ),
      'parent_item_colon'  => ''
    ) );
    
    //** Add support for property */
    $supports = array( 'title', 'editor', 'thumbnail' );
    if( isset( $wp_properties[ 'configuration' ][ 'enable_comments' ] ) && $wp_properties[ 'configuration' ][ 'enable_comments' ] == 'true' ) {
      array_push( $supports, 'comments' );
    }

    // Register custom post types
    register_post_type( 'property', array(
      'labels'              => $wp_properties[ 'labels' ],
      'public'              => true,
      'exclude_from_search' => ( isset( $wp_properties[ 'configuration' ][ 'exclude_from_regular_search_results' ] ) && $wp_properties[ 'configuration' ][ 'exclude_from_regular_search_results' ] == 'true' ? true : false ),
      'show_ui'             => true,
      '_edit_link'          => 'post.php?post=%d',
      'capability_type'     => array( 'wpp_property', 'wpp_properties' ),
      'hierarchical'        => true,
      'rewrite'             => array(
        'slug' => $wp_properties[ 'configuration' ][ 'base_slug' ]
      ),
      'query_var'           => $wp_properties[ 'configuration' ][ 'base_slug' ],
      'supports'            => $supports,
      'menu_icon'           => WPP_URL . 'images/pp_menu-1.6.png'
    ) );

    if( $wp_properties[ 'taxonomies' ] ) {
    
      foreach( $wp_properties[ 'taxonomies' ] as $taxonomy => $taxonomy_data ) {

        //** Check if taxonomy is disabled */
        if( isset( $wp_properties[ 'configuration' ][ 'disabled_taxonomies' ] ) &&
          is_array( $wp_properties[ 'configuration' ][ 'disabled_taxonomies' ] ) &&
          in_array( $taxonomy, $wp_properties[ 'configuration' ][ 'disabled_taxonomies' ] )
        ) {
          continue;
        }

        register_taxonomy( $taxonomy, 'property', array(
          'hierarchical' => $taxonomy_data[ 'hierarchical' ],
          'label'        => $taxonomy_data[ 'label' ],
          'labels'       => $taxonomy_data[ 'labels' ],
          'query_var'    => $taxonomy,
          'rewrite'      => array( 'slug' => $taxonomy ),
          'show_ui'      => ( current_user_can( 'manage_wpp_categories' ) ? true : false ),
          'capabilities' => array(
            'manage_terms' => 'manage_wpp_categories',
            'edit_terms'   => 'manage_wpp_categories',
            'delete_terms' => 'manage_wpp_categories',
            'assign_terms' => 'manage_wpp_categories'
          )
        ) );
      }
    }

  }

  /**
   * Loads applicable WP-Properrty scripts and styles
   *
   * @since 1.10
   *
   */
  static public function load_assets( $types = array() ) {
    global $post, $property, $wp_properties;

    add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('jquery-ui-slider');" ) );
    add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('jquery-ui-mouse');" ) );
    add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('jquery-ui-widget');" ) );
    add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('wpp-jquery-fancybox');" ) );
    add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('wpp-jquery-address');" ) );
    add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('wpp-jquery-scrollTo');" ) );
    add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('wp-property-frontend');" ) );
    wp_enqueue_style( 'wpp-jquery-fancybox-css' );
    wp_enqueue_style( 'jquery-ui' );

    foreach( $types as $type ) {

      switch( $type ) {

        case 'single':

          if( !isset( $wp_properties[ 'configuration' ][ 'do_not_use' ][ 'locations' ] ) || $wp_properties[ 'configuration' ][ 'do_not_use' ][ 'locations' ] != 'true' ) {
            add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('google-maps');" ) );
          }

          add_action( 'wp_enqueue_scripts', create_function( '', "wp_enqueue_script('jquery-ui-mouse');" ) );
          break;

        case 'overview':

          break;

      }

    }

  }

  /**
   * Checks if script or style have been loaded.
   *
   * @todo Add handler for styles.
   * @since Denali 3.0
   *
   */
  static public function is_asset_loaded( $handle = false ) {
    global $wp_properties, $wp_scripts;

    if( empty( $handle ) ) {
      return;
    }

    $footer = (array) $wp_scripts->in_footer;
    $done   = (array) $wp_scripts->done;

    $accepted = array_merge( $footer, $done );

    if( !in_array( $handle, $accepted ) ) {
      return false;
    }

    return true;

  }

  /**
   * PHP function to echoing a message to JS console
   *
   * @since 1.32.0
   */
  static public function console_log( $text = false ) {
    global $wp_properties;

    if( !isset( $wp_properties[ 'configuration' ][ 'developer_mode' ] ) || $wp_properties[ 'configuration' ][ 'developer_mode' ] != 'true' ) {
      return false;
    }

    if( empty( $text ) ) {
      return false;
    }

    if( is_array( $text ) || is_object( $text ) ) {
      $text = str_replace( "\n", '', print_r( $text, true ) );
    }

    //** Cannot use quotes */
    $text = str_replace( '"', '-', $text );

    add_filter( 'wp_footer', create_function( '$nothing,$echo_text = "' . $text . '"', 'echo \'<script type="text/javascript">if(typeof console == "object"){console.log("\' . $echo_text . \'");}</script>\'; ' ) );
    add_filter( 'admin_footer', create_function( '$nothing,$echo_text = "' . $text . '"', 'echo \'<script type="text/javascript">if(typeof console == "object"){console.log("\' . $echo_text . \'");}</script>\'; ' ) );

    return true;
  }

  /**
   * Tests if remote script or CSS file can be opened prior to sending it to browser
   *
   *
   * @version 1.26.0
   */
  static public function can_get_script( $url = false, $args = array() ) {
    global $wp_properties;

    if( empty( $url ) ) {
      return false;
    }

    $match = false;

    if( empty( $args ) ) {
      $args[ 'timeout' ] = 10;
    }

    $result = wp_remote_get( $url, $args );
    if( is_wp_error( $result ) ) {
      return false;
    }

    $type = $result[ 'headers' ][ 'content-type' ];

    if( strpos( $type, 'javascript' ) !== false ) {
      $match = true;
    }

    if( strpos( $type, 'css' ) !== false ) {
      $match = true;
    }

    if( !$match || $result[ 'response' ][ 'code' ] != 200 ) {

      if( $wp_properties[ 'configuration' ][ 'developer_mode' ] == 'true' ) {
        WPP_F::console_log( "Remote asset ($url) could not be loaded, content type returned: " . $result[ 'headers' ][ 'content-type' ] );
      }

      return false;
    }

    return true;

  }

  /**
   * Tests if remote image can be loaded, before sending to browser or TCPDF
   *
   * @version 1.26.0
   */
  static public function can_get_image( $url = false ) {
    global $wp_properties;

    if( empty( $url ) ) {
      return false;
    }

    $result = wp_remote_get( $url, array( 'timeout' => 10 ) );

    //** Image content types should always begin with 'image' (I hope) */
    if( ( is_object( $result ) && get_class( $result ) == 'WP_Error' ) || strpos( (string) $result[ 'headers' ][ 'content-type' ], 'image' ) === false ) {
      return false;
    }

    return true;

  }

  /**
   * Remove non-XML characters
   *
   * @version 1.30.2
   */
  static public function strip_invalid_xml( $value ) {

    $ret = "";

    $bad_chars = array( '\u000b' );

    $value = str_replace( $bad_chars, ' ', $value );

    if( empty( $value ) ) {
      return $ret;
    }

    $length = strlen( $value );

    for( $i = 0; $i < $length; $i++ ) {

      $current = ord( $value{$i} );

      if( ( $current == 0x9 ) || ( $current == 0xA ) || ( $current == 0xD ) ||
        ( ( $current >= 0x20 ) && ( $current <= 0xD7FF ) ) ||
        ( ( $current >= 0xE000 ) && ( $current <= 0xFFFD ) ) ||
        ( ( $current >= 0x10000 ) && ( $current <= 0x10FFFF ) )
      ) {

        $ret .= chr( $current );

      } else {
        $ret .= " ";
      }
    }

    return $ret;
  }

  /**
   * Convert JSON data to XML if it is in JSON
   *
   * @version 1.26.0
   */
  static public function json_to_xml( $json, $options = array() ) {

    //** An array of serializer options */
    $options = wp_parse_args( $options, array(
      'indent'         => " ",
      'linebreak'      => "\n",
      'addDecl'        => true,
      'encoding'       => 'ISO-8859-1',
      'rootName'       => 'objects',
      'defaultTagName' => 'object',
      'mode'           => false
    ) );

    if( empty( $json ) ) {
      return false;
    }

    if( !class_exists( 'XML_Serializer' ) ) {
      set_include_path( get_include_path() . PATH_SEPARATOR . WPP_Path . 'third-party/XML/' );
      @require_once 'Serializer.php';
    }

    //** If class still doesn't exist, for whatever reason, we fail */
    if( !class_exists( 'XML_Serializer' ) ) {
      return false;
    }

    $encoding = function_exists( 'mb_detect_encoding' ) ? mb_detect_encoding( $json ) : 'UTF-8';

    if( $encoding == 'UTF-8' ) {
      $json = preg_replace( '/[^(\x20-\x7F)]*/', '', $json );
    }

    $json = WPP_F::strip_invalid_xml( $json );

    $data = json_decode( $json, true );

    //** If could not decode, return false so we presume with XML format */
    if( !is_array( $data ) ) {
      return false;
    }

    $Serializer = new XML_Serializer( $options );

    $status = $Serializer->serialize( $data );

    if( PEAR::isError( $status ) ) {
      return false;
    }

    if( $Serializer->getSerializedData() ) {
      return $Serializer->getSerializedData();
    }

    return false;

  }

  /**
   * Convert CSV to XML
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.32.0
   */
  static public function detect_encoding( $string ) {

    $encoding = array(
      'UTF-8',
      'windows-1251',
      'ISO-8859-1',
      'GBK',
      'ASCII',
      'JIS',
      'EUC-JP',
    );

    if( !function_exists( 'mb_detect_encoding' ) ) {
      return;
    }

    foreach( $encoding as $single ) {
      if( @mb_detect_encoding( $string, $single, true ) ) {
        $matched = $single;
      }
    }

    return $matched ? $matched : new WP_Error( 'encoding_error', __( 'Could not detect.', 'wpp' ) );

  }

  /**
   * Convert CSV to XML
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.32.0
   */
  static public function csv_to_xml( $string, $args = false ) {

    $uploads = wp_upload_dir();

    $defaults = array(
      'delimiter' => ',',
      'enclosure' => '"',
      'escape'    => "\\"
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $temp_file = $uploads[ 'path' ] . time() . '.csv';

    file_put_contents( $temp_file, $string );

    ini_set( "auto_detect_line_endings", 1 );
    $current_row = 1;

    $handle       = fopen( $temp_file, "r" );
    $header_array = array();
    $csv          = array();

    while( ( $data = fgetcsv( $handle, 10000, "," ) ) !== FALSE ) {
      $number_of_fields = count( $data );
      if( $current_row == 1 ) {
        for( $c = 0; $c < $number_of_fields; $c++ ) {
          $header_array[ $c ] = str_ireplace( '-', '_', sanitize_key( $data[ $c ] ) );
        }
      } else {

        $data_array = array();

        for( $c = 0; $c < $number_of_fields; $c++ ) {

          //** Clean up values */
          $value                             = trim( $data[ $c ] );
          $data_array[ $header_array[ $c ] ] = $value;

        }

        /** Removing - this removes empty values from the CSV, we want to leave them to make sure the associative array is consistant for the importer - $data_array = array_filter($data_array); */

        if( !empty( $data_array ) ) {
          $csv[ ] = $data_array;
        }

      }
      $current_row++;
    }

    fclose( $handle );

    unlink( $temp_file );

    //** Get it into XML (We want to use json_to_xml because it does all the cleansing of weird characters) */
    $xml = WPP_F::json_to_xml( json_encode( $csv ) );

    return $xml;

  }

  /**
   * Get filesize of a file.
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.25.0
   */
  static public function get_filesize( $file ) {
    $bytes = filesize( $file );
    $s     = array( 'b', 'Kb', 'Mb', 'Gb' );
    $e     = floor( log( $bytes ) / log( 1024 ) );

    return sprintf( '%.2f ' . $s[ $e ], ( $bytes / pow( 1024, floor( $e ) ) ) );
  }

  /**
   * Set all existing property objects' property type
   *
   * @todo Add regex to check for opening and closing bracket.
   * @version 1.23.1
   */
  static public function mass_set_property_type( $property_type = false ) {
    global $wpdb;

    if( !$property_type ) {
      return false;
    }

    //** Get all properties */
    $ap = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property'" );

    if( !$ap ) {
      return false;
    }

    foreach( $ap as $id ) {

      if( update_post_meta( $id, 'property_type', $property_type ) ) {
        $success[ ] = true;
      }

    }

    if( !$success ) {
      return false;
    }

    return sprintf( __( 'Set %1s properties to "%2s" property type', 'wpp' ), count( $success ), $property_type );

  }

  /**
   * Attempts to detect if current page has a given shortcode
   *
   * @todo Add regex to check for opening and closing bracket.
   * @version 1.23.1
   */
  static public function detect_shortcode( $shortcode = false ) {
    global $post;

    if( !$post ) {
      return false;
    }

    $shortcode = '[' . $shortcode;

    if( strpos( $post->post_content, $shortcode ) !== false ) {
      return true;
    }

    return false;

  }

  /**
   * Reassemble address from parts
   *
   * @version 1.23.0
   */
  static public function reassemble_address( $property_id = false ) {

    if( !$property_id ) {
      return false;
    }

    $address_part[ ] = get_post_meta( $property_id, 'street_number', true );
    $address_part[ ] = get_post_meta( $property_id, 'route', true );
    $address_part[ ] = get_post_meta( $property_id, 'city', true );
    $address_part[ ] = get_post_meta( $property_id, 'state', true );
    $address_part[ ] = get_post_meta( $property_id, 'state_code', true );
    $address_part[ ] = get_post_meta( $property_id, 'country', true );
    $address_part[ ] = get_post_meta( $property_id, 'postal_code', true );

    $maybe_address = trim( implode( ' ', $address_part ) );

    if( !empty( $maybe_address ) ) {
      return $maybe_address;
    }

    return false;

  }

  /**
   * Creates a nonce, similar to wp_create_nonce() but does not depend on user being logged in
   *
   * @version 1.17.3
   */
  static public function generate_nonce( $action = -1 ) {

    $user = wp_get_current_user();

    $uid = (int) $user->ID;

    if( empty( $uid ) ) {
      $uid = $_SERVER[ 'REMOTE_ADDR' ];
    }

    $i = wp_nonce_tick();

    return substr( wp_hash( $i . $action . $uid, 'nonce' ), -12, 10 );

  }

  /**
   * Verifies nonce.
   *
   * @version 1.17.3
   */
  static public function verify_nonce( $nonce, $action = false ) {

    $user = wp_get_current_user();
    $uid  = (int) $user->ID;

    if( empty( $uid ) ) {
      $uid = $_SERVER[ 'REMOTE_ADDR' ];
    }

    $i = wp_nonce_tick();

    // Nonce generated 0-12 hours ago
    if( substr( wp_hash( $i . $action . $uid, 'nonce' ), -12, 10 ) == $nonce )
      return 1;
    // Nonce generated 12-24 hours ago
    if( substr( wp_hash( ( $i - 1 ) . $action . $uid, 'nonce' ), -12, 10 ) == $nonce )
      return 2;

    // Invalid nonce
    return false;

  }

  /**
   * Returns attribute information.
   *
   * Checks $wp_properties and returns a concise array of array-specific settings and attributes
   *
   * @todo Consider putting this into settings action, or somewhere, so it its only ran once, or adding caching
   * @version 1.17.3
   */
  static public function get_attribute_data( $attribute = false ) {
    global $wp_properties;

    if( !$attribute ) {
      return;
    }

    if( wp_cache_get( $attribute, 'wpp_attribute_data' ) ) {
      return wp_cache_get( $attribute, 'wpp_attribute_data' );
    }

    $post_table_keys = array(
      'post_author',
      'post_date',
      'post_date_gmt',
      'post_content',
      'post_title',
      'post_excerpt',
      'post_status',
      'comment_status',
      'ping_status',
      'post_password',
      'post_name',
      'to_ping',
      'pinged',
      'post_modified',
      'post_modified_gmt',
      'post_content_filtered',
      'post_parent',
      'guid',
      'menu_order',
      'post_type',
      'post_mime_type',
      'comment_count' );

    if( !$attribute ) {
      return;
    }

    $ui_class = array( $attribute );

    if( in_array( $attribute, $post_table_keys ) ) {
      $return[ 'storage_type' ] = 'post_table';
    }

    $return[ 'slug' ] = $attribute;

    if( isset( $wp_properties[ 'property_stats' ][ $attribute ] ) ) {
      $return[ 'is_stat' ]      = 'true';
      $return[ 'storage_type' ] = 'meta_key';
      $return[ 'label' ]        = $wp_properties[ 'property_stats' ][ $attribute ];
    }

    if( isset( $wp_properties[ 'property_meta' ][ $attribute ] ) ) {
      $return[ 'is_meta' ]         = 'true';
      $return[ 'storage_type' ]    = 'meta_key';
      $return[ 'label' ]           = $wp_properties[ 'property_meta' ][ $attribute ];
      $return[ 'input_type' ]      = 'textarea';
      $return[ 'data_input_type' ] = 'textarea';
    }

    if( isset( $wp_properties[ 'searchable_attr_fields' ][ $attribute ] ) ) {
      $return[ 'input_type' ] = $wp_properties[ 'searchable_attr_fields' ][ $attribute ];
      $ui_class[ ]            = $return[ 'input_type' ];
    }

    if( isset( $wp_properties[ 'admin_attr_fields' ][ $attribute ] ) ) {
      $return[ 'data_input_type' ] = $wp_properties[ 'admin_attr_fields' ][ $attribute ];
      $ui_class[ ]                 = $return[ 'data_input_type' ];
    }

    if( isset( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && $wp_properties[ 'configuration' ][ 'address_attribute' ] == $attribute ) {
      $return[ 'is_address_attribute' ] = 'true';
      $ui_class[ ]                      = 'address_attribute';
    }

    if( isset( $wp_properties[ 'property_inheritance' ] ) && is_array( $wp_properties[ 'property_inheritance' ] ) ) {
      foreach( $wp_properties[ 'property_inheritance' ] as $property_type => $type_data ) {
        if( in_array( $attribute, $type_data ) ) {
          $return[ 'inheritance' ][ ] = $property_type;
        }
      }
    }

    if( isset( $wp_properties[ 'predefined_values' ][ $attribute ] ) ) {
      $return[ 'predefined_values' ] = $wp_properties[ 'predefined_values' ][ $attribute ];
    }

    if( isset( $wp_properties[ 'predefined_search_values' ][ $attribute ] ) ) {
      $return[ 'predefined_search_values' ] = $wp_properties[ 'predefined_search_values' ][ $attribute ];
    }

    if( isset( $wp_properties[ 'sortable_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'sortable_attributes' ] ) ) {
      $return[ 'sortable' ] = true;
      $ui_class[ ]          = 'sortable';
    }

    if( isset( $wp_properties[ 'hidden_frontend_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'hidden_frontend_attributes' ] ) ) {
      $return[ 'hidden_frontend_attribute' ] = true;
      $ui_class[ ]                           = 'fe_hidden';
    }

    if( isset( $wp_properties[ 'currency_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'currency_attributes' ] ) ) {
      $return[ 'currency' ] = true;
      $ui_class[ ]          = 'currency';
    }

    if( isset( $wp_properties[ 'numeric_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'numeric_attributes' ] ) ) {
      $return[ 'numeric' ] = true;
      $ui_class[ ]         = 'numeric';
    }

    if( isset( $wp_properties[ 'searchable_attributes' ] ) && in_array( $attribute, (array)$wp_properties[ 'searchable_attributes' ] ) ) {
      $return[ 'searchable' ] = true;
      $ui_class[ ]            = 'searchable';
    }

    if( empty( $return[ 'title' ] ) ) {
      $return[ 'title' ] = WPP_F::de_slug( $return[ 'slug' ] );
    }

    $return[ 'ui_class' ] = implode( ' wpp_', $ui_class );

    $return = apply_filters( 'wpp_attribute_data', $return );

    wp_cache_add( $attribute, $return, 'wpp_attribute_data' );

    return $return;

  }

  /**
   * Makes sure the script is loaded, otherwise loads it
   *
   * @version 1.17.3
   */
  static public function force_script_inclusion( $handle = false ) {
    global $wp_scripts;

    //** WP 3.3+ allows inline wp_enqueue_script(). Yay. */
    wp_enqueue_script( $handle );

    if( !$handle ) {
      return;
    }

    //** Check if already included */
    if( wp_script_is( $handle, 'done' ) ) {
      return true;
    }

    //** Check if script has dependancies that have not been loaded */
    if( is_array( $wp_scripts->registered[ $handle ]->deps ) ) {
      foreach( $wp_scripts->registered[ $handle ]->deps as $dep_handle ) {
        if( !wp_script_is( $dep_handle, 'done' ) ) {
          $wp_scripts->in_footer[ ] = $dep_handle;
        }
      }
    }
    //** Force script into footer */
    $wp_scripts->in_footer[ ] = $handle;
  }

  /**
   * Makes sure the style is loaded, otherwise loads it
   *
   * @param bool|string $handle registered style's name
   *
   * @return bool
   * @author Maxim Peshkov
   */
  static public function force_style_inclusion( $handle = false ) {
    global $wp_styles;
    static $printed_styles = array();

    if( !$handle ) {
      return;
    }

    wp_enqueue_style( $handle );

    //** Check if already included */
    if( wp_style_is( $handle, 'done' ) || isset( $printed_styles[ $handle ] ) ) {
      return true;
    } elseif( headers_sent() ) {
      $printed_styles[ $handle ] = true;
      wp_print_styles( $handle );
    } else {
      return false;
    }

  }

  /**
   * Returns an array of all keys that can be queried using property_overview
   *
   * @version 1.17.3
   */
  static public function get_queryable_keys() {
    global $wp_properties;

    $keys = array_keys( (array) $wp_properties[ 'property_stats' ] );

    foreach( $wp_properties[ 'searchable_attributes' ] as $attr ) {
      if( !in_array( $attr, $keys ) ) {
        $keys[ ] = $attr;
      }
    }

    $keys[ ] = 'id';
    $keys[ ] = 'property_id';
    $keys[ ] = 'post_id';
    $keys[ ] = 'post_author';
    $keys[ ] = 'post_title';
    $keys[ ] = 'post_date';
    $keys[ ] = 'post_parent';
    $keys[ ] = 'property_type';
    $keys[ ] = 'featured';

    //* Adds filter for ability to apply custom queryable keys */
    $keys = apply_filters( 'get_queryable_keys', $keys );

    return $keys;
  }

  /**
   * Returns array of sortable attributes if set, or default
   *
   * @version 1.17.2
   */
  static public function get_sortable_keys() {
    global $wp_properties;

    $sortable_attrs = array();

    if( isset( $wp_properties[ 'configuration' ][ 'property_overview' ][ 'add_sort_by_title' ] ) && $wp_properties[ 'configuration' ][ 'property_overview' ][ 'add_sort_by_title' ] != 'false' ) {
      $sortable_attrs[ 'post_title' ] = __( 'Title', 'wpp' );
    }

    if( !empty( $wp_properties[ 'property_stats' ] ) && !empty( $wp_properties[ 'sortable_attributes' ] ) ) {
      foreach( (array)$wp_properties[ 'property_stats' ] as $slug => $label ) {
        if( in_array( $slug, (array)$wp_properties[ 'sortable_attributes' ] ) ) {
          $sortable_attrs[ $slug ] = $label;
        }
      }
    }

    //* If not set, menu_order will not be used at all if any of the attributes are marked as searchable */
    if( empty( $sortable_attrs ) ) {
      $sortable_attrs[ 'menu_order' ] = __( 'Default', 'wpp' );
    }

    $sortable_attrs = apply_filters( 'wpp::get_sortable_keys', $sortable_attrs );
    
    return $sortable_attrs;
  }

  /**
   * Pre post query - for now mostly to disable caching
   *
   * Called in &get_posts() in query.php
   *
   * @todo This function is a hack. Need to use post_type rewrites better. - potanin@UD
   *
   * @version 1.26.0
   */
  static public function posts_results( $posts ) {
    global $wpdb, $wp_query;

    //** Look for child properties */
    if( !empty( $wp_query->query_vars[ 'attachment' ] ) ) {
      $post_name = $wp_query->query_vars[ 'attachment' ];

      if( $child = $wpdb->get_row( "SELECT * FROM {$wpdb->posts} WHERE post_name = '{$post_name}' AND post_type = 'property' AND post_parent != '' LIMIT 0, 1" ) ) {
        $posts[ 0 ] = $child;

        return $posts;
      }
    }

    //** Look for regular pages that are placed under base slug */
    if( 
      isset( $wp_query->query_vars[ 'post_type' ] )
      && $wp_query->query_vars[ 'post_type' ] == 'property' 
      && count( $wpdb->get_row( "SELECT * FROM {$wpdb->posts} WHERE post_name = '{$wp_query->query_vars['name']}' AND post_type = 'property'  LIMIT 0, 1" ) ) == 0 
    ) {
      $posts[] = $wpdb->get_row( "SELECT * FROM {$wpdb->posts} WHERE post_name = '{$wp_query->query_vars['name']}' AND post_type = 'page'  LIMIT 0, 1" );
    }

    return $posts;
  }

  /**
   * Pre post query - for now mostly to disable caching
   *
   * @version 1.17.2
   */
  static public function pre_get_posts( $query ) {
    global $wp_properties;

    if( !isset( $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] ) || $wp_properties[ 'configuration' ][ 'disable_wordpress_postmeta_cache' ] != 'true' ) {
      return;
    }

    if( isset( $query->query_vars[ 'post_type' ] ) && $query->query_vars[ 'post_type' ] == 'property' ) {
      $query->query_vars[ 'cache_results' ] = false;
    }

  }

  /**
   * Format a number as numeric
   *
   * @version 1.16.3
   */
  static public function format_numeric( $content = '' ) {
    global $wp_properties;

    $content = trim( $content );

    $dec_point     = ( !empty( $wp_properties[ 'configuration' ][ 'dec_point' ] ) ? $wp_properties[ 'configuration' ][ 'dec_point' ] : "." );
    $thousands_sep = ( !empty( $wp_properties[ 'configuration' ][ 'thousands_sep' ] ) ? $wp_properties[ 'configuration' ][ 'thousands_sep' ] : "," );

    if( is_numeric( $content ) ) {
      $decimals = self::is_decimal( $content ) ? 2 : 0;
      $content  = number_format( $content, $decimals, $dec_point, $thousands_sep );
    }

    return $content;
  }

  /**
   * Determine if variable is decimal
   *
   * @param mixed $val
   *
   * @return bool
   * @author peshkov@UD
   */
  static public function is_decimal( $val ) {
    return is_numeric( $val ) && floor( $val ) != $val;
  }

  /**
   * Checks if an file exists in the uploads directory from a URL
   *
   * Only works for files in uploads folder.
   *
   * @todo update to handle images outside the uploads folder
   *
   * @version 1.16.3
   */
  static public function file_in_uploads_exists_by_url( $image_url = '' ) {

    if( empty( $image_url ) ) {
      return false;
    }

    $upload_dir = wp_upload_dir();
    $image_path = str_replace( $upload_dir[ 'baseurl' ], $upload_dir[ 'basedir' ], $image_url );

    if( file_exists( $image_path ) ) {
      return true;
    }

    return false;

  }

  /**
   * Setup default property page.
   *
   *
   * @version 1.16.3
   */
  static public function setup_default_property_page() {
    global $wpdb, $wp_properties, $user_ID;

    $base_slug = $wp_properties[ 'configuration' ][ 'base_slug' ];

    //** Check if this page actually exists */
    $post_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_name = '{$base_slug}'" );

    if( $post_id ) {
      //** Page already exists */
      return $post_id;
    }

    //** Check if page with this post name already exists */
    if( $post_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_name = 'properties'" ) ) {
      return array(
        'post_id'   => $post_id,
        'post_name' => 'properties'
      );
    }

    $property_page = array(
      'post_title'   => __( 'Properties', 'wpp' ),
      'post_content' => '[property_overview]',
      'post_name'    => 'properties',
      'post_type'    => 'page',
      'post_status'  => 'publish',
      'post_author'  => $user_ID
    );

    $post_id = wp_insert_post( $property_page );

    if( !is_wp_error( $post_id ) ) {
      //** get post_name of new page */
      $post_name = $wpdb->get_var( "SELECT post_name FROM {$wpdb->posts} WHERE ID = '{$post_id}'" );

      return array(
        'post_id'   => $post_id,
        'post_name' => $post_name
      );

    }

    return false;

  }

  /**
   * Perform WPP related things when a post is being deleted
   *
   * Makes sure all attached files and images get deleted.
   *
   *
   * @version 1.16.1
   */
  static public function before_delete_post( $post_id ) {
    global $wpdb, $wp_properties;

    if( $wp_properties[ 'configuration' ][ 'auto_delete_attachments' ] != 'true' ) {
      return;
    }

    //* Make sure this is a property */
    $is_property = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE ID = {$post_id} AND post_type = 'property'" );

    if( !$is_property ) {
      return;
    }

    $uploads = wp_upload_dir();

    //* Get Attachments */
    $attachments = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_parent = {$post_id} AND post_type = 'attachment' " );

    if( $attachments ) {
      foreach( $attachments as $attachment_id ) {

        $file_path = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = {$attachment_id} AND meta_key = '_wp_attached_file' " );

        wp_delete_attachment( $attachment_id, true );

        if( $file_path ) {
          $attachment_directories[ ] = $uploads[ 'basedir' ] . '/' . dirname( $file_path );
        }

      }
    }

    if( isset( $attachment_directories ) && is_array( $attachment_directories ) ) {
      $attachment_directories = array_unique( $attachment_directories );
      foreach( $attachment_directories as $dir ) {
        @rmdir( $dir );
      }
    }

  }

  /**
   * Get advanced details about an image (mostly for troubleshooting)
   *
   * @todo add some sort of light validating that the the passed item here is in fact an image
   *
   */
  static public function get_property_image_data( $requested_id ) {
    global $wpdb;

    if( empty( $requested_id ) ) {
      return false;
    }

    ob_start();

    if( is_numeric( $requested_id ) ) {

      $post_type = $wpdb->get_var( "SELECT post_type FROM {$wpdb->posts} WHERE ID = '$requested_id'" );
    } else {
      //** Try and image search */
      $image_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '%{$requested_id}%' " );

      if( $image_id ) {
        $post_type    = 'image';
        $requested_id = $image_id;
      }
    }

    if( $post_type == 'property' ) {

      //** Get Property Images */
      $property = WPP_F::get_property( $requested_id );

      echo 'Requested Property: ' . $property[ 'post_title' ];
      $data = get_children( array( 'post_parent' => $requested_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC' ) );
      echo "\nProperty has: " . count( $data ) . ' images.';

      foreach( $data as $img ) {
        $image_data[ 'ID' ]         = $img->ID;
        $image_data[ 'post_title' ] = $img->post_title;

        $img_meta = $wpdb->get_results( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = '{$img->ID}'" );

        foreach( $img_meta as $i_m ) {
          $image_data[ $i_m->meta_key ] = maybe_unserialize( $i_m->meta_value );
        }
        print_r( $image_data );

      }

    } else {

      $data       = $wpdb->get_row( "SELECT * FROM {$wpdb->posts} WHERE ID = '$requested_id'" );
      $image_meta = $wpdb->get_results( "SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = '$requested_id'" );
      foreach( $image_meta as $m_data ) {

        print_r( $m_data->meta_id );
        echo "<br />";
        print_r( $m_data->meta_key );
        echo "<br />";
        print_r( maybe_unserialize( $m_data->meta_value ) );
      }

    }

    $return_data = ob_get_contents();
    ob_end_clean();

    return $return_data;

  }

  /**
   * Resizes (generate) image.
   *
   * @todo add some sort of light validating that the the passed item here is in fact an image
   *
   * If image has no meta data (for instance, if imported via XML Importer), this function
   * what _wp_attachment_metadata the wp_generate_attachment_metadata() function would ideally regenerate.
   *
   * @todo Update so when multiple images are passed the first requested image data is returned
   *
   * @param integer(string) $attachment_id
   * @param array           $sizes . Arrays with sizes, or single name, later converted into array
   *
   * @return array. Image data for first image size (if multiple provided). Or FALSE if file could not be generated.
   * @since 1.6
   */
  static public function generate_image( $attachment_id, $sizes = array() ) {
    global $_wp_additional_image_sizes;

    // Determine if params are empty
    if( empty( $attachment_id ) || empty( $sizes ) ) {
      return false;
    }

    if( !is_array( $sizes ) ) {
      $sizes = array( $sizes );
    }

    // Check if image file exists
    $file = get_attached_file( $attachment_id );
    if( empty( $file ) ) {
      return false;
    }

    //** Get attachment metadata */
    $metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

    if( empty( $metadata ) ) {

      include_once ABSPATH . 'wp-admin/includes/image.php';

      /*
        If image has been imported via XML it may not have meta data
        Here we attempt tp replicate wp_generate_attachment_metadata() but only generate the
        minimum requirements for image meta data and we do not create ALL variations of image, just the requested.
      */

      $metadata             = array();
      $imagesize            = @getimagesize( $file );
      $metadata[ 'width' ]  = $imagesize[ 0 ];
      $metadata[ 'height' ] = $imagesize[ 1 ];

      // Make the file path relative to the upload dir
      $metadata[ 'file' ] = _wp_relative_upload_path( $file );

      if( $image_meta = wp_read_image_metadata( $file ) ) {
        $metadata[ 'image_meta' ] = $image_meta;
      }

    }

    //** Get width, height and crop for new image */
    foreach( $sizes as $size ) {
      if( isset( $_wp_additional_image_sizes[ $size ][ 'width' ] ) ) {
        $width = intval( $_wp_additional_image_sizes[ $size ][ 'width' ] ); // For theme-added sizes
      } else {
        $width = get_option( "{$size}_size_w" ); // For default sizes set in options
      }
      if( isset( $_wp_additional_image_sizes[ $size ][ 'height' ] ) ) {
        $height = intval( $_wp_additional_image_sizes[ $size ][ 'height' ] ); // For theme-added sizes
      } else {
        $height = get_option( "{$size}_size_h" ); // For default sizes set in options
      }
      if( isset( $_wp_additional_image_sizes[ $size ][ 'crop' ] ) ) {
        $crop = intval( $_wp_additional_image_sizes[ $size ][ 'crop' ] ); // For theme-added sizes
      } else {
        $crop = get_option( "{$size}_crop" ); // For default sizes set in options
      }

      //** Try to generate file and update attachment data */
      $resized[ $size ] = image_make_intermediate_size( $file, $width, $height, $crop );

    }

    if( empty( $resized[ $size ] ) ) {
      return false;
    }

    //** Cycle through resized and remove any blanks (would happen if image already exists)  */
    foreach( $resized as $key => $size_info ) {
      if( empty( $size_info ) ) {
        unset( $resized[ $key ] );
      }
    }

    if( !empty( $resized ) ) {

      foreach( $resized as $size => $resize ) {
        $metadata[ 'sizes' ][ $size ] = $resize;
      }

      update_post_meta( $attachment_id, '_wp_attachment_metadata', $metadata );

      //** Return first requested image **/

      return $resized;

    }

    return false;
  }

  /**
   * Check if theme-specific stylesheet exists.
   *
   * get_option('template') seems better choice than get_option('stylesheet'), which returns the current theme's slug
   * which is a problem when a child theme is used. We want the parent theme's slug.
   *
   * @since 1.6
   *
   */
  static public function has_theme_specific_stylesheet() {

    $theme_slug = get_option( 'template' );

    if( file_exists( WPP_Templates . "/theme-specific/{$theme_slug}.css" ) ) {
      return true;
    }

    return false;

  }

  /**
   * Check permissions and ownership of premium folder.
   *
   * @since 1.13
   *
   */
  static public function check_premium_folder_permissions() {
    global $wp_messages;

    // If folder is writable, it's all good
    if( !is_writable( WPP_Premium . "/" ) )
      $writable_issue = true;
    else
      return;

    // If not writable, check if this is an ownerhsip issue
    if( function_exists( 'posix_getuid' ) ) {
      if( fileowner( WPP_Path ) != posix_getuid() )
        $ownership_issue = true;
    } else {
      if( $writable_issue )
        $wp_messages[ 'error' ][ ] = __( 'If you have problems automatically downloading premium features, it may be due to PHP not having ownership issues over the premium feature folder.', 'wpp' );
    }

    // Attempt to take ownership -> most likely will not work
    if( $ownership_issue ) {
      if( @chown( WPP_Premium, posix_getuid() ) ) {
        //$wp_messages['error'][] = __('Succesfully took permission over premium folder.','wpp');
        return;
      } else {
        $wp_messages[ 'error' ][ ] = __( 'There is an ownership issue with the premium folder, which means your site cannot download WP-Property premium features and receive updates.  Please contact your host to fix this - PHP needs ownership over the <b>wp-content/plugins/wp-property/core/premium</b> folder.  Be advised: changing the file permissions will not fix this.', 'wpp' );
      }

    }

    if( !$ownership_issue && $writable_issue )
      $wp_messages[ 'error' ][ ] = __( 'One of the folders that is necessary for downloading additional features for the WP-Property plugin is not writable.  This means features cannot be downloaded.  To fix this, you need to set the <b>wp-content/plugins/wp-property/core/premium</b> permissions to 0755.', 'wpp' );

    if( $wp_messages )
      return $wp_messages;

    return false;

  }

  /**
   * Revalidate all addresses
   *
   * Revalidates addresses of all publishd properties.
   * If Google daily addres lookup is exceeded, breaks the function and notifies the user.
   *
   * @since 1.05
   *
   */
  static public function revalidate_all_addresses( $args = '' ) {
    global $wp_properties, $wpdb;

    set_time_limit( 0 );
    ob_start();

    $args = wp_parse_args( $args, array(
      'property_ids'      => false,
      'echo_result'       => 'true',
      'skip_existing'     => 'false',
      'return_geo_data'   => false,
      'attempt'           => 1,
      'max_attempts'      => 7,
      'delay'             => 0, //Delay validation in seconds
      'increase_delay_by' => 0.25
    ) );

    extract( $args, EXTR_SKIP );
    $delay             = isset( $delay ) ? $delay : 0;
    $attempt           = isset( $attempt ) ? $attempt : 1;
    $max_attempts      = isset( $max_attempts ) ? $max_attempts : 10;
    $increase_delay_by = isset( $increase_delay_by ) ? $increase_delay_by : 0.25;
    $echo_result       = isset( $echo_result ) ? $echo_result : 'true';
    $skip_existing     = isset( $skip_existing ) ? $skip_existing : 'false';
    $return_geo_data   = isset( $return_geo_data ) ? $return_geo_data : false;
    if( is_array( $args[ 'property_ids' ] ) ) {
      $all_properties = $args[ 'property_ids' ];
    } else {
      $all_properties = $wpdb->get_col( "
        SELECT ID FROM {$wpdb->posts} p
        left outer join {$wpdb->postmeta} pm on (pm.post_id=p.ID and pm.meta_key='wpp::last_address_validation' )
        WHERE p.post_type = 'property' AND p.post_status = 'publish'
        ORDER by pm.meta_value DESC
      " );
    }

    $return[ 'updated' ] = $return[ 'failed' ] = $return[ 'over_query_limit' ] = $return[ 'over_query_limit' ] = array();

    $google_map_localizations = WPP_F::draw_localization_dropdown( 'return_array=true' );

    foreach( (array) $all_properties as $post_id ) {
      if( $delay ) {
        sleep( $delay );
      }

      $result = WPP_F::revalidate_address( $post_id, array( 'skip_existing' => $skip_existing, 'return_geo_data' => $return_geo_data ) );

      $return[ $result[ 'status' ] ][ ] = $post_id;

      if( $return_geo_data ) {
        $return[ 'geo_data' ][ $post_id ] = $result[ 'geo_data' ];
      }

    }

    $return[ 'attempt' ] = $attempt;
    if ( !empty( $return[ 'over_query_limit' ] ) && $max_attempts >= $attempt && $delay < 2 ) {

      $_args = array(
        'property_ids' => $return[ 'over_query_limit' ],
        'echo_result' => false,
        'attempt' => $attempt + 1,
        'delay' => $delay + $increase_delay_by,
      ) + $args;

      $rerevalidate_result = self::revalidate_all_addresses( $_args );

      $return[ 'updated' ]          = array_merge( (array) $return[ 'updated' ], (array) $rerevalidate_result[ 'updated' ] );
      $return[ 'failed' ]           = array_merge( (array) $return[ 'failed' ], (array) $rerevalidate_result[ 'failed' ] );
      $return[ 'over_query_limit' ] = $rerevalidate_result[ 'over_query_limit' ];

      $return[ 'attempt' ] = $rerevalidate_result[ 'attempt' ];
    }

    foreach( array( 'updated', 'over_query_limit', 'failed', 'empty_address' ) as $status ) {
      $return[ $status ] = ( $echo_result == 'true' ) ? count( array_unique( (array) $return[ $status ] ) ) : array_unique( (array) $return[ $status ] );
    }

    $return[ 'success' ] = 'true';
    $return[ 'message' ] = sprintf( __( 'Updated %1$d %2$s using the %3$s localization.', 'wpp' ), ( $echo_result == 'true' ) ? $return[ 'updated' ] : count( $return[ 'updated' ] ), WPP_F::property_label( 'plural' ), $google_map_localizations[ $wp_properties[ 'configuration' ][ 'google_maps_localization' ] ] );

    if( $return[ 'empty_address' ] ) {
      $return[ 'message' ] .= "<br />" . sprintf( __( '%1$d %2$s has empty address.', 'wpp' ), ( $echo_result == 'true' ) ? $return[ 'empty_address' ] : count( $return[ 'empty_address' ] ), WPP_F::property_label( 'plural' ) );
    }

    if( $return[ 'failed' ] ) {
      $return[ 'message' ] .= "<br />" . sprintf( __( '%1$d %2$s could not be updated.', 'wpp' ), ( $echo_result == 'true' ) ? $return[ 'failed' ] : count( $return[ 'failed' ] ), WPP_F::property_label( 'plural' ) );
    }

    if( $return[ 'over_query_limit' ] ) {
      $return[ 'message' ] .= "<br />" . sprintf( __( '%1$d %2$s was ignored because query limit was exceeded.', 'wpp' ), ( $echo_result == 'true' ) ? $return[ 'over_query_limit' ] : count( $return[ 'over_query_limit' ] ), WPP_F::property_label( 'plural' ) );
    }

    //** Warning Silincer */
    ob_end_clean();

    if( $echo_result == 'true' ) {
      die( json_encode( $return ) );
    } else {
      return $return;
    }

  }

  /**
   * Address validation function
   *
   * Since 1.37.2 extracted from save_property and revalidate_all_addresses to make same functionality
   *
   * @global array  $wp_properties
   *
   * @param integer $post_id
   * @param array   $args
   *
   * @return array
   * @since 1.37.2
   * @author odokienko@UD
   */
  static public function revalidate_address( $post_id, $args = array() ) {
    global $wp_properties;

    $args = wp_parse_args( $args, array(
      'skip_existing'   => 'false',
      'return_geo_data' => false,
    ) );

    extract( $args, EXTR_SKIP );
    $skip_existing   = isset( $skip_existing ) ? $skip_existing : 'false';
    $return_geo_data = isset( $return_geo_data ) ? $return_geo_data : false;

    $return = array();

    $geo_data             = false;
    $geo_data_coordinates = false;
    $latitude             = get_post_meta( $post_id, 'latitude', true );
    $longitude            = get_post_meta( $post_id, 'longitude', true );
    $current_coordinates  = $latitude . $longitude;
    $address_is_formatted = get_post_meta( $post_id, 'address_is_formatted', true );

    $address = get_post_meta( $post_id, $wp_properties[ 'configuration' ][ 'address_attribute' ], true );

    $coordinates = ( empty( $latitude ) || empty( $longitude ) ) ? "" : array( 'lat' => get_post_meta( $post_id, 'latitude', true ), 'lng' => get_post_meta( $post_id, 'longitude', true ) );

    if( $skip_existing == 'true' && !empty( $current_coordinates ) && in_array( $address_is_formatted, array( '1', 'true' ) ) ) {
      $return[ 'status' ] = 'skipped';

      return $return;
    }

    if( !( empty( $coordinates ) && empty( $address ) ) ) {

      /* will be true if address is empty and used manual_coordinates and coordinates is not empty */
      $manual_coordinates = get_post_meta( $post_id, 'manual_coordinates', true );
      $manual_coordinates = ( $manual_coordinates != 'true' && $manual_coordinates != '1' ) ? false : true;

      $address_by_coordinates = !empty( $coordinates ) && $manual_coordinates && empty( $address );

      if( !empty( $address ) ) {
        $geo_data = WPP_F::geo_locate_address( $address, $wp_properties[ 'configuration' ][ 'google_maps_localization' ], true );
      }

      if( !empty( $coordinates ) && $manual_coordinates ) {
        $geo_data_coordinates = WPP_F::geo_locate_address( $address, $wp_properties[ 'configuration' ][ 'google_maps_localization' ], true, $coordinates );
      }

      /** if Address was invalid or empty but we have valid $coordinates we use them */
      if( !empty( $geo_data_coordinates->formatted_address ) && ( $address_by_coordinates || empty( $geo_data->formatted_address ) ) ) {
        $geo_data = $geo_data_coordinates;
        /** clean up $address to remember that addres was empty or invalid*/
        $address = '';
      }

      if( empty( $geo_data ) ) {
        $return[ 'status' ] = 'empty_address';
      }

    }

    if( !empty( $geo_data->formatted_address ) ) {

      foreach( (array) $wp_properties[ 'geo_type_attributes' ] + array( 'display_address' ) as $meta_key ) {
        delete_post_meta( $post_id, $meta_key );
      }

      update_post_meta( $post_id, 'address_is_formatted', true );

      if( !empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) && ( !$manual_coordinates || $address_by_coordinates ) ) {
        update_post_meta( $post_id, $wp_properties[ 'configuration' ][ 'address_attribute' ], $geo_data->formatted_address );
      }

      foreach( $geo_data as $geo_type => $this_data ) {
        if( in_array( $geo_type, (array) $wp_properties[ 'geo_type_attributes' ] ) && !in_array( $geo_type, array( 'latitude', 'longitude' ) ) ) {
          update_post_meta( $post_id, $geo_type, $this_data );
        }
      }

      update_post_meta( $post_id, 'wpp::last_address_validation', time() );

      update_post_meta( $post_id, 'latitude', $manual_coordinates ? $coordinates[ 'lat' ] : $geo_data->latitude );
      update_post_meta( $post_id, 'longitude', $manual_coordinates ? $coordinates[ 'lng' ] : $geo_data->longitude );

      if( $return_geo_data ) {
        $return[ 'geo_data' ] = $geo_data;
      }

      $return[ 'status' ] = 'updated';

    }

    //** Logs the last validation status for better troubleshooting */
    update_post_meta( $post_id, 'wpp::google_validation_status', ( isset( $geo_data->status ) ? $geo_data->status : 'success' ) );

    // Try to figure out what went wrong
    if( !empty( $geo_data->status ) && ( $geo_data->status == 'OVER_QUERY_LIMIT' || $geo_data->status == 'REQUEST_DENIED' ) ) {
      $return[ 'status' ] = 'over_query_limit';
    } elseif( empty( $address ) && empty( $geo_data ) ) {

      foreach( (array) $wp_properties[ 'geo_type_attributes' ] + array( 'display_address' ) as $meta_key ) {
        delete_post_meta( $post_id, $meta_key );
      }

      $return[ 'status' ] = 'empty_address';
      update_post_meta( $post_id, 'address_is_formatted', false );
    } elseif( empty( $return[ 'status' ] ) ) {
      $return[ 'status' ] = 'failed';
      update_post_meta( $post_id, 'address_is_formatted', false );
    }

    //** Neccessary meta data which is required by Supermap Premium Feature. Should be always set even the Supermap disabled. peshkov@UD */
    if( !metadata_exists( 'post', $post_id, 'exclude_from_supermap' ) ) {
      add_post_meta( $post_id, 'exclude_from_supermap', 'false' );
    }

    return $return;
  }

  /**
   * Minify JavaScript
   *
   * Uses third-party JSMin if class isn't declared.
   * If WP3 is detected, class not loaded to avoid footer warning error.
   * If for some reason W3_Plugin is active, but JSMin is not found,
   * we load ours to avoid breaking property maps.
   *
   * @since 1.06
   */
  static public function minify_js( $data ) {

    if( !class_exists( 'W3_Plugin' ) ) {
      include_once WPP_Path . 'third-party/jsmin.php';
    } elseif( file_exists( WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php' ) ) {
      include_once WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php';
    } else {
      include_once WPP_Path . 'third-party/jsmin.php';
    }

    if( class_exists( 'JSMin' ) ) {
      try {
        $data = JSMin::minify( $data );
      } catch( Exception $e ) {
        return $data;
      }
    }

    return $data;

  }

  /**
   * Minify CSS
   *
   * Syntax:
   * string CssMin::minify(string $source [, array $filters = array()][, array $plugins = array()]);
   *
   * string $source
   * The source css as string.
   * array $filters
   * The filter configuration as array (optional). See Filter Configuration
   * array $plugins
   * The plugin configuration as array (optional). See: Plugin Configuration
   * Example
   * //Simple minification WITHOUT filter or plugin configuration
   * $result = CssMin::minify(file_get_contents("path/to/source.css"));
   * //Minification WITH filter or plugin configuration
   * $filters = array();
   * $plugins = array();
   * // Minify via CssMin adapter function
   * $result = CssMin::minify(file_get_contents("path/to/source.css"), $filters, $plugins);
   * // Minify via CssMinifier class
   * $minifier = new CssMinifier(file_get_contents("path/to/source.css"), $filters, $plugins);
   * $result = $minifier->getMinified();
   *
   * @since 1.37.3.2
   * @author odokienko@UD
   */
  static public function minify_css( $data ) {

    include_once WPP_Path . 'third-party/cssmin.php';

    if( class_exists( 'CssMin' ) ) {
      try {
        $minified_data = CssMin::minify( $data );

        return $minified_data;
      } catch( Exception $e ) {
        return $data;
      }
    }

    return $data;

  }

  /**
   * Gets image dimensions for WP-Property images.
   *
   * This function is no longer used, only here for legacy support.
   *
   * @since 1.0
   *
   */
  static public function get_image_dimensions( $type = false ) {
    return WPP_F::image_sizes( $type );
  }

  /**
   * Prevents all columns on the overview page from being enabled if nothing is configured
   *
   *
   * @since 0.721
   *
   */
  static public function fix_screen_options() {
    global $current_user;

    $user_id = $current_user->data->ID;

    $current = get_user_meta( $user_id, 'manageedit-propertycolumnshidden', true );

    $default_hidden[ ] = 'type';
    $default_hidden[ ] = 'price';
    $default_hidden[ ] = 'bedrooms';
    $default_hidden[ ] = 'bathrooms';
    $default_hidden[ ] = 'deposit';
    $default_hidden[ ] = 'area';
    $default_hidden[ ] = 'phone_number';
    $default_hidden[ ] = 'purchase_price';
    $default_hidden[ ] = 'for_sale';
    $default_hidden[ ] = 'for_rent';
    $default_hidden[ ] = 'city';
    $default_hidden[ ] = 'featured';
    $default_hidden[ ] = 'menu_order';

    if( empty( $current ) ) {
      update_user_meta( $user_id, 'manageedit-propertycolumnshidden', $default_hidden );
    }

  }

  /**
   * Determines most common property type (used for defaults when needed)
   *
   *
   * @since 0.55
   *
   */
  static public function get_most_common_property_type( $array = false ) {
    global $wpdb, $wp_properties;

    $type_slugs = array_keys( (array) $wp_properties[ 'property_types' ] );

    $top_property_type = $wpdb->get_col( "
      SELECT DISTINCT(meta_value)
      FROM {$wpdb->postmeta}
      WHERE meta_key = 'property_type'
      GROUP BY meta_value
      ORDER BY  count(meta_value) DESC
    " );

    if( is_array( $top_property_type ) ) {
      foreach( $top_property_type as $slug ) {
        if( isset( $wp_properties[ 'property_types' ][ $slug ] ) ) {
          return $slug;
        }
      }
    }

    //* No DB entries, return first property type in settings */
    return $type_slugs[ 0 ];

  }

  /**
   * Splits a query string properly, using preg_split to avoid conflicts with dashes and other special chars.
   *
   * @param string $query string to split
   *
   * @return Array
   */
  static public function split_query_string( $query ) {
    /**
     * Split the string properly, so no interference with &ndash; which is used in user input.
     */
    //$data = preg_split( "/&(?!&ndash;)/", $query );
    //$data = preg_split( "/(&(?!.*;)|&&)/", $query );
    $data = preg_split( "/&(?!([a-zA-Z]+|#[0-9]+|#x[0-9a-fA-F]+);)/", $query );

    return $data;
  }

  /**
   * Determines if all of the arrays values are numeric
   *
   *
   * @since 0.55
   *
   */
  static public function is_numeric_range( $array = false ) {
    if( !is_array( $array ) || empty( $array ) ) {
      return false;
    }
    foreach( $array as $value ) {
      if( !is_numeric( $value ) ) {
        return false;
      }
    }

    return true;
  }

  static public function draw_property_type_dropdown( $args = '' ) {
    global $wp_properties;

    $defaults = array( 'id' => 'wpp_property_type', 'name' => 'wpp_property_type', 'selected' => '' );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $id       = isset( $id ) ? $id : 'wpp_property_type';
    $selected = isset( $selected ) ? $selected : '';

    if( !is_array( $wp_properties[ 'property_types' ] ) )
      return;

    $return = "<select id='$id' " . ( !empty( $name ) ? " name='$name' " : '' ) . " >";
    foreach( $wp_properties[ 'property_types' ] as $slug => $label )
      $return .= "<option value='$slug' " . ( $selected == $slug ? " selected='true' " : "" ) . "'>$label</option>";
    $return .= "</select>";

    return $return;

  }

  /**
   *
   */
  static public function draw_property_dropdown( $args = '' ) {
    global $wp_properties, $wpdb;

    $defaults = array( 'id' => 'wpp_properties', 'name' => 'wpp_properties', 'selected' => '' );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $id             = isset( $id ) ? $id : 'wpp_property_type';
    $selected       = isset( $selected ) ? $selected : '';
    $all_properties = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'property' AND post_status = 'publish'" );

    if( !is_array( $all_properties ) )
      return;

    $return = "<select id='$id' " . ( !empty( $name ) ? " name='$name' " : '' ) . " >";
    foreach( $all_properties as $p_data )
      $return .= "<option value='$p_data->id' " . ( $selected == $p_data->id ? " selected='true' " : "" ) . "'>{$p_data->post_title}</option>";
    $return .= "</select>";

    return $return;

  }

  /**
   * Return an array of all available attributes and meta keys
   *
   */
  static public function get_total_attribute_array( $args = '', $extra_values = false ) {
    global $wp_properties, $wpdb;

    $defaults = array(
      'use_optgroups' => 'false'
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $use_optgroups = isset( $use_optgroups ) ? $use_optgroups : 'false';

    $property_stats = $wp_properties[ 'property_stats' ];
    $property_meta  = $wp_properties[ 'property_meta' ];

    if( !is_array( $extra_values ) ) {
      $extra_values = array();
    }

    if( $use_optgroups == 'true' ) {
      $attributes[ 'Attributes' ] = $property_stats;
      $attributes[ 'Meta' ]       = $property_meta;
      $attributes[ 'Other' ]      = $extra_values;
    } else {
      $attributes = $property_stats + $property_meta + $extra_values;
    }

    $attributes = apply_filters( 'wpp_total_attribute_array', $attributes );

    if( !is_array( $attributes ) ) {
      $attributes = array();
    }

    return $attributes;

  }

  /**
   * Render a dropdown of property attributes.
   *
   */
  static public function draw_attribute_dropdown( $args = '', $extra_values = false ) {
    global $wp_properties, $wpdb;

    $defaults = array( 'id' => 'wpp_attribute', 'name' => 'wpp_attribute', 'selected' => '' );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $id       = isset( $id ) ? $id : 'wpp_attribute';
    $selected = isset( $selected ) ? $selected : 'false';
    $name     = isset( $name ) ? $name : 'wpp_attribute';

    $attributes = $wp_properties[ 'property_stats' ];

    if( is_array( $extra_values ) ) {
      $attributes = array_merge( $extra_values, $attributes );
    }

    if( !is_array( $attributes ) )
      return;

    $return = "<select id='$id' " . ( !empty( $name ) ? " name='$name' " : '' ) . " >";
    $return .= "<option value=''> - </option>";

    foreach( $attributes as $slug => $label )
      $return .= "<option value='$slug' " . ( $selected == $slug ? " selected='true' " : "" ) . ">$label ($slug)</option>";
    $return .= "</select>";

    return $return;

  }

  static public function draw_localization_dropdown( $args = '' ) {
    global $wp_properties, $wpdb;

    $defaults = array( 'id' => 'wpp_google_maps_localization', 'name' => 'wpp_google_maps_localization', 'selected' => '', 'return_array' => 'false' );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $return_array = isset( $return_array ) ? $return_array : 'false';
    $id           = isset( $id ) ? $id : 'wpp_google_maps_localization';
    $selected     = isset( $selected ) ? $selected : '';

    $attributes = array(
      'en'    => 'English',
      'ar'    => 'Arabic',
      'bg'    => 'Bulgarian',
      'cs'    => 'Czech',
      'de'    => 'German',
      'el'    => 'Greek',
      'es'    => 'Spanish',
      'fr'    => 'French',
      'hu'    => 'Hungarian',
      'it'    => 'Italian',
      'ja'    => 'Japanese',
      'ko'    => 'Korean',
      'da'    => 'Danish',
      'nl'    => 'Dutch',
      'no'    => 'Norwegian',
      'pt'    => 'Portuguese',
      'pt-BR' => 'Portuguese (Brazil)',
      'pt-PT' => 'Portuguese (Portugal)',
      'ru'    => 'Russian',
      'sv'    => 'Swedish',
      'th'    => 'Thai',
      'uk'    => 'Ukranian' );

    $attributes = apply_filters( "wpp_google_maps_localizations", $attributes );

    if( !is_array( $attributes ) )
      return;

    if( $return_array == 'true' )
      return $attributes;

    $return = "<select id='$id' " . ( !empty( $name ) ? " name='$name' " : '' ) . " >";
    foreach( $attributes as $slug => $label )
      $return .= "<option value='$slug' " . ( $selected == $slug ? " selected='true' " : "" ) . "'>$label ($slug)</option>";
    $return .= "</select>";

    return $return;

  }

  /**
   * Maybe add cache file
   *
   * @version 0.1
   * @since 1.40.0
   * @author peshkov@UD
   */
  static public function set_cache( $name, $data ) {
    $dir = WPP_Path . 'cache/';
    $file = $dir . MD5( $name ) . '.res';
    //** Try to create directory if it doesn't exist */
    if( !is_dir( $dir ) ) {
      @mkdir( $dir, 0755 );
    }
    if( is_dir( $dir ) && @file_put_contents( $file, maybe_serialize( $data ) ) ) {
      return true;
    }
    return false;
  }
  
  /**
   * Maybe get data from cache file
   *
   * @version 0.1
   * @since 1.40.0
   * @author peshkov@UD
   */
  static public function get_cache( $name, $live = 3600 ) {
    $dir = WPP_Path . 'cache/';
    $file = $dir . MD5( $name ) . '.res';
    if( is_file( $file ) && time() - filemtime( $file ) < $live ) {
      $handle = fopen( $file, "r" );
      $content = fread( $handle, filesize( $file ) );
      fclose( $handle );
      return maybe_unserialize( $content );
    }
    return false;
  }
  
  /**
   * Removes all WPP cache files
   *
   * @return string Response
   * @version 0.1
   * @since 1.32.2
   * @author Maxim Peshkov
   */
  static public function clear_cache() {
    $cache_dir = WPP_Path . 'cache/';
    if( file_exists( $cache_dir ) ) {
      wpp_recursive_unlink( $cache_dir );
    }

    return __( 'Cache was successfully cleared', 'wpp' );
  }

  /**
   * Checks for updates against UsabilityDynamics Updates Server
   *
   * @method feature_check
   * @since 0.55
   * @version 1.13.1
   */
  static public function feature_check() {
    global $wp_properties;

    $updates = array();

    try {

      $blogname    = get_bloginfo( 'url' );
      $blogname    = urlencode( str_replace( array( 'http://', 'https://' ), '', $blogname ) );
      $system      = 'wpp';
      $wpp_version = get_option( "wpp_version" );

      //** Get API key - force API key update just in case */
      $api_key = WPP_F::get_api_key( array( 'force_check' => true, 'return' => true ) );

      if( !$api_key || empty( $api_key ) ) {
        throw new Exception( __( 'The API key could not be generated.', 'wpp' ) );
      }

      if( strlen( $api_key ) != 40 ) {
        throw new Exception( sprintf( __( 'An error occurred during premium feature check. API Key \'<b>%s</b>\' is incorrect.', 'wpp' ), $api_key ) );
      }

      $check_url = "http://updates.usabilitydynamics.com/?system={$system}&site={$blogname}&system_version={$wpp_version}&api_key={$api_key}";

      $response = @wp_remote_get( $check_url, array( 'timeout' => 30 ) );

      if( empty( $response ) ) {
        throw new Exception( __( 'Could not do remote request.', 'wpp' ) );
      }

      if( is_wp_error( $response ) ) {
        throw new Exception( $response->get_error_message() );
      }

      if( $response[ 'response' ][ 'code' ] != '200' ) {
        throw new Exception( sprintf( __( 'Response code from requested server is %s.', 'wpp' ), $response[ 'response' ][ 'code' ] ) );
      }

      $r = @json_decode( $response[ 'body' ] );

      if( empty( $r ) ) {
        throw new Exception( __( 'Requested server returned empty result or timeout was exceeded. Please, try again later.', 'wpp' ) );
      }
      
      if( is_object( $r->available_features ) ) {
        $r->available_features = WPP_F::objectToArray( $r->available_features );
        //** Update WP-Property settings */
        $wpp_settings                         = get_option( 'wpp_settings' );
        $wpp_settings[ 'available_features' ] = $r->available_features;
        update_option( 'wpp_settings', $wpp_settings );
      } // available_features

      if( $r->features != 'eligible' ) {
        throw new Exception( __( 'There are no available premium features.', 'wpp' ) );
      }

      if( isset( $wp_properties[ 'configuration' ][ 'disable_automatic_feature_update' ] ) && $wp_properties[ 'configuration' ][ 'disable_automatic_feature_update' ] == 'true' ) {
        throw new Exception( __( 'No premium features were downloaded because the setting is disabled. Enable in the "Developer" tab.', 'wpp' ) );
      }

      //** Try to create directory if it doesn't exist */
      if( !is_dir( WPP_Premium ) ) {
        @mkdir( WPP_Premium, 0755 );
      }

      // If didn't work, we quit
      if( !is_dir( WPP_Premium ) ) {
        throw new Exception( __( 'Specific directory for uploading premium features can not be created.', 'wpp' ) );
      }

      //** Save code */
      if( isset( $r ) && isset( $r->code ) && is_object( $r->code ) ) {

        foreach( $r->code as $code ) {

          $filename = $code->filename;
          $php_code = $code->code;
          $version  = $code->version;

          //** Check version */
          $default_headers = array(
            'Name'        => 'Feature Name',
            'Version'     => 'Version',
            'Description' => 'Description',
          );

          $current_file = @get_file_data( WPP_Premium . "/" . $filename, $default_headers, 'plugin' );

          if( @version_compare( $current_file[ 'Version' ], $version ) == '-1' ) {
            $this_file = WPP_Premium . "/" . $filename;
            $fh        = @fopen( $this_file, 'w' );
            if( $fh ) {
              fwrite( $fh, $php_code );
              fclose( $fh );
              $res = '';
              if( $current_file[ 'Version' ] ) {
                $res = sprintf( __( '<b>%s</b> updated from version %s to %s .', 'wpp' ), $code->name, $current_file[ 'Version' ], $version );
              } else {
                $res = sprintf( __( '<b>%s</b> %s has been installed.', 'wpp' ), $code->name, $version );
              }
              if( !empty( $res ) ) {
                WPP_F::log( sprintf( __( 'WP-Property Premium Feature: %s', 'wpp' ), $res ) );
                $updates[ ] = $res;
              }
            } else {
              throw new Exception( __( 'There are no file permissions to upload or update premium features.', 'wpp' ) );
            }
          }

        }

      } else {
        throw new Exception( __( 'There are no available premium features. Check your licenses for the current domain', 'wpp' ) );
      }

      //** Update settings */
      WPP_F::settings_action( true );

    } catch( Exception $e ) {

      WPP_F::log( "Feature Update Error: " . $e->getMessage() );

      return new WP_Error( 'error', $e->getMessage() );

    }

    $result = __( 'Update ran successfully.', 'wpp' );
    if( !empty( $updates ) ) {
      $result .= '<ul>';
      foreach( $updates as $update ) {
        $result .= "<li>{$update}</li>";
      }
      $result .= '</ul>';
    } else {
      $result .= '<br/>' . __( 'You have the latest premium features versions.', 'wpp' );
    }

    $result = apply_filters( 'wpp::feature_check::result', $result, $updates );

    return $result;
  }

  /**
   * Makes a given property featured, usuall called via ajax
   *
   * @since 0.721
   */
  static public function toggle_featured( $post_id = false ) {
    global $current_user;

    if( !current_user_can( 'manage_options' ) )
      return;

    if( !$post_id )
      return;

    $featured = get_post_meta( $post_id, 'featured', true );

    // Check if already featured
    if( $featured == 'true' ) {
      update_post_meta( $post_id, 'featured', 'false' );
      $status = 'not_featured';
    } else {
      update_post_meta( $post_id, 'featured', 'true' );
      $status = 'featured';
    }

    echo json_encode( array( 'success' => 'true', 'status' => $status, 'post_id' => $post_id ) );
  }

  /**
   * Add or remove taxonomy columns
   *
   * @since 3.0
   */
  static public function overview_columns( $columns ) {
    global $wp_properties, $wp_taxonomies;

    $overview_columns = apply_filters( 'wpp_overview_columns', array(
      'cb'            => '',
      'title'         => __( 'Title', 'wpp' ),
      'property_type' => __( 'Type', 'wpp' ),
      'overview'      => __( 'Overview', 'wpp' ),
      'features'      => __( 'Features', 'wpp' ),
      'featured'      => __( 'Featured', 'wpp' )
    ) );

    if( !in_array( 'property_feature', array_keys( (array) $wp_taxonomies ) ) ) {
      unset( $overview_columns[ 'features' ] );
    }

    $overview_columns[ 'thumbnail' ] = __( 'Thumbnail', 'wpp' );

    foreach( $overview_columns as $column => $title ) {
      $columns[ $column ] = $title;
    }

    return $columns;

  }

  static public function custom_attribute_columns( $columns ) {
    global $wp_properties;

    if( !empty( $wp_properties[ 'column_attributes' ] ) ) {

      foreach( $wp_properties[ 'column_attributes' ] as $id => $slug ) {
        $columns[ $slug ] = __( $wp_properties[ 'property_stats' ][ $slug ], 'wpp' );
      }

    }

    return $columns;

  }

  /**
   * Displays dropdown of available property size images
   *
   *
   * @since 0.54
   *
   */
  static public function image_sizes_dropdown( $args = "" ) {
    global $wp_properties;

    $defaults = array(
      'name'                  => 'wpp_image_sizes',
      'selected'              => 'none',
      'blank_selection_label' => ' - '
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $blank_selection_label = isset( $blank_selection_label ) ? $blank_selection_label : ' - ';
    $selected              = isset( $selected ) ? $selected : 'none';

    if( empty( $id ) && !empty( $name ) ) {
      $id = $name;
    }

    $image_array = get_intermediate_image_sizes();

    ?>
    <select id="<?php echo $id ?>" name="<?php echo $name ?>">
      <option value=""><?php echo $blank_selection_label; ?></option>
      <?php
      foreach( $image_array as $name ) {
        $sizes = WPP_F::image_sizes( $name );

        if( !$sizes ) {
          continue;
        }

        ?>
        <option value='<?php echo $name; ?>' <?php if( $selected == $name ) echo 'SELECTED'; ?>>
          <?php echo $name; ?>: <?php echo $sizes[ 'width' ]; ?>px by <?php echo $sizes[ 'height' ]; ?>px
        </option>
      <?php } ?>
    </select>

  <?php
  }

  /**
   * Returns image sizes for a passed image size slug
   *
   * Looks through all images sizes.
   *
   * @since 0.54
   *
   * @param bool   $type
   * @param string $args
   *
   * @returns array keys: 'width' and 'height' if image type sizes found.
   */
  static public function image_sizes( $type = false, $args = "" ) {
    global $_wp_additional_image_sizes;

    $defaults = array(
      'return_all' => false
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $return_all = isset( $return_all ) ? $return_all : 'none';

    if( !$type ) {
      return false;
    }

    if( isset( $_wp_additional_image_sizes[ $type ] ) && is_array( $_wp_additional_image_sizes[ $type ] ) ) {
      $return = $_wp_additional_image_sizes[ $type ];

    } else {

      if( $type == 'thumbnail' || $type == 'thumb' ) {
        $return = array( 'width' => intval( get_option( 'thumbnail_size_w' ) ), 'height' => intval( get_option( 'thumbnail_size_h' ) ) );
      }

      if( $type == 'medium' ) {
        $return = array( 'width' => intval( get_option( 'medium_size_w' ) ), 'height' => intval( get_option( 'medium_size_h' ) ) );
      }

      if( $type == 'large' ) {
        $return = array( 'width' => intval( get_option( 'large_size_w' ) ), 'height' => intval( get_option( 'large_size_h' ) ) );
      }

    }

    if( !isset( $return ) || !is_array( $return ) ) {
      return;
    }

    if( !$return_all ) {

      // Zeroed out dimensions means they are deleted
      if( empty( $return[ 'width' ] ) || empty( $return[ 'height' ] ) ) {
        return;
      }

      // Zeroed out dimensions means they are deleted
      if( $return[ 'width' ] == '0' || $return[ 'height' ] == '0' ) {
        return;
      }

    }

    // Return dimensions
    return $return;

  }

  /**
   * AJAX Handler.
   * Saves WPP Settings
   *
   * @author peshkov@UD
   * @since 1.38.3
   */
  static public function save_settings() {
    global $wp_properties;

    $data = self::parse_str( $_REQUEST[ 'data' ] );

    $return = array(
      'success'  => true,
      'message'  => '',
      'redirect' => admin_url( "edit.php?post_type=property&page=property_settings&message=updated" )
    );

    try {
      if( empty( $data[ 'wpp_settings' ] ) || !wp_verify_nonce( $data[ '_wpnonce' ], 'wpp_setting_save' ) ) {
        throw new Exception( __( 'Request can not be verified.', 'wpp' ) );
      }
      //** Allow features to preserve their settings that are not configured on the settings page */
      $wpp_settings = apply_filters( 'wpp_settings_save', $data[ 'wpp_settings' ], $wp_properties );
      //** Prevent removal of featured settings configurations if they are not present */
      if( !empty( $wp_properties[ 'configuration' ][ 'feature_settings' ] ) ) {
        foreach( $wp_properties[ 'configuration' ][ 'feature_settings' ] as $feature_type => $preserved_settings ) {
          if( empty( $data[ 'wpp_settings' ][ 'configuration' ][ 'feature_settings' ][ $feature_type ] ) ) {
            $wpp_settings[ 'configuration' ][ 'feature_settings' ][ $feature_type ] = $preserved_settings;
          }
        }
      }
      update_option( 'wpp_settings', $wpp_settings );
    } catch( Exception $e ) {
      $return[ 'success' ] = false;
      $return[ 'message' ] = $e->getMessage();
    }

    return json_encode( $return );
  }

  /**
   * Loads settings into global variable
   * Also restores data from backup file.
   *
   * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
   *
   * As of 1.11 prevents removal of premium feature configurations that are not held in the settings page array
   *
   * 1.12 - added taxonomies filter: wpp_taxonomies
   * 1.14 - added backup from text file
   *
   * @param bool $force_db
   *
   * @return array|$wp_properties
   * @since 1.12
   */
  static public function settings_action( $force_db = false ) {
    global $wp_properties;

    //** Handle backup */
    if( isset( $_REQUEST[ 'wpp_settings' ] ) &&
      wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'wpp_setting_save' ) &&
      !empty( $_FILES[ 'wpp_settings' ][ 'tmp_name' ][ 'settings_from_backup' ] )
    ) {
      $backup_file     = $_FILES[ 'wpp_settings' ][ 'tmp_name' ][ 'settings_from_backup' ];
      $backup_contents = file_get_contents( $backup_file );
      if( !empty( $backup_contents ) ) {
        $decoded_settings = json_decode( $backup_contents, true );
      }
      if( !empty( $decoded_settings ) ) {
        //** Allow features to preserve their settings that are not configured on the settings page */
        $wpp_settings = apply_filters( 'wpp_settings_save', $decoded_settings, $wp_properties );
        //** Prevent removal of featured settings configurations if they are not present */
        if( !empty( $wp_properties[ 'configuration' ][ 'feature_settings' ] ) ) {
          foreach( $wp_properties[ 'configuration' ][ 'feature_settings' ] as $feature_type => $preserved_settings ) {
            if( empty( $decoded_settings[ 'configuration' ][ 'feature_settings' ][ $feature_type ] ) ) {
              $wpp_settings[ 'configuration' ][ 'feature_settings' ][ $feature_type ] = $preserved_settings;
            }
          }
        }
        update_option( 'wpp_settings', $wpp_settings );
        //** Load settings out of database to overwrite defaults from action_hooks. */
        $wp_properties_db = get_option( 'wpp_settings' );
        //** Overwrite $wp_properties with database setting */
        $wp_properties = array_merge( $wp_properties, $wp_properties_db );
        //** Reload page to make sure higher-end functions take affect of new settings */
        //** The filters below will be ran on reload, but the saving functions won't */
        if( $_REQUEST[ 'page' ] == 'property_settings' ) {
          unset( $_REQUEST );
          wp_redirect( admin_url( "edit.php?post_type=property&page=property_settings&message=updated" ) );
          exit;
        }
      }
    }

    if( $force_db ) {

      // Load settings out of database to overwrite defaults from action_hooks.
      $wp_properties_db = get_option( 'wpp_settings' );

      // Overwrite $wp_properties with database setting
      $wp_properties = array_merge( $wp_properties, $wp_properties_db );

    }

    add_filter( 'wpp_image_sizes', array( 'WPP_F', 'remove_deleted_image_sizes' ) );

    // Filers are applied
    $wp_properties[ 'configuration' ]             = apply_filters( 'wpp_configuration', $wp_properties[ 'configuration' ] );
    $wp_properties[ 'location_matters' ]          = apply_filters( 'wpp_location_matters', $wp_properties[ 'location_matters' ] );
    $wp_properties[ 'hidden_attributes' ]         = apply_filters( 'wpp_hidden_attributes', $wp_properties[ 'hidden_attributes' ] );
    $wp_properties[ 'descriptions' ]              = apply_filters( 'wpp_label_descriptions', $wp_properties[ 'descriptions' ] );
    $wp_properties[ 'image_sizes' ]               = apply_filters( 'wpp_image_sizes', $wp_properties[ 'image_sizes' ] );
    $wp_properties[ 'search_conversions' ]        = apply_filters( 'wpp_search_conversions', $wp_properties[ 'search_conversions' ] );
    $wp_properties[ 'searchable_attributes' ]     = apply_filters( 'wpp_searchable_attributes', $wp_properties[ 'searchable_attributes' ] );
    $wp_properties[ 'searchable_property_types' ] = apply_filters( 'wpp_searchable_property_types', $wp_properties[ 'searchable_property_types' ] );
    $wp_properties[ 'property_inheritance' ]      = apply_filters( 'wpp_property_inheritance', $wp_properties[ 'property_inheritance' ] );
    $wp_properties[ 'property_meta' ]             = apply_filters( 'wpp_property_meta', $wp_properties[ 'property_meta' ] );
    $wp_properties[ 'property_stats' ]            = apply_filters( 'wpp_property_stats', $wp_properties[ 'property_stats' ] );
    $wp_properties[ 'property_types' ]            = apply_filters( 'wpp_property_types', $wp_properties[ 'property_types' ] );
    $wp_properties[ 'taxonomies' ]                = apply_filters( 'wpp_taxonomies', ( isset( $wp_properties[ 'taxonomies' ] ) ? $wp_properties[ 'taxonomies' ] : array() ) );

    $wp_properties = stripslashes_deep( $wp_properties );
    
    return $wp_properties;

  }

  /**
   * Utility to remove deleted image sizes.
   *
   * @param $sizes
   *
   * @return mixed
   */
  static public function remove_deleted_image_sizes( $sizes ) {
    global $wp_properties;

    foreach( $sizes as $slug => $size ) {
      if( $size[ 'width' ] == '0' || $size[ 'height' ] == '0' )
        unset( $sizes[ $slug ] );

    }

    return $sizes;

  }

  /**
   * Loads property values into global $post variables.
   *
   * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
   * Ran after template_redirect.
   * $property is loaded in WPP_Core::template_redirect();
   *
   * @since 0.54
   *
   */
  static public function the_post( $post ) {
    global $post, $property;

    if( $post->post_type != 'property' ) {
      return $post;
    }

    //** Update global $post object to include property specific attributes */
    $post = (object) ( (array) $post + (array) $property );

  }

  /**
   * Check for premium features and load them
   *
   * @updated 1.39.0
   * @since 0.624
   */
  static public function load_premium() {
    global $wp_properties;

    $default_headers = array(
      'name'         => 'Name',
      'version'      => 'Version',
      'description'  => 'Description',
      'class'        => 'Class',
      'slug'         => 'Slug',
      'minimum.core' => 'Minimum Core Version',
      'minimum.php'  => 'Minimum PHP Version',
      'capability'   => 'Capability'
    );

    if( !is_dir( WPP_Premium ) )
      return;

    if( $premium_dir = opendir( WPP_Premium ) ) {

      if( file_exists( WPP_Premium . "/index.php" ) ) {
        @include_once( WPP_Premium . "/index.php" );
      }

      $_verified = array();

      while( false !== ( $file = readdir( $premium_dir ) ) ) {

        if( $file == 'index.php' )
          continue;

        if( end( @explode( ".", $file ) ) == 'php' ) {

          $_upgraded = false;

          $plugin_data = @get_file_data( WPP_Premium . "/" . $file, $default_headers, 'plugin' );

          $plugin_slug = $plugin_data[ 'slug' ] ? $plugin_data[ 'slug' ] : str_replace( array( '.php' ), '', $file );

          // Admin tools premium feature was moved to core. So it must not be loaded twice.
          if( $plugin_slug == 'class_admin_tools' ) {
            continue;
          }
          
          if( !isset( $wp_properties[ 'installed_features' ][ $plugin_slug ] ) ) {
            $wp_properties[ 'installed_features' ][ $plugin_slug ] = array();
          }

          if( 
            !empty( $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'version' ] ) 
            && version_compare( $plugin_data[ 'version' ], $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'version' ] ) > 0 
          ) {
            $_upgraded = true;
          }

          $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'name' ]        = $plugin_data[ 'name' ];
          $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'version' ]     = $plugin_data[ 'version' ];
          $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'description' ] = $plugin_data[ 'description' ];
          $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'class' ]       = $plugin_data[ 'class' ] ? $plugin_data[ 'class' ] : $plugin_slug;

          $_verified[] = $plugin_slug;

          if( $plugin_data[ 'minimum.core' ] ) {
            $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum.core' ] = $plugin_data[ 'minimum.core' ];
          }

          // If feature has a Minimum Core Version and it is more than current version - we do not load
          $feature_requires_upgrade = ( !empty( $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum.core' ] ) && ( version_compare( WPP_Version, $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'minimum.core' ] ) < 0 ) ? true : false );

          if( $feature_requires_upgrade ) {

            //** Disable feature if it requires a higher WPP version**/

            $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ]                 = 'true';
            $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_higher_wpp_version' ] = 'true';

          } elseif( !isset( $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] ) || $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] != 'true' ) {

            // Continue with loading feature...
            $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_higher_wpp_version' ] = 'false';

            // Module requires a higher version of PHP than is available.
            if( !$plugin_data[ 'minimum.php' ] || version_compare( PHP_VERSION, $plugin_data[ 'minimum.php' ] ) > 0 ) {

              if( WP_DEBUG == true ) {
                include_once( trailingslashit( WPP_Premium ) . $file );
              } else {
                @include_once( trailingslashit( WPP_Premium ) . $file );
              }

              // Initialize Module that declare a class.
              if( $plugin_data[ 'class' ] && class_exists( $_class = $plugin_data[ 'class' ] ) ) {
                $_instance = new $_class( $wp_properties, $plugin_data );

                // Call Upgrade Method, if exists.
                if( $_upgraded && is_callable( array( $_instance, 'upgrade' ) ) ) {
                  $_instance->upgrade( $wp_properties );
                }

              }

            }

            // Disable plugin if class does not exists - file is empty
            if( !$_class && !class_exists( $plugin_slug ) ) {
              unset( $wp_properties[ 'installed_features' ][ $plugin_slug ] );
            }

            $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'disabled' ] = 'false';

          } else {
            //* This happens when feature cannot be loaded and is disabled */

            //** We unset requires core upgrade in case feature was update while being disabled */
            $wp_properties[ 'installed_features' ][ $plugin_slug ][ 'needs_higher_wpp_version' ] = 'false';

          }

        }

      }

      //** Remove features that are not found on disk */
      if( isset( $wp_properties[ 'installed_features' ] ) ) {
        foreach( (array) $wp_properties[ 'installed_features' ] as $_slug => $data ) {
          if( !in_array( $_slug, $_verified ) ) {
            unset( $wp_properties[ 'installed_features' ][ $_slug ] );
          }
        }
      }

    }


  }

  /**
   * Check if premium feature is installed or not
   *
   * @param string $slug . Slug of premium feature
   *
   * @return boolean.
   */
  static public function check_premium( $slug ) {
    global $wp_properties;

    if( empty( $wp_properties[ 'installed_features' ][ $slug ][ 'version' ] ) ) {
      return false;
    }

    $file = WPP_Premium . "/" . $slug . ".php";

    $default_headers = array(
      'Name'        => 'Name',
      'Version'     => 'Version',
      'Description' => 'Description'
    );

    $plugin_data = @get_file_data( $file, $default_headers, 'plugin' );

    if( !is_array( $plugin_data ) || empty( $plugin_data[ 'Version' ] ) ) {
      return false;
    }

    return true;
  }

  /**
   * Checks updates for premium features by AJAX
   * Prints results to body.
   *
   * @global array $wp_properties
   * @return null
   */
  static public function check_plugin_updates() {
    global $wp_properties;

    $result = WPP_F::feature_check();

    if( is_wp_error( $result ) ) {
      printf( __( 'An error occurred during premium feature check: <b> %s </b>.', 'wpp' ), $result->get_error_message() );
    } else {
      echo $result;
    }

    return null;
  }

  /**
   * Run on plugin activation.
   *
   * As of WP 3.1 this is not ran on automatic update.
   *
   * @since 1.10
   *
   */
  static public function activation() {
    global $wp_rewrite;
    // Do close to nothing because only ran on activation, not updates, as of 3.1
    // Now handled by WPP_F::manual_activation().

    $wp_rewrite->flush_rules();
  }

  /**
   * Run manually when a version mismatch is detected.
   *
   * Holds official current version designation.
   * Called in admin_init hook.
   *
   * @since 1.10
   * @version 1.13
   *
   */
  static public function manual_activation() {

    $installed_ver = get_option( "wpp_version", 0 );
    $wpp_version   = WPP_Version;

    if( @version_compare( $installed_ver, $wpp_version ) == '-1' ) {
      // We are upgrading.

      // Unschedule event
      $timestamp = wp_next_scheduled( 'wpp_premium_feature_check' );
      wp_unschedule_event( $timestamp, 'wpp_premium_feature_check' );
      wp_clear_scheduled_hook( 'wpp_premium_feature_check' );

      // Schedule event
      wp_schedule_event( time(), 'daily', 'wpp_premium_feature_check' );

      //** Upgrade data if needed */
      WPP_Legacy::upgrade();

      // Update option to latest version so this isn't run on next admin page load
      update_option( "wpp_version", $wpp_version );

      // Get premium features on activation
      @WPP_F::feature_check();

    }

    return;

  }

  /**
   * Plugin Deactivation
   *
   */
  static public function deactivation() {
    global $wp_rewrite;
    $timestamp = wp_next_scheduled( 'wpp_premium_feature_check' );
    wp_unschedule_event( $timestamp, 'wpp_premium_feature_check' );
    wp_clear_scheduled_hook( 'wpp_premium_feature_check' );

    $wp_rewrite->flush_rules();

  }

  /**
   * Returns array of searchable property IDs
   *
   *
   * @return array|$wp_properties
   * @since 0.621
   *
   */
  static public function get_searchable_properties() {
    global $wp_properties;

    $searchable_properties = array();

    if( !is_array( $wp_properties[ 'searchable_property_types' ] ) )
      return;

    // Get IDs of all property types
    foreach( $wp_properties[ 'searchable_property_types' ] as $property_type ) {

      $this_type_properties = WPP_F::get_properties( "property_type=$property_type" );

      if( is_array( $this_type_properties ) && is_array( $searchable_properties ) )
        $searchable_properties = array_merge( $searchable_properties, $this_type_properties );
    }

    if( is_array( $searchable_properties ) )
      return $searchable_properties;

    return false;

  }

  /**
   * Modifies value of specific property stat (property attribute)
   *
   * Used by filter wpp_attribute_filter in WPP_Object_List_Table::single_row();
   *
   * @param $value
   * @param $slug
   *
   * @return mixed|string|void $value Modified value
   */
  static public function attribute_filter( $value, $slug ) {
    global $wp_properties;

    // Filter bool values
    if( $value == 'true' ) {
      $value = __( 'Yes', 'wp' );
    } elseif( $value == 'false' ) {
      $value = __( 'No', 'wp' );
    }

    // Filter currency
    if( !empty( $wp_properties[ 'currency_attributes' ] ) ) {
      foreach( $wp_properties[ 'currency_attributes' ] as $id => $attr ) {
        if( $slug == $attr ) {
          $value = apply_filters( "wpp_stat_filter_price", $value );
        }
      }
    }

    return $value;
  }

  /**
   * Returns array of searchable attributes and their ranges
   *
   *
   * @param      $search_attributes
   * @param      $searchable_property_types
   * @param bool $cache
   * @param bool $instance_id
   *
   * @return array|$range
   * @since 0.57
   */
  static public function get_search_values( $search_attributes, $searchable_property_types, $cache = true, $instance_id = false ) {
    global $wpdb, $wp_properties;

    // Non post_meta fields
    $non_post_meta = array(
      'ID'        => 'equal',
      'post_date' => 'date'
    );
    
    if( $instance_id && $cache ) {
      $result = WPP_F::get_cache( $instance_id );
    }

    if( empty( $result ) ) {
      $query_attributes = "";
      $query_types      = "";

      //** Use the requested attributes, or all searchable */
      if( !is_array( $search_attributes ) ) {
        $search_attributes = $wp_properties[ 'searchable_attributes' ];
      }

      if( !is_array( $searchable_property_types ) ) {
        $searchable_property_types = explode( ',', $searchable_property_types );
        foreach( $searchable_property_types as $k => $v ) {
          $searchable_property_types[ $k ] = trim( $v );
        }
      }
      $searchable_property_types_sql = "AND pm2.meta_value IN ('" . implode( "','", $searchable_property_types ) . "')";

      //** Cycle through requested attributes */
      foreach( $search_attributes as $searchable_attribute ) {

        if( $searchable_attribute == 'property_type' ) {
          continue;
        }

        //** Load attribute data */
        $attribute_data = WPP_F::get_attribute_data( $searchable_attribute );

        if( isset( $attribute_data[ 'numeric' ] ) || isset( $attribute_data[ 'currency' ] ) ) {
          $is_numeric = true;
        } else {
          $is_numeric = false;
        }
        
        //** Check to see if this attribute has predefined values or if we have to get them from DB */
        //** If the attributes has predefind values, we use them */
        if( !empty( $wp_properties[ 'predefined_search_values' ][ $searchable_attribute ] ) ) {
          $predefined_search_values = $wp_properties[ 'predefined_search_values' ][ $searchable_attribute ];
          $predefined_search_values = str_replace( array( ', ', ' ,' ), array( ',', ',' ), trim( $predefined_search_values ) );
          $predefined_search_values = explode( ',', $predefined_search_values );

          if( is_array( $predefined_search_values ) ) {
            foreach( $predefined_search_values as $value ) {
              $range[ $searchable_attribute ][ ] = $value;
            }
          } else {
            $range[ $searchable_attribute ][ ] = $predefined_search_values;
          }

        } elseif( array_key_exists( $searchable_attribute, $non_post_meta ) ) {

          $type = $non_post_meta[ $searchable_attribute ];

          //** No predefined value exist */
          $db_values = $wpdb->get_col( "
            SELECT DISTINCT(" . ( $type == 'data' ? "DATE_FORMAT(p1.{$searchable_attribute}, '%Y%m')" : "p1.{$searchable_attribute}" ) . ")
            FROM {$wpdb->posts} p1
            LEFT JOIN {$wpdb->postmeta} pm2 ON p1.ID = pm2.post_id
            WHERE pm2.meta_key = 'property_type' 
              AND p1.post_status = 'publish'
              $searchable_property_types_sql
            order by p1.{$searchable_attribute}
          " );

          //* Get all available values for this attribute for this property_type */
          $range[ $searchable_attribute ] = $db_values;

        } else {

          //** No predefined value exist */
          $db_values = $wpdb->get_col( "
            SELECT DISTINCT(pm1.meta_value)
            FROM {$wpdb->posts} p1
            LEFT JOIN {$wpdb->postmeta} pm1 ON p1.ID = pm1.post_id
            LEFT JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '{$searchable_attribute}' 
              AND pm2.meta_key = 'property_type'
              AND pm1.meta_value != ''
              AND p1.post_status = 'publish'
              $searchable_property_types_sql
            ORDER BY " . ( $is_numeric ? 'ABS(' : '' ) . "pm1.meta_value" . ( $is_numeric ? ')' : '' ) . " ASC
          " );

          //* Get all available values for this attribute for this property_type */
          $range[ $searchable_attribute ] = $db_values;

        }

        //** Get unique values*/
        if( is_array( $range[ $searchable_attribute ] ) ) {
          $range[ $searchable_attribute ] = array_unique( $range[ $searchable_attribute ] );
        } else {
          //* This should not happen */
        }

        foreach( $range[ $searchable_attribute ] as $key => $value ) {

          $original_value = $value;

          // Clean up values if a conversion exists
          $value = WPP_F::do_search_conversion( $searchable_attribute, trim( $value ) );
          
          // Fix value with special chars. Disabled here, should only be done in final templating stage.
          // $value = htmlspecialchars($value, ENT_QUOTES);

          //* Remove bad characters signs if attribute is numeric or currency */
          if( $is_numeric ) {
            $value = str_replace( array( ",", "$" ), '', $value );
          }

          //** Put cleaned up value back into array */
          $range[ $searchable_attribute ][ $key ] = $value;

        }
        
        //** Sort values */
        sort( $range[ $searchable_attribute ], SORT_REGULAR );

      } //** End single attribute data gather */

      $result = $range;

      if( $instance_id && $cache ) {
        WPP_F::set_cache( $instance_id, $result );
      }
    }

    return apply_filters( 'wpp::get_search_values', $result, array(
      'search_attributes' => $search_attributes, 
      'searchable_property_types' => $searchable_property_types, 
      'cache' => $cache, 
      'instance_id' => $instance_id,
    ) );
  }

  /**
   * Check if a search converstion exists for a attributes value
   */
  static public function do_search_conversion( $attribute, $value, $reverse = false ) {
    global $wp_properties;

    if( !isset( $wp_properties[ 'search_conversions' ][ $attribute ] ) ) {
      return $value;
    }
    
    // First, check if any conversions exists for this attribute, if not, return value
    if( count( $wp_properties[ 'search_conversions' ][ $attribute ] ) < 1 ) {
      return $value;
    }

    // If reverse is set to true, means we are trying to convert a value to integerer (most likely),
    // For isntance: in "bedrooms", $value = 0 would be converted to "Studio"
    if( $reverse ) {

      $flipped_conversion = array_flip( $wp_properties[ 'search_conversions' ][ $attribute ] );

      if( !empty( $flipped_conversion[ $value ] ) ) {
        return $flipped_conversion[ $value ];
      }

    }
    // Need to $conversion == '0' or else studios will not work, since they have 0 bedrooms
    $conversion = isset( $wp_properties[ 'search_conversions' ][ $attribute ][ $value ] ) ? $wp_properties[ 'search_conversions' ][ $attribute ][ $value ] : false;
    if( $conversion === '0' || !empty( $conversion ) )
      return $conversion;

    // Return value in case something messed up
    return $value;

  }

  /**
   * Primary static function for queries properties  based on type and attributes
   *
   * @todo There is a limitation when doing a search such as 4,5+ then mixture of specific and open ended search is not supported.
   * @since 1.08
   *
   * @param string $args / $args
   *
   * @param bool   $total
   *
   * @return bool|mixed|void
   */
  static public function get_properties( $args = "", $total = false ) {
    global $wpdb, $wp_properties, $wpp_query;

    //** Cleanup (fix) ID argument if it's passed */
    $args = wp_parse_args( $args );
    if( isset( $args[ 'id' ] ) ) {
      $args[ 'ID' ] = $args[ 'id' ];
      unset( $args[ 'id' ] );
    }
    //** property_id is replaced with ID only if Property Attribute with slug 'property_id' does not exist */
    if( isset( $args[ 'property_id' ] ) && !key_exists( 'property_id', $wp_properties[ 'property_stats' ] ) ) {
      $args[ 'ID' ] = $args[ 'property_id' ];
      unset( $args[ 'property_id' ] );
    }
    
    //** Prints args to firebug if debug mode is enabled */
    $log = is_array( $args ) ? urldecode( http_build_query( $args ) ) : $args;
    WPP_F::console_log( "get_properties() args: {$log}" );

    //** The function can be overwritten using the filter below. */
    $response = apply_filters( 'wpp::get_properties::custom', null, $args, $total );
    if( $response !== null ) {
      return $response;
    }

    $_query_keys = array();

    /* Define keys that should not be used to query data */
    $_system_keys = array(
      'pagi',
      'pagination',
      'limit_query',
      'starting_row',
      'sort_by',
      'sort_order'
    );

    // Non post_meta fields
    $non_post_meta = array(
      'post_title'  => 'like',
      'post_status' => 'equal',
      'post_author' => 'equal',
      'ID'          => 'or',
      'post_parent' => 'equal',
      'post_date'   => 'date'
    );

    /**
     * Specific meta data can contain value with commas. E.g. location field ( address_attribute )
     * The current list contains meta slugs which will be ignored for comma parsing. peshkov@UD
     */
    $commas_ignore = apply_filters( 'wpp::get_properties::commas_ignore', array_filter( array( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ) );

    $capture_sql_args = array( 'limit_query' );

    //** added to avoid range and "LIKE" searches on single numeric values *
    if( is_array( $args ) ) {
      foreach( (array) $args as $thing => $value ) {

        if( in_array( $thing, (array) $capture_sql_args ) ) {
          $sql_args[ $thing ] = $value;
          unset( $args[ $thing ] );
          continue;
        }

        // unset empty filter options
        if( empty( $value ) ) {
          unset( $args[ $thing ] );
          continue;
        }

        if( is_array( $value ) ) {
          $value = implode( ',', $value );
        }
        $value = trim( $value );

        $original_value = $value;

        $numeric = !empty( $wp_properties[ 'numeric_attributes' ] ) && in_array( $thing, (array) $wp_properties[ 'numeric_attributes' ] ) ? true : false;

        //** If not CSV and last character is a +, we look for open-ended ranges, i.e. bedrooms: 5+
        if( substr( $original_value, -1, 1 ) == '+' && !strpos( $original_value, ',' ) && $numeric ) {
          //** User requesting an open ended range, we leave it off with a dash, i.e. 500- */
          $args[ $thing ] = str_replace( '+', '', $value ) . '-';
        } elseif( is_numeric( $value ) && $numeric ) {
          //** If number is numeric, we do a specific search, i.e. 500-500 */
          if( !array_key_exists( $thing, $non_post_meta ) ) {
            $args[ $thing ] = $value . '-' . $value;
          }
        } elseif( is_string( $value ) ) {
          $args[ $thing ] = $value;
        }
      }
    }

    $defaults = array(
      'property_type' => 'all',
      'pagi' => false,
      'sort_by' => false,
    );

    $query      = wp_parse_args( $args, $defaults );
    $query      = apply_filters( 'wpp_get_properties_query', $query );
    $query_keys = array_keys( (array) $query );

    //** Search by non meta values */
    $additional_sql = '';

    //** Show 'publish' posts if status is not specified */
    if( !array_key_exists( 'post_status', $query ) ) {
      $additional_sql .= " AND p.post_status = 'publish' ";
    } else {
      if( $query[ 'post_status' ] != 'all' ) {
        if( strpos( $query[ 'post_status' ], ',' ) === false ) {
          $additional_sql .= " AND p.post_status = '{$query['post_status']}' ";
        } else {
          $post_status = explode( ',', $query[ 'post_status' ] );
          foreach( $post_status as &$ps ) {
            $ps = trim( $ps );
          }
          $additional_sql .= " AND p.post_status IN ( '" . implode( "','", $post_status ) . "') ";
        }
      } else {
        $additional_sql .= " AND p.post_status <> 'auto-draft' ";
      }
      unset( $query[ 'post_status' ] );
    }

    foreach( (array) $non_post_meta as $field => $condition ) {
      if( array_key_exists( $field, $query ) ) {
        if( $condition == 'like' ) {
          $additional_sql .= " AND p.$field LIKE '%{$query[$field]}%' ";
        }
        else if( $condition == 'equal' ) {
          $additional_sql .= " AND p.$field = '{$query[$field]}' ";
        }
        else if( $condition == 'or' ) {
          $f = '';
          $d = !is_array( $query[ $field ] ) ? explode( ',', $query[ $field ] ) : $query[ $field ];
          foreach( $d as $k => $v ) {
            $f .= ( !empty( $f ) ? ",'" . trim($v) . "'" : "'" . trim($v) . "'" );
          }
          $additional_sql .= " AND p.$field IN ({$f}) ";
        }
        else if( $condition == 'date' ) {
          $additional_sql .= " AND YEAR( p.$field ) = " . substr( $query[ $field ], 0, 4 ) . " AND MONTH( p.$field ) = " . substr( $query[ $field ], 4, 2 ) . " ";
        }
        unset( $query[ $field ] );
      }
    }

    if( !empty( $sql_args[ 'limit_query' ] ) ) {
      $sql_args[ 'starting_row' ] = ( $sql_args[ 'starting_row' ] ? $sql_args[ 'starting_row' ] : 0 );
      $limit_query                = "LIMIT {$sql_args['starting_row']}, {$sql_args['limit_query']};";

    } elseif( substr_count( $query[ 'pagi' ], '--' ) ) {
      $pagi = explode( '--', $query[ 'pagi' ] );
      if( count( $pagi ) == 2 && is_numeric( $pagi[ 0 ] ) && is_numeric( $pagi[ 1 ] ) ) {
        $limit_query = "LIMIT $pagi[0], $pagi[1];";
      }
    }

    /** Handles the sort_by parameter in the Short Code */
    if( $query[ 'sort_by' ] ) {
      $sql_sort_by    = $query[ 'sort_by' ];
      $sql_sort_order = isset( $query[ 'sort_order' ] ) ? strtoupper( $query[ 'sort_order' ] ) : 'ASC';
    } else {
      $sql_sort_by    = 'post_date';
      $sql_sort_order = 'ASC';
    }

    //** Unsert arguments that will conflict with attribute query */
    foreach( (array) $_system_keys as $system_key ) {
      unset( $query[ $system_key ] );
    }

    // Go down the array list narrowing down matching properties
    foreach( (array) $query as $meta_key => $criteria ) {

      $specific = '';

      // Stop filtering ( loop ) because no IDs left
      if( isset( $matching_ids ) && empty( $matching_ids ) ) {
        break;
      }

      $numeric = ( isset( $wp_properties[ 'numeric_attributes' ] ) && in_array( $meta_key, (array) $wp_properties[ 'numeric_attributes' ] ) ) ? true : false;

      if( !in_array( $meta_key, (array) $commas_ignore ) && substr_count( $criteria, ',' ) || ( substr_count( $criteria, '-' ) && $numeric ) || substr_count( $criteria, '--' ) ) {
      
        if( substr_count( $criteria, '-' ) && !substr_count( $criteria, ',' ) ) {
          $cr = explode( '-', $criteria );
          // Check pieces of criteria. Array should contains 2 int's elements
          // In other way, it's just value of meta_key
          if( count( $cr ) > 2 || ( ( float ) $cr[ 0 ] == 0 && ( float ) $cr[ 1 ] == 0 ) ) {
            $specific = $criteria;
          } else {
            $hyphen_between = $cr;
            // If min value doesn't exist, set 1
            if( empty( $hyphen_between[ 0 ] ) ) {
              $hyphen_between[ 0 ] = 1;
            }
          }
        }
        
        if ( substr_count( $criteria, ',' ) ) {
          $comma_and = explode( ',', $criteria );
        }
        
      } else {
        $specific = $criteria;
      }

      if( !isset( $limit_query ) ) {
        $limit_query = '';
      }

      switch( $meta_key ) {

        case 'property_type':

          // Get all property types
          if( $specific == 'all' ) {
            if( isset( $matching_ids ) ) {
              $matching_id_filter = implode( "' OR ID ='", $matching_ids );
              $matching_ids       = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE (ID ='$matching_id_filter' ) AND post_type = 'property'" );
            } else {
              $matching_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property'" );
            }
            break;
          }

          //** If comma_and is set, $criteria is ignored, otherwise $criteria is used */
          $property_type_array = isset( $comma_and ) && is_array( $comma_and ) ? $comma_and : array( $specific );

          //** Make sure property type is in slug format */
          foreach( $property_type_array as $key => $this_property_type ) {
            foreach( (array) $wp_properties[ 'property_types' ] as $pt_key => $pt_value ) {
              if( strtolower( $pt_value ) == strtolower( $this_property_type ) ) {
                $property_type_array[ $key ] = $pt_key;
              }
            }
          }

          if( !empty( $property_type_array ) ) {
            //** Multiple types passed */
            $where_string = implode( "' OR meta_value ='", $property_type_array );
          } else {
            //** Only on type passed */
            $where_string = $property_type_array[ 0 ];
          }

          // See if mathinc_ids have already been filtered down
          if( isset( $matching_ids ) ) {
            $matching_id_filter = implode( "' OR post_id ='", $matching_ids );
            $matching_ids       = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE (post_id ='$matching_id_filter' ) AND ( meta_key = 'property_type' AND (meta_value ='$where_string' ))" );
          } else {
            $matching_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = 'property_type' AND (meta_value ='$where_string' ))" );
          }

          break;

        case apply_filters( 'wpp::get_properties::custom_case', false, $meta_key ):

          $matching_ids = apply_filters( 'wpp::get_properties::custom_key', $matching_ids, $meta_key, $criteria );

          break;

        default:

          // Get all properties for that meta_key
          if( $specific == 'all' && empty( $comma_and ) && empty( $hyphen_between ) ) {

            if( isset( $matching_ids ) ) {
              $matching_id_filter = implode( "' OR post_id ='", $matching_ids );
              $matching_ids       = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE (post_id ='$matching_id_filter' ) AND ( meta_key = '$meta_key' )" );
            } else {
              $matching_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = '$meta_key' )" );
            }
            break;

          } else {

            if( !empty( $comma_and ) ) {
              $where_and = "( meta_value ='" . implode( "' OR meta_value ='", $comma_and ) . "')";
              $specific  = $where_and;
            }

            if( !empty( $hyphen_between ) ) {
              // We are going to see if we are looking at some sort of date, in which case we have a special MySQL modifier
              $adate = false;
              if( preg_match( '%\\d{1,2}/\\d{1,2}/\\d{4}%i', $hyphen_between[ 0 ] ) ) $adate = true;

              if( !empty( $hyphen_between[ 1 ] ) ) {

                if( preg_match( '%\\d{1,2}/\\d{1,2}/\\d{4}%i', $hyphen_between[ 1 ] ) ) {
                  foreach( $hyphen_between as $key => $value ) {
                    $hyphen_between[ $key ] = "STR_TO_DATE( '{$value}', '%c/%e/%Y' )";
                  }
                  $where_between = "STR_TO_DATE( `meta_value`, '%c/%e/%Y' ) BETWEEN " . implode( " AND ", $hyphen_between ) . "";
                } else {
                  $where_between = "`meta_value` BETWEEN " . implode( " AND ", $hyphen_between ) . "";
                }

              } else {

                if( $adate ) {
                  $where_between = "STR_TO_DATE( `meta_value`, '%c/%e/%Y' ) >= STR_TO_DATE( '{$hyphen_between[0]}', '%c/%e/%Y' )";
                } else {
                  $where_between = "`meta_value` >= $hyphen_between[0]";
                }

              }
              $specific = $where_between;
            }

            if( $specific == 'true' ) {
              // If properties data were imported, meta value can be '1' instead of 'true'
              // So we're trying to find also '1'
              $specific = "meta_value IN ( 'true', '1' )";
            } elseif( !substr_count( $specific, 'meta_value' ) ) {
              //** Determine if we don't need to use LIKE in SQL query */
              preg_match( "/^#(.+)#$/", $specific, $matches );
              if( $matches ) {
                $specific = " meta_value = '{$matches[1]}'";
              } else {
                //** Adds conditions for Searching by partial value */
                $s        = explode( ' ', trim( $specific ) );
                $specific = '';
                $count    = 0;
                foreach( $s as $p ) {
                  if( $count > 0 ) {
                    $specific .= " AND ";
                  }
                  $specific .= "meta_value LIKE '%{$p}%'";
                  $count++;
                }
              }
            }

            if( isset( $matching_ids ) ) {
              $matching_id_filter = implode( ",", $matching_ids );
              $sql_query          = "SELECT post_id FROM {$wpdb->postmeta} WHERE post_id IN ( $matching_id_filter ) AND meta_key = '$meta_key' AND $specific";
            } else {
              $sql_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '$meta_key' AND $specific";
            }

            //** Some specific additional conditions can be set in filters */
            $sql_query = apply_filters( 'wpp::get_properties::meta_key::sql_query', $sql_query, array(
              'meta_key'           => $meta_key,
              'specific'           => $specific,
              'matching_id_filter' => isset( $matching_id_filter ) ? $matching_id_filter : false,
              'criteria'           => $criteria,
            ) );

            $matching_ids = $wpdb->get_col( $sql_query );

          }
          break;

      } // END switch

      unset( $comma_and );
      unset( $hyphen_between );

    } // END foreach

    // Return false, if there are any result using filter conditions
    if( empty( $matching_ids ) ) {
      return false;
    }

    // Remove duplicates
    $matching_ids = array_unique( $matching_ids );

    $matching_ids = apply_filters( 'wpp::get_properties::matching_ids', $matching_ids, array_merge( (array) $query, array( 
      'additional_sql'  => $additional_sql, 
      'total'           => $total,
    ) ) );
    
    $result = apply_filters( 'wpp::get_properties::custom_sort', false, array(
      'matching_ids'    => $matching_ids,
      'additional_sql'  => $additional_sql,
      'sort_by'         => $sql_sort_by,
      'sort_order'      => $sql_sort_order,
      'limit_query'     => $limit_query,
    ) );
    
    if( !$result ) {
    
      // Sorts the returned Properties by the selected sort order
      if( $sql_sort_by &&
        $sql_sort_by != 'menu_order' &&
        $sql_sort_by != 'post_date' &&
        $sql_sort_by != 'post_title'
      ) {
      
        //** Sorts properties in random order. */
        if( $sql_sort_by === 'random' ) {

          $result = $wpdb->get_col( "
            SELECT ID FROM {$wpdb->posts } AS p
            WHERE ID IN (" . implode( ",", $matching_ids ) . ")
            $additional_sql
            ORDER BY RAND() $sql_sort_order
            $limit_query" );

        } else {

          //** Determine if attribute has numeric format or all values of meta_key are numbers we use CAST in SQL query to avoid sort issues */
          if( ( isset( $wp_properties[ 'numeric_attributes' ] ) && in_array( $sql_sort_by, $wp_properties[ 'numeric_attributes' ] ) ) || 
              self::meta_has_number_data_type( $matching_ids, $sql_sort_by )
          ) {
            $meta_value = "CAST( meta_value AS DECIMAL(20,3 ))";
          } else {
            $meta_value = "meta_value";
          }

          $result = $wpdb->get_col( "
            SELECT p.ID , (SELECT pm.meta_value FROM {$wpdb->postmeta} AS pm WHERE pm.post_id = p.ID AND pm.meta_key = '{$sql_sort_by}' LIMIT 1 ) as meta_value
              FROM {$wpdb->posts} AS p
              WHERE p.ID IN ( " . implode( ",", $matching_ids ) . ")
              {$additional_sql}
              ORDER BY {$meta_value} {$sql_sort_order}
              {$limit_query}" );

        }

      } else {

        $result = $wpdb->get_col( "
          SELECT ID FROM {$wpdb->posts } AS p
          WHERE ID IN (" . implode( ",", $matching_ids ) . ")
          $additional_sql
          ORDER BY $sql_sort_by $sql_sort_order
          $limit_query" );

      }
    
    }

    // Stores the total Properties returned
    if( $total ) {
      $total = count( $wpdb->get_col( "
        SELECT p.ID
          FROM {$wpdb->posts} AS p
          WHERE p.ID IN (" . implode( ",", $matching_ids ) . ")
          {$additional_sql}" ) );
    }

    if( !empty( $result ) ) {
      $return = array();
      if( !empty( $total ) ) {
        $return[ 'total' ]   = $total;
        $return[ 'results' ] = $result;
      } else {
        $return = $result;
      }

      return apply_filters( 'wpp::get_properties::result', $return, $args );
    }

    return false;

  }

  /**
   * Determine if property has children
   *
   * @param int $id
   *
   * @return boolean
   * @author peshkov@UD
   * @since 1.37.5
   */
  static public function has_children( $id ) {

    $children = get_posts( array(
      'post_type'   => 'property',
      'post_parent' => $id
    ) );

    if( !empty( $children ) ) {
      return true;
    }

    return false;
  }

  /**
   * Prepares Request params for get_properties() function
   *
   * @param array $attrs
   * @return array $attrs
   */
  static public function prepare_search_attributes( $attrs ) {
    global $wp_properties;

    $prepared = array();

    $non_numeric_chars = apply_filters( 'wpp_non_numeric_chars', array( '-', '$', ',' ) );

    foreach( $attrs as $search_key => $search_query ) {

      //** Fix search form passed paramters to be usable by get_properties();
      if( is_array( $search_query ) ) {
        //** Array variables are either option lists or minimum and maxim variables
        $stack = array_keys( $search_query );
        if( is_numeric( array_shift( $stack ) ) ) {
          //** get regular arrays (non associative) */
          $search_query = implode( ',', $search_query );
        } elseif( isset( $search_query[ 'options' ] ) && is_array( $search_query[ 'options' ] ) ) {
          //** Get queries with options */
          $search_query = implode( ',', $search_query[ 'options' ] );
        } elseif( in_array( 'min', array_keys( $search_query ) ) ||
          in_array( 'max', array_keys( $search_query ) )
        ) {
          //** Get arrays with minimum and maxim ranges */

          //* There is no range if max value is empty and min value is -1 */
          if( $search_query[ 'min' ] == '-1' && empty( $search_query[ 'max' ] ) ) {
            $search_query = '-1';
          } else {
            //* Set range */
            //** Ranges are always numeric, so we clear it up */
            foreach( $search_query as $range_indicator => $value ) {
              $search_query[ $range_indicator ] = str_replace( $non_numeric_chars, '', $value );
            }

            if( empty( $search_query[ 'min' ] ) && empty( $search_query[ 'max' ] ) ) {
              continue;
            }

            if( empty( $search_query[ 'min' ] ) ) {
              $search_query[ 'min' ] = '0';
            }

            if( empty( $search_query[ 'max' ] ) ) {
              $search_query = $search_query[ 'min' ] . '+';
            } else {
              $search_query = str_replace( $non_numeric_chars, '', $search_query[ 'min' ] ) . '-' . str_replace( $non_numeric_chars, '', $search_query[ 'max' ] );
            }
          }
        }
      }

      if( is_string( $search_query ) ) {
        if( $search_query != '-1' && $search_query != '-' ) {
          $prepared[ $search_key ] = trim( $search_query );
        }
      }

    }

    return $prepared;
  }

  /**
   * Returns array of all values for a particular attribute/meta_key
   */
  static public function get_all_attribute_values( $slug ) {
    global $wpdb;

    // Non post_meta fields
    $non_post_meta = array(
      'post_title',
      'post_status',
      'post_author',
      'post_date'
    );

    if( !in_array( $slug, $non_post_meta ) )
      $prefill_meta = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '$slug'" );
    else
      $prefill_meta = $wpdb->get_col( "SELECT $slug FROM {$wpdb->posts} WHERE post_type = 'property' AND post_status != 'auto-draft'" );
    /**
     * @todo check if this condition is required - Anton Korotkov
     */
    /*if(empty($prefill_meta[0]))
      unset($prefill_meta);*/

    $prefill_meta = apply_filters( 'wpp_prefill_meta', $prefill_meta, $slug );

    if( count( $prefill_meta ) < 1 )
      return false;

    $return = array();
    // Clean up values
    foreach( $prefill_meta as $meta ) {

      if( empty( $meta ) )
        continue;

      $return[ ] = $meta;

    }

    if( !empty( $return ) && !empty( $return ) ) {
      // Remove duplicates
      $return = array_unique( $return );

      sort( $return );

    }

    return $return;

  }

  /**
   * Load property information into an array or an object
   *
   * @version 1.11 Added support for multiple meta values for a given key
   *
   * @since 1.11
   * @version 1.14 - fixed problem with drafts
   * @todo Code pertaining to displaying data should be migrated to prepare_property_for_display() like :$real_value = nl2br($real_value);
   * @todo Fix the long dashes - when in latitude or longitude it breaks it when using static map
   *
   */
  static public function get_property( $id, $args = false ) {
    global $wp_properties, $wpdb;

    $id = trim( $id );

    $defaults = array(
      'get_children'          => 'true',
      'return_object'         => 'false',
      'load_gallery'          => 'true',
      'load_thumbnail'        => 'true',
      'allow_multiple_values' => 'false',
      'load_parent'           => 'true'
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $get_children          = isset( $get_children ) ? $get_children : 'true';
    $return_object         = isset( $return_object ) ? $return_object : 'false';
    $load_gallery          = isset( $load_gallery ) ? $load_gallery : 'true';
    $load_thumbnail        = isset( $load_thumbnail ) ? $load_thumbnail : 'true';
    $allow_multiple_values = isset( $allow_multiple_values ) ? $allow_multiple_values : 'false';
    $load_parent           = isset( $load_parent ) ? $load_parent : 'true';

    $args = is_array( $args ) ? http_build_query( $args ) : (string) $args;
    if( $return = wp_cache_get( $id . $args ) ) {
      return $return;
    }

    $post = get_post( $id, ARRAY_A );

    if( $post[ 'post_type' ] != 'property' ) {
      return false;
    }

    //** Figure out what all the editable attributes are, and get their keys */
    $wp_properties[ 'property_meta' ]  = ( is_array( $wp_properties[ 'property_meta' ] ) ? $wp_properties[ 'property_meta' ] : array() );
    $wp_properties[ 'property_stats' ] = ( is_array( $wp_properties[ 'property_stats' ] ) ? $wp_properties[ 'property_stats' ] : array() );
    $editable_keys                     = array_keys( array_merge( $wp_properties[ 'property_meta' ], $wp_properties[ 'property_stats' ] ) );

    $return = array();

    //** Load all meta keys for this object */
    if( $keys = get_post_custom( $id ) ) {
      foreach( $keys as $key => $value ) {

        if( $allow_multiple_values == 'false' ) {
          $value = $value[ 0 ];
        }

        $keyt = trim( $key );

        //** If has _ prefix it's a built-in WP key */
        if( '_' == $keyt{0} ) {
          continue;
        }

        // Fix for boolean values
        switch( $value ) {

          case 'true':
            $real_value = true; //** Converts all "true" to 1 */
            break;

          case 'false':
            $real_value = false;
            break;

          default:
            $real_value = $value;
            break;

        }

        // Handle keys with multiple values
        if( count( $value ) > 1 ) {
          $return[ $key ] = $value;
        } else {
          $return[ $key ] = $real_value;
        }

      }
    }

    $return = array_merge( $return, $post );

    //** Make sure certain keys were not messed up by custom attributes */
    $return[ 'system' ]  = array();
    $return[ 'gallery' ] = array();

    /*
     * Figure out what the thumbnail is, and load all sizes
     */
    if( $load_thumbnail == 'true' ) {

      $wp_image_sizes = get_intermediate_image_sizes();

      $thumbnail_id = get_post_meta( $id, '_thumbnail_id', true );
      $attachments  = get_children( array( 'post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC' ) );

      if( $thumbnail_id ) {
        foreach( $wp_image_sizes as $image_name ) {
          $this_url                          = wp_get_attachment_image_src( $thumbnail_id, $image_name, true );
          $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
        }

        $featured_image_id = $thumbnail_id;

      } elseif( $attachments ) {
        foreach( $attachments as $attachment_id => $attachment ) {

          foreach( $wp_image_sizes as $image_name ) {
            $this_url                          = wp_get_attachment_image_src( $attachment_id, $image_name, true );
            $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
          }

          $featured_image_id = $attachment_id;
          break;
        }
      }

      if( !empty( $featured_image_id ) ) {
        $return[ 'featured_image' ] = $featured_image_id;

        $image_title = $wpdb->get_var( "SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '{$featured_image_id}' " );

        $return[ 'featured_image_title' ] = $image_title;
        $return[ 'featured_image_url' ]   = wp_get_attachment_url( $featured_image_id );
      }

    }

    /*
    *
    * Load all attached images and their sizes
    *
     */
    if( $load_gallery == 'true' ) {

      // Get gallery images
      if( $attachments ) {
        foreach( $attachments as $attachment_id => $attachment ) {
          $return[ 'gallery' ][ $attachment->post_name ][ 'post_title' ]    = $attachment->post_title;
          $return[ 'gallery' ][ $attachment->post_name ][ 'post_excerpt' ]  = $attachment->post_excerpt;
          $return[ 'gallery' ][ $attachment->post_name ][ 'post_content' ]  = $attachment->post_content;
          $return[ 'gallery' ][ $attachment->post_name ][ 'attachment_id' ] = $attachment_id;
          foreach( $wp_image_sizes as $image_name ) {
            $this_url                                                     = wp_get_attachment_image_src( $attachment_id, $image_name, true );
            $return[ 'gallery' ][ $attachment->post_name ][ $image_name ] = $this_url[ 0 ];
          }
        }
      } else {
        $return[ 'gallery' ] = false;
      }
    }

    /*
    *
    *  Load parent if exists and inherit Parent's atttributes.
    *
    */
    if( $load_parent == 'true' && $post[ 'post_parent' ] ) {

      $return[ 'is_child' ] = true;

      $parent_object = WPP_F::get_property( $post[ 'post_parent' ], array( 'load_gallery' => $load_gallery, 'get_children' => false ) );

      $return[ 'parent_id' ]    = $post[ 'post_parent' ];
      $return[ 'parent_link' ]  = $parent_object[ 'permalink' ];
      $return[ 'parent_title' ] = $parent_object[ 'post_title' ];

      // Inherit things
      if( !empty( $wp_properties[ 'property_inheritance' ][ $return[ 'property_type' ] ] ) ) {
        foreach( (array)$wp_properties[ 'property_inheritance' ][ $return[ 'property_type' ] ] as $inherit_attrib ) {
          if( !empty( $parent_object[ $inherit_attrib ] ) && empty( $return[ $inherit_attrib ] ) ) {
            $return[ $inherit_attrib ] = $parent_object[ $inherit_attrib ];
          }
        }
      }
    }

    /*
    *
    * Load Children and their attributes
    *
    */
    if( $get_children == 'true' ) {

      //** Calculate variables if based off children if children exist */
      $children = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE  post_type = 'property' AND post_status = 'publish' AND post_parent = '{$id}' ORDER BY menu_order ASC " );

      if( count( $children ) > 0 ) {

        $range = array();
      
        //** Cycle through children and get necessary variables */
        foreach( $children as $child_id ) {

          $child_object                      = WPP_F::get_property( $child_id, array( 'load_gallery' => $load_gallery, 'load_parent' => false ) );
          $return[ 'children' ][ $child_id ] = $child_object;

          //** Save child image URLs into one array for quick access */
          if( !empty( $child_object[ 'featured_image_url' ] ) ) {
            $return[ 'system' ][ 'child_images' ][ $child_id ] = $child_object[ 'featured_image_url' ];
          }

          //** Exclude variables from searchable attributes (to prevent ranges) */
          $excluded_attributes    = $wp_properties[ 'geo_type_attributes' ];
          $excluded_attributes[ ] = $wp_properties[ 'configuration' ][ 'address_attribute' ];

          foreach( $wp_properties[ 'searchable_attributes' ] as $searchable_attribute ) {

            $attribute_data = WPP_F::get_attribute_data( $searchable_attribute );

            if( !empty( $attribute_data[ 'numeric' ] ) || !empty( $attribute_data[ 'currency' ] ) ) {

              if( !empty( $child_object[ $searchable_attribute ] ) && !in_array( $searchable_attribute, $excluded_attributes ) ) {
                $range[ $searchable_attribute ][ ] = $child_object[ $searchable_attribute ];
              }

            }
          }
        }

        //* Cycle through every type of range (i.e. price, deposit, bathroom, etc) and fix-up the respective data arrays */
        foreach( (array) $range as $range_attribute => $range_values ) {

          //* Cycle through all values of this range (attribute), and fix any ranges that use dashes */
          foreach( $range_values as $key => $single_value ) {

            //* Remove dollar signs */
            $single_value = str_replace( "$", '', $single_value );

            //* Fix ranges */
            if( strpos( $single_value, '&ndash;' ) ) {

              $split = explode( '&ndash;', $single_value );

              foreach( $split as $new_single_value )

                if( !empty( $new_single_value ) ) {
                  array_push( $range_values, trim( $new_single_value ) );
                }

              //* Unset original value with dash */
              unset( $range_values[ $key ] );

            }
          }

          //* Remove duplicate values from this range */
          $range[ $range_attribute ] = array_unique( $range_values );

          //* Sort the values in this particular range */
          sort( $range[ $range_attribute ] );

          if( count( $range[ $range_attribute ] ) < 2 ) {
            $return[ $range_attribute ] = $return[ $range_attribute ] . ' ( ' . $range[ $range_attribute ][ 0 ] . ' )';
          }

          if( count( $range[ $range_attribute ] ) > 1 ) {
            $return[ $range_attribute ] = $return[ $range_attribute ] . ' ( ' . min( $range[ $range_attribute ] ) . " - " . max( $range[ $range_attribute ] ) . ' )';
          }

          //** If we end up with a range, we make a note of it */
          if( !empty( $return[ $range_attribute ] ) ) {
            $return[ 'system' ][ 'upwards_inherited_attributes' ][ ] = $range_attribute;
          }

        }

      }
    }

    if( !empty( $return[ 'location' ] ) && !in_array( 'address', $editable_keys ) && !isset( $return[ 'address' ] ) ) {
      $return[ 'address' ] = $return[ 'location' ];
    }

    $return[ 'wpp_gpid' ]  = WPP_F::maybe_set_gpid( $id );
    $return[ 'permalink' ] = get_permalink( $id );

    //** Make sure property_type stays as slug, or it will break many things:  (widgets, class names, etc)  */
    if( !empty( $return[ 'property_type' ] ) ) {
      $return[ 'property_type_label' ] = isset( $wp_properties[ 'property_types' ][ $return[ 'property_type' ] ]) ? $wp_properties[ 'property_types' ][ $return[ 'property_type' ] ] : false;
      if( empty( $return[ 'property_type_label' ] ) ) {
        foreach( $wp_properties[ 'property_types' ] as $pt_key => $pt_value ) {
          if( strtolower( $pt_value ) == strtolower( $return[ 'property_type' ] ) ) {
            $return[ 'property_type' ]       = $pt_key;
            $return[ 'property_type_label' ] = $pt_value;
          }
        }
      }
    }

    //** If phone number is not set but set globally, we load it into property array here */
    if( empty( $return[ 'phone_number' ] ) && !empty( $wp_properties[ 'configuration' ][ 'phone_number' ] ) ) {
      $return[ 'phone_number' ] = $wp_properties[ 'configuration' ][ 'phone_number' ];
    }

    if( is_array( $return ) ) {
      ksort( $return );
    }

    $return = apply_filters( 'wpp_get_property', $return );

    //* Get rid of all empty values */
    foreach( $return as $key => $item ) {

      //** Don't blank keys starting w/ post_  - this should be converted to use get_attribute_data() to check where data is stored for better check - potanin@UD */
      if( strpos( $key, 'post_' ) === 0 ) {
        continue;
      }

      if( empty( $item ) ) {
        unset( $return[ $key ] );
      }

    }

    //** Convert to object */
    if( $return_object == 'true' ) {
      $return = WPP_F::array_to_object( $return );
    }

    wp_cache_add( $id . $args, $return );

    return $return;

  }

  /**
   * Gets prefix to an attribute
   *
   * @todo This should be obsolete, in any case we can't assume everyone uses USD - potanin@UD (11/22/11)
   *
   */
  static public function get_attrib_prefix( $attrib ) {

    if( $attrib == 'price' ) {
      return "$";
    }

    if( $attrib == 'deposit' ) {
      return "$";
    }

  }

  /**
   * Gets annex to an attribute. (Unused Function)
   *
   * @todo This function does not seem to be used by anything. potanin@UD (11/12/11)
   *
   */
  static public function get_attrib_annex( $attrib ) {

    if( $attrib == 'area' ) {
      return __( ' sq ft.', 'wpp' );
    }

  }

  /**
   * Get coordinates for property out of database
   *
   */
  static public function get_coordinates( $listing_id = false ) {
    global $post, $property;

    if( !$listing_id ) {
      if( empty( $property ) ) {
        return false;
      }
      $listing_id = is_object( $property ) ? $property->ID : $property[ 'ID' ];
    }

    $latitude  = get_post_meta( $listing_id, 'latitude', true );
    $longitude = get_post_meta( $listing_id, 'longitude', true );

    if( empty( $latitude ) || empty( $longitude ) ) {
      /** Try parent */
      if( !empty( $property->parent_id ) ) {
        $latitude  = get_post_meta( $property->parent_id, 'latitude', true );
        $longitude = get_post_meta( $property->parent_id, 'longitude', true );
      }
      /** Still nothing */
      if( empty( $latitude ) || empty( $longitude ) ) {
        return false;
      }
    }

    return array( 'latitude' => $latitude, 'longitude' => $longitude );
  }

  /**
   * Validate if a URL is valid.
   */
  static public function isURL( $url ) {
    return preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url );
  }

  /**
   * Determine if a email is valid.
   *
   * @param $value
   *
   * @return boolean
   */
  static public function is_email( $value ) {
    return preg_match( '/^[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*(.[a-z]{2,3})$/', strtolower( $value ) );
  }

  /**
   * Returns an array of a property's stats and their values.
   *
   * Query is array of variables to use load ours to avoid breaking property maps.
   *
   * @since 1.0
   *
   */
  static public function get_stat_values_and_labels( $property_object, $args = false ) {
    global $wp_properties;

    $defaults = array(
      'label_as_key' => 'true',
    );

    if( is_array( $property_object ) ) {
      $property_object = (object) $property_object;
    }

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $exclude = isset( $exclude ) ? ( is_array( $exclude ) ? $exclude : explode( ',', $exclude ) ) : false;
    $include = isset( $include ) ? ( is_array( $include ) ? $include : explode( ',', $include ) ) : false;

    if( empty( $property_stats ) ) {
      $property_stats = $wp_properties[ 'property_stats' ];
    }
    
    $return = array();

    foreach( $property_stats as $slug => $label ) {

      // Determine if it's frontend and the attribute is hidden for frontend
      if( 
        isset( $wp_properties[ 'hidden_frontend_attributes' ] ) 
        && in_array( $slug, (array) $wp_properties[ 'hidden_frontend_attributes' ] ) 
        && !current_user_can( 'manage_options' ) 
      ) {
        continue;
      }

      // Exclude passed variables
      if( is_array( $exclude ) && in_array( $slug, $exclude ) ) {
        continue;
      }

      if( !empty( $property_object->{$slug} ) ) {
        $value = $property_object->{$slug};
      } else {
        $value = get_post_meta( $property_object->ID, $slug, true );
      }

      if( $value === true ) {
        $value = 'true';
      }

      //** Override property_type slug with label */
      if( $slug == 'property_type' ) {
        $value = $property_object->property_type_label;
      }

      // Include only passed variables
      if( is_array( $include ) && in_array( $slug, $include ) ) {
        if( !empty( $value ) ) {
          if( $label_as_key == 'true' ) $return[ $label ] = $value;
          else $return[ $slug ] = array( 'label' => $label, 'value' => $value );
        }
        continue;
      }

      if( !is_array( $include ) ) {
        if( !empty( $value ) ) {
          if( $label_as_key == 'true' ) $return[ $label ] = $value;
          else $return[ $slug ] = array( 'label' => $label, 'value' => $value );
        }
      }

    }

    if( count( $return ) > 0 ) {
      return $return;
    }

    return false;

  }

  static public function array_to_object( $array = array() ) {
    if( is_array( $array ) ) {
      $data = new stdClass();

      foreach( $array as $akey => $aval ) {
        $data->{$akey} = $aval;
      }

      return $data;
    }

    return (object) false;
  }

  /**
   * Returns a minified Google Maps Infobox
   *
   * Used in property map and supermap
   *
   * @filter wpp_google_maps_infobox
   * @version 1.11 - added return if $post or address attribute are not set to prevent fatal error
   * @since 1.081
   *
   */
  static public function google_maps_infobox( $post, $args = false ) {
    global $wp_properties;

    $map_image_type     = $wp_properties[ 'configuration' ][ 'single_property_view' ][ 'map_image_type' ];
    $infobox_attributes = $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_attributes' ];
    $infobox_settings   = $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ];

    if( empty( $wp_properties[ 'configuration' ][ 'address_attribute' ] ) ) {
      return;
    }

    if( empty( $post ) ) {
      return;
    }

    if( is_array( $post ) ) {
      $post = (object) $post;
    }

    $property = (array) prepare_property_for_display( $post, array(
      'load_gallery' => 'false',
      'scope'        => 'google_map_infobox'
    ) );

    //** Check if we have children */
    if( 
      !empty( $property[ 'children' ] ) 
      && ( !isset( $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'do_not_show_child_properties' ] )
      || $wp_properties[ 'configuration' ][ 'google_maps' ][ 'infobox_settings' ][ 'do_not_show_child_properties' ] != 'true' )
    ) {
      foreach( $property[ 'children' ] as $child_property ) {
        $child_property           = (array) $child_property;
        $html_child_properties[ ] = '<li class="infobox_child_property"><a href="' . $child_property[ 'permalink' ] . '">' . $child_property[ 'post_title' ] . '</a></li>';
      }
    }

    if( empty( $infobox_attributes ) ) {
      $infobox_attributes = array(
        'price',
        'bedrooms',
        'bathrooms' );
    }

    if( empty( $infobox_settings ) ) {
      $infobox_settings = array(
        'show_direction_link' => true,
        'show_property_title' => true
      );
    }

    $infobox_style = ( !empty( $infobox_settings[ 'minimum_box_width' ] ) ) ? 'style="min-width: ' . $infobox_settings[ 'minimum_box_width' ] . 'px;"' : '';

    foreach( $infobox_attributes as $attribute ) {
      $property_stats[ $attribute ] = $wp_properties[ 'property_stats' ][ $attribute ];
    }

    $property_stats = WPP_F::get_stat_values_and_labels( $property, array(
      'property_stats' => $property_stats
    ) );

    if( !empty( $property[ 'featured_image' ] ) ) {
      $image = wpp_get_image_link( $property[ 'featured_image' ], $map_image_type, array( 'return' => 'array' ) );
      if( !empty( $image ) && is_array( $image ) ) {
        $imageHTML = "<img width=\"{$image['width']}\" height=\"{$image['height']}\" src=\"{$image['link']}\" alt=\"" . addslashes( $post->post_title ) . "\" />";
        if( @$wp_properties[ 'configuration' ][ 'property_overview' ][ 'fancybox_preview' ] == 'true' && !empty( $property[ 'featured_image_url' ] ) ) {
          $imageHTML = "<a href=\"{$property['featured_image_url']}\" class=\"fancybox_image thumbnail\">{$imageHTML}</a>";
        }
      }
    }

    ob_start(); ?>

    <div id="infowindow" <?php echo $infobox_style; ?>>
      <?php if( $infobox_settings[ 'show_property_title' ] == 'true' ) { ?>
        <div class="wpp_google_maps_attribute_row_property_title">
          <a href="<?php echo get_permalink( $property[ 'ID' ] ); ?>"><?php echo $property[ 'post_title' ]; ?></a>
        </div>
      <?php } ?>

      <table cellpadding="0" cellspacing="0" class="wpp_google_maps_infobox_table" style="">
        <tr>
          <?php if( !empty( $imageHTML ) ) { ?>
            <td class="wpp_google_maps_left_col" style=" width: <?php echo $image[ 'width' ]; ?>px">
              <?php echo $imageHTML; ?>
              <?php if( $infobox_settings[ 'show_direction_link' ] == 'true' ): ?>
                <div class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_directions_link">
                  <a target="_blank"
                    href="http://maps.google.com/maps?gl=us&daddr=<?php echo addslashes( str_replace( ' ', '+', $property[ 'formatted_address' ] ) ); ?>"
                    class="btn btn-info"><?php _e( 'Get Directions', 'wpp' ) ?></a>
                </div>
              <?php endif; ?>
            </td>
          <?php } ?>

          <td class="wpp_google_maps_right_col" vertical-align="top" style="vertical-align: top;">
            <?php if( !empty( $imageHTML ) && $infobox_settings[ 'show_direction_link' ] == 'true' ) { ?>
              <div class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_directions_link">
                <a target="_blank"
                  href="http://maps.google.com/maps?gl=us&daddr=<?php echo addslashes( str_replace( ' ', '+', $property[ 'formatted_address' ] ) ); ?>"
                  class="btn btn-info"><?php _e( 'Get Directions', 'wpp' ) ?></a>
              </div>
            <?php
            }

            $attributes = array();

            $labels_to_keys = array_flip( $wp_properties[ 'property_stats' ] );

            if( is_array( $property_stats ) ) {
              foreach( $property_stats as $attribute_label => $value ) {

                $attribute_slug = $labels_to_keys[ $attribute_label ];
                $attribute_data = WPP_F::get_attribute_data( $attribute_slug );

                if( empty( $value ) ) {
                  continue;
                }

                if( ( $attribute_data[ 'data_input_type' ] == 'checkbox' && ( $value == 'true' || $value == 1 ) ) ) {
                  if( $wp_properties[ 'configuration' ][ 'google_maps' ][ 'show_true_as_image' ] == 'true' ) {
                    $value = '<div class="true-checkbox-image"></div>';
                  } else {
                    $value = __( 'Yes', 'wpp' );
                  }
                } elseif( $value == 'false' ) {
                  continue;
                }

                $attributes[ ] = '<li class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_' . $attribute_slug . '">';
                $attributes[ ] = '<span class="attribute">' . $attribute_label . '</span>';
                $attributes[ ] = '<span class="value">' . $value . '</span>';
                $attributes[ ] = '</li>';
              }
            }

            if( count( $attributes ) > 0 ) {
              echo '<ul class="wpp_google_maps_infobox">' . implode( '', $attributes ) . '<li class="wpp_google_maps_attribute_row wpp_fillter_element">&nbsp;</li></ul>';
            }

            if( !empty( $html_child_properties ) ) {
              echo '<ul class="infobox_child_property_list">' . implode( '', $html_child_properties ) . '<li class="infobox_child_property wpp_fillter_element">&nbsp;</li></ul>';
            }

            ?>

          </td>
        </tr>
      </table>

    </div>

    <?php
    $data = ob_get_contents();
    $data = preg_replace( array( '/[\r\n]+/' ), array( "" ), $data );
    $data = addslashes( $data );

    ob_end_clean();

    $data = apply_filters( 'wpp_google_maps_infobox', $data, $post );

    return $data;
  }

  /**
   * Updates parent ID.
   * Determines if parent exists and it doesn't have own parent.
   *
   * @param integer $parent_id
   * @param integer $post_id
   *
   * @return int
   * @author peshkov@UD
   * @since 1.37.5
   */
  static public function update_parent_id( $parent_id, $post_id ) {
    global $wpdb, $wp_properties;

    $parent_id = !empty( $parent_id ) ? $parent_id : 0;

    $post = get_post( $_REQUEST[ 'parent_id' ] );

    if( !$post ) {
      $parent_id = 0;
    } else {
      if( $post->post_parent > 0 ) {
        if( empty( $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] ) || $wp_properties[ 'configuration' ][ 'allow_parent_deep_depth' ] != 'true' ) {
          $parent_id = 0;
        }
      }
    }

    if( $parent_id == 0 ) {
      $wpdb->query( "UPDATE {$wpdb->posts} SET post_parent=0 WHERE ID={$post_id}" );
    }

    update_post_meta( $post_id, 'parent_gpid', WPP_F::maybe_set_gpid( $parent_id ) );

    return $parent_id;
  }

  /**
   * Returns property object for displaying on map
   *
   * Used for speeding up property queries, only returns:
   * ID, post_title, atitude, longitude, exclude_from_supermap, location, supermap display_attributes and featured image urls
   *
   * 1.11: addded htmlspecialchars and addslashes to post_title
   *
   * @since 1.11
   *
   */
  static public function get_property_map( $id, $args = '' ) {
    global $wp_properties, $wpdb;

    $defaults = array(
      'thumb_type'     => ( !empty( $wp_properties[ 'feature_settings' ][ 'supermap' ][ 'supermap_thumb' ] ) ? $wp_properties[ 'feature_settings' ][ 'supermap' ][ 'supermap_thumb' ] : 'thumbnail' ),
      'return_object'  => 'false',
      'map_image_type' => $wp_properties[ 'configuration' ][ 'single_property_view' ][ 'map_image_type' ]
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if( class_exists( 'class_wpp_supermap' ) )
      $display_attributes = $wp_properties[ 'configuration' ][ 'feature_settings' ][ 'supermap' ][ 'display_attributes' ];

    $return[ 'ID' ] = $id;

    $data = $wpdb->get_results( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = $id GROUP BY meta_key" );

    foreach( $data as $row ) {
      $return[ $row->meta_key ] = $row->meta_value;
    }

    $return[ 'post_title' ] = htmlspecialchars( addslashes( $wpdb->get_var( "SELECT post_title FROM {$wpdb->posts} WHERE ID = $id" ) ) );

    // Get Images
    $wp_image_sizes = get_intermediate_image_sizes();

    $thumbnail_id = get_post_meta( $id, '_thumbnail_id', true );
    $attachments  = get_children( array( 'post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC' ) );

    if( $thumbnail_id ) {
      foreach( $wp_image_sizes as $image_name ) {
        $this_url                          = wp_get_attachment_image_src( $thumbnail_id, $image_name, true );
        $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
      }

      $featured_image_id = $thumbnail_id;

    } elseif( $attachments ) {
      foreach( $attachments as $attachment_id => $attachment ) {

        foreach( $wp_image_sizes as $image_name ) {
          $this_url                          = wp_get_attachment_image_src( $attachment_id, $image_name, true );
          $return[ 'images' ][ $image_name ] = $this_url[ 0 ];
        }

        $featured_image_id = $attachment_id;
        break;
      }
    }

    if( $featured_image_id ) {
      $return[ 'featured_image' ] = $featured_image_id;

      $image_title = $wpdb->get_var( "SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '$featured_image_id' " );

      $return[ 'featured_image_title' ] = $image_title;
      $return[ 'featured_image_url' ]   = wp_get_attachment_url( $featured_image_id );

    }

    return $return;

  }

  /**
   * Generates Global Property ID for standard reference point during imports.
   *
   * Property ID is currently not used.
   *
   * @return integer. Global ID number
   *
   * @param bool|int $property_id . Property ID.
   *
   * @param bool     $check_existance
   *
   * @todo API call to UD server to verify there is no duplicates
   * @since 1.6
   */
  static public function get_gpid( $property_id = false, $check_existance = false ) {

    if( $check_existance && $property_id ) {
      $exists = get_post_meta( $property_id, 'wpp_gpid', true );

      if( $exists ) {
        return $exists;
      }
    }

    return 'gpid_' . rand( 1000000000, 9999999999 );

  }

  /**
   * Generates Global Property ID if it does not exist
   *
   * @param bool $property_id
   *
   * @return string | Returns GPID
   * @since 1.6
   */
  static public function maybe_set_gpid( $property_id = false ) {

    if( !$property_id ) {
      return false;
    }

    $exists = get_post_meta( $property_id, 'wpp_gpid', true );

    if( $exists ) {
      return $exists;
    }

    $gpid = WPP_F::get_gpid( $property_id, true );

    update_post_meta( $property_id, 'wpp_gpid', $gpid );

    return $gpid;

    return false;

  }

  /**
   * Returns post_id fro GPID if it exists
   *
   * @since 1.6
   */
  static public function get_property_from_gpid( $gpid = false ) {
    global $wpdb;

    if( !$gpid ) {
      return false;
    }

    $post_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id  WHERE meta_key = 'wpp_gpid' AND meta_value = '{$gpid}' " );

    if( is_numeric( $post_id ) ) {
      return $post_id;
    }

    return false;

  }

  /**
   * This static function is not actually used, it's only use to hold some common translations that may be used by our themes.
   *
   * Translations for Denali theme.
   *
   * @since 1.14
   *
   */
  static public function strings_for_translations() {

    __( 'General Settings', 'wpp' );
    __( 'Find your property', 'wpp' );
    __( 'Edit', 'wpp' );
    __( 'City', 'wpp' );
    __( 'Contact us', 'wpp' );
    __( 'Login', 'wpp' );
    __( 'Explore', 'wpp' );
    __( 'Message', 'wpp' );
    __( 'Phone Number', 'wpp' );
    __( 'Name', 'wpp' );
    __( 'E-mail', 'wpp' );
    __( 'Send Message', 'wpp' );
    __( 'Submit Inquiry', 'wpp' );
    __( 'Inquiry', 'wpp' );
    __( 'Comment About', 'wpp' );
    __( 'Inquire About', 'wpp' );
    __( 'Inquiry About:', 'wpp' );
    __( 'Inquiry message:', 'wpp' );
    __( 'You forgot to enter your e-mail.', 'wpp' );
    __( 'You forgot to enter a message.', 'wpp' );
    __( 'You forgot to enter your  name.', 'wpp' );
    __( 'Error with sending message. Please contact site administrator.', 'wpp' );
    __( 'Thank you for your message.', 'wpp' );
  }

  /**
   * Determine if all values of meta key have 'number type'
   * If yes, returns boolean true
   *
   * @param mixed  $property_ids
   * @param string $meta_key
   *
   * @return boolean
   * @since 1.16.2
   * @author Maxim Peshkov
   */
  static public function meta_has_number_data_type( $property_ids, $meta_key ) {
    global $wpdb;

    /* There is no sense to continue if no ids */
    if( empty( $property_ids ) ) {
      return false;
    }

    if( is_array( $property_ids ) ) {
      $property_ids = implode( ",", $property_ids );
    }

    $values = $wpdb->get_col( "
      SELECT pm.meta_value
      FROM {$wpdb->prefix}posts AS p
      JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = p.ID
        WHERE p.ID IN (" . $property_ids . ")
          AND p.post_status = 'publish'
          AND pm.meta_key = '$meta_key'
    " );

    foreach( $values as $value ) {
      $value = trim( $value );

      //** Hack for child properties. Skip values with dashes */
      if( empty( $value ) || strstr( $value, '&ndash;' ) || strstr( $value, '–' ) ) {
        continue;
      }

      preg_match( '#^[\d,\.\,]+$#', $value, $matches );
      if( empty( $matches ) ) {
        return false;
      }
    }

    return true;
  }

  /**
   * Function for displaying WPP Data Table rows
   *
   * Ported from WP-CRM
   *
   * @since 3.0
   *
   */
  static public function list_table() {
    global $current_screen;

    include WPP_Path . 'core/ui/class_wpp_object_list_table.php';

    //** Get the paramters we care about */
    $sEcho         = $_REQUEST[ 'sEcho' ];
    $per_page      = $_REQUEST[ 'iDisplayLength' ];
    $iDisplayStart = $_REQUEST[ 'iDisplayStart' ];
    $iColumns      = $_REQUEST[ 'iColumns' ];
    $sColumns      = $_REQUEST[ 'sColumns' ];
    $order_by      = $_REQUEST[ 'iSortCol_0' ];
    $sort_dir      = $_REQUEST[ 'sSortDir_0' ];
    //$current_screen = $wpi_settings['pages']['main'];

    //** Parse the serialized filters array */
    parse_str( $_REQUEST[ 'wpp_filter_vars' ], $wpp_filter_vars );
    $wpp_search = $wpp_filter_vars[ 'wpp_search' ];

    $sColumns = explode( ",", $sColumns );

    //* Init table object */
    $wp_list_table = new WPP_Object_List_Table( array(
      "ajax"           => true,
      "per_page"       => $per_page,
      "iDisplayStart"  => $iDisplayStart,
      "iColumns"       => $iColumns,
      "current_screen" => 'property_page_all_properties'
    ) );

    if( in_array( $sColumns[ $order_by ], $wp_list_table->get_sortable_columns() ) ) {
      $wpp_search[ 'sorting' ] = array(
        'order_by' => $sColumns[ $order_by ],
        'sort_dir' => $sort_dir
      );
    }

    $wp_list_table->prepare_items( $wpp_search );

    //print_r( $wp_list_table ); die();

    if( $wp_list_table->has_items() ) {
      foreach( $wp_list_table->items as $count => $item ) {
        $data[ ] = $wp_list_table->single_row( $item );
      }
    } else {
      $data[ ] = $wp_list_table->no_items();
    }

    //print_r( $data );

    return json_encode( array(
      'sEcho'                => $sEcho,
      'iTotalRecords'        => count( $wp_list_table->all_items ),
      // @TODO: Why iTotalDisplayRecords has $wp_list_table->all_items value ? Maxim Peshkov
      'iTotalDisplayRecords' => count( $wp_list_table->all_items ),
      'aaData'               => $data
    ) );
  }

  /**
   * Get Search filter fields
   *
   */
  static public function get_search_filters() {
    global $wp_properties;

    $filters       = array();
    $filter_fields = array(
      'property_type' => array(
        'type'  => 'multi_checkbox',
        'label' => __( 'Type', 'wpp' )
      ),
      'featured'      => array(
        'type'  => 'multi_checkbox',
        'label' => __( 'Featured', 'wpp' )
      ),
      'post_status'   => array(
        'default' => 'publish',
        'type'    => 'radio',
        'label'   => __( 'Status', 'wpp' )
      ),
      'post_author'   => array(
        'default' => '0',
        'type'    => 'dropdown',
        'label'   => __( 'Author', 'wpp' )
      ),
      'post_date'     => array(
        'default' => '',
        'type'    => 'dropdown',
        'label'   => __( 'Date', 'wpp' )
      )

    );

    foreach( $filter_fields as $slug => $field ) {

      $f = array();

      switch( $field[ 'type' ] ) {

        default:
          break;

        case 'input':
          break;

        case 'multi_checkbox':
        case 'range_dropdown':
        case 'dropdown':
        case 'radio':
          $attr_values = self::get_all_attribute_values( $slug );
          break;

      }

      $f = $field;

      switch( $slug ) {

        default:
          break;

        case 'property_type':

          if( !empty( $wp_properties[ 'property_types' ] ) ) {
            $attrs = array();
            if( is_array( $attr_values ) ) {
              foreach( $attr_values as $attr ) {
                if( !empty( $wp_properties[ 'property_types' ][ $attr ] ) ) {
                  $attrs[ $attr ] = $wp_properties[ 'property_types' ][ $attr ];
                }
              }
            }
          }
          $attr_values = $attrs;

          break;

        case 'featured':

          $attrs = array();
          if( is_array( $attr_values ) ) {
            foreach( $attr_values as $attr ) {
              $attrs[ $attr ] = $attr == 'true' ? __( 'Yes', 'wpp' ) : __( 'No', 'wpp' );
            }
          }
          $attr_values = $attrs;

          break;

        case 'post_status':
          $all   = 0;
          $attrs = array();
          if( is_array( $attr_values ) ) {
            foreach( $attr_values as $attr ) {
              $count = self::get_properties_quantity( array( $attr ) );
              switch( $attr ) {
                case 'publish':
                  $label = __( 'Published', 'wpp' );
                  break;
                case 'pending':
                  $label = __( 'Pending', 'wpp' );
                  break;
                case 'trash':
                  $label = __( 'Trashed', 'wpp' );
                  break;
                default:
                  $label = strtoupper( substr( $attr, 0, 1 ) ) . substr( $attr, 1, strlen( $attr ) );
              }
              $attrs[ $attr ] = $label . ' (' . WPP_F::format_numeric( $count ) . ')';
              $all += $count;
            }
          }

          $attrs[ 'all' ] = __( 'All', 'wpp' ) . ' (' . WPP_F::format_numeric( $all ) . ')';
          $attr_values    = $attrs;

          ksort( $attr_values );

          break;

        case 'post_author':

          $attr_values      = self::get_users_of_post_type( 'property' );
          $attr_values[ 0 ] = __( 'Any', 'wpp' );

          ksort( $attr_values );

          break;

        case 'post_date':

          $attr_values       = array();
          $attr_values[ '' ] = __( 'Show all dates', 'wpp' );

          $attrs = self::get_property_month_periods();

          foreach( $attrs as $value => $attr ) {
            $attr_values[ $value ] = $attr;
          }

          break;

      }

      if( !empty( $attr_values ) ) {

        $f[ 'values' ]    = $attr_values;
        $filters[ $slug ] = $f;

      }

    }

    $filters = apply_filters( "wpp_get_search_filters", $filters );

    return $filters;
  }

  /**
   * Returns users' ids of post type
   *
   * @global object $wpdb
   *
   * @param string  $post_type
   *
   * @return array
   */
  static public function get_users_of_post_type( $post_type ) {
    global $wpdb;

    switch( $post_type ) {

      case 'property':
        $results = $wpdb->get_results( $wpdb->prepare( "
          SELECT DISTINCT u.ID, u.display_name
          FROM {$wpdb->posts} AS p
          JOIN {$wpdb->users} AS u ON u.ID = p.post_author
          WHERE p.post_type = '%s'
            AND p.post_status != 'auto-draft'
          ", $post_type ), ARRAY_N );
        break;

      default:
        break;
    }

    if( empty( $results ) ) {
      return false;
    }

    $users = array();
    foreach( $results as $result ) {
      $users[ $result[ 0 ] ] = $result[ 1 ];
    }

    $users = apply_filters( 'wpp_get_users_of_post_type', $users, $post_type );

    return $users;
  }

  /**
   * Process bulk actions
   */
  static public function property_page_all_properties_load() {

    if( !empty( $_REQUEST[ 'action' ] ) && !empty( $_REQUEST[ 'post' ] ) ) {

      switch( $_REQUEST[ 'action' ] ) {

        case 'trash':
          foreach( $_REQUEST[ 'post' ] as $post_id ) {
            $post_id = (int) $post_id;
            wp_trash_post( $post_id );
          }
          break;

        case 'untrash':
          foreach( $_REQUEST[ 'post' ] as $post_id ) {
            $post_id = (int) $post_id;
            wp_untrash_post( $post_id );
          }
          break;

        case 'delete':
          foreach( $_REQUEST[ 'post' ] as $post_id ) {
            $post_id = (int) $post_id;
            if( get_post_status( $post_id ) == 'trash' ) {
              wp_delete_post( $post_id );
            } else {
              wp_trash_post( $post_id );
            }
          }
          break;

        default:
          //** Any custom action can be processed using action hook */
          do_action( 'wpp::all_properties::process_bulk_action', $_REQUEST[ 'action' ] );
          break;

      }

    }

    /** Screen Options */
    add_screen_option( 'layout_columns', array( 'max' => 2, 'default' => 2 ) );

    //** Default Help items */
    $contextual_help[ 'General Help' ][ ] = '<h3>' . __( 'General Help', 'wpp' ) . '</h3>';
    $contextual_help[ 'General Help' ][ ] = '<p>' . __( 'Comming soon...', 'wpp' ) . '</p>';

    //** Hook this action is you want to add info */
    $contextual_help = apply_filters( 'property_page_all_properties_help', $contextual_help );

    do_action( 'wpp_contextual_help', array( 'contextual_help' => $contextual_help ) );

  }

  /**
   * Settings page load handler
   *
   * @author korotkov@ud
   */
  static public function property_page_property_settings_load() {

    //** Default Help items */
    $contextual_help[ 'Main' ][ ] = '<h3>' . __( 'Default Properties Page', 'wpp' ) . '</h3>';
    $contextual_help[ 'Main' ][ ] = '<p>' . __( 'The default <b>property page</b> will be used to display property search results, as well as be the base for property URLs. ', 'wpp' ) . '</p>';
    $contextual_help[ 'Main' ][ ] = '<p>' . __( 'By default, the <b>Default Properties Page</b> is set to <b>properties</b>, which is a dynamically created page used for displaying property search results. ', 'wpp' ) . '</p>';
    $contextual_help[ 'Main' ][ ] = '<p>' . __( 'We recommend you create an actual WordPress page to be used as the <b>Default Properties Page</b>. For example, you may create a root page called "Real Estate" - the URL of the default property page will be ' . get_bloginfo( 'url' ) . '<b>/real_estate/</b>, and you properties will have the URLs of ' . get_bloginfo( 'url' ) . '/real_estate/<b>property_name</b>/', 'wpp' ) . '</p>';

    $contextual_help[ 'Display' ][ ] = '<h3>' . __( 'Display', 'wpp' ) . '</h3>';
    $contextual_help[ 'Display' ][ ] = '<p>' . __( 'This tab allows you to do many things. Make custom picture sizes that will let you to make posting pictures easier. Change the way you view property photos with the use of Fancy Box, Choose  to use pagination on the bottom of property pages and whether or not to show child properties. Manage Google map attributes and map thumbnail sizes. Select here which attributes you want to show once a property is pin pointed on your map. Change your currency and placement of symbols.', 'wpp' ) . '</p>';

    $contextual_help[ 'Premium Features' ][ ] = '<h3>' . __( 'Premium Features', 'wpp' ) . '</h3>';
    $contextual_help[ 'Premium Features' ][ ] = '<p>' . __( 'Tab allows you to manage your WP-Property Premium Features', 'wpp' ) . '</p>';

    $contextual_help[ 'Help' ][ ] = '<h3>' . __( 'Help', 'wpp' ) . '</h3>';
    $contextual_help[ 'Help' ][ ] = '<p>' . __( 'This tab will help you troubleshoot your plugin, do exports and check for updates for Premium Features', 'wpp' ) . '</p>';

    //** Hook this action is you want to add info */
    $contextual_help = apply_filters( 'property_page_property_settings_help', $contextual_help );

    $contextual_help[ 'More Help' ][ ] = '<h3>' . __( 'More Help', 'wpp' ) . '</h3>';
    $contextual_help[ 'More Help' ][ ] = '<p>' . __( 'Visit <a target="_blank" href="https://usabilitydynamics.com/products/wp-property/">WP-Property Help Page</a> on UsabilityDynamics.com for more help.', 'wpp' ) . '</>';

    do_action( 'wpp_contextual_help', array( 'contextual_help' => $contextual_help ) );

  }

  /**
   * Returns custom default coordinates
   *
   * @global array $wp_properties
   * @return array
   * @author peshkov@UD
   * @since 1.37.6
   */
  static public function get_default_coordinates() {
    global $wp_properties;

    $coords = $wp_properties[ 'default_coords' ];

    if( !empty( $wp_properties[ 'custom_coords' ][ 'latitude' ] ) ) {
      $coords[ 'latitude' ] = $wp_properties[ 'custom_coords' ][ 'latitude' ];
    }

    if( !empty( $wp_properties[ 'custom_coords' ][ 'longitude' ] ) ) {
      $coords[ 'longitude' ] = $wp_properties[ 'custom_coords' ][ 'longitude' ];
    }

    return $coords;
  }

  /**
   * Counts properties by post types
   *
   * @global object $wpdb
   *
   * @param array   $post_status
   *
   * @return int
   */
  static public function get_properties_quantity( $post_status = array( 'publish' ) ) {
    global $wpdb;

    $results = $wpdb->get_col( "
      SELECT ID
      FROM {$wpdb->posts}
      WHERE post_status IN ('" . implode( "','", $post_status ) . "')
        AND post_type = 'property'
    " );

    $results = apply_filters( 'wpp_get_properties_quantity', $results, $post_status );

    return count( $results );

  }

  /**
   * Returns month periods of properties
   *
   * @global object $wpdb
   * @global object $wp_locale
   * @return array
   */
  static public function get_property_month_periods() {
    global $wpdb, $wp_locale;

    $months = $wpdb->get_results( "
      SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
      FROM $wpdb->posts
      WHERE post_type = 'property'
        AND post_status != 'auto-draft'
      ORDER BY post_date DESC
    " );

    $months = apply_filters( 'wpp_get_property_month_periods', $months );

    $results = array();

    foreach( $months as $date ) {

      $month = zeroise( $date->month, 2 );
      $year  = $date->year;

      $results[ $date->year . $month ] = $wp_locale->get_month( $month ) . " $year";

    }

    return $results;

  }

  /**
   * Deletes directory recursively
   *
   * @param string $dirname
   *
   * @return bool
   * @author korotkov@ud
   */
  static public function delete_directory( $dirname ) {

    if( is_dir( $dirname ) )
      $dir_handle = opendir( $dirname );

    if( !$dir_handle )
      return false;

    while( $file = readdir( $dir_handle ) ) {
      if( $file != "." && $file != ".." ) {

        if( !is_dir( $dirname . "/" . $file ) )
          unlink( $dirname . "/" . $file );
        else
          delete_directory( $dirname . '/' . $file );

      }
    }

    closedir( $dir_handle );

    return rmdir( $dirname );

  }

  /**
   * Prevent Facebook integration if 'Facebook Tabs' did not installed.
   *
   * @author korotkov@ud
   */
  static public function check_facebook_tabs() {
    //** Check if FB Tabs is not installed to prevent an ability to use WPP as Facebook App or Page Tab */
    if( !class_exists( 'class_wpp_facebook_tabs' ) ) {

      //** If request goes really from Facebook */
      if( !empty( $_REQUEST[ 'signed_request' ] ) && strstr( $_SERVER[ 'HTTP_REFERER' ], 'facebook.com' ) ) {

        //** Show message */
        die( sprintf( __( 'You cannot use your site as Facebook Application. You should <a href="%s">purchase</a> WP-Property Premium Feature "Facebook Tabs" to manage your Facebook Tabs.', 'wpp' ), 'https://usabilitydynamics.com/products/wp-property/premium/' ) );
      }
    }
  }

  /**
   * Formats phone number for display
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string $phone_number
   *
   * @return string $phone_number
   */
  static public function format_phone_number( $phone_number ) {

    $phone_number = ereg_replace( "[^0-9]", '', $phone_number );
    if( strlen( $phone_number ) != 10 ) return ( False );
    $sArea        = substr( $phone_number, 0, 3 );
    $sPrefix      = substr( $phone_number, 3, 3 );
    $sNumber      = substr( $phone_number, 6, 4 );
    $phone_number = "(" . $sArea . ") " . $sPrefix . "-" . $sNumber;

    return $phone_number;
  }

  /**
   * Shorthand function for drawing checkbox input fields.
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string $args List of arguments to overwrite the defaults.
   * @param bool   $checked Option, default is false. Whether checkbox is checked or not.
   *
   * @return string Checkbox input field and hidden field with the opposive value
   */
  static public function checkbox( $args = '', $checked = false ) {
    $defaults = array(
      'name'      => '',
      'id'        => false,
      'class'     => false,
      'group'     => false,
      'special'   => '',
      'value'     => 'true',
      'label'     => false,
      'maxlength' => false
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $name      = isset( $name ) ? $name : '';
    $id        = isset( $id ) ? $id : false;
    $class     = isset( $class ) ? $class : false;
    $group     = isset( $group ) ? $group : false;
    $special   = isset( $special ) ? $special : '';
    $value     = isset( $value ) ? $value : 'true';
    $label     = isset( $label ) ? $label : false;
    $maxlength = isset( $maxlength ) ? $maxlength : false;

    // Get rid of all brackets
    if( strpos( "$name", '[' ) || strpos( "$name", ']' ) ) {

      $class_from_name = $name;

      //** Remove closing empty brackets to avoid them being displayed as __ in class name */
      $class_from_name = str_replace( '][]', '', $class_from_name );

      $replace_variables = array( '][', ']', '[' );
      $class_from_name   = 'wpp_' . str_replace( $replace_variables, '_', $class_from_name );
    } else {
      $class_from_name = 'wpp_' . $name;
    }

    // Setup Group
    if( $group ) {
      if( strpos( $group, '|' ) ) {
        $group_array  = explode( "|", $group );
        $count        = 0;
        $group_string = '';
        foreach( $group_array as $group_member ) {
          $count++;
          if( $count == 1 ) {
            $group_string .= $group_member;
          } else {
            $group_string .= "[{$group_member}]";
          }
        }
      } else {
        $group_string = $group;
      }
    }

    if( is_array( $checked ) ) {

      if( in_array( $value, $checked ) ) {
        $checked = true;
      } else {
        $checked = false;
      }
    } else {
      $checked = strtolower( $checked );
      if( $checked == 'yes' ) $checked = 'true';
      if( $checked == 'true' ) $checked = 'true';
      if( $checked == 'no' ) $checked = false;
      if( $checked == 'false' ) $checked = false;
    }

    $id               = ( $id ? $id : $class_from_name );
    $insert_id        = ( $id ? " id='$id' " : " id='$class_from_name' " );
    $insert_name      = ( isset( $group_string ) ? " name='" . $group_string . "[$name]' " : " name='$name' " );
    $insert_checked   = ( $checked ? " checked='checked' " : " " );
    $insert_value     = " value=\"$value\" ";
    $insert_class     = " class='$class_from_name $class wpp_checkbox " . ( $group ? 'wpp_' . $group . '_checkbox' : '' ) . "' ";
    $insert_maxlength = ( $maxlength ? " maxlength='$maxlength' " : " " );

    $opposite_value = '';

    // Determine oppositve value
    switch( $value ) {
      case 'yes':
        $opposite_value = 'no';
        break;

      case 'true':
        $opposite_value = 'false';
        break;

      case 'open':
        $opposite_value = 'closed';
        break;

    }

    $return = '';

    // Print label if one is set
    if( $label ) $return .= "<label for='$id'>";

    // Print hidden checkbox if there is an opposite value */
    if( $opposite_value ) {
      $return .= '<input type="hidden" value="' . $opposite_value . '" ' . $insert_name . ' />';
    }

    // Print checkbox
    $return .= "<input type='checkbox' $insert_name $insert_id $insert_class $insert_checked $insert_maxlength  $insert_value $special />";
    if( $label ) $return .= " $label</label>";

    return $return;
  }

  /**
   * Shorthand function for drawing a textarea
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string $args List of arguments to overwrite the defaults.
   *
   * @return string Input field and hidden field with the opposive value
   */
  static public function textarea( $args = '' ) {
    $defaults = array( 'name' => '', 'id' => false, 'checked' => false, 'class' => false, 'style' => false, 'group' => '', 'special' => '', 'value' => '', 'label' => false, 'maxlength' => false );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $name      = isset( $name ) ? $name : '';
    $id        = isset( $id ) ? $id : false;
    $checked   = isset( $checked ) ? $checked : false;
    $class     = isset( $class ) ? $class : false;
    $style     = isset( $style ) ? $style : false;
    $group     = isset( $group ) ? $group : '';
    $special   = isset( $special ) ? $special : '';
    $value     = isset( $value ) ? $value : '';
    $label     = isset( $label ) ? $label : false;
    $maxlength = isset( $maxlength ) ? $maxlength : false;
    $return    = isset( $return ) ? $return : '';

    // Get rid of all brackets
    if( strpos( "$name", '[' ) || strpos( "$name", ']' ) ) {
      $replace_variables = array( '][', ']', '[' );
      $class_from_name   = $name;
      $class_from_name   = 'wpp_' . str_replace( $replace_variables, '_', $class_from_name );
    } else {
      $class_from_name = 'wpp_' . $name;
    }

    // Setup Group
    if( $group ) {
      if( strpos( $group, '|' ) ) {
        $group_array  = explode( "|", $group );
        $count        = 0;
        $group_string = '';
        foreach( $group_array as $group_member ) {
          $count++;
          if( $count == 1 ) {
            $group_string .= "$group_member";
          } else {
            $group_string .= "[$group_member]";
          }
        }
      } else {
        $group_string = "$group";
      }
    }

    $id = ( $id ? $id : $class_from_name );

    $insert_id        = ( $id ? " id='$id' " : " id='$class_from_name' " );
    $insert_name      = ( $group_string ? " name='" . $group_string . "[$name]' " : " name=' wpp_$name' " );
    $insert_checked   = ( $checked ? " checked='true' " : " " );
    $insert_style     = ( $style ? " style='$style' " : " " );
    $insert_value     = ( $value ? $value : "" );
    $insert_class     = " class='$class_from_name input_textarea $class' ";
    $insert_maxlength = ( $maxlength ? " maxlength='$maxlength' " : " " );

    // Print label if one is set

    // Print checkbox
    $return .= "<textarea $insert_name $insert_id $insert_class $insert_checked $insert_maxlength $special $insert_style>$insert_value</textarea>";

    return $return;
  }

  /**
   * Shorthand function for drawing regular or hidden input fields.
   *
   * @since 1.36.0
   * @source WPP_F
   *
   * @param string      $args List of arguments to overwrite the defaults.
   * @param bool|string $value Value may be passed in arg array or seperately
   *
   * @return string Input field and hidden field with the opposive value
   */
  static public function input( $args = '', $value = false ) {
    $defaults = array( 'name' => '', 'group' => '', 'special' => '', 'value' => $value, 'title' => '', 'type' => 'text', 'class' => false, 'hidden' => false, 'style' => false, 'readonly' => false, 'label' => false );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
    $name     = isset( $name ) ? $name : '';
    $label    = isset( $label ) ? $label : false;
    $style    = isset( $style ) ? $style : false;
    $type     = isset( $type ) ? $type : 'text';
    $class    = isset( $class ) ? $class : false;
    $hidden   = isset( $hidden ) ? $hidden : false;
    $group    = isset( $group ) ? $group : '';
    $readonly = isset( $readonly ) ? $readonly : false;
    $special  = isset( $special ) ? $special : '';
    $title    = isset( $title ) ? $title : '';

    // Add prefix
    if( $class ) {
      $class = "wpp_$class";
    }

    // if [ character is present, we do not use the name in class and id field
    if( !strpos( "$name", '[' ) ) {
      $id              = $name;
      $class_from_name = $name;
    }

    $return = '';

    if( $label ) $return .= "<label for='$name'>";
    $return .= "<input " . ( $type ? "type=\"$type\" " : '' ) . " " . ( $style ? "style=\"$style\" " : '' ) . ( isset( $id ) ? "id=\"$id\" " : '' ) . " class=\"" . ( $type ? "" : "input_field " ) . ( isset( $class_from_name ) ? $class_from_name : '' ) . " $class " . ( $hidden ? " hidden " : '' ) . "" . ( $group ? "group_$group" : '' ) . " \"    name=\"" . ( $group ? $group . "[" . $name . "]" : $name ) . "\"   value=\"" . stripslashes( $value ) . "\"   title=\"$title\" $special " . ( $type == 'forget' ? " autocomplete='off'" : '' ) . " " . ( $readonly ? " readonly=\"readonly\" " : "" ) . " />";
    if( $label ) $return .= " $label </label>";

    return $return;
  }

  /**
   * Recursive conversion of an object into an array
   *
   * @since 1.36.0
   * @source WPP_F
   *
   */
  static public function objectToArray( $object ) {

    if( !is_object( $object ) && !is_array( $object ) ) {
      return $object;
    }

    if( is_object( $object ) ) {
      $object = get_object_vars( $object );
    }

    return array_map( array( 'WPP_F', 'objectToArray' ), $object );
  }

  /**
   * Get a URL of a page.
   *
   * @since 1.36.0
   * @source WPP_F
   *
   */
  static public function base_url( $page = '', $get = '' ) {
    global $wpdb, $wp_properties;

    $permalink           = '';
    $permalink_structure = get_option( 'permalink_structure' );

    //** Using Permalinks */
    if( '' != $permalink_structure ) {
      $page_id = false;
      if( !is_numeric( $page ) ) {
        $page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} where post_name = %s", $page ) );
      } else {
        $page_id = $page;
      }
      //** If the page doesn't exist, return default url ( base_slug ) */
      if( empty( $page_id ) ) {
        $permalink = home_url() . "/" . ( !is_numeric( $page ) ? $page : $wp_properties[ 'configuration' ][ 'base_slug' ] ) . '/';
      } else {
        $permalink = get_permalink( $page_id );
      }
    } //** Not using permalinks */
    else {
      //** If a slug is passed, convert it into ID */
      if( !is_numeric( $page ) ) {
        $page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} where post_name = %s AND post_status = 'publish' AND post_type = 'page'", $page ) );
        //* In case no actual page_id was found, we continue using non-numeric $page, it may be 'property' */
        if( !$page_id ) {
          $query = '?p=' . $page;
        } else {
          $query = '?page_id=' . $page_id;
        }
      } else {
        $page_id = $page;
        $query   = '?page_id=' . $page_id;
      }
      $permalink = home_url( $query );
    }

    //** Now set GET params */
    if( !empty( $get ) ) {
      $get = wp_parse_args( $get );
      $get = http_build_query( $get, '', '&' );
      $permalink .= ( strpos( $permalink, '?' ) === false ) ? '?' : '&';
      $permalink .= $get;
    }

    return $permalink;

  }

  /**
   * Fixes images permalinks in [gallery] shortcode
   *
   * @param string $output
   * @param int    $id
   * @param type   $size
   * @param type   $permalink
   * @param type   $icon
   * @param type   $text
   *
   * @return string
   * @author peshkov@UD
   * @since 1.37.6
   */
  static public function wp_get_attachment_link( $output, $id, $size, $permalink, $icon, $text ) {

    if( function_exists( 'debug_backtrace' ) && !is_admin() ) {
      $backtrace = debug_backtrace();
      foreach( (array) $backtrace as $f ) {
        if( $f[ 'function' ] === 'gallery_shortcode' ) {
          $link   = wp_get_attachment_url( $id );
          $output = preg_replace( '/href=[\",\'](.*?)[\",\']/', 'href=\'' . $link . '\'', $output );
          break;
        }
      }
    }

    return $output;
  }

  /**
   * Returns clear post status
   *
   * @author peshkov@UD
   * @version 0.1
   */
  static public function clear_post_status( $post_status = '', $ucfirst = true ) {
    switch( $post_status ) {
      case 'publish':
        $post_status = __( 'published', 'wpp' );
        break;
      case 'pending':
        $post_status = __( 'pending', 'wpp' );
        break;
      case 'trash':
        $post_status = __( 'trashed', 'wpp' );
        break;
      case 'inherit':
        $post_status = __( 'inherited', 'wpp' );
        break;
      case 'auto-draft':
        $post_status = __( 'drafted', 'wpp' );
        break;
    }
    return ( $ucfirst ? ucfirst( $post_status ) : $post_status );
  }
  
  /**
   * Sanitizes data.
   * Prevents shortcodes and XSS adding!
   *
   * @todo: remove the method since it's added in utility library.
   * @author peshkov@UD
   */
  static public function sanitize_request( $data ) {
    if( is_array( $data ) ) {
      foreach( $data as $k => $v ) {
        $data[ $k ] = self::sanitize_request( $v );
      }
    } else {
      $data = strip_shortcodes( $data );
      $data = filter_var( $data, FILTER_SANITIZE_STRING );
    }
    return $data;
  }

}

/**
 * Implementing this for old versions of PHP
 *
 * @since 1.15.9
 *
 */
if( !function_exists( 'array_fill_keys' ) ) {

  function array_fill_keys( $target, $value = '' ) {

    if( is_array( $target ) ) {

      foreach( $target as $key => $val ) {

        $filledArray[ $val ] = is_array( $value ) ? $value[ $key ] : $value;

      }

    }

    return $filledArray;

  }

}

/**
 * Delete a file or recursively delete a directory
 *
 * @param string  $str Path to file or directory
 * @param boolean $flag If false, doesn't remove root directory
 *
 * @version 0.1
 * @since 1.32.2
 * @author Maxim Peshkov
 */
if( !function_exists( 'wpp_recursive_unlink' ) ) {
  function wpp_recursive_unlink( $str, $flag = false ) {
    if( is_file( $str ) ) {
      return @unlink( $str );
    } elseif( is_dir( $str ) ) {
      $scan = glob( rtrim( $str, '/' ) . '/*' );
      foreach( $scan as $index => $path ) {
        wpp_recursive_unlink( $path, true );
      }
      if( $flag ) {
        return @rmdir( $str );
      } else {
        return true;
      }
    }
  }
}

/**
 * Add 'property' to the list of RSSable post_types.
 *
 * @param string $request
 *
 * @return string
 * @author korotkov@ud
 * @since 1.36.2
 */
if( !function_exists( 'property_feed' ) ) {
  function property_feed( $qv ) {

    if( isset( $qv[ 'feed' ] ) && !isset( $qv[ 'post_type' ] ) ) {
      $qv[ 'post_type' ] = get_post_types( $args = array(
        'public'   => true,
        '_builtin' => false
      ) );
      array_push( $qv[ 'post_type' ], 'post' );
    }

    return $qv;

  }
}
