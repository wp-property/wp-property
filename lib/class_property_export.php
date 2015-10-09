<?php
/**
 * This file holds the functionality that allows us to export our properties to an XML feed
 *
 * @since 1.4.2
 */
/** First we need to add our appropriate actions */
add_action( 'wpp_settings_help_tab', array( 'WPP_Export', 'help_tab' ), 10, 4 );
add_action( 'wp_ajax_wpp_export_properties', array( 'WPP_Export', 'wpp_export_properties' ) );
add_action( 'wp_ajax_nopriv_wpp_export_properties', array( 'WPP_Export', 'wpp_export_properties' ) );

/**
 * This is the actual object which peforms all of the functionality
 *
 * @todo: wpp_agents data should include agent data not just ID
 * @todo: Featured image is not being imported. Should be able to take from feed.
 *
 */
class WPP_Export {

  /**
   * This function shows help stuff on the properties settings help tab
   */
  static public function help_tab() {
    $export_url = WPP_Export::get_property_export_url();

    if ( !$export_url ) {
      return;
    }

    $export_url = $export_url . '&limit=10&format=json';
    ?>
    <div class="wpp_settings_block">
      <label for="wpp_export_url"><?php _e( 'Feed URL:', ud_get_wp_property()->domain ); ?></label>
      <input id="wpp_export_url" type="text" style="width: 70%" readonly="true" value="<?php echo esc_attr( $export_url ); ?>"/>
      <a class="button" href="<?php echo $export_url; ?>"><?php _e( 'Open', ud_get_wp_property()->domain ); ?></a>
      <br/><br/>
      <?php _e( 'You may append the export URL with the following arguments:', ud_get_wp_property()->domain ); ?>
      <ul style="margin: 15px 0 0 10px">
        <li><b>limit</b> - number</li>
        <li><b>per_page</b> - number</li>
        <li><b>starting_row</b> - number</li>
        <li><b>sort_order</b> - number</li>
        <li><b>sort_by</b> - number</li>
        <li><b>property_type</b> - string - <?php printf( __( 'Slug for the %s type.', ud_get_wp_property()->domain ), \WPP_F::property_label() ); ?></li>
        <li><b>format</b> - string - "xml" <?php _e( 'or', ud_get_wp_property()->domain ); ?> "json"</li>
      </ul>
      </li>
      </ul>
    </div> <?php
  }

  /**
   * This function generates your unique site's export feed
   *
   * @returns string URL to site's export feed
   */
  static public function get_property_export_url() {
    if ( $apikey = WPP_F::get_api_key() ) {
      if ( empty( $apikey ) )
        return __( "There has been an error retreiving your API key.", "wpp" );
      // We have the API key, we need to build the url
      return admin_url( 'admin-ajax.php' ) . "?action=wpp_export_properties&api=" . $apikey;
    }
    //return __("There has been an error retreiving your API key.", "wpp");
    return false;
  }

  /**
   * Converts arrays, objects, strings to XML object
   *
   * @see class XML_Serializer
   *
   * @param array $data
   * @param array $options Serializer options
   *
   * @return array|string|\WP_Error
   * @author peshkov@UD
   */
  static public function convert_to_xml( $data, $options ) {

    //** An array of serializer options */
    $options = wp_parse_args( $options, array(
      'indent' => " ",
      'linebreak' => "\n",
      'addDecl' => true,
      'encoding' => 'ISO-8859-1',
      'rootName' => 'objects',
      'defaultTagName' => 'object',
      'mode' => false
    ) );

    try {

      if ( !class_exists( 'XML_Serializer' ) ) {
        set_include_path( get_include_path() . PATH_SEPARATOR . WPP_Path . 'lib/third-party/XML/' );
        @require_once 'Serializer.php';
      }

      //** If class still doesn't exist, for whatever reason, we fail */
      if ( !class_exists( 'XML_Serializer' ) ) {
        throw new Exception( __( 'XML_Serializer could not be loaded.', 'pea' ) );
      }

      $Serializer = new XML_Serializer( $options );

      $status = $Serializer->serialize( $data );

      if ( PEAR::isError( $status ) ) {
        throw new Exception( __( 'Could not convert data to XML.', 'pea' ) );
      }

      $data = $Serializer->getSerializedData();

    } catch ( Exception $e ) {
      return new WP_Error( 'error', $e->getMessage() );
    }

    return $data;
  }

  /**
   * This function takes all your properties and exports it as an XML feed
   *
   * @todo Improve efficiency of function, times out quickly for feeds of 500 properties. memory_limit and set_time_limit should be removed once efficiency is improved
   *
   */
  static public function wpp_export_properties() {
    global $wp_properties;

    ini_set( 'memory_limit', -1 );

    $mtime = microtime();
    $mtime = explode( " ", $mtime );
    $mtime = $mtime[ 1 ] + $mtime[ 0 ];
    $starttime = $mtime;

    // Set a new path
    set_include_path( get_include_path() . PATH_SEPARATOR . WPP_Path . 'lib/third-party/XML/' );
    // Include our necessary libaries
    require_once 'Serializer.php';
    require_once 'Unserializer.php';

    $api_key = WPP_F::get_api_key();

    $taxonomies = $wp_properties[ 'taxonomies' ];

    // If the API key isn't valid, we quit
    if ( !isset( $_REQUEST[ 'api' ] ) || $_REQUEST[ 'api' ] != $api_key ) {
      die( __( 'Invalid API key.', ud_get_wp_property()->domain ) );
    }

    if ( isset( $_REQUEST[ 'limit' ] ) ) {
      $per_page = $_REQUEST[ 'limit' ];
      $starting_row = 0;
    }

    if ( isset( $_REQUEST[ 'per_page' ] ) ) {
      $per_page = $_REQUEST[ 'per_page' ];
    }

    if ( isset( $_REQUEST[ 'starting_row' ] ) ) {
      $starting_row = $_REQUEST[ 'starting_row' ];
    }

    if ( isset( $_REQUEST[ 'property_type' ] ) ) {
      $property_type = $_REQUEST[ 'property_type' ];
    } else {
      $property_type = 'all';
    }

    if ( strtolower( $_REQUEST[ 'format' ] ) == 'xml' ) {
      $xml_format = true;
    } else {
      $xml_format = false;
    }

    $wpp_query[ 'query' ][ 'pagi' ] = $starting_row . '--' . $per_page;
    $wpp_query[ 'query' ][ 'sort_by' ] = ( isset( $_REQUEST[ 'sort_by' ] ) ? $_REQUEST[ 'sort_by' ] : 'post_date' );
    $wpp_query[ 'query' ][ 'sort_order' ] = ( isset( $_REQUEST[ 'sort_order' ] ) ? $_REQUEST[ 'sort_order' ] : 'ASC' );
    $wpp_query[ 'query' ][ 'property_type' ] = $property_type;

    $wpp_query[ 'query' ] = apply_filters( 'wpp::xml::export::query', $wpp_query[ 'query' ] );

    $wpp_query = WPP_F::get_properties( $wpp_query[ 'query' ], true );

    $results = $wpp_query[ 'results' ];

    if ( count( $results ) == 0 ) {
      die( __( 'No published properties.', ud_get_wp_property()->domain ) );
    }

    if ( $xml_format ) {

    } else {

    }

    $properties = array();

    foreach ( $results as $count => $id ) {

      //** Reserve time on every iteration. */
      set_time_limit( 0 );

      $property = WPP_F::get_property( $id, "return_object=false&load_parent=false&get_children=false" );

      if ( $property[ 'post_parent' ] && !$property[ 'parent_gpid' ] ) {
        $property[ 'parent_gpid' ] = WPP_F::maybe_set_gpid( $property[ 'post_parent' ] );
      }

      // Unset unnecessary data
      unset(
      $property[ 'comment_count' ],
      $property[ 'post_modified_gmt' ],
      $property[ 'comment_status' ],
      $property[ 'post_password' ],
      $property[ 'guid' ],
      $property[ 'filter' ],
      $property[ 'post_author' ],
      $property[ 'permalink' ],
      $property[ 'ping_status' ],
      $property[ 'post_modified' ],
      $property[ 'post_mime_type' ]
      );

      // Set unique site ID
      $property[ 'wpp_unique_id' ] = md5( $api_key . $property[ 'ID' ] );

      //** Get taxonomies */
      if ( $taxonomies ) {
        foreach ( $taxonomies as $taxonomy_slug => $taxonomy_data ) {
          if ( $these_terms = wp_get_object_terms( $property[ 'ID' ], $taxonomy_slug, array( 'fields' => 'names' ) ) ) {
            $property[ 'taxonomies' ][ $taxonomy_slug ] = $these_terms;
          }
        }
      }

      $fixed_property = array();
      foreach ( $property as $meta_key => $meta_value ) {
        // Maybe Unserialize
        $meta_value = maybe_unserialize( $meta_value );
        if ( is_array( $meta_value ) || is_object( $meta_value ) ) {
          $fixed_property[ $meta_key ] = $meta_value;
          continue;
        }
        $fixed_property[ $meta_key ] = $meta_value;
      }
      $properties[ $id ] = $fixed_property;

    }

    $properties = apply_filters( 'wpp::xml::export::data', $properties );

    if ( $xml_format ) {
      $result = self::convert_to_xml( $properties, apply_filters( 'wpp::xml::export::serializer_options', array() ) );

      /** Deprecated. peshkov@UD
      $result = json_encode( $properties );
      $result = WPP_F::json_to_xml( $result, apply_filters( 'wpp::xml::export::serializer_options', array() ) );
      //*/

      if ( !$result ) {
        die( __( 'There is an Error on trying to create XML feed.', ud_get_wp_property()->domain ) );
      }
      header( 'Content-type: text/xml' );
      header( 'Content-Disposition: inline; filename="wpp_xml_data.xml"' );
    } else {
      $result = json_encode( $properties );
      header( 'Content-type: application/json' );
      header( 'Content-Disposition: inline; filename="wpp_xml_data.json"' );
    }

    header( "Cache-Control: no-cache" );
    header( "Pragma: no-cache" );

    die( $result );
  }

}